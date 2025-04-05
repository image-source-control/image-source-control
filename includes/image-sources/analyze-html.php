<?php

namespace ISC\Image_Sources;

use ISC_Log;

/**
 * Analyze chunks of HTML
 */
class Analyze_HTML {

	/**
	 * Extract image-related markup from larger chunks of HTML.
	 *
	 * @param string $html HTML to extract images from.
	 * @return array Array of image markup.
	 */
	public function extract_images_from_html( string $html ): array {
		/**
		 * Removed [caption], because this check runs after the hook that interprets shortcodes
		 * img tag is checked individually since there is a different order of attributes when images are used in gallery or individually
		 *
		 * 0 – full match
		 * 1 - <figure> if set and having a class attribute
		 * 2 – classes from figure tag
		 * 3 – inner code starting with <a>
		 * 4 – opening link tag
		 * 5 – "rel" attribute from link tag
		 * 6 – image id from link wp-att- value in "rel" attribute
		 * 7 – full img tag
		 * 8 – image URL
		 * 9 – closing link tag
		 *
		 * tested with:
		 * * with and without [caption]
		 * * with and without link attribute
		 *
		 * potential issues:
		 * * line breaks in the code – use \s* where potential line breaks could appear
		 *
		 * Use (\x20|\x9|\xD|\xA)+ to match whitespace following HTML starting tag name according to W3C REC 3.1. See issue PR #136
		 */
		$pattern = apply_filters( 'isc_public_caption_regex', '#(?:<figure[^>]*class="([^"]*)"[^>]*>\s*)?((<a[\x20|\x9|\xD|\xA]+[^>]*>)?\s*(<img[\x20|\x9|\xD|\xA]+[^>]*[^>]*src="(.+)".*\/?>).*(\s*</a>)??[^<]*)#isU', $html );
		preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER );

		/**
		 * Convert the matches into a multidimensional array with string keys to simplify usage and manipulation
		 */
		$matches_new = array_map(
			function ( $match ) {
				return [
					'full'         => $match[0] ?? '',
					'figure_class' => $match[1] ?? '',
					'inner_code'   => $match[2] ?? '',
					'img_src'      => $match[5] ?? '',
				];
			},
			$matches
		);

		/**
		 * Filter matches from regex
		 */
		return apply_filters( 'isc_extract_images_from_html', $matches_new, $matches, $pattern );
	}

	/**
	 * Extract image ID from HTML
	 *
	 * @param string $html HTML to extract an image ID from.
	 * @return int Image ID.
	 */
	public function extract_image_id( string $html ): int {
		if ( ! $html ) {
			return 0;
		}

		$id = 0;

		/**
		 * Image IDs seem to be in the following elements:
		 * - img tag with class "wp-image-123"
		 * - img tag with data-id="123"
		 */
		$success = preg_match( '#wp-image-(\d+)|data-id="(\d+)#is', $html, $matches_id );
		if ( $success ) {
			$id = $matches_id[1] ? intval( $matches_id[1] ) : intval( $matches_id[2] );
			ISC_Log::log( sprintf( 'found ID "%s"', $id ) );
		} else {
			ISC_Log::log( sprintf( 'no ID found for "%s"', $html ) );
		}

		return $id;
	}

	/**
	 * Extract the image URL from HTML, namely, the value of the src attribute
	 *
	 * @param string $html HTML to extract an image URL from.
	 * @return string Image URL.
	 */
	public function extract_image_src( string $html ): string {
		if ( ! $html ) {
			return '';
		}

		$src = '';

		$success = preg_match( '#src="([^"]+)"#is', $html, $matches_src );
		if ( $success ) {
			$src = $matches_src[1];
			ISC_Log::log( sprintf( 'found src "%s"', $src ) );
		} else {
			ISC_Log::log( sprintf( 'no src found for "%s"', $html ) );
		}

		return $src;
	}

	/**
	 * Extract image URLs from HTML.
	 * One image URL per HTML attribute will be found.
	 *
	 * Limitations:
	 * - retrieves the first valid image URL from any HTML tag
	 * - if an IMG tag has a SRC attribute with a valid image URL, all other tags with valid URLs are ignored
	 * - technically, one could generate tags with different images from the Media Library, e.g., having a differently cut URL in a data-attribute
	 *  that then shows dynamically using JavaScript
	 *  this would cause one of the images not being listed with a source or a used image
	 *  since I haven’t seen that in the wild, this case is deliberately ignored
	 *
	 * @param string $html Any HTML code.
	 * @return array List of image URLs.
	 */
	public static function extract_image_urls_from_html_tags( $html = '' ): array {
		$urls = [];

		if ( empty( $html ) ) {
			ISC_Log::log( 'Exit due to missing empty HTML' );
			return $urls;
		}

		ISC_Log::log( 'look for image IDs within the HTML' );

		$types = implode( '|', \ISC\Image_Sources\Image_Sources::get_instance()->allowed_extensions );
		/**
		 * Look for any URLs
		 * - starting with http, or https
		 * - ending with a valid image format or " or '
		 * - or any URL in the SRG attribute of IMG tags, where we ignore the extension
		 *
		 * What it does
		 * (?<!, ) – needed to prevent multiple images from `srcset` attribute to being considered here
		 * (http[s]?:) - start with http or https
		 * [^\'^"^ ] - stop at one of these chars since they don't belong into URLs: ", ', or space
		 * or looks for any URL in the SRC attribute
		 *
		 * Limitations
		 * - we don't include images that don't have a full path, e.g., url("../img/image.png") or url("image.png"); they are likely not in the Media library
		 */
		$pattern = '#((?<!, )((http[s]?:)[^\'^"^ ]*\.(' . $types . ')))|(<img[\x20|\x9|\xD|\xA]+[^>]*[^>]*src="(.+)".*\/?>)#isU';

		/**
		 * Match index
		 * 0 - full match (including first char)
		 * 1 - URLs with valid image file extension
		 * 2 - ignore
		 * 3 – protocol
		 * 4 - file format (e.g. "jpg")
		 * 5 – full img tag
		 * 6 - URLs from img tags – the file extension is ignored here
		 */
		preg_match_all( $pattern, $html, $matches );

		// merge matches from both conditions and remove empty ones
		$urls = array_filter( array_merge( $matches[1], $matches[6] ) );
		// remove duplicate URLs
		return array_values( array_unique( $urls ) );
	}

	/**
	 * Extract any image URLs from HTML.
	 *
	 * @param string $html Any HTML code.
	 *
	 * @return array List of image URLs.
	 */
	public static function extract_image_urls( string $html = '' ): array {
		$urls = [];

		if ( empty( $html ) ) {
			\ISC_Log::log( 'Exit due to empty HTML' );
			return $urls;
		}

		\ISC_Log::log( 'Looking for valid image URLs within HTML' );

		// Get allowed image extensions as a pipe-separated string.
		$types = implode( '|', \ISC\Image_Sources\Image_Sources::get_instance()->allowed_extensions );

		/**
		 * The regex below matches:
		 * - URLs starting with "http://" or "https://"
		 * - Followed by one or more non-whitespace characters (non-greedy)
		 * - Ending with a dot and one of the allowed extensions (case-insensitive)
		 * - Optionally followed by a query string
		 * - The URL is expected to be wrapped in either:
		 * -- single or double quotes
		 * -- whitespace
		 * -- brackets "()"
		 */
		$pattern = '#https?://\S+?\.(?:' . $types . ')(?:\?\S*)?(?=["\'\s\)])#i';

		preg_match_all( $pattern, $html, $matches );

		if ( ! empty( $matches[0] ) ) {
			// Remove duplicate URLs.
			return array_values( array_unique( $matches[0] ) );
		}

		return $urls;
	}
}
