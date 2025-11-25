<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer;

use ISC\Pro\Indexer\Indexer;
use ISC\Pro\Indexer\Index_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test the Indexer class
 *
 * @package ISC\Tests\WPUnit\Pro\Includes\Indexer
 */
class Indexer_Test extends WPTestCase {

	/**
	 * @var Indexer
	 */
	protected Indexer $indexer;

	/**
	 * @var Index_Table
	 */
	protected Index_Table $index_table;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->indexer = new Indexer();
		$this->index_table = new Index_Table();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		delete_post_meta_by_key( Indexer::LAST_INDEX_META_KEY );
	}

	/**
	 * Test that remove_post_index_meta removes the last index metadata
	 */
	public function test_remove_post_index_meta_deletes_metadata(): void {
		// Create a test post
		$post_id = $this->factory()->post->create();

		// Set the last index meta
		update_post_meta( $post_id, Indexer::LAST_INDEX_META_KEY, time() );

		// Verify meta exists
		$meta_before = get_post_meta( $post_id, Indexer::LAST_INDEX_META_KEY, true );
		$this->assertNotEmpty( $meta_before );

		// Call the method
		$result = $this->indexer->remove_post_index_meta( $post_id );

		// Verify meta was deleted
		$this->assertTrue( $result );
		$meta_after = get_post_meta( $post_id, Indexer::LAST_INDEX_META_KEY, true );
		$this->assertEmpty( $meta_after );
	}

	/**
	 * Test that is_indexer_expired returns true when oldest entry is too old
	 */
	public function test_is_indexer_expired_returns_true_when_expired(): void {
		// Create test data
		$post_id = $this->factory()->post->create();
		$attachment_id = $this->factory()->attachment->create();

		// Insert an entry with an old timestamp (8 days ago, beyond MAX_DAYS_SINCE_LAST_CHECK)
		$old_timestamp = time() - ( 8 * DAY_IN_SECONDS );
		$this->index_table->insert_or_update( $post_id, $attachment_id, 'content', $old_timestamp );

		// Reset cache to ensure fresh query
		Index_Table::reset_oldest_entry_date_cache();

		// Check if expired
		$is_expired = Indexer::is_indexer_expired();

		$this->assertTrue( $is_expired, 'Indexer should be expired when oldest entry is older than 7 days' );
	}

	/**
	 * Test that is_indexer_expired returns false when entries are recent
	 */
	public function test_is_indexer_expired_returns_false_when_not_expired(): void {
		// Create test data
		$post_id = $this->factory()->post->create();
		$attachment_id = $this->factory()->attachment->create();

		// Insert a recent entry (1 day ago)
		$this->index_table->insert_or_update( $post_id, $attachment_id, 'content' );

		// Reset cache to ensure fresh query
		Index_Table::reset_oldest_entry_date_cache();

		// Check if expired
		$is_expired = Indexer::is_indexer_expired();

		$this->assertFalse( $is_expired, 'Indexer should not be expired when entries are recent' );
	}

	/**
	 * Test that mark_post_as_indexed sets the correct metadata
	 */
	public function test_mark_post_as_indexed_sets_metadata(): void {
		// Create a test post
		$post_id = $this->factory()->post->create();

		// Mark as indexed
		$before_time = time();
		Indexer::mark_post_as_indexed( $post_id );
		$after_time = time();

		// Verify meta was set
		$last_index = get_post_meta( $post_id, Indexer::LAST_INDEX_META_KEY, true );
		$this->assertNotEmpty( $last_index );
		$this->assertGreaterThanOrEqual( $before_time, $last_index );
		$this->assertLessThanOrEqual( $after_time, $last_index );
	}

	/**
	 * Test that clear_all_index_data removes all index entries and post meta
	 */
	public function test_clear_all_index_data_removes_everything(): void {
		// Create test data
		$post_id = $this->factory()->post->create();
		$attachment_id = $this->factory()->attachment->create();
		update_post_meta( $post_id, Indexer::LAST_INDEX_META_KEY, time() );

		// Insert index entry
		$this->index_table->insert_or_update( $post_id, $attachment_id, 'content' );

		// Verify data exists
		global $wpdb;
		$table_name = $wpdb->prefix . 'isc_index';
		$entries_before = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		$this->assertGreaterThan( 0, $entries_before );
		$meta_before = get_post_meta( $post_id, Indexer::LAST_INDEX_META_KEY, true );
		$this->assertNotEmpty( $meta_before );

		// Clear all data
		Indexer::clear_all_index_data();

		// Verify everything was cleared
		$entries_after = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		$this->assertEquals( 0, $entries_after );
		$meta_after = get_post_meta( $post_id, Indexer::LAST_INDEX_META_KEY, true );
		$this->assertEmpty( $meta_after );
	}

	/**
	 * Test that is_index_any_url_enabled returns true when option is enabled
	 */
	public function test_is_index_any_url_enabled_returns_true_when_enabled(): void {
		update_option( 'isc_options', [
			'unused_images' => [
				'index_any_url' => true
			]
		] );

		$result = Indexer::is_index_any_url_enabled();

		$this->assertTrue( $result );
	}

	/**
	 * Test that is_index_any_url_enabled returns false when option is disabled
	 */
	public function test_is_index_any_url_enabled_returns_false_when_disabled(): void {
		update_option( 'isc_options', [
			'unused_images' => [
				'index_any_url' => false
			]
		] );

		$result = Indexer::is_index_any_url_enabled();

		$this->assertFalse( $result );
	}

	/**
	 * Test that is_index_any_url_enabled returns false when option doesn't exist
	 */
	public function test_is_index_any_url_enabled_returns_false_when_option_not_set(): void {
		delete_option( 'isc_options' );

		$result = Indexer::is_index_any_url_enabled();

		$this->assertFalse( $result );
	}

	/**
	 * Test get_global_threshold returns default value of 4
	 */
	public function test_get_global_threshold_returns_default_value(): void {
		$threshold = Indexer::get_global_threshold();

		$this->assertEquals( 4, $threshold, 'Default threshold should be 4' );
	}

	/**
	 * Test get_global_threshold respects filter
	 */
	public function test_get_global_threshold_respects_filter(): void {
		add_filter( 'isc_indexer_global_image_threshold', function() {
			return 10;
		} );

		$threshold = Indexer::get_global_threshold();

		$this->assertEquals( 10, $threshold, 'Should return filtered value' );

		remove_all_filters( 'isc_indexer_global_image_threshold' );
	}

	/**
	 * Test get_global_threshold enforces minimum value of 1
	 */
	public function test_get_global_threshold_enforces_minimum(): void {
		add_filter( 'isc_indexer_global_image_threshold', function() {
			return 0; // Try to set below minimum
		} );

		$threshold = Indexer::get_global_threshold();

		$this->assertEquals( 1, $threshold, 'Should enforce minimum of 1' );

		remove_all_filters( 'isc_indexer_global_image_threshold' );
	}

	/**
	 * Test get_global_threshold enforces maximum value of 100
	 */
	public function test_get_global_threshold_enforces_maximum(): void {
		add_filter( 'isc_indexer_global_image_threshold', function() {
			return 150; // Try to set above maximum
		} );

		$threshold = Indexer::get_global_threshold();

		$this->assertEquals( 100, $threshold, 'Should enforce maximum of 100' );

		remove_all_filters( 'isc_indexer_global_image_threshold' );
	}

	/**
	 * Test get_global_threshold converts non-integer values to int
	 */
	public function test_get_global_threshold_converts_to_int(): void {
		add_filter( 'isc_indexer_global_image_threshold', function() {
			return '25'; // String value
		} );

		$threshold = Indexer::get_global_threshold();

		$this->assertIsInt( $threshold, 'Should return an integer' );
		$this->assertEquals( 25, $threshold, 'Should convert string to int' );

		remove_all_filters( 'isc_indexer_global_image_threshold' );
	}

	/**
	 * Test get_global_threshold handles negative values
	 */
	public function test_get_global_threshold_handles_negative_values(): void {
		add_filter( 'isc_indexer_global_image_threshold', function() {
			return -5;
		} );

		$threshold = Indexer::get_global_threshold();

		$this->assertEquals( 1, $threshold, 'Should convert negative values to minimum of 1' );

		remove_all_filters( 'isc_indexer_global_image_threshold' );
	}
}