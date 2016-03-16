<?php

class ISC_Class {

        /**
         * define default meta fields
         */
        protected $_fields = array(
            'image_source' => array(
                'id' => 'isc_image_source',
                'default' => '',
            ),
            'image_source_url' => array(
                'id' => 'isc_image_source_url',
                'default' => '',
            ),
            'image_source_own' => array(
                'id' => 'isc_image_source_own',
                'default' => '',
            ),
            'image_posts' => array(
                'id' => 'isc_image_posts',
                'default' => array()
            ),
            'image_licence' => array(
                'id' => 'isc_image_licence',
                'default' => ''
            )
        );

        /**
         * allowed image file types/extensions
         * @since 1.1
         */
        protected $_allowedExtensions = array(
            'jpg', 'png', 'gif', 'jpeg'
        );

        /**
        * Thumbnail size in list of all images.
        * @since 1.2
        */
        protected $_thumbnail_size = array('thumbnail', 'medium', 'large', 'custom');

        /**
         * options saved in the db
         * @since 1.2
         */
        protected $_options = array();

        /**
        * Position of image's caption
        */
        protected $_caption_position = array(
            'top-left',
            'top-center',
            'top-right',
            'center',
            'bottom-left',
            'bottom-center',
            'bottom-right'
        );

        protected static $instance;

        public static function get_instance() {
            return self::$instance;
        }

        /**
         * Setup registers filterts and actions.
         */
        public function __construct()
        {
            // load all plugin options
            $this->_options = get_option('isc_options');
            self::$instance = $this;
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
            if ( empty($attachments) || !is_array($attachments) ) {
                return;
            }

            $count = 0;
            foreach ( $attachments as $_attachment ) {
                $set = false;
                setup_postdata($_attachment);
                foreach ( $this->_fields as $_field ) {
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
         * retrieve images added to a post or page and save all information as a post meta value for the post
         * @since 1.1
         * @updated 1.3.5 added isc_images_in_posts filter
         * @todo check for more post types that maybe should not be parsed here
         */
        public function save_image_information($post_id, $_content)
        {
            // apply shortcodes to content
            $_content = do_shortcode($_content);

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

            // apply filter to image array, so other developers can add their own logic
            $_imgs = apply_filters('isc_images_in_posts', $_imgs, $post_id);

            if (empty($_imgs)) {
                $_imgs = array();
            }
            update_post_meta($post_id, 'isc_post_images', $_imgs);
        }

        /**
         * filter image src attribute from text
         * @since 1.1
         * @updated 1.1.3
         * @return array with image src uri-s
         */
        public function _filter_src_attributes($content = '')
        {
            $srcs = array();
            if (empty($content))
                return $srcs;

            // parse HTML with DOM
            $dom = new DOMDocument;

            libxml_use_internal_errors(true);
            if ( function_exists('mb_convert_encoding') ) {
                $content = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
            }
            $dom->loadHTML($content);

            // Prevents from sending E_WARNINGs notice (Outputs are forbidden during activation)
            libxml_clear_errors();

            foreach ($dom->getElementsByTagName('img') as $node) {
                if ( isset( $node->attributes ) ) {
                    if ( null !== $node->attributes->getNamedItem('src') ) {
                        $srcs[] = $node->attributes->getNamedItem('src')->textContent;
                    }
                }
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
            global $wpdb;

            if (empty($url)) {
                return 0;
            }
            $types = implode( '|', $this->_allowedExtensions );
            /**
             * check for the format 'image-title-(e12452112-)300x200.jpg(?query…)' and removes
             *   the image size
             *   edit marks
             *   additional query vars
             */
            $newurl = esc_url( preg_replace( "/(-e\d+){0,1}(-\d+x\d+){0,1}\.({$types})(.*)/i", '.${3}', $url ) );

            // remove protocoll (http or https)
            $newurl = str_ireplace( array( 'http:', 'https:' ) , '', $newurl );

            // not escaped, because escaping already happened above
            $raw_query = $wpdb->prepare(
                "SELECT ID FROM `$wpdb->posts` WHERE post_type='attachment' AND guid = %s OR guid = %s LIMIT 1",
                "http:$newurl",
                "https:$newurl"
            );
            $query = apply_filters( 'isc_get_image_by_url_query', $raw_query, $newurl );
            $id = $wpdb->get_var($query);

            return $id;
        }

        /**
        * Update isc_image_posts meta field for all images found in a post with a given ID.
        * @param $post_id ID of the target post
        * @param $content content of the target post
         * @updated 1.3.5 added images_in_posts_simple filter
        */
        public function update_image_posts_meta($post_id, $content)
        {
            // apply shortcodes to content
            $content = do_shortcode($content);

            $image_urls = $this->_filter_src_attributes($content);
            $image_ids = array();
            $added_images = array();
            $removed_images = array();

            // add thumbnail information
            $thumb_id = get_post_thumbnail_id($post_id);
            if ( !empty( $thumb_id )) { $image_urls[] = wp_get_attachment_url($thumb_id); }
            
            // get urls from gallery images
            // this might not be needed, since the gallery shortcode might have run already, but just in case
            // only for php 5.3 and higher
            if ( -1 !== version_compare( phpversion(), '5.3' ) && preg_match_all('/\[gallery([^\]]+)\]/m', $content, $results, PREG_SET_ORDER)) {
                foreach ($results as $result) {
                        if (! preg_match('/ids="([^"]+)"/m', $result[1], $ids)) {
                                continue;
                        }
                        $image_urls = array_merge($image_urls, array_map( 'map_walker', explode(',', $ids[1])));
                }
            }

            // apply filter to image array, so other developers can add their own logic
            $image_urls = apply_filters('isc_images_in_posts_simple', $image_urls, $post_id);

            $isc_post_images = get_post_meta($post_id, 'isc_post_images', true);
            // just needed in very rare cases, when updates comes from outside of isc and meta fields doesn’t exist yet
            if(empty($isc_post_images)) $isc_post_images = array();

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
									$meta = array_unique( $meta );
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
					$meta = array_unique( $meta );
                    update_post_meta($id, 'isc_image_posts', $meta);
                }
            }

            foreach ($removed_images as $id) {
                $image_meta = get_post_meta($id, 'isc_image_posts', true);
                if (is_array($image_meta)) {
                    $offset = array_search($post_id, $image_meta);
                    if (false !== $offset) {
                        array_splice($image_meta, $offset, 1);
						$image_meta = array_unique( $image_meta );
                        update_post_meta($id, 'isc_image_posts', $image_meta);
                    }
                }
            }
        }

        /**
        *   Returns default options
        */
        public function default_options()
        {
            $default['display_type'] = array('list');
            $default['list_on_archives'] = false;
            $default['list_on_excerpts'] = false;
            $default['image_list_headline'] = __('image sources', ISCTEXTDOMAIN);
            $default['exclude_own_images'] = false;
            $default['use_authorname'] = true;
            $default['by_author_text'] = __('Owned by the author', ISCTEXTDOMAIN);
            $default['installed'] = false;
            $default['version'] = ISCVERSION;
            $default['webgilde'] = false;
            $default['thumbnail_in_list'] = false;
            $default['thumbnail_size'] = 'thumbnail';
            $default['thumbnail_width'] = 150;
            $default['thumbnail_height'] = 150;
            $default['warning_nosource'] = true;
            $default['warning_onesource_missing'] = true;
            $default['hide_list'] = false;
            $default['caption_position'] = 'top-left';
            $default['source_pretext'] = __('Source:', ISCTEXTDOMAIN);
            $default['enable_licences'] = false;
            $default['licences'] = __("CC BY 2.0|http://creativecommons.org/licenses/by/2.0/legalcode", ISCTEXTDOMAIN);
            return $default;
        }

        /**
        * Returns isc_options if it exists, returns the default options otherwise.
        */
        public function get_isc_options() {
            return get_option('isc_options', $this->default_options());
        }

        /**
        * Add isc_image_posts on all attachments. Launched during first installation.
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
							$meta = array_unique( $meta );
                            update_post_meta($id, 'isc_image_posts', $meta);
                        }
                    }
                }
            }
        }

        /**
         * transform the licences from the options textfield into an array
         *
         * @param string $licences text with licences
         * @return array $new_licences array with licences and licence information
         * @return false if no array created
         * @since 1.3.5
         */
        public function licences_text_to_array($licences = '')
        {
            if($licences == '') return false;
            // split the text by line
            $licences_array = preg_split('/\r?\n/', trim($licences));
            if(count($licences_array) == 0 ) return false;
            // create the array with licence => url
            $new_licences = array();
            foreach($licences_array as $_licence) {
                if(trim($_licence) != '') {
                    $temp = explode('|', $_licence);
                    $new_licences[$temp[0]] = array();
                    if( isset($temp[1]))
                        $new_licences[$temp[0]]['url'] = esc_url ($temp[1]);
                }
            }

            if($new_licences == array()) return false;
            else return $new_licences;
        }
}// end of class
