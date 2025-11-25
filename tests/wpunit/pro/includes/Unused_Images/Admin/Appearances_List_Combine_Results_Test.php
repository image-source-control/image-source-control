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
	 * Test that position data from indexer is added to database post
	 *
	 * When a post exists in database results (without position)
	 * and also in indexed posts (with position), the position should be added to the existing post.
	 */
	public function test_preserves_position_from_indexer() {
		$indexed_posts = [
			(object) [
				'ID'         => 10,
				'post_title' => 'Test Post',
				'post_type'  => 'post',
				'position'   => 'thumbnail',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 10,
					'post_title' => 'Test Post',
					'post_type'  => 'post',
				],
			],
		];

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, [] );

		$this->assertCount( 1, $result['posts'], 'Should have only one post after deduplication' );
		$this->assertEquals( 10, $result['posts'][0]->ID );
		$this->assertEquals( 'thumbnail', $result['posts'][0]->position, 'Position from indexer should be added to database post' );
	}

	/**
	 * Test that database post search_type is preserved when merged with indexer
	 */
	public function test_preserves_search_type_from_database() {
		$indexed_posts = [
			(object) [
				'ID'         => 20,
				'post_title' => 'Test Post',
				'post_type'  => 'post',
				'position'   => 'content',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'          => 20,
					'post_title'  => 'Test Post',
					'post_type'   => 'post',
					'search_type' => 'id',
				],
			],
		];

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, [] );

		$this->assertCount( 1, $result['posts'] );
		$this->assertEquals( 'id', $result['posts'][0]->search_type, 'Should preserve search_type from database post' );
		$this->assertEquals( 'content', $result['posts'][0]->position, 'Should add position from indexed post' );
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
	 * Test merge order: database → indexer → image sources
	 *
	 * Verifies that:
	 * - Indexer can add position to database post
	 * - Image sources don't override existing entries (they're added last)
	 */
	public function test_merge_order_database_then_indexer_then_image_sources() {
		// Database post without position
		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 50,
					'post_title' => 'Shared Post',
					'post_type'  => 'post',
				],
			],
		];

		// Indexer post with position (same ID)
		$indexed_posts = [
			(object) [
				'ID'         => 50,
				'post_title' => 'Shared Post',
				'post_type'  => 'post',
				'position'   => 'body',
			],
		];

		// Image sources with same post
		$image_sources_index = [
			(object) [
				'ID'         => 50,
				'post_title' => 'Shared Post',
				'post_type'  => 'post',
			],
		];

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, $image_sources_index );

		$this->assertCount( 1, $result['posts'], 'Should have only one post after deduplication' );
		$this->assertEquals( 50, $result['posts'][0]->ID );
		$this->assertEquals( 'body', $result['posts'][0]->position, 'Position should be added from indexer to database post' );
	}

	/**
	 * Test with multiple posts and partial overlaps
	 */
	public function test_handles_partial_overlaps_between_sources() {
		$indexed_posts = [
			(object) [
				'ID'         => 70,
				'post_title' => 'Post A',
				'post_type'  => 'post',
				'position'   => 'content',
			],
			(object) [
				'ID'         => 71,
				'post_title' => 'Post B',
				'post_type'  => 'post',
				'position'   => 'thumbnail',
			],
		];

		$database_results = [
			'posts' => [
				(object) [
					'ID'         => 71,
					'post_title' => 'Post B',
					'post_type'  => 'post',
				],
				(object) [
					'ID'         => 72,
					'post_title' => 'Post C',
					'post_type'  => 'page',
				],
			],
		];

		$image_sources_index = [
			(object) [
				'ID'         => 72,
				'post_title' => 'Post C',
				'post_type'  => 'page',
			],
			(object) [
				'ID'         => 73,
				'post_title' => 'Post D',
				'post_type'  => 'post',
			],
		];

		$result = Appearances_List::combine_results( $indexed_posts, $database_results, $image_sources_index );

		$this->assertCount( 4, $result['posts'], 'Should have 4 unique posts (70, 71, 72, 73)' );
		
		// Find post 71 and verify it has position from indexer
		$post_b = array_values( array_filter( $result['posts'], function( $post ) {
			return $post->ID === 71;
		} ) )[0];
		
		$this->assertEquals( 'thumbnail', $post_b->position, 'Post B should have position added from indexer' );
	}
}