<?php

/**
 * Logic to get and store sources
 */
class ISC_Model {
	/**
	 * Instance of ISC_Model
	 *
	 * @var ISC_Model
	 */
	protected static $instance;

	/**
	 * Maximum number of entries in custom queries
	 */
	const MAX_POSTS = 100;

	/**
	 * Setup registers filters and actions.
	 */
	public function __construct() {
		// attachment field handling
		add_action( 'add_attachment', [ $this, 'attachment_added' ], 10, 2 );
		add_filter( 'attachment_fields_to_save', [ $this, 'isc_fields_save' ], 10, 2 );
	}

	/**
	 * Get instance of ISC_Model
	 *
	 * @return ISC_Model
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Update the isc_image_posts and isc_post_images indexes
	 *
	 * @since 2.0
	 * @param integer $post_id ID of the target post.
	 * @param string  $content content of the target post.
	 */
	public static function update_indexes( $post_id, $content ) {

		// check if we can even save the image information
		// abort on archive pages since some output from other plugins might be disabled here
		if (
			is_archive()
			|| is_home()
			|| ! self::can_save_image_information( $post_id ) ) {
			return;
		}

		$image_ids = self::filter_image_ids( $content );
		// todo: maybe handle thumbnails here as well, the content is different, though

		// retrieve images added to a post or page and save all information as a post meta value for the post
		self::update_post_images_meta( $post_id, $image_ids );

		// add the post ID to the list of posts associated with a given image
		self::update_image_posts_meta( $post_id, $image_ids );
	}

	/**
	 * Update isc_image_posts meta field with includes IDs of all posts that have the image in its content
	 * the function should be used to push a post ID to the (maybe) existing meta field
	 *
	 * @param integer $post_id ID of the target post.
	 * @param array   $image_ids IDs of the attachments in the content.
	 */
	public static function update_image_posts_meta( $post_id, $image_ids ) {
		ISC_Log::log( 'enter update_image_posts_meta()' );

		$added_images   = [];
		$removed_images = [];

		// add thumbnail information
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( ! empty( $thumb_id ) ) {
			$image_ids[ $thumb_id ] = wp_get_attachment_url( $thumb_id );
			ISC_Log::log( 'thumbnail found with ID' . $thumb_id );
		}

		// apply filter to image array, so other developers can add their own logic
		$image_ids = apply_filters( 'isc_images_in_posts_simple', $image_ids, $post_id );

		// check if image IDs refer to an attachment post type
		$valid_image_post_types = apply_filters( 'isc_valid_post_types', [ 'attachment' ] );
		foreach ( $image_ids as $_id => $_url ) {
			if ( ! in_array( get_post_type( $_id ), $valid_image_post_types, true ) ) {
				unset( $image_ids[ $_id ] );
			}
		}

		$isc_post_images = get_post_meta( $post_id, 'isc_post_images', true );
		// just needed in very rare cases, when updates comes from outside of isc and meta fields doesn’t exist yet
		if ( empty( $isc_post_images ) ) {
			$isc_post_images = [];
		}

		foreach ( $image_ids as $id => $url ) {
			if ( is_array( $isc_post_images ) && ! array_key_exists( $id, $isc_post_images ) ) {
				ISC_Log::log( 'add new image: ' . $id );
				array_push( $added_images, $id );
			}
		}
		if ( is_array( $isc_post_images ) ) {
			foreach ( $isc_post_images as $old_id => $value ) {
				// if (!in_array($old_id, $image_ids)) {
				if ( ! array_key_exists( $old_id, $image_ids ) ) {
					array_push( $removed_images, $old_id );
					ISC_Log::log( 'remove image: ' . $old_id );
				} else {
					if ( ! empty( $old_id ) ) {
						$meta = get_post_meta( $old_id, 'isc_image_posts', true );
						if ( empty( $meta ) ) {
							self::update_post_meta( $old_id, 'isc_image_posts', [ $post_id ] );
						} else {
							// In case the isc_image_posts is not up to date
							if ( is_array( $meta ) && ! in_array( $post_id, $meta ) ) {
								array_push( $meta, $post_id );
								$meta = array_unique( $meta );
								self::update_post_meta( $old_id, 'isc_image_posts', $meta );
							}
						}
					}
				}
			}
		}

		foreach ( $added_images as $id ) {
			$meta = get_post_meta( $id, 'isc_image_posts', true );
			if ( ! is_array( $meta ) || $meta == [] ) {
				self::update_post_meta( $id, 'isc_image_posts', [ $post_id ] );
			} else {
				array_push( $meta, $post_id );
				$meta = array_unique( $meta );
				self::update_post_meta( $id, 'isc_image_posts', $meta );
			}
		}

		foreach ( $removed_images as $id ) {
			$image_meta = get_post_meta( $id, 'isc_image_posts', true );
			if ( is_array( $image_meta ) ) {
				$offset = array_search( $post_id, $image_meta );
				if ( $offset !== false ) {
					array_splice( $image_meta, $offset, 1 );
					$image_meta = array_unique( $image_meta );
					self::update_post_meta( $id, 'isc_image_posts', $image_meta );
				}
			}
		}
	}

	/**
	 * Retrieve images added to a post or page and save all information as a post meta value for the post
	 *
	 * @param integer $post_id ID of a post.
	 * @param array   $image_ids IDs of the attachments in the content.
	 *
	 * @todo check for more post types that maybe should not be parsed here
	 */
	public static function update_post_images_meta( $post_id, $image_ids ) {
		ISC_Log::log( 'enter update_post_images_meta()' );

		// add thumbnail information
		$thumb_id = get_post_thumbnail_id( $post_id );

		/**
		 * If an image is used both inside the post and as post thumbnail, the thumbnail entry overrides the regular image.
		 */
		if ( ! empty( $thumb_id ) ) {
			$image_ids[ $thumb_id ] = [
				'src'       => wp_get_attachment_url( $thumb_id ),
				'thumbnail' => true,
			];
			ISC_Log::log( 'thumbnail found with ID' . $thumb_id );
		}

		// apply filter to image array, so other developers can add their own logic
		$image_ids = apply_filters( 'isc_images_in_posts', $image_ids, $post_id );

		if ( empty( $image_ids ) ) {
			$image_ids = [];
		}

		ISC_Log::log( 'save isc_post_images with size of ' . count( $image_ids ) );

		self::update_post_meta( $post_id, 'isc_post_images', $image_ids );
	}

	/**
	 * Add meta values to all attachments
	 *
	 * @todo probably deprecated
	 * @todo probably need to fix this when more fields are added along the way
	 * @todo use compare => 'NOT EXISTS' when WP 3.5 is up to retrieve only values where it is not set
	 * @todo this currently updates all empty fields; empty in this context is empty string, 0, false or not existing; add check if meta field already existed before
	 */
	public function add_meta_values_to_attachments() {
		// retrieve all attachments
		$args = [
			'post_type'   => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => null,
		];

		$attachments = get_posts( $args );
		if ( empty( $attachments ) || ! is_array( $attachments ) ) {
			return;
		}

		$count = 0;
		foreach ( $attachments as $_attachment ) {
			$set = false;
			setup_postdata( $_attachment );
			foreach ( $this->fields as $_field ) {
				$meta = get_post_meta( $_attachment->ID, $_field['id'], true );
				if ( empty( $meta ) ) {
					self::update_post_meta( $_attachment->ID, $_field['id'], $_field['default'] );
					$set = true;
				}
			}
			if ( $set ) {
				$count++;
			}
		}
	}

	/**
	 * Update attachment meta field
	 *
	 * @param integer $att_id attachment post ID.
	 */
	public function attachment_added( $att_id ) {
		if ( ! isset( $this->fields ) ) {
			return;
		}

		foreach ( $this->fields as $field ) {
			self::update_post_meta( $att_id, $field['id'], $field['default'] );
		}
	}

	/**
	 * Save image source to post_meta
	 * Used as a filter function. See save_field() it you are looking for a direct method to store post meta values
	 *
	 * @updated 1.5 added field for url
	 *
	 * @param array $post post data.
	 * @param array $attachment attachment data.
	 * @return array $post updated post data
	 */
	public function isc_fields_save( $post, $attachment ) {
		if ( isset( $attachment['isc_image_source'] ) ) {
			self::save_field( $post['ID'], 'isc_image_source', $attachment['isc_image_source'] );
		}
		if ( isset( $attachment['isc_image_source_url'] ) ) {
			self::save_field( $post['ID'], 'isc_image_source_url', $this->sanitize_source_url( $attachment['isc_image_source_url'], $attachment['isc_image_source'] ) );
		}
		$own = ( isset( $attachment['isc_image_source_own'] ) ) ? $attachment['isc_image_source_own'] : '';
		self::save_field( $post['ID'], 'isc_image_source_own', $own );

		if ( isset( $attachment['isc_image_licence'] ) ) {
			self::save_field( $post['ID'], 'isc_image_licence', $attachment['isc_image_licence'] );
		}

		return $post;
	}

	/**
	 * Sanitize source URL string by removing any HTML tags
	 *
	 * @param string $source_url source URL string
	 *
	 * @return string sanitized source URL
	 */
	public function sanitize_source_url( string $source_url ): string {
		return wp_kses( $source_url, [] );
	}

	/**
	 * Store attachment-related post meta values
	 *
	 * @param int    $att_id WP_Post ID of the attachment
	 * @param string $key post meta key
	 * @param mixed  $value post meta value
	 */
	public static function save_field( $att_id, $key, $value ) {
		if ( is_string( $value ) ) {
			$value = trim( $value );
		}

		self::update_post_meta( $att_id, $key, $value );
	}

	/**
	 * Don’t save meta data for non-public post types, since those shouldn’t be visible in the frontend
	 * ignore also attachment posts
	 * ignore revisions
	 *
	 * @param integer $post_id WP_Post ID. Useful if post object is not given.
	 */
	private static function can_save_image_information( $post_id = null ) {

		// load post
		$post = get_post( $post_id );

		if ( ! isset( $post->post_type )
			 || ! in_array( $post->post_type, get_post_types( [ 'public' => true ], 'names' ), true ) // is the post type public
			 || $post->post_type === 'attachment'
			 || $post->post_type === 'revision' ) {
			return false;
		}

		return true;
	}

	/**
	 * Update image-post index attached to attachments when a post is updated
	 *
	 * @param int     $post_ID      Post ID.
	 * @param WP_Post $post_after   Post object following the update.
	 * @param WP_Post $post_before  Post object before the update.
	 *
	 * @return void
	 */
	public static function update_image_post_meta( $post_ID, $post_after, $post_before ) {
		if ( ! self::can_save_image_information( $post_ID ) ) {
			return;
		}

		// remove

		$image_ids = self::filter_image_ids( $post_before->post_content );
		$thumb_id  = get_post_thumbnail_id( $post_ID );
		if ( ! empty( $thumb_id ) ) {
			$image_ids[ $thumb_id ] = '';
		}

		// iterate through all image ids and remove the post ID from their "image_posts" meta data
		foreach ( $image_ids as $image_id => $image_src ) {
			$meta = get_post_meta( $image_id, 'isc_image_posts', true );
			if ( is_array( $meta ) ) {
				unset( $meta[ array_search( $post_ID, $meta ) ] );
				self::update_post_meta( $image_id, 'isc_image_posts', $meta );
			}
		}
	}

	/**
	 * Remove the post_images index from a single post
	 * namely the post meta field `isc_post_images`
	 */
	public static function clear_single_post_images_index( $post_id ) {
		delete_post_meta( $post_id, 'isc_post_images' );
	}

	/**
	 * Remove post_images index
	 * namely the post meta field `isc_post_images`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function clear_post_images_index() {
		global $wpdb;

		return $wpdb->delete( $wpdb->postmeta, [ 'meta_key' => 'isc_post_images' ], [ '%s' ] );
	}

	/**
	 * Remove image_posts index
	 * namely the post meta field `isc_image_posts`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function clear_image_posts_index() {
		global $wpdb;

		return $wpdb->delete( $wpdb->postmeta, [ 'meta_key' => 'isc_image_posts' ], [ '%s' ] );
	}

	/**
	 * Remove all image-post relations
	 * this concerns the post meta fields `isc_image_posts` and `isc_post_images`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function clear_index() {
		global $wpdb;

		$rows_deleted_1 = self::clear_post_images_index();
		$rows_deleted_2 = self::clear_image_posts_index();

		if ( $rows_deleted_1 !== false && $rows_deleted_2 !== false ) {
			return $rows_deleted_1 + $rows_deleted_2;
		}

		return false;
	}

	/**
	 * Checks if there are image with missing sources
	 * this includes attachments that were not indexed yet (don’t have the appropriate meta values)
	 *
	 * @return int number of images with missing sources
	 */
	public static function count_missing_sources(): int {

		// get known and used attachments without sources
		return count( self::get_attachments_with_empty_sources() );
	}

	/**
	 * Get attachments.
	 * Allows to be called with custom arguments.
	 *
	 * @param array $args arguments for the query.
	 * @return array with attachments. Returns all attachments in the Media Library if called without additional arguments.
	 */
	public static function get_attachments( $args ) {
		$args = wp_parse_args( $args, [
			'post_type'   => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => null,
		] );

		return get_posts( $args );
	}

	/**
	 * Get all attachments with empty sources options.
	 *
	 * @return array with attachments.
	 */
	public static function get_attachments_with_empty_sources() {
		$args = [
			'post_type'   => 'attachment',
			'numberposts' => self::MAX_POSTS,
			'post_status' => null,
			'post_parent' => null,
			'meta_query'  => [
				'relation' => 'OR', // Use OR to combine the conditions
				// images with empty source string that are not using the standard source option
				[
					'relation' => 'AND',
					[
						'key'     => 'isc_image_source',
						'value'   => '',
						'compare' => '=',
					],
					[
						'key'     => 'isc_image_source_own',
						'value'   => '1',
						'compare' => '!=',
					],
				],
				// ISC meta key is completely missing, which means that ISC has not yet seen this image anywhere
				// or it was saved after the plugin was installed
				[
					'key'     => 'isc_image_source',
					'compare' => 'NOT EXISTS',
				],
			],
		];

		return get_posts( $args );
	}

	/**
	 * Get all attachments that are not used
	 * We are using a custom query since WP_Query is not flexible enough
	 *
	 * @return array|object|stdClass[]|null query results objects or post IDs.
	 */
	public static function get_unused_attachments() {
		global $wpdb;

		/**
		 * Notes on the query:
		 *
		 * if the `isc_image_posts` post meta value is not set or an empty array, the image is not known to ISC.
		 * `_thumbnail_id` is the post meta key for the featured image of a post. Image IDs in here are considered used images
		 *     though we cannot be 100% sure if the theme makes use of them
		 * we are not considering `post_parent` relevant here, since an image might have been uploaded to a post once, but no longer be used in there
		 */
		$query = "SELECT DISTINCT p.*, attachment_meta.meta_value as metadata
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'isc_image_posts'
		LEFT JOIN {$wpdb->postmeta} featured ON featured.meta_value = p.ID AND featured.meta_key = '_thumbnail_id'
		LEFT JOIN {$wpdb->postmeta} attachment_meta ON attachment_meta.post_id = p.ID AND attachment_meta.meta_key = '_wp_attachment_metadata'
		WHERE p.post_type = 'attachment'
		AND (pm.meta_value IS NULL OR pm.meta_value = 'a:0:{}')
		AND featured.meta_value IS NULL
		LIMIT " . ISC_Model::MAX_POSTS;

		return $wpdb->get_results( $query );
	}

	/**
	 * Calculates the number of files and total filesize of all associated image files from the given metadata array.
	 *
	 * @param string|array $image_metadata The metadata array of the image.
	 * @return int[] analyzed information with [files] and [total_size]
	 */
	public static function analyze_unused_image( $image_metadata ): array {
		$information = [
			'files' => 0,
			'total_size'  => 0,
		];

		if ( empty( $image_metadata ) ) {
			return $information;
		}

		$image_metadata = maybe_unserialize( $image_metadata );

		if ( isset( $image_metadata['filesize'] ) ) {
			$information['files']++;
			$information['total_size'] += (int) $image_metadata['filesize'];
		}

		foreach ( $image_metadata as $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value['filesize'] ) ) {
					$information['files']++;
					$information['total_size'] += (int) $value['filesize'];
				}

				// If the current value contains nested arrays (e.g., 'sizes'), recursively call the function
				if ( is_array( reset( $value ) ) ) {
					$return = self::analyze_unused_image( $value );
					$information['files'] += (int) $return['files'] ?? 0;
					$information['total_size'] += (int) $return['total_size'] ?? 0;
				}
			}
		}

		return $information;
	}

	/**
	 * Update the transient we set for missing sources to not check them for another day
	 * running each time we are writing the `isc_image_source` post meta key
	 *
	 * @return int number of missing sources.
	 */
	public static function update_missing_sources_transient() {
		$missing_sources = self::count_missing_sources();
		set_transient( 'isc-show-missing-sources-warning', $missing_sources, DAY_IN_SECONDS );
		return $missing_sources;
	}

	/**
	 * Filter image ids from content
	 *
	 * @param string $content post content.
	 * @return string[] with image ids => image src uri-s
	 */
	public static function filter_image_ids( $content = '' ) {
		$srcs = [];

		ISC_Log::log( 'enter filter_image_ids() to look for image IDs within the content' );

		if ( empty( $content ) ) {
			ISC_Log::log( 'exit filter_image_ids() due to missing content' );
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

		/**
		 * Handle multiple tags at once
		 * the original use case is checking AMP pages generated in reader mode in the AMP plugin and in AMPforWP
		 * for IMG and AMP-IMG tags
		 */
		$tags = apply_filters( 'isc_filter_image_ids_tags', [ 'img' ] );

		if ( ! is_array( $tags ) ) {
			return [];
		}

		// I am keeping the original $dom->getElementsByTagName as well as the new DOMXpath solution for multiple elements for now
		// since I am not 100% sure about the implications of the latter on existing features
		if ( count( $tags ) === 1 ) {
			$nodes = $dom->getElementsByTagName( 'img' );
		} else {
			$xpath       = new DOMXpath( $dom );
			$tags_string = '//' . implode( '|//', $tags );
			$nodes       = $xpath->query( $tags_string );
		}

		foreach ( $nodes as $node ) {
			if ( isset( $node->attributes ) ) {
				$matched = false;
				if ( $node->attributes->getNamedItem( 'class' ) !== null ) {
					ISC_Log::log( sprintf( 'found class attribute "%s"', $node->attributes->getNamedItem( 'class' )->textContent ) );

					if ( preg_match( '#.*wp-image-(\d+?).*#U', $node->attributes->getNamedItem( 'class' )->textContent, $matches ) ) {
						$srcs[ intval( $matches[1] ) ] = $node->attributes->getNamedItem( 'src' )->textContent;
						$matched                       = true;

						ISC_Log::log( sprintf( 'found image ID "%d" with src "%s"', intval( $matches[1] ), $srcs[ intval( $matches[1] ) ] ) );
					}
				}
				if ( ! $matched ) {
					if ( $node->attributes->getNamedItem( 'src' ) !== null ) {
						$url = $node->attributes->getNamedItem( 'src' )->textContent;
						ISC_Log::log( sprintf( 'found src "%s"', $url ) );
						// get ID of images by url
						$id = self::get_image_by_url( $url );
						if ( $id ) {
							$srcs[ $id ] = $url;
						}
					}
				}
			}
		}

		/**
		 * Filter image IDs found in HTML content, or add new ones based on other rules.
		 *
		 * @since 2.9.0
		 *
		 * @param string[] $srcs image sources with image ids => image src uri
		 * @param string $content any HTML document
		 */
		return apply_filters( 'isc_filter_image_ids_from_content', $srcs, $content );
	}

	/**
	 * Get image ID by URL accessing the database directly
	 *
	 * @param string $url URL of the image.
	 *
	 * @return integer ID of the image.
	 */
	public static function get_image_by_url( string $url = '' ): ?int {
		global $wpdb;

		ISC_Log::log( 'enter get_image_by_url() to look for URL ' . $url );

		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return 0;
		}

		// get the file extension, e.g. "jpg"
		$ext = pathinfo( $url, PATHINFO_EXTENSION );
		if ( ! $ext ) {
			ISC_Log::log( 'exit get_image_by_url() no extension found' );
			if ( apply_filters( 'isc_allow_empty_file_extension', __return_false() ) ) {
				ISC_Log::log( "get_image_by_url() didn’t find an extension for $url but continues." );
			} else {
				return 0;
			}
		} elseif ( ! in_array( $ext, ISC_Class::get_instance()->allowed_extensions, true ) ) {
			// a valid image extension is required, if an extension is given
			ISC_Log::log( 'exit get_image_by_url() due to invalid image extension' );
			return 0;
		}

		/**
		 * Check for the format 'image-title-(e12452112-)300x200.jpg(?query…)' and removes
		 * - the image size
		 * - edit marks
		 * - "scaled" or "rotated"
		 * - additional query vars
		 */
		// this was my original approach without "scaled" and "rotated"
		// $types = implode( '|', ISC_Class::get_instance()->allowed_extensions );
		// $newurl = esc_url( preg_replace( "/(-e\d+){0,1}(-\d+x\d+){0,1}\.({$types})(.*)/i", '.${3}', $url ) );
		// this is how WordPress core is detecting changed image URLs
		$newurl   = esc_url( preg_replace( "/-(?:\d+x\d+|scaled|rotated)\.{$ext}(.*)/i", '.' . $ext, $url ) );
		$orig_url = $url;

		$storage = new ISC_Storage_Model();

		// check if the URL is already in storage and if so, take it from there
		if ( $storage->is_image_url_in_storage( $newurl ) ) {
			$id_from_storage = absint( $storage->get_image_id_from_storage( $newurl ) );
			ISC_Log::log( "found $newurl in storage with attachment ID $id_from_storage" );
			return $id_from_storage;
		}

		/**
		 * Attachment_url_to_postid needs the URL including protocol, but cannot handle sizes, so it needs to be at exactly this position
		 * this function finds images based on the _wp_attached_file post meta value that includes the image path followed after the upload dir
		 * it therefore also works when the domain changed
		 */
		$id = attachment_url_to_postid( $newurl );
		if ( $id ) {
			// store attachment ID in storage
			$storage->update_post_id( $newurl, $id );
			ISC_Log::log( '_attachment_url_to_postid found image ID ' . $id );
			return $id;
		}

		// remove protocol (http or https)
		$url    = str_ireplace( [ 'http:', 'https:' ], '', $url );
		$newurl = str_ireplace( [ 'http:', 'https:' ], '', $newurl );

		// gather different URLs formats
		$urls = [
			'http:' . $url,
			'https:' . $url,
			'http:' . $newurl,
			'https:' . $newurl,
		];

		// remove duplicates
		$urls = array_unique( $urls );

		$url_queries = [];
		foreach ( $urls as $_url ) {
			// return if any of the URLs is already in storage
			if ( $storage->is_image_url_in_storage( $_url ) ) {
				$id_from_storage = absint( $storage->get_image_id_from_storage( $_url ) );
				ISC_Log::log( "found $newurl in storage with attachment ID $id_from_storage" );
				return $id_from_storage;
			}

			$url_queries[] = 'guid = "' . esc_url( $_url ) . '"';
		}
		$url_query_string = implode( ' OR ', $url_queries );
		ISC_Log::log( sprintf( 'SQL query looking for anything with %s', implode( ', ', array_unique( [ $url, $newurl ] ) ) ) );

		// not escaped, because escaping already happened above
		$raw_query = "SELECT ID, guid FROM `$wpdb->posts` WHERE post_type='attachment' AND {$url_query_string} LIMIT 1";

		$query   = apply_filters( 'isc_get_image_by_url_query', $raw_query, $newurl );
		$results = $wpdb->get_results( $query );

		$id   = isset( $results[0]->ID ) ? absint( $results[0]->ID ) : 0;
		$guid = $results[0]->guid ?? null;

		if ( $id ) {
			$storage->update_post_id( $guid, $id );
			ISC_Log::log( 'found image ID ' . $id );
		} else {
			// this should ideally only apply to image URLs that are not in the media library
			// ISC also stores the URL to prevent too many database requests
			// using $newurl, because it is already stripped by potential parameters and stuff
			$storage->update( $newurl, [ 'post_id' => null ] );
			ISC_Log::log( 'no image ID found' );
		}

		return $id;
	}

	/**
	 * Store post meta information.
	 * Use this function instead of core’s update_post_meta() to allow debugging
	 *
	 * @param int    $post_id post ID of the attachment.
	 * @param string $key     post meta key.
	 * @param mixed  $value   value of the post meta information.
	 */
	public static function update_post_meta( int $post_id, string $key, $value ) {

		/**
		 * Run when any post meta information is stored by ISC
		 *
		 * @since 2.9.0
		 *
		 * @param int    $post_id post ID of the attachment.
		 * @param string $key     post meta key.
		 * @param mixed  $value   value of the post meta information.
		 */
		do_action( 'isc_update_post_meta', $post_id, $key, $value );

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Get an array of all posts that have the isc_post_images meta field set
	 * and the number of them not having it
	 * list each post type separately
	 *
	 * @return array
	 */
	public static function get_posts_with_image_index(): array {
		$post_types = get_post_types( [ 'public' => true ] );
		// remove the attachment post type
		$post_types = array_diff( $post_types, ['attachment'] );
		$results = [];

		foreach ( $post_types as $post_type ) {
			$count_posts = wp_count_posts( $post_type );
			$total_posts = $count_posts->publish;

			$args = [
				'post_type'      => $post_type,
				'posts_per_page' => self::MAX_POSTS,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'     => 'isc_post_images',
						'compare' => 'NOT EXISTS',
					]
				]
			];
			$query_without = new WP_Query( $args );
			$posts_without_meta_field = $query_without->post_count;

			$results[$post_type] = [
				'total_posts'          => $total_posts,
				'without_meta_field'   => $posts_without_meta_field,
				'with_meta_field'      => $total_posts - $posts_without_meta_field
			];
		}

		return $results;
	}

    /**
     * Generate the base file name for an attachment ID
     * to use when searching the database
     * E.g., `image-300x200.jpg` or `image-12345.jpg` would return just "image"
     *
     * @param int $image_id
     *
     * @return string
     */
    public function get_base_file_name( int $image_id ): string {
        // load the attachment post
        $file = get_post_meta( $image_id, '_wp_attached_file', true );
        // get guid as fallback, e.g., _wp_attached_file can be empty for external images
        if ( empty( $file ) ) {
            $attachment = get_post( $image_id );
            $file       = $attachment->guid;
        }

        // remove the extension from the file URL
        return str_replace( '.' . pathinfo( $file, PATHINFO_EXTENSION ), '', $file );
    }
}
