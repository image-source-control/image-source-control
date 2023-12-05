<?php
	/**
	 * Handles everything displayed in WP Admin
	 * storing updated information is not part of this class since it is only included if is_admin() returns true
	 * which is not the case for the Customizer of Block editor
	 *
	 * @since 1.7 - move a lot of functions here from general class
	 */
class ISC_Admin extends ISC_Class {

	/**
	 * Initiate admin functions
	 */
	public function __construct() {
		parent::__construct();

		// register attachment fields
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_isc_fields' ), 10, 2 );

		// admin notices and ISC page header
		add_action( 'admin_notices', array( $this, 'branded_admin_header' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// settings page
		add_action( 'admin_menu', array( $this, 'add_menu_items' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );

		// scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		add_action( 'admin_print_scripts', array( $this, 'admin_head_scripts' ) );

		// ajax calls
		add_action( 'wp_ajax_isc-post-image-relations', array( $this, 'list_post_image_relations' ) );
		add_action( 'wp_ajax_isc-image-post-relations', array( $this, 'list_image_post_relations' ) );
		add_action( 'wp_ajax_isc-clear-index', array( $this, 'clear_index' ) );
		add_action( 'wp_ajax_isc-clear-storage', array( $this, 'clear_storage' ) );

		// add links to setting and source list to plugin page
		add_action( 'plugin_action_links_' . ISCBASE, array( $this, 'add_links_to_plugin_page' ) );

		// fire when an attachment is removed
		add_action( 'delete_attachment', array( $this, 'delete_attachment' ) );
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
	 * @return array
	 */
	public function add_links_to_plugin_page( $links ) {
		// add link to premium.
		if ( ! class_exists( 'ISC_Pro_Admin', false ) ) {
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
		};

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
	 *
	 * @since 1.0
	 * @update 1.1.1
	 *
	 * @param string $hook settings page hool.
	 */
	public function add_admin_scripts( $hook ) {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return;
		}
		if ( $screen->id === 'settings_page_isc-settings' ) {
			wp_enqueue_script( 'isc_settings_script', plugins_url( '/assets/js/settings.js', __FILE__ ), array(), ISCVERSION, true );
		}

		if ( $screen->id === 'media_page_isc-sources' ) {
			wp_enqueue_script( 'isc_sources_script', plugins_url( '/assets/js/sources.js', __FILE__ ), array(), ISCVERSION, true );
		}

		if ( in_array( $screen->id, array( 'upload', 'widgets', 'customize' ) ) ) {
			wp_enqueue_script( 'isc_attachment_compat', plugins_url( '/assets/js/wp.media.view.AttachmentCompat.js', __FILE__ ), array( 'media-upload' ), ISCVERSION, true );
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
					confirm_message : '<?php esc_html_e( 'Are you sure?', 'image-source-control-isc' ); ?>',
					baseurl : '<?php echo ISCBASEURL; ?>'
				}
			</script>
			<?php
		}
		// add style to media edit pages
		if ( isset( $screen->id ) && $screen->id === 'attachment' ) {
			// Meta field in media view
			?>
			<style>
				.compat-attachment-fields input[type="text"] { width: 100%; }
				.compat-attachment-fields th { vertical-align: top; }
			</style>
			<?php
		}
		// add style to plugin overview page
		if ( isset( $screen->id ) && $screen->id === 'plugins' ) {
			// Meta field in media view
			?>
			<style>
				.row-actions .isc-get-pro { font-weight: bold; color: #F70; }
			</style>
			<?php
		}
		// add style and scripts to settings page
		if ( isset( $screen->id ) && $screen->id === 'settings_page_isc-settings' ) {
			?>
			<script>
				isc_settings = {
					baseurl : '<?php echo ISCBASEURL; ?>'
				}
			</script>
			<style>
				#isc-settings-caption-pos-options {
					background: url(<?php echo ISCBASEURL . 'admin/templates/settings/preview/image-for-position-preview.jpg'; ?>) no-repeat center/cover;
				}
			</style>
			<?php
		}
		// add to any backend pages
		?>
		<style>
			.compat-attachment-fields .isc-get-pro{ font-weight: bold; color: #F70; }
			div.error.isc-notice { border-left-color: #F70; }
		</style>
		<?php
		// add nonce to all pages
		$params = array(
			'ajaxNonce' => wp_create_nonce( 'isc-admin-ajax-nonce' ),
		);
		wp_localize_script( 'jquery', 'isc', $params );
	}

	/**
	 * Add custom field to attachment
	 *
	 * @param array  $form_fields field fields.
	 * @param object $post post object.
	 * @return array with form fields
	 */
	public function add_isc_fields( $form_fields, $post ) {
		$options = $this->get_isc_options();

		/**
		 * Return, when the ISC fields are enabled for blocks and the HTTP_REFERER is a post edit page. This likely means, that the block editor is used.
		 * While the URL to edit an attachment can also include "wp-admin.php.php", the HTTP_REFERER is then different
		 */
		if ( ! empty( $_SERVER['HTTP_REFERER'] ) && strpos( $_SERVER['HTTP_REFERER'], 'wp-admin/post.php' ) !== false && ( ! array_key_exists( 'block_options', $options ) || $options['block_options'] ) ) {
			return $form_fields;
		}

		if ( ! class_exists( 'ISC_Pro_Admin', false ) ) {
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
				ISC\Includes\Standard_Source::get_standard_source_label()
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
				$html                                 .= '<option value="">--</option>';
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
			array( $this, 'render_sources_page' )
		);

		add_options_page(
			__( 'Image Source Control Settings', 'image-source-control-isc' ),
			__( 'Image Sources', 'image-source-control-isc' ),
			'edit_others_posts',
			'isc-settings',
			array( $this, 'render_isc_settings_page' )
		);
	}

	/**
	 * Add an ISC branded header to plugin pages
	 */
	public static function branded_admin_header() {
		$screen    = get_current_screen();
		$screen_id = isset( $screen->id ) ? $screen->id : null;
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
	 * Settings API initialization
	 */
	public function settings_init() {
		$this->upgrade_management();
		register_setting( 'isc_options_group', 'isc_options', array( $this, 'settings_validation' ) );

		// Position: How and where to display image sources
		add_settings_section( 'isc_settings_section_source_type', __( 'Position of the image sources', 'image-source-control-isc' ), array( $this, 'render_section_position' ), 'isc_settings_page' );
		add_settings_field( 'source_type_list', '1. ' . __( 'Per-page list', 'image-source-control-isc' ), array( $this, 'renderfield_source_type_list' ), 'isc_settings_page', 'isc_settings_section_source_type' );
		add_settings_field( 'source_type_overlay', '2. ' . __( 'Overlay', 'image-source-control-isc' ), array( $this, 'renderfield_source_type_overlay' ), 'isc_settings_page', 'isc_settings_section_source_type' );
		add_settings_field( 'full_list_type', '3. ' . __( 'Global list', 'image-source-control-isc' ), array( $this, 'renderfield_source_type_complete_list' ), 'isc_settings_page', 'isc_settings_section_source_type' );

		// settings for sources list below content
		add_settings_section( 'isc_settings_section_list_below_content', '1. ' . __( 'Per-page list', 'image-source-control-isc' ), '__return_false', 'isc_settings_page' );
		add_settings_field( 'image_list_headline', __( 'Headline', 'image-source-control-isc' ), array( $this, 'renderfield_list_headline' ), 'isc_settings_page', 'isc_settings_section_list_below_content' );
		add_settings_field( 'below_content_included_images', __( 'Included images', 'image-source-control-isc' ), array( $this, 'renderfield_below_content_included_images' ), 'isc_settings_page', 'isc_settings_section_list_below_content' );

		// source in caption
		add_settings_section( 'isc_settings_section_overlay', '2. ' . __( 'Overlay', 'image-source-control-isc' ), '__return_false', 'isc_settings_page' );
		add_settings_field( 'source_overlay', __( 'Overlay pre-text', 'image-source-control-isc' ), array( $this, 'renderfield_overlay_text' ), 'isc_settings_page', 'isc_settings_section_overlay' );
		add_settings_field( 'overlay_style', __( 'Layout', 'image-source-control-isc' ), array( $this, 'renderfield_overlay_style' ), 'isc_settings_page', 'isc_settings_section_overlay' );
		add_settings_field( 'overlay_included_images', __( 'Included images', 'image-source-control-isc' ), array( $this, 'renderfield_overlay_included_images' ), 'isc_settings_page', 'isc_settings_section_overlay' );

		// Global list group
		add_settings_section( 'isc_settings_section_complete_list', '3. ' . __( 'Global list', 'image-source-control-isc' ), '__return_false', 'isc_settings_page' );
		add_settings_field( 'global_list_included_images', __( 'Included images', 'image-source-control-isc' ), array( $this, 'renderfield_global_list_included_images' ), 'isc_settings_page', 'isc_settings_section_complete_list' );
		add_settings_field( 'images_per_page_in_list', __( 'Images per page', 'image-source-control-isc' ), array( $this, 'renderfield_images_per_page_in_list' ), 'isc_settings_page', 'isc_settings_section_complete_list' );
		add_settings_field( 'global_list_included_data', __( 'Included data', 'image-source-control-isc' ), array( $this, 'renderfield_global_list_data' ), 'isc_settings_page', 'isc_settings_section_complete_list' );

		// Licence settings group
		add_settings_section( 'isc_settings_section_licenses', __( 'Image licenses', 'image-source-control-isc' ), '__return_false', 'isc_settings_page' );
		add_settings_field( 'enable_licences', __( 'Enable licenses', 'image-source-control-isc' ), array( $this, 'renderfield_enable_licences' ), 'isc_settings_page', 'isc_settings_section_licenses' );
		add_settings_field( 'licences', __( 'List of licenses', 'image-source-control-isc' ), array( $this, 'renderfield_licences' ), 'isc_settings_page', 'isc_settings_section_licenses' );

		// Misc settings group
		add_settings_section( 'isc_settings_section_misc', __( 'Miscellaneous settings', 'image-source-control-isc' ), '__return_false', 'isc_settings_page' );
		add_settings_field( 'standard_source', __( 'Standard source', 'image-source-control-isc' ), array( $this, 'renderfield_standard_source' ), 'isc_settings_page', 'isc_settings_section_misc' );
		add_settings_field( 'block_options', __( 'Block options', 'image-source-control-isc' ), array( $this, 'renderfield_block_options' ), 'isc_settings_page', 'isc_settings_section_misc' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			add_settings_field( 'elementor', 'Elementor', array( $this, 'renderfield_elementor' ), 'isc_settings_page', 'isc_settings_section_misc' );
		}
		add_settings_field( 'warning_one_source', __( 'Warn about missing sources', 'image-source-control-isc' ), array( $this, 'renderfield_warning_source_missing' ), 'isc_settings_page', 'isc_settings_section_misc' );
		add_settings_field( 'enable_log', __( 'Debug log', 'image-source-control-isc' ), array( $this, 'renderfield_enable_log' ), 'isc_settings_page', 'isc_settings_section_misc' );
		add_settings_field( 'remove_on_uninstall', __( 'Delete data on uninstall', 'image-source-control-isc' ), array( $this, 'renderfield_remove_on_uninstall' ), 'isc_settings_page', 'isc_settings_section_misc' );
	}

	/**
	 * Manage data structure upgrading of outdated versions
	 */
	public function upgrade_management() {
		$default_options = $this->default_options();

		/**
		 * This function checks options in database
		 * during the admin_init hook to handle plugin's upgrade.
		 */
		$options = get_option( 'isc_options', $default_options );

		if ( is_array( $options ) ) {
			// version 1.7 and higher
			if ( version_compare( '1.7', $options['version'], '>' ) ) {
				// convert old into new settings
				if ( isset( $options['attach_list_to_post'] ) ) {
					$options['display_type'][] = 'list';
				}
				if ( isset( $options['source_on_image'] ) ) {
					$options['display_type'][] = 'overlay';
				}
			}
		} else {
			// create options from default just in case the isc_option is stored with something other than an array in it.
			update_option( 'isc_options', $default_options );
			$options = $default_options;
		}

		if ( ISCVERSION !== $options['version'] ) {
			$options            = $options + $default_options;
			$options['version'] = ISCVERSION;
			update_option( 'isc_options', $options );
		}
	}

	/**
	 * Image_control's page callback
	 */
	public function render_isc_settings_page() {
		require_once ISCPATH . '/admin/templates/settings.php';
	}

	/**
	 * Prints out all settings sections added to a particular settings page
	 *
	 * Copy of do_settings_sections() in WP 5.5.1 with adjustments to design each settings section in a meta box.
	 *
	 * @global array $wp_settings_sections Storage array of all settings sections added to admin pages.
	 * @global array $wp_settings_fields Storage array of settings fields and info about their pages/sections.
	 *
	 * @param string $page The slug name of the page whose settings sections you want to output.
	 */
	public static function do_settings_sections( $page ) {
		global $wp_settings_sections;

		if ( ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {

			?>
			<div class="postbox <?php echo esc_attr( $section['id'] ); ?>" id="<?php echo esc_attr( $section['id'] ); ?>">
			<?php
			if ( $section['title'] ) {
				?>
				<div class="postbox-header"><h2 class="hndle"><?php echo esc_html( $section['title'] ); ?></h2></div>
				<?php
			}

			?>
			<div class="inside">
			<div class="submitbox">
				<?php
				if ( $section['callback'] ) {
					call_user_func( $section['callback'], $section );
				}
				?>
				<table class="form-table" role="presentation">
				<?php
				do_settings_fields( $page, $section['id'] );
				?>
			</table></div></div></div>
			<?php
		}
	}

	/**
	 * Missing sources page callback
	 */
	public function render_sources_page() {

		?><div class="wrap metabox-holder">
			<div id="isc-section-wrapper">
		<?php

			$attachments = ISC_Model::get_attachments_with_empty_sources();
			if ( ! empty( $attachments ) ) {
				ob_start();
				require_once ISCPATH . '/admin/templates/sources/images-without-sources.php';
				$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Images without sources', 'image-source-control-isc' ), 'images-without-sources');
			} else {
				?>
				<div class="notice notice-success"><p><span class="dashicons dashicons-yes" style="color: #46b450"></span><?php esc_html_e( 'All images found in the frontend have sources assigned.', 'image-source-control-isc' ); ?></p></div>
				<?php
			}

			$attachments = ISC_Model::get_unused_attachments();
			if ( ! empty( $attachments ) ) {
				ob_start();
				$attachment_count = count( $attachments );
				$files = 0;
				$filesize = 0;
				foreach( $attachments as $attachment ) {
					$file_info = ISC_Model::analyze_unused_image( $attachment->metadata );
					$files += $file_info['files'] ?? 0;
					$filesize += $file_info['total_size'] ?? 0;
				}
				require_once ISCPATH . '/admin/templates/sources/unused-attachments.php';
				$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Unused Images', 'image-source-control-isc' ), 'unused-attachments');
			}

			$post_type_image_index = ISC_Model::get_posts_with_image_index();
			ob_start();
			require_once ISCPATH . '/admin/templates/sources/post-index.php';
			$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Post Index', 'image-source-control-isc' ), 'post-index');

			$storage_model = new ISC_Storage_Model();
			$external_images = $storage_model->get_storage_without_wp_images();
			if ( ! empty( $stored_images ) ) {
				ob_start();
				require_once ISCPATH . '/admin/templates/sources/external-images.php';
				$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Additional images', 'image-source-control-isc' ), 'external-images');
			}

			$storage_size  = count( $storage_model->get_storage() );
			ob_start();
			require_once ISCPATH . '/admin/templates/sources/storage.php';
			$this->render_sources_page_section( ob_get_clean(), esc_html__( 'Storage', 'image-source-control-isc' ), 'storage');

		?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render sources page sections
	 *
	 * @param string $section section HTML.
	 * @param string $title section title.
	 * @param string $id section id.
	 */
	public function render_sources_page_section( $section, $title = '', $id = '' ) {
		include ISCPATH . '/admin/templates/sources/section.php';
	}

	/**
	 * Render the top of the Position settings section
	 */
	public function render_section_position() {
		require_once ISCPATH . '/admin/templates/settings/section-position.php';
	}

	/**
	 * Position: option to enable the Per-page list
	 */
	public function renderfield_source_type_list() {
		$options = $this->get_isc_options();
		require_once ISCPATH . '/admin/templates/settings/source-type-list.php';
	}

	/**
	 * Position: option to enable Overlays
	 */
	public function renderfield_source_type_overlay() {
		$options = $this->get_isc_options();
		require_once ISCPATH . '/admin/templates/settings/source-type-overlay.php';
	}

	/**
	 * Position: information about how to use the complete source list
	 */
	public function renderfield_source_type_complete_list() {
		require_once ISCPATH . '/admin/templates/settings/source-type-all.php';
	}

	/**
	 * Render option to define a headline for the image list
	 */
	public function renderfield_list_headline() {
		$options = $this->get_isc_options();
		require_once ISCPATH . '/admin/templates/settings/source-list-headline.php';
	}

	/**
	 * Render option to define which images to show on the sources list of the current page
	 */
	public function renderfield_below_content_included_images() {
		$options                 = $this->get_isc_options();
		$included_images         = ! empty( $options['list_included_images'] ) ? $options['list_included_images'] : '';
		$included_images_options = $this->get_list_included_images_options();
		require_once ISCPATH . '/admin/templates/settings/below-content-included-images.php';
	}

	/**
	 * Render option for the text preceding the source.
	 */
	public function renderfield_overlay_text() {
		$options = $this->get_isc_options();
		require_once ISCPATH . '/admin/templates/settings/overlay-text.php';
	}

	/**
	 * Render option for the style of the overlay
	 */
	public function renderfield_overlay_style() {
		$options       = $this->get_isc_options();
		$caption_style = ! empty( $options['caption_style'] ) ? $options['caption_style'] : null;
		require_once ISCPATH . '/admin/templates/settings/overlay-style.php';
		require_once ISCPATH . '/admin/templates/settings/overlay-position.php';
	}

	/**
	 * Render option to define which images should show the overlay
	 */
	public function renderfield_overlay_included_images() {
		$options                  = $this->get_isc_options();
		$included_images          = ! empty( $options['overlay_included_images'] ) ? $options['overlay_included_images'] : '';
		$included_images_options  = $this->get_overlay_included_images_options();
		require_once ISCPATH . '/admin/templates/settings/overlay-included-images.php';
		$checked_advanced_options = ! empty( $options['overlay_included_advanced'] ) && is_array( $options['overlay_included_advanced'] ) ? $options['overlay_included_advanced'] : array();
		$advanced_options         = $this->get_overlay_advanced_included_images_options();
		require_once ISCPATH . '/admin/templates/settings/overlay-advanced-included-images.php';
	}

	/**
	 * Render option to define which images should show in the global list
	 */
	public function renderfield_global_list_included_images() {
		$options                 = $this->get_isc_options();
		$included_images         = ! empty( $options['global_list_included_images'] ) ? $options['global_list_included_images'] : '';
		$included_images_options = $this->get_global_list_included_images_options();
		require_once ISCPATH . '/admin/templates/settings/global-list-included-images.php';
	}

	/**
	 * Render option for the number of images per page in the Global list
	 */
	public function renderfield_images_per_page_in_list() {
		$options         = $this->get_isc_options();
		$images_per_page = isset( $options['images_per_page'] ) ? absint( $options['images_per_page'] ) : 99999;
		require_once ISCPATH . '/admin/templates/settings/images-per-page.php';
	}

	/**
	 * Render option to define which columns show up the global list
	 */
	public function renderfield_global_list_data() {
		$options                  = $this->get_isc_options();
		$included_columns         = ! empty( $options['global_list_included_data'] ) ? $options['global_list_included_data'] : array();
		$included_columns_options = $this->get_global_list_included_data_options();
		require_once ISCPATH . '/admin/templates/settings/global-list-data.php';
	}

	/**
	 * Render option to display thumbnails in the Global list
	 */
	public function renderfield_thumbnail_in_list() {
		$options = $this->get_isc_options();
		$sizes   = array();

		// convert the sizes array to match key and value
		foreach ( $this->thumbnail_size as $_size ) {
			$sizes[ $_size ] = $_size;
		}

		// requires WP 5.3
		if ( function_exists( 'wp_get_registered_image_subsizes' ) ) {
			// go through sizes we consider for thumbnails and get their current sizes as set up in WordPress
			$wp_image_sizes = wp_get_registered_image_subsizes();
			if ( is_array( $wp_image_sizes ) ) {
				foreach ( $wp_image_sizes as $_name => $_sizes ) {
					if ( isset( $sizes[ $_name ] ) ) {
						$sizes[ $_name ] = $_sizes;
					}
				}
			}
		}

		require_once ISCPATH . '/admin/templates/settings/global-list-thumbnail-enable.php';
	}

	/**
	 * Render option to enable the license settings.
	 */
	public function renderfield_enable_licences() {
		$options = $this->get_isc_options();
		require_once ISCPATH . '/admin/templates/settings/licenses-enable.php';
	}

	/**
	 * Render option to define the available licenses
	 */
	public function renderfield_licences() {
		$options = $this->get_isc_options();

		// fall back to default if field is empty
		if ( empty( $options['licences'] ) ) {
			// retrieve default options
			$default = ISC_Class::get_instance()->default_options();
			if ( ! empty( $default['licences'] ) ) {
				$options['licences'] = $default['licences'];
			}
		}

		require_once ISCPATH . '/admin/templates/settings/licenses.php';
	}

	/**
	 * Render options for standard image sources
	 */
	public function renderfield_standard_source() {
		$standard_source      = ISC\Includes\Standard_Source::get_standard_source();
		$standard_source_text = ISC\Includes\Standard_Source::get_standard_source_text();
		require_once ISCPATH . '/admin/templates/settings/standard-source.php';

		do_action( 'isc_admin_settings_template_after_standard_source' );
	}

	/**
	 * Render options for block editor support
	 */
	public function renderfield_block_options() {
		$options = $this->get_isc_options();
		$checked = ! array_key_exists( 'block_options', $options ) || $options['block_options'];
		require_once ISCPATH . '/admin/templates/settings/block-options.php';
	}

	/**
	 * Render option for Elementor support
	 */
	public function renderfield_elementor() {
		if ( ! class_exists( 'ISC_Pro_Admin', false ) ) {
			require_once ISCPATH . '/admin/templates/settings/elementor.php';
		}

		do_action( 'isc_admin_settings_template_after_elementor' );
	}

	/**
	 * Render the option to display a warning in the admin area if an image source is missing.
	 */
	public function renderfield_warning_source_missing() {
		$options = $this->get_isc_options();
		require_once ISCPATH . '/admin/templates/settings/warn-source-missing.php';
	}

	/**
	 * Render the option to log image source activity in isc.log
	 */
	public function renderfield_enable_log() {
		$options      = $this->get_isc_options();
		$checked      = ! empty( $options['enable_log'] );
		$log_file_url = ISC_Log::get_log_file_URL();
		require_once ISCPATH . '/admin/templates/settings/log-enable.php';
	}

	/**
	 * Render the option to remove all options and meta data when the plugin is deleted.
	 */
	public function renderfield_remove_on_uninstall() {
		$options = $this->get_isc_options();
		$checked = ! empty( $options['remove_on_uninstall'] );
		require_once ISCPATH . '/admin/templates/settings/remove-on-uninstall.php';
	}

	/**
	 * List image post relations (called with ajax)
	 *
	 * @since 1.6.1
	 */
	public function list_post_image_relations() {

		// get all meta fields
		$args              = array(
			'posts_per_page' => -1,
			'post_status'    => null,
			'post_parent'    => null,
			'meta_query'     => array(
				array(
					'key' => 'isc_post_images',
				),
			),
		);
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
		$args              = array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'inherit',
			'meta_query'     => array(
				array(
					'key' => 'isc_image_posts',
				),
			),
		);
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

		$removed_rows = ISC_Model::clear_index();

		die( esc_html__( "$removed_rows entries deleted", 'image-source-control-isc' ) );
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
	 * Input validation function.
	 *
	 * @param array $input values from the admin panel.
	 */
	public function settings_validation( $input ) {
		$output = $this->get_isc_options();
		if ( ! is_array( $input['display_type'] ) ) {
			$output['display_type'] = array();
		} else {
			$output['display_type'] = $input['display_type'];
		}
		$output['list_on_archives'] = ! empty( $input['list_on_archives'] );
		$output['list_on_excerpts'] = ! empty( $input['list_on_excerpts'] );

		$output['image_list_headline'] = isset( $input['image_list_headline'] ) ? esc_html( $input['image_list_headline'] ) : '';
		$output['enable_licences']     = ! empty( $input['enable_licences'] );

		if ( isset( $input['licences'] ) ) {
			$output['licences'] = esc_textarea( $input['licences'] );
		} else {
			$output['licences'] = false;
		}
		$output['images_per_page']     = absint ( $input['images_per_page'] );
		if ( isset( $input['thumbnail_in_list'] ) ) {
			$output['thumbnail_in_list'] = true;
			if ( in_array( $input['thumbnail_size'], $this->thumbnail_size ) ) {
				$output['thumbnail_size'] = $input['thumbnail_size'];
			}
			if ( 'custom' === $input['thumbnail_size'] ) {
				if ( is_numeric( $input['thumbnail_width'] ) ) {
					// Ensures that the value stored in database in a positive integer.
					$output['thumbnail_width'] = absint( round( $input['thumbnail_width'] ) );
				}
				if ( is_numeric( $input['thumbnail_height'] ) ) {
					$output['thumbnail_height'] = absint( round( $input['thumbnail_height'] ) );
				}
			}
		} else {
			$output['thumbnail_in_list'] = false;
		}
		$output['warning_onesource_missing'] = ! empty( $input['warning_onesource_missing'] );

		// remove the debug log file when it was disabled
		if ( isset( $output['enable_log'] ) && ! isset( $input['enable_log'] ) ) {
			ISC_Log::delete_log_file();
		}
		$output['enable_log']          = ! empty( $input['enable_log'] );
		$output['block_options']       = ! empty( $input['block_options'] );
		$output['remove_on_uninstall'] = ! empty( $input['remove_on_uninstall'] );
		$output['hide_list']           = ! empty( $input['hide_list'] );

		if ( isset( $input['caption_position'] ) && in_array( $input['caption_position'], $this->caption_position, true ) ) {
			$output['caption_position'] = $input['caption_position'];
		}
		$output['caption_style'] = ! empty( $input['caption_style'] ) ? $input['caption_style'] : null;
		if ( isset( $input['source_pretext'] ) ) {
			$output['source_pretext'] = esc_textarea( $input['source_pretext'] );
		}
		$output['list_included_images']    = isset( $input['list_included_images'] ) ? esc_attr( $input['list_included_images'] ) : '';
		$output['overlay_included_images'] = isset( $input['overlay_included_images'] ) ? esc_attr( $input['overlay_included_images'] ) : '';
		$output['overlay_included_advanced'] = isset( $input['overlay_included_advanced'] ) && is_array( $input['overlay_included_advanced'] ) ? $input['overlay_included_advanced'] : array();
		$output['global_list_included_images'] = isset( $input['global_list_included_images'] ) ? esc_attr( $input['global_list_included_images'] ) : '';

		/**
		 * 2.0 moved the options to handle "own images" into "standard sources" and only offers a single choice for one of the options now
		 * this section maps old to new settings
		 */
		if ( ! empty( $input['exclude_own_images'] ) ) {
			// don’t show sources for marked images
			$output['standard_source'] = 'exclude';
		} elseif ( ! empty( $input['use_authorname'] ) ) {
			// show author name
			$output['standard_source'] = 'author_name';
		} else {
			$output['standard_source'] = isset( $input['standard_source'] ) ? esc_attr( $input['standard_source'] ) : 'custom_text';
		}

		// custom source text
		if ( isset( $input['by_author_text'] ) ) {
			$output['standard_source_text'] = esc_html( $input['by_author_text'] );
		} else {
			$output['standard_source_text'] = ISC\Includes\Standard_Source::get_standard_source_text();
		}

		/**
		 * Allow other developers to manipulate settings on save
		 */
		return apply_filters( 'isc_settings_on_save_after_validation', $output, $input );
	}

	/**
	 * Actions to perform when an attachment is removed
	 * - delete it from the ISC storage
	 *
	 * @param int $post_id WP_Post ID.
	 */
	public function delete_attachment( $post_id ) {
		$storage_model = new ISC_Storage_Model();
		$storage_model->remove_image_by_id( $post_id );
	}

	/**
	 * Get URL to the ISC website by site language
	 *
	 * @param string $url_param    Default parameter added to the main URL, leading to https://imagesourceocontrol.com/
	 * @param string $url_param_de Parameter added to the main URL for German backends, leading to https://imagesourceocontrol.de/
	 * @param string $utm_campaign Position of the link for the campaign link
	 *
	 * @return string website URL
	 */
	public static function get_isc_localized_website_url( string $url_param, string $url_param_de, string $utm_campaign ) {
		if ( strpos( determine_locale(), 'de_' ) === 0 ) {
			$url = 'https://imagesourcecontrol.de/' . $url_param_de;
		} else {
			$url = 'https://imagesourcecontrol.com/' . $url_param;
		}
		return esc_url( $url . '?utm_source=isc-plugin&utm_medium=link&utm_campaign=' . $utm_campaign );
	}

	/**
	 * Get link to ISC Pro
	 *
	 * @param string $utm_campaign Position of the link for the campaign link
	 * @return string
	 */
	public static function get_pro_link( $utm_campaign = 'upsell-link' ) {
		return '<a href="' . self::get_isc_localized_website_url( 'pricing/', 'preise/', $utm_campaign ) . '" class="isc-get-pro" target="_blank">' . esc_html__( 'Get ISC Pro', 'image-source-control-isc' ) . '</a>';
	}

	/**
	 * Get link to the ISC manual
	 *
	 * @param string $utm_campaign Position of the link for the campaign link
	 */
	public static function get_manual_url( $utm_campaign = 'manual' ) {
		// check if the locale starts with "de_"
		if ( strpos( determine_locale(), 'de_' ) === 0 ) {
			$base_url = 'https://imagesourcecontrol.de/dokumentation/';
		} else {
			$base_url = 'https://imagesourcecontrol.com/documentation/';
		}
		return $base_url . '?utm_source=isc-plugin&utm_medium=link&utm_campaign=' . $utm_campaign;
	}
}
