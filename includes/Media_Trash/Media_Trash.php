<?php

namespace ISC\Media_Trash;

use ISC\Plugin;

/**
 * Main class to handle media trash logic
 */
class Media_Trash {
	/**
	 * Instance of Media_Trash.
	 *
	 * @var Media_Trash
	 */
	protected static $instance;

	/**
	 * Get singleton instance
	 *
	 * @return Media_Trash
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		// Activate MEDIA_TRASH if module is enabled and not already defined
		if ( Plugin::is_module_enabled( 'media_trash' ) && ! defined( 'MEDIA_TRASH' ) ) {
			define( 'MEDIA_TRASH', true );
		}
	}

	/**
	 * Check if the media trash module is enabled
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return defined( 'MEDIA_TRASH' ) && MEDIA_TRASH && Plugin::is_module_enabled( 'media_trash' );
	}
}
