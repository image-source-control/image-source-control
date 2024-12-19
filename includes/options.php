<?php

namespace ISC;

/**
 * Handling main plugin options
 */
trait Options {

	/**
	 * Returns isc_options if it exists, returns the default options otherwise.
	 *
	 * @return array
	 */
	public static function get_options(): array {
		$options = get_option( 'isc_options', self::default_options() );

		if ( ! is_array( $options ) ) {
			$options = self::default_options();
		}

		return $options;
	}

	/**
	 * Returns default options
	 *
	 * @return string[]
	 */
	public static function default_options(): array {
		include ISCPATH . 'includes/default-licenses.php';
		global $isc_default_licenses;

		$default['display_type']              = [ 'list' ];
		$default['list_on_archives']          = false;
		$default['list_on_excerpts']          = false;
		$default['image_list_headline']       = __( 'image sources', 'image-source-control-isc' );
		$default['version']                   = ISCVERSION;
		$default['images_per_page']           = 99999;
		$default['thumbnail_in_list']         = false;
		$default['thumbnail_size']            = 'thumbnail';
		$default['thumbnail_width']           = 150;
		$default['thumbnail_height']          = 150;
		$default['warning_onesource_missing'] = true;
		$default['remove_on_uninstall']       = false;
		$default['caption_position']          = 'top-left';
		$default['caption_style']             = null;
		$default['source_pretext']            = __( 'Source:', 'image-source-control-isc' );
		$default['enable_licences']           = false;
		$default['licences']                  = apply_filters( 'isc-licences-list', $isc_default_licenses );
		$default['list_included_images']      = '';
		$default['overlay_included_images']   = '';
		$default['block_options']             = true;
		$default['enable_log']                = false;
		$default['standard_source']           = 'custom_text';
		$default['standard_source_text']      = '';

		/**
		 * Allow manipulating defaults for plugin settings
		 */
		return apply_filters( 'isc_default_settings', $default );
	}
}