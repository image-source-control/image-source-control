<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer;

use ISC\Pro\Indexer\Index_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing the Index_Table::get_oldest_entry_date() and get_oldest_entry_date_by_post_id() methods
 */
class Index_Table_Get_Oldest_Entry_Date_Test extends WPTestCase {

	/**
	 * @var Index_Table
	 */
	private Index_Table $index_table;

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

		$this->index_table = new Index_Table();

		// Create test posts and attachments
		$this->post_id_1       = $this->factory()->post->create();
		$this->post_id_2       = $this->factory()->post->create();
		$this->attachment_id_1 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->attachment_id_3 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
	}

	public function tearDown(): void {
		parent::tearDown();

		// Clear the table after each test
		$this->index_table->clear_all();

		// Reset the static cache
		Index_Table::reset_oldest_entry_date_cache();
	}

	/**
	 * Test get_oldest_entry_date returns null when table is empty
	 */
	public function test_get_oldest_entry_date_returns_null_when_empty() {
		$timestamp = $this->index_table->get_oldest_entry_date();
		$string    = $this->index_table->get_oldest_entry_date( 'string' );

		$this->assertNull( $timestamp, 'Should return null timestamp for empty table' );
		$this->assertNull( $string, 'Should return null string for empty table' );
	}

	/**
	 * Test get_oldest_entry_date returns timestamp by default
	 */
	public function test_get_oldest_entry_date_returns_timestamp_by_default() {
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content'
		);

		$result = $this->index_table->get_oldest_entry_date();

		$this->assertIsInt( $result, 'Should return integer timestamp by default' );
		$this->assertGreaterThan( 0, $result, 'Timestamp should be positive' );
	}

	/**
	 * Test get_oldest_entry_date returns string when requested
	 */
	public function test_get_oldest_entry_date_returns_string_when_requested() {
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content'
		);

		$result = $this->index_table->get_oldest_entry_date( 'string' );

		$this->assertIsString( $result, 'Should return string when requested' );
		$this->assertNotEmpty( $result, 'String should not be empty' );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result, 'Should match Y-m-d H:i:s format' );
	}

	/**
	 * Test get_oldest_entry_date finds the oldest among multiple entries
	 */
	public function test_get_oldest_entry_date_finds_oldest_among_multiple() {
		$current_time = time();

		// Insert entries with different timestamps
		// Oldest entry (3 hours ago)
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content',
			$this->index_table->get_last_checked( $current_time - 10800 )
		);

		// Middle entry (2 hours ago)
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_2,
			'content',
			$this->index_table->get_last_checked( $current_time - 7200 )
		);

		// Newest entry (1 hour ago)
		$this->index_table->insert_or_update(
			$this->post_id_2,
			$this->attachment_id_3,
			'content',
			$this->index_table->get_last_checked( $current_time - 3600 )
		);

		$oldest_timestamp = $this->index_table->get_oldest_entry_date();

		// Should return the oldest (3 hours ago)
		$expected = $current_time - 10800;
		$this->assertEqualsWithDelta( $expected, $oldest_timestamp, 2, 'Should return the oldest timestamp' );
	}

	/**
	 * Test get_oldest_entry_date caching behavior
	 */
	public function test_get_oldest_entry_date_caching() {
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content'
		);

		// First call - should query database
		$first_call = $this->index_table->get_oldest_entry_date();

		// Second call - should use cache
		$second_call = $this->index_table->get_oldest_entry_date();

		$this->assertEquals( $first_call, $second_call, 'Cached result should match first result' );

		// Add a new older entry
		$this->index_table->insert_or_update(
			$this->post_id_2,
			$this->attachment_id_2,
			'content',
			$this->index_table->get_last_checked( time() - 7200 )
		);

		// Third call - should still return cached value (not the new older entry)
		$third_call = $this->index_table->get_oldest_entry_date();

		$this->assertEquals( $first_call, $third_call, 'Should continue using cached value' );
	}

	/**
	 * Test cache is reset after clear_all()
	 */
	public function test_cache_is_reset_after_clear_all() {
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content'
		);

		$before_clear = $this->index_table->get_oldest_entry_date();
		$this->assertIsInt( $before_clear );

		// Clear all entries
		$this->index_table->clear_all();

		// Cache should be reset, should return null for empty table
		$after_clear = $this->index_table->get_oldest_entry_date();
		$this->assertNull( $after_clear, 'Should return null after clear_all()' );
	}

	/**
	 * Test cache is reset manually with reset_oldest_entry_date_cache()
	 */
	public function test_manual_cache_reset() {
		$current_time = time();

		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content',
			$this->index_table->get_last_checked( $current_time - 3600 )
		);

		$first_call = $this->index_table->get_oldest_entry_date();

		// Add older entry
		$this->index_table->insert_or_update(
			$this->post_id_2,
			$this->attachment_id_2,
			'content',
			$this->index_table->get_last_checked( $current_time - 7200 )
		);

		// Still cached
		$second_call = $this->index_table->get_oldest_entry_date();
		$this->assertEquals( $first_call, $second_call );

		// Reset cache manually
		Index_Table::reset_oldest_entry_date_cache();

		// Should now return the new older timestamp
		$third_call = $this->index_table->get_oldest_entry_date();
		$expected   = $current_time - 7200;
		$this->assertEqualsWithDelta( $expected, $third_call, 2, 'Should return new oldest after cache reset' );
	}

	/**
	 * Test string and timestamp return types are consistent
	 */
	public function test_string_and_timestamp_return_types_are_consistent() {
		$current_time = time();

		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content',
			$this->index_table->get_last_checked( $current_time - 3600 )
		);

		$timestamp = $this->index_table->get_oldest_entry_date();
		$string    = $this->index_table->get_oldest_entry_date( 'string' );

		// Convert string back to timestamp
		$timestamp_from_string = strtotime( $string );

		$this->assertEqualsWithDelta( $timestamp, $timestamp_from_string, 1, 'String and timestamp should represent the same time' );
	}

	/**
	 * Test get_oldest_entry_date_by_post_id returns null when table is empty
	 */
	public function test_get_oldest_entry_date_by_post_id_returns_null_when_empty() {
		$result = $this->index_table->get_oldest_entry_date_by_post_id( $this->post_id_1 );

		$this->assertNull( $result, 'Should return null for post with no entries' );
	}

	/**
	 * Test get_oldest_entry_date_by_post_id with invalid post ID
	 */
	public function test_get_oldest_entry_date_by_post_id_with_invalid_id() {
		$result_zero     = $this->index_table->get_oldest_entry_date_by_post_id( 0 );
		$result_negative = $this->index_table->get_oldest_entry_date_by_post_id( - 1 );

		$this->assertNull( $result_zero, 'Should return null for post_id = 0' );
		$this->assertNull( $result_negative, 'Should return null for negative post_id' );
	}

	/**
	 * Test get_oldest_entry_date_by_post_id returns timestamp by default
	 */
	public function test_get_oldest_entry_date_by_post_id_returns_timestamp() {
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content'
		);

		$result = $this->index_table->get_oldest_entry_date_by_post_id( $this->post_id_1 );

		$this->assertIsInt( $result, 'Should return integer timestamp by default' );
		$this->assertGreaterThan( 0, $result, 'Timestamp should be positive' );
	}

	/**
	 * Test get_oldest_entry_date_by_post_id returns string when requested
	 */
	public function test_get_oldest_entry_date_by_post_id_returns_string() {
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content'
		);

		$result = $this->index_table->get_oldest_entry_date_by_post_id( $this->post_id_1, 'string' );

		$this->assertIsString( $result, 'Should return string when requested' );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result, 'Should match Y-m-d H:i:s format' );
	}

	/**
	 * Test get_oldest_entry_date_by_post_id finds oldest for specific post
	 */
	public function test_get_oldest_entry_date_by_post_id_finds_oldest_for_post() {
		$current_time = time();

		// Post 1: oldest entry (3 hours ago)
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content',
			$this->index_table->get_last_checked( $current_time - 10800 )
		);

		// Post 1: newer entry (1 hour ago)
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_2,
			'content',
			$this->index_table->get_last_checked( $current_time - 3600 )
		);

		// Post 2: entry (2 hours ago)
		$this->index_table->insert_or_update(
			$this->post_id_2,
			$this->attachment_id_3,
			'content',
			$this->index_table->get_last_checked( $current_time - 7200 )
		);

		$oldest_post_1 = $this->index_table->get_oldest_entry_date_by_post_id( $this->post_id_1 );
		$oldest_post_2 = $this->index_table->get_oldest_entry_date_by_post_id( $this->post_id_2 );

		// Post 1 should return 3 hours ago (oldest of its two entries)
		$this->assertEqualsWithDelta( $current_time - 10800, $oldest_post_1, 2, 'Should return oldest for post 1' );

		// Post 2 should return 2 hours ago (its only entry)
		$this->assertEqualsWithDelta( $current_time - 7200, $oldest_post_2, 2, 'Should return oldest for post 2' );
	}

	/**
	 * Test get_oldest_entry_date_by_post_id is NOT cached (unlike get_oldest_entry_date)
	 */
	public function test_get_oldest_entry_date_by_post_id_is_not_cached() {
		$current_time = time();

		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content',
			$this->index_table->get_last_checked( $current_time - 3600 )
		);

		$first_call = $this->index_table->get_oldest_entry_date_by_post_id( $this->post_id_1 );

		// Add older entry
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_2,
			'content',
			$this->index_table->get_last_checked( $current_time - 7200 )
		);

		// Should immediately return the new older timestamp (no caching)
		$second_call = $this->index_table->get_oldest_entry_date_by_post_id( $this->post_id_1 );

		$this->assertNotEquals( $first_call, $second_call, 'Should not cache results' );
		$this->assertEqualsWithDelta( $current_time - 7200, $second_call, 2, 'Should return new oldest immediately' );
	}

	/**
	 * Test get_oldest_entry_date_by_post_id only considers entries for that specific post
	 */
	public function test_get_oldest_entry_date_by_post_id_isolates_by_post() {
		$current_time = time();

		// Very old entry for post 2
		$this->index_table->insert_or_update(
			$this->post_id_2,
			$this->attachment_id_1,
			'content',
			$this->index_table->get_last_checked( $current_time - 86400 ) // 24 hours ago
		);

		// Recent entry for post 1
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_2,
			'content',
			$this->index_table->get_last_checked( $current_time - 3600 ) // 1 hour ago
		);

		$oldest_post_1 = $this->index_table->get_oldest_entry_date_by_post_id( $this->post_id_1 );

		// Should only return post 1's entry (1 hour ago), not post 2's older entry
		$this->assertEqualsWithDelta( $current_time - 3600, $oldest_post_1, 2, 'Should only consider entries for the specific post' );
	}
}