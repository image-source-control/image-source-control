<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Content_Scan;

use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing the Index_Table::delete_not_updated_since() method
 */
class Content_Scan_Table_Delete_Not_Updated_Since_Test extends WPTestCase {

	/**
	 * @var Content_Scan_Table
	 */
	private Content_Scan_Table $content_scan_table;

	/**
	 * @var int
	 */
	private $post_id;

	/**
	 * @var int
	 */
	private $attachment_id;

	public function setUp(): void {
		parent::setUp();

		$this->content_scan_table = new Content_Scan_Table();

		// Create test post and attachment
		$this->post_id       = $this->factory()->post->create();
		$this->attachment_id = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test delete_not_updated_since with multiple entries of different ages
	 */
	public function test_delete_not_updated_since() {
		$current_time = time();

		// Create multiple entries with different timestamps
		// Entry 1: Very old (3 hours ago) - should be deleted
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 10800 )
		);

		// Entry 2: Old (2 hours ago) - should be deleted
		$attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_2,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 7200 )
		);

		// Entry 3: Just outside safety margin (1 hour + 2 minutes ago) - should be deleted
		// The safety margin makes the cutoff at (1h + 1min), so this is old enough
		$attachment_id_3 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_3,
			'thumbnail',
			$this->content_scan_table->get_last_checked( $current_time - 3720 )
		);

		// Entry 4: Recent (30 minutes ago) - should NOT be deleted
		$attachment_id_4 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_4,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 1800 )
		);

		// Entry 5: Very recent (5 minutes ago) - should NOT be deleted
		$attachment_id_5 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_5,
			'head',
			$this->content_scan_table->get_last_checked( $current_time - 300 )
		);

		// Verify all 5 entries exist before deletion
		$count_before = $this->content_scan_table->count_occurrences_by_post_id( $this->post_id );
		$this->assertEquals( 5, $count_before, 'Should have 5 entries before deletion' );

		// Delete entries older than 1 hour
		// With safety margin, this deletes entries older than 1h + 1min
		$deleted = $this->content_scan_table->delete_not_updated_since( $current_time - 3600 );

		// Should delete entries 1, 2, and 3 (3 entries total)
		$this->assertEquals( 3, $deleted, 'Should delete exactly 3 old entries' );

		// Verify remaining entries
		$remaining = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 2, $remaining, 'Should have 2 entries remaining' );

		// Verify the correct entries remain
		$this->assertArrayHasKey( $attachment_id_4, $remaining, 'Recent entry (30 min) should remain' );
		$this->assertArrayHasKey( $attachment_id_5, $remaining, 'Very recent entry (5 min) should remain' );

		// Verify the old entries are gone
		$this->assertArrayNotHasKey( $this->attachment_id, $remaining, 'Very old entry (3h) should be deleted' );
		$this->assertArrayNotHasKey( $attachment_id_2, $remaining, 'Old entry (2h) should be deleted' );
		$this->assertArrayNotHasKey( $attachment_id_3, $remaining, 'Borderline entry (1h+2min) should be deleted' );
	}

	/**
	 * Test delete_not_updated_since with no matching entries
	 */
	public function test_delete_not_updated_since_with_no_old_entries() {
		$current_time = time();

		// Insert only recent entries
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 300 )
		);

		// Try to delete entries older than 1 hour (there are none)
		$deleted = $this->content_scan_table->delete_not_updated_since( $current_time - 3600 );

		$this->assertEquals( 0, $deleted, 'Should delete 0 entries when none are old enough' );

		// Verify entry still exists
		$remaining = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 1, $remaining, 'Recent entry should remain' );
	}

	/**
	 * Test delete_not_updated_since deletes across multiple posts
	 */
	public function test_delete_not_updated_since_across_multiple_posts() {
		$current_time = time();
		$post_id_2    = $this->factory()->post->create();

		// Post 1: Old entry
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 7200 )
		);

		// Post 2: Old entry
		$attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$post_id_2,
			$attachment_id_2,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 7200 )
		);

		// Post 1: Recent entry
		$attachment_id_3 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_3,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 300 )
		);

		// Verify 3 total entries before deletion
		$count_post_1 = $this->content_scan_table->count_occurrences_by_post_id( $this->post_id );
		$count_post_2 = $this->content_scan_table->count_occurrences_by_post_id( $post_id_2 );
		$this->assertEquals( 2, $count_post_1, 'Post 1 should have 2 entries' );
		$this->assertEquals( 1, $count_post_2, 'Post 2 should have 1 entry' );

		// Delete old entries across all posts
		$deleted = $this->content_scan_table->delete_not_updated_since( $current_time - 3600 );

		$this->assertEquals( 2, $deleted, 'Should delete 2 old entries across different posts' );

		// Verify only the recent entry remains in post 1
		$remaining_post_1 = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 1, $remaining_post_1, 'Post 1 should have 1 entry remaining' );
		$this->assertArrayHasKey( $attachment_id_3, $remaining_post_1 );

		// Verify post 2 has no entries left
		$remaining_post_2 = $this->content_scan_table->get_by_post_id( $post_id_2 );
		$this->assertEmpty( $remaining_post_2, 'Post 2 should have no entries remaining' );
	}

	/**
	 * Test delete_not_updated_since with different positions
	 * Verifies that position doesn't affect deletion (time-based only)
	 */
	public function test_delete_not_updated_since_ignores_position() {
		$current_time = time();

		// Old entries with different positions
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 7200 )
		);

		$attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_2,
			'thumbnail',
			$this->content_scan_table->get_last_checked( $current_time - 7200 )
		);

		$attachment_id_3 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_3,
			'head',
			$this->content_scan_table->get_last_checked( $current_time - 7200 )
		);

		// Delete old entries
		$deleted = $this->content_scan_table->delete_not_updated_since( $current_time - 3600 );

		$this->assertEquals( 3, $deleted, 'Should delete all old entries regardless of position' );
	}

	/**
	 * Test delete_not_updated_since with zero timestamp returns 0
	 */
	public function test_delete_not_updated_since_with_zero_timestamp() {
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		// Zero timestamp should not delete anything
		$deleted = $this->content_scan_table->delete_not_updated_since( 0 );

		$this->assertEquals( 0, $deleted, 'Should not delete anything with zero timestamp' );

		// Verify entry still exists
		$remaining = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 1, $remaining );
	}

	/**
	 * Test delete_not_updated_since with negative timestamp returns 0
	 */
	public function test_delete_not_updated_since_with_negative_timestamp() {
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		// Negative timestamp should not delete anything
		$deleted = $this->content_scan_table->delete_not_updated_since( - 100 );

		$this->assertEquals( 0, $deleted, 'Should not delete anything with negative timestamp' );

		// Verify entry still exists
		$remaining = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 1, $remaining );
	}

	/**
	 * Test safety margin is applied (MINUTE_IN_SECONDS subtraction)
	 * The method subtracts 60 seconds from the timestamp as a safety margin
	 */
	public function test_delete_not_updated_since_applies_safety_margin() {
		$current_time = time();

		// Entry exactly at the boundary (1 hour ago)
		// With safety margin, this is within the protected window
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 3600 )
		);

		// Entry within safety margin (1 hour + 30 seconds ago)
		// Still within the protected window (1h + 1min)
		$attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_2,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 3630 )
		);

		// Entry outside safety margin (1 hour + 2 minutes ago)
		// This is older than (1h + 1min), so should be deleted
		$attachment_id_3 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_3,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 3720 )
		);

		// Entry well outside safety margin (2 hours ago)
		$attachment_id_4 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->content_scan_table->insert_or_update(
			$this->post_id,
			$attachment_id_4,
			'content',
			$this->content_scan_table->get_last_checked( $current_time - 7200 )
		);

		// Delete entries older than 1 hour
		// With safety margin, this becomes: delete entries older than 1h + 1min
		$deleted = $this->content_scan_table->delete_not_updated_since( $current_time - 3600 );

		// Should delete attachment_id_3 (1h + 2min) and attachment_id_4 (2h)
		// Should keep attachment_id (1h) and attachment_id_2 (1h + 30s)
		$this->assertEquals( 2, $deleted, 'Should delete 2 entries outside safety margin window' );

		$remaining = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 2, $remaining, 'Should have 2 entries remaining within safety margin' );
		$this->assertArrayHasKey( $this->attachment_id, $remaining, 'Entry at 1h should remain (within safety margin)' );
		$this->assertArrayHasKey( $attachment_id_2, $remaining, 'Entry at 1h+30s should remain (within safety margin)' );
		$this->assertArrayNotHasKey( $attachment_id_3, $remaining, 'Entry at 1h+2min should be deleted (outside safety margin)' );
		$this->assertArrayNotHasKey( $attachment_id_4, $remaining, 'Entry at 2h should be deleted (outside safety margin)' );
	}

	/**
	 * Test delete_not_updated_since with empty table
	 */
	public function test_delete_not_updated_since_with_empty_table() {
		$deleted = $this->content_scan_table->delete_not_updated_since( time() - 3600 );

		$this->assertEquals( 0, $deleted, 'Should return 0 when table is empty' );
	}
}