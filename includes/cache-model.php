<?php
/**
 * Logic to get and store entries in the ISC cache
 * it reduces the number of SQL queries for sources in the frontend
 *
 * The cache in detail:
 * key: the image URL
 * value: attachment ID or null if there is none
 */
class ISC_Cache_Model {

	/**
	 * Name of the option
	 *
	 * @var string
	 */
	protected $option_slug = 'isc_cache';

	/**
	 * Cache option with content
	 *
	 * @var array
	 */
	public $cache;

	/**
	 * Instance of ISC_Model
	 *
	 * @var ISC_Cache_Model
	 */
	protected static $instance;

	/**
	 * Load cache
	 */
	public function __construct() {
		$this->get_cache();
	}

	/**
	 * Get cache array
	 *
	 * @return array
	 */
	public function get_cache() {
		if ( $this->cache ) {
			return $this->cache;
		}

		$this->cache = get_option( $this->option_slug, array() );

		return $this->cache;
	}

	/**
	 * Check if the attachment ID is known to the cache
	 *
	 * @param string $url part of the image URL string.
	 * @return bool
	 */
	public function is_image_url_cached( $url ) {
		$cache = $this->get_cache();

		return isset( $cache[ $url ] );
	}

	/**
	 * Return image ID based on a given URL
	 *
	 * @param string $url part of the image URL string.
	 * @return int|false|null attachment ID if found in cache, false if not found, null if the image URL does not have an image ID.
	 */
	public function get_image_id_from_cache( $url ) {
		if ( ! $this->is_image_url_cached( $url ) ) {
			return false;
		}

		$cache = $this->get_cache();

		// return attachment ID or null if element exists
		return ( $cache[ $url ] ) ? absint( $cache[ $url ] ) : null;
	}

	/**
	 * Updates or adds element to cache
	 *
	 * @param string $url image URL.
	 * @param int    $id attachment ID.
	 */
	public function update( $url, $id ) {
		$cache         = $this->get_cache();
		$cache[ $url ] = $id;

		$this->cache = $cache;
		update_option( $this->option_slug, $cache, true );
	}
}
