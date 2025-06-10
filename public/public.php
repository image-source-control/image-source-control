<?php

use ISC\Standard_Source;
use ISC\Helpers;

/**
 * Handles all frontend facing functionalities
 *
 * @todo move frontend-only functions from general class here
 */
class ISC_Public extends \ISC\Image_Sources\Image_Sources {

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

		add_action( 'wp', [ $this, 'register_hooks' ] );
	}

	/**
	 * Register hooks after the page is set up so that we have access to the post ID.
	 */
	public function register_hooks() {
		// register the shortcode for the global list. Since this shortcode has to be placed manually and is normally only used once, checking if ISC is disabled on cetain pages doesn’t make sense
		add_shortcode( 'isc_list_all', [ '\ISC\Image_Sources\Renderer\Global_List', 'execute_shortcode' ] );

		if ( ! self::can_load_image_sources() ) {
			return;
		}

		// prepare the log
		$this->prepare_log();

		if ( ISC\Image_Sources\Renderer\Caption::has_caption_style() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'front_scripts' ] );
			add_action( 'wp_head', [ $this, 'front_head' ] );
		}

		// Content filters need to be above 10 in order to interpret also gallery shortcode
		self::register_the_content_filters();
		add_filter( 'the_excerpt', [ $this, 'excerpt_filter' ], 20 );
		add_filter( 'render_block', [ $this, 'add_featured_image_source_to_excerpt_block' ], 10, 2 );

		add_shortcode( 'isc_list', [ $this, 'list_post_attachments_with_sources_shortcode' ] );
	}

	/**
	 * Check if the current frontend page can run ISC properly
	 *
	 * @return bool
	 */
	public static function can_load_image_sources() {

		$post_id = get_the_ID();

		if ( $post_id
			&& is_singular()
			/**
			 * Filter posts that should not output ISC information by their ID
			 *
			 * @param int[] void WP_Post IDs.
			 */
			&& in_array( $post_id, apply_filters( 'isc_public_excluded_post_ids', [] ), true ) ) {
			return false;
		}

		return apply_filters( 'isc_can_load', ISC\Plugin::is_module_enabled( 'image_sources' ) );
	}

	/**
	 * Prepare the log file
	 */
	public function prepare_log() {
		if ( ISC_Log::clear_log() ) {
			ISC_Log::delete_log_file();
		}

		if ( ISC_Log::enabled() ) {
			ISC_Log::log( '---' );
		}
	}

	/**
	 * Register our the_content filters
	 */
	public static function register_the_content_filters() {

		// Content filters need to be above 10 in order to interpret also gallery shortcode
		// needs to be added to remove_the_content_filters() as well to prevent infinite loops
		add_filter( 'the_content', [ self::get_instance(), 'add_sources_to_content' ], 20 );
	}

	/**
	 * Unregister our the_content filters
	 * used in places where we want to prevent infinite loops
	 */
	public static function remove_the_content_filters() {
		remove_filter( 'the_content', [ self::get_instance(), 'add_sources_to_content' ], 20 );
	}

	/**
	 * Get an instance of ISC_Public
	 *
	 * @return ISC_Public|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
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
		Helpers::enqueue_script( 'isc_caption', 'public/assets/js/captions.js' );

		$options = $this->get_options();

		$caption_style = [
			'position'         => 'absolute',
			'font-size'        => '0.9em',
			'background-color' => '#333',
			'color'            => '#fff',
			'opacity'          => '0.70',
			'padding'          => '0 0.15em',
			'text-shadow'      => 'none',
			'display'          => 'block',
		];

		$front_data = [
			'caption_position' => isset( $options['caption_position'] ) ? esc_html( $options['caption_position'] ) : '',
			/**
			 * Filter: isc_public_caption_default_style
			 * Allows to change the default caption style.
			 *
			 * @param array $caption_style The default caption style.
			 * @param array $options The options array.
			 */
			'caption_style'    => apply_filters( 'isc_public_caption_default_style', $caption_style, $options ),
		];

		/**
		 * Filter: isc_public_caption_script_options
		 *
		 * @param array $front_data The data to be localized.
		 * @param array $options The options array.
		 */
		$filtered_front_data = apply_filters( 'isc_public_caption_script_options', $front_data, $options );

		wp_localize_script(
			'isc_caption',
			'isc_front_data',
			$filtered_front_data
		);
	}

	/**
	 * Front-end scripts in <head /> section.
	 */
	public function front_head() {
		// don’t add the script on AMP pages
		if ( self::is_amp() ) {
			return;
		}

		$options = $this->get_options();
		?>
			<style>
				.isc-source { position: relative; display: inline-block; line-height: initial; }
				/* Hides the caption initially until it is positioned via JavaScript */
				.isc-source > .isc-source-text { display: none; }
				.wp-block-cover .isc-source { position: static; }
				<?php
				// The 2022 theme adds display:block to the featured image block, which creates additional line breaks. `display: inline` fixes that.
				?>
				span.isc-source-text a { display: inline; color: #fff; }
				<?php
				// force the overlay caption into the bottom left corner for lightboxes introduced in WP 6.4; only applied to the positions that are currently broken
				if ( in_array( $options['caption_position'], [ 'top-center', 'top-right', 'center' ], true ) ) {
					?>
					.wp-lightbox-overlay.active .isc-source-text { top: initial !important; left: initial !important; bottom: 0! important; }
					<?php
				}
				?>
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
		// return if content is empty or null (the latter being an actual issue on a user’s site likely caused by a third-party plugin using the the_content filter wrongly)
		if ( empty( $content ) ) {
			ISC_Log::log( 'skipped adding sources because the content was empty' );
			return $content;
		}

		if ( ISC\Indexer::is_global_list_page( $content ) ) {
			ISC_Log::log( 'skipped adding sources because the content contains the Global List' );
			return $content;
		}

		// return if this is not the main query or within the loop
		if ( ! self::is_main_loop() ) {
			ISC_Log::log( 'skipped adding sources because the content was loaded outside the main loop' );
			return $content;
		}

		if ( ISC_Log::is_type( 'backtrace' ) ) {
			ISC_Log::log_stack_trace();
		}

		// maybe add source captions
		if ( self::captions_enabled() && apply_filters( 'isc_public_add_source_captions_to_content', true ) ) {
			$content = self::add_source_captions_to_content( $content );
		} else {
			ISC_Log::log( 'not creating image overlays because the option is disabled for post content' );
		}

		/**
		 * Indexing the content for images here after we added overlays, so that we could also count
		 * non-images with overlays
		 */
		if ( apply_filters( 'isc_update_indexes_in_the_content', true ) ) {
			\ISC\Indexer::update_indexes( $content );
		}

		/**
		 * The sources list is rendered after indexing the content for images
		 * since it is build on top of it
		 */
		// maybe add source list
		return self::add_source_list_to_content( $content );
	}

	/**
	 * Check main loop
	 *
	 * @return bool true if we are currently on something that could be called the "main loop"
	 */
	public static function is_main_loop() {

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
		ISC_Log::log( 'start creating source overlays' );

		/**
		 * Split content where `isc_stop_overlay` is found to not display overlays starting there
		 */
		if ( strpos( $content, 'isc_stop_overlay' ) ) {
			list( $content, $content_after ) = explode( 'isc_stop_overlay', $content, 2 );
		} else {
			$content_after = '';
		}

		$content = apply_filters( 'isc_public_caption_regex_content', $content );
		$matches = $this->html_analyzer->extract_images_from_html( $content );

		ISC_Log::log( 'embedded images found: ' . count( $matches ) );

		if ( ! count( $matches ) ) {
			return $content . $content_after;
		}

		// gather elements already replaced to prevent duplicate sources, see GitHub #105
		$replaced = [];

		foreach ( $matches as $_match ) {
			if ( ! $_match['img_src'] ) {
				ISC_Log::log( 'skipped an image because src is empty' );
				continue;
			}
			$hash = md5( $_match['inner_code'] );

			if ( in_array( $hash, $replaced, true ) ) {
				ISC_Log::log( 'skipped an image because it appears multiple times' );
				continue;
			} else {
				$replaced[] = $hash;
			}

			$id = $this->html_analyzer->extract_image_id( $_match['inner_code'] );

			// if ID is still missing get the image by URL
			if ( ! $id ) {
				$id = ISC_Model::get_image_by_url( $_match['img_src'] );
				ISC_Log::log( sprintf( 'ID for source "%s": "%s"', $_match['img_src'], $id ) );
			}

			$source = ISC\Image_Sources\Renderer\Caption::get( $id );

			if ( ! $source ) {
				ISC_Log::log( sprintf( 'skipped empty sources string for ID "%s"', $id ) );
				continue;
			}

			// get any alignment from the original code
			preg_match( '#alignleft|alignright|alignnone|aligncenter#is', $_match['full'], $matches_align );
			$alignment     = $matches_align[0] ?? '';
			$old_content   = $_match['inner_code'];
			$markup_before = '';
			$markup_after  = '';

			// default style
			if ( ISC\Image_Sources\Renderer\Caption::has_caption_style() ) {
				$markup_before = '<span id="isc_attachment_' . $id . '" class="isc-source ' . $alignment . '">';
				$markup_after  = '</span>';
			}

			$content = str_replace(
				$old_content,
				sprintf(
					'%s%s%s%s',
					apply_filters( 'isc_overlay_html_markup_before', $markup_before, $id ),
					str_replace( 'wp-image-' . $id, 'wp-image-' . $id . ' with-source', $old_content ), // new content
					$source,
					apply_filters( 'isc_overlay_html_markup_after', $markup_after, $id )
				),
				$content
			);
		}
		ISC_Log::log( 'number of unique images found: ' . count( $replaced ) );

		/**
		 * Attach follow content back
		 */
		return $content . $content_after;
	}

	/**
	 * Return true if captions are enabled
	 *
	 * @return bool
	 */
	public static function captions_enabled(): bool {
		$options = self::get_instance()->get_options();
		return ! empty( $options['display_type'] ) && is_array( $options['display_type'] ) && in_array( 'overlay', $options['display_type'], true );
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
		$options = $this->get_options();
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
	 * @param array  $block full block details.
	 * @return string $excerpt
	 */
	public function add_featured_image_source_to_excerpt_block( $block_content, $block ) {

		if ( isset( $block['blockName'] ) && $block['blockName'] !== 'core/post-excerpt' ) {
			return $block_content;
		}

		$options = $this->get_options();
		if ( empty( $options['list_on_excerpts'] ) ) {
			return $block_content;
		}

		$post = get_post();
		if ( ! $post || empty( $post->ID ) ) {
			return $block_content;
		}

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
			ISC_Log::log( 'exit because of invalid request' );
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
			ISC_Log::log( 'exit because override was set' );
			return $override;
		}

		$attachments      = get_post_meta( $post_id, 'isc_post_images', true );
		$exclude_standard = Standard_Source::standard_source_is( 'exclude' );

		if ( ! empty( $attachments ) && is_array( $attachments ) ) {
			ISC_Log::log( sprintf( 'going through %d attachments', count( $attachments ) ) );
			$atts = [];
			foreach ( $attachments as $attachment_id => $attachment_array ) {
				$image_uses_standard_source = Standard_Source::use_standard_source( $attachment_id );
				$source                     = self::get_image_source_text_raw( $attachment_id );

				// check if source of own images can be displayed
				if ( ( ! $image_uses_standard_source && $source === '' ) || ( $image_uses_standard_source && $exclude_standard ) ) {
					if ( $image_uses_standard_source && $exclude_standard ) {
						ISC_Log::log( sprintf( 'image %d: "own" sources are excluded', $attachment_id ) );
					} else {
						ISC_Log::log( sprintf( 'image %d: skipped because of empty source', $attachment_id ) );
					}
					unset( $atts[ $attachment_id ] );
				} else {
					$atts[ $attachment_id ]['title'] = get_the_title( $attachment_id );
					ISC_Log::log( sprintf( 'image %d: getting title "%s"', $attachment_id, $atts[ $attachment_id ]['title'] ) );
					$atts[ $attachment_id ]['source'] = ISC\Image_Sources\Renderer\Image_Source_String::get( $attachment_id );
					if ( ! $atts[ $attachment_id ]['source'] ) {
						ISC_Log::log( sprintf( 'image %d: skipped because of empty standard source', $attachment_id ) );
						unset( $atts[ $attachment_id ] );
					}
				}
			}

			return $this->render_attachments( $atts );
		} else {
			// see description above.
			ISC_Log::log( 'exit without any images found ' );
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
		if ( $attachments === [] ) {
			ISC_Log::log( 'exit due to missing attachments' );
			return '';
		}

		ob_start();

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
	 * Shortcode function to list all image sources of a given page (per-page list)
	 *
	 * @param array $atts attributes.
	 * @return string
	 */
	public function list_post_attachments_with_sources_shortcode( $atts = [] ) {
		global $post;

		ISC_Log::log( 'enter for [isc_list] shortcode' );

		// hotfix for https://github.com/webgilde/image-source-control/issues/48 to prevent loops
		if ( defined( 'REST_REQUEST' ) ) {
			ISC_Log::log( 'exit due to calling through REST_REQUEST' );
			return '';
		}

		$a = shortcode_atts( [ 'id' => 0 ], $atts );

		// if $id not set, use the current ID from the post
		if ( ! $a['id'] && isset( $post->ID ) ) {
			$a['id'] = $post->ID;
		}

		return $this->list_post_attachments_with_sources( $a['id'] );
	}

	/**
	 * Get source string of a feature image
	 *
	 * @param integer $post_id post object ID.
	 *
	 * @return string source
	 */
	public function get_thumbnail_source_string( int $post_id = 0 ): string {
		if ( empty( $post_id ) || ! has_post_thumbnail( $post_id ) ) {
			return '';
		}

		$id            = get_post_thumbnail_id( $post_id );
		$source_string = ISC\Image_Sources\Renderer\Caption::get(
			$id,
			[],
			[
				'prefix' => false,
				'styled' => false,
			]
		);
		if ( ! $source_string ) {
			return '';
		}

		return '<p class="isc-source-text">' . apply_filters( 'isc_featured_image_source_pre_text', _x( 'Featured image: ', 'label of the featured image source, when displayed below post excerpts', 'image-source-control-isc' ) ) . ' ' . $source_string . '</p>';
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

		_deprecated_function( __METHOD__, '1.9.0' );

		return ISC\Image_Sources\Renderer\Image_Source_String::get( $id );
	}

	/**
	 * Render the image source box
	 * dedicated to displaying an empty box as well so don’t add more visible elements
	 *
	 * @param string $content content of the source box, i.e., list of sources.
	 * @param bool   $create_placeholder if true, create a placeholder box without content.
	 */
	public function render_image_source_box( string $content = '', bool $create_placeholder = false ): string {
		$options  = $this->get_options();
		$headline = $options['image_list_headline'];

		ob_start();
		require ISCPATH . 'public/views/image-source-box.php';

		ISC_Log::log( 'finished creating image source box' );

		/**
		 * Filter: isc_render_image_source_box
		 * allow to modify the output of the image source box
		 *
		 * @param string $content content of the source box.
		 * @param string $headline headline of the source box.
		 * @param bool   $create_placeholder if true, create a placeholder box without content.
		 */
		return apply_filters( 'isc_render_image_source_box', ob_get_clean(), $content, $headline, $create_placeholder );
	}

	/**
	 * Render source string of single image by its id
	 *  this only returns the string with source and license (and urls),
	 *  but no wrapping, because the string is used in a lot of functions
	 *  (e.g. image source list where title is prepended)
	 *
	 * @updated 1.5 wrapped source into source url
	 * @updated 2.4 accept metadata as an argument
	 * @deprecated since 3.0
	 *
	 * @param int|string $id   id of the image.
	 * @param string[]   $data metadata.
	 * @param array      $args arguments
	 *                         use "disable-links" = (any value), to disable any working links.
	 *
	 * @return bool|string false if no source was given, else string with source
	 */
	public function render_image_source_string( $id, array $data = [], array $args = [] ) {
		_deprecated_function( __METHOD__, '3.0.0', 'ISC\Image_Sources\Renderer\Image_Source_String::get' );

		return ISC\Image_Sources\Renderer\Image_Source_String::get( $id, $data, $args );
	}

	/**
	 * Check if the source list can be added to the content automatically
	 *
	 * @return bool true if the source list can be added to the content
	 */
	public function can_add_list_to_content() {
		$options = $this->get_options();

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
	 * Get image source string for public output
	 *
	 * @depreacted since 3.0
	 *
	 * @param int $attachment_id attachment ID.
	 * @return string
	 */
	public static function get_image_source_text( $attachment_id ) {
		_deprecated_function( __METHOD__, '3.0.0', 'ISC\Image_Sources\Image_Source::get_image_source_text' );

		return ISC\Image_Sources\Image_Sources::get_image_source_text( $attachment_id );
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
			|| ( isset( $pagenow ) && in_array( $pagenow, [ 'wp-login.php', 'wp-signup.php', 'wp-activate.php' ], true ) )
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
				|| isset( $_GET ['wpamp'] );
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
