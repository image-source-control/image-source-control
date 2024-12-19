<?php
/**
 * Render settings to enable Per-page lists in the content
 *
 * @var array $options ISC options.
 */
?>
<div class="isc-settings-highlighted">
<label>
	<input type="checkbox" name="isc_options[display_type][]" value="list" <?php checked( in_array( 'list', $options['display_type'], true ), true ); ?> />
	<?php
	esc_html_e( 'Insert below the content', 'image-source-control-isc' );
	?>
</label>
<p class="description"><?php esc_html_e( 'Automatically inserts the list of image sources below posts and pages.', 'image-source-control-isc' ); ?></p>
<label class="isc-empty-label">
	<?php
	esc_html_e( 'Insert manually', 'image-source-control-isc' );
	?>
</label>
	<p class="description">
		<?php
		printf(
		// translators: %s is a shortcode.
			esc_html__( 'Place the shortcode %s anywhere in your content or widget to show the image source list.', 'image-source-control-isc' ),
			wp_kses(
				'<code>[isc_list]</code>',
				[
					'code' => [],
				]
			)
		);
		?>
		<a href="<?php echo esc_url( ISC\Admin_Utils::get_manual_url( 'settings-per-page-list-position' ) ); ?>" target="_blank"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a>
	</p>
	<h4><?php esc_html_e( 'Archive pages', 'image-source-control-isc' ); ?></h4>
	<p class="description"><?php esc_html_e( 'The following options try to place image sources within post content on post list pages like your home page or category archives.', 'image-source-control-isc' ); ?></p>
	<label>
		<input type="checkbox" name="isc_options[list_on_archives]" id="list-on-archives" value="1" <?php checked( 1, $options['list_on_archives'], true ); ?> />
		<?php
		esc_html_e( 'Insert below full posts', 'image-source-control-isc' );
		?>
	</label>
	<p class="description"><?php esc_html_e( 'Choose this option if you want to display the sources list attached to posts on archive and category pages that display the full content.', 'image-source-control-isc' ); ?></p>
	<label>
		<input type="checkbox" name="isc_options[list_on_excerpts]" id="list-on-excerpts" value="1" <?php checked( 1, $options['list_on_excerpts'], true ); ?> />
		<?php
		esc_html_e( 'Insert below excerpts', 'image-source-control-isc' );
		?>
	</label>
	<p class="description"><?php esc_html_e( 'Choose this option if you want to display the source of the featured image below the post excerpt. The source will be attached to the excerpt and it might happen that you see it everywhere. If this happens you should display the source manually in your template.', 'image-source-control-isc' ); ?></p>
</div>
<?php
