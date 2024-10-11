<?php

namespace ISC\Tests\WPUnit\Pro\Pblc;

use ISC\Tests\WPUnit\WPTestCase;
use \ISC_Pro_Public;

/**
 * Test if ISC_Pro_Public::render_source_url_html() works with various combinations of source URLs and source text.
 */
class Multiple_Links_In_Source_String_Test extends WPTestCase {
	public function setUp(): void {
		$this->renderer = new ISC_Pro_Public(); // Assuming the class containing the function is named 'Renderer'
		parent::setUp();
	}

	/**
	 * Test if render_source_url_html() returns the source text without change if no source URL is given.
	 */
	public function test_single_source_without_url() {
		$markup   = 'Example Source';
		$id       = 1;
		$metadata = [
			'source'     => 'Example Source',
			'source_url' => '',
		];

		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $markup, $actual );
	}

	/**
	 * Test if render_source_url_html() returns the source text wrapped in a link if a source URL is given.
	 */
	public function test_single_source_with_url() {
		$markup   = '<a href="https://example.com" target="_blank" rel="nofollow">Example Source</a>';
		$id       = 1;
		$metadata = [
			'source'     => 'Example Source',
			'source_url' => 'https://example.com',
		];

		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $markup, $actual );
	}

	/**
	 * Test if render_source_url_html() returns the source text without change if no source URL is given.
	 */
	public function test_multiple_sources_without_urls() {
		$markup   = 'Source 1, Source 2, Source 3';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Source 2, Source 3',
			'source_url' => ', , ',
		];

		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $markup, $actual );
	}

	/**
	 * Test if render_source_url_html() returns the source text wrapped in a link if a source URL is given.
	 */
	public function test_multiple_sources_with_urls() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Source 2, Source 3',
			'source_url' => 'https://example.com/1, https://example.com/2, https://example.com/3',
		];

		$expected = '<a href="https://example.com/1" target="_blank" rel="nofollow">Source 1</a>, <a href="https://example.com/2" target="_blank" rel="nofollow">Source 2</a>, <a href="https://example.com/3" target="_blank" rel="nofollow">Source 3</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test if render_source_url_html() wraps specific source texts in a link if some are not linked intentionally.
	 */
	public function test_mixed_sources() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Source 2, Source 3',
			'source_url' => 'https://example.com/1, , https://example.com/3',
		];

		$expected = '<a href="https://example.com/1" target="_blank" rel="nofollow">Source 1</a>, Source 2, <a href="https://example.com/3" target="_blank" rel="nofollow">Source 3</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test if render_source_url_html() skips the first source text link if the URL meta value starts with a comma
	 */
	public function test_empty_first() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Source 2, Source 3',
			'source_url' => ', https://example.com/2, https://example.com/3',
		];

		$expected = 'Source 1, <a href="https://example.com/2" target="_blank" rel="nofollow">Source 2</a>, <a href="https://example.com/3" target="_blank" rel="nofollow">Source 3</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test if render_source_url_html() skips the last source text link if the URL meta value ends with a comma
	 */
	public function test_empty_last() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Source 2, Source 3',
			'source_url' => 'https://example.com/1, https://example.com/2,',
		];

		$expected = '<a href="https://example.com/1" target="_blank" rel="nofollow">Source 1</a>, <a href="https://example.com/2" target="_blank" rel="nofollow">Source 2</a>, Source 3';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test if render_source_url_html() can cope with commas in URLs.
	 */
	public function test_commas_in_urls() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Source 2, Source 3',
			'source_url' => 'https://example.com/?values=one,two, , https://example.com/2',
		];

		$expected = '<a href="https://example.com/?values=one,two" target="_blank" rel="nofollow">Source 1</a>, Source 2, <a href="https://example.com/2" target="_blank" rel="nofollow">Source 3</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test if render_source_url_html() removes harmful code and HTML tags.
	 */
	public function test_harmful_code() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Source 2, Source 3',
			'source_url' => 'https://example.com/1, <script>alert("Hello");</script>, https://example.com/3',
		];

		$expected = '<a href="https://example.com/1,%20scriptalert(Hello);/script" target="_blank" rel="nofollow">Source 1</a>, <a href="https://example.com/3" target="_blank" rel="nofollow">Source 2</a>, Source 3';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}
}
