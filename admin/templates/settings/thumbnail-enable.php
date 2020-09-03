<?php
/**
 * Render the Thumbnail setting to enable the use of thumbnails
 *
 * @var array $options ISC options.
 */
?>
<input type="checkbox" id="use-thumbnail" name="isc_options[use_thumbnail]" value="1" <?php checked( $options['thumbnail_in_list'] ); ?> />
<select id="thumbnail-size-select" name="isc_options[size_select]" <?php disabled( ! $options['thumbnail_in_list'] ); ?>>
    <?php foreach ( $this->thumbnail_size as $size ) : ?>
        <option value="<?php echo esc_html( $size ); ?>" <?php selected( $size, $options['thumbnail_size'] ); ?>><?php echo esc_html( $size ); ?></option>
    <?php endforeach; ?>
</select>
<p class="description"><?php esc_html_e( 'Display thumbnails on the list of all images in the blog.', 'image-source-control-isc' ); ?></p>
