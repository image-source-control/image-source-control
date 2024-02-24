<?php

namespace ISC;

/**
 * Handle user-related attributes
 */
class User {
	/**
	 * Get the current user email address
	 *
	 * @return string|false email address or false if none is set
	 */
	public static function get_user_email() {
		$email = wp_get_current_user()->user_email;

		return is_email( $email ) ? $email : false;
	}

	/**
	 * Get the current user language
	 *
	 * @return string
	 */
	public static function get_user_language(): string {
		return strpos( determine_locale(), 'de_' ) === 0 ? 'de' : 'en';
	}

	/**
	 * Is the backend language in German?
	 *
	 * @return bool
	 */
	public static function has_german_backend(): bool {
		return self::get_user_language() === 'de';
	}
}