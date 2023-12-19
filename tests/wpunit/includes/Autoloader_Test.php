<?php
/**
 * The class is responsible for locating and loading the autoloader file used in the plugin.
 */
namespace ISC\Tests\WPUnit;

use ISC\Autoloader;

class Autoloader_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the get_autoloader() method
	 *
	 * @return void
	 */
	public function test_get_autoloader() {
		$this->assertInstanceOf( Autoloader::class, Autoloader::get() );
	}

	/**
	 * Test if this is correctly identifying the test environment
	 */
	public function test_is_test_environment() {
		$this->assertTrue( Autoloader::get()->is_test() );
	}

	/**
	 * Test the get_directory() method
	 *
	 * @return void
	 */
	public function test_get_directory() {
		$this->assertEquals( ISCPATH, Autoloader::get()->get_directory() );
	}


}