<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Compatibility;

use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test \ISC\Pro\Compatibility\BuddyBoss
 */
class BuddyBoss_Test extends WPTestCase {

	protected $buddyBoss;

	public function setUp(): void {
		parent::setUp();

		// Mock BuddyBoss's existence by defining its constant.
		if ( ! defined( 'BP_PLATFORM_VERSION' ) ) {
			define( 'BP_PLATFORM_VERSION', '1.0.0' );
		}

		$this->buddyBoss = new \ISC\Pro\Compatibility\BuddyBoss();
	}

	/**
	 * Create a regular image attachment.
	 *
	 * @return int The attachment ID.
	 */
	private function create_regular_image(): int {
		$image_id = wp_insert_post( [
			                            'post_type'      => 'attachment',
			                            'post_title'     => 'Regular Image',
			                            'guid'           => 'https://example.com/regular-image.jpg',
			                            'post_mime_type' => 'image/jpeg',
		                            ] );

		return $image_id;
	}

	/**
	 * Create a BuddyBoss community image attachment.
	 *
	 * @return int The attachment ID.
	 */
	private function create_buddyboss_image(): int {
		$image_id = wp_insert_post( [
			                            'post_type'      => 'attachment',
			                            'post_title'     => 'BuddyBoss Community Image',
			                            'guid'           => 'https://example.com/bb_medias/buddyboss-image.jpg',
			                            'post_mime_type' => 'image/jpeg',
		                            ] );

		// Add BuddyBoss specific meta
		add_post_meta( $image_id, 'bp_media_upload', '1' );

		return $image_id;
	}

	/**
	 * Test that the exclude_buddyboss_community_images method adds the correct WHERE clause.
	 *
	 * @return void
	 */
	public function test_exclude_buddyboss_community_images_modifies_where_clause() {
		global $wpdb;

		$original_where = "AND wp_posts.post_type = 'attachment'";
		$modified_where = $this->buddyBoss->exclude_buddyboss_community_images( $original_where );

		// Assert that the original clause is preserved
		$this->assertStringContainsString( $original_where, $modified_where );

		// Assert that the BuddyBoss exclusion clause is added
		$this->assertStringContainsString( 'NOT EXISTS', $modified_where );
		$this->assertStringContainsString( 'bp_media_upload', $modified_where );
		$this->assertStringContainsString( "meta_value = '1'", $modified_where );
	}

	/**
	 * Test that BuddyBoss images are excluded from the WHERE clause query.
	 *
	 * @return void
	 */
	public function test_buddyboss_images_are_excluded_from_query() {
		global $wpdb;

		// Create test images
		$regular_image_id   = $this->create_regular_image();
		$buddyboss_image_id = $this->create_buddyboss_image();

		// Build a basic WHERE clause
		$where_clause = "WHERE wp_posts.post_type = 'attachment'";

		// Apply the filter
		$modified_where = $this->buddyBoss->exclude_buddyboss_community_images( $where_clause );

		// Execute a query to get attachments
		$query   = "SELECT ID FROM {$wpdb->posts} AS wp_posts {$modified_where}";
		$results = $wpdb->get_col( $query );

		// Assert that regular image is included
		$this->assertContains( (string) $regular_image_id, $results );

		// Assert that BuddyBoss image is excluded
		$this->assertNotContains( (string) $buddyboss_image_id, $results );
	}

	/**
	 * Test that hooks are registered when BuddyBoss is active and Image Sources module is enabled.
	 *
	 * @return void
	 */
	public function test_hooks_are_registered_when_buddyboss_active() {
		// Mock the Image Sources module being enabled
		add_filter( 'isc_module_enabled_image_sources', '__return_true' );

		// Re-instantiate to trigger register_hooks
		$buddyBoss = new \ISC\Pro\Compatibility\BuddyBoss();
		$buddyBoss->register_hooks();

		// Check if the filter is registered
		$this->assertNotFalse(
			has_filter( 'isc_image_sources_attachments_with_empty_sources_where_clause', [ $buddyBoss, 'exclude_buddyboss_community_images' ] ),
			'The filter should be registered when BuddyBoss is active and Image Sources module is enabled.'
		);
	}

	/**
	 * Test that images with bp_media_upload meta but different value are not excluded.
	 *
	 * @return void
	 */
	public function test_images_with_different_bp_meta_value_are_not_excluded() {
		global $wpdb;

		$image_id = wp_insert_post( [
			                            'post_type'      => 'attachment',
			                            'post_title'     => 'Image with Different Meta',
			                            'guid'           => 'https://example.com/other-image.jpg',
			                            'post_mime_type' => 'image/jpeg',
		                            ] );

		// Add meta with different value
		add_post_meta( $image_id, 'bp_media_upload', '0' );

		// Build and modify WHERE clause
		$where_clause   = "WHERE wp_posts.post_type = 'attachment'";
		$modified_where = $this->buddyBoss->exclude_buddyboss_community_images( $where_clause );

		// Execute query
		$query   = "SELECT ID FROM {$wpdb->posts} AS wp_posts {$modified_where}";
		$results = $wpdb->get_col( $query );

		// Assert image with different meta value is included
		$this->assertContains( (string) $image_id, $results );
	}
}