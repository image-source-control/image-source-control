<?php
/**
 * Render the available AI image icons.
 */

$ai_image_labels = ISC\Image_Sources\Ai_Labels::get_labels();
?>
<ul>
	<?php foreach ( $ai_image_labels as $value => $label ) : ?>
		<li>
			<?php echo ISC\Image_Sources\Ai_Labels::get_icon( $value ); ?>
			<?php echo esc_html( $label ); ?>
		</li>
	<?php endforeach; ?>
</ul>
