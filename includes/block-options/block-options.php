<?php
/**
 * Integration with the Block editor
 */

class ISC_Block_Options {
	/**
	 * Construct an instance of ISC_Block_Options
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Check, if block options are enabled
	 *
	 * @return bool
	 */
	public static function enabled(): bool {
		$options = ISC_Class::get_instance()->get_isc_options();
		// if settings don’t exist, block options are enabled by default
		if ( ! array_key_exists( 'block_options', $options )
			|| $options['block_options']
			|| apply_filters( 'isc_force_block_options', false ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Register ISC fields to be usable with the REST API
	 *
	 * @return void
	 */
	public function init() {
		if ( ! function_exists( 'register_block_type' ) || ! self::enabled() ) {
			// if block options are disabled, at least add a link to the media library where one can adjust the source
			add_action( 'enqueue_block_editor_assets', [ $this, 'edit_link_assets' ] );
			return;
		}

		add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ] );
		add_action( 'update_option', [ $this, 'widgets_update_option' ], 10, 3 );

		register_post_meta(
			'attachment',
			'isc_image_source',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);
		register_post_meta(
			'attachment',
			'isc_image_source_url',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);
		register_post_meta(
			'attachment',
			'isc_image_licence',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);
		register_post_meta(
			'attachment',
			'isc_image_source_own',
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'boolean',
			]
		);

		// Post Types supporting the editor.
		$post_types_with_editor = get_post_types_by_support( [ 'editor' ] );
		// Get all post types with the REST API enabled
		$post_types_with_block_editor = get_post_types( [ 'show_in_rest' => true ], 'names' );
		// Get the intersection of both arrays.
		$block_ready_post_types = array_intersect( $post_types_with_editor, $post_types_with_block_editor );

		foreach ( $block_ready_post_types as $type ) {
			// See https://developer.wordpress.org/reference/hooks/rest_after_insert_this-post_type/.
			add_action( "rest_after_insert_{$type}", [ $this, 'save_post' ] );
		}
	}

	/**
	 * Update ISC fields when saving the widgets page
	 *
	 * @param string              $name       option name.
	 * @param string|array|object $old_option option value before updating.
	 * @param string|array|object $new_option new option value.
	 *
	 * @return void
	 */
	public function widgets_update_option( $name, $old_option, $new_option ) {
		if ( $name !== 'widget_block' ) {
			// Do not tamper with other options.
			return;
		}

		foreach ( $new_option as $sidebar ) {
			if ( isset( $sidebar['content'] ) ) {
				$this->save_meta( $sidebar['content'] );
			}
		}
	}

	/**
	 * Grab ISC fields from a page|sidebar content then save the post meta
	 *
	 * @param string $content block editor content.
	 * @param int    $post_id currently edited post (when not on widgets/customizer).
	 *
	 * @return void
	 */
	private function save_meta( $content, $post_id = 0 ) {
		preg_match_all( '#<!-- (wp:image|wp:media-text|wp:cover|wp:post-featured-image|wp:generateblocks/image)[^{]+({.+})#', $content, $results, PREG_SET_ORDER );

		foreach ( $results as $match ) {
			$attributes = json_decode( $match[2], true );
			$image_id   = isset( $attributes['id'] ) ? (int) $attributes['id'] : 0;

			if ( isset( $attributes['mediaId'] ) ) {
				// Media and text.
				$image_id = (int) $attributes['mediaId'];
			}

			if ( $match[1] === 'wp:post-featured-image' && $post_id ) {
				// Post featured image.
				$image_id = get_post_thumbnail_id( $post_id );
			}

			if ( ! $image_id ) {
				continue;
			}

			foreach ( [ 'isc_image_source', 'isc_image_source_url', 'isc_image_source_own', 'isc_image_licence' ] as $field ) {
				if ( $field === 'isc_image_source_own' ) {
					ISC_Model::update_post_meta( $image_id, $field, isset( $attributes[ $field ] ) && $attributes[ $field ] === true ? '1' : '' );
					continue;
				}
				ISC_Model::update_post_meta( $image_id, $field, isset( $attributes[ $field ] ) ? $attributes[ $field ] : '' );
			}
		}
	}

	/**
	 * Update ISC fields when saving post using the REST API.
	 *
	 * @param WP_Post $post the currently edited post.
	 *
	 * @return void
	 */
	public function save_post( $post ) {
		$this->save_meta( $post->post_content, $post->ID );
	}

	/**
	 * Enqueue JS file and print all needed JS variables
	 */
	public function editor_assets() {
		$dependencies = [ 'jquery', 'wp-api', 'lodash', 'wp-blocks', 'wp-element', 'wp-i18n' ];
		$screen       = get_current_screen();
		wp_enqueue_script( 'isc_attachment_compat', trailingslashit( ISCBASEURL ) . 'admin/assets/js/wp.media.view.AttachmentCompat.js', [ 'media-upload' ], ISCVERSION, true );

		if ( $screen && isset( $screen->base ) && $screen->base !== 'widgets' ) {
			$dependencies[] = 'wp-editor';
		}
		wp_register_script(
			'isc/image-block',
			plugin_dir_url( __FILE__ ) . 'isc-image-block.js',
			$dependencies,
			true,
			false
		);

		$plugin_options = ISC_Class::get_instance()->get_isc_options();

		global $post, $pagenow;

		if ( ( ! empty( $post ) && current_user_can( 'edit_post', $post->ID ) ) || in_array( $pagenow, [ 'widgets.php', 'customize.php', 'site-editor.php' ], true ) ) {
			// The current user can edit the current post, or on widgets page or customizer.
			$isc_data = [
				'option'   => $plugin_options,
				'postmeta' => new stdClass(),
			];

			// Add all our data as a variable in an inline script.
			wp_add_inline_script( 'isc/image-block', 'var iscData = ' . wp_json_encode( $isc_data ) . ';', 'before' );
		}
		wp_enqueue_script( 'isc/image-block' );
		wp_set_script_translations( 'isc/image-block', 'image-source-control-isc', apply_filters( 'isc_path_to_languages', '' ) );

		wp_enqueue_script( 'isc/media-upload', trailingslashit( ISCBASEURL ) . 'admin/assets/js/media-upload.js', [ 'media-upload' ], ISCVERSION, true );
	}

	/**
	 * Enqueue script to add an edit button to image blocks
	 */
	public function edit_link_assets() {
		wp_enqueue_script(
			'isc/image-block-edit-link',
			plugin_dir_url( __FILE__ ) . 'isc-image-block-edit-link.js',
			[ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ],
			ISCVERSION,
			true // load in the footer
		);

		wp_set_script_translations( 'isc/image-block-edit-link', 'image-source-control-isc', apply_filters( 'isc_path_to_languages', '' ) );
	}
}
