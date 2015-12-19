<?php
namespace Avh\EmPermalink;

/**
 * Class RequirementsCheck
 *
 * @package   Avh\EmPermalink
 * @author    Peter van der Does <peter@avirtualhome.com>
 * @copyright Copyright (c) 2015, AVH Software
 */
class RequirementsCheck
{
    private $file;
    private $missing_dependencies = [];
    private $pass_dependency      = false;
    private $php                  = '5.2.4';
    private $title                = '';
    private $wp                   = '3.8';

    /**
     * Constructor
     *
     * @param array $args
     */
    public function __construct($args = [])
    {
        foreach (['title', 'php', 'wp', 'file'] as $setting) {
            if (isset($args[$setting])) {
                $this->$setting = $args[$setting];
            }
        }
    }

    /**
     * Check for dependency based on a named constant.
     *
     * @param string $define
     * @param string $plugin
     *
     * @return bool
     */
    public function checkDependencyDefined($define, $plugin)
    {
        if (!defined($define)) {
            add_action('admin_notices', [$this, 'displayDependencyNotice']);
            add_action('admin_notices', [$this, 'deactivate']);
            $this->removeActivatedNotice();
            $this->missing_dependencies[] = $plugin;

            return false;
        }
        $this->pass_dependency = true;

        return true;
    }

    /**
     * Deactivate the plugin.
     */
    public function deactivate()
    {
        if (isset($this->file)) {
            deactivate_plugins(plugin_basename($this->file));
        }
    }

    /**
     * Display a notice when the plugin is missing de version is incompatible
     */
    public function displayDependencyNotice()
    {
        echo '<div class="error">';
        echo '<p>The &#8220;' . esc_html($this->title) . '&#8221; plugin is missing depedencies.</p>';
        if (count($this->missing_dependencies) > 1) {
            $message = 'The following plugins are missing: ';
        } else {
            $message = 'The following plugin is missing: ';
        }
        echo '<p>' . $message . esc_html(implode(', ', $this->missing_dependencies)) . '</p>';
        echo '</div>';
    }

    /**
     * Display a notice when the PHP version is incompatible
     */
    public function displayPhpVersionNotice()
    {
        echo '<div class="error">';
        echo '<p>The &#8220;' .
             esc_html($this->title) .
             '&#8221; plugin cannot run on PHP versions older than ' .
             $this->php .
             '. Please contact your host and ask them to upgrade.</p>';
        echo '</div>';
    }

    /**
     * Display a notice when the WordPress version is incompatible
     */
    public function displayWordPressVersionNotice()
    {
        echo '<div class="error">';
        echo '<p>The &#8220;' .
             esc_html($this->title) .
             '&#8221; plugin cannot run on WordPress versions older than ' .
             $this->wp .
             '. Please update WordPress.</p>';
        echo '</div>';
    }

    /**
     * @return boolean
     */
    public function hasDependencies()
    {
        return $this->pass_dependency;
    }

    /**
     * Check is the plugin passes the set requirements.
     *
     * @return bool
     */
    public function passes()
    {
        $passes = $this->checkPhpPasses() && $this->checkWordPressPasses();
        if (!$passes) {
            add_action('admin_notices', [$this, 'deactivate']);
        }

        return $passes;
    }

    /**
     * Check if running PHP version is equal or later as the given version.
     *
     * @param string $min_version
     *
     * @return mixed
     */
    private function checkPhpAtLeast($min_version)
    {
        return version_compare(phpversion(), $min_version, '>=');
    }

    /**
     * Check if the running PHP version passes.
     * If it doesn't pass add action to display the PHP notice
     *
     * @return bool
     */
    private function checkPhpPasses()
    {
        if ($this->checkPhpAtLeast($this->php)) {
            return true;
        } else {
            add_action('admin_notices', [$this, 'displayPhpVersionNotice']);

            return false;
        }
    }

    /**
     * Check if the running WordPress version is equal or later as the given version.
     *
     * @param string $min_version
     *
     * @return mixed
     */
    private function checkWordPressAtLeast($min_version)
    {
        return version_compare(get_bloginfo('version'), $min_version, '>=');
    }

    /**
     * Check if the running WordPress version passes.
     * If it doesn't pass add action to display the WordPress notice
     *
     * @return bool
     */
    private function checkWordPressPasses()
    {
        if ($this->checkWordPressAtLeast($this->wp)) {
            return true;
        } else {
            add_action('admin_notices', [$this, 'displayWordPressVersionNotice']);

            return false;
        }
    }

    /**
     * Removes the notice that a plugin is activated.
     */
    private function removeActivatedNotice()
    {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
}
