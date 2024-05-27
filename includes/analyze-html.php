<?php
/**
 * Analyze chunks of HTML
 */
namespace ISC;
class Analyze_HTML {

	/**
	 * Extract image-related markup from larger chunks of HTML.
	 *
	 * @param string $html HTML to extract images from.
	 * @return array Array of image markup.
	 */
	public function extract_images_from_html( string $html ) : array {
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
			function( $match ) {
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
	public function extract_image_id( string $html ) : int {
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
			\ISC_Log::log( sprintf( 'found ID "%s"', $id ) );
		} else {
			\ISC_Log::log( sprintf( 'no ID found for "%s"', $html ) );
		}

		return $id;
	}

	/**
	 * Extract the image URL from HTML, namely, the value of the src attribute
	 *
	 * @param string $html HTML to extract an image URL from.
	 * @return string Image URL.
	 */
	public function extract_image_src( string $html ) : string {
		if ( ! $html ) {
			return '';
		}

		$src = '';

		$success = preg_match( '#src="([^"]+)"#is', $html, $matches_src );
		if ( $success ) {
			$src = $matches_src[1];
			\ISC_Log::log( sprintf( 'found src "%s"', $src ) );
		} else {
			\ISC_Log::log( sprintf( 'no src found for "%s"', $html ) );
		}

		return $src;
	}
}