<?php
/**
 * Render the settings page
 *
 * @var string $page            The settings page.
 * @var array  $settings_section The settings section.
 */

?>
<div class="wrap metabox-holder">
	<form id="isc-section-wrapper" method="post" action="options.php">
		<?php
		foreach ( (array) $settings_section as $section ) {

			?>
			<div class="postbox <?php echo esc_attr( $section['id'] ); ?>" id="<?php echo esc_attr( $section['id'] ); ?>">
				<?php
				if ( $section['title'] ) {
					?>
					<div class="postbox-header"><h2 class="hndle"><?php echo esc_html( $section['title'] ); ?></h2>
					<?php if ( ! empty( $section['close_button'] ) ) : ?>
						<span class="dashicons dashicons-no-alt"></span>
					<?php endif; ?>
					</div>
					<?php
				}
				?>
				<div class="inside">
					<div class="submitbox">
						<?php
						if ( $section['callback'] ) {
							call_user_func( $section['callback'], $section );
						}
						?>
						<table class="form-table" role="presentation">
							<?php
							do_settings_fields( $page, $section['id'] );
							?>
						</table>
					</div>
				</div>
			</div>
			<?php
		}

		settings_fields( 'isc_options_group' );
		?>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'image-source-control-isc' ); ?>">
		</p>
	</form>
</div>
