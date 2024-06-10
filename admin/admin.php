<?php
/**
 * Handles everything displayed in WP Admin
 * storing updated information is not part of this class since it is only included if is_admin() returns true
 * which is not the case for the Customizer of Block editor
 */

use ISC\Plugin;
use ISC\User;

class ISC_Admin extends ISC_Class {

	/**
	 * Initiate admin functions
	 */
	public function __construct() {
		parent::__construct();

		// load more admin-related classes
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );

		// register attachment fields
		add_filter( 'attachment_fields_to_edit', [ $this, 'add_isc_fields' ], 10, 2 );

		// admin notices and ISC page header
		add_action( 'admin_notices', [ $this, 'branded_admin_header' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );

		// backend pages
		add_action( 'admin_menu', [ $this, 'add_menu_items' ] );

		// scripts and styles
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_scripts' ] );
		add_action( 'admin_print_scripts', [ $this, 'admin_head_scripts' ] );

		// ajax calls
		add_action( 'wp_ajax_isc-post-image-relations', [ $this, 'list_post_image_relations' ] );
		add_action( 'wp_ajax_isc-image-post-relations', [ $this, 'list_image_post_relations' ] );
		add_action( 'wp_ajax_isc-clear-index', [ $this, 'clear_index' ] );
		add_action( 'wp_ajax_isc-clear-storage', [ $this, 'clear_storage' ] );
		add_action( 'wp_ajax_isc-clear-image-posts-index', [ $this, 'clear_image_posts_index' ] );
		add_action( 'wp_ajax_isc-clear-post-images-index', [ $this, 'clear_post_images_index' ] );

		// add links to setting and source list to plugin page
		add_action( 'plugin_action_links_' . ISCBASE, [ $this, 'add_links_to_plugin_page' ] );

		// fire when an attachment is removed
		add_action( 'delete_attachment', [ $this, 'delete_attachment' ] );

		new \ISC\Settings();
	}

	/**
	 * Load additional admin classes
	 */
	public function plugins_loaded() {
		new \ISC\Feedback();
	}

	/**
	 * Check if the current WP Admin page belongs to ISC
	 *
	 * @return bool true if this is an ISC-related page
	 */
	private static function is_isc_page() {
		$screen = get_current_screen();

		return isset( $screen->id ) && in_array( $screen->id, apply_filters( 'isc_admin_pages', [ 'settings_page_isc-settings', 'media_page_isc-sources' ] ), true );
	}

	/**
	 * Add links to setting and source list pages from plugins.php
	 *
	 * @param array $links existing plugin links.
	 *
	 * @return array
	 */
	public function add_links_to_plugin_page( $links ) {
		// add link to premium.
		if ( ! Plugin::is_pro() ) {
			array_unshift( $links, self::get_pro_link( 'plugin-overview' ) );
		}
		// settings link
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'page', 'isc-settings', get_admin_url() . 'options-general.php' ) ),
			__( 'Settings', 'image-source-control-isc' )
		);
		// image source link
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'page', 'isc-sources', get_admin_url() . 'upload.php' ) ),
			__( 'Image Sources', 'image-source-control-isc' )
		);

		return $links;
	}

	/**
	 * Search for missing sources and display a warning if found some
	 */
	public function admin_notices() {

		// only check, if check-option was enabled
		$options = $this->get_isc_options();
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

	/**
	 * Add scripts to ISC-related pages
	 */
	public function add_admin_scripts() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return;
		}

		if ( $screen->id === 'media_page_isc-sources' ) {
			wp_enqueue_script( 'isc_sources_script', plugins_url( '/assets/js/sources.js', __FILE__ ), [], ISCVERSION, true );
		}

		if ( in_array( $screen->id, [ 'upload', 'widgets', 'customize' ], true ) ) {
			wp_enqueue_script( 'isc_attachment_compat', plugins_url( '/assets/js/wp.media.view.AttachmentCompat.js', __FILE__ ), [ 'media-upload' ], ISCVERSION, true );
		}

		// Load CSS
		if ( self::is_isc_page() ) {
			wp_enqueue_style( 'isc_image_settings_css', plugins_url( '/assets/css/isc.css', __FILE__ ), false, ISCVERSION );
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
		// add style to plugin overview page
		if ( isset( $screen->id ) && $screen->id === 'plugins' ) {
			// Meta field in media view
			?>
			<style>
				.row-actions .isc-get-pro {
					font-weight: bold;
					color: #F70;
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

			div.error.isc-notice {
				border-left-color: #F70;
			}
		</style>
		<?php
		// add nonce to all pages
		$params = [
			'ajaxNonce' => wp_create_nonce( 'isc-admin-ajax-nonce' ),
		];
		wp_localize_script( 'jquery', 'isc', $params );
	}

	/**
	 * Add custom field to attachment
	 *
	 * @param array  $form_fields field fields.
	 * @param object $post        post object.
	 *
	 * @return array with form fields
	 */
	public function add_isc_fields( $form_fields, $post ) {
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
			&& ( ISC_Block_Options::enabled() && ! apply_filters( 'isc_force_block_options', false ) ) ) {

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
			$form_fields['isc_image_source_pro']['html']  = self::get_pro_link( 'attachment-edit' );
		}

		// add input field for source
		$form_fields['isc_image_source']['label'] = __( 'Image Source', 'image-source-control-isc' );
		$form_fields['isc_image_source']['value'] = ISC_Class::get_image_source_text( $post->ID );
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
				ISC\Standard_Source::get_standard_source_label()
			);
		$form_fields['isc_image_source_own']['html'] =
			"<input type='checkbox' value='1' name='attachments[{$post->ID}][isc_image_source_own]' id='attachments[{$post->ID}][isc_image_source_own]' "
			. checked( get_post_meta( $post->ID, 'isc_image_source_own', true ), 1, false )
			. ' style="width:14px"/> ';

		// add input field for source url
		$form_fields['isc_image_source_url']['label'] = __( 'Image Source URL', 'image-source-control-isc' );
		$form_fields['isc_image_source_url']['value'] = ISC_Class::get_image_source_url( $post->ID );
		$form_fields['isc_image_source_url']['helps'] = __( 'URL to link the source text to.', 'image-source-control-isc' );

		// add input field for license, if enabled
		$options  = $this->get_isc_options();
		$licences = $this->licences_text_to_array( $options['licences'] );
		if ( $options['enable_licences'] && $licences ) {
			$form_fields['isc_image_licence']['input'] = 'html';
			$form_fields['isc_image_licence']['label'] = __( 'Image License', 'image-source-control-isc' );
			$form_fields['isc_image_licence']['helps'] = __( 'Choose the image license.', 'image-source-control-isc' );
			$html                                      = '<select name="attachments[' . $post->ID . '][isc_image_licence]" id="attachments[' . $post->ID . '][isc_image_licence]">';
			$html                                     .= '<option value="">--</option>';
			foreach ( $licences as $_licence_name => $_licence_data ) {
				$html .= '<option value="' . $_licence_name . '" ' . selected( ISC_Class::get_image_license( $post->ID ), $_licence_name, false ) . '>' . $_licence_name . '</option>';
			}
			$html                                    .= '</select>';
			$form_fields['isc_image_licence']['html'] = $html;
		}

		return apply_filters( 'isc_admin_attachment_form_fields', $form_fields, $post, $options );
	}

	/**
	 * Create the menu pages for ISC with access for editors and higher roles
	 */
	public function add_menu_items() {
		$options = $this->get_isc_options();
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
	 * Add an ISC branded header to plugin pages
	 */
	public static function branded_admin_header() {
		$screen    = get_current_screen();
		$screen_id = $screen->id ?? null;
		if ( ! self::is_isc_page() ) {
			return;
		}
		switch ( $screen_id ) {
			case 'settings_page_isc-settings':
				$title = __( 'Settings', 'image-source-control-isc' );
				break;
			case 'media_page_isc-sources':
				$title = __( 'Tools', 'image-source-control-isc' );
				break;
			default:
				$title = get_admin_page_title();
		}
		include ISCPATH . 'admin/templates/header.php';
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

			$stats            = ISC\Unused_Images::get_unused_attachment_stats();
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

			$storage_model   = new ISC_Storage_Model();
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

	/**
	 * List post-images (images associated with a specidic post ID)
	 * Called using AJAX
	 */
	public function list_post_image_relations() {

		// get all meta fields
		$args              = [
			'posts_per_page' => - 1,
			'post_status'    => null,
			'post_parent'    => null,
			'post_type'	     => 'any',
			'meta_query'     => [
				[
					'key' => 'isc_post_images',
				],
			],
		];
		$posts_with_images = new WP_Query( $args );

		if ( $posts_with_images->have_posts() ) {
			require_once ISCPATH . '/admin/templates/post-images-list.php';
		} else {
			die( esc_html__( 'No entries found', 'image-source-control-isc' ) );
		}

		wp_reset_postdata();

		die();
	}

	/**
	 * List post image relations (called with ajax)
	 *
	 * @since 1.6.1
	 */
	public function list_image_post_relations() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		// get all images
		$args              = [
			'post_type'      => 'attachment',
			'posts_per_page' => - 1,
			'post_status'    => 'inherit',
			'meta_query'     => [
				[
					'key' => 'isc_image_posts',
				],
			],
		];
		$images_with_posts = new WP_Query( $args );

		if ( $images_with_posts->have_posts() ) {
			require_once ISCPATH . '/admin/templates/image-posts-list.php';
		} else {
			die( esc_html__( 'No entries found', 'image-source-control-isc' ) );
		}

		wp_reset_postdata();

		die();
	}

	/**
	 * Callback to clear all image-post relations
	 */
	public function clear_index() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		die(
			sprintf(
				// translators: %d is the number of deleted entries
				esc_html__( '%d entries deleted', 'image-source-control-isc' ),
				(int) ISC_Model::clear_index()
			)
		);
	}

	/**
	 * Callback to clear all image-post relations
	 */
	public function clear_storage() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		ISC_Storage_Model::clear_storage();

		die( esc_html__( 'Storage deleted', 'image-source-control-isc' ) );
	}

	/**
	 * Callback to clear the isc_image_posts post meta
	 */
	public function clear_image_posts_index() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		if ( ! isset( $_POST['image_id'] ) ) {
			die( 'No image ID given' );
		}

		$image_id = (int) $_POST['image_id'];
		delete_post_meta( $image_id, 'isc_image_posts' );

		die( 'Image-Posts index cleared' );
	}

	/**
	 * Callback to clear the isc_post_images post meta
	 */
	public function clear_post_images_index() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Wrong capabilities' );
		}

		if ( ! isset( $_POST['post_id'] ) ) {
			die( 'No post ID given' );
		}

		$post_id = (int) $_POST['post_id'];
		delete_post_meta( $post_id, 'isc_post_images' );

		die( 'Post-Images index cleared' );
	}

	/**
	 * Actions to perform when an attachment is removed
	 * - delete it from the ISC storage
	 * - clear the transient with the information about unused images
	 *
	 * @param int $post_id WP_Post ID.
	 */
	public function delete_attachment( $post_id ) {
		$storage_model = new ISC_Storage_Model();
		$storage_model->remove_image_by_id( $post_id );

		// remove the transient with the unused image numbers
		delete_transient( 'isc-unused-attachments-stats' );
	}

	/**
	 * Get URL to the ISC website by site language
	 *
	 * @param string $url_param    Default parameter added to the main URL, leading to https://imagesourceocontrol.com/.
	 * @param string $url_param_de Parameter added to the main URL for German backends, leading to https://imagesourceocontrol.de/.
	 * @param string $utm_campaign Position of the link for the campaign link.
	 *
	 * @return string website URL
	 */
	public static function get_isc_localized_website_url( string $url_param, string $url_param_de, string $utm_campaign ) {
		if ( User::has_german_backend() ) {
			$url = 'https://imagesourcecontrol.de/' . $url_param_de;
		} else {
			$url = 'https://imagesourcecontrol.com/' . $url_param;
		}

		return esc_url( $url . '?utm_source=isc-plugin&utm_medium=link&utm_campaign=' . $utm_campaign );
	}

	/**
	 * Get link to ISC Pro
	 *
	 * @param string $utm_campaign Position of the link for the campaign link.
	 *
	 * @return string
	 */
	public static function get_pro_link( $utm_campaign = 'upsell-link' ) {
		return '<a href="' . self::get_isc_localized_website_url( 'pricing/', 'preise/', $utm_campaign ) . '" class="isc-get-pro" target="_blank">' . esc_html__( 'Get ISC Pro', 'image-source-control-isc' ) . '</a>';
	}

	/**
	 * Get link to the ISC manual
	 *
	 * @param string $utm_campaign Position of the link for the campaign link.
	 */
	public static function get_manual_url( $utm_campaign = 'manual' ) {
		// check if the locale starts with "de_"
		if ( User::has_german_backend() ) {
			$base_url = 'https://imagesourcecontrol.de/dokumentation/';
		} else {
			$base_url = 'https://imagesourcecontrol.com/documentation/';
		}

		return $base_url . '?utm_source=isc-plugin&utm_medium=link&utm_campaign=' . $utm_campaign;
	}
}
