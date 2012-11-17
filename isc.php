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

            add_filter('attachment_fields_to_edit', array(&$this, 'add_isc_fields'), 10, 2);
            add_filter('attachment_fields_to_save', array(&$this, 'isc_fields_save'), 10, 2);
            
            add_action('admin_menu', array( $this, 'create_menu') );
            
            add_shortcode('isc_list', array($this, 'list_post_attachments_with_sources_shortcode'));
        }
        
        /**
         * create the menu pages for isc
         */
        public function create_menu () {
            
            // this page should be accessable by editors and higher
            $menuhook = add_submenu_page( 'upload.php', 'missing image sources by Image Source Control Plugin', __('Missing Sources', ISCTEXTDOMAIN), 'edit_others_posts', ISCPATH . '/templates/missing_sources.php', '' );
            
        }

        /**
         * add custom field to attachment
         * @param arr $form_fields
         * @param object $post
         * @return arr
         */
        public function add_isc_fields($form_fields, $post) {
            // add input field for source
            $form_fields['image_source']['label'] = __('Image Source', ISCTEXTDOMAIN);
            $form_fields['image_source']['value'] = get_post_meta($post->ID, '_image_source', true);
            $form_fields['image_source']['helps'] = __('Include the image source here.', ISCTEXTDOMAIN);

            // add checkbox to mark as your own image
            $form_fields['image_source_own']['input'] = 'html';
            $form_fields['image_source_own']['helps'] = __('Check this box if this is your own image and doesn\'t need a source.', ISCTEXTDOMAIN);
            $form_fields['image_source_own']['html'] = "<input type='checkbox' value='1' name='attachments[{$post->ID}][image_source_own]' id='attachments[{$post->ID}][image_source_own]' " . checked(get_post_meta($post->ID, '_image_source_own', true), 1) . "/> "
                    . __('This is my image', ISCTEXTDOMAIN);

            return $form_fields;
        }

        /**
         * save image source to post_meta
         * @param object $post
         * @param $attachment
         * @return object $post
         */
        public function isc_fields_save($post, $attachment) {
            if (isset($attachment['image_source']))
                update_post_meta($post['ID'], '_image_source', $attachment['image_source']);
            update_post_meta($post['ID'], '_image_source_own', $attachment['image_source_own']);
            return $post;
        }

        /**
         * create image sources list for all images of this post
         * @param int $post_id id of the current post/page
         * @return echo output
         */
        public function list_post_attachments_with_sources( $post_id = 0 ) {

            if (empty($post_id)) {
                global $post;
                if (!empty($post->ID)) {
                    $post_id = $post->ID;
                }
            }

            if (empty($post_id))
                return;

            $attachments = get_children(array(
                'post_parent' => $post_id,
                'post_type' => 'attachment',
                'numberposts' => -1, // show all
                'post_status' => 'inherit',
                'post_mime_type' => 'image',
                'order' => 'ASC',
                'orderby' => 'menu_order ASC'
                    ));

            $return = '';
            if (!empty($attachments)) :
                ob_start();
                ?>
                <p class="isc_image_list_title"><?php _e('image sources:', ISCTEXTDOMAIN); ?></p>
                <ul class="isc_image_list"><?php
                foreach ($attachments as $attachment_id => $attachment) :
                    ?><li><?php
                    echo $attachment->post_title . ': ';
                    if ( get_post_meta($attachment_id, '_image_source_own', true) ) {
                        _e('by the author', ISCTEXTDOMAIN);
                    } else {
                        echo get_post_meta($attachment_id, '_image_source', true);
                    }
                    ?></li><?php
                endforeach;
                ?></ul><?php
                $return = ob_get_clean();
            endif;
            
            return $return;
        }
        
        /**
         * shortcode function to list all image sources
         * @param arr $atts
         */
        public function list_post_attachments_with_sources_shortcode ( $atts = array() ) {
            
            extract( shortcode_atts( array(
		'id' => 0,
            ), $atts ) );
            
            // if $id not set, use the current ID from the post
            if ( empty( $id )) {
                global $post;
                $id = $post->ID;
            }
            
            if ( empty( $id )) return;
            return $this->list_post_attachments_with_sources( $id );
            
        }

    }

    function add_image_source_fields_start() {

        new ISC_CLASS();
    
    }

    add_action('plugins_loaded', 'add_image_source_fields_start');

    /**
     * the next functions are just to have an easier access from outside the class
     */
    function isc_list($post_id = 0) {

        echo ISC_CLASS::list_post_attachments_with_sources($post_id);
    }

}

