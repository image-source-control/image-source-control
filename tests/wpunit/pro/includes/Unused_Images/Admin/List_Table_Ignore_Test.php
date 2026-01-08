<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing \ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table ignore/unignore functionality
 */
class List_Table_Ignore_Test extends WPTestCase {

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
	protected Unused_Images_List_Table $list_table;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

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
	 * Test get_views includes ignored filter when ignored images exist
	 *
	 * Tests: \ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table::get_views()
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
	 * Tests: \ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table::get_views()
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
	 * Tests: \ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table::get_bulk_actions()
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
	 * Tests: \ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table::get_bulk_actions()
	 */
	public function test_get_bulk_actions_shows_unignore_on_ignored_view() {
		// Set current view to 'ignored'
		$_REQUEST['filter'] = 'ignored';

		$bulk_actions = $this->list_table->get_bulk_actions();

		$this->assertIsArray( $bulk_actions, 'get_bulk_actions() should return an array' );
		$this->assertArrayHasKey( 'unignore', $bulk_actions, 'Bulk actions should include "unignore" option on ignored view' );
		$this->assertArrayNotHasKey( 'ignore', $bulk_actions, 'Bulk actions should not include "ignore" on ignored view' );
	}

	/**
	 * Test query filters exclude ignored images from all view
	 *
	 * Tests: Query filtering in \ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table
	 */
	public function test_query_filters_exclude_ignored_images_from_all_view() {
		$_REQUEST['filter'] = 'all';
		$items = $this->list_table->get_items();

		$item_ids = array_map( 'intval', wp_list_pluck( $items, 'ID' ) );

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
	 * Tests: Query filtering in \ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table
	 */
	public function test_query_filters_exclude_ignored_images_from_unchecked_view() {
		// Set current view to 'unchecked'
		$_REQUEST['filter'] = 'unchecked';

		// Get items
		$items = $this->list_table->get_items();

		$item_ids = array_map( 'intval', wp_list_pluck( $items, 'ID' ) );

		// Verify ignored images are excluded
		$this->assertNotContains( $this->attachment_ids[1], $item_ids, 'Ignored image 1 should not appear in unchecked view' );
		$this->assertNotContains( $this->attachment_ids[2], $item_ids, 'Ignored image 2 should not appear in unchecked view' );
	}

	/**
	 * Test query filters exclude ignored images from unused view
	 *
	 * Tests: Query filtering in \ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table
	 */
	public function test_query_filters_exclude_ignored_images_from_unused_view() {
		// Set current view to 'unused' (default view)
		$_REQUEST['filter'] = 'unused';

		// Get items
		$items = $this->list_table->get_items();

		$item_ids = array_map( 'intval', wp_list_pluck( $items, 'ID' ) );

		// Verify ignored images are excluded
		$this->assertNotContains( $this->attachment_ids[1], $item_ids, 'Ignored image 1 should not appear in unused view' );
		$this->assertNotContains( $this->attachment_ids[2], $item_ids, 'Ignored image 2 should not appear in unused view' );
	}

	/**
	 * Test query filters show only ignored images in ignored view
	 *
	 * Tests: Query filtering in \ISC\Pro\Unused_Images\Admin\Unused_Images_List_Table
	 */
	public function test_query_filters_show_only_ignored_images_in_ignored_view() {
		// Set current view to 'ignored'
		$_REQUEST['filter'] = 'ignored';

		// Get items
		$items = $this->list_table->get_items();

		$item_ids = array_map( 'intval', wp_list_pluck( $items, 'ID' ) );

		// Verify only ignored images are shown
		$this->assertContains( $this->attachment_ids[1], $item_ids, 'Ignored image 1 should appear in ignored view' );
		$this->assertContains( $this->attachment_ids[2], $item_ids, 'Ignored image 2 should appear in ignored view' );

		// Verify non-ignored images are excluded
		$this->assertNotContains( $this->attachment_ids[3], $item_ids, 'Non-ignored image 3 should not appear in ignored view' );
		$this->assertNotContains( $this->attachment_ids[4], $item_ids, 'Non-ignored image 4 should not appear in ignored view' );
		$this->assertNotContains( $this->attachment_ids[5], $item_ids, 'Non-ignored image 5 should not appear in ignored view' );
	}
}
