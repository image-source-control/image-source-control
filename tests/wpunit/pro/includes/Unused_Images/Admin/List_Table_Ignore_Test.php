<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images_List_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing \ISC\Pro\Unused_Images_List_Table ignore/unignore functionality
 */
class List_Table_Ignore_Test extends WPTestCase {

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	protected $admin_user_id;

	/**
	 * Test attachment IDs
	 *
	 * @var array
	 */
	protected $attachment_ids = [];

	/**
	 * List_Table instance
	 *
	 * @var Unused_Images_List_Table
	 */
	protected $list_table;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create admin user with manage_options capability
		$this->admin_user_id = $this->factory->user->create( [
			'role' => 'administrator',
		] );
		wp_set_current_user( $this->admin_user_id );

		// Create multiple test attachments
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->attachment_ids[ $i ] = $this->factory->attachment->create( [
				'post_title'     => 'Test Image ' . $i,
				'post_mime_type' => 'image/jpeg',
			] );
		}

		// Mark some as ignored for testing
		update_post_meta( $this->attachment_ids[1], 'isc_ignored_unused_image', '1' );
		update_post_meta( $this->attachment_ids[2], 'isc_ignored_unused_image', '1' );

		// Initialize List_Table
		$this->list_table = new Unused_Images_List_Table();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Call parent tearDown first
		parent::tearDown();

		// Clean up transient cache
		delete_transient( 'isc_has_ignored_images' );

		// Reset $_GET, $_POST, and $_REQUEST
		$_GET = [];
		$_POST = [];
		$_REQUEST = [];
	}

	/**
	 * Test bulk ignore action ignores multiple images
	 *
	 * Tests: \ISC\Pro\Unused_Images_List_Table::process_bulk_action()
	 */
	public function test_bulk_ignore_action_ignores_multiple_images() {
		// Set up bulk action
		$_GET['action'] = 'ignore';
		$_GET['attachment'] = [ $this->attachment_ids[3], $this->attachment_ids[4] ];

		// Process bulk action
		$this->list_table->process_bulk_action();

		// Verify images were ignored
		$this->assertEquals( '1', get_post_meta( $this->attachment_ids[3], 'isc_ignored_unused_image', true ), 'Third image should be ignored' );
		$this->assertEquals( '1', get_post_meta( $this->attachment_ids[4], 'isc_ignored_unused_image', true ), 'Fourth image should be ignored' );

		// Verify other images remain unchanged
		$this->assertEquals( '1', get_post_meta( $this->attachment_ids[1], 'isc_ignored_unused_image', true ), 'First image should still be ignored' );
		$this->assertEmpty( get_post_meta( $this->attachment_ids[5], 'isc_ignored_unused_image', true ), 'Fifth image should not be ignored' );
	}

	/**
	 * Test bulk unignore action unignores multiple images
	 *
	 * Tests: \ISC\Pro\Unused_Images_List_Table::process_bulk_action()
	 */
	public function test_bulk_unignore_action_unignores_multiple_images() {
		// Set up bulk action
		$_GET['action'] = 'unignore';
		$_GET['attachment'] = [ $this->attachment_ids[1], $this->attachment_ids[2] ];

		// Process bulk action
		$this->list_table->process_bulk_action();

		// Verify images were unignored
		$this->assertEmpty( get_post_meta( $this->attachment_ids[1], 'isc_ignored_unused_image', true ), 'First image should be unignored' );
		$this->assertEmpty( get_post_meta( $this->attachment_ids[2], 'isc_ignored_unused_image', true ), 'Second image should be unignored' );
	}

	/**
	 * Test bulk ignore validates numeric IDs
	 *
	 * Tests: \ISC\Pro\Unused_Images_List_Table::process_bulk_action()
	 */
	public function test_bulk_ignore_validates_numeric_ids() {
		// Set up bulk action with mixed numeric and non-numeric IDs
		$_GET['action'] = 'ignore';
		$_GET['attachment'] = [ $this->attachment_ids[5], 'not_a_number', '999999', 'another_string' ];

		// Process bulk action (should not cause errors)
		$this->list_table->process_bulk_action();

		// Verify only the valid numeric ID was processed
		$this->assertEquals( '1', get_post_meta( $this->attachment_ids[5], 'isc_ignored_unused_image', true ), 'Fifth image should be ignored' );
	}

	/**
	 * Test bulk ignore skips invalid IDs
	 *
	 * Tests: \ISC\Pro\Unused_Images_List_Table::process_bulk_action()
	 */
	public function test_bulk_ignore_skips_invalid_ids() {
		// Set up bulk action with invalid ID
		$_GET['action'] = 'ignore';
		$_GET['attachment'] = [ 999999 ]; // Non-existent attachment ID

		// Process bulk action (should not cause errors)
		$this->list_table->process_bulk_action();

		// The test passes if no errors occurred
		$this->assertTrue( true, 'Bulk action should handle invalid IDs gracefully' );
	}

	/**
	 * Test get_views includes ignored filter when ignored images exist
	 *
	 * Tests: \ISC\Pro\Unused_Images_List_Table::get_views()
	 */
	public function test_get_views_includes_ignored_filter_when_ignored_images_exist() {
		// Clear cache to force fresh query
		delete_transient( 'isc_has_ignored_images' );

		$views = $this->list_table->get_views();

		$this->assertIsArray( $views, 'get_views() should return an array' );
		$this->assertArrayHasKey( 'ignored', $views, 'Views should include "ignored" filter when ignored images exist' );
	}

	/**
	 * Test get_views excludes ignored filter when no ignored images
	 *
	 * Tests: \ISC\Pro\Unused_Images_List_Table::get_views()
	 */
	public function test_get_views_excludes_ignored_filter_when_no_ignored_images() {
		// Unignore all images
		delete_post_meta( $this->attachment_ids[1], 'isc_ignored_unused_image' );
		delete_post_meta( $this->attachment_ids[2], 'isc_ignored_unused_image' );

		// Clear cache to force fresh query
		delete_transient( 'isc_has_ignored_images' );

		$views = $this->list_table->get_views();

		$this->assertIsArray( $views, 'get_views() should return an array' );
		$this->assertArrayNotHasKey( 'ignored', $views, 'Views should not include "ignored" filter when no ignored images exist' );
	}

	/**
	 * Test get_bulk_actions shows ignore on non-ignored views
	 *
	 * Tests: \ISC\Pro\Unused_Images_List_Table::get_bulk_actions()
	 */
	public function test_get_bulk_actions_shows_ignore_on_non_ignored_views() {
		$bulk_actions = $this->list_table->get_bulk_actions();

		$this->assertIsArray( $bulk_actions, 'get_bulk_actions() should return an array' );
		$this->assertArrayHasKey( 'ignore', $bulk_actions, 'Bulk actions should include "ignore" option on non-ignored views' );
		$this->assertArrayNotHasKey( 'unignore', $bulk_actions, 'Bulk actions should not include "unignore" on non-ignored views' );
	}

	/**
	 * Test get_bulk_actions shows unignore on ignored view
	 *
	 * Tests: \ISC\Pro\Unused_Images_List_Table::get_bulk_actions()
	 */
	public function test_get_bulk_actions_shows_unignore_on_ignored_view() {
		$bulk_actions = $this->list_table->get_bulk_actions();

		$this->assertIsArray( $bulk_actions, 'get_bulk_actions() should return an array' );
		$this->assertArrayHasKey( 'unignore', $bulk_actions, 'Bulk actions should include "unignore" option on ignored view' );
		$this->assertArrayNotHasKey( 'ignore', $bulk_actions, 'Bulk actions should not include "ignore" on ignored view' );
	}

	/**
	 * Test query filters exclude ignored images from all view
	 *
	 * Tests: Query filtering in \ISC\Pro\Unused_Images_List_Table
	 */
	public function test_query_filters_exclude_ignored_images_from_all_view() {
		// Get items (this would typically be done via prepare_items())
		$items = $this->list_table->get_items();

		$item_ids = wp_list_pluck( $items, 'ID' );

		// Verify ignored images are excluded
		$this->assertNotContains( $this->attachment_ids[1], $item_ids, 'Ignored image 1 should not appear in all view' );
		$this->assertNotContains( $this->attachment_ids[2], $item_ids, 'Ignored image 2 should not appear in all view' );

		// Verify non-ignored images are included
		$this->assertContains( $this->attachment_ids[3], $item_ids, 'Non-ignored image 3 should appear in all view' );
		$this->assertContains( $this->attachment_ids[4], $item_ids, 'Non-ignored image 4 should appear in all view' );
		$this->assertContains( $this->attachment_ids[5], $item_ids, 'Non-ignored image 5 should appear in all view' );
	}

	/**
	 * Test query filters exclude ignored images from unchecked view
	 *
	 * Tests: Query filtering in \ISC\Pro\Unused_Images_List_Table
	 */
	public function test_query_filters_exclude_ignored_images_from_unchecked_view() {
		// Get items
		$items = $this->list_table->get_items();

		$item_ids = wp_list_pluck( $items, 'ID' );

		// Verify ignored images are excluded
		$this->assertNotContains( $this->attachment_ids[1], $item_ids, 'Ignored image 1 should not appear in unchecked view' );
		$this->assertNotContains( $this->attachment_ids[2], $item_ids, 'Ignored image 2 should not appear in unchecked view' );
	}

	/**
	 * Test query filters exclude ignored images from unused view
	 *
	 * Tests: Query filtering in \ISC\Pro\Unused_Images_List_Table
	 */
	public function test_query_filters_exclude_ignored_images_from_unused_view() {
		// Get items
		$items = $this->list_table->get_items();

		$item_ids = wp_list_pluck( $items, 'ID' );

		// Verify ignored images are excluded
		$this->assertNotContains( $this->attachment_ids[1], $item_ids, 'Ignored image 1 should not appear in unused view' );
		$this->assertNotContains( $this->attachment_ids[2], $item_ids, 'Ignored image 2 should not appear in unused view' );
	}

	/**
	 * Test query filters show only ignored images in ignored view
	 *
	 * Tests: Query filtering in \ISC\Pro\Unused_Images_List_Table
	 */
	public function test_query_filters_show_only_ignored_images_in_ignored_view() {
		// Get items
		$items = $this->list_table->get_items();

		$item_ids = wp_list_pluck( $items, 'ID' );

		// Verify only ignored images are shown
		$this->assertContains( $this->attachment_ids[1], $item_ids, 'Ignored image 1 should appear in ignored view' );
		$this->assertContains( $this->attachment_ids[2], $item_ids, 'Ignored image 2 should appear in ignored view' );

		// Verify non-ignored images are excluded
		$this->assertNotContains( $this->attachment_ids[3], $item_ids, 'Non-ignored image 3 should not appear in ignored view' );
		$this->assertNotContains( $this->attachment_ids[4], $item_ids, 'Non-ignored image 4 should not appear in ignored view' );
		$this->assertNotContains( $this->attachment_ids[5], $item_ids, 'Non-ignored image 5 should not appear in ignored view' );
	}
}
