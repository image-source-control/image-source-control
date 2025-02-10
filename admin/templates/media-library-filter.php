<?php
/**
 * Add a filter to the Media Library view.
 *
 * @var array $filters
 * @var string $filter_current
 */

?>
<select name="isc_filter" id="isc_media_library_filter">
	<option value=""><?php esc_html_e( 'All images', 'image-source-control-isc' ); ?></option>
	<?php foreach ( $filters as $filter ) : ?>
		<option value="<?php echo esc_attr( $filter['value'] ); ?>" <?php selected( $filter['value'], $filter_current ); ?>>
			<?php echo esc_html( $filter['label'] ); ?>
		</option>
	<?php endforeach; ?>
</select>