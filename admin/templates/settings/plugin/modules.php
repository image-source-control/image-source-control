<?php
/**
 * Render the Modules option for the Plugin options
 *
 * @var array $modules_options The options for the modules
 * @var array $modules_options_selected The selected modules
 */

// show a warning if no modules are selected
if ( empty( $modules_options_selected ) ) :
	?>
	<div class="message error">
		<p class="description">
			<?php esc_html_e( 'No modules selected.', 'image-source-control-isc' ); ?>
		</p>
	</div>
<?php
endif;
?>
<div id="isc-settings-plugin-modules" class="isc-settings-highlighted">
	<?php
	foreach ( $modules_options as $key => $_options ) :
		$is_pro = ! empty( $_options['is_pro'] );
		?>
		<label>
			<input type="checkbox" name="isc_options[modules][]" value="<?php echo esc_attr( $key ); ?>"
				<?php checked( in_array( $key, $modules_options_selected, true ) ); ?>
				<?php echo $is_pro ? 'disabled="disabled" class="is-pro"' : ''; ?>
			/>
			<?php
			if ( $is_pro ) :
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ISC\Admin_Utils::get_pro_link( 'plugin-modules-' . sanitize_title( $_options['label'] ) );
endif;
			?>
			<?php echo isset( $_options['label'] ) ? esc_html( $_options['label'] ) : ''; ?>
		</label><br/>
		<?php
		if ( isset( $_options['description'] ) ) :
			?>
			<p class="description">
				<?php
				echo esc_html( $_options['description'] );
				?>
			</p>
			<?php
		endif;
	endforeach;
	?>
</div>
