<?php

namespace ISC\Image_Sources;

use ISC_Model;

/**
 * Add the admin menu items für Image Sources features
 */
class Admin_Notices {
	use \ISC\Options;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	/**
	 * Search for missing sources and display a warning if found some
	 */
	public function admin_notices() {

		// only check, if check-option was enabled
		$options = $this->get_options();
		// skip the warning on the image sources screen since the list shows up there
		$screen = get_current_screen();
		if ( empty( $options['warning_onesource_missing'] )
			|| empty( $screen->id )
			|| $screen->id === 'media_page_isc-sources' ) {
			return;
		}

		$missing_sources = (int) get_transient( 'isc-show-missing-sources-warning' );

		// check for missing sources if the transient is empty and store that value
		if ( ! $missing_sources ) {
			$missing_sources = ISC_Model::update_missing_sources_transient();
		}

		// attachments without sources
		if ( $missing_sources ) {
			require_once ISCPATH . '/admin/templates/notice-missing.php';
		}
	}
}
