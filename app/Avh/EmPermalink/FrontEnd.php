<?php

namespace Avh\EmPermalink;

/**
 * Class FrontEnd
 *
 * @package   Avh\EmPermalink
 * @author    Peter van der Does <peter@avirtualhome.com>
 * @copyright Copyright (c) 2015, AVH Software
 */
class FrontEnd
{
    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->setActionFilters();
    }

    /**
     * Rewrite the permalink for the custom post types.
     *
     * @param string   $post_link The post's permalink.
     * @param \WP_Post $post      The post in question.
     * @param bool     $leavename Whether to keep the post name.
     * @param bool     $sample    Is it a sample permalink.
     *
     * @return mixed|string|void
     */
    public function filterPermalink($post_link, $post, $leavename, $sample)
    {
        switch ($post->post_type) {
            case EM_POST_TYPE_EVENT:

                $EM_Event = em_get_event($post->ID, $search_by = 'post_id');
                $rewritecode_wordpress = [
                    '%year%',
                    '%monthnum%',
                    '%day%',
                    '%hour%',
                    '%minute%',
                    '%second%',
                    '%category%'
                ];
                $rewritecode_events = [
                    '%event_year%',
                    '%event_monthnum%',
                    '%event_day%',
                    '%event_hour%',
                    '%event_minute%',
                    '%event_second%',
                    '%event_owner%',
                    '%event_location%',
                    '%event_name%'
                ];
                $rewritecode = array_merge($rewritecode_wordpress, $rewritecode_events);

                if ('' != $post_link && !in_array($EM_Event->post_status, ['draft', 'pending', 'auto-draft'])) {
                    $unixtime = strtotime($EM_Event->post_date);
                    $unixtime_start = strtotime($EM_Event->event_start_date . ' ' . $EM_Event->event_start_time);

                    $category = '';
                    if (strpos($post_link, '%category%') !== false) {

                        $EM_Categories = $EM_Event->get_categories();
                        if ($EM_Categories->categories) {
                            usort($EM_Categories->categories, '_usort_terms_by_ID'); // order by ID
                            $category_object = $EM_Categories->categories[0];
                            $category_object = get_term($category_object, EM_TAXONOMY_CATEGORY);
                            $category = $category_object->slug;
                            if (isset($category_object->parent)) {
                                $parent = $category_object->parent;
                                $category = rps_EM_get_parents(
                                        $parent,
                                        false,
                                        '/',
                                        true,
                                        [],
                                        EM_TAXONOMY_CATEGORY
                                    ) . $category;
                            }
                        }
                    }

                    $eventlocation = '';
                    if (strpos($post_link, '%event_location%') !== false) {
                        $EM_Location = em_get_location($EM_Event->location_id);
                        $eventlocation = $EM_Location->location_slug;
                    }
                    $author = '';
                    if (strpos($post_link, '%event_owner%') !== false) {
                        $authordata = get_userdata($EM_Event->event_owner);
                        $author = $authordata->user_nicename;
                    }
                    if (strpos($post_link, '%event_name%') !== false) {
                        $event_name = $EM_Event->event_slug;
                    }

                    $date = explode(" ", date('Y m d H i s', $unixtime));
                    $rewritereplace_wordpress = [$date[0], $date[1], $date[2], $date[3], $date[4], $date[5], $category];

                    $date = explode(" ", date('Y m d H i s', $unixtime_start));
                    $rewritereplace_event = [
                        $date[0],
                        $date[1],
                        $date[2],
                        $date[3],
                        $date[4],
                        $date[5],
                        $author,
                        $eventlocation,
                        $event_name
                    ];

                    $rewritereplace = array_merge($rewritereplace_wordpress, $rewritereplace_event);
                    $post_link = str_replace($rewritecode, $rewritereplace, $post_link);
                    $post_link = user_trailingslashit($post_link, 'single');
                } else { // if they're not using the fancy permalink option
                    $post_link = home_url('?event=' . $EM_Event->event_slug);
                }
                break;

            case EM_POST_TYPE_LOCATION:
                $EM_Location = em_get_location($post->ID, $search_by = 'post_id');
                $rewritecode_wordpress = ['%year%', '%monthnum%', '%day%', '%hour%', '%minute%', '%second%'];
                $rewritecode_events = ['%location_name%'];
                $rewritecode = array_merge($rewritecode_wordpress, $rewritecode_events);

                if ('' != $post_link && !in_array($post->post_status, ['draft', 'pending', 'auto-draft'])) {
                    $unixtime = strtotime($EM_Location->post_date);
                    $date = explode(" ", date('Y m d H i s', $unixtime));

                    $rewritereplace_wordpress = [$date[0], $date[1], $date[2], $date[3], $date[4], $date[5]];
                    $rewritereplace_locations = [$EM_Location->location_slug];

                    $rewritereplace = array_merge($rewritereplace_wordpress, $rewritereplace_locations);
                    $post_link = str_replace($rewritecode, $rewritereplace, $post_link);
                    $post_link = user_trailingslashit($post_link, 'single');
                } else { // if they're not using the fancy permalink option
                    $post_link = home_url('?p=' . $EM_Location->location_id);
                }
                break;
        }

        return $post_link;
    }

    /**
     * Rewrite the permalink for taxonomies.
     *
     * @param string $termlink Term link URL.
     * @param object $term     Term object.
     * @param string $taxonomy Taxonomy slug.
     *
     * @return mixed|string
     */
    public function filterTermLink($termlink, $term, $taxonomy)
    {
        switch ($taxonomy) {
            case EM_TAXONOMY_CATEGORY:
                if (strpos($termlink, '%category_name%') !== false) {
                    $rewritecode = ['%category_name%'];
                    $rewritereplace = [$term->slug];
                    $termlink = str_replace($rewritecode, $rewritereplace, $termlink);
                    $termlink = user_trailingslashit($termlink, 'single');
                }
        }

        return $termlink;
    }

    /**
     * Setup permalinks
     *
     * Create new placeholders and add the default permalink
     */
    public function generateRewriteRules()
    {
        /**
         * Rewrite tags that can be used in permalink structures.
         *
         * These are translated into the regular expressions stored in
         * {@link WP_Rewrite::$rewritereplace} and are rewritten to the
         * query variables listed in {@link WP_Rewrite::$queryreplace}.
         *
         * @var array
         */
        $rewritecode = [
            '%event_year%',
            '%event_monthnum%',
            '%event_day%',
            '%event_hour%',
            '%event_minute%',
            '%event_second%',
            '%event_name%',
            '%event_owner%',
            '%event_location%',
            '%location_name%',
            '%category_name%'
        ];

        /**
         * Regular expressions to be substituted into rewrite rules in place
         * of rewrite tags, see {@link WP_Rewrite::$rewritecode}.
         *
         * @var array
         */
        $rewritereplace = [
            '([0-9]{4})',
            '([0-9]{1,2})',
            '([0-9]{1,2})',
            '([0-9]{1,2})',
            '([0-9]{1,2})',
            '([0-9]{1,2})',
            '([^/]+)',
            '([^/]+)',
            '([^/]+)',
            '([^/]+)',
            '([^/]+)'
        ];

        /**
         * Query variables that rewrite tags map to, see {@link WP_Rewrite::$rewritecode}.
         *
         * @var array
         */
        $queryreplace = [
            'event_year=',
            'event_monthnum=',
            'event_day=',
            'event_hour=',
            'event_minute=',
            'event_second=',
            EM_POST_TYPE_EVENT . '=',
            'event_owner=',
            'location=',
            EM_POST_TYPE_LOCATION . '=',
            EM_TAXONOMY_CATEGORY . '='
        ];
        /**
         * Add new rewrite placeholders
         */
        foreach ($rewritecode as $index => $placeholder) {
            $regex = $rewritereplace[$index];
            $query_var = $queryreplace[$index];
            add_rewrite_tag($placeholder, $regex, $query_var);
        }

        add_permastruct(
            EM_POST_TYPE_EVENT,
            get_option('dbem_cp_events_slug', EM_POST_TYPE_EVENT_SLUG),
            ['with_front' => false]
        );
        add_permastruct(
            EM_POST_TYPE_LOCATION,
            get_option('dbem_cp_locations_slug', EM_POST_TYPE_LOCATION_SLUG),
            ['with_front' => false]
        );
        add_permastruct(
            EM_TAXONOMY_CATEGORY,
            get_option('dbem_taxonomy_category_slug', EM_TAXONOMY_CATEGORY_SLUG) . '/%category_name%',
            ['hierarchical' => true, 'with_front' => false]
        );

        flush_rewrite_rules();
    }

    /**
     * Parse the query vars.
     *
     * Some of it has already been parsed by Event Manager, we just add our added query vars.
     *
     */
    function parseQuery()
    {
        global $wp_query;
        if (!is_admin()) {
            if (!empty($wp_query->query_vars['post_type']) && ($wp_query->query_vars['post_type'] == EM_POST_TYPE_EVENT || $wp_query->query_vars['post_type'] == 'event-recurring') && (empty($wp_query->query_vars['post_status']) || !in_array(
                        $wp_query->query_vars['post_status'],
                        ['trash', 'pending', 'draft']
                    ))
            ) {
                if (isset($wp_query->query_vars['meta_query'])) {
                    $query = $wp_query->query_vars['meta_query'];
                } else {
                    $query = [];
                }
                $year = date('Y');
                $month = '01';
                $day = '01';
                $is_date = false;
                if (isset ($wp_query->query_vars['event_year'])) {
                    $is_date = true;
                    $year = $wp_query->query_vars['event_year'];
                    $start_date = new \DateTime($year . '/01/01');
                    $end_date = new \DateTime($year . '/12/31');
                }
                if (isset ($wp_query->query_vars['event_month'])) {
                    $is_date = true;
                    $month = $wp_query->query_vars['event_month'];
                    $start_date = new \DateTime($year . '/' . $month . '/01');
                    $end_date = new \DateTime($year . '/' . $month . '/31');
                }
                if (isset ($wp_query->query_vars['event_day'])) {
                    $is_date = true;
                    $day = $wp_query->query_vars['event_day'];
                    $start_date = new \DateTime($year . '/' . $month . '/' . $day);
                    $end_date = new \DateTime($year . '/' . $month . '/' . $day);
                }
                if ($is_date) {
                    $check_date_start = $start_date->format('U');
                    $check_date_end = $end_date->format('U');
                    $query[] = ['key' => '_start_ts', 'value' => $check_date_start, 'compare' => '>='];
                    $query[] = ['key' => '_start_ts', 'value' => $check_date_end, 'compare' => '<'];
                }

                if (!empty($query) && is_array($query)) {
                    $wp_query->query_vars['meta_query'] = $query;
                }
            }
        }
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
            'menu_icon'           => plugins_url('includes/images/calendar-16.png', __FILE__),
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
     * Setup actions and filters.
     *
     */
    public function setActionFilters()
    {
        add_action('init', [$this, 'generateRewriteRules'], 10);
        add_action('init', [$this, 'registerPostTypes'], 11);
        add_filter('query_vars', [$this, 'setupQueryVars'], 10, 1);
        add_action('parse_query', [$this, 'parseQuery'], 999);
        add_filter('post_type_link', [$this, 'filterPermalink'], 10, 4);
        add_filter('term_link', [$this, 'filterTermLink'], 10, 3);
    }

    /**
     * Add extra query vars
     *
     * @param array $vars
     *
     * @return array
     */
    public function setupQueryVars($vars)
    {
        $vars[] = 'event_year';

        return $vars;
    }
}
