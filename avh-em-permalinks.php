<?php
/**
 * Plugin Name: AVH Event Manager Permalinks
 * Plugin URI:
 * Description: Create better permalink for Event Manager
 * Version: 0.1.2-dev.1
 * Author:      Peter van der Does
 * Author URI:
 * License:     GPLv2+
 * Text Domain: avh-em-permalinks
 * Domain Path: /languages
 */
use Avh\EmPermalink\Plugin;
use Avh\EmPermalink\RequirementsCheck;

/**
 * Copyright (c) 2015 Peter van der Does (email : peter@avirtualhome.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Register The Composer Auto Loader
 * Composer provides a convenient, automatically generated class loader
 * for our application. We just need to utilize it! We'll require it
 * into the script here so that we do not have to worry about the
 * loading of any our classes "manually". Feels great to relax.
 */
require __DIR__ . '/vendor/autoload.php';

$avh_em_permalinks_requirements_check = new RequirementsCheck(
    [
        'title' => 'AVH Event Manager Permalinks',
        'php'   => '5.4',
        'wp'    => '4.1',
        'file'  => __FILE__,
    ]
);

if ($avh_em_permalinks_requirements_check->passes()) {
    $plugin_dir = pathinfo($plugin, PATHINFO_DIRNAME);
    $plugin_basename = plugin_basename($plugin);
    new Plugin($plugin_dir, $plugin_basename);
}

unset($avh_em_permalinks_requirements_check);
