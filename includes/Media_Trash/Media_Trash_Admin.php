<?php

namespace ISC\Media_Trash;

/**
 * Admin features for media trash
 */
class Media_Trash_Admin {
	/**
	 * Instance of Media_Trash_Admin.
	 *
	 * @var Media_Trash_Admin
	 */
	protected static $instance;

	/**
	 * Get singleton instance
	 *
	 * @return Media_Trash_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! Media_Trash::is_enabled() ) {
			return;
		}

		// Hook into trash/restore/delete actions
		add_action( 'wp_trash_post', [ $this, 'on_trash_post' ], 10, 1 );
		add_action( 'untrash_post', [ $this, 'on_untrash_post' ], 10, 1 );
		add_action( 'before_delete_post', [ $this, 'on_delete_post' ], 10, 2 );

		// Add admin notice
		add_action( 'admin_notices', [ $this, 'show_admin_notice' ] );
	}

	/**
	 * Handle post being trashed
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_trash_post( int $post_id ) {
		$post = get_post( $post_id );

		// Only handle attachments
		if ( ! $post || 'attachment' !== $post->post_type ) {
			return;
		}

		// Store ISC meta before moving files
		$this->store_isc_meta( $post_id );

		// Move files to trash
		Media_Trash_File_Handler::move_to_trash( $post_id );
	}

	/**
	 * Handle post being restored from trash
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_untrash_post( int $post_id ) {
		$post = get_post( $post_id );

		// Only handle attachments
		if ( ! $post || 'attachment' !== $post->post_type ) {
			return;
		}

		// Restore files from trash
		Media_Trash_File_Handler::restore_from_trash( $post_id );

		// Restore ISC meta
		$this->restore_isc_meta( $post_id );
	}

	/**
	 * Handle post being permanently deleted
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function on_delete_post( int $post_id, $post ) {
		// Only handle attachments
		if ( ! $post || 'attachment' !== $post->post_type ) {
			return;
		}

		// Only delete from trash if the post was in trash
		if ( 'trash' === $post->post_status ) {
			Media_Trash_File_Handler::delete_from_trash( $post_id );
			$this->delete_isc_meta_backup( $post_id );
		}
	}

	/**
	 * Store ISC meta data before trashing
	 *
	 * @param int $post_id Post ID.
	 */
	private function store_isc_meta( int $post_id ) {
		// Get current ISC source
		$source = get_post_meta( $post_id, 'isc_image_source', true );
		$source_url = get_post_meta( $post_id, 'isc_image_source_url', true );
		$source_license = get_post_meta( $post_id, 'isc_image_licence', true );

		// Store as backup meta
		if ( ! empty( $source ) ) {
			update_post_meta( $post_id, '_isc_trash_backup_source', $source );
		}
		if ( ! empty( $source_url ) ) {
			update_post_meta( $post_id, '_isc_trash_backup_source_url', $source_url );
		}
		if ( ! empty( $source_license ) ) {
			update_post_meta( $post_id, '_isc_trash_backup_licence', $source_license );
		}
	}

	/**
	 * Restore ISC meta data after untrashing
	 *
	 * @param int $post_id Post ID.
	 */
	private function restore_isc_meta( int $post_id ) {
		// Restore from backup meta
		$backup_source = get_post_meta( $post_id, '_isc_trash_backup_source', true );
		$backup_source_url = get_post_meta( $post_id, '_isc_trash_backup_source_url', true );
		$backup_source_license = get_post_meta( $post_id, '_isc_trash_backup_licence', true );

		if ( ! empty( $backup_source ) ) {
			update_post_meta( $post_id, 'isc_image_source', $backup_source );
			delete_post_meta( $post_id, '_isc_trash_backup_source' );
		}
		if ( ! empty( $backup_source_url ) ) {
			update_post_meta( $post_id, 'isc_image_source_url', $backup_source_url );
			delete_post_meta( $post_id, '_isc_trash_backup_source_url' );
		}
		if ( ! empty( $backup_source_license ) ) {
			update_post_meta( $post_id, 'isc_image_licence', $backup_source_license );
			delete_post_meta( $post_id, '_isc_trash_backup_licence' );
		}
	}

	/**
	 * Delete ISC meta backup after permanent deletion
	 *
	 * @param int $post_id Post ID.
	 */
	private function delete_isc_meta_backup( int $post_id ) {
		delete_post_meta( $post_id, '_isc_trash_backup_source' );
		delete_post_meta( $post_id, '_isc_trash_backup_source_url' );
		delete_post_meta( $post_id, '_isc_trash_backup_licence' );
	}

	/**
	 * Show admin notice when media trash is enabled
	 */
	public function show_admin_notice() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, [ 'upload', 'attachment' ], true ) ) {
			return;
		}

		// Only show to users who can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once ISCPATH . '/admin/templates/media-trash-notice.php';
	}
}
