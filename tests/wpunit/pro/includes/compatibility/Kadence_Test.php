<?php

namespace ISC\Tests\WPUnit;
use \ISC_Model;

include_once dirname( __FILE__, 4 ) . '/model/Extract_Images_From_Html_Test.php';
/**
 * Test if ISC_Pro_Kadence provides compatibility with Kadence Blocks and Kadence Theme specific HTML.
 * This test is based on the Extract_Images_From_Html_Test class to see if all previous patters also work
 */
class Kadence_Test extends Extract_Images_From_Html_Test {

	public function setUp(): void {
		parent::setUp();
		// adjust the general regular expression to also search for DIVs between the image and the link tag.
		add_filter( 'isc_public_caption_regex', [ 'ISC_Pro_Compatibility_Kadence', 'public_caption_regex' ] );
		// filter the matches from the regular expression to apply some fixes.
		add_filter( 'isc_extract_images_from_html', [ 'ISC_Pro_Compatibility_Kadence', 'filter_matches' ], 10, 2 );
	}

	/**
	 * Test if ISC_Pro_Kadence::public_caption_regex() finds images that have a DIV container between A tag and IMG tag.
	 */
	public function test_public_caption_regex() {
		$markup   = '<figure class="alignleft"><a href="https://example.com/image.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/></div></a></figure>';
		$expected = [
			[
				'full' => '<figure class="alignleft"><a href="https://example.com/image.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/></div></a>',
				'figure_class' => 'alignleft',
				'inner_code' => '<a href="https://example.com/image.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/></div></a>',
				'img_src' => 'https://example.com/image.png',
			],
		];
		$actual   = ISC_Model::extract_images_from_html( $markup );
		$this->assertEquals( $expected, $actual );
	}
}