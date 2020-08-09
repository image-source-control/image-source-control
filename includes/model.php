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
	 * Setup registers filters and actions.
	 */
	public function __construct() {
		// attachment field handling
		add_action( 'add_attachment', array( $this, 'attachment_added' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'isc_fields_save' ), 10, 2 );
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
		self::save_image_information( $post_id, $image_ids );

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

		$added_images   = array();
		$removed_images = array();

		// add thumbnail information
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( ! empty( $thumb_id ) ) {
			$image_ids[ $thumb_id ] = wp_get_attachment_url( $thumb_id );
			ISC_Log::log( 'thumbnail found with ID' . $thumb_id );
		}

		// apply filter to image array, so other developers can add their own logic
		$image_ids = apply_filters( 'isc_images_in_posts_simple', $image_ids, $post_id );

		// check if image IDs refer to an attachment post type
		$valid_image_post_types = apply_filters( 'isc_valid_post_types', array( 'attachment' ) );
		foreach ( $image_ids as $_id => $_url ) {
			if ( ! in_array( get_post_type( $_id ), $valid_image_post_types, true ) ) {
				unset( $image_ids[ $_id ] );
			}
		}

		$isc_post_images = get_post_meta( $post_id, 'isc_post_images', true );
		// just needed in very rare cases, when updates comes from outside of isc and meta fields doesn’t exist yet
		if ( empty( $isc_post_images ) ) {
			$isc_post_images = array();
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
					ISC_Log::log( 'remove image: ' . $id );
				} else {
					if ( ! empty( $old_id ) ) {
						$meta = get_post_meta( $old_id, 'isc_image_posts', true );
						if ( empty( $meta ) ) {
							update_post_meta( $old_id, 'isc_image_posts', array( $post_id ) );
						} else {
							// In case the isc_image_posts is not up to date
							if ( is_array( $meta ) && ! in_array( $post_id, $meta ) ) {
								array_push( $meta, $post_id );
								$meta = array_unique( $meta );
								update_post_meta( $old_id, 'isc_image_posts', $meta );
							}
						}
					}
				}
			}
		}

		foreach ( $added_images as $id ) {
			$meta = get_post_meta( $id, 'isc_image_posts', true );
			if ( ! is_array( $meta ) || array() == $meta ) {
				update_post_meta( $id, 'isc_image_posts', array( $post_id ) );
			} else {
				array_push( $meta, $post_id );
				$meta = array_unique( $meta );
				update_post_meta( $id, 'isc_image_posts', $meta );
			}
		}

		foreach ( $removed_images as $id ) {
			$image_meta = get_post_meta( $id, 'isc_image_posts', true );
			if ( is_array( $image_meta ) ) {
				$offset = array_search( $post_id, $image_meta );
				if ( false !== $offset ) {
					array_splice( $image_meta, $offset, 1 );
					$image_meta = array_unique( $image_meta );
					update_post_meta( $id, 'isc_image_posts', $image_meta );
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
	public static function save_image_information( $post_id, $image_ids ) {

		ISC_Log::log( 'enter save_image_information()' );

		// add thumbnail information
		$thumb_id = get_post_thumbnail_id( $post_id );

		/**
		 * If an image is used both inside the post and as post thumbnail, the thumbnail entry overrides the regular image.
		 */
		if ( ! empty( $thumb_id ) ) {
			$image_ids[ $thumb_id ] = array(
				'src'       => wp_get_attachment_url( $thumb_id ),
				'thumbnail' => true,
			);
			ISC_Log::log( 'thumbnail found with ID' . $thumb_id );
		}

		// apply filter to image array, so other developers can add their own logic
		$image_ids = apply_filters( 'isc_images_in_posts', $image_ids, $post_id );

		if ( empty( $image_ids ) ) {
			$image_ids = array();
		}

		ISC_Log::log( 'save isc_post_images with size of ' . count( $image_ids ) );

		update_post_meta( $post_id, 'isc_post_images', $image_ids );
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
		$args = array(
			'post_type'   => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => null,
		);

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
					update_post_meta( $_attachment->ID, $_field['id'], $_field['default'] );
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
			update_post_meta( $att_id, $field['id'], $field['default'] );
		}
	}

	/**
	 * Save image source to post_meta
	 *
	 * @updated 1.5 added field for url
	 *
	 * @param array $post post data.
	 * @param array $attachment attachment data.
	 * @return array $post updated post data
	 */
	public function isc_fields_save( $post, $attachment ) {
		if ( isset( $attachment['isc_image_source'] ) ) {
			update_post_meta( $post['ID'], 'isc_image_source', trim( $attachment['isc_image_source'] ) );
		}
		if ( isset( $attachment['isc_image_source_url'] ) ) {
			$url = esc_url_raw( $attachment['isc_image_source_url'] );
			update_post_meta( $post['ID'], 'isc_image_source_url', $url );
		}
		$own = ( isset( $attachment['isc_image_source_own'] ) ) ? $attachment['isc_image_source_own'] : '';
		update_post_meta( $post['ID'], 'isc_image_source_own', $own );
		if ( isset( $attachment['isc_image_licence'] ) ) {
			update_post_meta( $post['ID'], 'isc_image_licence', $attachment['isc_image_licence'] );
		}

		return $post;
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
			 || ! in_array( $post->post_type, get_post_types( array( 'public' => true ), 'names' ), true ) // is the post type public
			 || 'attachment' === $post->post_type
			 || 'revision' === $post->post_type ) {
			return false;
		}

		return true;
	}

	/**
	 * Remove post_images index
	 * namely the post meta field `isc_post_images`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function clear_post_images_index() {
		global $wpdb;

		return $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'isc_post_images' ), array( '%s' ) );
	}

	/**
	 * Remove image_posts index
	 * namely the post meta field `isc_image_posts`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function clear_image_posts_index() {
		global $wpdb;

		return $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'isc_image_posts' ), array( '%s' ) );
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

		if ( false !== $rows_deleted_1 && false !== $rows_deleted_2 ) {
			return $rows_deleted_1 + $rows_deleted_2;
		}

		return false;
	}

	/**
	 * Checks if there are image with missing sources
	 * this includes attachments that were not indexed yet (don’t have the appropriate meta values)
	 *
	 * @return bool true if sources are missing.
	 */
	public static function has_missing_sources() {
		$args = array(
			'post_type'   => 'attachment',
			'numberposts' => 1,
			'post_status' => null,
			'post_parent' => null,
			'meta_query'  => array(
				array(
					'key'     => 'isc_image_source',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => 'isc_image_source_own',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		);

		if ( ! empty( get_posts( $args ) ) ) {
			return true;
		}

		// look for unindexed attachments
		$args = array(
			'post_type'   => 'attachment',
			'numberposts' => 1,
			'post_status' => null,
			'post_parent' => null,
			'meta_query'  => array(
				array(
					'key'     => 'isc_image_source',
					'value'   => 'any',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		return ! empty( get_posts( $args ) );
	}

	/**
	 * Update the transient we set for missing sources to not check them for another hour
	 * running each time we are writing the `isc_image_source` post meta key
	 *
	 * @return string value of the transient.
	 */
	public static function update_missing_sources_transient() {
		if ( self::has_missing_sources() ) {
			set_transient( 'isc-show-missing-sources-warning', true, DAY_IN_SECONDS );
			return true;
		} else {
			set_transient( 'isc-show-missing-sources-warning', 'no', DAY_IN_SECONDS );
			return 'no';
		}
	}

	/**
	 * Filter image ids from content
	 *
	 * @param string $content post content.
	 * @return array with image ids => image src uri-s
	 */
	public static function filter_image_ids( $content = '' ) {
		$srcs = array();

		ISC_Log::log( 'enter filter_image_ids() to look for image IDs within the content' );

		if ( empty( $content ) ) {
			ISC_Log::log( 'exit save_image_information() due to missing content' );
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

					ISC_Log::log( sprintf( 'found class attribute "%s"', $node->attributes->getNamedItem( 'class' )->textContent ) );

					if ( preg_match( '#.*wp-image-(\d+?).*#U', $node->attributes->getNamedItem( 'class' )->textContent, $matches ) ) {
						$srcs[ intval( $matches[1] ) ] = $node->attributes->getNamedItem( 'src' )->textContent;
						$matched                       = true;

						ISC_Log::log( sprintf( 'found image ID "%d" with src "%s"', intval( $matches[1] ), $srcs[ intval( $matches[1] ) ] ) );
					}
				}
				if ( ! $matched ) {
					if ( null !== $node->attributes->getNamedItem( 'src' ) ) {
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
	public static function get_image_by_url( $url = '' ) {
		global $wpdb;

		ISC_Log::log( 'enter get_image_by_url() to look for URL ' . $url );

		if ( empty( $url ) ) {
			return 0;
		}
		$types = implode( '|', ISC_Class::get_instance()->allowed_extensions );
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

		ISC_Log::log( 'SQL: ' . $raw_query );

		$query = apply_filters( 'isc_get_image_by_url_query', $raw_query, $newurl );
		$id    = $wpdb->get_var( $query );

		$id ? ISC_Log::log( 'found image ID ' . $id ) : ISC_Log::log( 'found image ID –' );

		return intval( $id );
	}
}
