<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images;

use ISC\Pro\Unused_Images\Database_Check_Model;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing \ISC\Pro\Unused_Images\Database_Check_Model::search_in_options()
 */
class Database_Check_Model_Search_In_Options_Test extends WPTestCase {

	/**
	 * Test that search_in_options() returns results from both the file path and attachment ID checks
	 * This is also covered in Database_Check_Model_Search_Attachment_Id_In_Options_Test and Database_Check_Model_Search_File_Path_In_Options_Test
	 */
	public function test_combines_results_from_id_and_path() {
		$attachment_id = rand( 10000, 99999 );
		$search_string = 'test-image';

		update_option( 'option_with_id', $attachment_id );
		update_option( 'option_with_path', 'some text https://example.com/' . $search_string . '.png' );

		$result       = ( new Database_Check_Model() )->search_in_options( $search_string, $attachment_id );
		$option_names = wp_list_pluck( $result, 'option_name' );

		$this->assertContains( 'option_with_id', $option_names );
		$this->assertContains( 'option_with_path', $option_names );
	}

	/**
	 * Test that search_in_options() respects the ignored options filter
	 */
	public function test_respects_ignored_options_filter() {
		$attachment_id = rand( 10000, 99999 );
		$search_string = 'another-image';

		update_option( 'ignored_option', 'https://example.com/' . $search_string . '.jpg' );
		update_option( 'other_option', $attachment_id );

		add_filter( 'isc_unused_images_ignored_options', function( array $options ) {
			$options[] = 'ignored_option';

			return $options;
		} );

		$result       = ( new Database_Check_Model() )->search_in_options( $search_string, $attachment_id );
		$option_names = wp_list_pluck( $result, 'option_name' );

		$this->assertNotContains( 'ignored_option', $option_names );
		$this->assertContains( 'other_option', $option_names );

		remove_all_filters( 'isc_unused_images_ignored_options' );
	}

	/**
	 * Test that the ignored options support regular expressions
	 */
	public function test_respects_ignored_options_filter_regular_expression() {
		$attachment_id = rand( 10000, 99999 );
		$search_string = 'another-image';

		update_option( 'ignored_option', 'https://example.com/' . $search_string . '.jpg' );

		add_filter( 'isc_unused_images_ignored_options', function( array $options ) {
			$options[] = '/ignored_.*/';

			return $options;
		} );

		$result       = ( new Database_Check_Model() )->search_in_options( $search_string, $attachment_id );
		$option_names = wp_list_pluck( $result, 'option_name' );

		$this->assertNotContains( 'ignored_option', $option_names );

		remove_all_filters( 'isc_unused_images_ignored_options' );
	}
}