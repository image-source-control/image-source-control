<?php
/**
 * Render the caption style setting
 *
 * @var array $caption_style Caption style
 * @var array $caption_style_options Available caption style options
 */

?>
<div class="isc-settings-highlighted" id="isc-settings-caption-style">
	<?php
	foreach ( $caption_style_options as $_key => $style_options ) :
		$value  = $style_options['value'] ?? '';
		$is_pro = ! empty( $style_options['is_pro'] );
		?>
		<label>
			<input type="radio" name="isc_options[caption_style]" value="<?php echo esc_attr( $value ); ?>"
				<?php checked( $caption_style, $value ); ?>
				<?php echo $is_pro ? 'disabled="disabled" class="is-pro"' : ''; ?>
			/>
			<?php
			if ( $is_pro ) :
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ISC\Admin_Utils::get_pro_link( 'overlay-' . esc_html( $style_options['label'] ) );
			endif;
			?>
			<?php echo isset( $style_options['label'] ) ? esc_html( $style_options['label'] ) : ''; ?>
		</label>
		<?php
		if ( isset( $style_options['description'] ) ) :
			?>
			<p class="description">
				<?php
				echo wp_kses(
					$style_options['description'],
					[
						'code' => [],
					]
				);
				?>
			</p>
			<?php
		else :
			echo '<br>';
		endif;
		?>
	<?php endforeach; ?>
	<p><a href="<?php echo esc_url( ISC\Admin_Utils::get_isc_localized_website_url( 'documentation/customizations/#isc_overlay_html_source', 'dokumentation/anpassungen/#isc_overlay_html_source', 'overlay-layout' ) ); ?>" target="_blank"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a></p>
</div>