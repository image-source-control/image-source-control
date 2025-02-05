<?php

namespace ISC\Image_Sources;

use ISC_Model;

/**
 * Add the admin menu items and pages Image Sources features
 */
class Admin_Menu {
	use \ISC\Options;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_items' ] );
	}

	/**
	 * Create the menu pages for ISC with access for editors and higher roles
	 */
	public function add_menu_items() {
		$options = self::get_options();
		if ( empty( $options['warning_onesource_missing'] ) ) {
			$notice_alert = '';
		} else {
			$missing_images = get_transient( 'isc-show-missing-sources-warning' );
			$notice_alert   = '&nbsp;<span class="update-plugins count-' . $missing_images . '"><span class="update-count">' . $missing_images . '</span></span>';
		}

		add_submenu_page(
			'upload.php',
			esc_html__( 'Image Source Control', 'image-source-control-isc' ),
			__( 'Image Sources', 'image-source-control-isc' ) . $notice_alert,
			'edit_others_posts',
			'isc-sources',
			[ $this, 'render_sources_page' ]
		);
	}


	/**
	 * Missing sources page callback
	 */
	public function render_sources_page() {

		?>
		<div class="wrap metabox-holder">
			<div id="isc-section-wrapper">
				<?php

				$attachments = ISC_Model::get_attachments_with_empty_sources();
				if ( ! empty( $attachments ) ) {
					ob_start();
					require_once ISCPATH . '/admin/templates/sources/images-without-sources.php';
					$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Images without sources', 'image-source-control-isc' ), 'images-without-sources' );
				} else {
					?>
					<div class="notice notice-success"><p><span class="dashicons dashicons-yes" style="color: #46b450"></span><?php esc_html_e( 'All images found in the frontend have sources assigned.', 'image-source-control-isc' ); ?></p></div>
					<?php
				}

				$stats            = \ISC\Unused_Images::get_unused_attachment_stats();
				$attachment_count = $stats['attachment_count'] ?? 0;
				$files            = $stats['files'] ?? 0;
				$filesize         = $stats['filesize'] ?? 0;
				if ( $files ) {
					ob_start();
					require_once ISCPATH . '/admin/templates/sources/unused-attachments.php';
					$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Unused Images', 'image-source-control-isc' ), 'unused-attachments' );
				}

				$post_type_image_index = ISC_Model::get_posts_with_image_index();
				ob_start();
				require_once ISCPATH . '/admin/templates/sources/post-index.php';
				$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Post Index', 'image-source-control-isc' ), 'post-index' );

				$storage_model   = new \ISC_Storage_Model();
				$external_images = $storage_model->get_storage_without_wp_images();
				if ( ! empty( $stored_images ) ) {
					ob_start();
					require_once ISCPATH . '/admin/templates/sources/external-images.php';
					$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Additional images', 'image-source-control-isc' ), 'external-images' );
				}

				$storage_size = count( $storage_model->get_storage() );
				ob_start();
				require_once ISCPATH . '/admin/templates/sources/storage.php';
				$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Storage', 'image-source-control-isc' ), 'storage' );

				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render sources page sections
	 *
	 * @param string $section section HTML.
	 * @param string $title   section title.
	 * @param string $id      section id.
	 */
	public function render_sources_page_section( $section, $title = '', $id = '' ) {
		include ISCPATH . '/admin/templates/sources/section.php';
	}
}