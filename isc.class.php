<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

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
            add_action('the_content', array($this, 'content_filter'));

            // insert all backend functions below this check

            if (is_admin()) {
                register_activation_hook(ISCPATH . '/isc.php', array($this, 'activation'));

                add_action('add_attachment', array($this, 'attachment_added'), 10, 2);
                add_filter('attachment_fields_to_edit', array($this, 'add_isc_fields'), 10, 2);
                add_filter('attachment_fields_to_save', array($this, 'isc_fields_save'), 10, 2);

                add_action('admin_notices', array($this, 'admin_notices'));

                add_action('admin_menu', array($this, 'create_menu'));
                add_action('admin_init', array($this, 'SAPI_init'));

                add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));
                add_action( 'admin_print_scripts', array($this, 'admin_headjs') );

                // save image information in meta field when a post is saved
                add_action('save_post', array($this, 'save_image_information_on_post_save'));
            }
        }

        /**
         * load an image source by url
         *
         * @updated 1.3.5
         * @param string $url url of the image
         * @return type
         */
        public function get_source_by_url($url)
        {
            $options = $this->get_isc_options();
            $id = $this->get_image_by_url($url);
            $metadata['source'] = get_post_meta($id, 'isc_image_source', true);
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

        public function content_filter($content)
        {
            $options = $this->get_isc_options();
            if ($options['source_on_image']) {
                $pattern = '#(\[caption.*align="(.+)"[^\]*]{0,}\])? *(<a [^>]+>)? *(<img .*class=".*(align\d{4,})?.*wp-image-(\d+)\D*".*src="(.+)".*/?>).*(?(3)(?:</a>)|.*).*(?(1)(?:\[/caption\])|.*)#isU';
                $count = preg_match_all($pattern, $content, $matches);
                if (false !== $count) {
                    for ($i=0; $i < $count; $i++) {
                        $id = $matches[6][$i];
                        $src = $matches[7][$i];
                        $source = '<p class="isc-source-text">' . $options['source_pretext'] . ' ' . $this->get_source_by_url($src) . '</p>';
                        $old_content = $matches[0][$i];
                        $new_content = str_replace('wp-image-' . $id, 'wp-image-' . $id . ' with-source', $old_content);
                        $alignment = (!empty($matches[1][$i]))? $matches[2][$i] : $matches[5][$i];

                        $content = str_replace($old_content, '<div id="isc_attachment_' . $id . '" class="isc-source ' . $alignment . '"> ' . $new_content . $source . '</div>', $content);
                    }
                }
            }
            return $content;
        }

        public function attachment_added($att_id)
        {
            foreach ($this->_fields as $field) {
                update_post_meta($att_id, $field['id'], $field['default']);
            }
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
         * create the menu pages for isc
         */
        public function create_menu()
        {
            global $isc_missing;
            global $isc_setting;

            // These pages should be available only for editors and higher
            $isc_missing = add_submenu_page('upload.php', 'missing image sources by Image Source Control Plugin', __('Missing Sources', ISCTEXTDOMAIN), 'edit_others_posts', 'isc_missing_sources_page', array($this, 'render_missing_sources_page'));
            $isc_setting = add_options_page(__('Image control - ISC plugin', ISCTEXTDOMAIN), __('Image Control', ISCTEXTDOMAIN), 'edit_others_posts', 'isc_settings_page', array($this, 'render_isc_settings_page'));
        }

        /**
         * add scripts to admin pages
         * @since 1.0
         * @update 1.1.1
         */
        public function add_admin_scripts($hook)
        {
            global $isc_setting;
            if ('post.php' == $hook) {
                // 2013-12-11 (maik) quick fix for post.php.js to avoid access conflicts caused by other plugins due to by inconsistent naming
                wp_enqueue_script('isc_postphp_script', plugins_url('/js/post.js', __FILE__), array('jquery'), ISCVERSION);
            }
            if ($hook == $isc_setting) {
                wp_enqueue_script('isc_script', plugins_url('/js/isc.js', __FILE__), false, ISCVERSION);
                wp_enqueue_style('isc_image_settings_css', plugins_url('/css/image-settings.css', __FILE__), false, ISCVERSION);
            }
        }

        /**
         * add custom field to attachment
         * @param arr $form_fields
         * @param object $post
         * @return arr
         * @since 1.0
         * @updated 1.1
         * @updated 1.3.5 added field for licence
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

            // add input field for source
            $options = $this->get_isc_options();
            if($options['enable_licences'] && $licences = $this->licences_text_to_array($options['licences'])) {
                $form_fields['isc_image_licence']['input'] = 'html';
                $form_fields['isc_image_licence']['label'] = __('Image Licence', ISCTEXTDOMAIN);
                $form_fields['isc_image_licence']['helps'] = __('Choose the image licence.', ISCTEXTDOMAIN);
                $html = '<select name="attachments['.$post->ID.'][isc_image_licence]" id="attachments['.$post->ID.'][isc_image_licence]">';
                    $html .= '<option value="">--</option>';
                foreach($licences as $_licence_name => $_licence_data) {
                    $html .= '<option value="'.$_licence_name.'" '.selected(get_post_meta($post->ID, 'isc_image_licence', true), $_licence_name, false) .'>'.$_licence_name.'</option>';
                }
                $html .= '</select>';
                $form_fields['isc_image_licence']['html'] = $html;
            }

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
            $own = (isset($attachment['isc_image_source_own'])) ? $attachment['isc_image_source_own'] : '';
            update_post_meta($post['ID'], 'isc_image_source_own', $own);
            if (isset($attachment['isc_image_licence'])) {
                update_post_meta($post['ID'], 'isc_image_licence', $attachment['isc_image_licence']);
            }
            return $post;
        }

        /**
         * create image sources list for all images of this post
         * @since 1.0
         * @updated 1.1, 1.3.5
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

            // get licence array
            $options = $this->get_isc_options();
            if(!$options['enable_licences']) {
                $licences = false;
            } else {
                $licences = $this->licences_text_to_array($options['licences']);
                if($licences == false) $licences = array();
            }

            $return = '';
            if (!empty($attachments)) {
                $atts = array();
                foreach ($attachments as $attachment_id => $attachment_array) {
                    $atts[$attachment_id]['title'] = get_the_title($attachment_id);
                    $own = get_post_meta($attachment_id, 'isc_image_source_own', true);
                    $source = get_post_meta($attachment_id, 'isc_image_source', true);

                    // remove if no information set or author images are not to be displayed
                    if ( ($own == '' && $source == '' ) || ($own != '' && $this->_options['exclude_own_images'])) {
                        unset($atts[$attachment_id]);
                        continue;
                    } elseif ($own != '') {
                        if ($this->_options['use_authorname']) {
                            $authorname = '';
                            $att_post = get_post($attachment_id);
                            if (null !== $att_post) {
                                $authorname = get_the_author_meta('display_name', $att_post->post_author);
                            }
                            $atts[$attachment_id ]['source'] = $authorname;
                        } else {
                            $atts[$attachment_id ]['source'] = $this->_options['by_author_text'];
                        }
                    } else {
                        $atts[$attachment_id ]['source'] = $source;
                    }

                    // add licence information
                    // TODO maybe don’t display an unused licence (e.g. removed from the licence textarea)
                    if(is_array($licences)) {
                        $_licence = get_post_meta($attachment_id, 'isc_image_licence', true);
                        if(isset($licences[$_licence]['url'])) {
                            $atts[$attachment_id]['licence_url'] = $licences[$_licence]['url'];
                        }
                        if($_licence) $atts[$attachment_id]['licence'] = $_licence;
                    }
                }

                $return = $this->_renderAttachments($atts);
            }

            return $return;
        }

        /**
         * render attachment list
         *
         * @param array $attachments
         * @updated 1.3.5
         */
        protected function _renderAttachments($attachments)
        {
            // don't display anything, if no image sources displayed
            if ($attachments == array()) {
                return ;
            }

            $options = $this->get_isc_options();
            $show_text = __('Show the list', ISCTEXTDOMAIN);
            $hide_text = __('Hide the list', ISCTEXTDOMAIN);

            ob_start();
            $headline = $this->_options['image_list_headline'];
            $hide_style = ($options['hide_list'])? 'style="height: 0px; overflow: hidden;"': 'style="height: auto; overflow: hidden;"';
            $hide_class = ($options['hide_list'])? ' isc-list-up': ' isc-list-down';
            $hide_title = ($options['hide_list'])? $show_text : $hide_text;
            ?><div class="isc_image_list_box"><?php
            printf('<p class="isc_image_list_title" title="%2$s" style="cursor: pointer;">%1$s</p>', $headline, $hide_title); ?>
            <script type="text/javascript">
                /* <!--[CDATA[ */
                    isc_jstext = {
                        show_list: "<?php echo esc_attr($show_text); ?>",
                        hide_list: "<?php echo esc_attr($hide_text); ?>"
                    }
                /* ]]--> */
            </script>
            <ul class="isc_image_list <?php echo $hide_class; ?>"<?php echo $hide_style; ?>><?php

            foreach ($attachments as $atts_id => $atts_array) {
                if (empty($atts_array['source'])) {
                    continue;
                }
                // TODO find a more flexible way to create the source information in less lines
                if($options['enable_licences'] && isset($atts_array['licence']))
                    if($atts_array['licence_url']) {
                        printf('<li>%1$s: %2$s | <a href="%4$s" target="_blank" rel="nofollow">%3$s</a></li>', $atts_array['title'], $atts_array['source'], $atts_array['licence'], $atts_array['licence_url']);
                    } else {
                        printf('<li>%1$s: %2$s | %3$s</li>', $atts_array['title'], $atts_array['source'], $atts_array['licence']);
                    }
                else
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
        * Display scripts in <head></head> section of admin page. Useful for creating js variables in the js global namespace.
        */
        public function admin_headjs()
        {
            global $pagenow;
            $options = $this->get_isc_options();
            if ('post.php' == $pagenow) {
                ?>
                <script type="text/javascript">
				/* <![CDATA[ */
                    isc_data = {
                        warning_nosource : <?php echo (($options['warning_nosource'])? 'true' : 'false'); ?>,
                        block_form_message : '<?php _e('Please specify the image source', ISCTEXTDOMAIN); ?>'
                    }
				/* ]]> */
                </script>
                <?php
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
                $_imgs = false;
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
                        $source = $this->_common_texts['not_available'];
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
                * Here, all jobs to perform during first activation, especially options and custom fields.
                * Important: NO add_action('something', 'somefunction') here.
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
            $default['source_on_image'] = false;
            $default['source_pretext'] = __('Source:', ISCTEXTDOMAIN);
            $default['enable_licences'] = false;
            $default['licences'] = __("CC BY 2.0|http://creativecommons.org/licenses/by/2.0/legalcode", ISCTEXTDOMAIN);
            return $default;
        }

        /**
         * Settings API initialization
         *
         * @update 1.3.5 added settings for sources
        */
        public function SAPI_init()
        {
            $this->upgrade_management();
            register_setting('isc_options_group', 'isc_options', array($this, 'settings_validation'));
            add_settings_section('isc_settings_section', '', '__return_false', 'isc_settings_page');

            // Starts Page/Post settings group
            add_settings_field('image_list_headline', __('Image list headline', ISCTEXTDOMAIN), array($this, 'renderfield_list_headline'), 'isc_settings_page', 'isc_settings_section');
            /**
            * All new setting in Page/Post group Here!
            */
            add_settings_field('hide_list', __('Hide the image list', ISCTEXTDOMAIN), array($this, 'renderfield_hide_list'), 'isc_settings_page', 'isc_settings_section');
            // Ends Page/Post settings group

            // Starts Full images list group
            add_settings_field('use_thumbnail', __("Use thumbnails in images list", ISCTEXTDOMAIN), array($this, 'renderfield_use_thumbnail'), 'isc_settings_page', 'isc_settings_section');
            /**
            * All new setting in Full images list group Here!
            */
            add_settings_field('thumbnail_width', __("Thumbnails max-width", ISCTEXTDOMAIN), array($this, 'renderfield_thumbnail_width'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('thumbnail_height', __("Thumbnails max-height", ISCTEXTDOMAIN), array($this, 'renderfield_thumbnail_height'), 'isc_settings_page', 'isc_settings_section');
            // Ends Full images list group

            // Starts Licence settings group
            add_settings_field('enable_licences', __("Enable licences", ISCTEXTDOMAIN), array($this, 'renderfield_enable_licences'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('licences', __('List of licences', ISCTEXTDOMAIN), array($this, 'renderfield_licences'), 'isc_settings_page', 'isc_settings_section');

            // Starts Misc settings group
            add_settings_field('exclude_own_images', __('Exclude own images', ISCTEXTDOMAIN), array($this, 'renderfield_exclude_own_images'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('use_authorname', __('Use authors names', ISCTEXTDOMAIN), array($this, 'renderfield_use_authorname'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('by_author_text', __('Custom text for owned images', ISCTEXTDOMAIN), array($this, 'renderfield_byauthor_text'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('webgilde_backlink', __("Link to webgilde's website", ISCTEXTDOMAIN), array($this, 'renderfield_webgile'), 'isc_settings_page', 'isc_settings_section');
            /**
            * All new setting in Misc settings group Here!
            */
            add_settings_field('source_caption', __("Source as caption on image", ISCTEXTDOMAIN), array($this, 'renderfield_source_caption'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('caption_position', __("Caption position", ISCTEXTDOMAIN), array($this, 'renderfield_caption_pos'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('warning_one_source', __("Warning when there is at least one missing source", ISCTEXTDOMAIN), array($this, 'renderfield_warning_onesource_misisng'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('warning_nosource', __("Warnings when source not available", ISCTEXTDOMAIN), array($this, 'renderfield_warning_nosource'), 'isc_settings_page', 'isc_settings_section');
            // Ends Misc settings group
        }

        /**
        * manage data structure upgrading of outdated versions
        */
        public function upgrade_management() {

            /*
             * Since the activation hook is not executed on plugin upgrade, this function checks options in database
             * during the admin_init hook to handle plugin's upgrade.
             */

            $options = get_option('isc_options');

            if (!is_array($options)) {
                // special case for version prior to 1.2 (which don't have options)
                $options = $this->default_options();
                $this->init_image_posts_metafield();
                $options['installed'] = true;
                update_option('isc_options', $options);
            }

            if (ISCVERSION != $options['version']) {
                $options = $options + $this->default_options();
                $options['version'] = ISCVERSION;
                update_option('isc_options', $options);
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
            <div id="isc-admin-wrap">
                <form id="image-control-form" method="post" action="options.php">
                    <div class="postbox isc-setting-group"><?php // Open the div for the first settings group ?>
                    <h3 class="setting-group-head"><?php _e('Post / Page images list', ISCTEXTDOMAIN); ?></h3>
                    <?php
                        settings_fields( 'isc_options_group' );
                        do_settings_sections( 'isc_settings_page' );
                    ?>
                    </div><?php //Close the last settings group div ?>
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
                    </p>
                </form>
            </div><!-- #isc-admin-wrap -->
            <?php
        }

        /**
        * Missing sources page callback
        */
        public function render_missing_sources_page()
        {
            require_once(ISCPATH . '/templates/missing_sources.php');
        }

        /**
        * *******************************
        * WordPress Setting API Callbacks
        * *******************************
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

        public function renderfield_hide_list()
        {
            $options = $this->get_isc_options();
            $description = __("Hide the list when the post is loaded. A simple click on the list headline will show the list content.", ISCTEXTDOMAIN);
            ?>
            <div id="hide-list-block">
                <label for="hide-list"><?php _e('Hide the image list of a post', ISCTEXTDOMAIN) ?></label>
                <input type="checkbox" name="isc_options[hide_list]" id="hide-list" <?php checked($options['hide_list']); ?> />
                <p><em><?php echo $description; ?></em></p>
            </div>
            </td></tr></tbody></table>
            </div><!-- .postbox -->
            <div class="postbox isc-setting-group">
            <h3 class="setting-group-head"><?php _e('Full images list', ISCTEXTDOMAIN) ?></h3>
            <table class="form-table"><tbody>
            <?php
        }

        /**
         * render option to exclude image from lists if it is makes as "by the author"
         *
         * @since 1.3.7
         */
        public function renderfield_exclude_own_images()
        {
            $options = $this->get_isc_options();
            $description = __("Exclude images maked as 'own image' from image lists (post and all) in the frontend. You can still manage them in the dashboard.", ISCTEXTDOMAIN);

            ?>
            <div id="use-authorname-block">
                <label for="exclude_own_images"><?php _e('Exclude own images from lists', ISCTEXTDOMAIN) ?></label>
                <input type="checkbox" name="isc_options[exclude_own_images]" id="exclude_own_images" <?php checked($options['exclude_own_images']); ?> />
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }

        public function renderfield_use_authorname()
        {
            $options = $this->get_isc_options();
            $description = __("Display the author's public name as source when the image is owned by the author (the uploader of the image, not necessarily the author of the post the image is displayed on). Uncheck to use a custom text instead.", ISCTEXTDOMAIN);

            ?>
            <div id="use-authorname-block">
                <label for="use_authorname"><?php _e('Use author name', ISCTEXTDOMAIN) ?></label>
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

        public function renderfield_enable_licences()
        {
            $options = $this->get_isc_options();
            $description = __("Enable this to be able to add and display copyright/copyleft licences for your images and manage them in the field below.", ISCTEXTDOMAIN);

            ?>
            <div id="enable-licences">
                <input type="checkbox" name="isc_options[enable_licences]" id="enable_licences" <?php checked($options['enable_licences']); ?> />
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }

        public function renderfield_licences()
        {
            $options = $this->get_isc_options();
            $description = __('List of licences the author can choose for an image. Enter a licence per line and separate the name from the optional link with a pipe symbol (e.g. <em>CC BY 2.0|http://creativecommons.org/licenses/by/2.0/legalcode</em>).' ,ISCTEXTDOMAIN);
            ?>
            <div id="licences">
                <textarea name="isc_options[licences]"><?php echo $options['licences'] ?></textarea>
                <p><em><?php echo $description; ?></em></p>
            </div>
            </td></tr></tbody></table>
            </div><!-- .postbox -->
            <div class="postbox isc-setting-group">
            <h3 class="setting-group-head"><?php _e('Miscellaneous settings', ISCTEXTDOMAIN); ?></h3>
            <table class="form-table"><tbody><tr>
            <?php
        }

        public function renderfield_webgile()
        {
            $options = $this->get_isc_options();
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
            $description = __('Display thumbnails on the list of all images in the blog.' ,ISCTEXTDOMAIN);
            ?>
            <div id="use-thumbnail-block">
                <input type="checkbox" id="use-thumbnail" name="isc_options[use_thumbnail]" value="1" <?php checked($options['thumbnail_in_list']); ?> />
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
            $description = __('Custom value of the maximum allowed height for thumbnail.' ,ISCTEXTDOMAIN);
            ?>
            <div id="thumbnail-custom-height">
                <input type="text" id="custom-height" name="isc_options[thumbnail_height]" class="small-text" value="<?php echo $options['thumbnail_height'] ?>"/> px
                <p><em><?php echo $description; ?></em></p>
            </div>
            </td></tr></tbody></table>
            </div><!-- .postbox -->
            <div class="postbox isc-setting-group">
            <h3 class="setting-group-head"><?php _e('Licences settings', ISCTEXTDOMAIN); ?></h3>
            <table class="form-table"><tbody><tr>
            <?php
        }

        public function renderfield_warning_nosource()
        {
            $options = $this->get_isc_options();
            $description = __('Warn and prevent data to be saved when an attachment is edited and the source has not been specified.' ,ISCTEXTDOMAIN);
            ?>
            <div id="no-source-block">
                <input type="checkbox" id="no-source" name="isc_options[no_source]"value="1" <?php checked($options['warning_nosource']); ?>/>
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }

        public function renderfield_warning_onesource_misisng()
        {
            $options = $this->get_isc_options();
            $description = __('Display an admin notice in admin pages when one or more image sources are missing.' ,ISCTEXTDOMAIN);
            ?>
            <div id="one-source-block">
                <input type="checkbox" id="one-source" name="isc_options[one_source]"value="1" <?php checked($options['warning_onesource_missing']); ?>/>
                <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }

        public function renderfield_caption_pos()
        {
            $options = $this->get_isc_options();
            $description = __('Position of captions into images' ,ISCTEXTDOMAIN);
            ?>
            <div id="caption-position-block">
                    <select id="caption-pos" name="isc_options[cap_pos]">
                        <?php foreach ($this->_caption_position as $pos) : ?>
                            <option value="<?php echo $pos; ?>" <?php selected($pos, $options['caption_position']); ?>><?php echo $pos; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p><em><?php echo $description; ?></em></p>
            </div>
            <?php
        }

        public function renderfield_source_caption()
        {
            $options = $this->get_isc_options();
            $description_checkbox = __('Tick to display source onto each image.' ,ISCTEXTDOMAIN);
            $description_textfield = __('The text preceding the source on each image.' ,ISCTEXTDOMAIN);
            ?>
            <div id="caption-block">
                <input type="checkbox" id="source-on-image" value="1" name="isc_options[source_on_image]" <?php checked($options['source_on_image']); ?> />
                <p><em><?php echo $description_checkbox; ?></em></p>
                <input type="text" id='source-pretext' name="isc_options[source_pretext]" value="<?php echo $options['source_pretext']; ?>" />
                <p><em><?php echo $description_textfield; ?></em></p>
            </div>
            <?php
        }

        /**
        * ****************************
        * End of Setting API Callbacks
        * ****************************
        */

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
            if (isset($input['source_on_image'])) {
                $output['source_on_image'] = true;
                $output['source_pretext'] = $input['source_pretext'];
            } else {
                $output['source_on_image'] = false;
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
        * Search for missing sources
        */
        public function admin_notices()
        {
            $args = array(
                'post_type' => 'attachment',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => null,
                'meta_query' => array(
                    array(
                        'key' => 'isc_image_source',
                        'value' => '',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'isc_image_source_own',
                        'value' => '',
                        'compare' => ''
                    )
                )
            );
            $attachments = get_posts($args);
            $options = $this->get_isc_options();
            if (!empty($attachments) && $options['warning_onesource_missing'] ) {
            $missing_src = esc_url(admin_url('upload.php?page=isc_missing_sources_page'));
            ?>
                <div class="updated"><p><?php printf(__('One or more attachments still have no source. See the <a href="%s">missing sources</a> list', ISCTEXTDOMAIN), $missing_src);?></p></div>
            <?php
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
}
