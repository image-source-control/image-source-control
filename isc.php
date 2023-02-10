<?php
/**
 * Plugin Name: Image Source Control Lite
 * Version: 2.10.0
 * Plugin URI: https://imagesourcecontrol.com/
 * Description: Image Source Control saves the source of an image, lists them and warns if it is missing.
 * Author: Thomas Maier
 * Author URI: https://imagesourcecontrol.com/
 * License: GPL v3
 *
 * Image Source Control Plugin for WordPress
 * Copyright (C) 2012-2022, Thomas Maier & webgilde GmbH
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

if ( class_exists( 'ISC_Class', false ) ) {
	die( 'You can only activate Image Source Control once. Please disable the other version first.' );
}

define( 'ISCVERSION', '2.10.0' );
define( 'ISCNAME', 'Image Source Control' );
define( 'ISCDIR', basename( dirname( __FILE__ ) ) );
define( 'ISCPATH', plugin_dir_path( __FILE__ ) );
define( 'ISCBASE', plugin_basename( __FILE__ ) ); // plugin base as used by WordPress to identify it.
define( 'ISCBASEURL', plugin_dir_url( __FILE__ ) ); // URL to the plugin directory

require_once ISCPATH . 'isc.class.php';
require_once ISCPATH . 'includes/model.php';
require_once ISCPATH . 'includes/storage-model.php';
require_once ISCPATH . 'includes/log.php';

if ( is_admin() ) {
	if ( ! class_exists( 'ISC_Admin', false ) ) {
		require_once ISCPATH . 'admin/admin.php';
	}
	new ISC_Admin();
} elseif ( ! is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	// include frontend functions
	if ( ! class_exists( 'ISC_Public', false ) ) {
		require_once ISCPATH . 'public/public.php';
	}
	new ISC_Public();
	require_once ISCPATH . 'functions.php';
} else {
	new ISC_Class();
}

if ( ! class_exists( 'ISC_Pro_Model', false ) && file_exists( ISCPATH . 'pro/isc-pro.php' ) ) {
	require_once ISCPATH . 'pro/isc-pro.php';
}

require_once ISCPATH . 'includes/block-options/block-options.php';