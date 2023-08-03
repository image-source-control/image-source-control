<?php
/**
 * Render content of a section on the Tools page
 *
 * @var string $id ID of the section.
 * @var string $title Title of the section.
 * @var string $section HTML content of the section.
 */
?>
<div class="postbox" id="<?php echo esc_attr( $id ); ?>">
<?php
	if ( $title ) {
		?>
		<div class="postbox-header"><h2 class="hndle"><?php echo esc_html( $title ); ?></h2></div>
		<?php
	}
	?>
	<div class="inside">
		<div class="submitbox">
			<?php echo $section; ?>
		</div>
	</div>
</div>
