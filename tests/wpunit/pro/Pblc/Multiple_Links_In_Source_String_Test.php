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

	/**
	 * Test that colons inside URLs (http://) are not treated as separators.
	 */
	public function test_url_protocol_colon_not_split() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Source 2',
			'source_url' => 'https://example.com/1, https://example.com/2',
		];

		$expected = '<a href="https://example.com/1" target="_blank" rel="nofollow">Source 1</a>, <a href="https://example.com/2" target="_blank" rel="nofollow">Source 2</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test that forward slashes inside URLs are not treated as separators.
	 */
	public function test_url_path_slash_not_split() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Source 2',
			'source_url' => 'https://example.com/path/to/resource, https://example.com/another/path',
		];

		$expected = '<a href="https://example.com/path/to/resource" target="_blank" rel="nofollow">Source 1</a>, <a href="https://example.com/another/path" target="_blank" rel="nofollow">Source 2</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test that pipe separators with spaces work correctly.
	 */
	public function test_pipe_separator_with_space() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1| Source 2 | Source 3',
			'source_url' => 'https://example.com/1| https://example.com/2 | https://example.com/3',
		];

		$expected = '<a href="https://example.com/1" target="_blank" rel="nofollow">Source 1</a>| <a href="https://example.com/2" target="_blank" rel="nofollow">Source 2</a> | <a href="https://example.com/3" target="_blank" rel="nofollow">Source 3</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test that colon separators with spaces work correctly.
	 */
	public function test_colon_separator_with_space() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Image Source Control: Features : Contact',
			'source_url' => 'https://imagesourcecontrol.com: https://imagesourcecontrol.com/features : https://imagesourcecontrol.com/contact',
		];

		$expected = '<a href="https://imagesourcecontrol.com" target="_blank" rel="nofollow">Image Source Control</a>: <a href="https://imagesourcecontrol.com/features" target="_blank" rel="nofollow">Features</a> : <a href="https://imagesourcecontrol.com/contact" target="_blank" rel="nofollow">Contact</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test that slash separators with spaces work correctly.
	 */
	public function test_slash_separator_with_space() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1/ Source 2 / Source 3',
			'source_url' => 'https://example.com/1/ https://example.com/2 / https://example.com/3',
		];

		$expected = '<a href="https://example.com/1" target="_blank" rel="nofollow">Source 1</a>/ <a href="https://example.com/2" target="_blank" rel="nofollow">Source 2</a> / <a href="https://example.com/3" target="_blank" rel="nofollow">Source 3</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test that pipe without space is not treated as a separator.
	 */
	public function test_pipe_without_space_not_split() {
		$markup   = 'Foo|Bar';
		$id       = 1;
		$metadata = [
			'source'     => 'Foo|Bar',
			'source_url' => 'https://example.com',
		];

		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $markup, $actual );
	}

	/**
	 * Test that colon without space is not treated as a separator.
	 */
	public function test_colon_without_space_not_split() {
		$markup   = 'Foo:Bar';
		$id       = 1;
		$metadata = [
			'source'     => 'Foo:Bar',
			'source_url' => 'https://example.com',
		];

		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $markup, $actual );
	}

	/**
	 * Test that slash without space is not treated as a separator.
	 */
	public function test_slash_without_space_not_split() {
		$markup   = 'Foo/Bar';
		$id       = 1;
		$metadata = [
			'source'     => 'Foo/Bar',
			'source_url' => 'https://example.com',
		];

		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $markup, $actual );
	}

	/**
	 * Test mixed separators (comma and colon with space).
	 */
	public function test_mixed_separators_comma_and_colon() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => 'Source 1, Features : Contact',
			'source_url' => 'https://example.com/1, https://example.com/features : https://example.com/contact',
		];

		$expected = '<a href="https://example.com/1" target="_blank" rel="nofollow">Source 1</a>, <a href="https://example.com/features" target="_blank" rel="nofollow">Features</a> : <a href="https://example.com/contact" target="_blank" rel="nofollow">Contact</a>';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test that whitespace is preserved around linked sources.
	 */
	public function test_whitespace_preservation_around_links() {
		$markup   = '';
		$id       = 1;
		$metadata = [
			'source'     => '  Source 1  ,  Source 2  ',
			'source_url' => '  https://example.com/1  ,  https://example.com/2  ',
		];

		$expected = '  <a href="https://example.com/1" target="_blank" rel="nofollow">Source 1</a>  ,  <a href="https://example.com/2" target="_blank" rel="nofollow">Source 2</a>  ';
		$actual   = $this->renderer->render_source_url_html( $markup, $id, $metadata );
		$this->assertEquals( $expected, $actual );
	}
}
