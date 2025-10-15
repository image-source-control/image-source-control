<?php

namespace ISC\Tests\WPUnit\Media_Trash;

use ISC\Media_Trash\Media_Trash_File_Handler;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test file operations for media trash
 */
class Media_Trash_File_Handler_Test extends WPTestCase {

	protected $upload_dir;
	protected $trash_dir;

	protected function setUp(): void {
		parent::setUp();

		// Get upload directory
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
	 * Create a test attachment with file
	 *
	 * @param string $filename Filename.
	 * @param string $subdir   Subdirectory (e.g., '2023/01').
	 * @return int Attachment ID
	 */
	private function create_test_attachment( string $filename, string $subdir = '' ): int {
		// Create directory if needed
		$dir = $this->upload_dir;
		if ( ! empty( $subdir ) ) {
			$dir = trailingslashit( $dir ) . $subdir;
			wp_mkdir_p( $dir );
		}

		// Create a dummy file
		$file_path = trailingslashit( $dir ) . $filename;
		file_put_contents( $file_path, 'test content' );

		// Create attachment
		$attachment_id = $this->factory->post->create( [
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'post_title'  => 'Test Image',
		] );

		// Set _wp_attached_file
		$attached_file = empty( $subdir ) ? $filename : trailingslashit( $subdir ) . $filename;
		update_post_meta( $attachment_id, '_wp_attached_file', $attached_file );

		return $attachment_id;
	}

	/**
	 * Test trash directory creation
	 */
	public function test_ensure_trash_dir_exists() {
		$result = Media_Trash_File_Handler::ensure_trash_dir_exists();

		$this->assertTrue( $result, 'Trash directory creation should succeed' );
		$this->assertTrue( file_exists( $this->trash_dir ), 'Trash directory should exist' );
		$this->assertTrue( file_exists( $this->trash_dir . '/.htaccess' ), '.htaccess should exist in trash directory' );
		$this->assertTrue( file_exists( $this->trash_dir . '/index.php' ), 'index.php should exist in trash directory' );
	}

	/**
	 * Test getting original file path
	 */
	public function test_get_original_file_path() {
		$attachment_id = $this->create_test_attachment( 'test-image.jpg', '2023/01' );

		$file_path = Media_Trash_File_Handler::get_original_file_path( $attachment_id );

		$expected = trailingslashit( $this->upload_dir ) . '2023/01/test-image.jpg';
		$this->assertEquals( $expected, $file_path, 'Original file path should match expected path' );
	}

	/**
	 * Test moving files to trash with year/month structure
	 */
	public function test_move_to_trash_with_year_month_structure() {
		$attachment_id = $this->create_test_attachment( 'test-image.jpg', '2023/01' );
		$original_file = trailingslashit( $this->upload_dir ) . '2023/01/test-image.jpg';

		$this->assertTrue( file_exists( $original_file ), 'Original file should exist before moving to trash' );

		$result = Media_Trash_File_Handler::move_to_trash( $attachment_id );

		$this->assertTrue( $result, 'Move to trash should succeed' );
		$this->assertFalse( file_exists( $original_file ), 'Original file should not exist after moving to trash' );

		$trash_file = trailingslashit( $this->trash_dir ) . '2023/01/test-image.jpg';
		$this->assertTrue( file_exists( $trash_file ), 'File should exist in trash directory with same structure' );
	}

	/**
	 * Test moving files to trash with custom directory structure
	 */
	public function test_move_to_trash_with_custom_structure() {
		$attachment_id = $this->create_test_attachment( 'custom-image.jpg', 'custom/path/to/image' );
		$original_file = trailingslashit( $this->upload_dir ) . 'custom/path/to/image/custom-image.jpg';

		$this->assertTrue( file_exists( $original_file ), 'Original file should exist before moving to trash' );

		$result = Media_Trash_File_Handler::move_to_trash( $attachment_id );

		$this->assertTrue( $result, 'Move to trash should succeed' );
		$this->assertFalse( file_exists( $original_file ), 'Original file should not exist after moving to trash' );

		$trash_file = trailingslashit( $this->trash_dir ) . 'custom/path/to/image/custom-image.jpg';
		$this->assertTrue( file_exists( $trash_file ), 'File should exist in trash with custom directory structure' );
	}

	/**
	 * Test moving files to trash without subdirectory
	 */
	public function test_move_to_trash_without_subdirectory() {
		$attachment_id = $this->create_test_attachment( 'root-image.jpg' );
		$original_file = trailingslashit( $this->upload_dir ) . 'root-image.jpg';

		$this->assertTrue( file_exists( $original_file ), 'Original file should exist before moving to trash' );

		$result = Media_Trash_File_Handler::move_to_trash( $attachment_id );

		$this->assertTrue( $result, 'Move to trash should succeed' );
		$this->assertFalse( file_exists( $original_file ), 'Original file should not exist after moving to trash' );

		$trash_file = trailingslashit( $this->trash_dir ) . 'root-image.jpg';
		$this->assertTrue( file_exists( $trash_file ), 'File should exist in trash directory' );
	}

	/**
	 * Test restoring files from trash
	 */
	public function test_restore_from_trash() {
		$attachment_id = $this->create_test_attachment( 'restore-test.jpg', '2023/02' );
		$original_file = trailingslashit( $this->upload_dir ) . '2023/02/restore-test.jpg';

		// Move to trash
		Media_Trash_File_Handler::move_to_trash( $attachment_id );
		$this->assertFalse( file_exists( $original_file ), 'Original file should be in trash' );

		// Restore from trash
		$result = Media_Trash_File_Handler::restore_from_trash( $attachment_id );

		$this->assertTrue( $result, 'Restore from trash should succeed' );
		$this->assertTrue( file_exists( $original_file ), 'Original file should be restored' );

		$trash_file = trailingslashit( $this->trash_dir ) . '2023/02/restore-test.jpg';
		$this->assertFalse( file_exists( $trash_file ), 'File should not exist in trash after restore' );
	}

	/**
	 * Test deleting files from trash
	 */
	public function test_delete_from_trash() {
		$attachment_id = $this->create_test_attachment( 'delete-test.jpg', '2023/03' );

		// Move to trash
		Media_Trash_File_Handler::move_to_trash( $attachment_id );

		$trash_file = trailingslashit( $this->trash_dir ) . '2023/03/delete-test.jpg';
		$this->assertTrue( file_exists( $trash_file ), 'File should be in trash' );

		// Delete from trash
		$result = Media_Trash_File_Handler::delete_from_trash( $attachment_id );

		$this->assertTrue( $result, 'Delete from trash should succeed' );
		$this->assertFalse( file_exists( $trash_file ), 'File should be deleted from trash' );
	}

	/**
	 * Test that _wp_attached_file remains correct after trash operations
	 */
	public function test_wp_attached_file_remains_correct() {
		$attachment_id = $this->create_test_attachment( 'meta-test.jpg', '2023/04' );
		$original_attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );

		// Move to trash
		Media_Trash_File_Handler::move_to_trash( $attachment_id );
		$after_trash = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$this->assertEquals( $original_attached_file, $after_trash, '_wp_attached_file should remain unchanged after trash' );

		// Restore
		Media_Trash_File_Handler::restore_from_trash( $attachment_id );
		$after_restore = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$this->assertEquals( $original_attached_file, $after_restore, '_wp_attached_file should remain unchanged after restore' );
	}

	/**
	 * Test handling missing files gracefully
	 */
	public function test_move_to_trash_with_missing_file() {
		$attachment_id = $this->create_test_attachment( 'missing.jpg', '2023/05' );

		// Delete the file manually
		$file_path = trailingslashit( $this->upload_dir ) . '2023/05/missing.jpg';
		unlink( $file_path );

		$result = Media_Trash_File_Handler::move_to_trash( $attachment_id );

		// Should return false when file doesn't exist
		$this->assertFalse( $result, 'Move to trash should fail when file is missing' );
	}

	/**
	 * Test restore when file doesn't exist in trash
	 */
	public function test_restore_from_trash_with_missing_file() {
		$attachment_id = $this->create_test_attachment( 'missing-restore.jpg', '2023/06' );

		// Don't move to trash, just try to restore
		$result = Media_Trash_File_Handler::restore_from_trash( $attachment_id );

		$this->assertFalse( $result, 'Restore should fail when file is not in trash' );
	}
}
