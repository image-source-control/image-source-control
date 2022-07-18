<?php
	/**
	 * Handles all admin functionalities
	 *
	 * @todo move frontend-only functions from general class here
	 */
class ISC_Public extends ISC_Class {

	/**
	 * Instance of ISC_Public
	 *
	 * @var $instance
	 */
	protected static $instance = null;

	/**
	 * ISC_Public constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Load after plugins are loaded
	 */
	public function plugins_loaded() {
		$options = $this->get_isc_options();
		// load caption layout scripts and styles. The default layout loads scripts
		if ( empty( $options['caption_style'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ) );
			add_action( 'wp_head', array( $this, 'front_head' ) );
		}

		// Content filters need to be above 10 in order to interpret also gallery shortcode
		self::register_the_content_filters();
		add_filter( 'the_excerpt', array( $this, 'excerpt_filter' ), 20 );
		add_filter( 'render_block', array( $this, 'add_featured_image_source_to_excerpt_block' ), 10, 2 );

		add_shortcode( 'isc_list', array( $this, 'list_post_attachments_with_sources_shortcode' ) );
		add_shortcode( 'isc_list_all', array( $this, 'list_all_post_attachments_sources_shortcode' ) );
	}

	/**
	 * Register our the_content filters
	 */
	public static function register_the_content_filters() {

		// Content filters need to be above 10 in order to interpret also gallery shortcode
		// needs to be added to remove_the_content_filters() as well to prevent infinite loops
		add_filter( 'the_content', array( self::get_instance(), 'add_sources_to_content' ), 20 );
	}

	/**
	 * Unregister our the_content filters
	 * used in places where we want to prevent infinite loops
	 */
	public static function remove_the_content_filters() {
		remove_filter( 'the_content', array( self::get_instance(), 'add_sources_to_content' ), 20 );
	}

	/**
	 * Get an instance of ISC_Public
	 *
	 * @return ISC_Public|null
	 */
	public static function get_instance() {
		null === self::$instance && self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Enqueue scripts for the front-end.
	 */
	public function front_scripts() {
		// don’t add the script on AMP pages
		if ( self::is_amp() ) {
			return;
		}
		// inject in footer as we can only reliably position captions when the DOM is fully loaded
		wp_enqueue_script( 'isc_caption', plugins_url( '/assets/js/captions.js', __FILE__ ), null, ISCVERSION, true );
	}

			/**
			 * Front-end scripts in <head /> section.
			 */
	public function front_head() {
		// don’t add the script on AMP pages
		if ( self::is_amp() ) {
			return;
		}

		$options = $this->get_isc_options();
		?>
			<script type="text/javascript">
			/* <![CDATA[ */
				var isc_front_data =
				{
					caption_position : '<?php echo esc_html( $options['caption_position'] ); ?>',
				}
			/* ]]> */
			</script>
			<style>
				.isc-source { position: relative; display: inline-block; line-height: initial; }
                .wp-block-cover .isc-source { position: static; }
				<?php
				// The 2022 theme adds display:block to the featured image block, which creates additional line breaks. `display: inline` fixes that.
				?>
				span.isc-source-text a { display: inline; color: #fff; }
			</style>
			<?php
	}

	/**
	 * Find images in the content, create the index, and maybe add sources to it
	 *
	 * @param string $content post content.
	 * @return string $content
	 */
	public function add_sources_to_content( $content ) {
		// create a new line in the log to separate different posts
		ISC_Log::log( '---' );

		// bail early if the content is used to create the excerpt
		if ( doing_filter( 'get_the_excerpt' ) ) {
			ISC_Log::log( 'skipped adding sources to the excerpt' );
			return $content;
		}

		// disabling the content filters while working in page builders or block editor
		if ( wp_is_json_request() || defined( 'REST_REQUEST' ) ) {
			ISC_Log::log( 'skipped adding sources while working in page builders' );
			return $content;
		}

		// return if this is not the main query or within the loop
		if ( ! self::is_main_loop() ) {
			ISC_Log::log( 'skipped adding sources because the content was loaded outside the main loop' );
			return $content;
		}

		global $post;

		if ( empty( $post->ID ) ) {
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				ISC_Log::log( 'exit content for ' . $_SERVER['REQUEST_URI'] . ' due to missing post_id' );
			}
			return $content;
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			ISC_Log::log( 'index content for ' . $_SERVER['REQUEST_URI'] . ' and post ID ' . $post->ID );
		}

		// Skip any source output or indexing if this is a page with a full source list.
		if ( has_shortcode( $content, '[isc_list_all]' )
			|| false !== strpos( $content, 'isc_all_image_list_box' ) ) {
			return $content;
		}

		// create index, if it doesn’t exist, yet
		$attachments = get_post_meta( $post->ID, 'isc_post_images', true );

		/**
		 * $attachments is an empty string if it was never set and an array if it was set
		 * the array is empty if no images were found in the past. This prevents re-indexing as well
		 */
		if ( $attachments === '' ) {
			ISC_Log::log( 'isc_post_images is empty. Updating index for post ID ' . $post->ID );

			// retrieve images added to a post or page and save all information as a post meta value for the post
			ISC_Model::update_indexes( $post->ID, $content );
		} elseif ( is_array( $attachments ) ) {
			ISC_Log::log( sprintf( 'found existing list of %d sources for post ID %d', count( $attachments ), $post->ID ) );
		}

		// maybe add source captions
		$options = $this->get_isc_options();
		if ( ! empty( $options['display_type'] ) && is_array( $options['display_type'] ) && in_array( 'overlay', $options['display_type'], true )
			&& apply_filters( 'isc_public_add_source_captions_to_content', true ) ) {
			$content = self::add_source_captions_to_content( $content );
		} else {
			ISC_Log::log( 'not creating image overlays because the option is disabled' );
		}
		// maybe add source list
		$content = self::add_source_list_to_content( $content );

		return $content;
	}

	/**
	 * Check main loop
	 *
	 * @return bool true if we are currently on something that could be called the "main loop"
	 */
	private static function is_main_loop() {

		// Exception: Oxygen builder, where `is_the_loop()` is false
		if ( defined( 'CT_VERSION' ) && defined( 'CT_FW_PATH' ) ) {
			return true;
		} elseif ( self::is_amp_reader_mode() && is_single() ) {
			// Exception: AMP reader mode and the AMPforWP plugin need this extra check
			return true;
		} elseif ( in_the_loop() && is_main_query() ) {
			return true;
		}

		return false;
	}

	/**
	 * Add captions to post content and include source into caption, if this setting is enabled
	 *
	 * @param string $content post content.
	 * @return string $content
	 */
	public function add_source_captions_to_content( $content ) {
		$options          = $this->get_isc_options();
		$exclude_standard = $this->is_standard_source( 'exclude' );

		ISC_Log::log( 'start creating source overlays' );

		/**
		 * Split content where `isc_stop_overlay` is found to not display overlays starting there
		 */
		if ( strpos( $content, 'isc_stop_overlay' ) ) {
			list( $content, $content_after ) = explode( 'isc_stop_overlay', $content, 2 );
		} else {
			$content_after = '';
		}

		/**
		 * Removed [caption], because this check runs after the hook that interprets shortcodes
		 * img tag is checked individually since there is a different order of attributes when images are used in gallery or individually
		 *
		 * 0 – full match
		 * 1 - <figure> if set
		 * 2 – alignment
		 * 3 – inner code starting with <a>
		 * 4 – opening link attribute
		 * 5 – "rel" attribute from link tag
		 * 6 – image id from link wp-att- value in "rel" attribute
		 * 7 – full img tag
		 * 8 – image URL
		 * 9 – (unused)
		 * 10 - </figure>
		 *
		 * tested with:
		 * * with and without [caption]
		 * * with and without link attribute
		 *
		 * potential issues:
		 * * line breaks in the code – use \s* where potential line breaks could appear
		 *
		 * Use (\x20|\x9|\xD|\xA)+ to match whitespace following HTML starting tag name according to W3C REC 3.1. See issue PR #136
		 */
		$pattern = '#(<[^>]*class="[^"]*(alignleft|alignright|alignnone|aligncenter).*)?((<a[\x20|\x9|\xD|\xA]+[^>]*(rel="[^"]*[^"]*wp-att-(\d+)"[^>]*)*>)?\s*(<img[\x20|\x9|\xD|\xA]+[^>]*[^>]*src="(.+)".*\/?>).*(\s*</a>)??[^<]*).*(<\/figure.*>)?#isU';

		$match_content = apply_filters( 'isc_public_caption_regex_content', $content );

		// PREG_SET_ORDER keeps all entries together under one key
		$count = preg_match_all( $pattern, $match_content, $matches, PREG_SET_ORDER );

		ISC_Log::log( 'embedded images found: ' . $count );

		// gather elements already replaced to prevent duplicate sources, see github #105
		$replaced = array();

		if ( false !== $count ) {
			foreach ( $matches as $key => $_match ) {
				$hash = md5( $_match[3] );

				if ( in_array( $hash, $replaced, true ) ) {
					ISC_Log::log( 'skipped one image because it appears multiple times' );
					continue;
				} else {
					$replaced[] = $hash;
				}

				/**
				 * Interpret the image tag
				 * we only need the ID if we don’t have it yet
				 * it can be retrieved from "wp-image-" class (single) or "aria-describedby="gallery-1-34" in gallery
				 */
				$id      = $_match[6];
				$img_tag = $_match[7];

				ISC_Log::log( sprintf( 'found ID "%s" and img tag "%s"', $id, $img_tag ) );

				if ( ! $id ) {
						$success = preg_match( '#wp-image-(\d+)|aria-describedby="gallery-1-(\d+)#is', $img_tag, $matches_id );
					if ( $success ) {
						$id = $matches_id[1] ? intval( $matches_id[1] ) : intval( $matches_id[2] );
						ISC_Log::log( sprintf( 'found ID "%s" in the image tag', $id ) );
					} else {
						ISC_Log::log( sprintf( 'no ID found for "%s" in the image tag', $img_tag ) );
					}
				}

				// if ID is still missing get image by URL
				if ( ! $id ) {
					$src = $_match[8];
					$id  = ISC_Model::get_image_by_url( $src );
					ISC_Log::log( sprintf( 'ID for source "%s": "%s"', $src, $id ) );
				}

				// don’t show caption for own image if admin choose not to do so
				if ( $exclude_standard ) {
					if ( self::use_standard_source( $id ) ) {
						ISC_Log::log( sprintf( 'skipped "own" image for ID "%s"', $id ) );
						continue;
					}
				}

				// don’t display empty sources
				$source_string = $this->render_image_source_string( $id );
				if ( ! $source_string ) {
					ISC_Log::log( sprintf( 'skipped empty sources string for ID "%s"', $id ) );
					continue;
				}

				// get any alignment from the original code
				preg_match( '#alignleft|alignright|alignnone|aligncenter#is', $_match[0], $matches_align );
				$alignment = isset( $matches_align[0] ) ? $matches_align[0] : '';

				$old_content = $_match[3];
				$new_content = str_replace( 'wp-image-' . $id, 'wp-image-' . $id . ' with-source', $old_content );

				// default style
				if ( empty( $options['caption_style'] ) ) {
					$source        = '<span class="isc-source-text">' . $options['source_pretext'] . ' ' . $source_string . '</span>';
					$markup_before = '<span id="isc_attachment_' . $id . '" class="isc-source ' . $alignment . '">';
					$markup_after  = '</span>';
				} else {
					// no style
					$source        = $options['source_pretext'] . ' ' . $source_string;
					$markup_before = '';
					$markup_after  = '';
				}

				$content = str_replace(
					$old_content,
					sprintf(
						'%s%s%s%s',
						apply_filters( 'isc_overlay_html_markup_before', $markup_before, $id ),
						$new_content,
						apply_filters( 'isc_overlay_html_source', $source, $id ),
						apply_filters( 'isc_overlay_html_markup_after', $markup_after, $id )
					),
					$content
				);
			}
			ISC_Log::log( 'number of unique images found: ' . count( $replaced ) );
		}
		/**
		 * Attach follow content back
		 */
		$content = $content . $content_after;

		return $content;
	}

	/**
	 * Add the Per-page list if the option is enabled
	 *
	 * @param string $content post content.
	 * @return string $content
	 */
	public function add_source_list_to_content( $content ) {

		/**
		 * Display the Per-page list
		 * on single pages if the following option is enabled: How to display source in Frontend > list below content
		 * on archive pages and home pages with posts if the following option is enabled: Archive Pages > Display sources list below full posts
		 */
		if ( $this->can_add_list_to_content() ) {
			ISC_Log::log( 'start creating source list below content' );
			$content = $content . $this->list_post_attachments_with_sources();
		}

		return $content;
	}

	/**
	 * Add image source of featured image to post excerpts
	 *
	 * @param string $excerpt post excerpt.
	 * @return string $excerpt
	 *
	 * @update 1.4.3
	 */
	public function excerpt_filter( $excerpt ) {

		// display inline sources
		$options = $this->get_isc_options();
		$post    = get_post();

		if ( empty( $options['list_on_excerpts'] ) ) {
			return $excerpt;
		}

		return $excerpt . $this->get_thumbnail_source_string( $post->ID );
	}

	/**
	 * Add image source of featured image to the post excerpt block in the frontend
	 *
	 * @param string $block_content rendered content of the block.
	 * @param array $block full block details.
	 * @return string $excerpt
	 */
	public function add_featured_image_source_to_excerpt_block( $block_content, $block ) {

		if ( isset( $block['blockName'] ) && $block['blockName'] !== 'core/post-excerpt' ) {
			return $block_content;
		}

		$options = $this->get_isc_options();
		if ( empty( $options['list_on_excerpts'] ) ) {
			return $block_content;
		}

		$post = get_post();
		return str_replace(
			'</p></div>',
			$this->get_thumbnail_source_string( $post->ID ) . '</p></div>',
			$block_content
		);
	}

	/**
	 * Create image sources list for all images of this post
	 *
	 * @since 1.0
	 * @updated 1.1, 1.3.5
	 * @updated 1.5 use new render function to create basic image source string
	 *
	 * @param integer $post_id id of the current post/page.
	 * @return string output
	 */
	public function list_post_attachments_with_sources( $post_id = 0 ) {
		global $post;

		if ( empty( $post_id ) && ! empty( $post->ID ) ) {
				$post_id = $post->ID;
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			ISC_Log::log( 'enter list_post_attachments_with_sources() for ' . $_SERVER['REQUEST_URI'] . ' and post_id ' . $post_id );
		}

		// don’t do anything on REST requests since that causes issues with the block editor rendering a "post" for each image
		// just in case, we also prevent output for "content" from attachments
		if ( defined( 'REST_REQUEST' ) || ! isset( $post->post_type ) || 'attachment' === $post->post_type ) {
			ISC_Log::log( 'exit list_post_attachments_with_sources() because of invalid request' );
			return '';
		}

		// do not render an empty source list on non-post pages unless explicitly stated.
		if ( empty( $post_id ) ) {
			/**
			 * Filter: isc_source_list_empty_output
			 * allow to return some output even if there is no post ID (e.g., on archive pages).
			 */
			return apply_filters( 'isc_source_list_empty_output', '' );
		}

		// allow developers to override the output of the sources list
		$override = apply_filters( 'isc_sources_list_override_output', false, $post_id );
		if ( $override ) {
			ISC_Log::log( 'exit list_post_attachments_with_sources() because override was set' );
			return $override;
		}

		$attachments      = get_post_meta( $post_id, 'isc_post_images', true );
		$exclude_standard = $this->is_standard_source( 'exclude' );

		if ( ! empty( $attachments ) ) {
			ISC_Log::log( sprintf( 'going through %d attachments', count( $attachments ) ) );
			$atts = array();
			foreach ( $attachments as $attachment_id => $attachment_array ) {
				$use_standard_source = self::use_standard_source( $attachment_id );
				$source              = self::get_image_source_text( $attachment_id );

				// check if source of own images can be displayed
				if ( ( $use_standard_source == '' && $source == '' ) || ( $use_standard_source != '' && $exclude_standard ) ) {
					if ( $use_standard_source != '' && $exclude_standard ) {
						ISC_Log::log( sprintf( 'image %d: "own" sources are excluded', $attachment_id ) );
					} else {
						ISC_Log::log( sprintf( 'image %d: skipped because of empty source', $attachment_id ) );
					}
					unset( $atts[ $attachment_id ] );
					continue;
				} else {
					$atts[ $attachment_id ]['title'] = get_the_title( $attachment_id );
					ISC_Log::log( sprintf( 'image %d: getting title "%s"', $attachment_id, $atts[ $attachment_id ]['title'] ) );
					$atts[ $attachment_id ]['source'] = $this->render_image_source_string( $attachment_id );
				}
			}

			return $this->render_attachments( $atts );
		} else {
			// see description above
			ISC_Log::log( 'exit list_post_attachments_with_sources() without any images found ' );
			// allow to return result if the source list is empty.
			return apply_filters( 'isc_source_list_empty_output', '' );
		}
	}

	/**
	 * Render attachment list
	 *
	 * @updated 1.3.5
	 * @updated 1.5 removed rendering the license to an earlier function
	 * @param array $attachments array of attachments.
	 * @return string
	 */
	public function render_attachments( $attachments ) {
		ISC_Log::log( 'start to render attachments list' );

		// don't display anything, if no image sources displayed
		if ( $attachments === array() ) {
			ISC_Log::log( 'exit render_attachments() due to missing attachments' );
			return '';
		}

		$options  = $this->get_isc_options();
		$headline = $options['image_list_headline'];

		ob_start();

		printf( '<p class="isc_image_list_title">%1$s</p>', $headline );
		?>
			<ul class="isc_image_list">
		<?php

		ISC_Log::log( sprintf( 'start listing %d attachments', count( $attachments ) ) );

		foreach ( $attachments as $atts_id => $atts_array ) {
			if ( empty( $atts_array['source'] ) ) {
				ISC_Log::log( sprintf( 'skip image %d because of empty source', $atts_id ) );
				continue;
			}
			echo apply_filters(
				'isc_source_list_line',
				sprintf( '<li>%1$s: %2$s</li>', $atts_array['title'], $atts_array['source'] ),
				$atts_id,
				$atts_array,
				$attachments
			);
		}
		?>
		</ul>
		<?php
		$output = apply_filters( 'isc_source_list', ob_get_clean() );

		return $this->render_image_source_box( $output );
	}

	/**
	 * Shortcode function to list all image sources
	 *
	 * @param array $atts attributes.
	 * @return string
	 */
	public function list_post_attachments_with_sources_shortcode( $atts = array() ) {
		global $post;

		ISC_Log::log( 'enter list_post_attachments_with_sources_shortcode() for [isc_list] shortcode' );

		// hotfix for https://github.com/webgilde/image-source-control/issues/48 to prevent loops
		if ( defined( 'REST_REQUEST' ) ) {
			ISC_Log::log( 'exit list_post_attachments_with_sources_shortcode() due to calling through REST_REQUEST' );
			return '';
		}

		$a = shortcode_atts( array( 'id' => 0 ), $atts );

		// if $id not set, use the current ID from the post
		if ( ! $a['id'] && isset( $post->ID ) ) {
			$a['id'] = $post->ID;
		}

		return $this->list_post_attachments_with_sources( $a['id'] );
	}

	/**
	 * Create a shortcode to list all image sources in the frontend
	 *
	 * @param array $atts attributes.
	 * @return string
	 */
	public function list_all_post_attachments_sources_shortcode( $atts = array() ) {
		$options = $this->get_isc_options();

		$a = shortcode_atts(
			array(
				'per_page'     => null,
				'before_links' => '',
				'after_links'  => '',
				'prev_text'    => '&#171; Previous',
				'next_text'    => 'Next &#187;',
				'included'     => null,
			),
			$atts
		);

		// How many entries per page. Use plugin options or default, if the shortcode attribute is missing.
		if ( $a['per_page'] === null ) {
			$per_page = isset( $options['images_per_page'] ) ? absint( $options['images_per_page'] ) : 99999;
		} else {
			$per_page = absint( $a['per_page'] );
		}

		// Which types of images are included? Use plugin options or default, if the shortcode attribute is missing.
		if ( $a['included'] === null ) {
			$included = isset( $options['global_list_included_images'] ) ? $options['global_list_included_images'] : '';
		} else {
			$included = $a['included'];
		}

		// use proper translation if attribute is not given
		$prev_text = '&#171; Previous' === $a['prev_text'] ? __( '&#171; Previous', 'image-source-control-isc' ) : $a['prev_text'];
		$next_text = 'Next &#187;' === $a['next_text'] ? __( 'Next &#187;', 'image-source-control-isc' ) : $a['next_text'];

		// check which images are included
		$args = array();
		if ( 'all' !== $included ) {
			// only load images attached to posts
			$args['meta_query'] = array(
				array(
					'key'     => 'isc_image_posts',
					'value'   => 'a:0:{}',
					'compare' => '!=',
				),
			);
		}

		$attachments = ISC_Model::get_attachments( apply_filters( 'isc_global_list_get_attachment_arguments', $args ) );

		if ( empty( $attachments ) ) {
			return '';
		}

		$connected_atts = array();

		foreach ( $attachments as $_attachment ) {
			$connected_atts[ $_attachment->ID ]['source']   = self::get_image_source_text( $_attachment->ID );
			$connected_atts[ $_attachment->ID ]['standard'] = self::use_standard_source( $_attachment->ID );
			// jump to next element if the standard source is set to be excluded from the source list
			if ( $this->is_standard_source( 'exclude' ) && '' != $connected_atts[ $_attachment->ID ]['standard'] ) {
				unset( $connected_atts[ $_attachment->ID ] );
				continue;
			}

			$connected_atts[ $_attachment->ID ]['title']       = $_attachment->post_title;
			$connected_atts[ $_attachment->ID ]['author_name'] = '';
			if ( $this->is_standard_source( 'custom_text' ) && ! empty( $connected_atts[ $_attachment->ID ]['own'] ) ) {
				$connected_atts[ $_attachment->ID ]['author_name'] = $this->get_standard_source_text();
			} else {
				// show author name
				$connected_atts[ $_attachment->ID ]['author_name'] = get_the_author_meta( 'display_name', $_attachment->post_author );
			}

			$metadata   = get_post_meta( $_attachment->ID, 'isc_image_posts', true );
			$usage_data = '';

			if ( is_array( $metadata ) && array() !== $metadata ) {
				$usage_data      .= "<ul style='margin: 0;'>";
				$usage_data_array = array();
				foreach ( $metadata as $data ) {
					// only list published posts
					if ( get_post_status( $data ) === 'publish' ) {
						$usage_data_array[] = sprintf(
								// translators: %1$s is a URL, %2$s is the title of an image, %3$s is the link text.
							__( '<li><a href="%1$s" title="View %2$s">%3$s</a></li>', 'image-source-control-isc' ),
							esc_url( get_permalink( $data ) ),
							esc_attr( get_the_title( $data ) ),
							esc_html( get_the_title( $data ) )
						);
					}
				}
				if ( 'all' !== $included && $usage_data_array === array() ) {
					unset( $connected_atts[ $_attachment->ID ] );
					continue;
				}
				$usage_data .= implode( '', $usage_data_array );
				$usage_data .= '</ul>';
			}

			$connected_atts[ $_attachment->ID ]['posts'] = $usage_data;
		}

		$total = count( $connected_atts );

		if ( 0 == $total ) {
			return '';
		}

		$page       = isset( $_GET['isc-page'] ) ? intval( $_GET['isc-page'] ) : 1;
		$down_limit = 1; // First page

		$up_limit = 1;

		if ( $per_page && $per_page < $total ) {
			$rem      = $total % $per_page; // The Remainder of $total / $per_page
			$up_limit = ( $total - $rem ) / $per_page;
			if ( 0 < $rem ) {
				$up_limit++; // If rem is positive, add the last page that contains less than $per_page attachment;
			}
		}

		ob_start();
		if ( 2 > $up_limit ) {
			$this->display_all_attachment_list( $connected_atts );
		} else {
			$starting_atts = $per_page * ( $page - 1 ); // for page 2 and 3 $per_page start display on $connected_atts[3*(2-1) = 3]
			$paged_atts    = array_slice( $connected_atts, $starting_atts, $per_page, true );
			$this->display_all_attachment_list( $paged_atts );
			$this->pagination_links( $up_limit, $a['before_links'], $a['after_links'], $prev_text, $next_text );
		}

		return ob_get_clean();
	}

	/**
	 * Render the global list
	 *
	 * @param array $atts attachments.
	 */
	public function display_all_attachment_list( $atts ) {
		if ( ! is_array( $atts ) || $atts === array() ) {
			return;
		}
		$options = $this->get_isc_options();

		require apply_filters( 'isc_public_global_list_view_path', ISCPATH . 'public/views/global-list.php' );
	}

	/**
	 * Render global list thumbnails
	 *
	 * @param int $attachment_id attachment ID.
	 */
	public function render_global_list_thumbnail( $attachment_id ) {
		$options = $this->get_isc_options();

		if ( 'custom' !== $options['thumbnail_size'] ) {
			$thumbnail = wp_get_attachment_image( $attachment_id, $options['thumbnail_size'] );
		} else {
			$thumbnail = wp_get_attachment_image( $attachment_id, array( $options['thumbnail_width'], $options['thumbnail_height'] ) );
		}

		// a thumbnail might be missing for images that are not hosted within WordPress
		if ( ! $thumbnail  ) {
			?><img src="<?php echo ISCBASEURL . '/public/assets/images/isc-icon-gray.svg' ?>"/><?php
		}

		echo $thumbnail;
	}

	/**
	 * Render pagination links, use $before_links and after_links to wrap pagination links inside an additional block
	 *
	 * @param int    $max_page total page count.
	 * @param string $before_links optional html to display before pagination links.
	 * @param string $after_links optional html to display after pagination links.
	 * @param string $prev_text text for the previous page link.
	 * @param string $next_text text for the next page link.
	 * @since 1.1.3
	 */
	public function pagination_links( $max_page, $before_links, $after_links, $prev_text, $next_text ) {
		if ( ( ! isset( $max_page ) ) || ( ! isset( $before_links ) ) || ( ! isset( $after_links ) ) || ( ! isset( $prev_text ) ) || ( ! isset( $next_text ) ) ) {
			return;
		}
		if ( ! empty( $before_links ) ) {
			echo $before_links;
		}
		?>
				<div class="isc-paginated-links">
			<?php
			$page = isset( $_GET['isc-page'] ) ? intval( $_GET['isc-page'] ) : 1;
			if ( $max_page < $page ) {
				$page = $max_page;
			}
			if ( $page < 1 ) {
				$page = 1;
			}
			$min_page          = 1;
			$backward_distance = $page - $min_page;
			$forward_distance  = $max_page - $page;

			$page_link = get_page_link();

			/**
			* Remove the query_string of the page_link (?page_id=xyz for the standard permalink structure),
			* which is already captured in $_SERVER['QUERY_STRING'].
			 *
			* @todo replace regex with other value (does WP store the url path without attributes somewhere?
			* >get_page_link() returns the permalink but for the default WP permalink structure, the permalink looks like "http://domain.tld/?p=52", while $_GET
			* still has a field named 'page_id' with the same value of 52.
			*/

			$pos = strpos( $page_link, '?' );
			if ( false !== $pos ) {
				$page_link = substr( $page_link, 0, $pos );
			}

			/**
			* Unset the actual "$_GET['isc-page']" variable (if is set). Pagination variable will be appended to the new query string with a different value for each
			* pagination link.
			*/

			if ( isset( $_GET['isc-page'] ) ) {
				unset( $_GET['isc-page'] );
			}

			$query_string = http_build_query( $_GET );

			$isc_query_tag = '';
			if ( empty( $query_string ) ) {
				$isc_query_tag = '?isc-page=';
			} else {
				$query_string  = '?' . $query_string;
				$isc_query_tag = '&isc-page=';
			}

			if ( $min_page !== $page ) {
				?>
					<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page - 1 ); ?>" class="prev page-numbers"><?php echo $prev_text; ?></a>
					<?php
			}

			if ( 5 < $max_page ) {
				if ( 3 < $backward_distance ) {
					?>
						<a href="<?php echo $page_link . $query_string . $isc_query_tag; ?>1" class="page-numbers">1</a>
						<span class="page-numbers dots">...</span>
						<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page - 2 ); ?>" class="page-numbers"><?php echo $page - 2; ?></a>
						<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page - 1 ); ?>" class="page-numbers"><?php echo $page - 1; ?></a>
						<span class="page-numbers current"><?php echo $page; ?></span>
						<?php
				} else {
					for ( $i = 1; $i <= $page; $i++ ) {
						if ( $i == $page ) {
							?>
								<span class="page-numbers current"><?php echo $i; ?></span>
								<?php
						} else {
							?>
								<a href="<?php echo $page_link . $query_string . $isc_query_tag . $i; ?>" class="page-numbers"><?php echo $i; ?></a>
								<?php
						}
					}
				}

				if ( 3 < $forward_distance ) {
					?>
						<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page + 1 ); ?>" class="page-numbers"><?php echo $page + 1; ?></a>
						<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page + 2 ); ?>" class="page-numbers"><?php echo $page + 2; ?></a>
						<span class="page-numbers dots">...</span>
						<a href="<?php echo $page_link . $query_string . $isc_query_tag . $max_page; ?>" class="page-numbers"><?php echo $max_page; ?></a>
						<?php
				} else {
					for ( $i = $page + 1; $i <= $max_page; $i++ ) {
						?>
							<a href="<?php echo $page_link . $query_string . $isc_query_tag . $i; ?>" class="page-numbers"><?php echo $i; ?></a>
							<?php
					}
				}
			} else {
				for ( $i = 1; $i <= $max_page; $i++ ) {
					if ( $i == $page ) {
						?>
							<span class="page-numbers current"><?php echo $i; ?></span>
							<?php
					} else {
						?>
							<a href="<?php echo $page_link . $query_string . $isc_query_tag . $i; ?>" class="page-numbers"><?php echo $i; ?></a>
							<?php
					}
				}
			}
			if ( $page != $max_page ) {
				?>
					<a href="<?php echo $page_link . $query_string . $isc_query_tag . ( $page + 1 ); ?>" class="next page-numbers"><?php echo $next_text; ?></a>
					<?php
			}
			?>
				</div>
			<?php
			echo $after_links;
	}

	/**
	 * Get source string of a feature image
	 *
	 * @since 1.8
	 * @param integer $post_id post object ID.
	 * @return string source
	 */
	public function get_thumbnail_source_string( $post_id = 0 ) {
		if ( empty( $post_id ) ) {
			return '';
		}

		if ( has_post_thumbnail( $post_id ) ) {
			$id    = get_post_thumbnail_id( $post_id );
			$thumb = get_post( $post_id );

			// don’t show caption for own image if admin choose not to do so
			if ( $this->is_standard_source( 'exclude' ) ) {
				if ( self::use_standard_source( $id ) ) {
					return '';
				}
			}
			// don’t display empty sources
			$src           = $thumb->guid;
			$source_string = $this->render_image_source_string( $id );
			if ( ! $source_string ) {
				return '';
			}

			return '<p class="isc-source-text">' . apply_filters( 'isc_featured_image_source_pre_text', _x( 'Featured image: ', 'label of the featured image source, when displayed below post excerpts', 'image-source-control-isc' ) ) . ' ' . $source_string . '</p>';
		}

		return '';
	}

	/**
	 * Load an image source string by url
	 *
	 * @updated 1.5
	 * @deprecated since 1.9
	 * @param string $url url of the image.
	 * @return string
	 */
	public function get_source_by_url( $url ) {
		// get the id by the image source
		$id = ISC_Model::get_image_by_url( $url );

		return $this->render_image_source_string( $id );
	}

	/**
	 * Render the image source box
	 * dedicated to displaying an empty box as well so don’t add more visible elements
	 *
	 * @param string $content content of the source box, i.e., list of sources.
	 */
	public function render_image_source_box( $content = null ) {
		ob_start();
		require ISCPATH . 'public/views/image-source-box.php';

		ISC_Log::log( 'finished creating image source box' );

		return ob_get_clean();
	}

	/**
	 * Render source string of single image by its id
	 *  this only returns the string with source and license (and urls),
	 *  but no wrapping, because the string is used in a lot of functions
	 *  (e.g. image source list where title is prepended)
	 *
	 * @updated 1.5 wrapped source into source url
	 * @updated 2.4 accept metadata as an argument
	 *
	 * @param int $id   id of the image.
	 * @param int $data metadata.
	 * @return bool|string false if no source was given, else string with source
	 */
	public function render_image_source_string( $id, $data = array() ) {
		$id = esc_attr( $id );

		$options = $this->get_isc_options();

		$metadata['source']     = isset( $data['source'] ) ? $data['source'] : self::get_image_source_text( $id );
		$metadata['source_url'] = isset( $data['source_url'] ) ? $data['source_url'] : get_post_meta( $id, 'isc_image_source_url', true );
		$metadata['own']        = isset( $data['own'] ) ? $data['own'] : self::use_standard_source( $id );
		$metadata['licence']    = isset( $data['licence'] ) ? $data['licence'] : get_post_meta( $id, 'isc_image_licence', true );

		$source = '';

		$att_post = get_post( $id );

		if ( '' != $metadata['own'] ) {
			if ( $this->is_standard_source( 'author_name' ) ) {
				if ( ! empty( $att_post ) ) {
					$source = get_the_author_meta( 'display_name', $att_post->post_author );
				}
			} else {
				$source = $this->get_standard_source_text();
			}
		} else {
			if ( '' != $metadata['source'] ) {
				$source = $metadata['source'];
			}
		}

		if ( $source == '' ) {
			return false;
		}

		// wrap link around source, if given
		if ( '' != $metadata['source_url'] ) {
			$source = apply_filters(
				'isc_public_source_url_html',
				sprintf( '<a href="%2$s" target="_blank" rel="nofollow">%1$s</a>', $source, $metadata['source_url'] ),
				$id,
				$metadata
			);
		}

		// add license if enabled
		if ( $options['enable_licences'] && isset( $metadata['licence'] ) && $metadata['licence'] ) {
			$licences = $this->licences_text_to_array( $options['licences'] );
			if ( isset( $licences[ $metadata['licence'] ]['url'] ) ) {
				$licence_url = $licences[ $metadata['licence'] ]['url'];
			}

			if ( isset( $licence_url ) && $licence_url != '' ) {
				$source = sprintf( '%1$s | <a href="%3$s" target="_blank" rel="nofollow">%2$s</a>', $source, $metadata['licence'], $licence_url );
			} else {
				$source = sprintf( '%1$s | %2$s', $source, $metadata['licence'] );
			}
		}

		return $source;
	}

	/**
	 * Check if the source list can be added to the content automatically
	 *
	 * @return bool true if the source list can be added to the content
	 */
	public function can_add_list_to_content() {
		$options = $this->get_isc_options();

		/**
		 * Tests:
		 * - is list allowed on archive pages?
		 * - automatic injection of the list is enabled
		 */
		if (
				(
				( is_archive() || is_home() )
			   && isset( $options['list_on_archives'] ) && $options['list_on_archives'] )
			|| ( is_singular() && isset( $options['display_type'] ) && is_array( $options['display_type'] ) && in_array( 'list', $options['display_type'], true ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the given attachment ought to use the standard source
	 *
	 * @param int $attachment_id attachment ID
	 * @return bool true if standard source is used
	 */
	public static function use_standard_source( $attachment_id ) {
		return (bool) apply_filters(
			'isc_public_attachment_use_standard_source',
			get_post_meta( $attachment_id, 'isc_image_source_own', true ),
			$attachment_id
		);
	}

	/**
	 * Get image source string for public output
	 *
	 * @param int $attachment_id attachment ID
	 * @return string
	 */
	public static function get_image_source_text( $attachment_id ) {
		return apply_filters(
			'isc_public_attachment_get_source',
			trim(
				get_post_meta( $attachment_id, 'isc_image_source', true )
			)
		);
	}

	/**
	 * Are we currently on an AMP URL?
	 *
	 * @return bool true if AMP url, false otherwise
	 */
	public static function is_amp() {
		global $pagenow;
		if ( is_admin()
			 || is_embed()
			 || is_feed()
			 || ( isset( $pagenow ) && in_array( $pagenow, array( 'wp-login.php', 'wp-signup.php', 'wp-activate.php' ), true ) )
			 || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			 || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
		) {
			return false;
		}

		if ( ! did_action( 'wp' ) ) {
			return false;
		}

		return ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() )
			   || ( function_exists( 'is_wp_amp' ) && is_wp_amp() )
			   || ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() )
			   || ( function_exists( 'is_penci_amp' ) && is_penci_amp() )
			   || isset( $_GET [ 'wpamp' ] );
	}

	/**
	 * Are we currently on a URL using AMP reader mode?
	 * This mode is optional in the official AMP plugin and the only choice in the AMPforWP plugin
	 * in reader mode, the classic WordPress filters are not working
	 *
	 * @return bool true if AMP url, false otherwise
	 */
	public static function is_amp_reader_mode() {
		// stop if we are not even on an AMP page
		if ( ! self::is_amp() ) {
			return false;
		}

		// official AMP plugin in reader mode
		if (
			class_exists( 'AMP_Options_Manager', false )
			&& AMP_Options_Manager::get_option( 'theme_support' ) === 'reader'
		) {
			return true;
		}

		// the AMPforWP plugin only uses reader mode since they base on an old version of the AMP plugin where only that one was available
		if ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) {
			return true;
		}

		return false;
	}



}
