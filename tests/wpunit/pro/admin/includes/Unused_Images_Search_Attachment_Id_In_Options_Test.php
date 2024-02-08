<?php

namespace ISC\Pro;

require_once dirname( __FILE__, 6 ) . '/pro/admin/includes/unused-images.php';

/**
 * Testing \ISC\Pro\Unused_Images::search_attachment_id_in_options()
 */
class Unused_Images_Search_Attachment_Id_In_Options_Test extends \Codeception\TestCase\WPTestCase {
	/**
	 * Test the search_attachment_id_in_options() function to see if it returns a results for a plain option
	 */
	public function test_attachment_id_in_plain_options() {
		$attachment_id = rand( 10000, 99999 );
		update_option( 'site_logo', $attachment_id );

		$unused_images = new Unused_Images();
		$result        = $unused_images->search_attachment_id_in_options( $attachment_id );

		// returns one result
		$this->assertCount( 1, $result );
		// the option name in the result is correct
		$this->assertEquals( 'site_logo', $result[0]->option_name );
	}

	/**
	 * Test the search_attachment_id_in_options() function to see if it returns a result for an ID that is a value in a multidimensional array
	 * Should find a result
	 */
	public function test_attachment_id_value_in_multidimensional_options() {
		$attachment_id = rand( 10000, 99999 );

		update_option( 'multidimensional_array_value', [
			123 => 'some',
			234 => [
				'first' => 'value',
				'key' => $attachment_id,
				'foo' => 'bar'
			]
		] );

		$unused_images = new Unused_Images();
		$result        = $unused_images->search_attachment_id_in_options( $attachment_id );

		// returns one result
		$this->assertCount( 1, $result );
		// the option name in the result is correct
		$this->assertEquals( 'multidimensional_array_value', $result[0]->option_name );
	}

	/**
	 * Test the search_attachment_id_in_options() function to see if it returns a result for an ID that is a key in a multidimensional array,
	 * serialized in the options values
	 * It should not return a result
	 */
	public function test_attachment_id_key_in_multidimensional_options() {
		$attachment_id = rand( 10000, 99999 );
		update_option( 'multidimensional_array_key', [
			123 => 'some',
			234 => [
				'first' => 'value',
				$attachment_id => 'value',
				'foo' => 'bar'
			]
		] );

		$unused_images = new Unused_Images();
		$result        = $unused_images->search_attachment_id_in_options( $attachment_id );

		// no result returned
		$this->assertEquals( [], $result );
	}

}
