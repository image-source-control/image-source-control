<?php
/**
 * Integration with Gutenberg
 */

class Isc_Gutenberg {

	/**
	 * Construct an instance of Isc_Gutenberg
	 */
	public function __construct() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ) );
		add_action( 'wp_ajax_isc_save_meta', array( $this, 'save_meta' ) );
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Register a custom WP REST API route for loading ISC fields
	 *
	 * @return void
	 */
	public function register_route() {
		register_rest_route( 'image-source-control/v1', '/load-fields/', array(
			'method'              => 'GET',
			'callback'            => array( $this, 'load_isc_fields' ),
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
		) );
	}

	/**
	 * Load ISC metadata for one or more images
	 *
	 * @param WP_REST_Request $request the entire request data.
	 *
	 * @return array|WP_Error
	 */
	public function load_isc_fields( WP_REST_Request $request ) {
		$args = $request->get_query_params();

		if ( empty ( $args['ids'] ) ) {
			return array( 'status' => true );
		}

		// Images ID-s is a single string, individual ID separated by hyphen.
		$ids = explode( '-', $args['ids'] );

		$meta_data = array();

		foreach ( $ids as $id ) {
			$meta_data[ $id ] = $this->get_isc_meta( get_post_meta( $id ) );
		}

		return array(
			'status' => true,
			'data'   => $meta_data,
		);
	}

	/**
	 * Extract and format ISC fields from raw postmeta data.
	 *
	 * @param array $data all post meta (including non-ISC) for a given image.
	 *
	 * @return array
	 */
	private function get_isc_meta( $data ) {
		return array(
			'isc_image_source'     => isset( $data['isc_image_source'], $data['isc_image_source'][0] ) ? $data['isc_image_source'][0] : '',
			'isc_image_source_url' => isset( $data['isc_image_source_url'], $data['isc_image_source_url'][0] ) ? $data['isc_image_source_url'][0] : '',
			'isc_image_source_own' => isset( $data['isc_image_source_own'], $data['isc_image_source_own'][0] ) && $data['isc_image_source_own'][0] === '1',
			'isc_image_licence'    => isset( $data['isc_image_licence'], $data['isc_image_licence'][0] ) ? $data['isc_image_licence'][0] : '',
		);
	}

	/**
	 * Save meta data
	 */
	public function save_meta() {
		$_post      = wp_unslash( $_POST );
		$isc_fields = array(
			'isc_image_source',
			'isc_image_source_url',
			'isc_image_source_own',
			'isc_image_licence',
		);
		if ( isset( $_post['nonce'] ) && false !== wp_verify_nonce( $_post['nonce'], 'isc-gutenberg-nonce' ) ) {
			// Check if the user can edit the image and that the `key` is actually an ISC postmeta key.
			if ( current_user_can( 'edit_post', $_post['id'] ) && in_array( $_post['key'], $isc_fields ) ) {
				update_post_meta( absint( $_post['id'] ), $_post['key'], $_post['value'] );
				wp_send_json( $_post );
			}
		}
		die;
	}

	/**
	 * Enqueue JS file and print all needed JS variables
	 */
	public function editor_assets() {
		$dependencies = array( 'jquery', 'wp-api', 'lodash', 'wp-blocks', 'wp-element', 'wp-i18n' );
		$screen = get_current_screen();
		if ( $screen && isset( $screen->base ) && $screen->base !== 'widgets' ) {
			$dependencies[] = 'wp-editor';
		}
		wp_register_script(
			'isc/image-block',
			plugin_dir_url( __FILE__ ) . 'isc-image-block.js',
			$dependencies,
			true
		);

		$plugin_options = ISC_Class::get_instance()->get_isc_options();

		global $post, $pagenow;

		if ( ( ! empty( $post ) && current_user_can( 'edit_post', $post->ID ) ) || in_array( $pagenow, array( 'widgets.php', 'customize.php' ) ) ) {
			// The current user can edit the current post, or on widgets page or customizer.
			$isc_data = array(
				'option'   => $plugin_options,
				'postmeta' => new stdClass(),
				'nonce'    => wp_create_nonce( 'isc-gutenberg-nonce' ),
				'route'      => ( get_option( 'permalink_structure', '' ) === '' )
					?
					site_url( 'index.php?rest_route=' . urlencode( '/image-source-control/v1/load-fields/' ) )
					:
					site_url( '/wp-json/image-source-control/v1/load-fields/' ),
				'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			);

			// Add all our data as a variable in an inline script.
			wp_add_inline_script( 'isc/image-block', 'var iscData = ' . wp_json_encode( $isc_data ) . ';', 'before' );
		}
		wp_enqueue_script( 'isc/image-block' );
	}

}
new Isc_Gutenberg();
