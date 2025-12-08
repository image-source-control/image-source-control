<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Admin\Unused_Images;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * @coversDefaultClass \ISC\Pro\Unused_Images\Admin\Unused_Images
 */
class Unused_Images_Get_Definitely_Used_Image_Ids_Test extends WPTestCase {

	/**
	 * Helper to get protected static method via reflection
	 *
	 * @return \ReflectionMethod
	 */
	protected function getReflectionMethod(): \ReflectionMethod {
		$rm = new \ReflectionMethod( Unused_Images::class, 'get_definitely_used_image_ids' );
		$rm->setAccessible( true );

		return $rm;
	}

	/**
	 * Clean up option after each test
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'site_icon' );
		remove_all_filters( 'isc_unused_images_ids_considered_used' );
	}

	/**
	 * Empty array when no site icon and no filter
	 *
	 * @covers ::get_definitely_used_image_ids
	 */
	public function test_returns_empty_array_when_no_site_icon_and_no_filter() {
		$rm  = $this->getReflectionMethod();
		$ids = $rm->invoke( null );
		$this->assertIsArray( $ids );
		$this->assertEmpty( $ids );
	}

	/**
	 * Return array with the site icon ID
	 *
	 * @covers ::get_definitely_used_image_ids
	 */
	public function test_includes_site_icon_option_id() {
		// Create a dummy attachment and set it as site_icon
		$attachment_id = $this->factory->post->create( [ 'post_type' => 'attachment' ] );
		update_option( 'site_icon', $attachment_id );

		$rm  = $this->getReflectionMethod();
		$ids = $rm->invoke( null );

		$this->assertCount( 1, $ids );
		$this->assertContains( $attachment_id, $ids );
	}

	/**
	 * Test that the filter is applied and performs cleanup
	 *
	 * @covers ::get_definitely_used_image_ids
	 */
	public function test_applies_filter_and_performs_cleanup() {
		// Prepare base IDs array via filter
		$base_id = 123;
		$dup_id  = 123;
		$other   = '456';
		add_filter( 'isc_unused_images_ids_considered_used', function( array $ids ) use ( $base_id, $dup_id, $other ) {
			// pre-populate with duplicate and string value
			return array_merge( $ids, [ $base_id, $dup_id, $other ] );
		} );

		$rm  = $this->getReflectionMethod();
		$ids = $rm->invoke( null );

		// All values must be ints
		foreach ( $ids as $id ) {
			$this->assertIsInt( $id );
		}
		// Should contain 123 and 456, but no duplicates
		$this->assertContains( 123, $ids );
		$this->assertContains( 456, $ids );
		$this->assertCount( 2, $ids );
	}
}
