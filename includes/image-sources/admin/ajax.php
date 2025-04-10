<?php

namespace ISC\Image_Sources;

use ISC\Image_Sources\Post_Meta\Image_Posts_Meta;
use ISC\Image_Sources\Post_Meta\Post_Images_Meta;

/**
 * Handle AJAX calls
 */
class Admin_Ajax {
	use \ISC\Options;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_isc-post-image-relations', [ $this, 'list_post_image_relations' ] );
		add_action( 'wp_ajax_isc-image-post-relations', [ $this, 'list_image_post_relations' ] );
		add_action( 'wp_ajax_isc-clear-index', [ $this, 'clear_index' ] );
		add_action( 'wp_ajax_isc-show-storage', [ $this, 'show_storage' ] );
		add_action( 'wp_ajax_isc-clear-storage', [ $this, 'clear_storage' ] );
		add_action( 'wp_ajax_isc-clear-image-posts-index', [ $this, 'clear_image_posts_index' ] );
		add_action( 'wp_ajax_isc-clear-post-images-index', [ $this, 'clear_post_images_index' ] );
	}

	/**
	 * List post-images (images associated with a specidic post ID)
	 * Called using AJAX
	 */
	public function list_post_image_relations() {

		// get all meta fields
		$args              = [
			'posts_per_page' => - 1,
			'post_status'    => null,
			'post_parent'    => null,
			'post_type'      => 'any',
			'meta_query'     => [
				[
					'key' => 'isc_post_images',
				],
			],
		];
		$posts_with_images = new \WP_Query( $args );

		if ( $posts_with_images->have_posts() ) {
			require_once ISCPATH . '/admin/templates/post-images-list.php';
		} else {
			die( esc_html__( 'No entries found', 'image-source-control-isc' ) );
		}

		wp_reset_postdata();

		die();
	}

	/**
	 * List post image relations (called with ajax)
	 */
	public function list_image_post_relations() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		// get all images
		$args              = [
			'post_type'      => 'attachment',
			'posts_per_page' => - 1,
			'post_status'    => 'inherit',
			'meta_query'     => [
				[
					'key' => 'isc_image_posts',
				],
			],
		];
		$images_with_posts = new \WP_Query( $args );

		if ( $images_with_posts->have_posts() ) {
			require_once ISCPATH . '/admin/templates/image-posts-list.php';
		} else {
			die( esc_html__( 'No entries found', 'image-source-control-isc' ) );
		}

		wp_reset_postdata();

		die();
	}

	/**
	 * Callback to clear all image-post relations
	 */
	public function clear_index() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		if ( \ISC\Indexer::clear_index() ) {
			wp_send_json_success( esc_html__( 'Index cleared', 'image-source-control-isc' ) );
		} else {
			wp_send_json_error( 'Error' );
		}
	}

	/**
	 * Show the storage array for debugging
	 */
	public function show_storage() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		$storage_model = new \ISC_Storage_Model();
		$images        = $storage_model->get_storage();

		// We are in debug mode, so it is fine to just output the content
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		print_r( $images );

		die();
	}

	/**
	 * Callback to clear all image-post relations
	 */
	public function clear_storage() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		\ISC_Storage_Model::clear_storage();

		die( esc_html__( 'Storage deleted', 'image-source-control-isc' ) );
	}

	/**
	 * Callback to clear the isc_image_posts post meta
	 */
	public function clear_image_posts_index() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		if ( ! isset( $_POST['image_id'] ) ) {
			die( 'No image ID given' );
		}

		$image_id = (int) $_POST['image_id'];
		$deleted  = Image_Posts_Meta::delete( $image_id );

		if ( $deleted ) {
			wp_send_json_success( 'Image-Posts index cleared' );
		} else {
			wp_send_json_success( 'Image-Posts index cleared (or did not exist)' );
		}
	}

	/**
	 * Callback to clear the isc_post_images post meta
	 */
	public function clear_post_images_index() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		if ( ! isset( $_POST['post_id'] ) ) {
			die( 'No post ID given' );
		}

		$post_id = (int) $_POST['post_id'];
		$deleted = Post_Images_Meta::delete( $post_id );

		if ( $deleted ) {
			wp_send_json_success( 'Post-Images index cleared' );
		} else {
			wp_send_json_success( 'Post-Images index cleared (or did not exist)' );
		}
	}
}
