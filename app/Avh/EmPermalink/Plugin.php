<?php

namespace Avh\EmPermalink;

use Illuminate\Config\Repository;

/**
 * Class Plugin
 *
 * @package   Avh\EmPermalink
 * @author    Peter van der Does <peter@avirtualhome.com>
 * @copyright Copyright (c) 2015, AVH Software
 */
class Plugin
{
    /**
     * Constructor.
     *
     * @param string $dir
     * @param string $basename
     */
    public function __construct($dir, $basename)
    {
        $this->app = new Application();

        $this->registerBindings();

        $this->settings = $this->app->make('Settings');
        $this->settings->set('plugin_dir', $dir);
        $this->settings->set('plugin_file', $basename);

        if (!defined('WP_INSTALLING') || WP_INSTALLING === false) {
            add_action('plugins_loaded', [$this, 'load']);
        }
    }

    public function actionInit()
    {
        $this->loadTextdomain('avh-em-permalinks', '/languages');
    }

    /**
     * Run after the plugins are loaded.
     *
     */
    public function load()
    {
        add_action('init', [$this, 'registerPostTypes'], 11);
        add_action('init', [$this, 'actionInit'], 10);
        $this->setSettings();
        $this->removeEventManagerActionFilter();
        new HandlePermalinks();
        if (is_admin()) {
            add_action('activate_' . $this->settings->get('plugin_basename'), [$this, 'pluginActivation']);
            add_action('deactivate_' . $this->settings->get('plugin_basename'), [$this, 'pluginDeactivation']);
        } else {
            new FrontEnd($this->app);
        }
    }

    /**
     * Load the text domain.
     *
     * @param string $name
     * @param string $path
     *
     * @return bool
     */
    public function loadTextdomain($name, $path)
    {
        return load_plugin_textdomain($name, false, $this->settings->get('plugin_dir') . $path);
    }

    /**
     * Runs after we activate the plugin.
     *
     * @internal Hook: activate_
     *
     */
    public function pluginActivation()
    {
        flush_rewrite_rules();
    }

    /**
     * Runs after we deactivate the plugin.
     *
     * @internal Hook: deactivate_
     *
     */
    public function pluginDeactivation()
    {
        flush_rewrite_rules();
    }

    /**
     * Setup the bindings.
     *
     */
    public function registerBindings()
    {
        /**
         * Setup Singleton classes
         *
         */
        $this->app->singleton(
            'Settings',
            function () {
                return new Repository();
            }
        )
        ;
    }

    /**
     * Reregister the Events custom post types.
     *
     * We'll be using a new rewrite functionality, making it possible to use our own placeholders.
     */
    public function registerPostTypes()
    {
        // We assume the admin url is absolute with at least one querystring
        define('EM_ADMIN_URL', admin_url() . 'edit.php?post_type=' . EM_POST_TYPE_EVENT);
        if (get_option('dbem_tags_enabled', true)) {
            register_taxonomy(
                EM_TAXONOMY_TAG,
                [EM_POST_TYPE_EVENT, 'event-recurring'],
                [
                    'hierarchical'   => false,
                    'public'         => true,
                    'show_ui'        => true,
                    'query_var'      => true,
                    'rewrite'        => false,
                    'label'          => __('Event Tags'),
                    'singular_label' => __('Event Tag'),
                    'labels'         => [
                        'name'                       => __('Event Tags', 'dbem'),
                        'singular_name'              => __('Event Tag', 'dbem'),
                        'search_items'               => __('Search Event Tags', 'dbem'),
                        'popular_items'              => __('Popular Event Tags', 'dbem'),
                        'all_items'                  => __('All Event Tags', 'dbem'),
                        'parent_items'               => __('Parent Event Tags', 'dbem'),
                        'parent_item_colon'          => __('Parent Event Tag:', 'dbem'),
                        'edit_item'                  => __('Edit Event Tag', 'dbem'),
                        'update_item'                => __('Update Event Tag', 'dbem'),
                        'add_new_item'               => __('Add New Event Tag', 'dbem'),
                        'new_item_name'              => __('New Event Tag Name', 'dbem'),
                        'seperate_items_with_commas' => __(
                            'Seperate event tags with commas',
                            'dbem'
                        ),
                        'add_or_remove_items'        => __('Add or remove events', 'dbem'),
                        'choose_from_the_most_used'  => __(
                            'Choose from most used event tags',
                            'dbem'
                        )
                    ],
                    'capabilities'   => [
                        'manage_terms' => 'edit_event_categories',
                        'edit_terms'   => 'edit_event_categories',
                        'delete_terms' => 'delete_event_categories',
                        'assign_terms' => 'edit_events'
                    ]
                ]
            );
        }
        if (get_option('dbem_categories_enabled', true)) {
            $supported_array = (EM_MS_GLOBAL && !is_main_site()) ? [] : [EM_POST_TYPE_EVENT, 'event-recurring'];
            register_taxonomy(
                EM_TAXONOMY_CATEGORY,
                $supported_array,
                [
                    'hierarchical'   => true,
                    'public'         => true,
                    'show_ui'        => true,
                    'query_var'      => true,
                    'rewrite'        => false,
                    'label'          => __('Event Categories', 'dbem'),
                    'singular_label' => __('Event Category', 'dbem'),
                    'labels'         => [
                        'name'                       => __('Event Categories', 'dbem'),
                        'singular_name'              => __('Event Category', 'dbem'),
                        'search_items'               => __('Search Event Categories', 'dbem'),
                        'popular_items'              => __('Popular Event Categories', 'dbem'),
                        'all_items'                  => __('All Event Categories', 'dbem'),
                        'parent_items'               => __('Parent Event Categories', 'dbem'),
                        'parent_item_colon'          => __('Parent Event Category:', 'dbem'),
                        'edit_item'                  => __('Edit Event Category', 'dbem'),
                        'update_item'                => __('Update Event Category', 'dbem'),
                        'add_new_item'               => __('Add New Event Category', 'dbem'),
                        'new_item_name'              => __('New Event Category Name', 'dbem'),
                        'seperate_items_with_commas' => __(
                            'Seperate event categories with commas',
                            'dbem'
                        ),
                        'add_or_remove_items'        => __('Add or remove events', 'dbem'),
                        'choose_from_the_most_used'  => __(
                            'Choose from most used event categories',
                            'dbem'
                        )
                    ],
                    'capabilities'   => [
                        'manage_terms' => 'edit_event_categories',
                        'edit_terms'   => 'edit_event_categories',
                        'delete_terms' => 'delete_event_categories',
                        'assign_terms' => 'edit_events'
                    ]
                ]
            );
        }
        $event_post_type = [
            'public'              => true,
            'hierarchical'        => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'exclude_from_search' => !get_option('dbem_cp_events_search_results'),
            'publicly_queryable'  => true,
            'rewrite'             => false,
            'has_archive'         => get_option('dbem_cp_events_has_archive', false) == true,
            'supports'            => apply_filters(
                'em_cp_event_supports',
                [
                    'custom-fields',
                    'title',
                    'editor',
                    'excerpt',
                    'comments',
                    'thumbnail',
                    'author'
                ]
            ),
            'capability_type'     => 'event',
            'capabilities'        => [
                'publish_posts'       => 'publish_events',
                'edit_posts'          => 'edit_events',
                'edit_others_posts'   => 'edit_others_events',
                'delete_posts'        => 'delete_events',
                'delete_others_posts' => 'delete_others_events',
                'read_private_posts'  => 'read_private_events',
                'edit_post'           => 'edit_event',
                'delete_post'         => 'delete_event',
                'read_post'           => 'read_event'
            ],
            'label'               => __('Events', 'dbem'),
            'description'         => __('Display events on your blog.', 'dbem'),
            'labels'              => [
                'name'               => __('Events', 'dbem'),
                'singular_name'      => __('Event', 'dbem'),
                'menu_name'          => __('Events', 'dbem'),
                'add_new'            => __('Add Event', 'dbem'),
                'add_new_item'       => __('Add New Event', 'dbem'),
                'edit'               => __('Edit', 'dbem'),
                'edit_item'          => __('Edit Event', 'dbem'),
                'new_item'           => __('New Event', 'dbem'),
                'view'               => __('View', 'dbem'),
                'view_item'          => __('View Event', 'dbem'),
                'search_items'       => __('Search Events', 'dbem'),
                'not_found'          => __('No Events Found', 'dbem'),
                'not_found_in_trash' => __('No Events Found in Trash', 'dbem'),
                'parent'             => __('Parent Event', 'dbem')
            ],
            'menu_icon'           => EM_DIR_URI.'includes/images/calendar-16.png',
            'yarpp_support'       => true
        ];
        if (get_option('dbem_recurrence_enabled')) {
            $event_recurring_post_type = [
                'public'              => apply_filters('em_cp_event_recurring_public', false),
                'show_ui'             => true,
                'show_in_admin_bar'   => true,
                'show_in_menu'        => 'edit.php?post_type=' . EM_POST_TYPE_EVENT,
                'show_in_nav_menus'   => false,
                'publicly_queryable'  => apply_filters(
                    'em_cp_event_recurring_publicly_queryable',
                    false
                ),
                'exclude_from_search' => true,
                'has_archive'         => false,
                'can_export'          => true,
                'hierarchical'        => false,
                'supports'            => apply_filters(
                    'em_cp_event_supports',
                    [
                        'custom-fields',
                        'title',
                        'editor',
                        'excerpt',
                        'comments',
                        'thumbnail',
                        'author'
                    ]
                ),
                'capability_type'     => 'recurring_events',
                'rewrite'             => false,
                'capabilities'        => [
                    'publish_posts'       => 'publish_recurring_events',
                    'edit_posts'          => 'edit_recurring_events',
                    'edit_others_posts'   => 'edit_others_recurring_events',
                    'delete_posts'        => 'delete_recurring_events',
                    'delete_others_posts' => 'delete_others_recurring_events',
                    'read_private_posts'  => 'read_private_recurring_events',
                    'edit_post'           => 'edit_recurring_event',
                    'delete_post'         => 'delete_recurring_event',
                    'read_post'           => 'read_recurring_event'
                ],
                'label'               => __('Recurring Events', 'dbem'),
                'description'         => __('Recurring Events Template', 'dbem'),
                'labels'              => [
                    'name'               => __('Recurring Events', 'dbem'),
                    'singular_name'      => __('Recurring Event', 'dbem'),
                    'menu_name'          => __('Recurring Events', 'dbem'),
                    'add_new'            => __(
                        'Add Recurring Event',
                        'dbem'
                    ),
                    'add_new_item'       => __(
                        'Add New Recurring Event',
                        'dbem'
                    ),
                    'edit'               => __('Edit', 'dbem'),
                    'edit_item'          => __(
                        'Edit Recurring Event',
                        'dbem'
                    ),
                    'new_item'           => __(
                        'New Recurring Event',
                        'dbem'
                    ),
                    'view'               => __('View', 'dbem'),
                    'view_item'          => __(
                        'Add Recurring Event',
                        'dbem'
                    ),
                    'search_items'       => __(
                        'Search Recurring Events',
                        'dbem'
                    ),
                    'not_found'          => __(
                        'No Recurring Events Found',
                        'dbem'
                    ),
                    'not_found_in_trash' => __(
                        'No Recurring Events Found in Trash',
                        'dbem'
                    ),
                    'parent'             => __(
                        'Parent Recurring Event',
                        'dbem'
                    )
                ]
            ];
        }
        if (get_option('dbem_locations_enabled', true)) {
            $location_post_type = [
                'public'              => true,
                'hierarchical'        => false,
                'show_in_admin_bar'   => true,
                'show_ui'             => !(EM_MS_GLOBAL && !is_main_site() && get_site_option(
                        'dbem_ms_mainblog_locations'
                    )),
                'show_in_menu'        => 'edit.php?post_type=' . EM_POST_TYPE_EVENT,
                'show_in_nav_menus'   => true,
                'can_export'          => true,
                'exclude_from_search' => !get_option('dbem_cp_locations_search_results'),
                'publicly_queryable'  => true,
                'rewrite'             => false,
                'query_var'           => true,
                'has_archive'         => get_option('dbem_cp_locations_has_archive', false) == true,
                'supports'            => apply_filters(
                    'em_cp_location_supports',
                    [
                        'title',
                        'editor',
                        'excerpt',
                        'custom-fields',
                        'comments',
                        'thumbnail',
                        'author'
                    ]
                ),
                'capability_type'     => 'location',
                'capabilities'        => [
                    'publish_posts'       => 'publish_locations',
                    'delete_others_posts' => 'delete_others_locations',
                    'delete_posts'        => 'delete_locations',
                    'delete_post'         => 'delete_location',
                    'edit_others_posts'   => 'edit_others_locations',
                    'edit_posts'          => 'edit_locations',
                    'edit_post'           => 'edit_location',
                    'read_private_posts'  => 'read_private_locations',
                    'read_post'           => 'read_location'
                ],
                'label'               => __('Locations', 'dbem'),
                'description'         => __('Display locations on your blog.', 'dbem'),
                'labels'              => [
                    'name'               => __('Locations', 'dbem'),
                    'singular_name'      => __('Location', 'dbem'),
                    'menu_name'          => __('Locations', 'dbem'),
                    'add_new'            => __('Add Location', 'dbem'),
                    'add_new_item'       => __('Add New Location', 'dbem'),
                    'edit'               => __('Edit', 'dbem'),
                    'edit_item'          => __('Edit Location', 'dbem'),
                    'new_item'           => __('New Location', 'dbem'),
                    'view'               => __('View', 'dbem'),
                    'view_item'          => __('View Location', 'dbem'),
                    'search_items'       => __('Search Locations', 'dbem'),
                    'not_found'          => __('No Locations Found', 'dbem'),
                    'not_found_in_trash' => __(
                        'No Locations Found in Trash',
                        'dbem'
                    ),
                    'parent'             => __('Parent Location', 'dbem')
                ],
                'yarpp_support'       => true
            ];
        }
        if (strstr(EM_POST_TYPE_EVENT_SLUG, EM_POST_TYPE_LOCATION_SLUG) !== false) {
            // Now register posts, but check slugs in case of conflicts and reorder registrations
            register_post_type(EM_POST_TYPE_EVENT, $event_post_type);
            if (get_option('dbem_recurrence_enabled')) {
                register_post_type('event-recurring', $event_recurring_post_type);
            }
            if (get_option('dbem_locations_enabled', true)) {
                register_post_type(EM_POST_TYPE_LOCATION, $location_post_type);
            }
        } else {
            if (get_option('dbem_locations_enabled', true)) {
                register_post_type(EM_POST_TYPE_LOCATION, $location_post_type);
            }
            register_post_type(EM_POST_TYPE_EVENT, $event_post_type);
            // Now register posts, but check slugs in case of conflicts and reorder registrations
            if (get_option('dbem_recurrence_enabled')) {
                register_post_type('event-recurring', $event_recurring_post_type);
            }
        }
    }

    /**
     * Remove the actions and filters that Event Manager calls.
     * Event Manager will NOT:
     * - Create Custom Post Type.
     * - Create Custom Taxonomies.
     * - Setup the rewrite rules.
     *
     */
    private function removeEventManagerActionFilter()
    {
        remove_action('init', 'wp_events_plugin_init', 1);
        remove_filter('rewrite_rules_array', ['EM_Permalinks', 'rewrite_rules_array']);
    }

    /**
     * Set the required settings to be used throughout the plugin
     */
    private function setSettings()
    {
        $dir = $this->settings->get('plugin_dir');
        $basename = $this->settings->get('plugin_file');
        $upload_dir_info = wp_upload_dir();

        $this->settings->set('plugin_basename', $basename);
        $this->settings->set('upload_dir', $upload_dir_info['basedir'] . '/avh-em-permalink');
        $this->settings->set('javascript_dir', $dir . '/js/');
        $this->settings->set('css_dir', $dir . '/css/');
        $this->settings->set('images_dir', $dir . '/images/');
        $this->settings->set('plugin_url', plugins_url('', $basename));

        $this->settings->set('siteurl', get_option('siteurl'));
    }
}
