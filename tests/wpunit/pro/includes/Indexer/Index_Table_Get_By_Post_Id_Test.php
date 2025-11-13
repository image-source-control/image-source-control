<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer;

use ISC\Pro\Indexer\Index_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing the Index_Table::get_by_post_id() method
 */
class Index_Table_Get_By_Post_Id_Test extends WPTestCase {

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
	}

	/**
	 * Test retrieving entries by post_id
	 */
	public function test_get_by_post_id_returns_entries() {
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$entries = $this->index_table->get_by_post_id( $this->post_id );

		$this->assertIsArray( $entries );
		$this->assertArrayHasKey( $this->attachment_id, $entries );
		$this->assertEquals( $this->post_id, $entries[ $this->attachment_id ]['post_id'] );
	}

	/**
	 * Test retrieving entries with invalid post_id returns empty array
	 */
	public function test_get_by_post_id_with_invalid_id_returns_empty_array() {
		$entries = $this->index_table->get_by_post_id( 0 );

		$this->assertIsArray( $entries );
		$this->assertEmpty( $entries );
	}

	/**
	 * Test that get_by_post_id returns entries keyed by attachment_id with one entry per attachment
	 */
	public function test_get_by_post_id_keys_by_attachment_id() {
		// Create multiple attachments
		$attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$attachment_id_3 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );

		// Insert entries for the same post with different attachments
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$this->index_table->insert_or_update(
			$this->post_id,
			$attachment_id_2,
			'thumbnail'
		);

		$this->index_table->insert_or_update(
			$this->post_id,
			$attachment_id_3,
			'content'
		);

		$entries = $this->index_table->get_by_post_id( $this->post_id );

		// Verify the array is keyed by attachment_id
		$this->assertArrayHasKey( $this->attachment_id, $entries );
		$this->assertArrayHasKey( $attachment_id_2, $entries );
		$this->assertArrayHasKey( $attachment_id_3, $entries );

		// Verify we get exactly 3 entries (one per attachment)
		$this->assertCount( 3, $entries );

		// Verify each entry has the correct attachment_id
		$this->assertEquals( $this->attachment_id, $entries[ $this->attachment_id ]['attachment_id'] );
		$this->assertEquals( $attachment_id_2, $entries[ $attachment_id_2 ]['attachment_id'] );
		$this->assertEquals( $attachment_id_3, $entries[ $attachment_id_3 ]['attachment_id'] );
	}

	/**
	 * Test that get_by_post_id returns only one entry per attachment_id when duplicates exist
	 * This documents the intentional behavior where the same attachment with different positions
	 * will only return one entry (the last one processed by the database)
	 */
	public function test_get_by_post_id_with_duplicate_attachment_ids() {
		// Insert the same attachment with different positions
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

		// Get entries using get_by_post_id
		$entries = $this->index_table->get_by_post_id( $this->post_id );

		// Should only return 1 entry (keyed by attachment_id, so duplicates are overwritten)
		$this->assertCount( 1, $entries, 'Should only return one entry per attachment_id, even with multiple positions' );

		// Verify it's keyed by the attachment_id
		$this->assertArrayHasKey( $this->attachment_id, $entries );

		// The position will be one of the three, but we can't guarantee which one
		// (depends on database ORDER BY behavior)
		$this->assertContains(
			$entries[ $this->attachment_id ]['position'],
			[ 'content', 'head', 'body' ],
			'The returned entry should have one of the positions we inserted'
		);

		// Verify the entry has the correct IDs
		$this->assertEquals( $this->post_id, $entries[ $this->attachment_id ]['post_id'] );
		$this->assertEquals( $this->attachment_id, $entries[ $this->attachment_id ]['attachment_id'] );
	}
}