<?php
/**
 * Plugin Name: AVH Event Manager Permalinks
 * Plugin URI:  
 * Description: Create better permalink for Event Manager
 * Version:     0.1.0
 * Author:      Peter van der Does
 * Author URI:  
 * License:     GPLv2+
 * Text Domain: avh-em-permalinks
 * Domain Path: /languages
 */

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

defined( 'WPINC' ) or die;

include( dirname( __FILE__ ) . '/lib/requirements-check.php' );

$avh_em_permalinks_requirements_check = new avh_em_permalinks_Requirements_Check( array(
	'title' => 'AVH Event Manager Permalinks',
	'php'   => '5.4',
	'wp'    => '4.1',
	'file'  => __FILE__,
));

if ( $avh_em_permalinks_requirements_check->passes() ) {
	// Pull in the plugin classes and initialize
	include( dirname( __FILE__ ) . '/lib/wp-stack-plugin.php' );
	include( dirname( __FILE__ ) . '/classes/plugin.php' );
	avh_em_permalinks_Plugin::start( __FILE__ );
}

unset( $avh_em_permalinks_requirements_check );
