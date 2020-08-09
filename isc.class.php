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
		protected $fields = array(
			'image_source'     => array(
				'id'      => 'isc_image_source',
				'default' => '',
			),
			'image_source_url' => array(
				'id'      => 'isc_image_source_url',
				'default' => '',
			),
			'image_source_own' => array(
				'id'      => 'isc_image_source_own',
				'default' => '',
			),
			'image_posts'      => array(
				'id'      => 'isc_image_posts',
				'default' => array(),
			),
			'image_licence'    => array(
				'id'      => 'isc_image_licence',
				'default' => '',
			),
		);

		/**
		 * Allowed image file types/extensions
		 *
		 * @var array allowed image extensions.
		 */
		public $allowed_extensions = array(
			'jpg',
			'png',
			'gif',
			'jpeg',
		);

		/**
		 * Thumbnail size in list of all images.
		 *
		 * @var array available thumbnail sizes.
		 */
		protected $thumbnail_size = array( 'thumbnail', 'medium', 'large', 'custom' );

		/**
		 * Options saved in the db
		 *
		 * @var array plugin options.
		 */
		protected $options = array();

		/**
		 * Position of image's caption
		 *
		 * @var array available positions for the image source overlay.
		 */
		protected $caption_position = array(
			'top-left',
			'top-center',
			'top-right',
			'center',
			'bottom-left',
			'bottom-center',
			'bottom-right',
		);

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
		 * Setup registers filters and actions.
		 */
		public function __construct() {
			// load all plugin options
			$this->options  = get_option( 'isc_options' );
			self::$instance = $this;
			$this->model    = new ISC_Model();

			/**
			 * Register actions to update missing sources checks each time attachments’ post meta is updated
			 *
			 * See the "updated_post_meta" action hook
			 */
			add_action( 'updated_post_meta', array( $this, 'maybe_update_attachment_post_meta' ), 10, 3 );

			/**
			 * Clear post-image index whenever the content of a post is updated
			 * this could force reindexing the post after adding or removing image sources
			 */
			add_action( 'wp_insert_post', array( 'ISC_Model', 'clear_post_images_index' ) );
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
			$srcs = array();
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
		 *   Returns default options
		 */
		public function default_options() {

				$licences = 'All Rights Reserved
Public Domain Mark 1.0|https://creativecommons.org/publicdomain/mark/1.0/
CC0 1.0 Universal|https://creativecommons.org/publicdomain/zero/1.0/
CC BY 4.0 International|https://creativecommons.org/licenses/by/4.0/
CC BY-SA 4.0 International|https://creativecommons.org/licenses/by-sa/4.0/
CC BY-ND 4.0 International|https://creativecommons.org/licenses/by-nd/4.0/
CC BY-NC 4.0 International|https://creativecommons.org/licenses/by-nc/4.0/
CC BY-NC-SA 4.0 International|https://creativecommons.org/licenses/by-nc-sa/4.0/
CC BY-NC-ND 4.0 International|https://creativecommons.org/licenses/by-nc-nd/4.0/
CC BY 3.0 Unported|https://creativecommons.org/licenses/by/3.0/
CC BY-SA 3.0 Unported|https://creativecommons.org/licenses/by-sa/3.0/
CC BY-ND 3.0 Unported|https://creativecommons.org/licenses/by-nd/3.0/
CC BY-NC 3.0 Unported|https://creativecommons.org/licenses/by-nc/3.0/
CC BY-NC-SA 3.0 Unported|https://creativecommons.org/licenses/by-nc-sa/3.0/
CC BY-NC-ND 3.0 Unported|https://creativecommons.org/licenses/by-nc-nd/3.0/
CC BY 2.5 Generic|https://creativecommons.org/licenses/by/2.5/
CC BY-SA 2.5 Generic|https://creativecommons.org/licenses/by-sa/2.5/
CC BY-ND 2.5 Generic|https://creativecommons.org/licenses/by-nd/2.5/
CC BY-NC 2.5 Generic|https://creativecommons.org/licenses/by-nc/2.5/
CC BY-NC-SA 2.5 Generic|https://creativecommons.org/licenses/by-nc-sa/2.5/
CC BY-NC-ND 2.5 Generic|https://creativecommons.org/licenses/by-nc-nd/2.5/
CC BY 2.0 Generic|https://creativecommons.org/licenses/by/2.0/
CC BY-SA 2.0 Generic|https://creativecommons.org/licenses/by-sa/2.0/
CC BY-ND 2.0 Generic|https://creativecommons.org/licenses/by-nd/2.0/
CC BY-NC 2.0 Generic|https://creativecommons.org/licenses/by-nc/2.0/
CC BY-NC-SA 2.0 Generic|https://creativecommons.org/licenses/by-nc-sa/2.0/
CC BY-NC-ND 2.0 Generic|https://creativecommons.org/licenses/by-nc-nd/2.0/';

			$default['display_type']              = array( 'list' );
			$default['list_on_archives']          = false;
			$default['list_on_excerpts']          = false;
			$default['image_list_headline']       = __( 'image sources', 'image-source-control-isc' );
			$default['exclude_own_images']        = false;
			$default['use_authorname']            = true;
			$default['by_author_text']            = __( 'Owned by the author', 'image-source-control-isc' );
			$default['version']                   = ISCVERSION;
			$default['thumbnail_in_list']         = false;
			$default['thumbnail_size']            = 'thumbnail';
			$default['thumbnail_width']           = 150;
			$default['thumbnail_height']          = 150;
			$default['warning_onesource_missing'] = true;
			$default['remove_on_uninstall']       = false;
			$default['hide_list']                 = false;
			$default['caption_position']          = 'top-left';
			$default['source_pretext']            = __( 'Source:', 'image-source-control-isc' );
			$default['enable_licences']           = false;
			$default['licences']                  = apply_filters( 'isc-licences-list', $licences );
			return $default;
		}

		/**
		 * Returns isc_options if it exists, returns the default options otherwise.
		 */
		public function get_isc_options() {
			return get_option( 'isc_options', $this->default_options() );
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
			$new_licences = array();
			foreach ( $licences_array as $_licence ) {
				if ( trim( $_licence ) !== '' ) {
					$temp                     = explode( '|', $_licence );
					$new_licences[ $temp[0] ] = array();
					if ( isset( $temp[1] ) ) {
						$new_licences[ $temp[0] ]['url'] = esc_url( $temp[1] );
					}
				}
			}

			if ( $new_licences === array() ) {
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
			if ( in_array( $meta_key, array( 'isc_image_source_own', 'isc_image_source' ), true ) ) {
				ISC_Model::update_missing_sources_transient();
			}
		}
}
