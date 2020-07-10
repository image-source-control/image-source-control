<div class="error"><p>
	<?php
	printf(
		wp_kses(
		// translators: %s is a URL
			__( 'One or more attachments still have no source. See the <a href="%s">missing sources</a> list', 'image-source-control-isc' ),
			array(
				'a' => array(
					'href' => array(),
				),
			)
		),
		esc_url( admin_url( 'upload.php?page=isc-sources' ) )
	);
	?>
</p></div>
