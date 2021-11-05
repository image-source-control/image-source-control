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
	 * Check if the image URL is known to the storage
	 *
	 * @param string $url part of the image URL string.
	 * @return bool
	 */
	public function is_image_url_in_storage( $url ) {
		$storage = $this->get_storage();

		return isset( $storage[ $url ] );
	}

	/**
	 * Return image ID based on a given URL
	 *
	 * @param string $url part of the image URL string.
	 * @return int|false|null attachment ID if found in the storage, false if not found, null if the image URL does not have an image ID.
	 */
	public function get_image_id_from_storage( $url ) {
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
		if ( ! $this->is_image_url_in_storage( $url ) ) {
			return false;
		}

		$storage = $this->get_storage();

		// return post ID or null if element exists
		return ( isset( $storage[ $url ][ $key ] ) ) ? absint( $storage[ $url ][ $key ] ) : null;
	}

	/**
	 * Updates or adds image URL with the post ID to the storage
	 *
	 * @param string  $url image URL.
	 * @param integer $post_id WP_Post ID.
	 */
	public function update_post_id( $url, $post_id ) {
		if ( absint( $post_id ) ) {
			$this->update( $url, array( 'post_id' => absint( $post_id ) ) );
		}
	}

	/**
	 * Updates or adds element to storage
	 *
	 * @param string $url image URL.
	 * @param array  $data storage data.
	 */
	public function update( $url, array $data ) {
		$storage = $this->get_storage();

		// merge existing data with new data
		if ( isset( $storage[ $url ] ) ) {
			$storage[ $url ] = array_merge( $storage[ $url ], $data );
		} else {
			$storage[ $url ] = $data;
		}

		$this->storage = $storage;
		update_option( $this->option_slug, $storage, true );
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
