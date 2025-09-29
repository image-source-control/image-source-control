<?php
/**
 * The ISC Storage reduces the number of SQL queries for sources in the frontend.
 * It can be extended to host image source information for images outside the WP media library, too.
 *
 * The Model class contans the logic to get and set entries in the ISC storage
 *
 * Structure of the storage array:
 * key: the image URL
 * value: array with data:
 * - post_id – Post ID of the attachment or null if there is none
 */
class ISC_Storage_Model {

	/**
	 * Name of the option
	 *
	 * @var string
	 */
	protected $option_slug = 'isc_storage';

	/**
	 * Storage option with content
	 *
	 * @var array
	 */
	public $storage;

	/**
	 * Instance of ISC_Storage_Model
	 *
	 * @var ISC_Storage_Model
	 */
	protected static $instance;

	/**
	 * Load storage
	 */
	public function __construct() {
		$this->get_storage();
	}

	/**
	 * Get storage array
	 *
	 * @return array
	 */
	public function get_storage() {
		if ( $this->storage ) {
			return $this->storage;
		}

		$this->storage = get_option( $this->option_slug, array() );

		return $this->storage;
	}

	/**
	 * Sanitize the image URL to serve as a key
	 * - remove protocols
	 * - sanitize using esc_url
	 *
	 * We intentionally keep www. since this could prevent images from being found or the URLs to work later, when used in the frontend.
	 *
	 * @param string $url raw URL input.
	 * @return string sanitized URL string
	 */
	public static function sanitize_url_key( $url ) {
		$limit = 2;
		return str_replace( array( 'http://', 'https://', '//' ), '', esc_url( $url ), $limit );
	}

	/**
	 * Check if the image URL is known to the storage
	 *
	 * @param string $url part of the image URL string.
	 * @return bool
	 */
	public function is_image_url_in_storage( $url ) {
		$storage = $this->get_storage();
		$url = self::sanitize_url_key( $url );

		return isset( $storage[ $url ] );
	}

	/**
	 * Return image ID based on a given URL
	 *
	 * @param string $url part of the image URL string.
	 * @return int|false|null attachment ID if found in the storage, false if not found, null if the image URL does not have an image ID.
	 */
	public function get_image_id_from_storage( $url ) {
		$url = self::sanitize_url_key( $url );

		if ( ! $this->is_image_url_in_storage( $url ) ) {
			return false;
		}

		$storage = $this->get_storage();

		// return post ID or null if element exists
		return ( isset( $storage[ $url ]['post_id'] ) ) ? absint( $storage[ $url ]['post_id'] ) : null;
	}

	/**
	 * Return any data for a given image URL
	 *
	 * @param string $url part of the image URL string.
	 * @param string $key key in the image data.
	 *
	 * @return int|false|null attachment ID if found in the storage, false if not found, null if the image URL does not have an image ID.
	 */
	public function get_data_by_image_url( $url, $key ) {
		$url = self::sanitize_url_key( $url );

		if ( ! $this->is_image_url_in_storage( $url ) ) {
			return false;
		}

		$storage = $this->get_storage();

		// return post ID or null if element exists
		return ( isset( $storage[ $url ][ $key ] ) ) ? $storage[ $url ][ $key ] : null;
	}

	/**
	 * Updates or adds image URL with the post ID to the storage
	 *
	 * @param string  $url image URL.
	 * @param integer $post_id WP_Post ID.
	 */
	public function update_post_id( $url, $post_id ) {
		$url = self::sanitize_url_key( $url );

		if ( absint( $post_id ) ) {
			$this->update( $url, array( 'post_id' => absint( $post_id ) ) );
		}
	}

	/**
	 * Updates or adds image URL with the post ID to the storage
	 *
	 * @param string $url image URL.
	 * @param string $key key in the image data.
	 * @param string $value new value.
	 */
	public function update_data_by_image_url( $url, $key, $value ) {
		$key = esc_attr( $key );
		$url = self::sanitize_url_key( $url );

		$storage = $this->get_storage();
		if ( isset( $storage[ $url ] ) ) {
			$data = $storage[ $url ];
		} else {
			$data = array();
		}

		$data[ $key ] = $value;

		$this->update( $url, $data );
	}

	/**
	 * Updates or adds element to storage
	 *
	 * @param string $url image URL.
	 * @param array  $data storage data.
	 */
	public function update( $url, array $data ) {
		$url = self::sanitize_url_key( $url );
		$storage = $this->get_storage();

		// merge existing data with new data
		if ( isset( $storage[ $url ] ) ) {
			$storage[ $url ] = array_merge( $storage[ $url ], $data );
		} else {
			$storage[ $url ] = $data;
		}

		$this->storage = $storage;
		// autoload is false since this can get quite large
		update_option( $this->option_slug, $storage, false );
	}

	/**
	 * Remove an element from the storage
	 *
	 * @param string $url image URL.
	 */
	public function remove_image( $url ) {
		$url = self::sanitize_url_key( $url );

		$storage = $this->get_storage();
		if ( ! isset( $storage[ $url ] ) ) {
			return;
		}

		unset( $storage[ $url ] );
		$this->storage = $storage;
		update_option( $this->option_slug, $storage, true );
	}

	/**
	 * Remove an element from the storage based on the post ID
	 *
	 * @param int $post_id WP_Post ID.
	 */
	public function remove_image_by_id( $post_id ) {
		$storage = $this->get_storage();

		// search for the post ID
		$image_key = array_search( $post_id, array_combine( array_keys( $storage ), array_column( $storage, 'post_id' ) ) );

		if ( $image_key ) {
			$this->remove_image( $image_key );
		}
	}

	/**
	 * Clear storage by removing the isc_storage option
	 *
	 * MAKE SURE THE OPTION DOES NOT CONTAIN DATA, LIKE IMAGE SOURCE STRINGS
	 *
	 * @return bool true if the option was removed
	 */
	public static function clear_storage() {
		return delete_option( 'isc_storage' );
	}

	/**
	 * Return storage without images that have an attachment ID
	 *
	 * @return array
	 */
	public function get_storage_without_wp_images() {
		$storage = $this->get_storage();

		$storage_filtered = array();
		// remove any entry with a post_ID, since they are hosted in WP Media
		foreach ( $storage as $url => $data ) {
			if ( ! isset( $data['post_id'] ) ) {
				$storage_filtered[ $url ] = $data;
			}
		}

		return $storage_filtered;
	}
}
