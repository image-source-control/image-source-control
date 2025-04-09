<?php

namespace ISC\Image_Sources;

use ISC\Plugin;
use ISC_Model;

/**
 * Main class to handle image attribution logic
 */
class Image_Sources {
	/**
	 * Allowed image file types/extensions
	 *
	 * @var array allowed image extensions.
	 */
	public $allowed_extensions = [
		'jpg',
		'png',
		'gif',
		'jpeg',
		'webp',
		'avif',
	];

	/**
	 * Thumbnail size in list of all images.
	 *
	 * @var array available thumbnail sizes.
	 */
	protected static $thumbnail_size = [ 'thumbnail', 'medium', 'large', 'custom' ];

	/**
	 * Options saved in the db
	 *
	 * @var array plugin options.
	 */
	protected $options = [];

	/**
	 * Instance of Image_Sources.
	 *
	 * @var Image_Sources
	 */
	protected static $instance;

	/**
	 * Instance of ISC_Model.
	 *
	 * @var ISC_Model
	 */
	protected $model;

	/**
	 * Get instance of Image_Sources
	 *
	 * @return Image_Sources
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Helper to extract information from HTML
	 *
	 * @var Analyze_HTML
	 */
	public $html_analyzer;

	/**
	 * Setup registers filters and actions.
	 */
	public function __construct() {
		self::$instance      = $this;
		$this->model         = new ISC_Model();
		$this->html_analyzer = new Analyze_HTML();

		/**
		 * Register actions to update missing sources checks each time attachments’ post meta is updated
		 *
		 * See the "updated_post_meta" action hook
		 */
		add_action( 'updated_post_meta', [ $this, 'maybe_update_attachment_post_meta' ], 10, 3 );

		/**
		 * Register actions to update missing sources checks each time attachments’ post meta is added
		 *
		 * See the "added_post_meta" action hook
		 */
		add_action( 'added_post_meta', [ $this, 'maybe_update_attachment_post_meta' ], 10, 3 );

		/**
		 * Register actions to update missing sources when an attachment was added
		 */
		add_action( 'add_attachment', [ 'ISC_Model', 'update_missing_sources_transient' ] );

		/**
		 * Register an action to update missing sources when an attachment was deleted
		 */
		add_action( 'deleted_post', [ 'ISC_Model', 'update_missing_sources_transient' ] );

		/**
		 * Update index when a post is deleted or moved into trash
		 */
		add_action( 'before_delete_post', [ '\ISC\Indexer', 'handle_post_deletion' ] );
		add_action( 'wp_trash_post', [ '\ISC\Indexer', 'handle_post_deletion' ] );

		/**
		 * Clear post-image index whenever the content of a single post is updated and move the content to a temporary post meta
		 * this could force reindexing the post after adding or removing image sources
		 */
		add_action( 'wp_insert_post', [ '\ISC\Indexer', 'prepare_for_reindex' ] );
	}

	/**
	 * Control if we are dynamically checking if sources are missing each time attachments are updated
	 * using the "updated_{$meta_type}_meta" hook
	 *
	 * @param int    $meta_id     ID of updated metadata entry.
	 * @param int    $object_id   ID of the object metadata is for.
	 * @param string $meta_key    Metadata key.
	 */
	public function maybe_update_attachment_post_meta( $meta_id, $object_id, $meta_key ) {
		if ( in_array( $meta_key, [ 'isc_image_source_own', 'isc_image_source' ], true ) ) {
			ISC_Model::update_missing_sources_transient();
		}
	}

	/**
	 * Get image source string for public output
	 *
	 * @param int $attachment_id attachment ID.
	 * @return string
	 */
	public static function get_image_source_text( $attachment_id ) {
		return apply_filters(
			'isc_public_attachment_get_source',
			trim(
				get_post_meta( $attachment_id, 'isc_image_source', true )
			)
		);
	}

	/**
	 * Get image source string before it was filtered for output
	 *
	 * @param int $attachment_id attachment ID.
	 * @return string
	 */
	public static function get_image_source_text_raw( $attachment_id ) {
		return apply_filters(
			'isc_raw_attachment_get_source',
			get_post_meta( $attachment_id, 'isc_image_source', true ),
			$attachment_id
		);
	}

	/**
	 * Get image source URL before it was filtered for output
	 *
	 * @param int $attachment_id attachment ID.
	 * @return string
	 */
	public static function get_image_source_url( $attachment_id ) {
		return apply_filters(
			'isc_raw_attachment_get_source_url',
			get_post_meta( $attachment_id, 'isc_image_source_url', true ),
			$attachment_id
		);
	}

	/**
	 * Get image license value before it was filtered for output
	 *
	 * @param int $attachment_id attachment ID.
	 * @return string
	 */
	public static function get_image_license( $attachment_id ) {
		return apply_filters(
			'isc_raw_attachment_get_license',
			get_post_meta( $attachment_id, 'isc_image_licence', true ),
			$attachment_id
		);
	}

	/**
	 * Get image title
	 *
	 * @param integer|string $attachment_id attachment ID.
	 * @return string
	 */
	public static function get_image_title( $attachment_id ) {
		return apply_filters(
			'isc_raw_attachment_title',
			get_the_title( $attachment_id ),
			$attachment_id
		);
	}

	/**
	 * Get plugin options
	 * A wrapper for backward compatibility
	 *
	 * @return string[]
	 */
	public function get_options() {
		return Plugin::get_options();
	}

	/**
	 * Return thumbnail sizes
	 *
	 * @return array|string[] []
	 */
	public static function get_thumbnail_sizes() {
		return self::$thumbnail_size;
	}
}
