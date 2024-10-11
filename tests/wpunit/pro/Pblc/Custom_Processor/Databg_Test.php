<?php

namespace ISC\Tests\WPUnit\Pro\Pblc\Custom_Processor;

use ISC\Tests\WPUnit\WPTestCase;
use ISC\Pro\Custom_Attribute_Processor;
use \ISC_Public;

/**
 * Test adding custom HTML rules using Custom_Attribute_Processor.php
 */
class Databg_Test extends WPTestCase {

	/**
	 * @var ISC_Public
	 */
	protected $isc_public;

	/**
	 * @var $image_id
	 *
	 * @return int
	 */
	protected $image_id;


	public function setUp(): void {
		parent::setUp();

		$this->image_id = $this->factory()->post->create( [
			                                                  'post_title' => 'Image One',
			                                                  'post_type'  => 'attachment',
			                                                  'guid'       => 'https://example.com/image.jpg',
		                                                  ] );

		add_post_meta( $this->image_id, 'isc_image_source', 'Author A' );

		$this->isc_public = new ISC_Public();
	}

	/**
	 * Test if a span with a data-bgsrc attribute is correctly processed without showing the caption
	 */
	public function test_span_data_bgsrc() {
		// register the rule
		new Custom_Attribute_Processor(
			'#<span[\x20|\x9|\xD|\xA]+[^>]*((data-bgsrc)="(.+)").*\/?>#isU',
			1,
			3,
			false
		);

		$markup = '<span data-bgsrc="https://example.com/image.jpg"></span>';
		$expected = '<span data-bgsrc="https://example.com/image.jpg" data-isc-source-text="Source: Author A"></span>';

		$actual = $this->isc_public->add_source_captions_to_content( $markup );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test if a span with a data-bgsrc attribute is correctly processed and the caption is shown
	 */
	public function test_span_data_bgsrc_with_caption() {
		// register the rule
		new Custom_Attribute_Processor(
			'#<span[\x20|\x9|\xD|\xA]+[^>]*((data-bgsrc)="(.+)").*\/?>#isU',
			1,
			3,
			true
		);

		$markup = '<span data-bgsrc="https://example.com/image.jpg"></span>';
		$expected = '<span data-bgsrc="https://example.com/image.jpg" data-isc-source-text="Source: Author A" data-isc-images="' . $this->image_id . '"></span>';

		$actual = $this->isc_public->add_source_captions_to_content( $markup );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test if an img tag with a data-image attribute is correctly processed and the caption is shown
	 */
	public function test_img_data_image_with_caption() {
		// register the rule
		new Custom_Attribute_Processor(
			'#<img[\x20|\x9|\xD|\xA]+[^>]*((data-img)="(.+)").*\/?>#isU',
			1,
			3,
			true
		);

		$markup = '<img data-img="https://example.com/image.jpg">';
		$expected = '<img data-img="https://example.com/image.jpg" data-isc-source-text="Source: Author A" data-isc-images="' . $this->image_id . '">';

		$actual = $this->isc_public->add_source_captions_to_content( $markup );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test if a tag that matches two rules only shows the markup once
	 */
	public function test_two_rules_one_match() {
		// register the rule
		new Custom_Attribute_Processor(
			'#<span[\x20|\x9|\xD|\xA]+[^>]*((data-bgsrc)="(.+)").*\/?>#isU',
			1,
			3,
			true
		);

		// register the rule
		new Custom_Attribute_Processor(
			'#<span[\x20|\x9|\xD|\xA]+[^>]*((data-src)="(.+)").*\/?>#isU',
			1,
			3,
			true
		);

		$markup = '<span data-bgsrc="https://example.com/image.jpg" data-src="https://example.com/image.jpg"></span>';
		$expected = '<span data-bgsrc="https://example.com/image.jpg" data-isc-source-text="Source: Author A" data-isc-images="' . $this->image_id . '" data-src="https://example.com/image.jpg"></span>';

		$actual = $this->isc_public->add_source_captions_to_content( $markup );
		$this->assertEquals( $expected, $actual );
	}
}