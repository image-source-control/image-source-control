<?php

namespace ISC;

/**
 * Handle newsletter signup
 */
class Newsletter {

	/**
	 * Signup URL
	 *
	 * @var string
	 */
	private const SIGNUP_URL = 'https://imagesourcecontrol.com/remote/subscribe.php';

	/**
	 * Subscribe to the newsletter
	 *
	 * @return array
	 */
	public function subscribe(): array {
		$return = [
			'success' => false,
		];

		$email = User::get_email();
		if ( ! $email ) {
			$return['message'] = 'Email invalid';
			return $return;
		}

		$data = [
			'email' => $email,
			'lang'  => User::has_german_backend() ? 'de' : 'en',
		];

		$result = wp_remote_post(
			self::SIGNUP_URL,
			[
				'method'      => 'POST',
				'timeout'     => 20,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => true,
				'body'        => $data,
			]
		);

		$status_code = wp_remote_retrieve_response_code( $result );

		if ( is_wp_error( $result ) ) {
			$return['message'] = esc_html__( 'Something went wrong. Please sign up manually.', 'image-source-control-isc' );
		} elseif ( $status_code === 201 ) {
			$return['message'] = esc_html__( 'Please check your email for the confirmation message.', 'image-source-control-isc' );
			$return['success'] = true;
			// mark as subscribed
			$this->mark_current_users_as_subscribed();
		} elseif ( $status_code === 204 ) {
			$return['message'] = esc_html__( 'The email address is invalid.', 'image-source-control-isc' );
		} elseif ( $status_code === 304 ) {
			// the email is already subscribed to any list; if they weren’t subscribed to Marketing, they are now
			$return['message'] = esc_html__( 'The email address is already subscribed.', 'image-source-control-isc' );
			$return['success'] = true;
			// if users see this, their WordPress probably doesn’t know that they are already subscribed
			$this->mark_current_users_as_subscribed();
		} else {
			$return['message'] = esc_html__( 'Something went wrong. Please sign up manually.', 'image-source-control-isc' );
		}

		return $return;
	}

	/**
	 * Close the newsletter box
	 */
	public function close() {
		update_user_meta( get_current_user_id(), 'isc_newsletter_closed', true );
	}

	/**
	 * Check if the user is subscribed to the newsletter
	 *
	 * @return bool
	 */
	public function current_user_is_subscribed() {
		$user_id = get_current_user_id();

		return ! $user_id || get_user_meta( $user_id, 'isc_newsletter_subscribed', true );
	}

	/**
	 * Check if the user closed the newsletter box
	 *
	 * @return bool
	 */
	public function current_user_closed_signup(): bool {
		$user_id = get_current_user_id();

		return ! $user_id || get_user_meta( $user_id, 'isc_newsletter_closed', true );
	}

	/**
	 * Update information that the current user is subscribed
	 */
	private function mark_current_users_as_subscribed() {
		if ( ! $this->current_user_is_subscribed() ) {
			update_user_meta( get_current_user_id(), 'isc_newsletter_subscribed', true );
		}
	}
}
