<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists('ISC_Class')) {

    class ISC_Class
    {
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
        * Commonly used text elements
        */
        protected $_common_texts = array();

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

        /**
         * Setup registers filterts and actions.
         */
        public function __construct()
        {
            // load all plugin options
            $this->_options = get_option('isc_options');
            $this->_common_texts['not_available'] = __('Not available', ISCTEXTDOMAIN);

            // insert all function for the frontend here

            add_shortcode('isc_list', array($this, 'list_post_attachments_with_sources_shortcode'));
            add_shortcode('isc_list_all', array($this, 'list_all_post_attachments_sources_shortcode'));
            add_action('wp_enqueue_scripts', array($this, 'front_scripts'));
            add_action('wp_head', array($this, 'front_head'));

            // ajax actions
            add_action('wp_ajax_isc-post-image-relations', array($this, 'list_post_image_relations'));
            add_action('wp_ajax_isc-image-post-relations', array($this, 'list_image_post_relations'));

            // insert all backend functions below this check
            if (!is_admin()) {
                // frontend functions
                add_filter('the_content', array($this, 'content_filter'), 20);
            }
        }

        /**
         * load an image source string by url
         *
         * @updated 1.5
         * @param string $url url of the image
         * @return type
         */
        public function get_source_by_url($url)
        {
            // get the id by the image source
            $id = $this->get_image_by_url($url);

            return $this->render_image_source_string($id);

        }

        /**
         * add captions to post content and include source into caption, if this setting is enabled
         *
         * @param string $content post content
         * @return string $content
         *
         * @update 1.4.3
         */
        public function content_filter($content)
        {
            // display inline sources
            $options = $this->get_isc_options();
            if (in_array('overlay', $options['display_type'])) {
                $pattern = '#(\[caption.*align="(.+)"[^\]*]{0,}\])? *(<a [^>]+>)? *(<img .*class=".*(align\d{4,})?.*wp-image-(\d+)\D*".*src="(.+)".*/?>).*(?(3)(?:</a>)|.*).*(?(1)(?:\[/caption\])|.*)#isU';
                $count = preg_match_all($pattern, $content, $matches);
                if (false !== $count) {
                    for ($i=0; $i < $count; $i++) {
                        $id = $matches[6][$i];
                        // don’t show caption for own image if admin choose not to do so
                        if($options['exclude_own_images']){
                            if(get_post_meta($id, 'isc_image_source_own', true)) continue;
                        }
                        $src = $matches[7][$i];
                        $source = '<p class="isc-source-text">' . $options['source_pretext'] . ' ' . $this->get_source_by_url($src) . '</p>';
                        $old_content = $matches[0][$i];
                        $new_content = str_replace('wp-image-' . $id, 'wp-image-' . $id . ' with-source', $old_content);
                        $alignment = (!empty($matches[1][$i]))? $matches[2][$i] : $matches[5][$i];

                        $content = str_replace($old_content, '<div id="isc_attachment_' . $id . '" class="isc-source ' . $alignment . '"> ' . $new_content . $source . '</div>', $content);
                    }
                }
            }

            // attach image source list to content, if option is enabled
            if (is_singular() && in_array('list', $options['display_type'])) {
                $content = $content . $this->list_post_attachments_with_sources();
            }

            return $content;
        }

        /**
         * render source string of single image by its id
         *  this only returns the string with source and licence (and urls),
         *  but no wrapping, because the string is used in a lot of functions
         *  (e.g. image source list where title is prepended)
         *
         * @updated 1.5 wrapped source into source url
         *
         * @param int $id id of the image
         */
        public function render_image_source_string($id){
            $id = absint($id);

            $options = $this->get_isc_options();

            $metadata['source'] = get_post_meta($id, 'isc_image_source', true);
            $metadata['source_url'] = get_post_meta($id, 'isc_image_source_url', true);
            $metadata['own'] = get_post_meta($id, 'isc_image_source_own', true);
            $metadata['licence'] = get_post_meta($id, 'isc_image_licence', true);

            $source = $this->_common_texts['not_available'];

            $att_post = get_post($id);

            if ('' != $metadata['own']) {
                if ($this->_options['use_authorname']) {
                    if (!empty($att_post)) {
                        $source = get_the_author_meta('display_name', $att_post->post_author);
                    }
                } else {
                    $source = $this->options['by_author_text'];
                }
            } else {
                if ('' != $metadata['source']) {
                    $source = $metadata['source'];
                }
            }

            // wrap link around source, if given
            if('' != $metadata['source_url']){
                $source = sprintf('<a href="%2$s" target="_blank" rel="nofollow">%1$s</a>', $source, $metadata['source_url']);
            }

            // add licence if enabled
            if($options['enable_licences'] && isset($metadata['licence']) && $metadata['licence']) {
                $licences = $this->licences_text_to_array($options['licences']);
                if(isset($licences[$metadata['licence']]['url'])) $licence_url = $licences[$metadata['licence']]['url'];
                if($licence_url) {
                    $source = sprintf('%1$s | <a href="%3$s" target="_blank" rel="nofollow">%2$s</a>', $source, $metadata['licence'], $licence_url);
                } else {
                    $source = sprintf('%1$s | %2$s', $source, $metadata['licence']);
                }
            }

            return $source;
        }

        /**
        * Front-end scripts in <head /> section.
        */
        public function front_head()
        {
            $options = $this->get_isc_options();
            ?>
            <script type="text/javascript">
            /* <![CDATA[ */
                var isc_front_data =
                {
                    caption_position : '<?php echo $options['caption_position']; ?>',
                }
            /* ]]> */
            </script>
            <?php
        }

        /**
        * Enqueue scripts for the front-end.
        */
        public function front_scripts() {
            wp_enqueue_script('isc_front_js', plugins_url('/js/front-js.js', __FILE__), array('jquery'), ISCVERSION);
        }

        /**
         * create image sources list for all images of this post
         *
         * @since 1.0
         * @updated 1.1, 1.3.5
         * @updated 1.5 use new render function to create basic image source string
         *
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

            $return = '';
            if (!empty($attachments)) {
                $atts = array();
                foreach ($attachments as $attachment_id => $attachment_array) {

                    $own = get_post_meta($attachment_id, 'isc_image_source_own', true);
                    $source = get_post_meta($attachment_id, 'isc_image_source', true);

                    // check if source of own images can be displayed
                    if ( ($own == '' && $source == '' ) || ($own != '' && $this->_options['exclude_own_images'])) {
                        unset($atts[$attachment_id]);
                        continue;
                    } else {
                        $atts[$attachment_id]['title'] = get_the_title($attachment_id);
                        $atts[$attachment_id]['source'] = $this->render_image_source_string($attachment_id);
                    }

                }

                $return = $this->render_attachments($atts);
            }

            return $return;
        }

        /**
         * render attachment list
         *
         * @param array $attachments
         * @updated 1.3.5
         * @updated 1.5 removed rendering the licence to an earlier function
         */
        protected function render_attachments($attachments)
        {
            // don't display anything, if no image sources displayed
            if ($attachments == array()) {
                return ;
            }


            $options = $this->get_isc_options();

            ob_start();
            $headline = $this->_options['image_list_headline'];
            ?><div class="isc_image_list_box"><?php
            printf('<p class="isc_image_list_title">%1$s</p>', $headline); ?>
            <ul class="isc_image_list"><?php

            foreach ($attachments as $atts_id => $atts_array) {
                if (empty($atts_array['source'])) {
                    continue;
                }
                printf('<li>%1$s: %2$s</li>', $atts_array['title'], $atts_array['source']);
            }
            ?></ul></div><?php
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
         * get all attachments with empty sources string
         */
        public function get_attachments_with_empty_sources()
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
                    // and does not belong to an author
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
         * get all attachments without the proper meta values (needed mostly after installing the plugin for unindexed images)
         *
         * @since 1.6
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
                        'value' => 'any', /* any string; needed prior to WP 3.9 */
                        'compare' => 'NOT EXISTS',
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
            if (empty($attachments) || !is_array($attachments)) {
                return;
            }

            $count = 0;
            foreach($attachments as $_attachment) {
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
         * save image information for a post when it is viewed – only called when using isc_list function
         * (to help indexing old posts)
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
         * retrieve images added to a post or page and save all information as a post meta value for the post
         * @since 1.1
         * @updated 1.3.5 added isc_images_in_posts filter
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
            // TODO better DOM method again regex (wasn’t able so far due to encoding problems)
            if(function_exists('mb_convert_encoding'))
                $content = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
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
         * @updated 1.3.5 added images_in_posts_simple filter
        */
        public function update_image_posts_meta($post_id, $content)
        {
            $image_urls = $this->_filter_src_attributes($content);
            $image_ids = array();
            $added_images = array();
            $removed_images = array();

            // add thumbnail information
            $thumb_id = get_post_thumbnail_id($post_id);
            if ( !empty( $thumb_id )) { $image_urls[] = wp_get_attachment_url($thumb_id); }

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
         */
        public function list_all_post_attachments_sources_shortcode($atts = array())
        {
            extract(shortcode_atts(array(
                'per_page' => 99999,
                'before_links' => '',
                'after_links' => '',
                'prev_text' => '&#171; Previous',
                'next_text' => 'Next &#187;'
                ),
                $atts));

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
            );

            $attachments = get_posts($args);
            if (empty($attachments)) {
                return;
            }

            $options = $this->get_isc_options();

            $connected_atts = array();

            foreach ($attachments as $_attachment) {
                $connected_atts[$_attachment->ID]['source'] = get_post_meta($_attachment->ID, 'isc_image_source', true);
                $connected_atts[$_attachment->ID]['own'] = get_post_meta($_attachment->ID, 'isc_image_source_own', true);
                // jump to next element if author images are not to be included in the list
                if($options['exclude_own_images'] && '' != $connected_atts[$_attachment->ID]['own']) {
                    unset($connected_atts[$_attachment->ID]);
                    continue;
                }

                $connected_atts[$_attachment->ID]['title'] = $_attachment->post_title;
                $connected_atts[$_attachment->ID]['author_name'] = '';
                if ('' != $connected_atts[$_attachment->ID]['own']) {
                    $connected_atts[$_attachment->ID]['author_name'] = get_the_author_meta('display_name', $_attachment->post_author);
                }

                $metadata = get_post_meta($_attachment->ID, 'isc_image_posts', true);
                $usage_data = '';

                if (is_array($metadata) && array() != $metadata) {
                    $usage_data .= "<ul style='margin: 0;'>";
                    foreach($metadata as $data) {
                        // only list published posts
                        if(get_post_status($data) == 'publish') {
                            $usage_data .= sprintf(__('<li><a href="%1$s" title="View %2$s">%3$s</a></li>', ISCTEXTDOMAIN),
                                esc_url(get_permalink($data)),
                                esc_attr(get_the_title($data)),
                                esc_html(get_the_title($data))
                            );
                        }
                    }
                    $usage_data .= "</ul>";
                }

                $connected_atts[$_attachment->ID]['posts'] = $usage_data;
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
        * @update 1.5 added new method to get source
        */
        public function display_all_attachment_list($atts)
        {
            if (!is_array($atts) || $atts == array())
                return;
            $options = $this->get_isc_options();
            ?>
            <div class="isc_all_image_list_box">
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
                        $source = $this->render_image_source_string($id);
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
                        <td <?php echo $v_align;?>><?php echo $source; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
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
        *   Returns default options
        */
        public function default_options()
        {
            $default['display_type'] = array('list');
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
        * Input validation function.
        * @param array $input values from the admin panel
         * @updated 1.3.5 added licences fields
        */
        public function settings_validation($input)
        {
            $output = $this->get_isc_options();
            if(!is_array($input['display_type'])){
                $output['display_type'] = array();
            } else {
                $output['display_type'] = $input['display_type'];
            }
            $output['image_list_headline'] = esc_html($input['image_list_headline_field']);
            if (isset($input['use_authorname_ckbox'])) {
                // Don't worry about the custom text if the author name is selected.
                $output['use_authorname'] = true;
            } else {
                $output['use_authorname'] = false;
                $output['by_author_text'] = esc_html($input['by_author_text_field']);
            }
            if (isset($input['exclude_own_images'])) {
                $output['exclude_own_images'] = true;
            } else {
                $output['exclude_own_images'] = false;
            }
            if (isset($input['webgilde_field'])) {
                $output['webgilde'] = true;
            } else {
                $output['webgilde'] = false;
            }
            if (isset($input['enable_licences'])) {
                $output['enable_licences'] = true;
            } else {
                $output['enable_licences'] = false;
            }
            if (isset($input['licences'])) {
                $output['licences'] = esc_textarea($input['licences']);
            } else {
                $output['licences'] = false;
            }
            if (isset($input['use_thumbnail'])) {
                $output['thumbnail_in_list'] = true;
                if (in_array($input['size_select'], $this->_thumbnail_size)) {
                    $output['thumbnail_size'] = $input['size_select'];
                }
                if ('custom' == $input['size_select']) {
                    if (is_numeric($input['thumbnail_width'])) {
                        // Ensures that the value stored in database in a positive integer.
                        $output['thumbnail_width'] = absint(round($input['thumbnail_width']));
                    }
                    if (is_numeric($input['thumbnail_height'])) {
                        $output['thumbnail_height'] = absint(round($input['thumbnail_height']));
                    }
                }
            } else {
                $output['thumbnail_in_list'] = false;
            }
            if (isset($input['no_source'])) {
                $output['warning_nosource'] = true;
            } else {
                $output['warning_nosource'] = false;
            }
            if (isset($input['one_source'])) {
                $output['warning_onesource_missing'] = true;
            } else {
                $output['warning_onesource_missing'] = false;
            }
            if (isset($input['hide_list'])){
                $output['hide_list'] = true;
            } else {
                $output['hide_list'] = false;
            }
            if (in_array($input['cap_pos'], $this->_caption_position))
                $output['caption_position'] = $input['cap_pos'];
            if (!isset($input['source_pretext'])) {
                $output['source_pretext'] = esc_textarea($input['source_pretext']);
            }
            return $output;
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

        /**
         * list image post relations (called with ajax)
         *
         * @since 1.6.1
         */
        public function list_post_image_relations(){
            // get all meta fields
            $args = array(
                'posts_per_page' => -1,
                'post_status' => null,
                'post_parent' => null,
                'meta_query' => array(
                    array(
                        'key' => 'isc_post_images',
                    ),
                )
            );
            $posts_with_images = new WP_Query($args);

            if($posts_with_images->have_posts()){
                require_once(ISCPATH . '/templates/post_images_list.php');
            }

            wp_reset_postdata();

            die();
        }

        /**
         * list post image relations (called with ajax)
         *
         * @since 1.6.1
         */
        public function list_image_post_relations(){
            // get all images
            $args = array(
                'post_type' => 'attachment',
                'posts_per_page' => -1,
                'post_status' => 'inherit',
                'meta_query' => array(
                    array(
                        'key' => 'isc_image_posts',
                    ),
                )
            );
            $images_with_posts = new WP_Query($args);

            if($images_with_posts->have_posts()){
                require_once(ISCPATH . '/templates/image_posts_list.php');
            }

            wp_reset_postdata();

            die();
        }

    }// end of class
}
