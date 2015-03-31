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
        add_action('init', [$this, 'actionInit'], 10);
        $this->setSettings();
        $this->removeEventManagerActionFilter();
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
