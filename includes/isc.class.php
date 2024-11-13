<?php
/**
 * Main controller of ISC
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
		 * @var ISC\Analyze_HTML
		 */
		public $html_analyzer;

		/**
		 * Setup registers filters and actions.
		 */
		public function __construct() {
			self::$instance      = $this;
			$this->model         = new ISC_Model();
			$this->html_analyzer = new ISC\Analyze_HTML();

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
			 * Clear post-image index whenever the content of a single post is updated
			 * this could force reindexing the post after adding or removing image sources
			 */
			add_action( 'wp_insert_post', [ 'ISC_Model', 'clear_single_post_images_index' ] );

			/**
			 * Fire when a post or page was updated
			 */
			add_action( 'post_updated', [ 'ISC_Model', 'update_image_post_meta' ], 10, 3 );
		}

		/**
		 * Filter image src attribute from text
		 *
		 * @since 1.1
		 * @updated 1.1.3
		 * @deprecated since 1.9 use filter_image_ids instead
		 *
		 * @param string $content post content.
		 * @return array with image src uri-s
		 */
		public function filter_src_attributes( $content = '' ) {
			$srcs = [];
			if ( empty( $content ) ) {
				return $srcs;
			}

			// parse HTML with DOM
			$dom = new DOMDocument();

			libxml_use_internal_errors( true );
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$content = mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' );
			}
			$dom->loadHTML( $content );

			// Prevents from sending E_WARNINGs notice (Outputs are forbidden during activation)
			libxml_clear_errors();

			foreach ( $dom->getElementsByTagName( 'img' ) as $node ) {
				if ( isset( $node->attributes ) ) {
					if ( null !== $node->attributes->getNamedItem( 'src' ) ) {
						$srcs[] = $node->attributes->getNamedItem( 'src' )->textContent;
					}
				}
			}

			return $srcs;
		}

		/**
		 * Returns default options
		 *
		 * @return string[]
		 */
		public function default_options() {
			include ISCPATH . 'includes/default-licenses.php';
			$default['display_type']              = [ 'list' ];
			$default['list_on_archives']          = false;
			$default['list_on_excerpts']          = false;
			$default['image_list_headline']       = __( 'image sources', 'image-source-control-isc' );
			$default['version']                   = ISCVERSION;
			$default['images_per_page']           = 99999;
			$default['thumbnail_in_list']         = false;
			$default['thumbnail_size']            = 'thumbnail';
			$default['thumbnail_width']           = 150;
			$default['thumbnail_height']          = 150;
			$default['warning_onesource_missing'] = true;
			$default['remove_on_uninstall']       = false;
			$default['caption_position']          = 'top-left';
			$default['caption_style']             = null;
			$default['source_pretext']            = __( 'Source:', 'image-source-control-isc' );
			$default['enable_licences']           = false;
			$default['licences']                  = apply_filters( 'isc-licences-list', $isc_default_licenses );
			$default['list_included_images']      = '';
			$default['overlay_included_images']   = '';
			$default['block_options']             = true;
			$default['enable_log']                = false;
			$default['standard_source']           = 'custom_text';
			$default['standard_source_text']      = '';

			/**
			 * Allow manipulating defaults for plugin settings
			 */
			return apply_filters( 'isc_default_settings', $default );
		}

		/**
		 * Returns isc_options if it exists, returns the default options otherwise.
		 *
		 * @return string[]
		 */
		public function get_isc_options() {
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
}
