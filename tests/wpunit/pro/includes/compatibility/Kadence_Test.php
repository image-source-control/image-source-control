<?php

namespace ISC\Tests\WPUnit;

use \ISC_Model;
use \ISC_Pro_Public;

/**
 * Test if ISC_Pro_Kadence provides compatibility with Kadence Blocks and Kadence Theme specific HTML.
 */
class Kadence_Test extends \Codeception\TestCase\WPTestCase {
	/**
	 * Test if ISC_Pro_Kadence::public_caption_regex() finds images that have a DIV container between A tag and IMG tag.
	 */
	public function test_public_caption_regex() {
		$markup   = '<figure class="alignleft"><a href="https://example.com/image.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/></div></a></figure>';
		$expected = [
			[
				0 => '<figure class="alignleft"><a href="https://example.com/image.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/>',
				1 => '<figure class="alignleft">',
				2 => 'alignleft',
				3 => '<a href="https://example.com/image.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/></div></a>',
				4 => '<a href="https://example.com/image.png">',
				5 => '',
				6 => '',
				7 => '<img src="https://example.com/image.png"/>',
				8 => 'https://example.com/image.png'
			],
		];
		$actual   = ISC_Model::extract_images_from_html( $markup );
		$this->assertEquals( $expected, $actual );
	}
}