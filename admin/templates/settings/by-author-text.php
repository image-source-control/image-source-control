<?php
/**
 * Render the By Author Text setting
 *
 * @var array $options ISC options.
 */
?>
<input type="text" id="byauthor" name="isc_options[by_author_text_field]" value="<?php echo esc_attr( $options['by_author_text'] ); ?>" <?php disabled( $options['use_authorname'] ); ?> class="regular-text" placeholder="<?php esc_html_e( 'Owned by the author', 'image-source-control' ); ?>"/>
<p class="description"><?php esc_html_e( "Enter the custom text to display if you do not want to use the author's public name.", 'image-source-control-isc' ); ?></p>

