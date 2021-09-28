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

		wp_register_script(
			'isc/image-block',
			plugin_dir_url( __FILE__ ) . 'isc-image-block.js',
			array( 'jquery', 'wp-api', 'lodash', 'wp-blocks', 'wp-editor', 'wp-element', 'wp-i18n' ),
			true
		);

		// Gather all info about images with any of the source data.
		global $wpdb;
		$table = $wpdb->prefix . 'postmeta';
		$query = "SELECT * FROM $table WHERE `meta_key` LIKE %s";

		$results = $wpdb->get_results( $wpdb->prepare( $query, '%isc_image%' ), 'ARRAY_A' );

		$metas = array();

		// Group all the results in an associative array with the image ID as array keys.
		foreach ( $results as $meta ) {
			if ( 'isc_image_posts' === $meta['meta_key'] ) {
				continue;
			}
			if ( ! isset( $metas[ $meta['post_id'] ] ) ) {
				$metas[ $meta['post_id'] ] = array();
			}
			$metas[ $meta['post_id'] ][ $meta['meta_key'] ] = ( 'isc_image_source_own' !== $meta['meta_key'] ) ? $meta['meta_value'] : (bool) $meta['meta_value'];
		}

		$plugin_options = ISC_Class::get_instance()->get_isc_options();

		global $post;

		if ( ! empty( $post ) && current_user_can( 'edit_post', $post->ID ) ) {
			// The current user can edit the current post.
			$isc_data = array(
				'option'   => $plugin_options,
				'postmeta' => $metas,
				'nonce'    => wp_create_nonce( 'isc-gutenberg-nonce' ),
			);

			// Add all our data as a variable in an inline script.
			wp_add_inline_script( 'isc/image-block', 'var iscData = ' . wp_json_encode( $isc_data ) . ';', 'before' );
		}
		wp_enqueue_script( 'isc/image-block' );
	}

}
new Isc_Gutenberg();
