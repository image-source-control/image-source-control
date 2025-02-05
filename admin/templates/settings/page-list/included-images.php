<?php
/**
 * Render the Included Images option
 *
 * @var string|bool $included_images value of the "list_included_images" option.
 * @var array $included_images_options information about the available options.
 */
?>
<p class="description"><?php esc_html_e( 'Choose which images and sources should be listed in the source list of the current page.', 'image-source-control-isc' ); ?></p>
<div class="isc-settings-highlighted">
	<?php
	foreach ( $included_images_options as $_key => $_options ) :
		$value  = isset( $_options['value'] ) ? $_options['value'] : '';
		$is_pro = ! empty( $_options['is_pro'] );
		?>
		<label>
			<input type="radio" name="isc_options[list_included_images]" value="<?php echo esc_attr( $value ); ?>"
				<?php checked( $included_images, $value ); ?>
				<?php echo $is_pro ? 'disabled="disabled"' : ''; ?>
			/>
			<?php if ( $is_pro ) : echo ISC\Admin_Utils::get_pro_link( 'page-list-' . sanitize_title( $_options['label'] ) ); endif; ?>
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
