<?php
/**
 * Plugin Name: Image Source Control Lite
 * Version: 3.1.1
 * Plugin URI: https://imagesourcecontrol.com/
 * Description: Image Source Control saves the source of an image, lists them and warns if it is missing.
 * Author: Thomas Maier
 * Author URI: https://imagesourcecontrol.com/
 * License: GPL v3
 *
 * Image Source Control Plugin for WordPress
 * Copyright (C) 2012-2025, Thomas Maier & webgilde GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( defined( 'ISCVERSION' ) ) {
	wp_die( 'You can only activate Image Source Control once. Please disable the other version first.' );
}

define( 'ISCVERSION', '3.1.1' );
define( 'ISCNAME', 'Image Source Control' );
define( 'ISCDIR', basename( __DIR__ ) );
define( 'ISCPATH', plugin_dir_path( __FILE__ ) );
define( 'ISCBASE', plugin_basename( __FILE__ ) ); // plugin base as used by WordPress to identify it.
define( 'ISCBASEURL', plugin_dir_url( __FILE__ ) ); // URL to the plugin directory.

// Load the autoloader.
require_once ISCPATH . 'includes/class-autoloader.php';
\ISC\Autoloader::get()->initialize();

if ( is_admin() ) {
	new ISC\Admin();
} elseif ( ! is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	// include frontend functions.
	new ISC_Public();
	require_once ISCPATH . 'includes/functions.php';
}

// deprecated. Added here for backward compatibility. Will be removed in future versions.
new ISC_Class();

if ( ! class_exists( 'ISC_Pro_Model', false ) && file_exists( ISCPATH . 'pro/isc-pro.php' ) ) {
	require_once ISCPATH . 'pro/isc-pro.php';
}

new ISC_Block_Options();
