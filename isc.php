<?php

/*
  Plugin Name: Image Source Control
  Version: 0.1
  Plugin URI: none
  Description: The Image Source Control saves the source of an image, lists them and warns if it is missing.
  Author: Thomas Maier
  Author URI: http://www.webzunft.de
  License: GPL v3

  Image Source Control Plugin for WordPress
  Copyright (C) 2012, Thomas Maier - post@webzunft.de

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
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

define('ISCVERSION', '0.1');
define('ISCNAME', 'Image Source Control');
define('ISCTEXTDOMAIN', 'isc');
define('ISCDIR', basename(dirname(__FILE__)));
define('ISCPATH', plugin_dir_path(__FILE__));

load_plugin_textdomain(ISCTEXTDOMAIN, false, ISCPATH . '/lang');

if (!class_exists('ISC_CLASS')) {

    class ISC_CLASS {

        public function __construct() {

            if (!current_user_can('upload_files'))
                return FALSE;

            add_filter('attachment_fields_to_edit', array(&$this, 'add_image_source_fields'), 10, 2);
            add_filter('attachment_fields_to_save', array(&$this, 'image_source_fields_save'), 10, 2);
        }

        /**
         * add custom field to attachment
         * @param arr $form_fields
         * @param object $post
         * @return arr
         */
        public function add_image_source_fields($form_fields, $post) {
            $form_fields['image_source']['label'] = __('Image Source', ISCTEXTDOMAIN);
            $form_fields['image_source']['value'] = get_post_meta($post->ID, '_image_source', true);
            $form_fields['image_source']['helps'] = __('Include the image source here.', ISCTEXTDOMAIN);
            return $form_fields;
        }

        /**
         * save image source to post_meta
         * @param object $post
         * @param $attachment
         * @return object $post
         */
        public function image_source_fields_save($post, $attachment) {
            if (isset($attachment['image_source']))
                update_post_meta($post['ID'], '_image_source', $attachment['image_source']);
            return $post;
        }

        /**
         * get image source for frontend
         */
        /*public function source() {

            $attachments = get_children(array(
                'post_parent' => get_the_ID(),
                'post_type' => 'attachment',
                'numberposts' => 1, // show all -1
                'post_status' => 'inherit',
                'post_mime_type' => 'image',
                'order' => 'ASC',
                'orderby' => 'menu_order ASC'
                    ));
            foreach ($attachments as $attachment_id => $attachment) {
                echo get_post_meta($attachment_id, '_custom_example', true);
            }
        }*/

    }

    function add_image_source_fields_start() {

        new ISC_CLASS();
    }

    add_action('plugins_loaded', 'add_image_source_fields_start');
}