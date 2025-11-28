<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Admin\Appearances_List;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Unit tests for Appearances_List::combine_results() method
 *
 * Focuses specifically on deduplication logic and data preservation
 * when merging posts from different sources (indexer, database search, image sources index).
 */
class Appearances_List_Combine_Results_Test extends WPTestCase {

	/**
	 * Test that posts from all three sources are combined into a single array
	 */
	public function test_combines_posts_from_all_sources() {
		$indexed_posts = [
			(object) [
				'ID'         => 1,
				'post_title' => 'Indexed Post',
				'post_type'  => 'post',
				'position'   => 'content',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 2,
					'post_title' => 'Database Post',
					'post_type'  => 'post',
				],
			],
		];

		$image_sources_index = [
			(object) [
				'ID'         => 3,
				'post_title' => 'Image Source Post',
				'post_type'  => 'page',
			],
		];

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, $image_sources_index );

		$this->assertCount( 3, $result['posts'], 'Should combine all posts from three sources' );
		$this->assertEquals( 2, $result['posts'][0]->ID, 'Database post should be first' );
		$this->assertEquals( 1, $result['posts'][1]->ID, 'Indexed post should be second' );
		$this->assertEquals( 3, $result['posts'][2]->ID, 'Image source post should be third' );
	}

	/**
	 * Test that duplicate posts (same ID) are deduplicated
	 */
	public function test_deduplicates_posts_by_id() {
		$indexed_posts = [
			(object) [
				'ID'         => 5,
				'post_title' => 'Test Post',
				'post_type'  => 'post',
				'position'   => 'content',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 5,
					'post_title' => 'Test Post',
					'post_type'  => 'post',
				],
			],
		];

		$image_sources_index = [
			(object) [
				'ID'         => 5,
				'post_title' => 'Test Post',
				'post_type'  => 'post',
			],
		];

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, $image_sources_index );

		$this->assertCount( 1, $result['posts'], 'Should deduplicate posts with the same ID' );
		$this->assertEquals( 5, $result['posts'][0]->ID );
	}

	/**
	 * Test that postmetas, options, usermetas are not affected by post deduplication
	 */
	public function test_preserves_non_post_data_structures() {
		$indexed_posts = [
			(object) [
				'ID'         => 30,
				'post_title' => 'Test Post',
				'post_type'  => 'post',
				'position'   => 'content',
			],
		];

		$database_results = [
			'posts'     => [
				(object) [
					'ID'         => 30,
					'post_title' => 'Test Post',
					'post_type'  => 'post',
				],
			],
			'postmetas' => [
				(object) [
					'post_id'  => 30,
					'meta_key' => 'featured_image',
				],
			],
			'options'   => [
				(object) [
					'option_name' => 'site_logo',
					'search_type' => 'ID',
				],
			],
			'usermetas' => [
				(object) [
					'user_id'   => 1,
					'meta_key'  => 'profile_picture',
					'user_name' => 'admin',
				],
			],
		];

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, [] );

		$this->assertCount( 1, $result['posts'], 'Should deduplicate posts' );
		$this->assertCount( 1, $result['postmetas'], 'Should preserve postmetas' );
		$this->assertCount( 1, $result['options'], 'Should preserve options' );
		$this->assertCount( 1, $result['usermetas'], 'Should preserve usermetas' );
		$this->assertEquals( 'featured_image', $result['postmetas'][0]->meta_key );
		$this->assertEquals( 'site_logo', $result['options'][0]->option_name );
		$this->assertEquals( 1, $result['usermetas'][0]->user_id );
	}

	/**
	 * Test with empty arrays from all sources
	 */
	public function test_handles_all_empty_sources() {
		$result = Appearances_List::combine_results( [], [], [] );

		$this->assertIsArray( $result, 'Should return an array' );
		$this->assertEmpty( $result, 'Should return empty array when all sources are empty' );
	}

	/**
	 * Test with only database results having non-post data
	 */
	public function test_handles_database_results_without_posts() {
		$database_results = [
			'postmetas' => [
				(object) [
					'post_id'  => 40,
					'meta_key' => 'gallery_images',
				],
			],
			'options'   => [
				(object) [
					'option_name' => 'header_background',
					'search_type' => 'URL',
				],
			],
		];

		$result = Appearances_List::combine_results( [], $database_results, [] );

		$this->assertArrayNotHasKey( 'posts', $result, 'Should not have posts key when no posts exist' );
		$this->assertCount( 1, $result['postmetas'], 'Should preserve postmetas' );
		$this->assertCount( 1, $result['options'], 'Should preserve options' );
	}

	/**
	 * Test that indexed_posts input array is not modified (no side effects)
	 *
	 * Critical test for Pro issue #333: The function must not modify input parameters by reference
	 */
	public function test_does_not_modify_indexed_posts_input() {
		$indexed_posts = [
			(object) [
				'ID'         => 10,
				'post_title' => 'Indexed Post',
				'post_type'  => 'post',
				'position'   => 'content',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 10,
					'post_title' => 'Database Post',
					'post_type'  => 'post',
				],
			],
		];

		// Store original values before calling combine_results
		$original_count = count( $indexed_posts );
		$original_id    = $indexed_posts[0]->ID;
		$original_title = $indexed_posts[0]->post_title;

		Appearances_List::combine_results( $indexed_posts, $database_results, [] );

		// Verify indexed_posts array was not modified
		$this->assertCount( $original_count, $indexed_posts, 'indexed_posts array count should not change' );
		$this->assertEquals( $original_id, $indexed_posts[0]->ID, 'Post ID should not change' );
		$this->assertEquals( $original_title, $indexed_posts[0]->post_title, 'Post title should not change' );
		$this->assertObjectHasProperty( 'position', $indexed_posts[0], 'position property should still exist' );
		$this->assertEquals( 'content', $indexed_posts[0]->position, 'position value should not change' );
	}

	/**
	 * Test that database_results input array is not modified (no side effects)
	 *
	 * Critical test for Pro issue #333: The function must not modify input parameters by reference
	 */
	public function test_does_not_modify_database_results_input() {
		$indexed_posts = [
			(object) [
				'ID'         => 20,
				'post_title' => 'Indexed Post',
				'post_type'  => 'post',
				'position'   => 'featured',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 20,
					'post_title' => 'Database Post',
					'post_type'  => 'post',
				],
			],
		];

		// Store original values
		$original_post_count = count( $database_results['posts'] );
		$original_post       = clone $database_results['posts'][0];

		Appearances_List::combine_results( $indexed_posts, $database_results, [] );

		// Verify database_results was not modified
		$this->assertCount( $original_post_count, $database_results['posts'], 'posts array count should not change' );
		$this->assertEquals( $original_post->ID, $database_results['posts'][0]->ID, 'Post ID should not change' );
		$this->assertEquals( $original_post->post_title, $database_results['posts'][0]->post_title, 'Post title should not change' );
		$this->assertObjectNotHasProperty( 'position', $database_results['posts'][0], 'position property should not be added to original object' );
	}

	/**
	 * Test that image_sources_index input array is not modified (no side effects)
	 *
	 * Critical test for Pro issue #333: The function must not modify input parameters by reference
	 */
	public function test_does_not_modify_image_sources_index_input() {
		$indexed_posts = [
			(object) [
				'ID'         => 25,
				'post_title' => 'Indexed Post',
				'post_type'  => 'post',
				'position'   => 'gallery',
			],
		];

		$image_sources_index = [
			(object) [
				'ID'         => 25,
				'post_title' => 'Image Source Post',
				'post_type'  => 'page',
			],
		];

		// Store original values
		$original_count = count( $image_sources_index );
		$original_post  = clone $image_sources_index[0];

		Appearances_List::combine_results( $indexed_posts, [], $image_sources_index );

		// Verify image_sources_index was not modified
		$this->assertCount( $original_count, $image_sources_index, 'image_sources_index array count should not change' );
		$this->assertEquals( $original_post->ID, $image_sources_index[0]->ID, 'Post ID should not change' );
		$this->assertEquals( $original_post->post_title, $image_sources_index[0]->post_title, 'Post title should not change' );
		$this->assertObjectNotHasProperty( 'position', $image_sources_index[0], 'position property should not be added to original object' );
	}

	/**
	 * Test that position attribute is only present for posts from indexed_posts
	 *
	 * This verifies that position is NOT copied to posts that originated from database_results.
	 * Position should only be present if the post came from indexed_posts originally.
	 */
	public function test_position_only_for_indexed_posts() {
		$indexed_posts = [
			(object) [
				'ID'         => 15,
				'post_title' => 'Indexed Post',
				'post_type'  => 'post',
				'position'   => 'thumbnail',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 15,
					'post_title' => 'Database Post',
					'post_type'  => 'post',
				],
			],
		];

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, [] );

		// When a post exists in both sources, database post takes precedence
		// Position should NOT be copied to the database post
		$this->assertCount( 1, $result['posts'], 'Should have only one post after deduplication' );
		$this->assertObjectNotHasProperty( 'position', $result['posts'][0], 'Database post should NOT have position property added from indexed post' );

		// Original database_results should also remain unchanged
		$this->assertObjectNotHasProperty( 'position', $database_results['posts'][0], 'Original database post should not have position property added' );
	}

	/**
	 * Test that position is preserved for posts that only exist in indexed_posts
	 */
	public function test_position_preserved_for_indexed_only_posts() {
		$indexed_posts = [
			(object) [
				'ID'         => 20,
				'post_title' => 'Indexed Only Post',
				'post_type'  => 'post',
				'position'   => 'content',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 21,
					'post_title' => 'Database Post',
					'post_type'  => 'post',
				],
			],
		];

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, [] );

		// Should have both posts
		$this->assertCount( 2, $result['posts'], 'Should have both posts' );

		// Find the indexed post in results (order: database first, then indexed)
		$indexed_result = null;
		foreach ( $result['posts'] as $post ) {
			if ( $post->ID === 20 ) {
				$indexed_result = $post;
				break;
			}
		}

		$this->assertNotNull( $indexed_result, 'Indexed post should be in results' );
		$this->assertObjectHasProperty( 'position', $indexed_result, 'Indexed-only post should retain position property' );
		$this->assertEquals( 'content', $indexed_result->position, 'Position value should be preserved' );
	}

	/**
	 * Test that non-post data structures in database_results are not modified
	 */
	public function test_does_not_modify_database_results_postmetas_options_usermetas() {
		$database_results = [
			'posts'     => [
				(object) [
					'ID'         => 50,
					'post_title' => 'Test Post',
					'post_type'  => 'post',
				],
			],
			'postmetas' => [
				(object) [
					'post_id'  => 50,
					'meta_key' => 'test_meta',
				],
			],
			'options'   => [
				(object) [
					'option_name' => 'test_option',
					'search_type' => 'ID',
				],
			],
			'usermetas' => [
				(object) [
					'user_id'   => 2,
					'meta_key'  => 'test_user_meta',
					'user_name' => 'testuser',
				],
			],
		];

		// Store original counts
		$original_postmetas_count = count( $database_results['postmetas'] );
		$original_options_count   = count( $database_results['options'] );
		$original_usermetas_count = count( $database_results['usermetas'] );

		Appearances_List::combine_results( [], $database_results, [] );

		// Verify counts haven't changed
		$this->assertCount( $original_postmetas_count, $database_results['postmetas'], 'postmetas should not be modified' );
		$this->assertCount( $original_options_count, $database_results['options'], 'options should not be modified' );
		$this->assertCount( $original_usermetas_count, $database_results['usermetas'], 'usermetas should not be modified' );

		// Verify objects are still the same
		$this->assertEquals( 50, $database_results['postmetas'][0]->post_id );
		$this->assertEquals( 'test_meta', $database_results['postmetas'][0]->meta_key );
		$this->assertEquals( 'test_option', $database_results['options'][0]->option_name );
		$this->assertEquals( 2, $database_results['usermetas'][0]->user_id );
	}

	/**
	 * Test with multiple posts that some should be deduplicated and originals remain unchanged
	 */
	public function test_complex_scenario_with_multiple_posts_and_no_side_effects() {
		$indexed_posts = [
			(object) [
				'ID'         => 100,
				'post_title' => 'Post 100',
				'post_type'  => 'post',
				'position'   => 'content',
			],
			(object) [
				'ID'         => 101,
				'post_title' => 'Post 101',
				'post_type'  => 'post',
				'position'   => 'featured',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 100,
					'post_title' => 'Post 100 DB',
					'post_type'  => 'post',
				],
				(object) [
					'ID'         => 102,
					'post_title' => 'Post 102',
					'post_type'  => 'page',
				],
			],
		];

		$image_sources_index = [
			(object) [
				'ID'         => 101,
				'post_title' => 'Post 101 ISI',
				'post_type'  => 'post',
			],
			(object) [
				'ID'         => 103,
				'post_title' => 'Post 103',
				'post_type'  => 'page',
			],
		];

		// Store originals
		$original_indexed_count = count( $indexed_posts );
		$original_db_count      = count( $database_results['posts'] );
		$original_isi_count     = count( $image_sources_index );

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, $image_sources_index );

		// Verify result is correct (4 unique posts: 100, 101, 102, 103)
		$this->assertCount( 4, $result['posts'], 'Should have 4 unique posts after deduplication' );

		// Verify all input arrays remain unchanged
		$this->assertCount( $original_indexed_count, $indexed_posts, 'indexed_posts count should not change' );
		$this->assertCount( $original_db_count, $database_results['posts'], 'database_results posts count should not change' );
		$this->assertCount( $original_isi_count, $image_sources_index, 'image_sources_index count should not change' );

		// Verify no position property was added to original database or image source objects
		$this->assertObjectNotHasProperty( 'position', $database_results['posts'][0], 'Database post should not have position added' );
		$this->assertObjectNotHasProperty( 'position', $image_sources_index[0], 'Image source post should not have position added' );

		// Verify indexed_posts still have their position property unchanged
		$this->assertEquals( 'content', $indexed_posts[0]->position, 'indexed_posts position should remain unchanged' );
		$this->assertEquals( 'featured', $indexed_posts[1]->position, 'indexed_posts position should remain unchanged' );
	}
}