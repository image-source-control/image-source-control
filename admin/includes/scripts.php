<?php

namespace ISC\Admin;

use ISC\Admin_Utils;

/**
 * Add general admin scripts
 */
class Admin_Scripts {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_scripts' ] );
		add_action( 'admin_print_scripts', [ $this, 'admin_head_scripts' ] );
	}

	/**
	 * Add scripts to ISC-related pages
	 */
	public function add_admin_scripts() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return;
		}

		// Load CSS
		if ( Admin_Utils::is_isc_page() ) {
			wp_enqueue_style( 'isc_image_settings_css', ISCBASEURL . '/admin/assets/css/isc.css', false, ISCVERSION );
		}
	}

	/**
	 * Display scripts in <head></head> section of admin page. Useful for creating js variables in the js global namespace.
	 */
	public function admin_head_scripts() {
		global $pagenow;
		$screen = get_current_screen();
		// add style to plugin overview page
		if ( isset( $screen->id ) && $screen->id === 'plugins' ) {
			?>
			<style>
				.row-actions .isc-get-pro {
					font-weight: bold;
					color: #F70;
				}
			</style>
			<?php
		}
		// add to any backend pages
		?>
		<style>
			div.error.isc-notice {
				border-left-color: #F70;
			}
		</style>
		<?php
		// add nonce to all pages
		$params = [
			'ajaxNonce' => wp_create_nonce( 'isc-admin-ajax-nonce' ),
		];
		wp_localize_script( 'jquery', 'isc', $params );
	}
}