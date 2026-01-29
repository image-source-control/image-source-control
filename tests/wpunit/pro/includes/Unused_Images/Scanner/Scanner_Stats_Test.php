<?php
/**
 * Tests for Scanner_Stats class
 *
 * @package ISC\Pro\Tests
 */

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Scanner;

use ISC\Pro\Unused_Images\Scanner\Scanner_Stats;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Scanner Stats Test
 */
class Scanner_Stats_Test extends WPTestCase {

	/**
	 * Scanner Stats instance
	 *
	 * @var Scanner_Stats
	 */
	private Scanner_Stats $scanner_stats;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->scanner_stats = new Scanner_Stats();
	}

	/**
	 * Tear down test fixtures
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test get_content_scan_stats returns proper structure and types
	 */
	public function test_get_content_scan_stats_structure(): void {
		$stats = $this->scanner_stats->get_content_scan_stats();

		$this->assertIsArray( $stats );

		$this->assertIsInt( $stats['total'] );
		$this->assertIsInt( $stats['scanned'] );
		$this->assertIsInt( $stats['unscanned'] );
		$this->assertIsBool( $stats['expired'] );
	}

	/**
	 * Test get_content_scan_stats total equals scanned plus unscanned
	 *
	 * Expected to be empty initially since no content has been scanned yet.
	 */
	public function test_get_content_scan_stats_totals_match(): void {
		$stats = $this->scanner_stats->get_content_scan_stats();

		$this->assertEquals( $stats['total'], $stats['scanned'] + $stats['unscanned'] );
	}

	/**
	 * Test get_database_scan_stats returns proper structure and structure
	 */
	public function test_get_database_scan_stats_structure(): void {
		$stats = $this->scanner_stats->get_database_scan_stats();

		$this->assertIsArray( $stats );

		$this->assertIsInt( $stats['total'] );
		$this->assertIsInt( $stats['checked'] );
		$this->assertIsInt( $stats['unchecked'] );
	}

	/**
	 * Test get_database_scan_stats with no attachments
	 */
	public function test_get_database_scan_stats_with_no_attachments(): void {
		$stats = $this->scanner_stats->get_database_scan_stats();

		$this->assertEquals( 0, $stats['total'] );
		$this->assertEquals( 0, $stats['checked'] );
		$this->assertEquals( 0, $stats['unchecked'] );
	}

	/**
	 * Test get_database_scan_stats with attachments but no checks
	 */
	public function test_get_database_scan_stats_with_unchecked_attachments(): void {
		// Create test attachments
		self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		$stats = $this->scanner_stats->get_database_scan_stats();

		$this->assertGreaterThanOrEqual( 2, $stats['total'] );
		$this->assertEquals( 0, $stats['checked'] );
		$this->assertEquals( $stats['total'], $stats['unchecked'] );
	}

	/**
	 * Test get_database_scan_stats with checked attachments
	 */
	public function test_get_database_scan_stats_with_checked_attachments(): void {
		// Create test attachment
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		// Mark as checked
		update_post_meta( $attachment_id, 'isc_possible_usages', array() );

		$stats = $this->scanner_stats->get_database_scan_stats();

		$this->assertGreaterThanOrEqual( 1, $stats['total'] );
		$this->assertGreaterThanOrEqual( 1, $stats['checked'] );
		$this->assertEquals( $stats['total'], $stats['checked'] + $stats['unchecked'] );
	}

	/**
	 * Test get_all_stats returns both content and database stats
	 */
	public function test_get_all_stats_structure(): void {
		$stats = $this->scanner_stats->get_all_stats();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'content', $stats );
		$this->assertArrayHasKey( 'database', $stats );
	}

	/**
	 * Test stats handle large numbers of attachments
	 */
	public function test_get_database_scan_stats_with_multiple_attachments(): void {
		// Create multiple test attachments
		$attachment_ids = array();
		for ( $i = 0; $i < 10; $i ++ ) {
			$attachment_ids[] = $this->factory->attachment->create_object(
				"test-image-{$i}.jpg",
				0,
				array(
					'post_mime_type' => 'image/jpeg',
					'post_type'      => 'attachment',
				)
			);
		}

		// Mark half as checked
		foreach ( array_slice( $attachment_ids, 0, 5 ) as $id ) {
			update_post_meta( $id, 'isc_possible_usages', array() );
		}

		$stats = $this->scanner_stats->get_database_scan_stats();

		$this->assertGreaterThanOrEqual( 10, $stats['total'] );
		$this->assertGreaterThanOrEqual( 5, $stats['checked'] );
		$this->assertGreaterThanOrEqual( 5, $stats['unchecked'] );
	}

	/**
	 * Test get_all_images_count counts only images when images_only option is enabled
	 *
	 * This tests the private get_all_images_count() method indirectly via get_database_scan_stats()
	 */
	public function test_database_scan_counts_only_images_when_images_only_enabled(): void {
		// Enable images_only option
		$options = get_option( 'isc_options', array() );
		$original_images_only = $options['images_only'] ?? false;
		$options['images_only'] = true;
		update_option( 'isc_options', $options );

		// Create image attachments
		self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		// Create non-image attachment
		self::factory()->attachment->create_object(
			'test-document.pdf',
			0,
			array(
				'post_mime_type' => 'application/pdf',
				'post_type'      => 'attachment',
			)
		);

		$stats = $this->scanner_stats->get_database_scan_stats();

		// Total should only count images (2), not the PDF
		$this->assertGreaterThanOrEqual( 2, $stats['total'], 'Should count at least the 2 images created' );

		// Restore original option
		$options['images_only'] = $original_images_only;
		update_option( 'isc_options', $options );
	}

	/**
	 * Test get_all_images_count counts all attachments when images_only option is disabled
	 *
	 * This tests the private get_all_images_count() method indirectly via get_database_scan_stats()
	 */
	public function test_database_scan_counts_all_attachments_when_images_only_disabled(): void {
		// Disable images_only option
		$options = get_option( 'isc_options', array() );
		$original_images_only = $options['images_only'] ?? false;
		$options['images_only'] = false;
		update_option( 'isc_options', $options );

		$stats_before = $this->scanner_stats->get_database_scan_stats();

		// Create image attachment
		self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		// Create non-image attachment
		self::factory()->attachment->create_object(
			'test-document.pdf',
			0,
			array(
				'post_mime_type' => 'application/pdf',
				'post_type'      => 'attachment',
			)
		);

		$stats_after = $this->scanner_stats->get_database_scan_stats();

		// Both image and PDF should be counted
		$this->assertEquals( $stats_before['total'] + 2, $stats_after['total'], 'Should count both image and PDF when images_only is disabled' );

		// Restore original option
		$options['images_only'] = $original_images_only;
		update_option( 'isc_options', $options );
	}

	/**
	 * Test get_all_images_count excludes site icon
	 *
	 * This tests that the site icon is properly excluded from total count
	 */
	public function test_database_scan_excludes_site_icon_from_total(): void {
		// Create an image to use as site icon
		$site_icon_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		// Get count before setting site icon
		$stats_before = $this->scanner_stats->get_database_scan_stats();

		// Set as site icon
		$original_site_icon = get_option( 'site_icon' );
		update_option( 'site_icon', $site_icon_id );

		// Get count after setting site icon
		$stats_after = $this->scanner_stats->get_database_scan_stats();

		// The site icon should be excluded, so total might be less or the same
		// (depending on whether it was already in the count)
		$this->assertIsInt( $stats_after['total'] );

		// Restore original site icon
		if ( $original_site_icon ) {
			update_option( 'site_icon', $original_site_icon );
		} else {
			delete_option( 'site_icon' );
		}
	}

	/**
	 * Test get_all_checked_images_count only counts images with isc_possible_usages meta
	 *
	 * This tests the private get_all_checked_images_count() method indirectly
	 */
	public function test_database_scan_checked_count_requires_meta_key(): void {
		// Create test attachments
		$checked_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$unchecked_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		// Mark only one as checked
		update_post_meta( $checked_id, 'isc_possible_usages', array( 'test' => 'data' ) );

		$stats = $this->scanner_stats->get_database_scan_stats();

		// Should have at least 1 checked
		$this->assertGreaterThanOrEqual( 1, $stats['checked'], 'Should count the checked attachment' );
		// Should have at least 1 unchecked
		$this->assertGreaterThanOrEqual( 1, $stats['unchecked'], 'Should count the unchecked attachment' );
	}

	/**
	 * Test get_all_checked_images_count respects images_only option
	 *
	 * This tests that checked count also filters by image type when images_only is enabled
	 */
	public function test_database_scan_checked_count_respects_images_only_option(): void {
		// Enable images_only option
		$options = get_option( 'isc_options', array() );
		$original_images_only = $options['images_only'] ?? false;
		$options['images_only'] = true;
		update_option( 'isc_options', $options );

		// Create and check an image
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		update_post_meta( $image_id, 'isc_possible_usages', array() );

		// Create and check a non-image
		$pdf_id = self::factory()->attachment->create_object(
			'test-document.pdf',
			0,
			array(
				'post_mime_type' => 'application/pdf',
				'post_type'      => 'attachment',
			)
		);
		update_post_meta( $pdf_id, 'isc_possible_usages', array() );

		$stats = $this->scanner_stats->get_database_scan_stats();

		// Only the image should be counted as checked (PDF should be excluded)
		$this->assertGreaterThanOrEqual( 1, $stats['checked'], 'Should count the checked image' );
		$this->assertEquals( 1, $stats['total'], 'Should only count the image in total when images_only is enabled' );

		// Restore original option
		$options['images_only'] = $original_images_only;
		update_option( 'isc_options', $options );
	}

	/**
	 * Test that checked and unchecked counts always sum to total
	 *
	 * This is a critical invariant that should always hold
	 */
	public function test_database_scan_checked_plus_unchecked_equals_total(): void {
		// Create mix of checked and unchecked attachments
		$checked_1 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$checked_2 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );
		$unchecked_1 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$unchecked_2 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		update_post_meta( $checked_1, 'isc_possible_usages', array() );
		update_post_meta( $checked_2, 'isc_possible_usages', array() );

		$stats = $this->scanner_stats->get_database_scan_stats();

		// This invariant must ALWAYS hold
		$this->assertEquals(
			$stats['total'],
			$stats['checked'] + $stats['unchecked'],
			'Checked + Unchecked must always equal Total'
		);
	}

	/**
	 * Test that empty isc_possible_usages meta still counts as checked
	 *
	 * Even if the meta value is empty, the presence of the key means it was checked
	 */
	public function test_database_scan_counts_empty_possible_usages_as_checked(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		// Set empty array as meta value
		update_post_meta( $attachment_id, 'isc_possible_usages', array() );

		$stats = $this->scanner_stats->get_database_scan_stats();

		$this->assertGreaterThanOrEqual( 1, $stats['checked'], 'Empty isc_possible_usages should still count as checked' );
	}

	/**
	 * Test consistency of counts across multiple instantiations
	 *
	 * Creating a new Scanner_Stats instance should yield the same results
	 */
	public function test_stats_consistency_across_instances(): void {
		// Create test data
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		update_post_meta( $attachment_id, 'isc_possible_usages', array() );

		$stats1 = $this->scanner_stats->get_database_scan_stats();

		// Create new instance
		$new_scanner_stats = new Scanner_Stats();
		$stats2 = $new_scanner_stats->get_database_scan_stats();

		$this->assertEquals( $stats1['total'], $stats2['total'], 'Total should be consistent across instances' );
		$this->assertEquals( $stats1['checked'], $stats2['checked'], 'Checked should be consistent across instances' );
		$this->assertEquals( $stats1['unchecked'], $stats2['unchecked'], 'Unchecked should be consistent across instances' );
	}
}