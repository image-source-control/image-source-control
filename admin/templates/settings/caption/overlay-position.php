<?php
/**
 * Render the Overlay Position setting
 *
 * @var array $options ISC options.
 */
?>
<h4><?php esc_html_e( 'Overlay position', 'image-source-control-isc' ); ?></h4>
<div id="isc-settings-caption-position-options-wrapper">
	<div id="isc-settings-caption-pos-options">
		<?php
		foreach ( $this->caption_position as $position) {
			$selected = $options['caption_position'] === $position ? ' selected' : '';
			$extraClass = $position === 'center' ? ' center' : '';
			echo "<button type='button' class='$selected$extraClass' data-position='$position'><span></span></button>";
		}
		?>
	</div>
	<input type="hidden" name="isc_options[caption_position]" id="isc-settings-caption-position" value="<?php echo esc_attr( $options['caption_position'] ); ?>">

	<div id="isc-settings-caption-preview" class="hidden"></div>
</div>