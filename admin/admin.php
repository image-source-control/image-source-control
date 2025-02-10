<?php

namespace ISC;

use ISC\Image_Sources\Image_Sources_Admin_Scripts;

/**
 * Initialize WP Admin
 */
class Admin {
	/**
	 * Initiate admin functions
	 */
	public function __construct() {
		// load more admin-related classes
		add_action( 'plugins_loaded', [ $this, 'load_modules' ] );

		// ISC page header
		add_action( 'admin_notices', [ $this, 'branded_admin_header' ] );

		// hide the admin language switcher from WPML on our pages
		add_filter( 'wpml_show_admin_language_switcher', [ $this, 'disable_wpml_admin_lang_switcher' ] );

		// add links to setting and source list to plugin page
		add_action( 'plugin_action_links_' . ISCBASE, [ $this, 'add_links_to_plugin_page' ] );

		// fire when an attachment is removed
		add_action( 'delete_attachment', [ $this, 'clear_unused_images_stats' ] );

		new \ISC\Settings();
	}

	/**
	 * Load additional admin classes and modules
	 */
	public function load_modules() {
		new \ISC\Admin\Admin_Scripts();
		new \ISC\Admin\Media_Library_Filter();

		if ( Plugin::is_module_enabled( 'image_sources' ) ) {
			new \ISC\Image_Sources\Admin();
		}

		new \ISC\Feedback();
	}

	/**
	 * Add links to pages from plugins.php
	 *
	 * @param array $links existing plugin links.
	 *
	 * @return array
	 */
	public function add_links_to_plugin_page( $links ): array {
		// add link to premium.
		if ( ! Plugin::is_pro() ) {
			array_unshift( $links, Admin_Utils::get_pro_link( 'plugin-overview' ) );
		}
		// settings link
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'page', 'isc-settings', get_admin_url() . 'options-general.php' ) ),
			__( 'Settings', 'image-source-control-isc' )
		);

		return $links;
	}

	/**
	 * Disable the WPML language switcher on ISC pages
	 *
	 * @param bool $state current state.
	 *
	 * @return bool
	 */
	public function disable_wpml_admin_lang_switcher( $state ): bool {
		// needs to run before plugins_loaded with prio 1, so our own `is_isc_page` function is not available here
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) ) {
			return $state;
		}

		// settings page
		if (
			$pagenow === 'options-general.php'
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& in_array( $_GET['page'], [ 'isc-settings' ], true )
		) {
			$state = false;
		}

		// media pages
		if (
			$pagenow === 'upload.php'
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& in_array( $_GET['page'], [ 'isc-unused-images', 'isc-images' ], true )
		) {
			$state = false;
		}

		return $state;
	}

	/**
	 * Add an ISC branded header to plugin pages
	 */
	public static function branded_admin_header() {
		$screen    = get_current_screen();
		$screen_id = $screen->id ?? null;
		if ( ! Admin_Utils::is_isc_page() ) {
			return;
		}
		switch ( $screen_id ) {
			case 'settings_page_isc-settings':
				$title = __( 'Settings', 'image-source-control-isc' );
				break;
			case 'media_page_isc-sources':
				$title = __( 'Tools', 'image-source-control-isc' );
				break;
			default:
				$title = get_admin_page_title();
		}
		include ISCPATH . 'admin/templates/header.php';
	}

	/**
	 * Actions to perform when an attachment is removed
	 */
	public function clear_unused_images_stats() {
		// remove the transient with the unused image numbers
		delete_transient( 'isc-unused-attachments-stats' );
	}
}
