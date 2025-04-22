<?php
namespace ISC\Tests\WPUnit;

use ISC\Tests\WPUnit\WPTestCase;

class RunnerVersion_Test extends WPTestCase {

	/**
	 * Verifies that the interpreter defined by the bash script
	 * (`ISC_EXPECT_PHP_VERSION`) is really running this test.
	 */
	public function test_runner_uses_expected_php_version() {

		$expected = getenv( 'ISC_EXPECT_PHP_VERSION' );

		// Variable is only set when the matrix script runs
		if ( ! $expected ) {
			$this->markTestSkipped(
				'ISC_EXPECT_PHP_VERSION not set – test is executed only ' .
				'inside php‑matrix.sh.'
			);
		}

		// `php-matrix.sh` passes a full patch version (e.g. "7.4.33"),
		// therefore an exact string comparison is sufficient.
		$this->assertStringStartsWith(
			$expected,
			PHP_VERSION,
			sprintf(
				'Runner should use PHP %s.x, but actually runs %s.',
				$expected,
				PHP_VERSION
			)
		);
	}
}
