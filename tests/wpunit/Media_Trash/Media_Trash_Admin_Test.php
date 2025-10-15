<?php

namespace ISC\Tests\WPUnit\Media_Trash;

use ISC\Media_Trash\Media_Trash_Admin;
use ISC\Media_Trash\Media_Trash_File_Handler;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test Media Trash Admin functionality including ISC meta tracking
 */
class Media_Trash_Admin_Test extends WPTestCase {

	protected $upload_dir;
	protected $trash_dir;

	protected function setUp(): void {
		parent::setUp();

		// Enable media trash module
		update_option( 'isc_options', [
			'modules' => [ 'media_trash' ]
		] );

		// Define MEDIA_TRASH if not already defined
		if ( ! defined( 'MEDIA_TRASH' ) ) {
			define( 'MEDIA_TRASH', true );
		}

		// Get directories
		$upload = wp_upload_dir();
		$this->upload_dir = $upload['basedir'];
		$this->trash_dir = Media_Trash_File_Handler::get_trash_dir();

		// Clean up trash directory
		if ( file_exists( $this->trash_dir ) ) {
			$this->delete_directory( $this->trash_dir );
		}
	}

	protected function tearDown(): void {
		// Clean up trash directory
		if ( file_exists( $this->trash_dir ) ) {
			$this->delete_directory( $this->trash_dir );
		}

		delete_option( 'isc_options' );
		parent::tearDown();
	}

	/**
	 * Recursively delete a directory
	 *
	 * @param string $dir Directory path.
	 */
	private function delete_directory( string $dir ) {
		if ( ! file_exists( $dir ) ) {
			return;
		}

		$files = array_diff( scandir( $dir ), [ '.', '..' ] );
		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			is_dir( $path ) ? $this->delete_directory( $path ) : unlink( $path );
		}
		rmdir( $dir );
	}

	/**
	 * Create a test attachment with file and ISC meta
	 *
	 * @param array $args Optional arguments.
	 * @return int Attachment ID
	 */
	private function create_test_attachment_with_meta( array $args = [] ): int {
		$defaults = [
			'filename'      => 'test-image.jpg',
			'subdir'        => '2023/01',
			'source'        => 'Test Source',
			'source_url'    => 'https://example.com',
			'source_license'=> 'CC BY 4.0',
		];

		$args = array_merge( $defaults, $args );

		// Create directory
		$dir = $this->upload_dir;
		if ( ! empty( $args['subdir'] ) ) {
			$dir = trailingslashit( $dir ) . $args['subdir'];
			wp_mkdir_p( $dir );
		}

		// Create file
		$file_path = trailingslashit( $dir ) . $args['filename'];
		file_put_contents( $file_path, 'test content' );

		// Create attachment
		$attachment_id = $this->factory->post->create( [
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'post_title'  => 'Test Image',
		] );

		// Set meta
		$attached_file = empty( $args['subdir'] ) ? $args['filename'] : trailingslashit( $args['subdir'] ) . $args['filename'];
		update_post_meta( $attachment_id, '_wp_attached_file', $attached_file );

		if ( ! empty( $args['source'] ) ) {
			update_post_meta( $attachment_id, 'isc_image_source', $args['source'] );
		}
		if ( ! empty( $args['source_url'] ) ) {
			update_post_meta( $attachment_id, 'isc_image_source_url', $args['source_url'] );
		}
		if ( ! empty( $args['source_license'] ) ) {
			update_post_meta( $attachment_id, 'isc_image_licence', $args['source_license'] );
		}

		return $attachment_id;
	}

	/**
	 * Test that ISC meta is stored when trashing
	 */
	public function test_isc_meta_stored_on_trash() {
		$attachment_id = $this->create_test_attachment_with_meta();

		// Initialize admin to register hooks
		$admin = new Media_Trash_Admin();

		// Trash the attachment
		wp_trash_post( $attachment_id );

		// Check backup meta was created
		$backup_source = get_post_meta( $attachment_id, '_isc_trash_backup_source', true );
		$backup_url = get_post_meta( $attachment_id, '_isc_trash_backup_source_url', true );
		$backup_license = get_post_meta( $attachment_id, '_isc_trash_backup_licence', true );

		$this->assertEquals( 'Test Source', $backup_source, 'ISC source should be backed up' );
		$this->assertEquals( 'https://example.com', $backup_url, 'ISC source URL should be backed up' );
		$this->assertEquals( 'CC BY 4.0', $backup_license, 'ISC license should be backed up' );
	}

	/**
	 * Test that ISC meta is restored when untrashing
	 */
	public function test_isc_meta_restored_on_untrash() {
		$attachment_id = $this->create_test_attachment_with_meta();

		// Initialize admin
		$admin = new Media_Trash_Admin();

		// Trash the attachment
		wp_trash_post( $attachment_id );

		// Clear original meta to simulate data loss
		delete_post_meta( $attachment_id, 'isc_image_source' );
		delete_post_meta( $attachment_id, 'isc_image_source_url' );
		delete_post_meta( $attachment_id, 'isc_image_licence' );

		// Untrash the attachment
		wp_untrash_post( $attachment_id );

		// Check meta was restored
		$source = get_post_meta( $attachment_id, 'isc_image_source', true );
		$url = get_post_meta( $attachment_id, 'isc_image_source_url', true );
		$license = get_post_meta( $attachment_id, 'isc_image_licence', true );

		$this->assertEquals( 'Test Source', $source, 'ISC source should be restored' );
		$this->assertEquals( 'https://example.com', $url, 'ISC source URL should be restored' );
		$this->assertEquals( 'CC BY 4.0', $license, 'ISC license should be restored' );

		// Check backup meta was removed
		$backup_source = get_post_meta( $attachment_id, '_isc_trash_backup_source', true );
		$this->assertEmpty( $backup_source, 'Backup meta should be removed after restore' );
	}

	/**
	 * Test that ISC meta backup is deleted on permanent deletion
	 */
	public function test_isc_meta_backup_deleted_on_permanent_delete() {
		$attachment_id = $this->create_test_attachment_with_meta();

		// Initialize admin
		$admin = new Media_Trash_Admin();

		// Trash the attachment
		wp_trash_post( $attachment_id );

		// Verify backup exists
		$backup_source = get_post_meta( $attachment_id, '_isc_trash_backup_source', true );
		$this->assertEquals( 'Test Source', $backup_source, 'Backup should exist after trash' );

		// Permanently delete
		wp_delete_post( $attachment_id, true );

		// Meta should be gone (post is deleted)
		$post = get_post( $attachment_id );
		$this->assertNull( $post, 'Post should be deleted' );
	}

	/**
	 * Test complete trash workflow: trash -> restore -> trash -> delete
	 */
	public function test_complete_trash_workflow() {
		$attachment_id = $this->create_test_attachment_with_meta();
		$original_file = trailingslashit( $this->upload_dir ) . '2023/01/test-image.jpg';
		$trash_file = trailingslashit( $this->trash_dir ) . '2023/01/test-image.jpg';

		// Initialize admin
		$admin = new Media_Trash_Admin();

		// Initial state
		$this->assertTrue( file_exists( $original_file ), 'Original file should exist' );

		// Trash
		wp_trash_post( $attachment_id );
		$this->assertFalse( file_exists( $original_file ), 'File should be moved to trash' );
		$this->assertTrue( file_exists( $trash_file ), 'File should exist in trash' );
		$this->assertEquals( 'trash', get_post_status( $attachment_id ), 'Post status should be trash' );

		// Restore
		wp_untrash_post( $attachment_id );
		$this->assertTrue( file_exists( $original_file ), 'File should be restored' );
		$this->assertFalse( file_exists( $trash_file ), 'File should not be in trash' );
		$this->assertEquals( 'inherit', get_post_status( $attachment_id ), 'Post status should be inherit' );

		// Trash again
		wp_trash_post( $attachment_id );
		$this->assertFalse( file_exists( $original_file ), 'File should be moved to trash again' );
		$this->assertTrue( file_exists( $trash_file ), 'File should exist in trash again' );

		// Permanently delete
		wp_delete_post( $attachment_id, true );
		$this->assertFalse( file_exists( $trash_file ), 'File should be deleted from trash' );
		$this->assertNull( get_post( $attachment_id ), 'Post should be deleted' );
	}

	/**
	 * Test that non-attachment posts are not affected
	 */
	public function test_non_attachment_posts_not_affected() {
		$post_id = $this->factory->post->create( [
			'post_type'   => 'post',
			'post_status' => 'publish',
		] );

		// Initialize admin
		$admin = new Media_Trash_Admin();

		// Trash the post
		wp_trash_post( $post_id );

		// Should not create backup meta
		$backup = get_post_meta( $post_id, '_isc_trash_backup_source', true );
		$this->assertEmpty( $backup, 'Non-attachment should not have backup meta' );
	}
}
