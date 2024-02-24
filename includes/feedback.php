<?php

namespace ISC;

use ISC\User;
use WP_User;
use ISC_Class;

/**
 * Handle feedback voluntarily sent on deactivation
 */
class Feedback {

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'admin_footer', [ $this, 'add_deactivation_popup' ] );
		add_action( 'wp_ajax_isc_send_feedback', [ $this, 'send_feedback' ] );
	}

	/**
	 * Check if we are on the Plugins page
	 *
	 * @return bool
	 */
	private function is_plugins_page(): bool {
		$screen = get_current_screen();

		return isset( $screen->id ) && in_array( $screen->id, [ 'plugins', 'plugins-network' ], true );
	}

	/**
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if ( ! $this->is_plugins_page() ) {
			return;
		}

		wp_enqueue_script( 'isc-feedback', ISCBASEURL . 'admin/assets/js/feedback.js', [], ISCVERSION, true );
		wp_enqueue_style( 'isc-feedback', ISCBASEURL . 'admin/assets/css/feedback.css', [], ISCVERSION );
	}

	/**
	 * Display deactivation modal on the Plugins page
	 *
	 * @return void
	 */
	public function add_deactivation_popup(): void {
		if ( ! $this->is_plugins_page() ) {
			return;
		}

		$from         = '';
		$email        = '';
		$current_user = wp_get_current_user();
		if ( $current_user instanceof WP_User ) {
			$from  = sprintf( '%1$s <%2$s>', $current_user->user_nicename, trim( $current_user->user_email ) );
			$email = $current_user->user_email;
		}

		include ISCPATH . 'admin/templates/feedback.php';
	}

	/**
	 * Send feedback via email
	 *
	 * @return void
	 */
	public function send_feedback() {
		// phpcs:ignore
		if ( ! array_key_exists( 'isc-feedback-form-nonce', $_POST ) || ! wp_verify_nonce( $_POST['isc-feedback-form-nonce'], 'isc-feedback-form' ) ) {
			wp_die();
		}

		$data = $this->prepare_feedback_data( $_POST );

		$installed = $data['installed'];
		$from      = $data['from'];
		$subject   = 'Image Source Control Feedback';
		$text      = $data['text'];
		$text     .= "\n\n" . home_url() . " ($installed)";

		// The user sent feedback with a reply request
		if (
			! empty( $_POST['isc-feedback-send-reply'] )
		) {
			$current_user = wp_get_current_user();
			$name         = ( $current_user instanceof WP_User ) ? $current_user->user_nicename : '';
			$email        = $data['email'];
			$from         = $name . ' <' . $email . '>';
			$text        .= "\n\n" . 'Feedback: ✓';
		}

		if ( class_exists( 'ISC_Pro_Admin', false ) ) {
			$text .= "\n\n" . 'Pro: ✓';
		}

		$headers[] = "From: $from";
		$headers[] = "Reply-To: $from";

		$to = User::has_german_backend() ? 'support@imagesourcecontrol.de' : 'support@imagesourcecontrol.com';

		wp_mail( $to, $subject, $text, $headers );
		die();
	}

	/**
	 * Prepare feedback data
	 *
	 * @param string[] $data Submitted feedback data.
	 *
	 * @return string[]
	 */
	private function prepare_feedback_data( array $data ): array {
		$feedback = [
			'from' => $data['isc-feedback-from'] ?? '',
			'text' => isset( $data['isc-feedback-text'] ) ? sanitize_text_field( $data['isc-feedback-text'] ) : '',
		];

		$feedback['email'] = ! array_key_exists( 'isc-feedback-reply-email', $data ) || ! is_email( $data['isc-feedback-reply-email'] ) ? '' : trim( $data['isc-feedback-reply-email'] );

		$options               = ISC_Class::get_instance()->get_isc_options();
		$feedback['installed'] = isset( $options['installed'] ) ? gmdate( 'd.m.Y', $options['installed'] ) : '–';

		return $feedback;
	}
}
