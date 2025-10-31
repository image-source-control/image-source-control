<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer;

use ISC\Pro\Indexer\Index_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing the Index_Table::get_by_attachment_id() method
 */
class Index_Table_Get_By_Attachment_Id_Test extends WPTestCase {

	/**
	 * @var Index_Table
	 */
	private Index_Table $index_table;

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

		$this->index_table = new Index_Table();

		// Create test post and attachment
		$this->post_id       = $this->factory()->post->create();
		$this->attachment_id = $this->factory()->post->create( [
			                                                       'post_type' => 'attachment',
		                                                       ] );
	}

	public function tearDown(): void {
		parent::tearDown();

		// Clear the table after each test
		$this->index_table->clear_all();

		// Reset the static cache
		Index_Table::reset_oldest_entry_date_cache();
	}

	/**
	 * Test retrieving entries by attachment_id
	 */
	public function test_get_by_attachment_id_returns_entries() {
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$entries = $this->index_table->get_by_attachment_id( $this->attachment_id );

		$this->assertIsArray( $entries );
		$this->assertArrayHasKey( $this->post_id, $entries );
		$this->assertEquals( $this->attachment_id, $entries[ $this->post_id ]['attachment_id'] );
	}

	/**
	 * Test retrieving entries with invalid attachment_id returns empty array
	 */
	public function test_get_by_attachment_id_with_invalid_id_returns_empty_array() {
		$entries = $this->index_table->get_by_attachment_id( 0 );

		$this->assertIsArray( $entries );
		$this->assertEmpty( $entries );
	}

	/**
	 * Test that get_by_attachment_id returns entries keyed by post_id with one entry per post
	 */
	public function test_get_by_attachment_id_keys_by_post_id() {
		// Create multiple posts
		$post_id_2 = $this->factory()->post->create();
		$post_id_3 = $this->factory()->post->create();

		// Insert entries for the same attachment across different posts
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$this->index_table->insert_or_update(
			$post_id_2,
			$this->attachment_id,
			'thumbnail'
		);

		$this->index_table->insert_or_update(
			$post_id_3,
			$this->attachment_id,
			'head'
		);

		$entries = $this->index_table->get_by_attachment_id( $this->attachment_id );

		// Verify the array is keyed by post_id
		$this->assertArrayHasKey( $this->post_id, $entries );
		$this->assertArrayHasKey( $post_id_2, $entries );
		$this->assertArrayHasKey( $post_id_3, $entries );

		// Verify we get exactly 3 entries (one per post)
		$this->assertCount( 3, $entries );

		// Verify each entry has the correct post_id
		$this->assertEquals( $this->post_id, $entries[ $this->post_id ]['post_id'] );
		$this->assertEquals( $post_id_2, $entries[ $post_id_2 ]['post_id'] );
		$this->assertEquals( $post_id_3, $entries[ $post_id_3 ]['post_id'] );

		// Verify all entries have the same attachment_id
		$this->assertEquals( $this->attachment_id, $entries[ $this->post_id ]['attachment_id'] );
		$this->assertEquals( $this->attachment_id, $entries[ $post_id_2 ]['attachment_id'] );
		$this->assertEquals( $this->attachment_id, $entries[ $post_id_3 ]['attachment_id'] );
	}

	/**
	 * Test that get_by_attachment_id returns only one entry per post_id when duplicates exist
	 * This documents the intentional behavior where the same attachment appearing with different
	 * positions on the same post will only return one entry (the last one processed by the database)
	 */
	public function test_get_by_attachment_id_with_duplicate_post_ids() {
		// Insert the same attachment on the same post with different positions
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'head'
		);

		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'body'
		);

		// Verify that 3 entries were actually created in the database
		$total_count = $this->index_table->count_occurrences_by_post_id( $this->post_id );
		$this->assertEquals( 3, $total_count, 'Should have 3 total entries in the database' );

		// Get entries using get_by_attachment_id
		$entries = $this->index_table->get_by_attachment_id( $this->attachment_id );

		// Should only return 1 entry (keyed by post_id, so duplicates are overwritten)
		$this->assertCount( 1, $entries, 'Should only return one entry per post_id, even with multiple positions' );

		// Verify it's keyed by the post_id
		$this->assertArrayHasKey( $this->post_id, $entries );

		// The position will be one of the three, but we can't guarantee which one
		// (depends on database ORDER BY behavior - ordered by post_id)
		$this->assertContains(
			$entries[ $this->post_id ]['position'],
			[ 'content', 'head', 'body' ],
			'The returned entry should have one of the positions we inserted'
		);

		// Verify the entry has the correct IDs
		$this->assertEquals( $this->post_id, $entries[ $this->post_id ]['post_id'] );
		$this->assertEquals( $this->attachment_id, $entries[ $this->post_id ]['attachment_id'] );
	}
}