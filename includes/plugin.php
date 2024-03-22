<?php

namespace ISC;

/**
 * Plugin class
 */
class Plugin {

	/**
	 * Check if this is the pro version
	 *
	 * @return bool
	 */
	public static function is_pro(): bool {
		return defined( 'ISCPRO' );
	}
}
