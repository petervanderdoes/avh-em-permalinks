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
    private $php = '5.2.4';
    private $title = '';
    private $wp = '3.8';

    public function __construct($args)
    {
        foreach (['title', 'php', 'wp', 'file'] as $setting) {
            if (isset($args[$setting])) {
                $this->$setting = $args[$setting];
            }
        }
    }

    public function deactivate()
    {
        if (isset($this->file)) {
            deactivate_plugins(plugin_basename($this->file));
        }
    }

    public function displayPhpVersionNotice()
    {
        echo '<div class="error">';
        echo "<p>The &#8220;" . esc_html(
                $this->title
            ) . "&#8221; plugin cannot run on PHP versions older than " . $this->php . '. Please contact your host and ask them to upgrade.</p>';
        echo '</div>';
    }

    public function displayWordPressVersionNotice()
    {
        echo '<div class="error">';
        echo "<p>The &#8220;" . esc_html(
                $this->title
            ) . "&#8221; plugin cannot run on WordPress versions older than " . $this->wp . '. Please update WordPress.</p>';
        echo '</div>';
    }

    public function passes()
    {
        $passes = $this->checkPhpPasses() && $this->checkWordPressPasses();
        if (!$passes) {
            add_action('admin_notices', [$this, 'deactivate']);
        }

        return $passes;
    }

    private static function checkPhpAtLeast($min_version)
    {
        return version_compare(phpversion(), $min_version, '>=');
    }

    private static function checkWordPressAtLeast($min_version)
    {
        return version_compare(get_bloginfo('version'), $min_version, '>=');
    }

    private function checkPhpPasses()
    {
        if ($this->checkPhpAtLeast($this->php)) {
            return true;
        } else {
            add_action('admin_notices', [$this, 'displayPhpVersionNotice']);

            return false;
        }
    }

    private function checkWordPressPasses()
    {
        if ($this->checkWordPressAtLeast($this->wp)) {
            return true;
        } else {
            add_action('admin_notices', [$this, 'displayWordPressVersionNotice']);

            return false;
        }
    }
}
