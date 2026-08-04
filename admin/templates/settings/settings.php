<?php
/**
 * Render the settings page
 *
 * @var string $page                  The settings page.
 * @var array  $settings_section      The settings section.
 * @var array  $compatibility_notices Compatibility notices.
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
	<?php if ( ! empty( $compatibility_notices ) ) : ?>
		<div class="postbox isc_settings_section_compatibility" id="isc_settings_section_compatibility">
			<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Compatibility', 'image-source-control-isc' ); ?></h2></div>
			<div class="inside">
				<ul>
					<?php foreach ( $compatibility_notices as $notice ) : ?>
						<li>
							<strong><?php echo esc_html( $notice['name'] ); ?></strong>
							<?php if ( ! empty( $notice['description'] ) ) : ?>
								: <?php echo esc_html( $notice['description'] ); ?>
							<?php endif; ?>
							<?php if ( ! empty( $notice['manual_url'] ) ) : ?>
								<a href="<?php echo esc_url( $notice['manual_url'] ); ?>" target="_blank"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a>
							<?php endif; ?>
							<?php if ( ! empty( $notice['show_pro_link'] ) && ! \ISC\Plugin::is_pro() ) : ?>
								<?php echo ISC\Admin_Utils::get_pro_link( 'compatibility-' . sanitize_title( $notice['name'] ) ); ?>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>
</div>
