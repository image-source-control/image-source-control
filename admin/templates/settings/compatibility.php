<?php
/**
 * Render compatibility notices on the settings page.
 *
 * @var array $notices Compatibility notices.
 */
?>
<ul>
	<?php foreach ( $notices as $notice ) : ?>
		<li>
			<strong><?php echo esc_html( $notice['name'] ); ?></strong>
			<?php if ( ! empty( $notice['description'] ) ) : ?>
				: <?php echo esc_html( $notice['description'] ); ?>
			<?php endif; ?>
			<?php if ( ! empty( $notice['manual_url'] ) ) : ?>
				<a href="<?php echo esc_url( $notice['manual_url'] ); ?>" target="_blank"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a>
			<?php endif; ?>
			<?php if ( ! empty( $notice['show_pro_link'] ) && ! \ISC\Plugin::is_pro() ) : ?>
				<?php echo ISC\Admin_Utils::get_pro_link( 'compatibility-' . sanitize_title( $notice['name'] ) ); ?>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
