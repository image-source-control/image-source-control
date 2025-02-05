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
	 * Get URL to the ISC website by site language
	 *
	 * @param string $url_param    Default parameter added to the main URL, leading to https://imagesourceocontrol.com/.
	 * @param string $url_param_de Parameter added to the main URL for German backends, leading to https://imagesourceocontrol.de/.
	 * @param string $utm_campaign Position of the link for the campaign link.
	 *
	 * @return string website URL
	 */
	public static function get_isc_localized_website_url( string $url_param, string $url_param_de, string $utm_campaign ) {
		if ( User::has_german_backend() ) {
			$url = 'https://imagesourcecontrol.de/' . $url_param_de;
		} else {
			$url = 'https://imagesourcecontrol.com/' . $url_param;
		}

		return esc_url( $url . '?utm_source=isc-plugin&utm_medium=link&utm_campaign=' . $utm_campaign );
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
