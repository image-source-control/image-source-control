<?php
//avoid direct calls to this file
if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}
if (!class_exists('ISC_Public')) {

    /**
     * handles all admin functionalities
     *
     * @since 1.7
     * @todo move frontend-only functions from general class here
     */
    class ISC_Public extends ISC_Class {

        public function __construct() {
            parent::__construct();

            add_action('wp_enqueue_scripts', array($this, 'front_scripts'));
            add_action('wp_head', array($this, 'front_head'));

            /**
             * filters need to be above 10 in order to interpret also gallery shortcode
             */
            add_filter('the_content', array($this, 'content_filter'), 20);
            add_filter('the_excerpt', array($this, 'excerpt_filter'), 20);
            add_shortcode('isc_list', array($this, 'list_post_attachments_with_sources_shortcode'));
            add_shortcode('isc_list_all', array($this, 'list_all_post_attachments_sources_shortcode'));
        }

        /**
        * Enqueue scripts for the front-end.
        */
        public function front_scripts() {
            // inject in footer as we only do stuff after dom-ready
            wp_enqueue_script('isc_front_js', plugins_url('/assets/js/front-js.js', __FILE__), array('jquery'), ISCVERSION, true);
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
            <style>
                .isc-source { position: relative; }
            </style>
            <?php
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
            if( isset($options['display_type']) && is_array($options['display_type']) && in_array('overlay', $options['display_type'] ) ) {
                /**
                 * split content where `isc_stop_overlay` is found to not display overlays starting there
                 */
                if( strpos( $content, 'isc_stop_overlay' ) ){
                    list( $content, $content_after )  = explode( 'isc_stop_overlay', $content, 2 );
                } else {
                    $content_after = '';
                }

                /**
                 * removed [caption], because this check runs after the hook that interprets shortcodes
                 * img tag is checked individually since there is a different order of attributes when images are used in gallery or individually
                 *
                 * 0 – full match
                 * 1 - <figure> if set
                 * 2 – alignment
                 * 3 – inner code starting with <a>
                 * 4 – opening link attribute
                 * 5 – "rel" attribute from link tag
                 * 6 – image id from link wp-att- value in "rel" attribute
                 * 7 – full img tag
                 * 8 – image URL
                 * 9 – (unused)
                 * 10 - </figure>
                 *
                 * tested with:
                 * * with and without [caption]
                 * * with and without link attibute
                 *
                 * potential issues:
                 * * line breaks in the code
                 */
                $pattern = '#(<[^>]*class="[^"]*(alignleft|alignright|alignnone|aligncenter|rev-slidebg).*)?((<a [^>]*(rel="[^"]*[^"]*wp-att-(\d+)"[^>]*)>)? *(<img [^>]*[^>]*src="(.+)".*\/?>).*(</a>)??[^<]*).*(<\/figure.*>)?#isU';
                $count = preg_match_all($pattern, $content, $matches);

                // error_log(print_r($content, true)); error_log(print_r($matches, true));
                if (false !== $count) {
                    for ($i=0; $i < $count; $i++) {

                        /**
                         * interpret the image tag
                         *
                         * we only need the ID if we don’t have it yet
                         * it can be retrieved from "wp-image-" class (single) or "aria-describedby="gallery-1-34" in gallery
                         */
                        $id = $matches[6][$i];
                        $img_tag = $matches[7][$i];

                        if( ! $id ){
                            $success = preg_match('#wp-image-(\d+)|aria-describedby="gallery-1-(\d+)#is', $img_tag, $matches_id);
                            if( $success ){
                                $id = $matches_id[1] ? intval( $matches_id[1] ) : intval( $matches_id[2] );
                            }
                        }

                        // if ID is still missing get image by URL
                        if( ! $id ){
                            $src = $matches[8][$i];
                            $id = $this->get_image_by_url($src);
                        }

                        // don’t show caption for own image if admin choose not to do so
                        if($options['exclude_own_images']){
                            if(get_post_meta($id, 'isc_image_source_own', true)) {
                                continue;
                            }
                        }

                        // don’t display empty sources
                        if( !$source_string = $this->render_image_source_string( $id ) ) {
                            continue;
                        }

                        // get any alignment from the original code
                        preg_match('#alignleft|alignright|alignnone|aligncenter|rev-slidebg#is', $matches[0][$i], $matches_align);
                        $alignment = isset( $matches_align[0] ) ? $matches_align[0] : '';
						
                        // revolution slider is not working with overlay source
                        if($alignment == 'rev-slidebg') {
                            continue;
                        }

                        $source = '<span class="isc-source-text">' . $options['source_pretext'] . ' ' . $source_string . '</span>';
                        $old_content = $matches[3][$i];
                        $new_content = str_replace('wp-image-' . $id, 'wp-image-' . $id . ' with-source', $old_content);

                        $content = str_replace($old_content, '<div id="isc_attachment_' . $id . '" class="isc-source ' . $alignment . '"> ' . $new_content . $source . '</div>', $content);

                    }
                }
                /**
                 * attach follow content back
                 */
                $content = $content . $content_after;
            }

            // attach image source list to content, if option is enabled
            if ((isset($options['list_on_archives']) && $options['list_on_archives']) ||
                    (is_singular() && isset($options['display_type']) && is_array($options['display_type']) && in_array('list', $options['display_type']))) {
                $content = $content . $this->list_post_attachments_with_sources();
            }

            return $content;
        }

        /**
         * add image source of featured image to post excerpts
         *
         * @param string $excerpt post excerpt
         * @return string $excerpt
         *
         * @update 1.4.3
         */
        public function excerpt_filter($excerpt)
        {

            // display inline sources
            $options = $this->get_isc_options();
            $post = get_post();

            if (empty($options['list_on_excerpts'])) return $excerpt;

            $source_string = $this->get_thumbnail_source_string($post->ID);

            $excerpt = $excerpt . $source_string;

            return $excerpt;
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
                    // unregister our content filter in order to prevent infinite loops when calling the_content in the next steps
                    remove_filter( 'the_content', array( $this, 'content_filter' ), 20 );

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
         * @updated 1.5 removed rendering the license to an earlier function
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
                'next_text' => 'Next &#187;',
                'included' => 'displayed'
                ),
                $atts));

            if ('&#171; Previous' == $prev_text)
                $prev_text = __('&#171; Previous', 'image-source-control-isc');
            if ('Next &#187;' == $next_text)
                $next_text = __('Next &#187;', 'image-source-control-isc');

            // retrieve all attachments
            $args = array(
                'post_type' => 'attachment',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => null
            );

            // check mode
            if( $included === 'all' ){
                // load all images

            } else { // load only images attached to posts
                $args['meta_query'] = array(
                    array(
                        'key' => 'isc_image_posts',
                        'value' => 'a:0:{}',
                        'compare' => '!='
                    )
                );
            }

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
                    $usage_data_array = array();
                    foreach($metadata as $data) {
                        // only list published posts
                        if(get_post_status($data) == 'publish') {
                            $usage_data_array[] = sprintf(__('<li><a href="%1$s" title="View %2$s">%3$s</a></li>', 'image-source-control-isc'),
                                esc_url(get_permalink($data)),
                                esc_attr(get_the_title($data)),
                                esc_html(get_the_title($data))
                            );
                        }
                    }
                    if ( 'all' !== $included && $usage_data_array === array() ) {
                        unset($connected_atts[$_attachment->ID]);
                        continue;
                    }
                    $usage_data .= implode( '', $usage_data_array );
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
                <p class="isc-backlink"><?php printf(__('Image list created by <a href="%s" title="Image Source Control">Image Source Control Plugin</a>', 'image-source-control-isc'), WEBGILDE); ?></p>
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

            /**
             * added comment `isc_stop_overlay` as a class to the table to suppress overlays within it starting at that point
             * todo: allow overlays to start again after the table
             *
             */
            ?><div class="isc_all_image_list_box isc_stop_overlay">
            <table>
                <thead>
                    <?php if ($options['thumbnail_in_list']) : ?>
                        <th><?php _e('Thumbnail', 'image-source-control-isc'); ?></th>
                    <?php endif; ?>
                    <th><?php _e("Attachment's ID", 'image-source-control-isc'); ?></th>
                    <th><?php _e('Title', 'image-source-control-isc'); ?></th>
                    <th><?php _e('Attached to', 'image-source-control-isc'); ?></th>
                    <th><?php _e('Source', 'image-source-control-isc'); ?></th>
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
         * get source string of a feature image
         *
         * @since 1.8
         * @param $post_id
         * @return source string
         */
        function get_thumbnail_source_string($post_id = 0){

            if(empty($post_id)) return '';

            $options = $this->get_isc_options();

            if(has_post_thumbnail($post_id)){
                $id = get_post_thumbnail_id($post_id);
                $thumb = get_post($post_id);

                // don’t show caption for own image if admin choose not to do so
                if($options['exclude_own_images']){
                    if(get_post_meta($id, 'isc_image_source_own', true)) return '';
                }
                // don’t display empty sources
                $src = $thumb->guid;
                if(!$source_string = $this->render_image_source_string($id)) return '';

                return '<p class="isc-source-text">' . $options['source_pretext'] . ' ' . $source_string . '</p>';
            }

            return '';
        }

        /**
         * load an image source string by url
         *
         * @updated 1.5
         * @deprecated since 1.9
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
         * render source string of single image by its id
         *  this only returns the string with source and license (and urls),
         *  but no wrapping, because the string is used in a lot of functions
         *  (e.g. image source list where title is prepended)
         *
         * @updated 1.5 wrapped source into source url
         *
         * @param int $id id of the image
         * @return bool|string false if no source was given, else string with source
         */
        public function render_image_source_string($id){
            $id = absint($id);

            $options = $this->get_isc_options();

            $metadata['source'] = get_post_meta($id, 'isc_image_source', true);
            $metadata['source_url'] = get_post_meta($id, 'isc_image_source_url', true);
            $metadata['own'] = get_post_meta($id, 'isc_image_source_own', true);
            $metadata['licence'] = get_post_meta($id, 'isc_image_licence', true);

            $source = '';

            $att_post = get_post($id);

            if ('' != $metadata['own']) {
                if ($this->_options['use_authorname']) {
                    if (!empty($att_post)) {
                        $source = get_the_author_meta('display_name', $att_post->post_author);
                    }
                } else {
                    $source = $this->_options['by_author_text'];
                }
            } else {
                if ('' != $metadata['source']) {
                    $source = $metadata['source'];
                }
            }

            if($source == '') return false;

            // wrap link around source, if given
            if('' != $metadata['source_url']){
                $source = sprintf('<a href="%2$s" target="_blank" rel="nofollow">%1$s</a>', $source, $metadata['source_url']);
            }

            // add license if enabled
            if($options['enable_licences'] && isset($metadata['licence']) && $metadata['licence']) {
                $licences = $this->licences_text_to_array($options['licences']);
                if(isset($licences[$metadata['licence']]['url'])) $licence_url = $licences[$metadata['licence']]['url'];

                if(isset($licence_url) && $licence_url != '') {
                    $source = sprintf('%1$s | <a href="%3$s" target="_blank" rel="nofollow">%2$s</a>', $source, $metadata['licence'], $licence_url);
                } else {
                    $source = sprintf('%1$s | %2$s', $source, $metadata['licence']);
                }
            }

            return $source;
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

    }
}
