<?php

namespace ISC;

/**
 * Admin Utils
 */
trait Admin_Utils {
	/**
	 * Get the ISC pages
	 *
	 * @return array
	 */
	public static function get_isc_pages(): array {
		return apply_filters(
			'isc_admin_pages',
			[
				'settings_page_isc-settings',
				'media_page_isc-sources',
			]
		);
	}

	/**
	 * Check if the current WP Admin page belongs to ISC
	 *
	 * @return bool true if this is an ISC-related page
	 */
	public static function is_isc_page(): bool {
		$screen = get_current_screen();

		return isset( $screen->id ) && in_array( $screen->id, self::get_isc_pages(), true );
	}

	/**
	 * Return true if we are on the media library page in list view
	 *
	 * @return bool
	 */
	public static function is_media_library_list_view_page(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! isset( $screen->id ) || $screen->id !== 'upload' ) {
			return false;
		}

		// don’t show this in grid mode
		if ( 'list' !== get_user_option( 'media_library_mode' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get URL to the ISC website by site language
	 * If the URL param contains an anchor, the anchor will be added after the UTM part
	 *
	 * @param string $url_param    Default parameter added to the main URL, leading to https://imagesourceocontrol.com/.
	 * @param string $url_param_de Parameter added to the main URL for German backends, leading to https://imagesourceocontrol.de/.
	 * @param string $utm_campaign Position of the link for the campaign link.
	 *
	 * @return string website URL
	 */
	public static function get_isc_localized_website_url( string $url_param, string $url_param_de, string $utm_campaign ) {
		if ( User::has_german_backend() ) {
			if ( strpos( $url_param_de, '#' ) !== false ) {
				$anchor       = substr( $url_param_de, strpos( $url_param_de, '#' ) );
				$url_param_de = substr( $url_param_de, 0, strpos( $url_param_de, '#' ) );
			}
			$url = 'https://imagesourcecontrol.de/' . $url_param_de;
		} else {
			if ( strpos( $url_param, '#' ) !== false ) {
				$anchor    = substr( $url_param, strpos( $url_param, '#' ) );
				$url_param = substr( $url_param, 0, strpos( $url_param, '#' ) );
			}
			$url = 'https://imagesourcecontrol.com/' . $url_param;
		}

		$url .= '?utm_source=isc-plugin&utm_medium=link&utm_campaign=' . $utm_campaign;

		// add the anchor to the URL
		if ( isset( $anchor ) ) {
			$url .= $anchor;
		}

		return esc_url( $url );
	}

	/**
	 * Get link to ISC Pro
	 *
	 * @param string $utm_campaign Position of the link for the campaign link.
	 *
	 * @return string
	 */
	public static function get_pro_link( $utm_campaign = 'upsell-link' ) {
		return '<a href="' . self::get_isc_localized_website_url( 'pricing/', 'preise/', $utm_campaign ) . '" class="isc-get-pro" target="_blank">' . esc_html__( 'Get ISC Pro', 'image-source-control-isc' ) . '</a>';
	}

	/**
	 * Get link to the ISC manual
	 *
	 * @param string $utm_campaign Position of the link for the campaign link.
	 */
	public static function get_manual_url( $utm_campaign = 'manual' ) {
		// check if the locale starts with "de_"
		if ( User::has_german_backend() ) {
			$base_url = 'https://imagesourcecontrol.de/dokumentation/';
		} else {
			$base_url = 'https://imagesourcecontrol.com/documentation/';
		}

		return $base_url . '?utm_source=isc-plugin&utm_medium=link&utm_campaign=' . $utm_campaign;
	}
}
