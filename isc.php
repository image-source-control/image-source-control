<?php
/*
  Plugin Name: Image Source Control
  Version: 1.8.11.2
  Plugin URI: http://webgilde.com/en/image-source-control/
  Description: The Image Source Control saves the source of an image, lists them and warns if it is missing.
  Author: Thomas Maier
  Author URI: http://webgilde.com/
  License: GPL v3

  Image Source Control Plugin for WordPress
  Copyright (C) 2012-2015, Thomas Maier - thomas.maier@webgilde.com

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Followed the following tutorials
 * http://wpengineer.com/2076/add-custom-field-attachment-in-wordpress/
 * http://bueltge.de/eigene-felder-dateiverwaltung-wordpress/1226/ (same like above, but in German)
 *
 *
 */

//avoid direct calls to this file
if ( ! function_exists( 'add_action' ) ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit();
}

define( 'ISCVERSION', '1.8.11.2' );
define( 'ISCNAME', 'Image Source Control' );
define( 'ISCTEXTDOMAIN', 'isc' );
define( 'ISCDIR', basename( dirname( __FILE__ ) ) );
define( 'ISCPATH', plugin_dir_path( __FILE__ ) );
define( 'WEBGILDE', 'http://webgilde.com/en/image-source-control' );

load_plugin_textdomain( ISCTEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

if ( ! class_exists('ISC_Class')) {
    require_once ISCPATH . 'isc.class.php' ;
}

if ( is_admin() ) {
    if ( ! class_exists( 'ISC_Admin' ) ) {
        require_once ISCPATH . 'admin/admin.php' ;
    }
    new ISC_Admin;
} elseif (!is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )) {
    // include frontend functions
    if ( ! class_exists( 'ISC_Public' ) ) {
        require_once ISCPATH . 'public/public.php';
    }
    new ISC_Public;
    require_once ISCPATH . 'functions.php';
} else {
    new ISC_Class;
}
