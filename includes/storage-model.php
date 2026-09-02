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
	 * Static so the ~MB-sized option is unserialized once per request rather
	 * than once per instance. get_image_by_url() constructs one instance per
	 * image, and WordPress re-runs maybe_unserialize() on every get_option().
	 *
	 * @var array|null null means "not loaded yet"
	 */
	protected static $storage = null;

	/**
	 * Instance of ISC_Storage_Model
	 *
	 * @var ISC_Storage_Model
	 */
	protected static $instance;

	/**
	 * Blog ID the storage above belongs to.
	 *
	 * The option is per site, the static is per request, so the memo must be
	 * invalidated when the current blog changes (switch_to_blog()).
	 *
	 * @var int|null
	 */
	protected static $storage_blog_id = null;

	/**
	 * Get storage array
	 *
	 * @return array
	 */
	public function get_storage() {
		$blog_id = get_current_blog_id();

		// Explicit null check: an empty array is a valid, already-loaded value.
		if ( null !== self::$storage && self::$storage_blog_id === $blog_id ) {
			return self::$storage;
		}

		self::$storage         = get_option( $this->option_slug, [] );
		self::$storage_blog_id = $blog_id;

		return self::$storage;
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
		return str_replace( [ 'http://', 'https://', '//' ], '', esc_url( $url ) );
	}

	/**
	 * Check if the image URL is known to the storage
	 *
	 * @param string $url part of the image URL string.
	 * @return bool
	 */
	public function is_image_url_in_storage( $url ) {
		$storage = $this->get_storage();
		$url     = self::sanitize_url_key( $url );

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
			$this->update( $url, [ 'post_id' => absint( $post_id ) ] );
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
			$data = [];
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
		$url     = self::sanitize_url_key( $url );
		$storage = $this->get_storage();

		// merge existing data with new data
		if ( isset( $storage[ $url ] ) ) {
			$storage[ $url ] = array_merge( $storage[ $url ], $data );
		} else {
			$storage[ $url ] = $data;
		}

		self::$storage = $storage;
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
		self::$storage = $storage;
		update_option( $this->option_slug, $storage, false );
	}

	/**
	 * Remove an element from the storage based on the post ID
	 *
	 * @param int $post_id WP_Post ID.
	 */
	public function remove_image_by_id( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}

		$storage = $this->get_storage();
		$changed = false;

		// An attachment can be stored under more than one URL, so remove every match.
		foreach ( $storage as $url => $data ) {
			if ( isset( $data['post_id'] ) && absint( $data['post_id'] ) === $post_id ) {
				unset( $storage[ $url ] );
				$changed = true;
			}
		}

		if ( ! $changed ) {
			return;
		}

		self::$storage = $storage;
		update_option( $this->option_slug, $storage, false );
	}

	/**
	 * Clear storage by removing the isc_storage option
	 *
	 * MAKE SURE THE OPTION DOES NOT CONTAIN DATA, LIKE IMAGE SOURCE STRINGS
	 *
	 * @return bool true if the option was removed
	 */
	public static function clear_storage() {
		self::$storage         = null;
		self::$storage_blog_id = null;

		return delete_option( 'isc_storage' );
	}

	/**
	 * Return storage without images that have an attachment ID
	 * This is only called on the Tools page in the backend
	 * if called more frequently, caching might be needed
	 *
	 * @return array
	 */
	public function get_storage_without_wp_images() {
		$storage = $this->get_storage();

		$storage_filtered = [];
		// remove any entry with a post_ID, since they are hosted in WP Media
		foreach ( $storage as $url => $data ) {
			if ( ! isset( $data['post_id'] ) ) {
				$storage_filtered[ $url ] = $data;
			}
		}

		return $storage_filtered;
	}
}
