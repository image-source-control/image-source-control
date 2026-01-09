<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Content_Scan;

use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing the Index_Table::bulk_update_by_post_id() method
 */
class Content_Scan_Table_Bulk_Update_By_Post_Id_Test extends WPTestCase {

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
	private $attachment_id_1;

	/**
	 * @var int
	 */
	private $attachment_id_2;

	/**
	 * @var int
	 */
	private $attachment_id_3;

	public function setUp(): void {
		parent::setUp();

		$this->content_scan_table = new Content_Scan_Table();

		// Create test post and attachments
		$this->post_id         = $this->factory()->post->create();
		$this->attachment_id_1 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->attachment_id_3 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test basic bulk update functionality - adding new entries
	 */
	public function test_bulk_update_adds_new_entries() {
		$entries = [
			[ 'attachment_id' => $this->attachment_id_1, 'position' => 'content' ],
			[ 'attachment_id' => $this->attachment_id_2, 'position' => 'content' ],
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries, 'content' );

		$this->assertIsArray( $result );
		$this->assertEquals( 2, $result['added'], 'Should add 2 new entries' );
		$this->assertEquals( 0, $result['updated'], 'Should update 0 entries' );
		$this->assertEquals( 0, $result['deleted'], 'Should delete 0 entries' );
		$this->assertEquals( 2, $result['total'], 'Total should be 2' );

		// Verify entries exist in database
		$stored_entries = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 2, $stored_entries );
	}

	/**
	 * Test updating existing entries (refreshing timestamps)
	 */
	public function test_bulk_update_refreshes_existing_entries() {
		// Insert initial entries
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_1, 'content' );
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_2, 'content' );

		// Wait to ensure timestamp difference
		sleep( 1 );

		// Update with same entries
		$entries = [
			[ 'attachment_id' => $this->attachment_id_1, 'position' => 'content' ],
			[ 'attachment_id' => $this->attachment_id_2, 'position' => 'content' ],
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries, 'content' );

		$this->assertEquals( 0, $result['added'], 'Should add 0 new entries' );
		$this->assertEquals( 2, $result['updated'], 'Should update 2 existing entries' );
		$this->assertEquals( 0, $result['deleted'], 'Should delete 0 entries' );
	}

	/**
	 * Test deleting entries that are no longer in the content
	 */
	public function test_bulk_update_deletes_removed_entries() {
		// Insert initial entries
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_1, 'content' );
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_2, 'content' );
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_3, 'content' );

		// Update with only attachment_id_1 (remove 2 and 3)
		$entries = [
			[ 'attachment_id' => $this->attachment_id_1, 'position' => 'content' ],
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries, 'content' );

		$this->assertEquals( 0, $result['added'], 'Should add 0 entries' );
		$this->assertEquals( 1, $result['updated'], 'Should update 1 entry' );
		$this->assertEquals( 2, $result['deleted'], 'Should delete 2 entries' );

		// Verify only attachment_id_1 remains
		$stored_entries = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 1, $stored_entries );
		$this->assertArrayHasKey( $this->attachment_id_1, $stored_entries );
	}

	/**
	 * Test mixed operation: add, update, and delete in one call
	 */
	public function test_bulk_update_mixed_operations() {
		// Insert initial entries (attachment 1 and 2)
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_1, 'content' );
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_2, 'content' );

		sleep( 1 );

		// Update: keep 1, remove 2, add 3
		$entries = [
			[ 'attachment_id' => $this->attachment_id_1, 'position' => 'content' ], // update
			[ 'attachment_id' => $this->attachment_id_3, 'position' => 'content' ], // add
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries, 'content' );

		$this->assertEquals( 1, $result['added'], 'Should add 1 entry (attachment 3)' );
		$this->assertEquals( 1, $result['updated'], 'Should update 1 entry (attachment 1)' );
		$this->assertEquals( 1, $result['deleted'], 'Should delete 1 entry (attachment 2)' );
		$this->assertEquals( 3, $result['total'], 'Total should be 3' );

		// Verify correct entries remain
		$stored_entries = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 2, $stored_entries );
		$this->assertArrayHasKey( $this->attachment_id_1, $stored_entries );
		$this->assertArrayHasKey( $this->attachment_id_3, $stored_entries );
		$this->assertArrayNotHasKey( $this->attachment_id_2, $stored_entries );
	}

	/**
	 * Test that position filter only affects entries with that position
	 */
	public function test_bulk_update_respects_position_filter() {
		// Insert entries with different positions
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_1, 'content' );
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_2, 'thumbnail' );
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_3, 'head' );

		// Update only 'content' position with new attachment
		$attachment_id_4 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$entries         = [
			[ 'attachment_id' => $attachment_id_4, 'position' => 'content' ],
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries, 'content' );

		// Should delete attachment_id_1 (content), but not 2 (thumbnail) or 3 (head)
		$this->assertEquals( 1, $result['added'], 'Should add new content entry' );
		$this->assertEquals( 0, $result['updated'], 'Should update 0 entries' );
		$this->assertEquals( 1, $result['deleted'], 'Should delete only content entry' );

		// Verify all entries
		$all_entries = $this->content_scan_table->count_occurrences_by_post_id( $this->post_id );
		$this->assertEquals( 3, $all_entries, 'Should have 3 total entries (1 content + 1 thumbnail + 1 head)' );
	}

	/**
	 * Test updating with empty array deletes all entries for that position
	 */
	public function test_bulk_update_with_empty_array_deletes_all() {
		// Insert entries
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_1, 'content' );
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_2, 'content' );
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_3, 'thumbnail' );

		// Update with empty array for 'content' position
		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, [], 'content' );

		$this->assertEquals( 0, $result['added'] );
		$this->assertEquals( 0, $result['updated'] );
		$this->assertEquals( 2, $result['deleted'], 'Should delete all content entries' );

		// Verify only thumbnail remains
		$all_entries = $this->content_scan_table->count_occurrences_by_post_id( $this->post_id );
		$this->assertEquals( 1, $all_entries, 'Should only have thumbnail entry' );
	}

	/**
	 * Test with invalid post_id returns error
	 */
	public function test_bulk_update_with_invalid_post_id() {
		$entries = [
			[ 'attachment_id' => $this->attachment_id_1, 'position' => 'content' ],
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( 0, $entries );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertEquals( 'Invalid post ID', $result['error'] );
	}

	/**
	 * Test with invalid position in filter falls back to no position filter
	 */
	public function test_bulk_update_with_invalid_position_filter() {
		// Insert entries with different positions
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_1, 'content' );
		$this->content_scan_table->insert_or_update( $this->post_id, $this->attachment_id_2, 'thumbnail' );

		// Try to update with invalid position filter - should affect all positions
		$entries = [
			[ 'attachment_id' => $this->attachment_id_3, 'position' => 'content' ],
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries, 'invalid_position' );

		// With no valid position filter, it should delete all existing entries
		$this->assertEquals( 1, $result['added'] );
		$this->assertEquals( 2, $result['deleted'], 'Should delete both entries when position filter is invalid' );
	}

	/**
	 * Test handling of entries with invalid attachment_id (should be skipped)
	 */
	/**
	 * Test handling of entries with invalid attachment_id (should be skipped)
	 */
	public function test_bulk_update_skips_invalid_attachment_ids() {
		$entries = [
			[ 'attachment_id' => $this->attachment_id_1, 'position' => 'content' ], // valid
			[ 'attachment_id' => 0, 'position' => 'content' ], // invalid - should skip
			[ 'attachment_id' => -1, 'position' => 'content' ], // invalid - absint(-1) = 1, but still should be validated
			[ 'attachment_id' => $this->attachment_id_2, 'position' => 'content' ], // valid
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries, 'content' );

		// Should only add the 2 valid entries (attachment_id_1 and attachment_id_2)
		$this->assertEquals( 2, $result['added'], 'Should add only valid attachment IDs' );

		$stored_entries = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 2, $stored_entries );
		$this->assertArrayHasKey( $this->attachment_id_1, $stored_entries );
		$this->assertArrayHasKey( $this->attachment_id_2, $stored_entries );
	}

	/**
	 * Test handling of entries missing attachment_id (should be skipped)
	 */
	public function test_bulk_update_skips_entries_without_attachment_id() {
		$entries = [
			[ 'attachment_id' => $this->attachment_id_1, 'position' => 'content' ], // valid
			[ 'position' => 'content' ], // missing attachment_id - should skip
			[ 'attachment_id' => $this->attachment_id_2, 'position' => 'content' ], // valid
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries, 'content' );

		// Should only add the 2 valid entries
		$this->assertEquals( 2, $result['added'] );

		$stored_entries = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 2, $stored_entries );
	}

	/**
	 * Test that entries use default position if not specified
	 */
	public function test_bulk_update_uses_default_position_when_not_specified() {
		$entries = [
			[ 'attachment_id' => $this->attachment_id_1 ], // no position specified
		];

		$result = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries );

		$this->assertEquals( 1, $result['added'] );

		$stored_entries = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertEquals( 'content', $stored_entries[ $this->attachment_id_1 ]['position'], 'Should use default position' );
	}

	/**
	 * Test real-world scenario: content update cycle
	 * Simulates what happens when a post is viewed multiple times with changing content
	 */
	public function test_bulk_update_realistic_content_update_cycle() {
		// First view: post has 2 images
		$entries_v1 = [
			[ 'attachment_id' => $this->attachment_id_1, 'position' => 'content' ],
			[ 'attachment_id' => $this->attachment_id_2, 'position' => 'content' ],
		];
		$result_v1 = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries_v1, 'content' );
		$this->assertEquals( 2, $result_v1['added'], 'First view: should add 2 images' );
		$this->assertEquals( 0, $result_v1['updated'], 'First view: should update 0 images' );
		$this->assertEquals( 0, $result_v1['deleted'], 'First view: should delete 0 images' );

		// Second view: user edited post, kept image 1, removed image 2, added image 3
		$entries_v2 = [
			[ 'attachment_id' => $this->attachment_id_1, 'position' => 'content' ],
			[ 'attachment_id' => $this->attachment_id_3, 'position' => 'content' ],
		];
		$result_v2 = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, $entries_v2, 'content' );

		$this->assertEquals( 1, $result_v2['added'], 'Second view: should add 1 image (attachment_3). Got: ' . $result_v2['added'] );
		$this->assertEquals( 1, $result_v2['updated'], 'Second view: should update 1 image (attachment_1). Got: ' . $result_v2['updated'] );
		$this->assertEquals( 1, $result_v2['deleted'], 'Second view: should delete 1 image (attachment_2). Got: ' . $result_v2['deleted'] );

		// Verify correct state
		$stored_entries = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertCount( 2, $stored_entries, 'Should have 2 entries after second view' );
		$this->assertArrayHasKey( $this->attachment_id_1, $stored_entries );
		$this->assertArrayHasKey( $this->attachment_id_3, $stored_entries );

		// Third view: all images removed
		$result_v3 = $this->content_scan_table->bulk_update_by_post_id( $this->post_id, [], 'content' );
		$this->assertEquals( 0, $result_v3['added'] );
		$this->assertEquals( 0, $result_v3['updated'] );
		$this->assertEquals( 2, $result_v3['deleted'], 'Third view: should delete all 2 remaining images' );

		// Verify table is clean
		$stored_entries = $this->content_scan_table->get_by_post_id( $this->post_id );
		$this->assertEmpty( $stored_entries, 'Should have no entries after third view' );
	}
}