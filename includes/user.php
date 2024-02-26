<?php

namespace ISC;

/**
 * Handle user-related attributes
 */
class User {
	/**
	 * Check if the current user is WP_User
	 *
	 * @return bool
	 */
	public static function is_user(): bool {
		$user = wp_get_current_user();
		return $user instanceof \WP_User;
	}

	/**
	 * Get the current user email address
	 *
	 * @return string|false email address or false if none is set
	 */
	public static function get_email() {
		if ( ! self::is_user() ) {
			return false;
		}

		$email = trim( wp_get_current_user()->user_email );

		return is_email( $email ) ? $email : false;
	}

	/**
	 * Get the current user’s public name
	 *
	 * @return string display name
	 */
	public static function get_name(): string {
		if ( ! self::is_user() ) {
			return '';
		}

		return ! empty( wp_get_current_user()->nickname ) ? wp_get_current_user()->nickname : wp_get_current_user()->display_name;
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