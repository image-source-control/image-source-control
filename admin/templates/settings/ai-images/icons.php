<?php
/**
 * Render the available AI image icons.
 */

$ai_image_labels = ISC\Image_Sources\Ai_Labels::get_labels();
?>
<ul class="isc-settings-ai-images-icons">
	<?php foreach ( $ai_image_labels as $value => $label ) : ?>
		<li title="<?php echo esc_attr( $label ); ?>">
			<?php echo ISC\Image_Sources\Ai_Labels::get_icon( $value ); ?>
		</li>
	<?php endforeach; ?>
</ul>
