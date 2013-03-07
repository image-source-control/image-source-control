<?php
/*
  Plugin Name: Image Source Control
  Version: 1.2
  Plugin URI: http://webgilde.com/en/image-source-control/
  Description: The Image Source Control saves the source of an image, lists them and warns if it is missing.
  Author: Thomas Maier
  Author URI: http://www.webgilde.com/
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

define('ISCVERSION', '1.2');
define('ISCNAME', 'Image Source Control');
define('ISCTEXTDOMAIN', 'isc');
define('ISCDIR', basename(dirname(__FILE__)));
define('ISCPATH', plugin_dir_path(__FILE__));
define('WEBGILDE', 'http://webgilde.com/en/image-source-control');

load_plugin_textdomain(ISCTEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');

if (!class_exists('ISC_CLASS')) {

    class ISC_CLASS
    {
        /**
         * define default meta fields
         */
        protected $_fields = array(
            'image_source' => array(
                'id' => 'isc_image_source',
                'default' => '',
            ),
            'image_source_own' => array(
                'id' => 'isc_image_source_own',
                'default' => '',
            ),
            'image_posts' => array(
                'id' => 'isc_image_posts',
                'default' => array()
            )
        );

        /**
         * allowed image file types/extensions
         * @since 1.1
         */
        protected $_allowedExtensions = array(
            'jpg', 'png', 'gif'
        );
        
        protected $_upgrade_step = array(
            '1.2'
        );
        
        /**
        * Thumbnail size in list of all images.
        */
        protected $_thumbnail_size = array('thumbnail', 'medium', 'large', 'custom');
        
        /**
         * options saved in the db
         * @since 1.2
         */
        protected $_options = array();

        /**
         * Setup registers filterts and actions.
         */
        public function __construct()
        {
            
            // load all plugin options
            $this->_options = get_option('isc_options');
            
            // insert all function for the frontend here
            
            add_shortcode('isc_list', array($this, 'list_post_attachments_with_sources_shortcode'));
            add_shortcode('isc_list_all', array($this, 'list_all_post_attachments_sources_shortcode'));
            
            // insert all backend functions below this check
            if (!current_user_can('upload_files')) {
                return false;
            }
            
            register_activation_hook(ISCPATH . '/isc.php', array($this, 'activation'));
            
            add_filter('attachment_fields_to_edit', array($this, 'add_isc_fields'), 10, 2);
            add_filter('attachment_fields_to_save', array($this, 'isc_fields_save'), 10, 2);
            
            add_action('admin_menu', array($this, 'create_menu'));
            add_action('admin_init', array($this, 'SAPI_init'));
            
            add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));
            
            // ajax function; 'add_meta_fields' is the action defined in isc.js as the action to be called via ajax
            add_action('wp_ajax_add_meta_fields', array($this, 'add_meta_values_to_attachments'));

            // save image information in meta field when a post is saved
            add_action('save_post', array($this, 'save_image_information_on_post_save'));
        }

        /**
         * create the menu pages for isc
         */
        public function create_menu()
        {
            global $isc_missing;
            global $isc_setting;
            
            /**
            * Check if the page is already created.
            */
            if (empty($isc_missing)) {
                // these pages should be accessible by editors and higher
                $isc_missing = add_submenu_page('upload.php', 'missing image sources by Image Source Control Plugin', __('Missing Sources', ISCTEXTDOMAIN), 'edit_others_posts', ISCPATH . '/templates/missing_sources.php', '');
                $isc_setting = add_options_page(__('Image control - ISC plugin', ISCTEXTDOMAIN), __('Image Control', ISCTEXTDOMAIN), 'edit_others_posts', 'isc_settings_page', array($this, 'render_isc_settings_page'));
            }
        }

        /**
         * add scripts to admin pages
         * @since 1.0
         * @update 1.1.1
         */
        public function add_admin_scripts($hook)
        {
            global $isc_setting;
            if ($hook != $isc_setting) {
                return;
            }
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
        public function add_isc_fields($form_fields, $post)
        {
            // add input field for source
            $form_fields['isc_image_source']['label'] = __('Image Source', ISCTEXTDOMAIN);
            $form_fields['isc_image_source']['value'] = get_post_meta($post->ID, 'isc_image_source', true);
            $form_fields['isc_image_source']['helps'] = __('Include the image source here.', ISCTEXTDOMAIN);

            // add checkbox to mark as your own image
            $form_fields['isc_image_source_own']['input'] = 'html';
            $form_fields['isc_image_source_own']['label'] = '';
            $form_fields['isc_image_source_own']['helps'] =
                __('Check this box if this is your own image and doesn\'t need a source.', ISCTEXTDOMAIN);
            $form_fields['isc_image_source_own']['html'] =
                "<input type='checkbox' value='1' name='attachments[{$post->ID}][isc_image_source_own]' id='attachments[{$post->ID}][isc_image_source_own]' "
                . checked(get_post_meta($post->ID, 'isc_image_source_own', true), 1, false )
                . " style=\"width:14px\"/> "
                . __('This is my image', ISCTEXTDOMAIN);

            return $form_fields;
        }

        /**
         * save image source to post_meta
         * @param object $post
         * @param $attachment
         * @return object $post
         */
        public function isc_fields_save($post, $attachment)
        {
            if (isset($attachment['isc_image_source'])) {
                update_post_meta($post['ID'], 'isc_image_source', $attachment['isc_image_source']);
            }

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
        public function list_post_attachments_with_sources($post_id = 0)
        {
            global $post;

            if (empty($post_id)) {
                if (!empty($post->ID)) {
                    $post_id = $post->ID;
                } else {
                    return;
                }
            }

            $attachments = get_post_meta($post_id, 'isc_post_images', true);
            // if attachments is an empty string, search for images in it
            if ($attachments == '') {
                $this->save_image_information_on_load();
                $this->update_image_posts_meta($post_id, $post->post_content);
                
                $attachments = get_post_meta($post_id, 'isc_post_images', true);
            }
            
            $authorname = '';
            if (!empty($post->post_author))
                $authorname = get_the_author_meta('display_name', $post->post_author);
            
            $return = '';
            if (!empty($attachments)) {
                $atts = array();
                foreach ($attachments as $attachment_id => $attachment_array) {
                    $atts[$attachment_id]['title'] = get_the_title($attachment_id);
                    $own = get_post_meta($attachment_id, 'isc_image_source_own', true);
                    $source = get_post_meta($attachment_id, 'isc_image_source', true);

                    if ( $own == '' && $source == '' ) {
                        // remove if no information set
                        unset($atts[$attachment_id]);
                        continue;
                    } elseif ($own != '') {
                        if ($this->_options['use_authorname'] && !empty($authorname)) {
                            $atts[$attachment_id ]['source'] = $authorname;
                        } else {
                            $atts[$attachment_id ]['source'] = $this->_options['by_author_text'];
                        }
                    } else {
                        $atts[$attachment_id ]['source'] = $source;
                    }
                }

                $return = $this->_renderAttachments($atts);
            }

            return $return;
        }

        /**
         * @param array $attachments
         */
        protected function _renderAttachments($attachments)
        {
            // don't display anything, if no image sources displayed
            if ($attachments == array()) {
                return ;
            }
            ob_start();
            
            $headline = $this->_options['image_list_headline'];
            printf('<p class="isc_image_list_title">%s</p>', $headline); ?>
            <ul class="isc_image_list"><?php

            foreach ($attachments as $atts_id => $atts_array) {
                if (empty($atts_array['source'])) {
                    continue;
                }
                printf('<li>%1$s: %2$s</li>', $atts_array['title'], $atts_array['source']);
            }
            ?></ul><?php
            return ob_get_clean();
        }

        /**
         * shortcode function to list all image sources
         * @param arr $atts
         */
        public function list_post_attachments_with_sources_shortcode($atts = array())
        {
            global $post;
            extract(shortcode_atts(array('id' => 0), $atts));

            // if $id not set, use the current ID from the post
            if (empty($id)) {
                $id = $post->ID;
            }

            if (empty($id)) {
                return;
            }
            return $this->list_post_attachments_with_sources($id);
        }

        /**
         * get all attachments without sources
         * the downside of this function: is there is not even an empty metakey field, nothing is going to be retrieved
         * @todo fix this in WP 3.5 with compare => 'NOT EXISTS'
         */
        public function get_attachments_without_sources()
        {
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
        public function add_meta_values_to_attachments()
        {
            // retrieve all attachments
            $args = array(
                'post_type' => 'attachment',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => null,
            );

            $attachments = get_posts($args);
            if (empty($attachments)) {
                return;
            }

            $count = 0;
            foreach ($attachments as $_attachment) {
                $set = false;
                setup_postdata($_attachment);
                foreach ($this->_fields as $_field) {
                    $meta = get_post_meta($_attachment->ID, $_field['id'], true);
                    if (empty($meta)) {
                        update_post_meta($_attachment->ID, $_field['id'], $_field['default']);
                        $set = true;
                    }
                }
                if ($set) {
                    $count++;
                }
            }
        }

        /**
         * show the loading image from wp-admin/images/loading.gif
         * @param bool $display should this be displayed directly or hidden? via inline css
         */
        public function show_loading_image($display = true)
        {
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
        public function save_image_information_on_post_save($post_id)
        {
            // return, if save_post is called more than one time
            if (did_action('save_post') !== 1) {
                return;
            }

            if (isset($_POST['post_type']) && 'attachment' == $_POST['post_type']) {
                return;
            }

            // check if this is a revision and if so, use parent post id
            if ($_id = wp_is_post_revision($post_id)) {
                $post_id = $_id;
            }

            $_content = '';
            if ( !empty( $_REQUEST['content']) ) $_content = stripslashes($_REQUEST['content']);
            
            // Needs to be called before the 'isc_post_images' field is updated.
            $this->update_image_posts_meta($post_id, $_content);
            
            $this->save_image_information($post_id, $_content);
        }

        /**
         * save image information for a post when it is viewed and the image source list is enabled
         * (this is in case the plugin is new and the current post wasn't saved before)
         *
         * @since 1.1
         */
        public function save_image_information_on_load()
        {
            global $post;
            if (empty($post->ID)) {
                return;
            }

            $post_id = $post->ID;
            $_content = $post->post_content;

            $this->save_image_information($post_id, $_content);
        }

        /**
         * retrieve images added to a post or page and save all information as a meta value
         * @since 1.1
         * @todo check for more post types that maybe should not be parsed here
         */
        public function save_image_information($post_id, $_content)
        {
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
            
            /**
            * if an image is used both inside the post and as post thumbnail, the thumbnail entry overrides the regular image.
            */
            if ( !empty( $thumb_id )) {
                $_imgs[$thumb_id] = array(
                    'src' => wp_get_attachment_url($thumb_id),
                    'thumbnail' => true
                );
            }

            if (empty($_imgs)) {
                $_imgs = false;
            }
            update_post_meta($post_id, 'isc_post_images', $_imgs);
        }

        /**
         * filter image src attribute from text
         * @since 1.1
         * @updated 1.1.3
         * @return array with image src uris
         */
        public function _filter_src_attributes($content = '')
        {
            $srcs = array();
            if (empty($content))
                return $srcs;

            // parse HTML with DOM
            $dom = new DOMDocument;
            
            libxml_use_internal_errors(true);
            $dom->loadHTML($content);
            
            // Prevents from sending E_WARNINGs notice (Outputs are forbidden during activation)
            libxml_clear_errors();
            
            foreach ($dom->getElementsByTagName('img') as $node) {
                $srcs[] = $node->getAttribute('src');
            }

            return $srcs;
        }

        /**
         * get image by url accessing the database directly
         * @since 1.1
         * @updated 1.1.3
         * @param string $url url of the image
         * @return id of the image
         */
        public function get_image_by_url($url = '')
        {
            if (empty($url)) {
                return 0;
            }
            $types = implode('|', $this->_allowedExtensions);
            // check for the format 'image-title-(e12452112-)300x200.jpg' and remove the image size and edit mark from it
            $newurl = preg_replace("/(-e\d+){0,1}-(\d+)x(\d+)\.({$types})$/i", '.${4}', $url);
            global $wpdb;
            $query = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE guid = %s", $newurl);
            $id = $wpdb->get_var($query);
            return $id;
        }
        
        /**
        * Update isc_image_posts meta field for all images found in a post with a given ID.
        * @param $post_id ID of the target post
        * @param $content content of the target post
        */
        public function update_image_posts_meta($post_id, $content)
        {
            $image_urls = $this->_filter_src_attributes($content);
            $image_ids = array();
            $added_images = array();
            $removed_images = array();

            $isc_post_images = get_post_meta($post_id, 'isc_post_images', true);
            
            foreach ($image_urls as $url) {
                $id = intval($this->get_image_by_url($url));
                array_push($image_ids, $id);
                if (is_array($isc_post_images) && !array_key_exists($id, $isc_post_images)) {
                    array_push($added_images, $id);
                }
            }
            if (is_array($isc_post_images)) {
                foreach ($isc_post_images as $old_id => $value) {
                    if (!in_array($old_id, $image_ids)) {
                        array_push($removed_images, $old_id);
                    } else {
                        if (!empty($old_id)) {
                            $meta = get_post_meta($old_id, 'isc_image_posts', true);
                            if (empty($meta)) {
                                update_post_meta($old_id, 'isc_image_posts', array($post_id));
                            } else {
                                // In case the isc_image_posts is not up to date
                                if (is_array($meta) && !in_array($post_id, $meta)) {
                                    array_push($meta, $post_id);
                                    update_post_meta($old_id, 'isc_image_posts', $meta);
                                }
                            }
                        }
                    }
                }
            }
            
            foreach ($added_images as $id) {
                $meta = get_post_meta($id, 'isc_image_posts', true);
                if (!is_array($meta) || array() == $meta) {
                    update_post_meta($id, 'isc_image_posts', array($post_id));
                } else {
                    array_push($meta, $post_id);
                    update_post_meta($id, 'isc_image_posts', $meta);
                }
            }
            
            foreach ($removed_images as $id) {
                $image_meta = get_post_meta($id, 'isc_image_posts', true);
                if (is_array($image_meta)) {
                    $offset = array_search($post_id, $image_meta);
                    if (false !== $offset) {
                        array_splice($image_meta, $offset, 1);
                        update_post_meta($id, 'isc_image_posts', $image_meta);
                    }
                }
            }            
        }
        
        /**
         * create shortcode to list all image sources in the frontend
         * @param array $atts
         * @since 1.1.3
         * @todo link to the post
         */
        public function list_all_post_attachments_sources_shortcode($atts = array())
        {
        
            /**
             * @todo why not translate here with the code below?
             * > Because the two if statements below will need to call gettext (again) for comparing values.
             */
            extract(shortcode_atts(array(
                'per_page' => 99999,
                'before_links' => '',
                'after_links' => '',
                'prev_text' => '&#171; Previous',
                'next_text' => 'Next &#187;'
                ),
                $atts));
            
            /**
             * @todo why not include this into the array above?
             */
            if ('&#171; Previous' == $prev_text) 
                $prev_text = __('&#171; Previous', ISCTEXTDOMAIN);
            if ('Next &#187;' == $next_text) 
                $next_text = __('Next &#187;', ISCTEXTDOMAIN);
        
            // retrieve all attachments
            $args = array(
                'post_type' => 'attachment',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => null,
                'meta_query' => array(
                    array(
                        'key' => 'isc_image_posts',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    )
                )
                /** @todo maybe add offset to not retrieve the first results when not on first page */
                /** @todo maybe add limit to not retrieve more results than on the current page */
                /** >No, we need to get total count of attachment with parents for $max_page in the pagination link. */
            );

            $attachments = get_posts($args);
            if (empty($attachments)) {
                return;
            }
            
            $options = $this->get_isc_options();
            
            $connected_atts = array();
            
            //Keeps only those ones who have parent
            
            foreach ($attachments as $_attachment) {
                $connected_atts[$_attachment->ID]['source'] = get_post_meta($_attachment->ID, 'isc_image_source', true);
                $connected_atts[$_attachment->ID]['own'] = get_post_meta($_attachment->ID, 'isc_image_source_own', true);
                $connected_atts[$_attachment->ID]['title'] = $_attachment->post_title;
                $connected_atts[$_attachment->ID]['author_name'] = '';
                if ('' != $connected_atts[$_attachment->ID]['own']) {
                    $parent = get_post($_attachment->post_parent);
                    $connected_atts[$_attachment->ID]['author_name'] = get_the_author_meta('display_name', $parent->post_author);
                }
                
                $metadata = get_post_meta($_attachment->ID, 'isc_image_posts', true);
                $parents_data = '';
                
                if (is_array($metadata) && array() != $metadata) {
                    $parents_data .= "<ul style='margin: 0;'>";
                    foreach($metadata as $data) {
                        $parents_data .= sprintf(__('<li><a href="%1$s" title="View %2$s">%3$s</a></li>', ISCTEXTDOMAIN),
                            esc_url(get_permalink($data)),
                            esc_attr(get_the_title($data)),
                            esc_html(get_the_title($data))
                        );
                    }
                    $parents_data .= "</ul>";
                }
                
                $connected_atts[$_attachment->ID]['posts'] = $parents_data;
            }
            
            $total = count($connected_atts);
            
            if (0 == $total) 
                return;
            
            $page = isset($_GET['isc-page']) ? intval($_GET['isc-page']) : 1;
            $down_limit = 1; // First page
            
            $up_limit = 1;
            
            if ($per_page < $total) {
                $rem = $total % $per_page; // The Remainder of $total/$per_page
                $up_limit = ($total - $rem) / $per_page;
                if (0 < $rem) {
                    $up_limit++; //If rem is positive, add the last page that contains less than $per_page attachment;
                }
            }
                
            ob_start();
            if ( 2 > $up_limit ) {
                $this->display_all_attachment_list($connected_atts);
            } else {
                $starting_atts = $per_page * ($page - 1); // for page 2 and 3 $per_page start display on $connected_atts[3*(2-1) = 3]
                $paged_atts = array_slice($connected_atts, $starting_atts, $per_page, true);
                $this->display_all_attachment_list($paged_atts);
                $this->pagination_links($up_limit, $before_links, $after_links, $prev_text, $next_text);
            }
            if (isset($options['webgilde']) && true == $options['webgilde']) {
            ?>
                <p class="isc-backlink"><?php printf(__('Image list created by <a href="%s" title="Image Source Control">Image Source Control Plugin</a>', ISCTEXTDOMAIN), WEBGILDE); ?></p>
            <?php
            }
            
            $output = ob_get_clean();
            return $output;
        }
        
        
        /**
        * performs rendering of all attachments list
         * @since 1.1.3
        */
        public function display_all_attachment_list($atts)
        {
            if (!is_array($atts) || $atts == array())
                return;
            $options = $this->get_isc_options();
            if (!isset($options['thumbnail_size'])) {
                $options = $options + $this->default_options();
            }
            ?>
            <table>
                <thead>
                    <?php if ($options['thumbnail_in_list']) : ?>
                        <th><?php _e('Thumbnail', ISCTEXTDOMAIN); ?></th>
                    <?php endif; ?>
                    <th><?php _e("Attachment's ID", ISCTEXTDOMAIN); ?></th>
                    <th><?php _e('Title', ISCTEXTDOMAIN); ?></th>
                    <th><?php _e('Attached to', ISCTEXTDOMAIN); ?></th>
                    <th><?php _e('Source', ISCTEXTDOMAIN); ?></th>
                </thead>
                <tbody>
                <?php foreach ($atts as $id => $data) : ?>
                    <?php
                        /** @todo ment for later: this text was used above already; find a place to but it so it is defined only once and used where needed */    
                        $source = __('Not available', ISCTEXTDOMAIN);
                        if ('' != $data['own']) {
                            /** @todo ment for later: this text was used above already; find a place to but it so it is defined only once and used where needed */
                            if ($this->_options['use_authorname']) {
                                $source = $data['author_name'];
                            } else {
                                $source = $this->_options['by_author_text'];
                            }
                        } else {
                            if (!empty($data['source']))
                                $source = $data['source'];
                        }
                    ?>
                    <tr>
                        <?php
                            $v_align = '';
                            if ($options['thumbnail_in_list']) :
                            $v_align = 'style="vertical-align: top;"';
                        ?>
                            <?php if ('custom' != $options['thumbnail_size']) : ?>
                                <td><?php echo wp_get_attachment_image($id, $options['thumbnail_size']); ?></td>
                            <?php else : ?>
                                <td><?php echo wp_get_attachment_image($id, array($options['thumbnail_width'], $options['thumbnail_height'])); ?></td>
                            <?php endif; ?>
                        <?php endif; ?>
                        <td <?php echo $v_align;?>><?php echo $id; ?></td>
                        <td <?php echo $v_align;?>><?php echo $data['title']; ?></td>
                        <td <?php echo $v_align;?>><?php echo $data['posts']; ?></td>
                        <td <?php echo $v_align;?>><?php echo esc_html($source); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }
        
        /**
        * Render pagination links, use $before_links and after_links to wrap pagination links inside an additional block
         * @param int $max_page total page count
         * @param string $before_links optional html to display before pagination links
         * @param string $after_links optional html to display after pagination links
         * @param string $prev_text text for the previous page link
         * @param string $next_text text for the next page link
         * @since 1.1.3
         * 
        */
        public function pagination_links($max_page, $before_links, $after_links, $prev_text, $next_text)
        {
            if ((!isset($max_page)) || (!isset($before_links)) || (!isset($after_links)) || (!isset($prev_text)) || (!isset($next_text)))
                return;
            if (!empty($before_links)) 
                echo $before_links;
                ?>
                <div class="isc-paginated-links">
                <?php
                $page = isset($_GET['isc-page']) ? intval($_GET['isc-page']) : 1;
                if ($max_page < $page) {                
                    $page = $max_page;
                }
                if ($page < 1) {
                    $page = 1;
                }
                $min_page = 1;
                $backward_distance = $page - $min_page;
                $forward_distance = $max_page - $page;
                
                $page_link = get_page_link();
                
                /** 
                * Remove the query_string of the page_link (?page_id=xyz for the standard permalink structure), 
                * which is already captured in $_SERVER['QUERY_STRING'].
                * @todo replace regex with other value (does WP store the url path without attributes somewhere?
                * >get_page_link() returns the permalink but for the default WP permalink structure, the permalink looks like "http://domain.tld/?p=52", while $_GET
                * still has a field named 'page_id' with the same value of 52.
                */
                
                $pos = strpos($page_link, '?');
                if (false !== $pos) {
                    $page_link = substr($page_link, 0, $pos);
                }
                
                /**
                * Unset the actual "$_GET['isc-page']" variable (if is set). Pagination variable will be appended to the new query string with a different value for each 
                * pagination link.
                */
                
                if (isset($_GET['isc-page'])) {
                    unset($_GET['isc-page']);
                }
                
                $query_string = http_build_query($_GET);
				
                $isc_query_tag = '';
                if (empty($query_string)) {
                    $isc_query_tag = '?isc-page=';
                } else {
                    $query_string = '?' . $query_string;
                    $isc_query_tag = '&isc-page=';
                }
                
                if ($min_page != $page) {
                    ?>
                    <a href="<?php echo $page_link . $query_string . $isc_query_tag . ($page-1); ?>" class="prev page-numbers"><?php echo $prev_text; ?></a>
                    <?php
                }
                
                if (5 < $max_page) {
                    
                    if (3 < $backward_distance) {
                        ?>
                        <a href="<?php echo $page_link . $query_string . $isc_query_tag; ?>1" class="page-numbers">1</a>
                        <span class="page-numbers dots">...</span>
                        <a href="<?php echo $page_link . $query_string . $isc_query_tag . ($page-2);?>" class="page-numbers"><?php echo $page-2; ?></a>
                        <a href="<?php echo $page_link . $query_string . $isc_query_tag . ($page-1);?>" class="page-numbers"><?php echo $page-1; ?></a>
                        <span class="page-numbers current"><?php echo $page; ?></span>
                        <?php
                    } else {
                        for ($i = 1; $i <= $page; $i++) {
                            if ($i == $page) { 
                            ?>
                                <span class="page-numbers current"><?php echo $i; ?></span>
                            <?php
                            } else { 
                            ?>
                                <a href="<?php echo $page_link . $query_string . $isc_query_tag . $i;?>" class="page-numbers"><?php echo $i; ?></a>
                            <?php
                            }
                        }
                    }
                    
                    if (3 < $forward_distance) {
                    ?>
                        <a href="<?php echo $page_link . $query_string . $isc_query_tag . ($page+1);?>" class="page-numbers"><?php echo $page+1; ?></a>
                        <a href="<?php echo $page_link . $query_string . $isc_query_tag . ($page+2);?>" class="page-numbers"><?php echo $page+2; ?></a>
                        <span class="page-numbers dots">...</span>
                        <a href="<?php echo $page_link . $query_string . $isc_query_tag . $max_page;?>" class="page-numbers"><?php echo $max_page; ?></a>
                    <?php
                    } else {
                        for ($i = $page+1; $i <= $max_page; $i++) {
                            ?>
                            <a href="<?php echo $page_link . $query_string . $isc_query_tag . $i;?>" class="page-numbers"><?php echo $i; ?></a>
                            <?php
                        }
                    }                    
                } else {
                    for ($i = 1; $i <= $max_page; $i++) {
                        if ($i == $page) { 
                        ?>
                            <span class="page-numbers current"><?php echo $i; ?></span>
                        <?php
                        } else { 
                        ?>
                            <a href="<?php echo $page_link . $query_string . $isc_query_tag . $i;?>" class="page-numbers"><?php echo $i; ?></a>
                        <?php
                        }
                    }
                }                
                if ($page != $max_page) {
                    ?>
                    <a href="<?php echo $page_link . $query_string . $isc_query_tag . ($page+1);?>" class="next page-numbers"><?php echo $next_text; ?></a>
                    <?php
                }
                ?>
                </div>
                <?php
                echo $after_links;
        }
        
        /**
        * The activation function
        */
        public function activation()
        {
            if (!is_array(get_option('isc_options'))) {
                update_option( 'isc_options', $this->default_options() );
            }
            $options = $this->get_isc_options();
            if (!$options['installed']) {
                /**
                * Here, all jobs to perform during first installation, especially options and custom fields.
                * Important: No add_action('something', 'somefunction').
                */
                
                // adds meta fields for attachments
                $this->add_meta_values_to_attachments();
                
                // set all isc_image_posts meta fields.
                $this->init_image_posts_metafield();
                
                $options['installed'] = true;
                update_option('isc_options', $options);
            }
        }
        
        /**
        *   Returns default options
        */
        public function default_options()
        {
            $default['image_list_headline'] = __('image sources', ISCTEXTDOMAIN);
            $default['use_authorname'] = true;
            $default['by_author_text'] = __('Owned by the author', ISCTEXTDOMAIN);
            $default['installed'] = false;
            $default['version'] = '1.2';
            $default['webgilde'] = false;
            $default['thumbnail_in_list'] = false;
            $default['thumbnail_size'] = 'thumbnail';
            $default['thumbnail_width'] = 150;
            $default['thumbnail_height'] = 150;
            return $default;
        }
        
        /**
        * Settings API initialization
        */
        public function SAPI_init()
        {
            $this->upgrade_management();
            register_setting('isc_options_group', 'isc_options', array($this, 'settings_validation'));
            add_settings_section('isc_settings_section', '', '__return_false', 'isc_settings_page');
            add_settings_field('image_list_headline', __('Image list headline', ISCTEXTDOMAIN), array($this, 'renderfield_list_headline'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('use_authorname', __('Use authors names', ISCTEXTDOMAIN), array($this, 'renderfield_use_authorname'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('by_author_text', __('Custom text for owned images', ISCTEXTDOMAIN), array($this, 'renderfield_byauthor_text'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('webgilde_backlink', __("Link to webgilde's website", ISCTEXTDOMAIN), array($this, 'renderfield_webgile'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('use_thumbnail', __("Use thumbnails in images list", ISCTEXTDOMAIN), array($this, 'renderfield_use_thumbnail'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('thumbnail_width', __("Thumbnails max-width", ISCTEXTDOMAIN), array($this, 'renderfield_thumbnail_width'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('thumbnail_height', __("Thumbnails max-height", ISCTEXTDOMAIN), array($this, 'renderfield_thumbnail_height'), 'isc_settings_page', 'isc_settings_section');
        }
        
        /**
        * manage data structure upgrading of outdated versions
        */
        public function upgrade_management() {
            /**
            * Since the activation hook is not executed on plugin upgrade, this function checks options in database
            * during the admin_init hook to handle plugin's upgrade.
            */
            
            $options = get_option( 'isc_options' );
            
            $max_step = count($this->_upgrade_step);
            $step_count = 0;
            
            if (!is_array($options) || !isset($options['version'])){ // versions prior to 1.2
            
                $default = $this->default_options();
                $options = $options + $default;
                $this->init_image_posts_metafield();
                $options['installed'] = true;
                
                update_option('isc_options', $options);
                $step_count++;
                
            } elseif(ISCVERSION != $options['version']) {
            
                while (ISCVERSION != $options['version'] && $step_count <= $max_step) {
                    switch ($options['version']) {
                        /**
                        * Here, the incremental upgrade process depending on the currently installed version.
                        */
                    }
                }
                
            }
        }
        
        /**
        * Image_control's page callback
        */
        public function render_isc_settings_page()
        {
            ?>
            <div id="icon-options-general" class="icon32"><br></div>
            <h2><?php _e('Images control settings', ISCTEXTDOMAIN); ?></h2>
            <form id="image-control-form" method="post" action="options.php">
            <?php
                settings_fields( 'isc_options_group' );
                do_settings_sections( 'isc_settings_page' );
                submit_button();
            ?>
            </form>
            <?php
        }
        
        /**
        * image_list field callbacks
        */
        public function renderfield_list_headline()
        {
            $options = $this->get_isc_options();
            $description = __('The headline of the image list added via shortcode or function in your theme.', ISCTEXTDOMAIN);
            ?>
            <div id="image-list-headline-block">
                <label for="list-head"><?php __('Image list headline', ISCTEXTDOMAIN); ?></label>
                <input type="text" name="isc_options[image_list_headline_field]" id="list-head" value="<?php echo $options['image_list_headline'] ?>" class="regular-text" />
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }
        
        public function renderfield_use_authorname()
        {
            $options = $this->get_isc_options();
            $description = __("Display the author's public name as source when the image is owned by the author. Uncheck to use a custom text instead.", ISCTEXTDOMAIN);
            ?>
            <div id="use-authorname-block">
                <label for="use_authorname"><?php _e('Use author name') ?></label>
                <input type="checkbox" name="isc_options[use_authorname_ckbox]" id="use_authorname" <?php checked($options['use_authorname']); ?> />
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }
        
        public function renderfield_byauthor_text()
        {
            $options = $this->get_isc_options();
            $description = __("Enter the custom text to display if you do not want to use the author's public name.", ISCTEXTDOMAIN);
            ?>
            <div id="by-author-text">
                <input type="text" id="byauthor" name="isc_options[by_author_text_field]" value="<?php echo $options['by_author_text']; ?>" <?php disabled($options['use_authorname']); ?> class="regular-text" />
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }
        
        public function renderfield_webgile()
        {
            $options = $this->get_isc_options();
            /**
            * Avoid warning notices because of the absence of webgilde field in isc_options throughout development steps.
            * This 'if' block can be removed for the next release.
            */
            if (!isset($options['webgilde'])) {
                $options = $options + $this->default_options();
            }
            $description = sprintf(__('Display a link to <a href="%s">Image Source Control plugin&#39;s website</a> below the list of all images in the blog?', ISCTEXTDOMAIN), WEBGILDE);
            ?>
            <div id="webgilde-block">
                <input type="checkbox" id="webgilde-link" name="isc_options[webgilde_field]" <?php checked($options['webgilde']); ?> />
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }
        
        public function renderfield_use_thumbnail()
        {
            $options = $this->get_isc_options();
            if (!isset($options['thumbnail_size'])) {
                $options = $options + $this->default_options();
            }
            $description = __('Display thumbnails on the list of all images in the blog.' ,ISCTEXTDOMAIN);
            ?>
            <div id="use-thumbnail-block">
                <input type="checkbox" id="use-thumbnail" name="isc_options[use_thumbnail]" <?php checked($options['thumbnail_in_list']); ?> />
                <select id="thumbnail-size-select" name="isc_options[size_select]" <?php disabled(!$options['thumbnail_in_list']) ?>>
                    <?php foreach ($this->_thumbnail_size as $size) : ?>
                        <option value="<?php echo $size; ?>" <?php selected($size, $options['thumbnail_size']);?>><?php echo $size; ?></option>
                    <?php endforeach; ?>
                </select>
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }
        
        public function renderfield_thumbnail_width()
        {
            $options = $this->get_isc_options();
            if (!isset($options['thumbnail_size'])) {
                $options = $options + $this->default_options();
            }
            $description = __('Custom value of the maximum allowed width for thumbnail.' ,ISCTEXTDOMAIN);
            ?>
            <div id="thumbnail-custom-width">
                <input type="text" id="custom-width" name="isc_options[thumbnail_width]" class="small-text" value="<?php echo $options['thumbnail_width'] ?>" /> px
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }
                
        public function renderfield_thumbnail_height()
        {
            $options = $this->get_isc_options();
            if (!isset($options['thumbnail_size'])) {
                $options = $options + $this->default_options();
            }
            $description = __('Custom value of the maximum allowed height for thumbnail.' ,ISCTEXTDOMAIN);
            ?>
            <div id="thumbnail-custom-height">
                <input type="text" id="custom-height" name="isc_options[thumbnail_height]" class="small-text" value="<?php echo $options['thumbnail_height'] ?>"/> px
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }
        
        /**
        * Returns isc_options if it exists, returns the default options otherwise.
        */
        public function get_isc_options() {
            return get_option('isc_options', $this->default_options());
        }
        
        /*
        * Input validation function.
        * @param array $input values from the admin panel
        */
        public function settings_validation($input)
        {
            $output = $this->get_isc_options();
            $output['image_list_headline'] = esc_html($input['image_list_headline_field']);
            if (isset($input['use_authorname_ckbox'])) {
                // Don't worry about the custom text if the author name is selected.
                $output['use_authorname'] = true;
            } else {
                $output['use_authorname'] = false;
                $output['by_author_text'] = esc_html($input['by_author_text_field']);
            }
            if (isset($input['webgilde_field'])) {
                $output['webgilde'] = true;
            } else {
                $output['webgilde'] = false;
            }
            if (isset($input['use_thumbnail'])) {
                $output['thumbnail_in_list'] = true;
                if (in_array($input['size_select'], $this->_thumbnail_size)) {
                    $output['thumbnail_size'] = $input['size_select'];
                }
                if ('custom' == $input['size_select']) {
                    if (is_numeric($input['thumbnail_width'])) {
                        // Ensures that the value stored in database in a positive integer.
                        $output['thumbnail_width'] = abs(intval(round($input['thumbnail_width'])));
                    }
                    if (is_numeric($input['thumbnail_height'])) {
                        $output['thumbnail_height'] = abs(intval(round($input['thumbnail_height'])));
                    }
                }
            } else {
                $output['thumbnail_in_list'] = false;
            }
            return $output;
        }
        
        /**
        * Adds isc_image_posts on all attachments. Launched during first installation.
        */
        public function init_image_posts_metafield()
        {
            $args = array(
                'post_type' => 'any',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => null,
            );
            $posts = get_posts($args);
            foreach ($posts as $post) {
                setup_postdata($post);
                $image_urls = $this->_filter_src_attributes($post->post_content);
                $image_ids = array();
                foreach ($image_urls as $url) {
                    $image_id = intval($this->get_image_by_url($url));
                    array_push($image_ids,$image_id);
                }
                foreach ($image_ids as $id) {
                    $meta = get_post_meta($id, 'isc_image_posts', true);
                    if (empty($meta)) {
                        update_post_meta($id, 'isc_image_posts', array($post->ID));
                    } else {
                        if (!in_array($post->ID, $meta)) {
                            array_push($meta, $post->ID);
                            update_post_meta($id, 'isc_image_posts', $meta);
                        }
                    }
                }
            }
        }
        
    }// end of class
    
    $inc_path = ABSPATH . 'wp-includes/';
    /**
    * "pluggable.php" is not defined at this point. Not sure about the reason.
    */
    require_once($inc_path . 'pluggable.php');
    
    /**
    * Need an instance of ISC_CLASS when the register_activation_hook is called (earlier than the "plugins_loaded" hook).
    */
    global $my_isc;
    $my_isc = new ISC_CLASS();

    /**
     * the next functions are just to have an easier access from outside the class
     */
    function isc_list($post_id = 0) {
        $isc = new ISC_CLASS();
        echo $isc->list_post_attachments_with_sources($post_id);
    }
}
