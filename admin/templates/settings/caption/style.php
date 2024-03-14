<?php
/**
 * Render the caption style setting
 *
 * @var array $caption_style Caption style
 * @var array $caption_style_options Available caption style options
 */

?>
<div class="isc-settings-highlighted">
	<?php
	foreach ( $caption_style_options as $_key => $_options ) :
		$value  = $_options['value'] ?? '';
		$is_pro = ! empty( $_options['is_pro'] );
		?>
		<label>
			<input type="radio" name="isc_options[caption_style]" value="<?php echo esc_attr( $value ); ?>"
				<?php checked( $caption_style, $value ); ?>
				<?php echo $is_pro ? 'disabled="disabled" class="is-pro"' : ''; ?>
			/>
			<?php
			if ( $is_pro ) :
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ISC_Admin::get_pro_link( 'overlay-' . esc_html( $_options['label'] ) );
			endif;
			?>
			<?php echo isset( $_options['label'] ) ? esc_html( $_options['label'] ) : ''; ?>
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
			echo '<br>';
		endif;
		?>
	<?php endforeach; ?>
</div>