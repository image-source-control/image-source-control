<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Content_Scan;
use ISC\Pro\Unused_Images\Content_Scan_Table;
use ISC\Tests\WPUnit\WPTestCase;
use ISC\Pro\Unused_Images\Admin\Appearances_List;

/**
 * Test class for Appearances_List render() method.
 *
 * Tests the complete rendering functionality including:
 * - HTML structure generation
 * - Data source integration (indexer, database, image sources)
 * - Conditional content display
 */
class Appearances_List_Test extends WPTestCase {

	private $test_image_id;
	private $test_post_id;

	public function setUp(): void {
		parent::setUp();

		// Reset the index table to ensure a clean state for each test
		Content_Scan_Table::reset_oldest_entry_date_cache();

		// Create test attachment
		$this->test_image_id = $this->factory()->attachment->create( [
			                                                             'post_mime_type' => 'image/jpeg',
			                                                             'post_title'     => 'Test Image'
		                                                             ] );

		// Create test post
		$this->test_post_id = $this->factory()->post->create( [
			                                                      'post_title' => 'Test Post',
			                                                      'post_type'  => 'post'
		                                                      ] );

		// Enable details by default
		$this->set_details_option( true );
	}

	/**
	 * Helper to enable the details option
	 *
	 * @param bool $value
	 */
	private function set_details_option( $value ) {
		$options                                         = (array) get_option( 'isc_options' );
		$options['unused_images']['appearances_details'] = (bool) $value;
		update_option( 'isc_options', $options );
	}

	/**
	 * Test render() method when no method finds any usage data
	 *
	 * Expected output:
	 * — unchecked — (no other markup)
	 */
	public function test_render_without_results() {
		// Test when combine_results returns empty array
		ob_start();
		Appearances_List::render( $this->test_image_id );
		$output = ob_get_clean();

		// Should show "— unchecked —" and no other markup
		$this->assertEquals( '&mdash; unchecked &mdash;', $output );
	}

	/**
	 * Test render() method when image is truly unused (checked but no results found).
	 *
	 * Expected output:
	 * — unused —
	 * + check indicators
	 */
	public function test_render_shows_unused_when_checked_but_no_results() {
		// Set up a checked database with empty results (image was checked but nothing found)
		update_post_meta($this->test_image_id, 'isc_possible_usages', []);
		update_post_meta($this->test_image_id, 'isc_possible_usages_last_check', time());

		ob_start();
		Appearances_List::render( $this->test_image_id );
		$output = ob_get_clean();

		// Should show "— unused —" because database was checked but found nothing
		$this->assertEquals('&mdash; unused &mdash;', $output);
	}

	/**
	 * Test render() method with only data from Index_Table and entries in the position "content".
	 *
	 * Expected output:
	 * Combined List:
	 * - Post with Image 1
	 * - Post with Image 2
	 * <h4>Frontend check</h4>
	 * - Post with Image 1
	 * - Post with Image 2
	 * <h4>Database check</h4>
	 * –– unchecked -–
	 */
	public function test_render_with_indexer_results_in_content() {
		$post_with_image_1 = $this->factory()->post->create([
			                                                    'post_title' => 'Post with Image 1',
			                                                    'post_type' => 'post'
		                                                    ]);
		$post_with_image_2 = $this->factory()->post->create([
			                                                    'post_title' => 'Post with Image 2',
			                                                    'post_type' => 'page'
		                                                    ]);

		$index_table = new Content_Scan_Table();

		$index_table->insert_or_update($post_with_image_1, $this->test_image_id, 'content');
		$index_table->insert_or_update($post_with_image_2, $this->test_image_id, 'content');

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// Should contain indexer section header
		$this->assertStringContainsString('<h4>Frontend check</h4>', $output);

		// Should NOT show "no results" for indexer section
		$indexer_section_start = strpos($output, 'Frontend check');
		$database_section_start = strpos($output, 'Database check');
		$indexer_section = substr($output, $indexer_section_start, $database_section_start - $indexer_section_start);

		$this->assertStringNotContainsString('&mdash; no results &mdash;', $indexer_section);

		// strip line breaks from $output
		$output = preg_replace('/\s+/', ' ', $output);

		// The other sections are empty
		$this->assertStringContainsString('<h4>Database check</h4> &mdash; unchecked &mdash; </details>', $output);

		// individual results are given
		$this->assertStringContainsString('<a href="http://isc.local/?p=' . $post_with_image_1 . '" target="_blank">Post with Image 1</a> (Post)', $output);
		$this->assertStringContainsString('<a href="http://isc.local/?page_id=' . $post_with_image_2 . '" target="_blank">Post with Image 2</a> (Page)', $output);
	}

	/**
	 * Test render() method with only data from Index_Table and an entry in the position "thumbnail".
	 *
	 * Expected output:
	 * Combined List:
	 * - Test Post
	 * <h4>Frontend check</h4>
	 * - Test Post
	 * <h4>Database check</h4>
	 * — unchecked —
	 */
	public function test_render_with_indexer_results_in_thumbnail() {
		$index_table = new Content_Scan_Table();

		$index_table->insert_or_update( $this->test_post_id, $this->test_image_id, 'thumbnail' );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// Should contain indexer section header
		$this->assertStringContainsString('<h4>Frontend check</h4>', $output);

		// Should NOT show "no results" for indexer section
		$indexer_section_start = strpos($output, 'Frontend check');
		$database_section_start = strpos($output, 'Database check');
		$indexer_section = substr($output, $indexer_section_start, $database_section_start - $indexer_section_start);

		$this->assertStringNotContainsString('&mdash; no results &mdash;', $indexer_section);

		// strip line breaks from $output
		$output = preg_replace('/\s+/', ' ', $output);

		// The other sections are empty
		$this->assertStringContainsString('<h4>Database check</h4> &mdash; unchecked &mdash; </details>', $output);

		// individual results are given
		$this->assertStringContainsString('<a href="http://isc.local/?p=' . $this->test_post_id . '" target="_blank">Test Post</a> (Post)', $output);

	}

	/**
	 * Test render() method with only data from the database search.
	 *
	 * Expected output:
	 * Combined List:
	 * - Post with Image 1
	 * - Post with Image 2
	 * <h4>Frontend check</h4>
	 * — no results — (no posts found)
	 * <h4>Database check</h4>
	 * - Post with Image 1
	 * - Post with Image 2
	 */
	public function test_render_with_only_database_results() {
		// Create additional test posts
		$post_with_image_1 = $this->factory()->post->create([
			                                                    'post_title' => 'Post with Image 1',
			                                                    'post_type' => 'post'
		                                                    ]);

		$post_with_image_2 = $this->factory()->post->create([
			                                                    'post_title' => 'Post with Image 2',
			                                                    'post_type' => 'page'
		                                                    ]);

		// Prepare database results
		$database_results = [
			'posts' => [
				(object) [
					'ID' => $post_with_image_1,
					'post_title' => 'Post with Image 1',
					'post_type' => 'post'
				],
				(object) [
					'ID' => $post_with_image_2,
					'post_title' => 'Post with Image 2',
					'post_type' => 'page'
				]
			]
		];

		update_post_meta($this->test_image_id, 'isc_possible_usages', $database_results);
		update_post_meta($this->test_image_id, 'isc_possible_usages_last_check', time());

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// Should contain database section header
		$this->assertStringContainsString('<h4>Database check</h4>', $output);

		// Should NOT show "no results" for database section
		$database_section_start = strpos($output, 'Database check');

		if ($database_section_start !== false) {
			$indexer_section_start = strpos($output, 'Frontend check');
			if ($indexer_section_start !== false) {
				$database_section = substr($output, $database_section_start, strpos($output, '</details>', $database_section_start) - $database_section_start);
				$this->assertStringNotContainsString('&mdash; no results &mdash;', $database_section);
			}
		} else {
			// If the database section is not found, it means the test setup failed
			$this->fail('Database section not found in output');
		}

		// strip line breaks from $output
		$output = preg_replace('/\s+/', ' ', $output);

		// The other sections are empty
		$this->assertStringContainsString('<h4>Frontend check</h4> &mdash; no results &mdash; <h4>', $output);
	}

	/**
	 * Test render() method with database results containing only postmetas.
	 *
	 * Expected output:
	 * <h4>Database check</h4>
	 *    Post meta key: featured_image
	 *    Post meta key: gallery_images
	 */
	public function test_render_database_results_with_postmetas_only() {
		// Test database results with only postmetas, no posts
		$database_results = [
			'postmetas' => [
				(object) [
					'post_id'  => $this->test_post_id,
					'meta_key' => 'featured_image'
				],
				(object) [
					'post_id'  => $this->test_post_id,
					'meta_key' => 'gallery_images'
				]
			]
		];

		update_post_meta( $this->test_image_id, 'isc_possible_usages', $database_results );
		update_post_meta( $this->test_image_id, 'isc_possible_usages_last_check', time() );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// strip line breaks from $output
		$output = preg_replace( '/\s+/', ' ', $output );

		// Show results for the post meta
		$this->assertStringContainsString( '<h4>Database check</h4>', $output );
		$this->assertStringContainsString( 'Post meta key: featured_image', $output );
		$this->assertStringContainsString( 'Post meta key: gallery_images', $output );
	}

	/**
	 * Test render() method with database results containing only options.
	 *
	 * Expected output:
	 * <h4>Database check</h4>
	 *   Option: theme_logo (ID)
	 *   Option: header_background (URL)
	 */
	public function test_render_database_results_with_options_only() {
		// Test database results with only options, no posts
		$database_results = [
			'options' => [
				(object) [
					'option_name' => 'theme_logo',
					'search_type' => 'ID'
				],
				(object) [
					'option_name' => 'header_background',
					'search_type' => 'URL'
				]
			]
		];

		update_post_meta( $this->test_image_id, 'isc_possible_usages', $database_results );
		update_post_meta( $this->test_image_id, 'isc_possible_usages_last_check', time() );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// strip line breaks from $output
		$output = preg_replace( '/\s+/', ' ', $output );

		$this->assertStringContainsString( '<h4>Database check</h4>', $output );
		$this->assertStringContainsString( 'Option: theme_logo', $output );
		$this->assertStringContainsString( 'Option: header_background', $output );
	}

	/**
	 * Test render() method with complete database results structure.
	 *
	 * Expected output:
	 * - Combined results section with all data
	 * - Database section with posts, postmetas, and options
	 * - Should NOT show "no results" for database section
	 */
	public function test_render_database_results_with_all_sections() {
		// Test database results with posts, postmetas, and options
		$database_results = [
			'posts' => [
				(object) [
					'ID'         => $this->test_post_id,
					'post_title' => 'Database Post',
					'post_type'  => 'post'
				]
			],
			'postmetas' => [
				(object) [
					'post_id'  => $this->test_post_id,
					'meta_key' => 'featured_image'
				]
			],
			'options' => [
				(object) [
					'option_name' => 'theme_logo',
					'search_type' => 'ID'
				]
			]
		];

		update_post_meta( $this->test_image_id, 'isc_possible_usages', $database_results );
		update_post_meta( $this->test_image_id, 'isc_possible_usages_last_check', time() );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// Should contain database section header
		$this->assertStringContainsString( '<h4>Database check</h4>', $output );
	}

	/**
	 * Test render() method with a different post in all data sources: indexer, database search, and image sources index.
	 *
	 * This tests the integration of all three data sources working together.
	 *
	 * Expected output:
	 * <div class='isc-appearances-list-combined'>
	 *     Image Source Post (Post)
	 *     Indexer Post (Post)
	 *     Database Post (Post)
	 * </div>
	 * Details
	 *    <h4>Frontend check</h4>
	 *      Image Source Post (Post)
	 *    <h4>Database check</h4>
	 *      Database Post (Post)
	 *   <h4>Post Index (Image Sources)</h4>
	 *      Image Source Post (Post)
	 */
	public function test_render_with_different_post_in_all_data_sources() {
		// 1. Add indexer posts
		$indexer_post = $this->factory()->post->create([
			                                               'post_title' => 'Indexer Post',
			                                               'post_type' => 'post'
		                                               ]);

		$index_table = new Content_Scan_Table();
		$index_table->insert_or_update($indexer_post, $this->test_image_id, 'content');

		// 2. Add database results
		$database_results = [
			'posts' => [
				(object) [
					'ID' => $this->test_post_id,
					'post_title' => 'Database Post',
					'post_type' => 'post'
				]
			]
		];
		update_post_meta($this->test_image_id, 'isc_possible_usages', $database_results);
		update_post_meta($this->test_image_id, 'isc_possible_usages_last_check', time());

		// 3. Add image sources index
		$images_sources_post = $this->factory()->post->create([
			                                               'post_title' => 'Image Source Post',
			                                               'post_type' => 'post'
		                                               ]);
		update_post_meta($this->test_image_id, 'isc_image_posts', [$images_sources_post]);

		// Test render
		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// All sections should have content, not "no results"
		$this->assertStringContainsString('<h4>Frontend check</h4>', $output);
		$this->assertStringContainsString('<h4>Database check</h4>', $output);
		$this->assertStringContainsString('<h4>Post Index (Image Sources)</h4>', $output);

		// no unchecked or no-results
		$this->assertStringNotContainsString('&mdash; unchecked &mdash;', $output);
		$this->assertStringNotContainsString('&mdash; no results &mdash;', $output);

		// Combined results should contain data from all sources
		$this->assertStringContainsString("<div class='isc-appearances-list-combined'>", $output);

		// strip line breaks from $output
		$output = preg_replace('/\s+/', ' ', $output);

		// individual results are given
		$this->assertStringContainsString('<a href="http://isc.local/?p=' . $images_sources_post . '" target="_blank">Image Source Post</a> (Post)', $output);
		$this->assertStringContainsString('<a href="http://isc.local/?p=' . $indexer_post . '" target="_blank">Indexer Post</a> (Post)', $output);
		$this->assertStringContainsString('<a href="http://isc.local/?p=' . $this->test_post_id . '" target="_blank">Test Post</a> (Post)', $output);
	}

	/**
	 * Test render() method with the same post in all data sources: indexer, database search, and image sources index.
	 *
	 * Expected output:
	 * <div class='isc-appearances-list-combined'>
	 *     Image Source Post (Post)
	 * </div>
	 * Details
	 *    <h4>Frontend check</h4>
	 *      Image Source Post (Post)
	 *    <h4>Database check</h4>
	 *      Database Post (Post)
	 *   <h4>Post Index (Image Sources)</h4>
	 *      Image Source Post (Post)
	 */
	public function test_render_with_same_post_in_all_data_sources() {
		$index_table = new Content_Scan_Table();
		$index_table->insert_or_update( $this->test_post_id, $this->test_image_id, 'content');

		// 2. Add database results
		$database_results = [
			'posts' => [
				(object) [
					'ID' => $this->test_post_id,
					'post_title' => 'Database Post', // old format, should not matter
					'post_type' => 'post' // old format, should not matter
				]
			]
		];
		update_post_meta($this->test_image_id, 'isc_possible_usages', $database_results);
		update_post_meta($this->test_image_id, 'isc_possible_usages_last_check', time());

		// 3. Add image sources index
		update_post_meta($this->test_image_id, 'isc_image_posts', [$this->test_post_id]);

		// Test render
		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// All sections should have content
		$this->assertStringContainsString('<h4>Frontend check</h4>', $output);
		$this->assertStringContainsString('<h4>Database check</h4>', $output);
		$this->assertStringContainsString('<h4>Post Index (Image Sources)</h4>', $output);

		// no unchecked or no-results
		$this->assertStringNotContainsString('&mdash; unchecked &mdash;', $output);
		$this->assertStringNotContainsString('&mdash; no results &mdash;', $output);

		// strip line breaks from $output
		$output = preg_replace('/\s+/', ' ', $output);

		// the post line is found 4 times
		$expected_link = '<a href="http://isc.local/?p=' . $this->test_post_id . '" target="_blank">Test Post</a> (Post)';
		$actual_count = substr_count($output, $expected_link);

		$this->assertEquals(4, $actual_count, 'The post link should appear exactly 4 times in the output');
	}

	/**
	 * Test render() method with all possible datasets given in the database search.
	 *
	 * Expected output structure:
	 * <div class='isc-appearances-list-combined'>
	 *   [combined results content]
	 * </div>
	 * <details class="isc-appearances-list">
	 *   <summary>Details</summary>
	 *   <h4>Frontend check</h4>
	 *
	 *   <h4>Database check</h4>
	 * </details>
	 */
	public function test_render_with_full_database_information() {
		// Setup test data for all three sources

		$database_results = [
			'posts'     => [
				(object) [
					'ID'         => $this->test_post_id,
				]
			],
			'postmetas' => [
				(object) [
					'post_id'  => $this->test_post_id,
					'meta_key' => 'test_meta'
				]
			],
			'options'   => [
				(object) [
					'option_name' => 'test_option',
					'search_type' => 'ID'
				]
			]
		];

		update_post_meta( $this->test_image_id, 'isc_possible_usages', $database_results );
		update_post_meta( $this->test_image_id, 'isc_possible_usages_last_check', time() );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// Test section headers
		$this->assertStringContainsString( '<h4>Frontend check</h4>', $output );
		$this->assertStringContainsString( '<h4>Database check</h4>', $output );

		// strip linebreaks
		$output = preg_replace( '/\s+/', ' ', $output );

		// Test individual findings
		$this->assertStringContainsString( '<a href="http://isc.local/?p=' . $this->test_post_id . '" target="_blank">Test Post</a>', $output );
		$this->assertStringContainsString( 'Post meta key: test_meta', $output );
		$this->assertStringContainsString( 'Option: test_option', $output );
	}

	/**
	 * Test render() method with image sources index data.
	 *
	 * Expected output:
	 * <h4>Post Index (Image Sources)</h4>
	 *   Second Test Post (Page)
	 */
	public function test_render_with_image_sources_index_data() {
		// Test with image sources index data
		$second_post_id = $this->factory()->post->create( [
			                                                  'post_title' => 'Second Test Post',
			                                                  'post_type'  => 'page'
		                                                  ] );

		update_post_meta( $this->test_image_id, 'isc_image_posts', [ $this->test_post_id, $second_post_id ] );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// Should contain image sources section (assuming module is enabled)
		$this->assertStringContainsString( '<h4>Post Index (Image Sources)</h4>', $output );
		$this->assertStringContainsString( '<a href="http://isc.local/?p=' . $this->test_post_id. '" target="_blank">Test Post</a>', $output );
		$this->assertStringContainsString( '<a href="http://isc.local/?page_id=' . $second_post_id . '" target="_blank">Second Test Post</a>', $output );
	}

	/**
	 * Test render() method when image sources module is disabled while image source data is given
	 *
	 * Expected output:
	 *  simply "— unchecked —" since the module is disabled and other sections are empty
	 */
	public function test_render_with_image_sources_module_disabled() {
		// disable the image source module
		update_option( 'isc_options', [
			'modules' => [ 'unused_images' ]
		] );

		// Add image sources index data
		update_post_meta( $this->test_image_id, 'isc_image_posts', [ $this->test_post_id ] );

		ob_start();
		Appearances_List::render( $this->test_image_id );
		$output = ob_get_clean();

		// Nothing returned
		$this->assertEquals( '&mdash; unchecked &mdash;', $output );
	}

	/**
	 * Test render() method output structure and section order.
	 *
	 * Expected order:
	 * 1. Combined results section
	 * 2. Details opening tag
	 * 3. Frontend check
	 * 4. Database check
	 * 5. Related posts from Image Sources Index (if enabled)
	 * 6. Details closing tag
	 * 7. Check indicators
	 */
	public function test_render_output_structure_order() {
		// Test the order of output sections
		$database_results = [
			'posts' => [
				(object) [
					'ID'         => $this->test_post_id,
				]
			]
		];

		update_post_meta( $this->test_image_id, 'isc_possible_usages', $database_results );
		update_post_meta( $this->test_image_id, 'isc_possible_usages_last_check', time() );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details', 'checks' ] );
		$output = ob_get_clean();

		// Test the order of sections in output
		$combined_pos = strpos( $output, "isc-appearances-list-combined" );
		$details_pos = strpos( $output, '<details class="isc-appearances-list">' );
		$indexer_pos = strpos( $output, 'Frontend check' );
		$database_pos = strpos( $output, 'Database check' );
		$checks_pos = strpos( $output, 'class="isc-check-indicator isc-check-indexer"' );

		// Combined results should come first, then details section
		$this->assertLessThan( $details_pos, $combined_pos );
		// Within details, indexer section should come before database section
		$this->assertLessThan( $database_pos, $indexer_pos );
		// Check indicators should come after the details section
		$this->assertGreaterThan( $details_pos, $checks_pos );
	}

	/**
	 * Test if details are missing when the option is disabled
	 */
	public function test_render_without_details_option() {
		// Disable the details option
		$this->set_details_option( false );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// Should not contain details section
		$this->assertStringNotContainsString( '<details class="isc-appearances-list">', $output );

		// Should show "— unchecked —" since no details are available
		$this->assertEquals( '&mdash; unchecked &mdash;', $output );
	}

	/**
	 * Test if the check indicators are rendered
	 */
	public function test_render_with_check_indicators() {
		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'checks' ] );
		$output = ob_get_clean();

		// Should contain check indicators
		$this->assertStringContainsString( 'class="isc-check-indicator isc-check-indexer"', $output );
		$this->assertStringContainsString( 'class="isc-check-indicator isc-check-database"', $output );
		// Checks are showing "no", because they didn’t run
		$this->assertStringContainsString( '<span class="dashicons dashicons-no-alt" title="Database check"></span>', $output );
		$this->assertStringContainsString( '<span class="dashicons dashicons-no-alt" title="Frontend check"></span>', $output );
	}

	/**
	 * Test check indicators with Database check run
	 */
	public function test_render_with_check_indicators_database_check_run() {
		// Simulate a database check run
		update_post_meta( $this->test_image_id, 'isc_possible_usages_last_check', time() );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'checks' ] );
		$output = ob_get_clean();

		// Should show "yes" for database check
		$this->assertStringContainsString( '<span class="dashicons dashicons-yes" title="Database check"></span>', $output );
		// Frontend check should still show "no"
		$this->assertStringContainsString( '<span class="dashicons dashicons-no-alt" title="Frontend check"></span>', $output );
	}

	/**
	 * Test check indicators with valid Indexer data
	 */
	public function test_render_with_check_indicators_indexer_data() {
		$index_table = new Content_Scan_Table();
		$index_table->insert_or_update( $this->test_post_id, $this->test_image_id, 'content' );

		Content_Scan_Table::reset_oldest_entry_date_cache();

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'checks' ] );
		$output = ob_get_clean();

		// Should show "yes" for indexer check
		$this->assertStringContainsString( '<span class="dashicons dashicons-yes" title="Frontend check"></span>', $output );
		// Database check should still show "no"
		$this->assertStringContainsString( '<span class="dashicons dashicons-no-alt" title="Database check"></span>', $output );
	}

	/**
	 * Test that render() shows the global indicator when image is probably global
	 */
	public function test_render_shows_global_indicator_for_global_images() {
		$index_table = new Content_Scan_Table();
		$threshold = Content_Scan::get_global_threshold(); // Default is 4

		// Add threshold + 1 entries with head/body positions to trigger global status
		for ( $i = 0; $i <= $threshold; $i++ ) {
			$post_id = $this->factory()->post->create( [ 'post_title' => "Post $i" ] );
			$position = ( $i % 2 === 0 ) ? 'head' : 'body';
			$index_table->insert_or_update( $post_id, $this->test_image_id, $position );
		}

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// Should contain the global indicator
		$this->assertStringContainsString( 'isc-probably-global-indicator', $output, 'Should show global indicator CSS class' );

		// Should show the threshold value in the message
		$this->assertStringContainsString( "Found more than $threshold times", $output, 'Should display threshold value in message' );

		// Should include link to documentation
		$this->assertStringContainsString( 'Manual', $output, 'Should include Manual link' );
	}

	/**
	 * Test that render() does NOT show the global indicator for normal images
	 */
	public function test_render_hides_global_indicator_for_normal_images() {
		$index_table = new Content_Scan_Table();

		// Add only content/thumbnail entries (not global)
		$post_id_1 = $this->factory()->post->create();
		$post_id_2 = $this->factory()->post->create();
		$index_table->insert_or_update( $post_id_1, $this->test_image_id, 'content' );
		$index_table->insert_or_update( $post_id_2, $this->test_image_id, 'thumbnail' );

		ob_start();
		Appearances_List::render( $this->test_image_id, [ 'details' ] );
		$output = ob_get_clean();

		// Should NOT contain the global indicator
		$this->assertStringNotContainsString( 'isc-probably-global-indicator', $output, 'Should not show global indicator for normal images' );
		$this->assertStringNotContainsString( 'Found more than', $output, 'Should not show "Found more than" message' );
	}

	/**
	 * Test is_probably_global() returns false when image has no index entries
	 */
	public function test_is_probably_global_returns_false_with_no_entries() {
		$result = Appearances_List::is_probably_global( $this->test_image_id );

		$this->assertFalse( $result, 'Should return false when image has no index entries' );
	}

	/**
	 * Test is_probably_global() returns false when image only has content/thumbnail positions
	 */
	public function test_is_probably_global_returns_false_with_only_content_positions() {
		$index_table = new Content_Scan_Table();

		// Add entries with content and thumbnail positions (not global)
		$post_id_1 = $this->factory()->post->create();
		$post_id_2 = $this->factory()->post->create();

		$index_table->insert_or_update( $post_id_1, $this->test_image_id, 'content' );
		$index_table->insert_or_update( $post_id_2, $this->test_image_id, 'thumbnail' );

		$result = Appearances_List::is_probably_global( $this->test_image_id );

		$this->assertFalse( $result, 'Should return false when image only has content/thumbnail positions' );
	}

	/**
	 * Test is_probably_global() returns false when head/body entries are at or below threshold
	 */
	public function test_is_probably_global_returns_false_at_threshold() {
		$index_table = new Content_Scan_Table();
		$threshold = Content_Scan::get_global_threshold(); // Default is 4

		// Add exactly threshold number of head/body entries
		for ( $i = 0; $i < $threshold; $i++ ) {
			$post_id = $this->factory()->post->create();
			$position = ( $i % 2 === 0 ) ? 'head' : 'body';
			$index_table->insert_or_update( $post_id, $this->test_image_id, $position );
		}

		$result = Appearances_List::is_probably_global( $this->test_image_id );

		$this->assertFalse( $result, 'Should return false when head/body count equals threshold' );
	}

	/**
	 * Test is_probably_global() returns true when head/body entries exceed threshold
	 */
	public function test_is_probably_global_returns_true_above_threshold() {
		$index_table = new Content_Scan_Table();
		$threshold = Content_Scan::get_global_threshold(); // Default is 4

		// Add threshold + 1 entries with head/body positions
		for ( $i = 0; $i <= $threshold; $i++ ) {
			$post_id = $this->factory()->post->create();
			$position = ( $i % 2 === 0 ) ? 'head' : 'body';
			$index_table->insert_or_update( $post_id, $this->test_image_id, $position );
		}

		$result = Appearances_List::is_probably_global( $this->test_image_id );

		$this->assertTrue( $result, 'Should return true when head/body count exceeds threshold' );
	}

	/**
	 * Test is_probably_global() only counts head/body positions, not content/thumbnail
	 */
	public function test_is_probably_global_only_counts_head_and_body_positions() {
		$index_table = new Content_Scan_Table();

		// Add many content/thumbnail entries (should not count toward global)
		for ( $i = 0; $i < 10; $i++ ) {
			$post_id = $this->factory()->post->create();
			$position = ( $i % 2 === 0 ) ? 'content' : 'thumbnail';
			$index_table->insert_or_update( $post_id, $this->test_image_id, $position );
		}

		// Add only 2 head entries (below threshold)
		$post_id_1 = $this->factory()->post->create();
		$post_id_2 = $this->factory()->post->create();
		$index_table->insert_or_update( $post_id_1, $this->test_image_id, 'head' );
		$index_table->insert_or_update( $post_id_2, $this->test_image_id, 'body' );

		$result = Appearances_List::is_probably_global( $this->test_image_id );

		$this->assertFalse( $result, 'Should only count head/body positions, not content/thumbnail' );
	}
}