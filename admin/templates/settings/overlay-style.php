<?php
/**
 * Render the Overlay Style setting
 *
 * @var array $caption_style Caption style.
 * @var array $caption_style_options Information about the available options.
 */
?>
<div class="isc-settings-highlighted">
	<?php
	foreach ( $caption_style_options as $_key => $_options ) :
		$value  = isset( $_options['value'] ) ? $_options['value'] : '';
		$is_pro = ! empty( $_options['is_pro'] );
		?>
		<label>
			<input type="radio" name="isc_options[caption_style]" value="<?php echo esc_attr( $value ); ?>"
				<?php checked( $caption_style, $value ); ?>
				<?php echo $is_pro ? 'disabled="disabled" class="is-pro"' : ''; ?>
			/>
			<?php if ( $is_pro ) : echo ISC_Admin::get_pro_link( 'overlay-' . sanitize_title( $_options['label'] ) ); endif; ?>
			<?php echo isset( $_options['label'] ) ? esc_html( $_options['label'] ) : ''; ?>
		</label>
		<?php
		if ( isset( $_options['description'] ) ) :
			?>
			<p class="description">
				<?php
				echo wp_kses(
					$_options['description'],
					array(
						'code' => array(),
					)
				);
				?>
			</p>
		<?php
		endif;
		?><div id="isc-settings-overlay-caption-style-options-<?php echo $_key; ?>" class="isc-settings-overlay-caption-style-options"><?php
		// add style based options
		do_action( 'isc_overlay_caption_style_options_' . $_key );
		?></div>
	<?php endforeach; ?>
</div>