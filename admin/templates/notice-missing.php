<?php
/**
 * Render warning about number of missing sources.
 *
 * @var integer $missing_sources number of missing sources.
 */
?>
<div class="wrap">
	<div class="error"><p>
		<?php
		printf( _n( '%s image has no credits.', '%s images have no credits.', $missing_sources, 'image-source-control-isc' ), $missing_sources );
		echo ' ';
		printf(
			wp_kses(
			// translators: %1$s is an opening link tag, %2$s is a closing tag.
				__( 'See the %1$smissing sources%2$s list.', 'image-source-control-isc' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			'<a href="' . esc_url( admin_url( 'upload.php?page=isc-sources' ) ) . '">',
			'</a>'
		);
		?>
	</p></div>
</div>
