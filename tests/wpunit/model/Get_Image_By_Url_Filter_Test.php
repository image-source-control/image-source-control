<?php

namespace ISC\Tests\WPUnit\Model;

use ISC\Tests\WPUnit\WPTestCase;
use ISC_Model;
use ISC_Storage_Model;

/**
 * Test if the `isc_filter_get_image_by_url_result_final` hook can safely change the resolved values.
 */
class Get_Image_By_Url_Filter_Test extends WPTestCase {

	public function setUp(): void {
		parent::setUp();

		$this->image_id = wp_insert_attachment( [
			'guid'      => 'https://example.com/wp-content/uploads/filter-image.png',
			'post_type' => 'attachment',
		] );

		update_post_meta( $this->image_id, '_wp_attached_file', 'filter-image.png' );
	}

	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'isc_filter_get_image_by_url_result_final' );
	}

	/**
	 * Test if the final result filter can change the resolved image GUID/URL without breaking the lookup.
	 */
	public function test_final_result_filter_can_change_guid_and_storage() {
		$storage = new ISC_Storage_Model();
		$updated_url = 'https://example.com/wp-content/uploads/override-guid.png';

		add_filter( 'isc_filter_get_image_by_url_result_final', function ( $params ) use ( $updated_url ) {
			$params['guid'] = $updated_url;
			$params['newurl'] = $updated_url;
			$params['url'] = $updated_url;
			$params['id'] = $this->image_id;

			return $params;
		}, 10, 1 );

		$result = ISC_Model::get_image_by_url( 'https://example.com/wp-content/uploads/lookup.png' );

		$this->assertSame( $this->image_id, $result );
		$this->assertSame( $this->image_id, $storage->get_image_id_from_storage( $updated_url ) );
	}

	/**
	 * Test if non-array filter return values fall back to the original params instead of warning.
	 */
	public function test_final_result_filter_non_array_value_is_ignored() {
		add_filter( 'isc_filter_get_image_by_url_result_final', function () {
			return 'not-an-array';
		}, 10, 0 );

		$result = ISC_Model::get_image_by_url( 'https://example.com/wp-content/uploads/lookup.png' );

		$this->assertSame( 0, $result );
	}
}
