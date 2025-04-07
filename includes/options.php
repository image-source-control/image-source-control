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
		$isc_default_licenses = 'All Rights Reserved
Public Domain Mark 1.0|https://creativecommons.org/publicdomain/mark/1.0/
CC0 1.0 Universal|https://creativecommons.org/publicdomain/zero/1.0/
CC BY 4.0 International|https://creativecommons.org/licenses/by/4.0/
CC BY-SA 4.0 International|https://creativecommons.org/licenses/by-sa/4.0/
CC BY-ND 4.0 International|https://creativecommons.org/licenses/by-nd/4.0/
CC BY-NC 4.0 International|https://creativecommons.org/licenses/by-nc/4.0/
CC BY-NC-SA 4.0 International|https://creativecommons.org/licenses/by-nc-sa/4.0/
CC BY-NC-ND 4.0 International|https://creativecommons.org/licenses/by-nc-nd/4.0/
CC BY 3.0 Unported|https://creativecommons.org/licenses/by/3.0/
CC BY-SA 3.0 Unported|https://creativecommons.org/licenses/by-sa/3.0/
CC BY-ND 3.0 Unported|https://creativecommons.org/licenses/by-nd/3.0/
CC BY-NC 3.0 Unported|https://creativecommons.org/licenses/by-nc/3.0/
CC BY-NC-SA 3.0 Unported|https://creativecommons.org/licenses/by-nc-sa/3.0/
CC BY-NC-ND 3.0 Unported|https://creativecommons.org/licenses/by-nc-nd/3.0/
CC BY 2.5 Generic|https://creativecommons.org/licenses/by/2.5/
CC BY-SA 2.5 Generic|https://creativecommons.org/licenses/by-sa/2.5/
CC BY-ND 2.5 Generic|https://creativecommons.org/licenses/by-nd/2.5/
CC BY-NC 2.5 Generic|https://creativecommons.org/licenses/by-nc/2.5/
CC BY-NC-SA 2.5 Generic|https://creativecommons.org/licenses/by-nc-sa/2.5/
CC BY-NC-ND 2.5 Generic|https://creativecommons.org/licenses/by-nc-nd/2.5/
CC BY 2.0 Generic|https://creativecommons.org/licenses/by/2.0/
CC BY-SA 2.0 DE|https://creativecommons.org/licenses/by-sa/2.0/de/deed
CC BY-SA 2.0 Generic|https://creativecommons.org/licenses/by-sa/2.0/
CC BY-ND 2.0 Generic|https://creativecommons.org/licenses/by-nd/2.0/
CC BY-NC 2.0 Generic|https://creativecommons.org/licenses/by-nc/2.0/
CC BY-NC-SA 2.0 Generic|https://creativecommons.org/licenses/by-nc-sa/2.0/
CC BY-NC-ND 2.0 Generic|https://creativecommons.org/licenses/by-nc-nd/2.0/
FAL Free Art License 1.3 |http://artlibre.org/licence/lal/en/
GFDL GNU Free Documentation License 1.2|https://www.gnu.org/licenses/fdl-1.2.html
GFDL GNU Free Documentation License 1.3|https://www.gnu.org/licenses/fdl-1.3.html';

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
		$default['images_only']               = false;

		/**
		 * Allow manipulating defaults for plugin settings
		 */
		return apply_filters( 'isc_default_settings', $default );
	}
}
