<?php
/**
 * Render the available AI image icons.
 */

$ai_image_options = ISC\Image_Sources\Ai_Labels::get_options();
?>
<ul>
	<?php foreach ( $ai_image_options as $value => $label ) : ?>
		<li>
			<?php echo ISC\Image_Sources\Image_Sources::sanitize_source_html( ISC\Image_Sources\Ai_Labels::get_icon( $value ) ); ?>
			<?php echo esc_html( $label ); ?>
		</li>
	<?php endforeach; ?>
</ul>
