<?php
/**
 * Analyze chunks of HTML
 */
namespace ISC;
class Analyze_HTML {

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
			\ISC_Log::log( sprintf( 'ISC\Analyze_HTML:extract_image_id: found ID "%s"', $id ) );
		} else {
			\ISC_Log::log( sprintf( 'ISC\Analyze_HTML:extract_image_id: no ID found for "%s"', $html ) );
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
			\ISC_Log::log( sprintf( 'ISC\Analyze_HTML:extract_image_src: found src "%s"', $src ) );
		} else {
			\ISC_Log::log( sprintf( 'ISC\Analyze_HTML:extract_image_src: no src found for "%s"', $html ) );
		}

		return $src;
	}
}