<?php
/**
 * Render the Image Index option for the Global List
 *
 * @var bool $indexed_images value of the "indexed_images" option.
 * @var bool $is_pro_enabled is the pro version active.
 */

?>
<hr>
<div class="isc-settings-highlighted">
		<label>
			<input type="checkbox" id="isc-settings-global-list-indexed-images" name="isc_options[global_list_indexed_images]" value="1"
				<?php checked( $indexed_images ); ?>
				<?php echo ! $is_pro_enabled ? 'disabled="disabled" class="is-pro"' : ''; ?>
			/>
			<?php
			if ( ! $is_pro_enabled ) :
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ISC_Admin::get_pro_link( 'global-list-indexed-images' );
endif;
			?>
			<?php esc_html_e( 'Index images outside the content', 'image-source-control-isc' ); ?>
		</label>
			<?php
			if ( isset( $_options['description'] ) ) :
				?>
		<p class="description">
				<?php
				echo esc_html__( 'Associate images inside and outside the main content with the post they appear on.', 'image-source-control-isc' )
				. ' ' . esc_html__( 'Including header, sidebar, and footer.', 'image-source-control-isc' )
				. ' ' . sprintf(
				// translators: %d is the number of pages.
					esc_html__( 'Capped at %d posts per image.', 'image-source-control-isc' ),
					10
				);
				?>
		</p>
		<p class="notice notice-error hidden" id="isc-settings-global-list-indexed-images-warning">
				<?php
				echo esc_html__( 'When changing this option, the image-post index is reset.', 'image-source-control-isc' ) . ' ';
				printf(
				// translators: %s is the column name.
					esc_html__( 'The %s column is filled by reindexing all posts again.', 'image-source-control-isc' ),
					'”' . esc_html__( 'Attached to', 'image-source-control-isc' ) . '”'
				);
				?>
		</p>
				<?php
			endif;
			?>
</div>
