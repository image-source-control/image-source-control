<?php

namespace ISC\Image_Sources;

use ISC\Admin_Utils;
use ISC\Helpers;

/**
 * Add the admin menu items für Image Sources features
 */
class Image_Sources_Admin_Scripts {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_scripts' ] );
		add_action( 'admin_print_scripts', [ $this, 'admin_head_scripts' ] );
	}

	/**
	 * Add scripts to ISC-related pages
	 */
	public function add_admin_scripts() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return;
		}

		if ( $screen->id === 'media_page_isc-sources' ) {
			Helpers::enqueue_script( 'isc_sources_script', 'admin/assets/js/sources.js' );
		}

		// check if we are on the media library page with list view
		if ( Admin_Utils::is_media_library_list_view_page() ) {
			return;
		}

		if ( in_array( $screen->id, [ 'upload', 'widgets', 'customize' ], true ) ) {
			Helpers::enqueue_script( 'isc_attachment_compat', 'admin/assets/js/wp.media.view.AttachmentCompat.js', [ 'media-upload' ] );
		}
	}

	/**
	 * Display scripts in <head></head> section of admin page. Useful for creating js variables in the js global namespace.
	 */
	public function admin_head_scripts() {
		global $pagenow;
		$screen = get_current_screen();
		// texts in JavaScript on sources page
		if ( 'upload.php' === $pagenow && isset( $_GET['page'] ) && 'isc-sources' === $_GET['page'] ) {
			?>
			<script>
				isc_data = {
					confirm_message: '<?php esc_html_e( 'Are you sure?', 'image-source-control-isc' ); ?>',
					baseurl:         '<?php echo esc_url( ISCBASEURL ); ?>'
				};
			</script>
			<?php
		}
		// add style to media edit pages
		if ( isset( $screen->id ) && $screen->id === 'attachment' ) {
			// Meta field in media view
			?>
			<style>
				.compat-attachment-fields input[type="text"] {
					width: 100%;
				}

				.compat-attachment-fields th {
					vertical-align: top;
				}
			</style>
			<?php
		}
		// add to any backend pages
		?>
		<style>
			.compat-attachment-fields .isc-get-pro {
				font-weight: bold;
				color: #F70;
			}
		</style>
		<?php
	}
}