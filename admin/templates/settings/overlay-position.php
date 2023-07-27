<?php
/**
 * Render the Overlay Position setting
 *
 * @var array $options ISC options.
 */
?>
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

	<div id="isc-settings-caption-preview" class="hidden">
		<iframe src="<?php echo ISCBASEURL . 'admin/templates/settings/preview/caption-preview.html'; ?>?path=<?php echo urlencode( ISCBASEURL ); ?>&caption_position=<?php echo urlencode( $options['caption_position'] ); ?>" width="250" height="181"></iframe>
	</div>
</div>