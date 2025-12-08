<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer;

use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Public;
use ISC\Pro\Unused_Images\Content_Scan\Content_Scan;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Tests for ISC\Pro\Indexer\Indexer_Public
 *
 * Most methods cannot be tested in a WPUnit test since they need an actual frontend with all hooks to run.
 */
class Content_Scan_Public_Test extends WPTestCase {

	/** @var Content_Scan_Public */
	protected Content_Scan_Public $content_scan_public;

	public function setUp(): void {
		parent::setUp();

		// Fresh instance each test
		$this->content_scan_public = new Content_Scan_Public();
	}

	public function tearDown(): void {
		parent::tearDown();

		// Ensure global post cleanup when tests used setup_postdata()
		if ( isset( $GLOBALS['post'] ) ) {
			wp_reset_postdata();
		}
	}

	/**
	 * index_for_post_expired() should return true when there is no last-index meta.
	 */
	public function test_index_for_post_expired_returns_true_when_no_meta() {
		$post_id = $this->factory->post->create();

		delete_post_meta( $post_id, Content_Scan::LAST_SCAN_META_KEY );

		$this->assertTrue( $this->content_scan_public->scan_for_post_expired( $post_id ) );
	}

	/**
	 * index_for_post_expired() should respect EXPIRATION_PERIOD:
	 * - recent timestamp => not expired
	 * - old timestamp => expired
	 */
	public function test_index_for_post_expired_respects_expiration_period() {
		$post_id = $this->factory->post->create();

		// recent timestamp -> not expired
		update_post_meta( $post_id, Content_Scan::LAST_SCAN_META_KEY, time() );
		$this->assertFalse( $this->content_scan_public->scan_for_post_expired( $post_id ) );

		// old timestamp -> expired
		$old_time = time() - ( Content_Scan_Public::EXPIRATION_PERIOD + 10 );
		update_post_meta( $post_id, Content_Scan::LAST_SCAN_META_KEY, $old_time );
		$this->assertTrue( $this->content_scan_public->scan_for_post_expired( $post_id ) );
	}
}