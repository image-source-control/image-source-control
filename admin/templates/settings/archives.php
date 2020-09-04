<?php
/**
 * Render Archive setting
 *
 * @var array $options ISC options.
 */
?>
<div id="isc-settings-highlighted">
	<p class="description"><?php esc_html_e( 'The following options try to place image sources within post content on post list pages like your home page or category archives.', 'image-source-control-isc' ); ?></p>
	<label>
		<input type="checkbox" name="isc_options[list_on_archives]" id="list-on-archives" value="1" <?php checked( 1, $options['list_on_archives'], true ); ?> />
		<?php
		esc_html_e( 'Display sources list below full posts', 'image-source-control-isc' );
		?>
	</label>
	<p class="description"><?php esc_html_e( 'Choose this option if you want to display the sources list attached to posts on archive and category pages that display the full content.', 'image-source-control-isc' ); ?></p>
	<label>
		<input type="checkbox" name="isc_options[list_on_excerpts]" id="list-on-excerpts" value="1" <?php checked( 1, $options['list_on_excerpts'], true ); ?> />
		<?php
		esc_html_e( 'Display sources list below excerpts', 'image-source-control-isc' );
		?>
	</label>
	<p class="description"><?php esc_html_e( 'Choose this option if you want to display the source of the featured image below the post excerpt. The source will be attached to the excerpt and it might happen that you see it everywhere. If this happens you should display the source manually in your template.', 'image-source-control-isc' ); ?></p>
</div>
<?php
