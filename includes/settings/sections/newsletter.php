<?php

namespace ISC\Settings\Sections;

use ISC\User;

/**
 * Handle newsletter signup
 */
class Newsletter {

	/**
	 * Newsletter logic instance
	 *
	 * @var \ISC\Newsletter
	 */
	private $newsletter;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->newsletter = new \ISC\Newsletter();

		if ( $this->newsletter->current_user_is_subscribed() || $this->newsletter->current_user_closed_signup() ) {
			return;
		}

		$this->add_settings_section();

		add_action( 'wp_ajax_newsletter_signup', [ $this, 'newsletter_signup' ] );
		add_action( 'wp_ajax_newsletter_close', [ $this, 'close_newsletter_box' ] );
	}

	/**
	 * Add settings section
	 */
	public function add_settings_section() {
		add_settings_section(
			'isc_settings_section_signup',
			__( 'Newsletter', 'image-source-control-isc' ),
			[ $this, 'render_settings_section' ],
			'isc_settings_page',
			[ 'close_button' => true ]
		);
	}

	/**
	 * Render settings section
	 */
	public function render_settings_section() {
		$email      = sanitize_email( User::get_email() );
		$signup_url = \ISC_Admin::get_isc_localized_website_url( 'newsletter', 'newsletter', 'newsletter' );

		require_once ISCPATH . 'admin/templates/settings/newsletter.php';
	}

	/**
	 * AJAX handler to subscribe to the newsletter
	 *
	 * @return void
	 */
	public function newsletter_signup() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		$return = $this->newsletter->subscribe();

		$response = [
			'success' => true,
			'message' => $return['message'],
		];
		echo wp_json_encode( $response );

		die();
	}

	/**
	 * AJAX handler to close the newsletter box and never show it again
	 *
	 * @return void
	 */
	public function close_newsletter_box() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		$this->newsletter->close();

		$response = [
			'success' => true,
		];
		echo wp_json_encode( $response );

		die();
	}
}
