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
		protected $allowed_extensions = array(
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
			$this->model = new ISC_Model();
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
		 * Filter image ids from text
		 *
		 * @param string $content post content.
+        * @return array with image ids => image src uri-s
+        */
		public function filter_image_ids( $content = '' ) {
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
						$matched = false;
					if ( null !== $node->attributes->getNamedItem( 'class' ) ) {
						if ( preg_match( '#.*wp-image-(\d+?).*#U', $node->attributes->getNamedItem( 'class' )->textContent, $matches ) ) {
								$srcs[ intval( $matches[1] ) ] = $node->attributes->getNamedItem( 'src' )->textContent;
								$matched                       = true;
						}
					}
					if ( ! $matched ) {
						if ( null !== $node->attributes->getNamedItem( 'src' ) ) {
							$url = $node->attributes->getNamedItem( 'src' )->textContent;
							// get ID of images by url
							$id = $this->get_image_by_url( $url );
							if ( $id ) {
									$srcs[ $id ] = $url;
							}
						}
					}
				}
			}

				return $srcs;
		}

		/**
		 * Get image by url accessing the database directly
		 *
		 * @since 1.1
		 * @updated 1.1.3
		 * @param string $url url of the image.
		 * @return integer ID of the image.
		 */
		public function get_image_by_url( $url = '' ) {
			global $wpdb;

			if ( empty( $url ) ) {
				return 0;
			}
			$types = implode( '|', $this->allowed_extensions );
			/**
			 * Check for the format 'image-title-(e12452112-)300x200.jpg(?query…)' and removes
			 *   the image size
			 *   edit marks
			 *   additional query vars
			 */
			$newurl = esc_url( preg_replace( "/(-e\d+){0,1}(-\d+x\d+){0,1}\.({$types})(.*)/i", '.${3}', $url ) );

			// remove protocoll (http or https)
			$url    = str_ireplace( array( 'http:', 'https:' ), '', $url );
			$newurl = str_ireplace( array( 'http:', 'https:' ), '', $newurl );

			// not escaped, because escaping already happened above
			$raw_query = $wpdb->prepare(
				"SELECT ID FROM `$wpdb->posts` WHERE post_type='attachment' AND guid = %s OR guid = %s OR guid = %s OR guid = %s LIMIT 1",
				"http:$url",
				"https:$url",
				"http:$newurl",
				"https:$newurl"
			);

			$query = apply_filters( 'isc_get_image_by_url_query', $raw_query, $newurl );
			$id    = $wpdb->get_var( $query );

			return intval( $id );
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
			$default['warning_nosource']          = true;
			$default['warning_onesource_missing'] = true;
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
}
