<?php

namespace ISC\Tests\WPUnit;

use ISC\Includes\Standard_Source;
use phpDocumentor\Reflection\Types\Object_;

/**
 * Testing the Standard_Source class
 */
class Standard_Source_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * User ID
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * Cleanup
	 */
	protected function tearDown(): void {
		// Delete the user created in test_get_standard_source_text_for_attachment_post_author
		if ( isset( $this->user_id ) ) {
			wp_delete_user( $this->user_id );
		}

		// Reset modified options
		delete_option( 'isc_options' );

		// Call parent tearDown
		parent::tearDown();
	}

	/**
	 * Test the get_standard_source_text() method
	 *
	 * @return void
	 */
	public function test_get_standard_source_text() {
		$this->assertEquals( '© http://isc.local', Standard_Source::get_standard_source_text() );
	}

	/**
	 * Test the get_standard_source() method to see which option is selected by default (should be Custom Text)
	 *
	 * @return void
	 */
	public function test_get_standard_source() {
		$this->assertEquals( 'custom_text', Standard_Source::get_standard_source() );
	}

	/**
	 * Test the standard_source_is() method to see which option is selected by default (should be Custom Text)
	 *
	 * @return void
	 */
	public function test_standard_source_is() {
		$this->assertTrue( Standard_Source::standard_source_is( 'custom_text' ) );
	}

	/**
	 * Test the use_standard_source() method
	 * This should return false, because the standard source is not used for the given attachment
	 *
	 * @return void
	 */
	public function test_use_standard_source() {
		$this->assertEmpty( Standard_Source::use_standard_source( 123 ) );
	}

	/**
	 * Test the use_standard_source() method with the standard source enabled for the given attachment
	 *
	 * @return void
	 */
	public function test_use_standard_source_enabled() {
		update_post_meta( 123, 'isc_image_source_own', 1 );
		$this->assertTrue( Standard_Source::use_standard_source( 123 ) );
	}

	/**
	 * Test the get_standard_source_text_for_attachment() method
	 *
	 * @return void
	 */
	public function test_get_standard_source_text_for_attachment() {
		// The attachment is using the standard source
		update_post_meta( 123, 'isc_image_source_own', 1 );
		$this->assertEquals( '© http://isc.local', Standard_Source::get_standard_source_text_for_attachment( 123 ) );
	}

	/**
	 * Test the get_standard_source_text_for_attachment() method
	 * with the standard source set to author name
	 *
	 * @return void
	 */
	public function test_get_standard_source_text_for_attachment_post_author() {
		// create the author
		$this->user_id = $this->factory()->user->create( [ 'display_name' => 'John Doe' ] );

		// create the attachment post
		$image_id = $this->factory->post->create( [
			                                          'post_title'  => 'Image One',
			                                          'post_type'   => 'attachment',
			                                          'guid'        => 'https://example.com/image-one.png',
			                                          'post_author' => $this->user_id,
		                                          ] );

		// Remove existing post meta in case another test didn’t do proper cleanup. For some reason, update_post_meta() alone didn’t work
		delete_post_meta( $image_id, 'isc_image_source_own' );
		// The attachment is using the standard source
		update_post_meta( $image_id, 'isc_image_source_own', 1 );
		// Set the standard source to author name
		update_option( 'isc_options', [ 'standard_source' => 'author_name' ] );
		// Clear the options cache
		Standard_Source::clear_options();

		$this->assertEquals( 'John Doe', Standard_Source::get_standard_source_text_for_attachment( $image_id ) );
	}
}