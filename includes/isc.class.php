<?php

use ISC\Image_Sources\Analyze_HTML;

/**
 * Main controller of ISC
 *
 * @deprecated since 3.0.0 Use ISC\Image_Sources\Image_Sources or ISC\Plugin instead
 */
class ISC_Class {

		/**
		 * Define default meta fields
		 *
		 * @var array option fields.
		 */
		protected $fields = [
			'image_source'     => [
				'id'      => 'isc_image_source',
				'default' => '',
			],
			'image_source_url' => [
				'id'      => 'isc_image_source_url',
				'default' => '',
			],
			'image_source_own' => [
				'id'      => 'isc_image_source_own',
				'default' => '',
			],
			'image_posts'      => [
				'id'      => 'isc_image_posts',
				'default' => [],
			],
			'image_licence'    => [
				'id'      => 'isc_image_licence',
				'default' => '',
			],
		];

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
		];

		/**
		 * Thumbnail size in list of all images.
		 *
		 * @var array available thumbnail sizes.
		 */
		protected $thumbnail_size = [ 'thumbnail', 'medium', 'large', 'custom' ];

		/**
		 * Options saved in the db
		 *
		 * @var array plugin options.
		 */
		protected $options = [];

		/**
		 * Instance of ISC_Class.
		 *
		 * @var ISC_Class
		 */
		protected static $instance;

		/**
		 * Instance of ISC_Model.
		 *
		 * @var ISC_Model
		 */
		protected $model;

		/**
		 * Get instance of ISC_Class
		 *
		 * @return ISC_Class
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
		}

		/**
		 * Returns default options
		 *
		 * @return string[]
		 */
		public function default_options() {
			_deprecated_function( __METHOD__, '3.0.0', 'ISC\Options::default_options' );

			return ISC\Options::default_options();
		}

		/**
		 * Returns isc_options if it exists, returns the default options otherwise.
		 *
		 * @return string[]
		 */
		public function get_isc_options() {
			_deprecated_function( __METHOD__, '3.0.0', 'ISC\Options::get_options' );

			$this->options = get_option( 'isc_options', $this->default_options() );

			return $this->options;
		}

		/**
		 * Transform the licenses from the options textfield into an array
		 *
		 * @param string $licences text with licenses.
		 * @return array|bool $new_licences array with licenses and license information or false if no array created.
		 * @since 1.3.5
		 */
		public function licences_text_to_array( $licences = '' ) {
			_deprecated_function( __METHOD__, '3.0.0', 'ISC\Image_Sources\Image_Sources::license_text_to_array' );

			if ( $licences === '' ) {
				return false;
			}
			// split the text by line
			$licences_array = preg_split( '/\r?\n/', trim( $licences ) );
			if ( count( $licences_array ) === 0 ) {
				return false;
			}
			// create the array with licence => url
			$new_licences = [];
			foreach ( $licences_array as $_licence ) {
				if ( trim( $_licence ) !== '' ) {
					$temp                     = explode( '|', $_licence );
					$new_licences[ $temp[0] ] = [];
					if ( isset( $temp[1] ) ) {
						$new_licences[ $temp[0] ]['url'] = esc_url( $temp[1] );
					}
				}
			}

			if ( $new_licences === [] ) {
				return false;
			} else {
				return $new_licences;
			}
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
			_deprecated_function( __METHOD__, '3.0.0', 'ISC\Image_Sources\Image_Sources::maybe_update_attachment_post_meta' );

			if ( in_array( $meta_key, [ 'isc_image_source_own', 'isc_image_source' ], true ) ) {
				ISC_Model::update_missing_sources_transient();
			}
		}

		/**
		 * Get image source string before it was filtered for output
		 *
		 * @param int $attachment_id attachment ID.
		 * @return string
		 */
		public static function get_image_source_text( $attachment_id ) {
			_deprecated_function( __METHOD__, '3.0.0', 'ISC\Image_Sources\Image_Sources::get_image_source_text' );

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
			_deprecated_function( __METHOD__, '3.0.0', 'ISC\Image_Sources\Image_Sources::get_image_source_url' );

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
			_deprecated_function( __METHOD__, '3.0.0', 'ISC\Image_Sources\Image_Sources::get_image_license' );

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
			_deprecated_function( __METHOD__, '3.0.0', 'ISC\Image_Sources\Image_Sources::get_image_title' );

			return apply_filters(
				'isc_raw_attachment_title',
				get_the_title( $attachment_id ),
				$attachment_id
			);
		}
}
