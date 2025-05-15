<?php
/**
 * Render layout settings for the Per-page lists in the content
 *
 * @var bool $list_layout_details value of the list_layout[details] option.
 * @var bool $is_pro_enabled whether the Pro version is enabled.
 */

?>
<div class="isc-settings-highlighted">
<label>
	<input type="checkbox" name="isc_options[list_layout][details]" value="1" <?php checked( $list_layout_details ); ?> <?php echo ! $is_pro_enabled ? 'disabled="disabled"' : ''; ?>/>
	<?php
	if ( ! $is_pro_enabled ) :
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ISC\Admin_Utils::get_pro_link( 'page-list-layout-details' );
endif;
	?>
	<?php
	esc_html_e( 'Expandable list', 'image-source-control-isc' );
	?>
</label>
<p class="description">
	<?php
	esc_html_e( 'Show as an expandable list that opens on click.', 'image-source-control-isc' );
	?>
</div>
<?php
