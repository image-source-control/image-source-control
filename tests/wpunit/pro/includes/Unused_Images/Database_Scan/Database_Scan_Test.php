<?php

/**
 * Tests for Database_Scan class
 *
 * @package ISC\Pro\Tests
 */

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Database_Scan;

use ISC\Pro\Unused_Images\Database_Scan\Database_Scan;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Database Scan Test
 */
class Database_Scan_Test extends WPTestCase {

	/**
	 * Database Scan instance
	 *
	 * @var Database_Scan
	 */
	private Database_Scan $database_scan;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->database_scan = new Database_Scan();
	}

	/**
	 * Tear down test fixtures
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test get_batch returns empty array when no attachments exist
	 */
	public function test_get_batch_returns_empty_with_no_attachments(): void {
		$batch = $this->database_scan->get_batch();

		$this->assertIsArray( $batch );
		$this->assertEmpty( $batch );
	}

	/**
	 * Test get_batch returns attachment IDs
	 */
	public function test_get_batch_returns_attachment_ids(): void {
		// Create test attachments
		$attachment_id_1 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$attachment_id_2 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		$batch = $this->database_scan->get_batch( [ 'batch_size' => 10 ] );

		$this->assertIsArray( $batch );
		$this->assertContains( $attachment_id_1, $batch );
		$this->assertContains( $attachment_id_2, $batch );
	}

	/**
	 * Test get_batch respects batch_size parameter
	 */
	public function test_get_batch_respects_batch_size(): void {
		// Create 5 test attachments
		for ( $i = 0; $i < 5; $i ++ ) {
			$filename = ( $i % 2 === 0 ) ? 'test-image1.jpg' : 'test-image2.jpg';
			self::factory()->attachment->create_upload_object( codecept_data_dir( $filename ) );
		}

		$batch = $this->database_scan->get_batch( [ 'batch_size' => 3 ] );

		$this->assertCount( 3, $batch );
	}

	/**
	 * Test get_batch uses default batch size when not specified
	 */
	public function test_get_batch_uses_default_batch_size(): void {
		// Create more than default batch size worth of attachments
		for ( $i = 0; $i < 15; $i ++ ) {
			$filename = ( $i % 2 === 0 ) ? 'test-image1.jpg' : 'test-image2.jpg';
			self::factory()->attachment->create_upload_object( codecept_data_dir( $filename ) );
		}

		$batch = $this->database_scan->get_batch();

		$this->assertCount( Database_Scan::DEFAULT_BATCH_SIZE, $batch );
	}

	/**
	 * Test get_batch clamps batch_size to minimum
	 */
	public function test_get_batch_clamps_to_minimum_batch_size(): void {
		// Create test attachments
		self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$batch = $this->database_scan->get_batch( [ 'batch_size' => - 5 ] );

		$this->assertCount( Database_Scan::MIN_BATCH_SIZE, $batch );
	}

	/**
	 * Test get_batch clamps batch_size to maximum
	 */
	public function test_get_batch_clamps_to_maximum_batch_size(): void {
		// Create more than max batch size worth of attachments
		for ( $i = 0; $i < 60; $i ++ ) {
			$filename = ( $i % 2 === 0 ) ? 'test-image1.jpg' : 'test-image2.jpg';
			self::factory()->attachment->create_upload_object( codecept_data_dir( $filename ) );
		}

		$batch = $this->database_scan->get_batch( [ 'batch_size' => 100 ] );

		$this->assertLessThanOrEqual( Database_Scan::MAX_BATCH_SIZE, count( $batch ) );
	}

	/**
	 * Test get_batch with only_missing=true excludes checked images
	 */
	public function test_get_batch_only_missing_excludes_checked(): void {
		// Create attachments
		$unchecked_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$checked_id   = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		// Mark one as checked
		update_post_meta( $checked_id, 'isc_possible_usages', array() );

		$batch = $this->database_scan->get_batch( [ 'only_missing' => true ] );

		$this->assertContains( $unchecked_id, $batch );
		$this->assertNotContains( $checked_id, $batch );
	}

	/**
	 * Test get_batch with only_missing=false includes all images
	 */
	public function test_get_batch_only_missing_false_includes_all(): void {
		// Create attachments
		$unchecked_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$checked_id   = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		// Mark one as checked
		update_post_meta( $checked_id, 'isc_possible_usages', array() );

		$batch = $this->database_scan->get_batch( [ 'only_missing' => false ] );

		$this->assertContains( $unchecked_id, $batch );
		$this->assertContains( $checked_id, $batch );
	}

	/**
	 * Test get_batch respects offset parameter
	 */
	public function test_get_batch_respects_offset(): void {
		// Create attachments
		for ( $i = 0; $i < 5; $i ++ ) {
			$filename = ( $i % 2 === 0 ) ? 'test-image1.jpg' : 'test-image2.jpg';
			self::factory()->attachment->create_upload_object( codecept_data_dir( $filename ) );
		}

		// Get first batch
		$batch1 = $this->database_scan->get_batch( [
			                                           'batch_size'   => 2,
			                                           'offset'       => 0,
			                                           'only_missing' => false,
		                                           ] );

		// Get second batch with offset
		$batch2 = $this->database_scan->get_batch( [
			                                           'batch_size'   => 2,
			                                           'offset'       => 2,
			                                           'only_missing' => false,
		                                           ] );

		// Batches should not overlap
		$this->assertEmpty( array_intersect( $batch1, $batch2 ) );
	}

	/**
	 * Test get_batch filters out processed_ids
	 */
	public function test_get_batch_filters_processed_ids(): void {
		// Create attachments
		$id1 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$id2 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );
		$id3 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$batch = $this->database_scan->get_batch( [
			                                          'batch_size'    => 10,
			                                          'processed_ids' => array( $id1, $id2 ),
		                                          ] );

		$this->assertNotContains( $id1, $batch );
		$this->assertNotContains( $id2, $batch );
		$this->assertContains( $id3, $batch );
	}

	/**
	 * Test get_batch with empty processed_ids array
	 */
	public function test_get_batch_with_empty_processed_ids(): void {
		$id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$batch = $this->database_scan->get_batch( [ 'processed_ids' => array() ] );

		$this->assertContains( $id, $batch );
	}

	/**
	 * Test get_batch filters by image type when images_only option is enabled
	 */
	public function test_get_batch_respects_images_only_option(): void {
		// Enable images_only option
		$options                = get_option( 'isc_options', array() );
		$original_images_only   = $options['images_only'] ?? false;
		$options['images_only'] = true;
		update_option( 'isc_options', $options );

		// Create image
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		// Create non-image
		$pdf_id = self::factory()->attachment->create_object(
			'test-document.pdf',
			0,
			array(
				'post_mime_type' => 'application/pdf',
				'post_type'      => 'attachment',
			)
		);

		$batch = $this->database_scan->get_batch();

		$this->assertContains( $image_id, $batch );
		$this->assertNotContains( $pdf_id, $batch );

		// Restore original option
		$options['images_only'] = $original_images_only;
		update_option( 'isc_options', $options );
	}

	/**
	 * Test run_batch returns proper structure
	 */
	public function test_run_batch_returns_proper_structure(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$result = $this->database_scan->run_batch( array( $attachment_id ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertArrayHasKey( 'stats', $result );
		$this->assertArrayHasKey( 'suggested_batch_size', $result );
	}

	/**
	 * Test run_batch results structure
	 */
	public function test_run_batch_results_structure(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$result = $this->database_scan->run_batch( array( $attachment_id ) );

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );

		$item = $result['results'][0];
		$this->assertArrayHasKey( 'id', $item );
		$this->assertArrayHasKey( 'success', $item );
		$this->assertArrayHasKey( 'has_usages', $item );
		$this->assertArrayHasKey( 'usage_count', $item );
	}

	/**
	 * Test run_batch stats structure
	 */
	public function test_run_batch_stats_structure(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$result = $this->database_scan->run_batch( array( $attachment_id ) );

		$this->assertIsArray( $result['stats'] );
		$this->assertArrayHasKey( 'total_time', $result['stats'] );
		$this->assertArrayHasKey( 'images_scanned', $result['stats'] );
		$this->assertArrayHasKey( 'avg_time_per_image', $result['stats'] );

		$this->assertIsFloat( $result['stats']['total_time'] );
		$this->assertIsInt( $result['stats']['images_scanned'] );
		$this->assertIsFloat( $result['stats']['avg_time_per_image'] );
	}

	/**
	 * Test run_batch with empty array
	 */
	public function test_run_batch_with_empty_array(): void {
		$result = $this->database_scan->run_batch( array() );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result['results'] );
		$this->assertEquals( 0, $result['stats']['images_scanned'] );
	}

	/**
	 * Test run_batch with multiple images
	 */
	public function test_run_batch_with_multiple_images(): void {
		$ids = array();
		for ( $i = 0; $i < 3; $i ++ ) {
			$filename = ( $i % 2 === 0 ) ? 'test-image1.jpg' : 'test-image2.jpg';
			$ids[]    = self::factory()->attachment->create_upload_object( codecept_data_dir( $filename ) );
		}

		$result = $this->database_scan->run_batch( $ids );

		$this->assertCount( 3, $result['results'] );
		$this->assertEquals( 3, $result['stats']['images_scanned'] );
	}

	/**
	 * Test run_batch stores isc_possible_usages meta
	 */
	public function test_run_batch_stores_meta_data(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		// Ensure no meta exists before
		$meta_before = get_post_meta( $attachment_id, 'isc_possible_usages' );
		$this->assertEmpty( $meta_before );

		$this->database_scan->run_batch( array( $attachment_id ) );

		// Meta key should now exist (even if empty array)
		$meta_after = get_post_meta( $attachment_id, 'isc_possible_usages' );
		$this->assertNotEmpty( $meta_after, 'Meta key should exist after scan' );
	}

	/**
	 * Test run_batch returns success status
	 */
	public function test_run_batch_returns_success_status(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$result = $this->database_scan->run_batch( array( $attachment_id ) );

		$this->assertIsBool( $result['results'][0]['success'] );
	}

	/**
	 * Test run_batch calculates usage count correctly
	 */
	public function test_run_batch_calculates_usage_count(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$result = $this->database_scan->run_batch( array( $attachment_id ) );

		$this->assertIsInt( $result['results'][0]['usage_count'] );
		$this->assertGreaterThanOrEqual( 0, $result['results'][0]['usage_count'] );
	}

	/**
	 * Test run_batch suggested_batch_size is within bounds
	 */
	public function test_run_batch_suggested_batch_size_within_bounds(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$result = $this->database_scan->run_batch( array( $attachment_id ) );

		$suggested = $result['suggested_batch_size'];
		$this->assertGreaterThanOrEqual( Database_Scan::MIN_BATCH_SIZE, $suggested );
		$this->assertLessThanOrEqual( Database_Scan:: MAX_BATCH_SIZE, $suggested );
	}

	/**
	 * Test run_single returns proper structure
	 */
	public function test_run_single_returns_proper_structure(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$result = $this->database_scan->run_single( $attachment_id );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'has_usages', $result );
		$this->assertArrayHasKey( 'usage_count', $result );
	}

	/**
	 * Test run_single with valid image ID
	 */
	public function test_run_single_with_valid_id(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$result = $this->database_scan->run_single( $attachment_id );

		$this->assertEquals( $attachment_id, $result['id'] );
		$this->assertIsBool( $result['success'] );
	}

	/**
	 * Test run_single stores meta data
	 */
	public function test_run_single_stores_meta_data(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$this->database_scan->run_single( $attachment_id );

		// Meta key should exist, even if the value is an empty array
		$meta = get_post_meta( $attachment_id, 'isc_possible_usages', true );
		$this->assertNotNull( $meta );
		$this->assertIsArray( $meta );
	}

	/**
	 * Test get_total_count with only_missing=true
	 */
	public function test_get_total_count_only_missing(): void {
		// Create attachments
		$unchecked_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$checked_id   = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		// Mark one as checked
		update_post_meta( $checked_id, 'isc_possible_usages', array() );

		$count = $this->database_scan->get_total_count( true );

		$this->assertGreaterThanOrEqual( 1, $count );
	}

	/**
	 * Test get_total_count with only_missing=false
	 */
	public function test_get_total_count_all_images(): void {
		self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		$count = $this->database_scan->get_total_count( false );

		$this->assertGreaterThanOrEqual( 2, $count );
	}

	/**
	 * Test get_total_count with no attachments
	 */
	public function test_get_total_count_with_no_attachments(): void {
		$count = $this->database_scan->get_total_count();

		$this->assertEquals( 0, $count );
	}

	/**
	 * Test get_total_count returns integer
	 */
	public function test_get_total_count_returns_integer(): void {
		$count = $this->database_scan->get_total_count();

		$this->assertIsInt( $count );
	}

	/**
	 * Test batch processing workflow:  get batch, run batch, repeat
	 */
	public function test_batch_processing_workflow(): void {
		// Create test attachments
		$ids = array();
		for ( $i = 0; $i < 5; $i ++ ) {
			$filename = ( $i % 2 === 0 ) ? 'test-image1.jpg' : 'test-image2.jpg';
			$ids[]    = self::factory()->attachment->create_upload_object( codecept_data_dir( $filename ) );
		}

		$processed_ids = array();

		// First batch
		$batch1 = $this->database_scan->get_batch( [
			                                           'batch_size'    => 2,
			                                           'processed_ids' => $processed_ids,
		                                           ] );
		$this->assertCount( 2, $batch1 );

		$result1 = $this->database_scan->run_batch( $batch1 );
		$this->assertEquals( 2, $result1['stats']['images_scanned'] );

		$processed_ids = array_merge( $processed_ids, $batch1 );

		// Second batch
		$batch2 = $this->database_scan->get_batch( [
			                                           'batch_size'    => 2,
			                                           'processed_ids' => $processed_ids,
		                                           ] );
		$this->assertCount( 2, $batch2 );
		$this->assertEmpty( array_intersect( $batch1, $batch2 ) );

		$result2 = $this->database_scan->run_batch( $batch2 );
		$this->assertEquals( 2, $result2['stats']['images_scanned'] );
	}

	/**
	 * Test that run_batch marks images as checked
	 */
	public function test_run_batch_marks_images_as_checked(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		// Verify unchecked initially
		$count_before = $this->database_scan->get_total_count( true );

		$this->database_scan->run_batch( array( $attachment_id ) );

		// Should now be checked
		$count_after = $this->database_scan->get_total_count( true );
		$this->assertLessThan( $count_before, $count_after );
	}

	/**
	 * Test performance stats are reasonable
	 */
	public function test_performance_stats_are_reasonable(): void {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$result = $this->database_scan->run_batch( array( $attachment_id ) );

		// Time should be positive and reasonable (less than 10 seconds for one image)
		$this->assertGreaterThan( 0, $result['stats']['total_time'] );
		$this->assertLessThan( 10, $result['stats']['total_time'] );

		// Average should equal total for single image
		$this->assertEquals( $result['stats']['total_time'], $result['stats']['avg_time_per_image'] );
	}

	/**
	 * Test get_batch returns IDs in ascending order
	 */
	public function test_get_batch_returns_ordered_ids(): void {
		// Create attachments
		for ( $i = 0; $i < 3; $i ++ ) {
			$filename = ( $i % 2 === 0 ) ? 'test-image1.jpg' : 'test-image2.jpg';
			self::factory()->attachment->create_upload_object( codecept_data_dir( $filename ) );
		}

		$batch = $this->database_scan->get_batch( [ 'batch_size' => 10 ] );

		// Check if sorted
		$sorted_batch = $batch;
		sort( $sorted_batch );
		$this->assertEquals( $sorted_batch, $batch );
	}
}