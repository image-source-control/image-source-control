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
			$this->options  = $this->get_isc_options();
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

			/**
			 * Fire when a post or page was updated
			 */
			add_action( 'post_updated', array( 'ISC_Model', 'update_image_post_meta' ), 10, 3 );
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
CC BY-NC-ND 2.0 Generic|https://creativecommons.org/licenses/by-nc-nd/2.0/
FAL Free Art License 1.3 |http://artlibre.org/licence/lal/en/
GFDL GNU Free Documentation License 1.2|https://www.gnu.org/licenses/fdl-1.2.html
GFDL GNU Free Documentation License 1.3|https://www.gnu.org/licenses/fdl-1.3.html';

			$default['display_type']              = array( 'list' );
			$default['list_on_archives']          = false;
			$default['list_on_excerpts']          = false;
			$default['image_list_headline']       = __( 'image sources', 'image-source-control-isc' );
			$default['version']                   = ISCVERSION;
			$default['images_per_page']			  = 99999;
			$default['thumbnail_in_list']         = false;
			$default['thumbnail_size']            = 'thumbnail';
			$default['thumbnail_width']           = 150;
			$default['thumbnail_height']          = 150;
			$default['warning_onesource_missing'] = true;
			$default['remove_on_uninstall']       = false;
			$default['hide_list']                 = false;
			$default['caption_position']          = 'top-left';
			$default['caption_style']             = null;
			$default['source_pretext']            = __( 'Source:', 'image-source-control-isc' );
			$default['enable_licences']           = false;
			$default['licences']                  = apply_filters( 'isc-licences-list', $licences );
			$default['list_included_images']      = '';
			$default['overlay_included_images']   = '';
			$default['enable_log']                = false;
			$default['standard_source']           = '';
			$default['standard_source_text']      = '';

			/**
			 * Allow manipulating defaults for plugin settings
			 */
			return apply_filters( 'isc_default_settings', $default );
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

		/**
		 * Get the options for included images in the sources list
		 */
		public function get_list_included_images_options() {
			$included_images_options = array(
				'default'   => array(
					'label'       => __( 'Images in the content', 'image-source-control-isc' ),
					'description' => sprintf(
						// translators: %1$s is "img" and %2$s stands for "the_content" wrapped in "code" tags
						__( 'Technically: %1$s tags within %2$s and the featured image.', 'image-source-control-isc' ),
						'<code>img</code>',
						'<code>the_content</code>'
					),
					'value'       => '',
					'coming_soon' => false,
				),
				'body_img'  => array(
					'label'       => __( 'Images on the whole page', 'image-source-control-isc' ),
					'description' =>
						__( 'Including header, sidebar, and footer.', 'image-source-control-isc' ) . ' ' .
						sprintf(
						// translators: %1$s is "img" and %2$s stands for "body" wrapped in "code" tags
							__( 'Technically: %1$s tags within %2$s.', 'image-source-control-isc' ),
							'<code>img</code>',
							'<code>body</code>'
						),
					'value'       => 'body_img',
					'is_pro'      => true,
				),
				'body_urls' => array(
					'label'       => __( 'Any image URL', 'image-source-control-isc' ),
					'description' =>
						__( 'Including CSS background, JavaScript, or HTML attributes.', 'image-source-control-isc' ) . ' ' .
						sprintf(
						// translators: %s stands for "body" wrapped in "code" tags
							__( 'Technically: any image URL found in %s.', 'image-source-control-isc' ),
							'<code>html</code>'
						),
					'value'       => 'body_urls',
					'is_pro'      => true,
				),
			);

			return apply_filters( 'isc_list_included_images_options', $included_images_options );
		}

		/**
		 * Get the options for images that get an overlay with the source
		 */
		public function get_overlay_included_images_options() {
			$included_images_options = array(
				'default'  => array(
					'label'       => __( 'Images in the content', 'image-source-control-isc' ),
					'description' => sprintf(
					// translators: %1$s is "img" and %2$s stands for "the_content" wrapped in "code" tags
						__( 'Technically: %1$s tags within %2$s.', 'image-source-control-isc' ),
						'<code>img</code>',
						'<code>the_content</code>'
					),
					'value'       => '',
					'coming_soon' => false,
				),
				'body_img' => array(
					'label'       => __( 'Images on the whole page', 'image-source-control-isc' ),
					'description' =>
						__( 'Including featured image, header, sidebar, and footer.', 'image-source-control-isc' ) . ' ' .
						sprintf(
						// translators: %1$s is "img" and %2$s stands for "body" wrapped in "code" tags
							__( 'Technically: %1$s tags within %2$s.', 'image-source-control-isc' ),
							'<code>img</code>',
							'<code>body</code>'
						),
					'value'       => 'body_img',
					'is_pro'      => true,
				),
			);

			return apply_filters( 'isc_overlay_included_images_options', $included_images_options );
		}

		/**
		 * Get the options for images that appear in the global list
		 */
		public function get_global_list_included_images_options() {
			$included_images_options = array(
				'in_posts'  => array(
					'label'       => __( 'Images in the content', 'image-source-control-isc' ),
					'description' => __( 'Only images that are used within the post and page content.', 'image-source-control-isc' ),
					'value'       => '',
				),
				'all' => array(
					'label'       => __( 'All images', 'image-source-control-isc' ),
					'description' => __( 'All images in the Media library, regardless of whether they are used within the post and page content or not', 'image-source-control-isc' ),
					'value'       => 'all',
					'is_pro'      => false,
				),
				'with_sources' => array(
					'label'       => __( 'Images with sources', 'image-source-control-isc' ),
					'description' => __( 'All images in the Media library that have an individual source or use the standard source.', 'image-source-control-isc' ),
					'value'       => 'with_sources',
					'is_pro'      => true,
				),
			);

			return apply_filters( 'isc_global_list_included_images_options', $included_images_options );
		}

	/**
	 * Get the options for which columns appear in the global list
	 */
	public function get_global_list_included_data_options() {
		$included_columns_options = array(
			'attachment_id' => array(
				'label'       => __( 'Attachment ID', 'image-source-control-isc' ),
				'is_pro'      => true,
			),
			'title' => array(
				'label'       => __( 'Title', 'image-source-control-isc' ),
				'is_pro'      => true,
			),
			'posts' => array(
				'label'       => __( 'Attached to', 'image-source-control-isc' ),
				'is_pro'      => true,
			),
			'source' => array(
				'label'       => __( 'Source', 'image-source-control-isc' ),
				'is_pro'      => true,
			)
		);

		return apply_filters( 'isc_global_list_included_data_options', $included_columns_options );
	}

		/**
		 * Get the standard source text as set up under Settings > Standard Source > Custom text
		 * if there was no input, yet
		 *
		 * @return string
		 */
		public function get_standard_source_text() {
			$options = $this->get_isc_options();
			if ( ! empty( $options['standard_source_text'] ) ) {
				return $options['standard_source_text'];
			} elseif ( isset( $options['by_author_text'] ) ) {
				return $options['by_author_text'];
			} else {
				return sprintf( '© %s', get_home_url() );
			}
		}

		/**
		 * Verify the standard source option
		 *
		 * @param string $value value of the [standard_source] option.
		 * @return bool whether $value is identical to the standard source option or not.
		 */
		public function is_standard_source( $value ) {
			$options = $this->get_isc_options();

			if ( isset( $options['standard_source'] ) ) {
				return $options['standard_source'] === $value;
			}

			/**
			 * 2.0 moved the options to handle "own images" into "standard sources" and only offers a single choice for one of the options now
			 * this section maps old to new settings
			 */
			if ( ! empty( $options['exclude_own_images'] ) ) {
				return 'exclude' === $value;
			} elseif ( ! empty( $options['use_authorname'] ) ) {
				return 'author_name' === $value;
			}

			return false;
		}


		/**
		 * Get the standard source setting
		 *
		 * @return string
		 */
		public function get_standard_source() {
			$options = $this->get_isc_options();

			// options since 2.0
			if ( ! empty( $options['standard_source'] ) ) {
				return $options['standard_source'];
			}

			/**
			 * 2.0 moved the options to handle "own images" into "standard sources" and only offers a single choice for one of the options now
			 * this section maps old to new settings
			 */
			if ( ! empty( $options['exclude_own_images'] ) ) {
				return 'exclude';
			} elseif ( ! empty( $options['use_authorname'] ) ) {
				return 'author_name';
			} elseif ( ! empty( $options['by_author_text'] ) ) {
				return 'custom_text';
			}

			return false;
		}

		/**
		 * Get the label of the standard source label
		 *
		 * @param string $value optional value, if missing, will use the stored value.
		 * @return string
		 */
		public function get_standard_source_label( $value = null ) {
			$options = $this->get_isc_options();

			$labels = array(
				'exclude'     => __( 'Exclude from lists', 'image-source-control-isc' ),
				'author_name' => __( 'Author name', 'image-source-control-isc' ),
				'custom_text' => __( 'Custom text', 'image-source-control-isc' ),
			);

			if ( ! $value ) {
				$value = $this->get_standard_source();
			}

			if ( $value && isset( $labels[ $value ] ) ) {
				return $labels[ $value ];
			}

			return false;
		}

		/**
		 * Check if the given attachment ought to use the standard source
		 *
		 * @param int $attachment_id attachment ID.
		 * @return bool true if standard source is used
		 */
		public static function use_standard_source( $attachment_id ) {
			return (bool) apply_filters(
				'isc_raw_attachment_use_standard_source',
				get_post_meta( $attachment_id, 'isc_image_source_own', true ),
				$attachment_id
			);
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
