<?php
/**
 * Render the advanced included images option for captions
 *
 * @var array $checked_advanced_options selected options
 * @var array $advanced_options         available options.
 */
?>
<h4><?php esc_html_e( 'Developer Options', 'image-source-control-isc' ); ?></h4>
<p class="description">
	<?php esc_html_e( 'Find images and display overlays in sensitive areas in your site’s HTML. They might need additional code to work or for styling.', 'image-source-control-isc' ); ?>
	<a href="<?php echo esc_url( ISC\Admin_Utils::get_isc_localized_website_url( 'blog/image-captions-for-background-images/', 'bildquellen-hintergrundbilder/', 'overlay-developer-options' ) ); ?>" target="_blank"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a>
</p>
<div>
	<?php
	foreach ( $advanced_options as $_key => $_options ) :
		$value = $_options['value'] ?? '';
		?>
		<label>
			<input type="checkbox" name="isc_options[overlay_included_advanced][]" value="<?php echo esc_attr( $value ); ?>"
				<?php checked( in_array( $value, $checked_advanced_options ) ); ?>
				<?php echo ! empty( $_options['is_pro'] ) ? 'disabled="disabled" class="is-pro"' : ''; ?>
			/>
			<?php
			if ( isset( $_options['label'] ) ) :
				echo wp_kses(
					$_options['label'],
					[
						'code' => [],
						'a'    => [
							'href'   => [],
							'target' => [],
						],
					]
				);
			endif;
			?>
		</label>
		<?php
		if ( isset( $_options['description'] ) ) :
			?>
			<p class="description">
				<?php
				echo wp_kses(
					$_options['description'],
					[
						'code' => [],
					]
				);
				?>
			</p>
			<?php
		else :
			?>
			<br>
			<?php
		endif;
		?>
	<?php endforeach; ?>
	<?php if ( ! \ISC\Plugin::is_pro() ) : ?>
		<p>
			<?php echo ISC\Admin_Utils::get_pro_link( 'overlay-developer-options-pitch' ); ?>
		</p>
	<?php endif; ?>
</div>
