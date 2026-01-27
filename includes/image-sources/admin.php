<?php

namespace ISC\Image_Sources;

/**
 * Admin features for image sources
 */
class Admin extends Image_Sources {
	/**
	 * Initiate admin functions
	 */
	public function __construct() {
		parent::__construct();

		// load components
		new Image_Sources_Admin_Scripts();
		new Admin_Menu();
		new Admin_Fields();
		new Admin_Notices();
		new Admin_Ajax();
		new Admin_Media_Library_Filters();

		// fire when an attachment is removed
		add_action( 'delete_attachment', [ $this, 'delete_attachment' ] );

		// add links to setting and source list to plugin page
		add_action( 'plugin_action_links_' . ISCBASE, [ $this, 'add_links_to_plugin_page' ] );
	}

	/**
	 * Actions to perform when an attachment is removed
	 * - delete it from the ISC storage
	 *
	 * @param int $post_id WP_Post ID.
	 */
	public function delete_attachment( $post_id ) {
		// prevent a fatal error during plugin updates in case this class was ever renamed or moved
		if ( ! class_exists( '\ISC_Storage_Model', false ) ) {
			return;
		}
		$storage_model = new \ISC_Storage_Model();
		$storage_model->remove_image_by_id( $post_id );
	}

	/**
	 * Add links to pages from plugins.php
	 *
	 * @param array $links existing plugin links.
	 *
	 * @return array
	 */
	public function add_links_to_plugin_page( $links ): array {
		// image source link
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'page', 'isc-sources', get_admin_url() . 'upload.php' ) ),
			__( 'Image Sources', 'image-source-control-isc' )
		);

		return $links;
	}
}