<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists('ISC_Admin')) {

    /**
     * handles all admin functionalities
     *
     * @since 1.7 - move a lot of functions here from general class
     */
    class ISC_Admin extends ISC_Class {

        /**
         * initiate admin functions
         *
         * @since 1.7
         */
        public function __construct() {
            
            parent::__construct();
            
            register_activation_hook(ISCPATH . '../isc.php', array($this, 'activation'));

            // attachment field handling
            add_action('add_attachment', array($this, 'attachment_added'), 10, 2);
            add_filter('attachment_fields_to_edit', array($this, 'add_isc_fields'), 10, 2);
            add_filter('attachment_fields_to_save', array($this, 'isc_fields_save'), 10, 2);

            // save image information in meta field when a post is saved
            add_action('save_post', array($this, 'save_image_information_on_post_save'));

            // admin notices
            add_action('admin_notices', array($this, 'admin_notices'));

            // settings page
            add_action('admin_menu', array($this, 'create_menu'));
            add_action('admin_init', array($this, 'SAPI_init'));

            // scripts and styles
            add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));
            add_action( 'admin_print_scripts', array($this, 'admin_headjs') );

            // ajax calls
            add_action('wp_ajax_isc-post-image-relations', array($this, 'list_post_image_relations'));
            add_action('wp_ajax_isc-image-post-relations', array($this, 'list_image_post_relations'));
        }

        /**
        * Search for missing sources and display a warning if found some
        */
        public function admin_notices()
        {

            // attachments without sources
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
                        'value' => '1',
                        'compare' => '!=',
                    )
                )
            );
            $attachments = get_posts($args);

            // load unindexed attachments
            $args = array(
                'post_type' => 'attachment',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => null,
                'meta_query' => array(
                    array(
                        'key' => 'isc_image_source',
                        'value' => 'any',
                        'compare' => 'NOT EXISTS'
                    ),
                )
            );
            $attachments2 = get_posts($args);
            $options = $this->get_isc_options();
            if ((!empty($attachments) || !empty($attachments2)) && $options['warning_onesource_missing'] ) {
            $missing_src = esc_url(admin_url('upload.php?page=isc_missing_sources_page'));
            ?>
                <div class="error"><p><?php printf(__('One or more attachments still have no source. See the <a href="%s">missing sources</a> list', ISCTEXTDOMAIN), $missing_src);?></p></div>
            <?php
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
            if ('post.php' == $hook) {
                // 2013-12-11 (maik) quick fix for post.php.js to avoid access conflicts caused by other plugins due to by inconsistent naming
                wp_enqueue_script('isc_postphp_script', plugins_url('/assets/js/post.js', __FILE__), array('jquery'), ISCVERSION);
            }
            //if ($hook == $isc_setting) {
                wp_enqueue_script('isc_script', plugins_url('/assets/js/isc.js', __FILE__), false, ISCVERSION);
                wp_enqueue_style('isc_image_settings_css', plugins_url('/assets/css/image-settings.css', __FILE__), false, ISCVERSION);
            //}
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
         * add custom field to attachment
         *
         * @since 1.0
         * @updated 1.1
         * @updated 1.3.5 added field for licence
         * @updated 1.5 added field for url

         * @param arr $form_fields
         * @param object $post
         * @return arr
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

            // add input field for source url
            $form_fields['isc_image_source_url']['label'] = __('Image Source URL', ISCTEXTDOMAIN);
            $form_fields['isc_image_source_url']['value'] = get_post_meta($post->ID, 'isc_image_source_url', true);
            $form_fields['isc_image_source_url']['helps'] = __('URL to link the source text to.', ISCTEXTDOMAIN);

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
         *
         * @updated 1.5 added field for url
         *
         * @param object $post
         * @param $attachment
         * @return object $post
         */
        public function isc_fields_save($post, $attachment)
        {
            if (isset($attachment['isc_image_source'])) {
                update_post_meta($post['ID'], 'isc_image_source', trim($attachment['isc_image_source']));
            }
            if (isset($attachment['isc_image_source_url'])) {
                $url = esc_url_raw($attachment['isc_image_source_url']);
                update_post_meta($post['ID'], 'isc_image_source_url', $url);
            }
            $own = (isset($attachment['isc_image_source_own'])) ? $attachment['isc_image_source_own'] : '';
            update_post_meta($post['ID'], 'isc_image_source_own', $own);
            if (isset($attachment['isc_image_licence'])) {
                update_post_meta($post['ID'], 'isc_image_licence', $attachment['isc_image_licence']);
            }
            return $post;
        }

         /**
         * this is an entry function to save image information to a post when it is saved
         * @since 1.1
         * @param type $post_id
         */
        public function save_image_information_on_post_save($post_id)
        {
            // return, if save_post is called more than one time
            if ( did_action('save_post') !== 1
                || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
                || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
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
            if ( !empty( $_POST['content']) ) $_content = stripslashes($_POST['content']);

            // Needs to be called before the 'isc_post_images' field is updated.
            $this->update_image_posts_meta($post_id, $_content);

            $this->save_image_information($post_id, $_content);
        }


        public function attachment_added($att_id)
        {
            foreach ($this->_fields as $field) {
                update_post_meta($att_id, $field['id'], $field['default']);
            }
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

                /**
                 * auto indexation removed in version 1.6
                 * not needed due to NOT EXISTS for meta fields since WP 3.5
                 *
                 * @todo remove the functions completely
                 */

                // adds meta fields for attachments
                // $this->add_meta_values_to_attachments();

                // set all isc_image_posts meta fields.
                // $this->init_image_posts_metafield();

                $options['installed'] = true;
                update_option('isc_options', $options);
            }
        }

        /**
         * create the menu pages for isc
         *
         * @since 1.0
         */
        public function create_menu()
        {
            global $isc_missing;
            global $isc_setting;

            // These pages should be available only for editors and higher
            $isc_missing = add_submenu_page('upload.php', 'missing image sources by Image Source Control Plugin', __('Missing Sources', ISCTEXTDOMAIN), 'edit_others_posts', 'isc_missing_sources_page', array($this, 'render_missing_sources_page'));
            $isc_setting = add_options_page(__('Image control - ISC plugin', ISCTEXTDOMAIN), __('Image Sources', ISCTEXTDOMAIN), 'edit_others_posts', 'isc_settings_page', array($this, 'render_isc_settings_page'));
        }

        /**
         * Settings API initialization
         *
         * @update 1.3.5 added settings for sources
         * @todo rewrite this and following functions to a more practical form (esp. shorter) or at least bundle field into more useful sections
        */
        public function SAPI_init()
        {
            $this->upgrade_management();
            register_setting('isc_options_group', 'isc_options', array($this, 'settings_validation'));
            add_settings_section('isc_settings_section', '', '__return_false', 'isc_settings_page');

            // handle type of source display
            add_settings_field('source_display_type', __('How to display sources', ISCTEXTDOMAIN), array($this, 'renderfield_sources_display_type'), 'isc_settings_page', 'isc_settings_section');

            // settings for archive pages
            add_settings_field('list_on_archives', __('Sources below full posts', ISCTEXTDOMAIN), array($this, 'renderfield_list_on_archives'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('list_on_excerpts', __('Sources below excerpts', ISCTEXTDOMAIN), array($this, 'renderfield_list_on_excerpts'), 'isc_settings_page', 'isc_settings_section');

            // settings for sources list below single pages
            add_settings_field('image_list_headline', __('Image list headline', ISCTEXTDOMAIN), array($this, 'renderfield_list_headline'), 'isc_settings_page', 'isc_settings_section');

            // source in caption
            add_settings_field('source_caption', __("Overlay pre-text", ISCTEXTDOMAIN), array($this, 'renderfield_overlay_text'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('caption_position', __("Overlay position", ISCTEXTDOMAIN), array($this, 'renderfield_overlay_position'), 'isc_settings_page', 'isc_settings_section');

            // full image sources list group
            add_settings_field('use_thumbnail', __("Use thumbnails in images list", ISCTEXTDOMAIN), array($this, 'renderfield_use_thumbnail'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('thumbnail_width', __("Thumbnails max-width", ISCTEXTDOMAIN), array($this, 'renderfield_thumbnail_width'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('thumbnail_height', __("Thumbnails max-height", ISCTEXTDOMAIN), array($this, 'renderfield_thumbnail_height'), 'isc_settings_page', 'isc_settings_section');

            // Licence settings group
            add_settings_field('enable_licences', __("Enable licences", ISCTEXTDOMAIN), array($this, 'renderfield_enable_licences'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('licences', __('List of licences', ISCTEXTDOMAIN), array($this, 'renderfield_licences'), 'isc_settings_page', 'isc_settings_section');

            // Misc settings group
            add_settings_field('exclude_own_images', __('Exclude own images', ISCTEXTDOMAIN), array($this, 'renderfield_exclude_own_images'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('use_authorname', __('Use authors names', ISCTEXTDOMAIN), array($this, 'renderfield_use_authorname'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('by_author_text', __('Custom text for owned images', ISCTEXTDOMAIN), array($this, 'renderfield_byauthor_text'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('webgilde_backlink', __("Link to webgilde's website", ISCTEXTDOMAIN), array($this, 'renderfield_webgile'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('warning_one_source', __("Warning when there is at least one missing source", ISCTEXTDOMAIN), array($this, 'renderfield_warning_onesource_misisng'), 'isc_settings_page', 'isc_settings_section');
            add_settings_field('warning_nosource', __("Warnings when source not available", ISCTEXTDOMAIN), array($this, 'renderfield_warning_nosource'), 'isc_settings_page', 'isc_settings_section');
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

            if (is_array($options)) {
                // version 1.7 and higher
                if(version_compare('1.7', $options['version'], '>')){
                    // convert old into new settings
                    if(isset($options['attach_list_to_post'])){
                        $options['display_type'][] = 'list';
                    }
                    if(isset($options['source_on_image'])){
                        $options['display_type'][] = 'overlay';
                    }
                }
            } else {
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
                    <div id="isc-setting-group-type" class="postbox isc-setting-group"><?php // Open the div for the first settings group ?>
                    <h3 class="setting-group-head"><?php _e('How to display source in Frontend', ISCTEXTDOMAIN); ?></h3>
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
            require_once(ISCPATH . '/admin/templates/missing_sources.php');
        }

        /**
        * *******************************
        * WordPress Setting API Callbacks
        * *******************************
        */
        /**
         * choose type of sources display in the frontend
         *
         * @since 1.7
         */
        public function renderfield_sources_display_type()
        {
            $options = $this->get_isc_options();
            ?>
            <p class="description"><?php echo __('Choose where to display image sources in the frontend', ISCTEXTDOMAIN); ?></p><br/>
            <div id="display_types_block">
                <input type="hidden" name="isc_options[display_type]" value=""/>

                <input type="checkbox" name="isc_options[display_type][]" id="display-types-list" value="list" <?php checked(in_array('list', $options['display_type']), true); ?> />
                <label for="display-types-list"><?php echo __('list below content', ISCTEXTDOMAIN);; ?></label>
                <p class="description"><?php echo __('Displays a list of image sources below singular pages.', ISCTEXTDOMAIN); ?></p>

                <input type="checkbox" name="isc_options[display_type][]" id="display-types-overlay" value="overlay" <?php checked(in_array('overlay', $options['display_type']), true); ?> />
                <label for="display-types-overlay"><?php echo __('overlay', ISCTEXTDOMAIN);; ?></label>
                <p class="description"><?php echo __('Display image source as a simple overlay', ISCTEXTDOMAIN);; ?></p>

                <p><?php echo __('If you don’t want to use any of these methods, you can still place the image source list manually as described <a href="http://webgilde.com/en/image-source-control/image-sources-frontend/" title="external link" target="_blank">here</a>', ISCTEXTDOMAIN);; ?></p>
            </div>
            </td></tr></tbody></table>
            </div><!-- .postbox -->
            <div id="isc-setting-group-list" class="postbox isc-setting-group">
            <h3 class="setting-group-head"><?php _e('Archive Pages', ISCTEXTDOMAIN); ?></h3>
            <table class="form-table"><tbody>
            <?php
        }

        /**
         * select the option for sources on archive pages
         *
         * @since 1.8
         */
        public function renderfield_list_on_archives()
        {
            $options = $this->get_isc_options();
            ?>
            <div id="display_types_block">
                <input type="checkbox" name="isc_options[list_on_archives]" id="list-on-archives" value="1" <?php checked(1, $options['list_on_archives'], true); ?> />
                <label for="list-on-archives"><?php echo __('Display sources list below full posts', ISCTEXTDOMAIN);; ?></label>
                <p class="description"><?php echo __('Choose this option if you want to display the sources list attached to posts on archive and category pages that display the full content.', ISCTEXTDOMAIN); ?></p>
            </div>
            <?php
        }

        /**
         * select the option for sources on archive pages
         *
         * @since 1.8
         */
        public function renderfield_list_on_excerpts()
        {
            $options = $this->get_isc_options();
            ?>
            <div id="display_types_block">
                <input type="checkbox" name="isc_options[list_on_excerpts]" id="list-on-excerpts" value="1" <?php checked(1, $options['list_on_excerpts'], true); ?> />
                <label for="list-on-excerpts"><?php echo __('Display sources list below excerpts', ISCTEXTDOMAIN);; ?></label>
                <p class="description"><?php echo __('Choose this option if you want to display the source of the featured image below the post excerpt. The source will be attached to the excerpt and it might happen that you see it everywhere. If this happens you should display the source manually in your template.', ISCTEXTDOMAIN); ?></p>
            </div>
            </td></tr></tbody></table>
            </div><!-- .postbox -->
            <div id="isc-setting-group-list" class="postbox isc-setting-group">
            <h3 class="setting-group-head"><?php _e('List below content', ISCTEXTDOMAIN); ?></h3>
            <table class="form-table"><tbody>
            <?php
        }

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
            </td></tr></tbody></table>
            </div><!-- .postbox -->
            <div id="isc-setting-group-overlay" class="postbox isc-setting-group">
            <h3 class="setting-group-head"><?php _e('Overlay', ISCTEXTDOMAIN) ?></h3>
            <table class="form-table"><tbody>
            <?php
        }

        public function renderfield_overlay_text()
        {
            $options = $this->get_isc_options();
            ?>
            <div id="overlay-block">
                <input type="text" id='source-pretext' name="isc_options[source_pretext]" value="<?php echo $options['source_pretext']; ?>" />
                <p><em><?php echo __('The text preceding the source.' ,ISCTEXTDOMAIN); ?></em></p>
            </div>
            <?php
        }

        public function renderfield_overlay_position()
        {
            $options = $this->get_isc_options();
            $description = __('Position of overlay into images' ,ISCTEXTDOMAIN);
            ?>
            <div id="caption-position-block">
                    <select id="caption-pos" name="isc_options[cap_pos]">
                        <?php foreach ($this->_caption_position as $pos) : ?>
                            <option value="<?php echo $pos; ?>" <?php selected($pos, $options['caption_position']); ?>><?php echo $pos; ?></option>
                        <?php endforeach; ?>
                    </select>
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
            $description = __("Exclude images marked as 'own image' from image lists (post and full) and overlay in the frontend. You can still manage them in the dashboard.", ISCTEXTDOMAIN);

            ?>
            <div id="use-authorname-block">
                <label for="exclude_own_images"><?php _e('Hide sources for own images', ISCTEXTDOMAIN) ?></label>
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

        /**
        * ****************************
        * End of Setting API Callbacks
        * ****************************
        */

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
                require_once(ISCPATH . '/admin/templates/post_images_list.php');
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
                require_once(ISCPATH . '/admin/templates/image_posts_list.php');
            }

            wp_reset_postdata();

            die();
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
            if(isset($input['list_on_archives'])){
                $output['list_on_archives'] = true;
            } else {
                $output['list_on_archives'] = false;
            }
            if(isset($input['list_on_excerpts'])){
                $output['list_on_excerpts'] = true;
            } else {
                $output['list_on_excerpts'] = false;
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
            if (isset($input['source_pretext'])) {
                $output['source_pretext'] = esc_textarea($input['source_pretext']);
            }
            return $output;
        }

    }
}