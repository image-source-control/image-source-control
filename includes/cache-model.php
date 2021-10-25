<?php
/**
 * Logic to get and store entries in the ISC cache
 * it reduces the number of SQL queries for sources in the frontend
 *
 * The cache in detail:
 * key: the image URL
 * value: array with data:
 * - post_id – Post ID of the attachment or null if there is none
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
	 * Check if the image URL is known to the cache
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

		// return post ID or null if element exists
		return ( isset( $cache[ $url ][ 'post_id' ] ) ) ? absint( $cache[ $url ][ 'post_id' ] ) : null;
	}

	/**
	 * Updates or adds image URL with the post ID to the cache
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
	 * Updates or adds element to cache
	 *
	 * @param string $url image URL.
	 * @param array  $data cache data.
	 */
	public function update( $url, array $data ) {
		$cache = $this->get_cache();

		// merge existing data with new data
		if ( isset( $cache[ $url ] ) ) {
			$cache[ $url ] = array_merge( $cache[ $url ], $data );
		} else {
			$cache[ $url ] = $data;
		}

		$this->cache = $cache;
		update_option( $this->option_slug, $cache, true );
	}
}
