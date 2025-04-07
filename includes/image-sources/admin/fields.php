<?php

namespace ISC\Image_Sources;

use ISC\Plugin;
use \ISC\Admin_Utils;

/**
 * Add fields for the image sources to the backend
 */
class Admin_Fields {
	use \ISC\Options;

	/**
	 * Constructor
	 */
	public function __construct() {
		// register attachment fields
		add_filter( 'attachment_fields_to_edit', [ $this, 'add_isc_fields' ], 10, 2 );
	}

	/**
	 * Add custom field to attachment
	 *
	 * @param array    $form_fields field fields.
	 * @param \WP_Post $post        post object.
	 *
	 * @return array with form fields
	 */
	public function add_isc_fields( $form_fields, $post ) {
		// Check if we should process this attachment based on settings
		if ( ! \ISC\Media_Type_Checker::should_process_attachment( $post ) ) {
			return $form_fields;
		}

		/**
		 * Return, when the ISC fields are enabled for blocks, and we are not using the block editor.
		 * It is tricky to detect and easy to break, so here is more information on it:
		 *
		 * Media modal on the block editor: uses AJAX and doesn’t "know" it is on a block editor page. But it knows that it comes from "wp-admin/post.php"
		 * Media modal in the Grid view of the Media Library: also uses AJAX, but the referrer is "wp-admin/upload.php"
		 * The List view of the Media Library does not open the modal, nor uses AJAX, but links to the attachment page; when testing, make sure to test a reload of the attachment page and when it was saved since the Referer then changes
		 * Classic Editor: not supported. I wasn’t able to find reliably parameters for it; technically, it looks like the media modal on the block editor; users can disable block support actively on these sites
		 */
		if ( ! empty( $_SERVER['HTTP_REFERER'] )
		     // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		     && strpos( $_SERVER['HTTP_REFERER'], 'wp-admin/post.php' ) !== false
		     && wp_doing_ajax()
		     // the filter allows users to force the ISC fields and Block options at the same time
		     && ( \ISC_Block_Options::enabled() && ! apply_filters( 'isc_force_block_options', false ) ) ) {

			$form_fields['isc_field_note'] = [
				'label' => __( 'Image Source', 'image-source-control-isc' ),
				'input' => 'html',
				'html'  => __( 'Find the image source fields in the image block options or media library.', 'image-source-control-isc' ),
			];

			return $form_fields;
		}

		if ( ! Plugin::is_pro() ) {
			$form_fields['isc_image_source_pro']['label'] = '';
			$form_fields['isc_image_source_pro']['input'] = 'html';
			$form_fields['isc_image_source_pro']['html']  = Admin_Utils::get_pro_link( 'attachment-edit' );
		}

		// add input field for source
		$form_fields['isc_image_source']['label'] = __( 'Image Source', 'image-source-control-isc' );
		$form_fields['isc_image_source']['value'] = Image_Sources::get_image_source_text_raw( $post->ID );
		$form_fields['isc_image_source']['helps'] = __( 'Include the image source here.', 'image-source-control-isc' );

		// add checkbox to mark as your own image
		$form_fields['isc_image_source_own']['input'] = 'html';
		$form_fields['isc_image_source_own']['label'] = __( 'Use standard source', 'image-source-control-isc' );
		$form_fields['isc_image_source_own']['helps'] =
			sprintf(
			// translators: %%1$s is an opening link tag, %2$s is the closing one
				__( 'Show a %1$sstandard source%2$s instead of the one entered above.', 'image-source-control-isc' ),
				'<a href="' . admin_url( 'options-general.php?page=isc-settings#isc_settings_section_misc' ) . '" target="_blank">',
				'</a>'
			) . '<br/>' .
			sprintf(
			// translators: %s is the name of an option
				__( 'Currently selected: %s', 'image-source-control-isc' ),
				\ISC\Standard_Source::get_standard_source_label()
			);
		$form_fields['isc_image_source_own']['html'] =
			"<input type='checkbox' value='1' name='attachments[{$post->ID}][isc_image_source_own]' id='attachments[{$post->ID}][isc_image_source_own]' "
			. checked( get_post_meta( $post->ID, 'isc_image_source_own', true ), 1, false )
			. ' style="width:14px"/> ';

		// add input field for source url
		$form_fields['isc_image_source_url']['label'] = __( 'Image Source URL', 'image-source-control-isc' );
		$form_fields['isc_image_source_url']['value'] = Image_Sources::get_image_source_url( $post->ID );
		$form_fields['isc_image_source_url']['helps'] = __( 'URL to link the source text to.', 'image-source-control-isc' );

		// add input field for license, if enabled
		$options  = $this->get_options();
		$licences = Utils::licences_text_to_array( $options['licences'] );
		if ( $options['enable_licences'] && $licences ) {
			$form_fields['isc_image_licence']['input'] = 'html';
			$form_fields['isc_image_licence']['label'] = __( 'Image License', 'image-source-control-isc' );
			$form_fields['isc_image_licence']['helps'] = __( 'Choose the image license.', 'image-source-control-isc' );
			$html                                      = '<select name="attachments[' . $post->ID . '][isc_image_licence]" id="attachments[' . $post->ID . '][isc_image_licence]">';
			$html                                     .= '<option value="">--</option>';
			foreach ( $licences as $_licence_name => $_licence_data ) {
				$html .= '<option value="' . $_licence_name . '" ' . selected( Image_Sources::get_image_license( $post->ID ), $_licence_name, false ) . '>' . $_licence_name . '</option>';
			}
			$html                                    .= '</select>';
			$form_fields['isc_image_licence']['html'] = $html;
		}

		// list posts the image is used in
		$form_fields['isc_image_usage']['input'] = 'html';
		$form_fields['isc_image_usage']['label'] = __( 'Appearances', 'image-source-control-isc' );
		$form_fields['isc_image_usage']['html']  = __( 'Where is this file used?', 'image-source-control-isc' );
		// add pro link
		$form_fields['isc_image_usage']['html'] .= ' ' . Admin_Utils::get_pro_link( 'media-library-usage' );

		return apply_filters( 'isc_admin_attachment_form_fields', $form_fields, $post, $options );
	}
}