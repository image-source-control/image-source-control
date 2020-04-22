<?php
/**
 * Integration with Gutenberg
 */

class Isc_Gutenberg{
	
	/**
	 * Construct an instance of Isc_Gutenberg
	 */
	public function __construct() {
		if ( !function_exists( 'register_block_type' ) ) {
			return;
		}
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ) );
		add_action( 'wp_ajax_isc_save_meta', array( $this, 'save_meta' ) );
	}
	
	public function save_meta() {
		$_post = wp_unslash( $_POST );
		if ( isset( $_post['nonce'] ) && false !== wp_verify_nonce( $_post['nonce'], 'isc-gutenberg-nonce' ) ) {
			if ( current_user_can( 'edit_posts' ) ) {
				update_post_meta( absint( $_post['id'] ), $_post['key'], $_post['value'] );
				wp_send_json( $_post );
			}
		}
		die;
	}
	
	/**
	 * Enqueue JS file
	 */
	public function editor_assets() {
		
		wp_register_script(
			'isc/image-block',
			plugin_dir_url( __FILE__ ) . 'isc-image-block.js',
			array( 'jquery', 'wp-api', 'lodash', 'wp-blocks', 'wp-editor', 'wp-element', 'wp-i18n', ),
			true
		);
		
		global $wpdb;
		$table = $wpdb->prefix . 'postmeta';
		$query = "SELECT * FROM $table WHERE `meta_key` LIKE '%isc_image%'";
		
		$results = $wpdb->get_results( $query, 'ARRAY_A' );
		
		$metas = array();
		
		foreach( $results as $meta ) {
			if ( 'isc_image_posts' == $meta['meta_key'] ) {
				continue;
			}
			if ( ! isset( $metas[ $meta['post_id'] ] ) ) {
				$metas[ $meta['post_id'] ] = array();
			}
			$metas[ $meta['post_id'] ][ $meta['meta_key'] ] = ( 'isc_image_source_own' != $meta['meta_key'] )? $meta['meta_value'] : (bool)$meta['meta_value'];
		}
		
		$plugin_options = ISC_Class::get_instance()->get_isc_options();
		
		$isc_data = array(
			'option' => $plugin_options,
			'postmeta' => $metas,
			'nonce' => wp_create_nonce( 'isc-gutenberg-nonce' ),
		);
		
		wp_add_inline_script( 'isc/image-block', 'var iscData = ' . wp_json_encode( $isc_data ) . ';', 'before' );
		
		wp_enqueue_script( 'isc/image-block' );
	}
	
}
new Isc_Gutenberg();

