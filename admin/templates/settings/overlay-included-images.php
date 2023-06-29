<?php
/**
 * Render the Included Images option for captions
 *
 * @var string|bool $included_images value of the "overlay_included_images" option.
 * @var array $included_images_options information about the available options.
 * @var array $advanced_options information about advanced options.
 */
?>
<p class="description"><?php esc_html_e( 'Choose which images should show a source overlay.', 'image-source-control-isc' ); ?></p>
<div class="isc-settings-highlighted">
	<?php
	foreach ( $included_images_options as $_key => $_options ) :
		$value  = $_options['value'] ?? '';
		$is_pro = ! empty( $_options['is_pro'] );
		?>
		<label>
			<input type="radio" name="isc_options[overlay_included_images]" value="<?php echo esc_attr( $value ); ?>"
				<?php checked( $included_images, $value ); ?>
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
			?>
	<?php endforeach; ?>
</div>
