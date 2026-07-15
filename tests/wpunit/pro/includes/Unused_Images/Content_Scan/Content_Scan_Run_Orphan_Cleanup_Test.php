<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Content_Scan;

use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Admin;
use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Tests orphan cleanup behavior after full scan completion.
 */
class Content_Scan_Admin_Orphan_Cleanup_Test extends WPTestCase {

	private Content_Scan_Admin $content_scan_admin;
	private Content_Scan_Table $content_scan_table;

	public function setUp(): void {
		parent::setUp();
		$this->content_scan_admin = new Content_Scan_Admin();
		$this->content_scan_table = new Content_Scan_Table();
	}

	public function tearDown(): void {
		$this->content_scan_table->clear_all();
		parent::tearDown();
	}

	/**
	 * Insert a synthetic orphan row (post_id does not exist).
	 */
	private function insert_orphan_row( int $attachment_id ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'isc_index',
			[
				'post_id'       => 99999999,
				'attachment_id' => $attachment_id,
				'position'      => 'content',
			],
			[ '%d', '%d', '%s' ]
		);
	}

	/**
	 * Invoke the private cleanup method via reflection.
	 */
	private function call_cleanup_method( string $scan_mode ): void {
		$reflection = new \ReflectionClass( $this->content_scan_admin );
		$method     = $reflection->getMethod( 'maybe_delete_orphaned_entries_after_full_scan' );
		$method->setAccessible( true );
		$method->invoke( $this->content_scan_admin, $this->content_scan_table, $scan_mode );
	}

	/**
	 * Full scan mode ("all") should delete orphaned entries.
	 */
	public function test_cleanup_runs_in_all_mode(): void {
		$attachment_id = self::factory()->attachment->create();
		$this->insert_orphan_row( $attachment_id );

		$orphans_before = $this->content_scan_table->find_orphaned_entries();
		$this->assertNotEmpty( $orphans_before, 'Expected orphans before cleanup.' );

		$this->call_cleanup_method( 'all' );

		$orphans_after = $this->content_scan_table->find_orphaned_entries();
		$this->assertEmpty( $orphans_after, 'Orphans should be deleted in full scan mode.' );
	}

	/**
	 * Non-full mode ("unindexed") should not delete orphaned entries.
	 */
	public function test_cleanup_does_not_run_in_unindexed_mode(): void {
		$attachment_id = self::factory()->attachment->create();
		$this->insert_orphan_row( $attachment_id );

		$orphans_before = $this->content_scan_table->find_orphaned_entries();
		$this->assertNotEmpty( $orphans_before, 'Expected orphans before calling cleanup in unindexed mode.' );

		$this->call_cleanup_method( 'unindexed' );

		$orphans_after = $this->content_scan_table->find_orphaned_entries();
		$this->assertNotEmpty( $orphans_after, 'Orphans should remain in unindexed mode.' );
	}
}