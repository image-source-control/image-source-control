<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Content_Scan;

use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing the Index_Table::count_by_attachment_id_and_position() method
 */
class Content_Scan_Table_Count_By_Attachment_Id_And_Position_Test extends WPTestCase {

	/**
	 * @var Content_Scan_Table
	 */
	private Content_Scan_Table $content_scan_table;

	/**
	 * @var int
	 */
	private $post_id_1;

	/**
	 * @var int
	 */
	private $post_id_2;

	/**
	 * @var int
	 */
	private $post_id_3;

	/**
	 * @var int
	 */
	private $attachment_id;

	public function setUp(): void {
		parent::setUp();

		$this->content_scan_table = new Content_Scan_Table();

		// Create test posts and attachment
		$this->post_id_1     = $this->factory()->post->create();
		$this->post_id_2     = $this->factory()->post->create();
		$this->post_id_3     = $this->factory()->post->create();
		$this->attachment_id = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test counting entries with invalid attachment_id returns 0
	 */
	public function test_count_with_invalid_attachment_id_returns_zero() {
		$count = $this->content_scan_table->count_by_attachment_id_and_position( 0, 'content' );

		$this->assertEquals( 0, $count, 'Should return 0 for invalid attachment ID' );
	}

	/**
	 * Test counting entries with invalid position returns 0
	 */
	public function test_count_with_invalid_position_returns_zero() {
		$this->content_scan_table->insert_or_update( $this->post_id_1, $this->attachment_id, 'content' );

		$count = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'invalid_position' );

		$this->assertEquals( 0, $count, 'Should return 0 for invalid position' );
	}

	/**
	 * Test counting returns 0 when no entries exist
	 */
	public function test_count_returns_zero_when_no_entries_exist() {
		$count = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'content' );

		$this->assertEquals( 0, $count, 'Should return 0 when no entries exist' );
	}

	/**
	 * Test counting entries for a specific position
	 */
	public function test_count_entries_for_specific_position() {
		// Add 3 entries with 'head' position
		$this->content_scan_table->insert_or_update( $this->post_id_1, $this->attachment_id, 'head' );
		$this->content_scan_table->insert_or_update( $this->post_id_2, $this->attachment_id, 'head' );
		$this->content_scan_table->insert_or_update( $this->post_id_3, $this->attachment_id, 'head' );

		$count = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'head' );

		$this->assertEquals( 3, $count, 'Should count 3 entries with head position' );
	}

	/**
	 * Test counting only counts the specified position, not others
	 */
	public function test_count_only_counts_specified_position() {
		// Add entries with different positions
		$this->content_scan_table->insert_or_update( $this->post_id_1, $this->attachment_id, 'head' );
		$this->content_scan_table->insert_or_update( $this->post_id_2, $this->attachment_id, 'body' );
		$this->content_scan_table->insert_or_update( $this->post_id_3, $this->attachment_id, 'content' );

		$count_head = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'head' );
		$count_body = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'body' );
		$count_content = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'content' );

		$this->assertEquals( 1, $count_head, 'Should count only head entries' );
		$this->assertEquals( 1, $count_body, 'Should count only body entries' );
		$this->assertEquals( 1, $count_content, 'Should count only content entries' );
	}

	/**
	 * Test counting with timestamp filter - only counts entries updated since timestamp
	 */
	public function test_count_with_timestamp_filter() {
		$current_time = time();

		// Add old entry (2 hours ago)
		$old_timestamp = $current_time - 7200;
		$this->content_scan_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id,
			'body',
			$this->content_scan_table->get_last_checked( $old_timestamp )
		);

		// Add recent entry (30 minutes ago)
		$recent_timestamp = $current_time - 1800;
		$this->content_scan_table->insert_or_update(
			$this->post_id_2,
			$this->attachment_id,
			'body',
			$this->content_scan_table->get_last_checked( $recent_timestamp )
		);

		// Add very recent entry (5 minutes ago)
		$very_recent_timestamp = $current_time - 300;
		$this->content_scan_table->insert_or_update(
			$this->post_id_3,
			$this->attachment_id,
			'body',
			$this->content_scan_table->get_last_checked( $very_recent_timestamp )
		);

		// Count all entries (no timestamp filter)
		$count_all = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'body' );
		$this->assertEquals( 3, $count_all, 'Should count all 3 entries without timestamp filter' );

		// Count only entries updated in the last hour
		$one_hour_ago = $current_time - 3600;
		$count_recent = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'body', $one_hour_ago );
		$this->assertEquals( 2, $count_recent, 'Should count only 2 entries updated in the last hour' );

		// Count only entries updated in the last 10 minutes
		$ten_minutes_ago = $current_time - 600;
		$count_very_recent = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'body', $ten_minutes_ago );
		$this->assertEquals( 1, $count_very_recent, 'Should count only 1 entry updated in the last 10 minutes' );
	}

	/**
	 * Test counting with future timestamp returns 0
	 */
	public function test_count_with_future_timestamp_returns_zero() {
		$current_time = time();

		// Add entry with current timestamp
		$this->content_scan_table->insert_or_update( $this->post_id_1, $this->attachment_id, 'head' );

		// Try to count with a future timestamp
		$future_timestamp = $current_time + 3600;
		$count = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'head', $future_timestamp );

		$this->assertEquals( 0, $count, 'Should return 0 when timestamp is in the future' );
	}

	/**
	 * Test counting multiple attachments - should only count the specified attachment
	 */
	public function test_count_only_counts_specified_attachment() {
		$attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );

		// Add entries for first attachment
		$this->content_scan_table->insert_or_update( $this->post_id_1, $this->attachment_id, 'body' );
		$this->content_scan_table->insert_or_update( $this->post_id_2, $this->attachment_id, 'body' );

		// Add entries for second attachment
		$this->content_scan_table->insert_or_update( $this->post_id_3, $attachment_id_2, 'body' );

		$count_first = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'body' );
		$count_second = $this->content_scan_table->count_by_attachment_id_and_position( $attachment_id_2, 'body' );

		$this->assertEquals( 2, $count_first, 'Should count only entries for first attachment' );
		$this->assertEquals( 1, $count_second, 'Should count only entries for second attachment' );
	}

	/**
	 * Test counting with exact threshold boundary
	 */
	public function test_count_at_threshold_boundary() {
		$threshold = \ISC\Pro\Unused_Images\Content_Scan\Content_Scan::get_global_threshold(); // Default is 4

		// Add exactly threshold number of entries
		for ( $i = 1; $i <= $threshold; $i++ ) {
			$post_id = $this->factory()->post->create();
			$this->content_scan_table->insert_or_update( $post_id, $this->attachment_id, 'head' );
		}

		$count = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'head' );

		$this->assertEquals( $threshold, $count, "Should count exactly $threshold entries" );
	}

	/**
	 * Test counting above threshold (global image scenario)
	 */
	public function test_count_above_threshold() {
		$threshold = \ISC\Pro\Unused_Images\Content_Scan\Content_Scan::get_global_threshold(); // Default is 4

		// Add threshold + 1 entries (simulating capped global image)
		for ( $i = 1; $i <= $threshold + 1; $i++ ) {
			$post_id = $this->factory()->post->create();
			$this->content_scan_table->insert_or_update( $post_id, $this->attachment_id, 'head' );
		}

		$count = $this->content_scan_table->count_by_attachment_id_and_position( $this->attachment_id, 'head' );

		$this->assertEquals( $threshold + 1, $count, 'Should count all entries even above threshold' );
	}
}