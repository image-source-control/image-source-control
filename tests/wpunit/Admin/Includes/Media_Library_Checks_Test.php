<?php

namespace ISC\Tests\WPUnit\Admin\Includes;

use ISC\Admin\Media_Library_Checks;
use ISC\Tests\WPUnit\WPTestCase;
use WP_Post;

/**
 * Test if ISC\Admin\Media_Type_Checker works as expected.
 */
class Media_Library_Checks_Test extends WPTestCase {

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure home_url is consistent for this test.
		update_option( 'home', 'https://localhost' );
		update_option( 'siteurl', 'https://localhost' );
	}

	/**
	 * Helper to create an attachment with specific GUID and metadata.
	 *
	 * @param string $guid     The GUID for the attachment.
	 * @param array  $metadata The attachment metadata.
	 *
	 * @return array|null|WP_Post The created attachment post object.
	 */
	protected function create_attachment_with_data( string $guid, array $metadata = [] ) {
		// Create a dummy post to attach the image to, if needed.
		$parent_post_id = $this->factory()->post->create();

		$attachment_id = wp_insert_attachment(
			[
				'post_title'     => 'Test Image ' . uniqid(),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/jpeg',
				'guid'           => $guid,
				'post_parent'    => $parent_post_id,
			],
			codecept_data_dir( 'test-image1.jpg' ), // Use one of the provided test images.
			$parent_post_id
		);

		// Ensure the attachment was created successfully.
		$this->assertIsInt( $attachment_id );
		$this->assertGreaterThan( 0, $attachment_id );

		// Update attachment metadata.
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return get_post( $attachment_id );
	}

	/**
	 * Test has_guid_domain_mismatch() returns true for different domains.
	 */
	public function test_has_guid_domain_mismatch_true(): void {
		// Temporarily change home_url for this test to ensure a domain mismatch.
		update_option( 'home', 'https://example.com' );
		update_option( 'siteurl', 'https://example.com' );

		$upload_dir = wp_get_upload_dir();
		$file_path  = '2023/01/test-image1.jpg';
		// Create a GUID with a different domain.
		$guid       = 'https://different-domain.com' . $upload_dir['baseurl'] . '/' . $file_path;
		$metadata   = [ 'file' => $file_path ];

		$attachment = $this->create_attachment_with_data( $guid, $metadata );

		$result = (new Media_Library_Checks() )->has_guid_domain_mismatch( $attachment );

		$this->assertTrue( $result, 'Expected GUID domain mismatch to be true.' );
	}

	/**
	 * Test has_guid_domain_mismatch() returns false for matching domains.
	 */
	public function test_has_guid_domain_mismatch_false(): void {
		$file_path  = '2023/01/test-image1.jpg';
		$guid       = home_url() . '/wp-content/uploads/' . $file_path; // GUID matches home_url.
		$metadata   = [ 'file' => $file_path ];

		$attachment = $this->create_attachment_with_data( $guid, $metadata );

		$result = (new Media_Library_Checks() )->has_guid_domain_mismatch( $attachment );

		$this->assertFalse( $result, 'Expected GUID domain mismatch to be false for matching domains.' );
	}

	/**
	 * Test has_guid_domain_mismatch() returns false if GUID has no host (e.g., relative path).
	 */
	public function test_has_guid_domain_mismatch_no_host_in_guid(): void {
		$file_path = '2023/01/test-image1.jpg';
		$guid      = '/wp-content/uploads/' . $file_path; // Relative GUID, no host.
		$metadata  = [ 'file' => $file_path ];

		$attachment = $this->create_attachment_with_data( $guid, $metadata );

		$result = (new Media_Library_Checks() )->has_guid_domain_mismatch( $attachment );

		$this->assertFalse( $result, 'Expected GUID domain mismatch to be false for relative GUID.' );
	}

	/**
	 * Test has_guid_path_mismatch() returns true for different directory paths.
	 */
	public function test_has_guid_path_mismatch_true_different_directory(): void {
		// GUID points to a different month directory.
		$guid     = home_url() . '/wp-content/uploads/2023/02/test-image1.jpg';
		$metadata = [ 'file' => '2023/01/test-image1.jpg' ];

		$attachment = $this->create_attachment_with_data( $guid, $metadata );

		$result = (new Media_Library_Checks() )->has_guid_path_mismatch( $attachment );

		$this->assertTrue( $result, 'Expected GUID path mismatch to be true for different directories.' );
	}

	/**
	 * Test has_guid_path_mismatch() returns false for same directory path, even with '-scaled' suffix.
	 */
	public function test_has_guid_path_mismatch_false_same_directory_scaled_image(): void {
		// GUID points to a scaled version in the same directory.
		$guid     = home_url() . '/wp-content/uploads/2023/01/test-image1-scaled.jpg';
		$metadata = [ 'file' => '2023/01/test-image1.jpg' ];

		$attachment = $this->create_attachment_with_data( $guid, $metadata );

		$result = (new Media_Library_Checks() )->has_guid_path_mismatch( $attachment );

		$this->assertFalse( $result, 'Expected GUID path mismatch to be false for scaled image in same directory.' );
	}

	/**
	 * Test has_guid_path_mismatch() returns false for matching directory paths and filenames.
	 */
	public function test_has_guid_path_mismatch_false_same_directory_original_image(): void {
		$guid     = home_url() . '/wp-content/uploads/2023/01/test-image1.jpg';
		$metadata = [ 'file' => '2023/01/test-image1.jpg' ];

		$attachment = $this->create_attachment_with_data( $guid, $metadata );

		$result = (new Media_Library_Checks() )->has_guid_path_mismatch( $attachment );

		$this->assertFalse( $result, 'Expected GUID path mismatch to be false for matching paths.' );
	}

	/**
	 * Test has_guid_path_mismatch() returns false if _wp_attachment_metadata is missing 'file'.
	 */
	public function test_has_guid_path_mismatch_no_meta_file(): void {
		$guid     = home_url() . '/wp-content/uploads/2023/01/test-image1.jpg';
		$metadata = []; // No 'file' key.

		$attachment = $this->create_attachment_with_data( $guid, $metadata );

		$result = (new Media_Library_Checks() )->has_guid_path_mismatch( $attachment );

		$this->assertFalse( $result, 'Expected GUID path mismatch to be false when meta file is missing.' );
	}

	/**
	 * Test has_guid_path_mismatch() returns false if _wp_attachment_metadata 'file' is empty.
	 */
	public function test_has_guid_path_mismatch_empty_meta_file(): void {
		$guid     = home_url() . '/wp-content/uploads/2023/01/test-image1.jpg';
		$metadata = [ 'file' => '' ]; // Empty 'file' key.

		$attachment = $this->create_attachment_with_data( $guid, $metadata );

		$result = (new Media_Library_Checks() )->has_guid_path_mismatch( $attachment );

		$this->assertFalse( $result, 'Expected GUID path mismatch to be false when meta file is empty.' );
	}

	/**
	 * Test has_guid_path_mismatch() returns true if GUID path is outside the uploads directory.
	 */
	public function test_has_guid_path_mismatch_guid_outside_uploads_dir(): void {
		// GUID points to a path not starting with the uploads base URL path.
		$guid      = home_url() . '/some-other-dir/2023/01/test-image1.jpg';
		$metadata  = [ 'file' => '2023/01/test-image1.jpg' ];

		$attachment = $this->create_attachment_with_data( $guid, $metadata );

		$result = (new Media_Library_Checks() )->has_guid_path_mismatch( $attachment );

		$this->assertTrue( $result, 'Expected GUID path mismatch to be true when GUID is outside uploads directory.' );
	}
}