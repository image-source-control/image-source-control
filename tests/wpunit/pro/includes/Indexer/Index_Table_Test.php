<?php

namespace ISC\Tests\WPUnit\Includes\Indexer;

use ISC\Pro\Indexer\Index_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing the ISC\Pro\Indexer\Index_Table class
 */
class Index_Table_Test extends WPTestCase {

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
	 * Test that the table is created on instantiation
	 */
	public function test_table_exists_after_instantiation() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'isc_index';

		$table = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		$this->assertEquals( $table_name, $table );
	}

	/**
	 * Test inserting a new entry
	 */
	public function test_insert_or_update_creates_new_entry() {
		$result = $this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$this->assertNotFalse( $result );
		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );
	}

	/**
	 * Test updating an existing entry
	 */
	/**
	 * Test updating an existing entry with new timestamp
	 */
	public function test_insert_or_update_updates_existing_entry() {
		// Insert initial entry
		$first_id = $this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		// Get the initial last_checked timestamp
		$entries = $this->index_table->get_by_post_id( $this->post_id );
		$initial_last_checked = $entries[ $this->attachment_id ]['last_checked'];

		// Wait a moment to ensure timestamp difference
		sleep( 1 );

		// Update the same entry (should update last_checked)
		$second_id = $this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		// Get the updated entry
		$updated_entries = $this->index_table->get_by_post_id( $this->post_id );
		$updated_last_checked = $updated_entries[ $this->attachment_id ]['last_checked'];

		// Verify it's the same entry (same ID)
		$this->assertEquals( $first_id, $second_id, 'Should return same ID when updating existing entry' );

		// Verify the last_checked timestamp was updated
		$this->assertNotEquals( $initial_last_checked, $updated_last_checked, 'last_checked should be updated' );
		$this->assertGreaterThan( strtotime( $initial_last_checked ), strtotime( $updated_last_checked ), 'Updated timestamp should be later than initial timestamp' );

		// Verify only one entry exists (no duplicate was created)
		$this->assertCount( 1, $updated_entries, 'Should only have one entry, not create a duplicate' );
	}

	/**
	 * Test inserting with invalid post_id returns false
	 */
	public function test_insert_with_invalid_post_id_returns_false() {
		$result = $this->index_table->insert_or_update(
			0,
			$this->attachment_id,
			'content'
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test inserting with invalid attachment_id returns false
	 */
	public function test_insert_with_invalid_attachment_id_returns_false() {
		$result = $this->index_table->insert_or_update(
			$this->post_id,
			0,
			'content'
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test inserting with invalid position uses default
	 */
	public function test_insert_with_invalid_position_uses_default() {
		$result = $this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'invalid_position'
		);

		$this->assertNotFalse( $result );

		$entries = $this->index_table->get_by_post_id( $this->post_id );
		$this->assertEquals( 'content', $entries[ $this->attachment_id ]['position'] );
	}

	/**
	 * Test deleting entries by post_id
	 */
	public function test_delete_by_post_id() {
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$result = $this->index_table->delete_by_post_id( $this->post_id );

		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );

		$entries = $this->index_table->get_by_post_id( $this->post_id );
		$this->assertEmpty( $entries );
	}

	/**
	 * Test deleting entries by post_id with specific position
	 */
	public function test_delete_by_post_id_with_position() {
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->index_table->insert_or_update(
			$this->post_id,
			$attachment_id_2,
			'thumbnail'
		);

		$result = $this->index_table->delete_by_post_id( $this->post_id, 'content' );

		$this->assertEquals( 1, $result );

		$entries = $this->index_table->get_by_post_id( $this->post_id );
		$this->assertCount( 1, $entries );
		$this->assertEquals( 'thumbnail', $entries[ $attachment_id_2 ]['position'] );
	}

	/**
	 * Test deleting entries by attachment_id
	 */
	public function test_delete_by_attachment_id() {
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$result = $this->index_table->delete_by_attachment_id( $this->attachment_id );

		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );

		$entries = $this->index_table->get_by_attachment_id( $this->attachment_id );
		$this->assertEmpty( $entries );
	}

	/**
	 * Test bulk insert or update
	 */
	public function test_bulk_insert_or_update() {
		$attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );

		$entries = [
			[
				'post_id'       => $this->post_id,
				'attachment_id' => $this->attachment_id,
				'position'      => 'content',
			],
			[
				'post_id'       => $this->post_id,
				'attachment_id' => $attachment_id_2,
				'position'      => 'thumbnail',
			],
		];

		$result = $this->index_table->bulk_insert_or_update( $entries );

		$this->assertIsArray( $result );
		$this->assertEquals( 2, $result['count'] );
		$this->assertCount( 2, $result['results'] );
	}

	/**
	 * Test counting occurrences by post_id
	 */
	public function test_count_occurrences_by_post_id() {
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->index_table->insert_or_update(
			$this->post_id,
			$attachment_id_2,
			'thumbnail'
		);

		$count = $this->index_table->count_occurrences_by_post_id( $this->post_id );

		$this->assertEquals( 2, $count );
	}

	/**
	 * Test get_global_stats_since
	 */
	public function test_get_global_stats_since() {
		$timestamp = time() - 3600; // 1 hour ago

		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$stats = $this->index_table->get_global_stats_since( $timestamp );

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'unique_images', $stats );
		$this->assertArrayHasKey( 'total_occurrences', $stats );
		$this->assertEquals( 1, $stats['unique_images'] );
		$this->assertEquals( 1, $stats['total_occurrences'] );
	}

	/**
	 * Test finding orphaned entries
	 */
	public function test_find_orphaned_entries() {
		// Insert valid entry
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		// Insert entry with non-existent post_id
		global $wpdb;
		$table_name = $wpdb->prefix . 'isc_index';
		$wpdb->insert(
			$table_name,
			[
				'post_id'       => 999999,
				'attachment_id' => $this->attachment_id,
				'position'      => 'content',
			],
			[ '%d', '%d', '%s' ]
		);

		$orphaned = $this->index_table->find_orphaned_entries();

		$this->assertIsArray( $orphaned );
		$this->assertCount( 1, $orphaned );
	}

	/**
	 * Test deleting orphaned entries
	 */
	public function test_delete_orphaned_entries() {
		// Insert entry with non-existent post_id
		global $wpdb;
		$table_name = $wpdb->prefix . 'isc_index';
		$wpdb->insert(
			$table_name,
			[
				'post_id'       => 999999,
				'attachment_id' => $this->attachment_id,
				'position'      => 'content',
			],
			[ '%d', '%d', '%s' ]
		);

		$deleted = $this->index_table->delete_orphaned_entries();

		$this->assertGreaterThan( 0, $deleted );
	}

	/**
	 * Test deleting invalid entries
	 */
	public function test_delete_invalid_entries() {
		// Insert entry with invalid IDs directly
		global $wpdb;
		$table_name = $wpdb->prefix . 'isc_index';
		$wpdb->insert(
			$table_name,
			[
				'post_id'       => 0,
				'attachment_id' => $this->attachment_id,
				'position'      => 'content',
			],
			[ '%d', '%d', '%s' ]
		);

		$deleted = $this->index_table->delete_invalid_entries();

		$this->assertGreaterThan( 0, $deleted );
	}

	/**
	 * Test clearing all entries
	 */
	public function test_clear_all() {
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		$result = $this->index_table->clear_all();

		$this->assertTrue( $result );

		$entries = $this->index_table->get_by_post_id( $this->post_id );
		$this->assertEmpty( $entries );
	}

	/**
	 * Test is_valid_position
	 */
	public function test_is_valid_position() {
		$this->assertTrue( $this->index_table->is_valid_position( 'content' ) );
		$this->assertTrue( $this->index_table->is_valid_position( 'thumbnail' ) );
		$this->assertTrue( $this->index_table->is_valid_position( 'head' ) );
		$this->assertTrue( $this->index_table->is_valid_position( 'body' ) );
		$this->assertFalse( $this->index_table->is_valid_position( 'invalid' ) );
	}

	/**
	 * Test get_default_position
	 */
	public function test_get_default_position() {
		$this->assertEquals( 'content', $this->index_table->get_default_position() );
	}

	/**
	 * Test update_last_checked
	 */
	public function test_update_last_checked() {
		$this->index_table->insert_or_update(
			$this->post_id,
			$this->attachment_id,
			'content'
		);

		// Wait a moment to ensure timestamp difference
		sleep( 1 );

		$result = $this->index_table->update_last_checked(
			$this->post_id,
			$this->attachment_id
		);

		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );
	}
}