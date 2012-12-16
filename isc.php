<?php
/*
  Plugin Name: Image Source Control
  Version: 1.1
  Plugin URI: none
  Description: The Image Source Control saves the source of an image, lists them and warns if it is missing.
  Author: Thomas Maier
  Author URI: http://www.webgilde.com
  License: GPL v3

  Image Source Control Plugin for WordPress
  Copyright (C) 2012, Thomas Maier - thomas.maier@webgilde.com

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

define('ISCVERSION', '1.1');
define('ISCNAME', 'Image Source Control');
define('ISCTEXTDOMAIN', 'isc');
define('ISCDIR', basename(dirname(__FILE__)));
define('ISCPATH', plugin_dir_path(__FILE__));

load_plugin_textdomain(ISCTEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');

if (!class_exists('ISC_CLASS')) {

    class ISC_CLASS {

        /**
         * define default meta fields
         */
        var $_fields = array(
            'image_source' => array(
                'id' => 'isc_image_source',
                'default' => '',
            ),
            'image_source_own' => array(
                'id' => 'isc_image_source_own',
                'default' => '',
            )
        );

        /**
         * allowed image file types/extensions
         * @since 1.1
         */
        var $_allowedExtensions = array(
            'jpg', 'png', 'gif'
        );

        public function __construct() {

            if (!current_user_can('upload_files'))
                return FALSE;

            add_filter('attachment_fields_to_edit', array(&$this, 'add_isc_fields'), 10, 2);
            add_filter('attachment_fields_to_save', array(&$this, 'isc_fields_save'), 10, 2);

            add_action('admin_menu', array($this, 'create_menu'));

            add_shortcode('isc_list', array($this, 'list_post_attachments_with_sources_shortcode'));

            add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));

            // ajax function; 'add_meta_fields' is the action defined in isc.js as the action to be called via ajax
            add_action('wp_ajax_add_meta_fields', array($this, 'add_meta_values_to_attachments'));

            // save image information in meta field when a post is saved
            add_action('save_post', array($this, 'save_image_information_on_post_save'));
        }

        /**
         * create the menu pages for isc
         */
        public function create_menu() {

            // this page should be accessable by editors and higher
            $menuhook = add_submenu_page('upload.php', 'missing image sources by Image Source Control Plugin', __('Missing Sources', ISCTEXTDOMAIN), 'edit_others_posts', ISCPATH . '/templates/missing_sources.php', '');
        }

        /**
         * add scripts to admin pages
         */
        public function add_admin_scripts($hook) {
            if ('wg-image-source-control/templates/missing_sources.php' != $hook)
                return;
            wp_enqueue_script('isc_script', plugins_url('/js/isc.js', __FILE__), false, ISCVERSION);
            // this is to define ajaxurl to be able to use this in its own js script
            // wp_localize_script( 'isc_script', 'IscAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        }

        /**
         * add custom field to attachment
         * @param arr $form_fields
         * @param object $post
         * @return arr
         * @since 1.0
         * @updated 1.1
         */
        public function add_isc_fields($form_fields, $post) {
            // add input field for source
            $form_fields['isc_image_source']['label'] = __('Image Source', ISCTEXTDOMAIN);
            $form_fields['isc_image_source']['value'] = get_post_meta($post->ID, 'isc_image_source', true);
            $form_fields['isc_image_source']['helps'] = __('Include the image source here.', ISCTEXTDOMAIN);
            
            // add checkbox to mark as your own image
            $form_fields['isc_image_source_own']['input'] = 'html';
            $form_fields['isc_image_source_own']['helps'] = __('Check this box if this is your own image and doesn\'t need a source.', ISCTEXTDOMAIN);
            $form_fields['isc_image_source_own']['html'] = "<input type='checkbox' value='1' name='attachments[{$post->ID}][isc_image_source_own]' id='attachments[{$post->ID}][isc_image_source_own]' " . checked(get_post_meta($post->ID, 'isc_image_source_own', true), 1, false ) .  "/> "
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

            if (isset($attachment['isc_image_source']))
                update_post_meta($post['ID'], 'isc_image_source', $attachment['isc_image_source']);

            update_post_meta($post['ID'], 'isc_image_source_own', $attachment['isc_image_source_own']);
            return $post;
        }

        /**
         * create image sources list for all images of this post
         * @since 1.0
         * @update 1.1
         * @param int $post_id id of the current post/page
         * @return echo output
         */
        public function list_post_attachments_with_sources($post_id = 0) {

            if (empty($post_id)) {
                global $post;
                if (!empty($post->ID)) {
                    $post_id = $post->ID;
                }
            }

            if (empty($post_id))
                return;

            $attachments = get_post_meta($post_id, 'isc_post_images', true);
            // if attachments is an empty string, search for images in it
            if ( $attachments == '' ) { 
                $this->save_image_information_on_load ($post_id, $_content);
                $attachments = get_post_meta($post_id, 'isc_post_images', true);
            }
            
            $return = '';
            if (!empty($attachments)) :
                ob_start();
                ?>
                <p class="isc_image_list_title"><?php _e('image sources:', ISCTEXTDOMAIN); ?></p>
                <ul class="isc_image_list"><?php
                $atts = array();
                foreach ($attachments as $attachment_id => $attachment_array) {
                    
                    $atts[ $attachment_id ]['title'] = get_the_title($attachment_id);
                    $own = get_post_meta($attachment_id, 'isc_image_source_own', true);
                    $source = get_post_meta($attachment_id, 'isc_image_source', true);
                    
                    if ( $own == '' AND $source == '' ) {
                        // remove if no information set
                        unset( $atts[ $attachment_id ] );
                        continue;
                    } elseif ( $own != '' ) {
                        $atts[ $attachment_id ]['source'] = __('by the author', ISCTEXTDOMAIN);
                    } else {
                        $atts[ $attachment_id ]['source'] = $source;
                    }
                    
                }
                
                foreach ($atts as $atts_id => $atts_array) :
                    if ( empty( $atts_array['source'] ) ) continue;
                    ?><li><?php echo $atts_array['title'] . ': ' . $atts_array['source']; ?></li><?php
                endforeach;
                ?></ul><?php
                $return = ob_get_clean();

            endif;
            
            // don't display anything, if no image sources displayed
            if ( count( $atts ) === 0 ) return;

            return $return;
        }

        /**
         * shortcode function to list all image sources
         * @param arr $atts
         */
        public function list_post_attachments_with_sources_shortcode($atts = array()) {

            extract(shortcode_atts(array(
                        'id' => 0,
                            ), $atts));

            // if $id not set, use the current ID from the post
            if (empty($id)) {
                global $post;
                $id = $post->ID;
            }

            if (empty($id))
                return;
            return $this->list_post_attachments_with_sources($id);
        }

        /**
         * get all attachments without sources
         * the downside of this function: is there is not even an empty metakey field, nothing is going to be retrieved
         * @todo fix this in WP 3.5 with compare => 'NOT EXISTS'
         */
        public function get_attachments_without_sources() {

            $args = array(
                'post_type' => 'attachment',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => null,
                'meta_query' => array(
                    // image source is empty
                    array(
                        'key' => 'isc_image_source',
                        'value' => '',
                        'compare' => '=',
                    ),
                    // and image source is not set
                    array(
                        'key' => 'isc_image_source_own',
                        'value' => '1',
                        'compare' => '!=',
                    ),
                )
            );
            $attachments = get_posts($args);
            if (!empty($attachments)) {
                return $attachments;
            }
        }

        /**
         * add meta values to all attachments
         * @todo probably need to fix this when more fields are added along the way
         * @todo use compare => 'NOT EXISTS' when WP 3.5 is up to retrieve only values where it is not set
         * @todo this currently updates all empty fields; empty in this context is empty string, 0, false or not existing; add check if meta field already existed before
         */
        public function add_meta_values_to_attachments() {

            // retrieve all attachments
            $args = array(
                'post_type' => 'attachment',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => null,
            );

            $attachments = get_posts($args);
            if (empty($attachments))
                return;

            $count = 0;
            foreach ($attachments as $_attachment) {
                $set = 0;
                setup_postdata($_attachment);
                foreach ($this->_fields as $_field) {
                    $meta = get_post_meta($_attachment->ID, $_field['id'], true);
                    if (empty($meta)) {
                        update_post_meta($_attachment->ID, $_field['id'], $_field['default']);
                        $set = 1;
                    }
                }
                if ($set)
                    $count++;
            }
            echo sprintf(__('Added meta fields to %d images.', ISCTEXTDOMAIN), $count);
            die();
        }

        /**
         * show the loading image from wp-admin/images/loading.gif
         * @param bool $display should this be displayed directly or hidden? via inline css
         */
        public function show_loading_image($display = true) {

            $img_path = admin_url("/images/loading.gif");
            $file_path = ABSPATH . "wp-admin/images/loading.gif";
            if (file_exists($file_path)) {
                echo '<span id="isc_loading_img" style="display: none;"><img src="' . $img_path . '" width="16" height="16" alt="loading"/></span>';
            }
        }

        /**
         * this is an entry function to save image information to a post when it is saved
         * @since 1.1
         * @param type $post_id
         */
        public function save_image_information_on_post_save($post_id) {

            // return, if save_post is called more than one time
            if (did_action('save_post') !== 1)
                return;

            if ('attachment' == $_POST['post_type']) {
                return;
            }
            
            // check if this is a revision and if so, use parent post id
            if ($_id = wp_is_post_revision($post_id))
                $post_id = $_id;
            
            $_content = stripslashes($_REQUEST['content']);
            $this->save_image_information($post_id, $_content);
            
        }
        
        /**
         * save image information for a post when it is viewed and the image source list is enabled
         * (this is in case the plugin is new and the current post wasn't saved before)
         * 
         * @since 1.1
         */
        
        public function save_image_information_on_load( ) {
            
            global $post;
            if (empty( $post->ID )) return;

            $post_id = $post->ID;
            $_content = $post->post_content;

            $this->save_image_information($post_id, $_content);
            
        }

        /**
         * retrieve images added to a post or page and save all information as a meta value
         * @since 1.1
         * @todo check for more post types that maybe should not be parsed here
         */
        public function save_image_information( $post_id, $_content ) {

            $_image_urls = $this->_filter_src_attributes($_content);

            $_imgs = array();

            foreach ($_image_urls as $_image_url) {
                // get ID of images by url
                $img_id = $this->get_image_by_url($_image_url);
                $_imgs[$img_id] = array(
                    'src' => $_image_url
                );
            }

            // add thumbnail information
            $thumb_id = get_post_thumbnail_id($post_id);
            $_imgs[$thumb_id] = array(
                'src' => wp_get_attachment_url($thumb_id),
                'thumbnail' => true
            );

            if (empty($_imgs))
                $_imgs = false;
            update_post_meta($post_id, 'isc_post_images', $_imgs);
        }

        /**
         * filter image src attribute from text
         * @since 1.1
         * @return array with image src uris
         */
        public function _filter_src_attributes($content = '') {

            $srcs = array();
            if (empty($content))
                return $srcs;

            // parse HTML with DOM
            $dom = new DOMDocument;
            $dom->loadHTML($content);
            foreach ($dom->getElementsByTagName('img') as $node) {
                $srcs[] = $node->getAttribute('src');
            }

            return $srcs;
        }

        /**
         * get image by url accessing the database directly
         * @since 1.1
         * @param string $url url of the image
         * @return id of the image
         */
        public function get_image_by_url($url = '') {

            if (empty($url))
                return 0;
            $types = implode('|', $this->_allowedExtensions);
            // check for the format 'image-title-300x200.jpg' and remove the image size from it
            $newurl = preg_replace("/-(\d+)x(\d+)\.({$types})$/i", '.${3}', $url);
            global $wpdb;
            $query = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE guid = %s", $newurl);
            $id = $wpdb->get_var($query);
            return $id;
        }

    }

    function add_image_source_fields_start() {

        $isc = new ISC_CLASS();
    }

    add_action('plugins_loaded', 'add_image_source_fields_start');

    /**
     * the next functions are just to have an easier access from outside the class
     */
    function isc_list($post_id = 0) {

        $isc = new ISC_CLASS();
        echo $isc->list_post_attachments_with_sources($post_id);
        
    }

}

