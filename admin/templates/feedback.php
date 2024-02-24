<?php
/**
 * Plugin deactivation feedback modal.
 *
 * @var string $from  FROM header for the email.
 * @var string $email Email address of the current user.
 */
?>
<div id="isc-feedback-overlay" style="display: none;">
	<div id="isc-feedback-content">
		<span id="isc-feedback-overlay-close-button">&#x2715;</span>
		<form action="" method="post" autocomplete="off">
			<p>
				<span class="dashicons dashicons-admin-comments"></span>
				<strong>
				<?php
					printf(
						// translators: %s is the plugin name, %d is the year the plugin was first released
						esc_html__( 'Your feedback shapes %1$s since %2$d', 'image-source-control-isc' ),
						esc_html( ISCNAME ),
						2012
					);
					?>
					</strong>
			</p>
			<textarea class="isc-feedback-text" name="isc-feedback-text" placeholder="<?php esc_attr_e( 'How can I improve Image Source Control?', 'image-source-control-isc' ); ?>"></textarea>
			<ul>
				<li><?php esc_html_e( 'Was anything confusing?', 'image-source-control-isc' ); ?></li>
				<li><?php esc_html_e( 'Did you expect different features?', 'image-source-control-isc' ); ?></li>
				<li><?php esc_html_e( 'Did you experience a technical issue?', 'image-source-control-isc' ); ?></li>
			</ul>
			<?php if ( $email ) : ?>
				<label>
					<input type="checkbox" id="isc-feedback-send-reply" name="isc-feedback-send-reply" value="1"/>
					<?php esc_html_e( 'I am open to chat', 'image-source-control-isc' ); ?>
				</label>
				<input type="email" id="isc-feedback-reply-email" name="isc-feedback-reply-email" value="<?php echo esc_attr( $email ); ?>" style="display: none;"/>
			<?php endif; ?>
			<?php if ( $from ) : ?>
				<input type="hidden" name="isc-feedback-from" value="<?php echo esc_attr( $from ); ?>"/>
			<?php endif; ?>
			<hr>
			<input class="isc-feedback-submit button button-primary" type="submit" value="<?php esc_attr_e( 'Send feedback & deactivate', 'image-source-control-isc' ); ?>"/>
			<input class="isc-feedback-only-deactivate button" type="submit" value="<?php esc_html_e( 'Just deactivate', 'image-source-control-isc' ); ?>"/>
			<?php wp_nonce_field( 'isc-feedback-form', 'isc-feedback-form-nonce' ); ?>
		</form>
		<div id="isc-feedback-after-submit" style="display: none">
			<h2>
				<?php esc_html_e( 'Thanks for submitting your feedback. I will get in touch soon.', 'image-source-control-isc' ); ?>
			</h2>
			<p>
				<?php esc_html_e( 'Disabling the plugin now…', 'image-source-control-isc' ); ?>
			</p>
		</div>
	</div>
</div>
