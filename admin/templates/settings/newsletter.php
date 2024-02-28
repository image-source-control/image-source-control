<?php
/**
 * Render newsletter signup form
 *
 * @var string|false $email user email address or false, if none is set
 * @var string       $signup_url URL to the signup form on our homepage
 */

?>
<p>
	<?php
	esc_html_e( 'Receive an email about updates, new features, discounts, and images in WordPress.', 'image-source-control-isc' );
	?>
	<br/>
	<?php
	esc_html_e( 'No spam, no sharing of your information with others.', 'image-source-control-isc' );
	if ( $email ) :
		echo ' ';
		printf(
		// translators: %s is an email address
			esc_html__( 'Sign up %s.', 'image-source-control-isc' ),
			// the email address is already validated
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$email
		);
		?>
<br/>
</p><p>
<button id="isc-signup-nl" class="button button-primary" type="button" disabled="disabled">
		<?php esc_html_e( 'Sign up', 'image-source-control-isc' ); ?>
</button>
	<?php endif; ?>
	<a href="<?php echo esc_url( $signup_url ); ?>" target="_blank"><?php esc_html_e( 'Sign up manually.', 'image-source-control-isc' ); ?></a>
</p>
<p id="isc-signup-loader" class="hidden"><span class="spinner is-active"></span></p>
<p id="isc-signup-nl-error" class="notice notice-error hidden"></p>
<p id="isc-signup-nl-success" class="notice notice-success hidden"></p>
