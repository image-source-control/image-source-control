<?php
/**
 * Render image source box
 *
 * Do not change the file. The styling is exactly as needed also to create an empty box as a placeholder.
 *
 * @var string $content list of image source or other content.
 * @var string $headline headline for the image list.
 * @var bool   $create_placeholder whether to create a placeholder or not.
 */

?>
<div class="isc_image_list_box"><?php if ( ! $create_placeholder ) : ?>
	<p class="isc_image_list_title"><?php echo esc_html( $headline ); ?></p>
	<?php echo $content; ?>
<?php endif; ?></div>
