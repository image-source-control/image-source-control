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
	 *
	 * @since 1.7
	 */
	public function __construct() {

		parent::__construct();

		// register attachment fields
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_isc_fields' ), 10, 2 );

		// admin notices
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// settings page
		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_action( 'admin_init', array( $this, 'SAPI_init' ) );

		// scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
		add_action( 'admin_print_scripts', array( $this, 'admin_headjs' ) );

		// ajax calls
		add_action( 'wp_ajax_isc-post-image-relations', array( $this, 'list_post_image_relations' ) );
		add_action( 'wp_ajax_isc-image-post-relations', array( $this, 'list_image_post_relations' ) );
		add_action( 'wp_ajax_isc-clear-index', array( $this, 'clear_index' ) );

		// add links to setting and source list to plugin page
		add_action( 'plugin_action_links_' . ISCBASE, array( $this, 'add_links_to_plugin_page' ) );
	}

	/**
	 * Add links to setting and source list pages from plugins.php
	 *
	 * @var array $links
	 * @return array
	 */
	public function add_links_to_plugin_page( $links ) {
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
		if ( empty( $options['warning_onesource_missing'] ) ) {
				return;
		};

		$show_warning = get_transient( 'isc-show-missing-sources-warning' );

		// check for missing sources if the transient is empty and store that value
		if ( ! $show_warning ) {
			$show_warning = ISC_Model::update_missing_sources_transient();
		}

		// attachments without sources
		if ( $show_warning && 'no' !== $show_warning ) {
			require_once ISCPATH . '/admin/templates/notice-missing.php';
		}
	}

	/**
	 * Add scripts to admin pages
	 *
	 * @since 1.0
	 * @update 1.1.1
	 *
	 * @param string $hook settings page hool.
	 */
	public function add_admin_scripts( $hook ) {
		if ( 'post.php' === $hook ) {
			// quick fix for post.php.js to avoid access conflicts caused by other plugins due to by inconsistent naming
			wp_enqueue_script( 'isc_postphp_script', plugins_url( '/assets/js/post.js', __FILE__ ), array( 'jquery' ), ISCVERSION );
		}
		wp_enqueue_script( 'isc_script', plugins_url( '/assets/js/isc.js', __FILE__ ), false, ISCVERSION );
		wp_enqueue_style( 'isc_image_settings_css', plugins_url( '/assets/css/isc.css', __FILE__ ), false, ISCVERSION );
	}

	/**
	 * Display scripts in <head></head> section of admin page. Useful for creating js variables in the js global namespace.
	 */
	public function admin_headjs() {
		global $pagenow;
		// texts in JavaScript on sources page
		if ( 'upload.php' === $pagenow && isset( $_GET['page'] ) && 'isc-sources' === $_GET['page'] ) {
			?>
			<script type="text/javascript">
				isc_data = {
					confirm_message : '<?php esc_html_e( 'Are you sure?', 'image-source-control-isc' ); ?>'
				}
			</script>
			<?php
		}
		// add nonce to all pages
		$params = array(
			'ajaxNonce' => wp_create_nonce( 'isc-admin-ajax-nonce' ),
		);
		wp_localize_script( 'jquery', 'isc', $params );
	}

	/**
	 * Add custom field to attachment
	 *
	 * @since 1.0
	 * @updated 1.1
	 * @updated 1.3.5 added field for license
	 * @updated 1.5 added field for url
	 * @param array  $form_fields field fields.
	 * @param object $post post object.
	 * @return array with form fields
	 */
	public function add_isc_fields( $form_fields, $post ) {
		// add input field for source
		$form_fields['isc_image_source']['label'] = __( 'Image Source', 'image-source-control-isc' );
		$form_fields['isc_image_source']['value'] = get_post_meta( $post->ID, 'isc_image_source', true );
		$form_fields['isc_image_source']['helps'] = __( 'Include the image source here.', 'image-source-control-isc' );

		// add checkbox to mark as your own image
		$form_fields['isc_image_source_own']['input'] = 'html';
		$form_fields['isc_image_source_own']['label'] = '';
		$form_fields['isc_image_source_own']['helps'] =
			__( 'Check this box if this is your own image and doesn\'t need a source.', 'image-source-control-isc' );
		$form_fields['isc_image_source_own']['html']  =
			"<input type='checkbox' value='1' name='attachments[{$post->ID}][isc_image_source_own]' id='attachments[{$post->ID}][isc_image_source_own]' "
			. checked( get_post_meta( $post->ID, 'isc_image_source_own', true ), 1, false )
			. ' style="width:14px"/> '
			. __( 'This is my image', 'image-source-control-isc' );

		// add input field for source url
		$form_fields['isc_image_source_url']['label'] = __( 'Image Source URL', 'image-source-control-isc' );
		$form_fields['isc_image_source_url']['value'] = get_post_meta( $post->ID, 'isc_image_source_url', true );
		$form_fields['isc_image_source_url']['helps'] = __( 'URL to link the source text to.', 'image-source-control-isc' );

		// add input field for source
		$options  = $this->get_isc_options();
		$licences = $this->licences_text_to_array( $options['licences'] );
		if ( $options['enable_licences'] && $licences ) {
			$form_fields['isc_image_licence']['input'] = 'html';
			$form_fields['isc_image_licence']['label'] = __( 'Image License', 'image-source-control-isc' );
			$form_fields['isc_image_licence']['helps'] = __( 'Choose the image license.', 'image-source-control-isc' );
			$html                                      = '<select name="attachments[' . $post->ID . '][isc_image_licence]" id="attachments[' . $post->ID . '][isc_image_licence]">';
				$html                                 .= '<option value="">--</option>';
			foreach ( $licences as $_licence_name => $_licence_data ) {
				$html .= '<option value="' . $_licence_name . '" ' . selected( get_post_meta( $post->ID, 'isc_image_licence', true ), $_licence_name, false ) . '>' . $_licence_name . '</option>';
			}
			$html                                    .= '</select>';
			$form_fields['isc_image_licence']['html'] = $html;
		}

		return $form_fields;
	}

	/**
	 * Create the menu pages for isc
	 *
	 * @since 1.0
	 */
	public function create_menu() {
		global $isc_page;
		global $isc_setting;

		// These pages should be available only for editors and higher
		$isc_page    = add_submenu_page( 'upload.php', 'Manage image sources with the Image Source Control Plugin', __( 'Image Sources', 'image-source-control-isc' ), 'edit_others_posts', 'isc-sources', array( $this, 'render_sources_page' ) );
		$isc_setting = add_options_page( __( 'Image control - ISC plugin', 'image-source-control-isc' ), __( 'Image Sources', 'image-source-control-isc' ), 'edit_others_posts', 'isc-settings', array( $this, 'render_isc_settings_page' ) );
	}

	/**
	 * Settings API initialization
	 *
	 * @update 1.3.5 added settings for sources
	 * @todo rewrite this and following functions to a more practical form (esp. shorter) or at least bundle field into more useful sections
	 */
	public function SAPI_init() {
		$this->upgrade_management();
		register_setting( 'isc_options_group', 'isc_options', array( $this, 'settings_validation' ) );
		add_settings_section( 'isc_settings_section', '', '__return_false', 'isc_settings_page' );

		// handle type of source display
		add_settings_field( 'source_display_type', __( 'How to display sources', 'image-source-control-isc' ), array( $this, 'renderfield_sources_display_type' ), 'isc_settings_page', 'isc_settings_section' );

		// settings for archive pages
		add_settings_field( 'list_on_archives', __( 'Sources below full posts', 'image-source-control-isc' ), array( $this, 'renderfield_list_on_archives' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'list_on_excerpts', __( 'Sources below excerpts', 'image-source-control-isc' ), array( $this, 'renderfield_list_on_excerpts' ), 'isc_settings_page', 'isc_settings_section' );

		// settings for sources list below single pages
		add_settings_field( 'image_list_headline', __( 'Image list headline', 'image-source-control-isc' ), array( $this, 'renderfield_list_headline' ), 'isc_settings_page', 'isc_settings_section' );

		// source in caption
		add_settings_field( 'source_caption', __( 'Overlay pre-text', 'image-source-control-isc' ), array( $this, 'renderfield_overlay_text' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'caption_position', __( 'Overlay position', 'image-source-control-isc' ), array( $this, 'renderfield_overlay_position' ), 'isc_settings_page', 'isc_settings_section' );

		// full image sources list group
		add_settings_field( 'use_thumbnail', __( 'Use thumbnails in images list', 'image-source-control-isc' ), array( $this, 'renderfield_use_thumbnail' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'thumbnail_width', __( 'Thumbnails max-width', 'image-source-control-isc' ), array( $this, 'renderfield_thumbnail_width' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'thumbnail_height', __( 'Thumbnails max-height', 'image-source-control-isc' ), array( $this, 'renderfield_thumbnail_height' ), 'isc_settings_page', 'isc_settings_section' );

		// Licence settings group
		add_settings_field( 'enable_licences', __( 'Enable licenses', 'image-source-control-isc' ), array( $this, 'renderfield_enable_licences' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'licences', __( 'List of licenses', 'image-source-control-isc' ), array( $this, 'renderfield_licences' ), 'isc_settings_page', 'isc_settings_section' );

		// Misc settings group
		add_settings_field( 'exclude_own_images', __( 'Exclude own images', 'image-source-control-isc' ), array( $this, 'renderfield_exclude_own_images' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'use_authorname', __( 'Use authors names', 'image-source-control-isc' ), array( $this, 'renderfield_use_authorname' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'by_author_text', __( 'Custom text for owned images', 'image-source-control-isc' ), array( $this, 'renderfield_byauthor_text' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'warning_one_source', __( 'Warn about missing sources', 'image-source-control-isc' ), array( $this, 'renderfield_warning_onesource_misisng' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'enable_log', __( 'Debug log', 'image-source-control-isc' ), array( $this, 'renderfield_enable_log' ), 'isc_settings_page', 'isc_settings_section' );
		add_settings_field( 'remove_on_uninstall', __( 'Delete data on uninstall', 'image-source-control-isc' ), array( $this, 'renderfield_remove_on_uninstall' ), 'isc_settings_page', 'isc_settings_section' );
	}

	/**
	 * Manage data structure upgrading of outdated versions
	 */
	public function upgrade_management() {

		/**
		 * This function checks options in database
		 * during the admin_init hook to handle plugin's upgrade.
		 */

		$options = get_option( 'isc_options', $this->default_options() );

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
			update_option( 'isc_options', $this->default_options() );
		}

		if ( ISCVERSION !== $options['version'] ) {
			$options            = $options + $this->default_options();
			$options['version'] = ISCVERSION;
			update_option( 'isc_options', $options );
		}

	}

	/**
	 * Image_control's page callback
	 */
	public function render_isc_settings_page() {
		?>
			<div id="icon-options-general" class="icon32"><br></div>
			<h1><?php esc_html_e( 'ISC settings', 'image-source-control-isc' ); ?></h1>
			<p>
			<?php
			printf(
				wp_kses(
						// translators: %1$s is a starting a-tag, %2$s is the closing one.
					__( 'You can manage and debug image sources under %1$sMedia > Image Sources%2$s.', 'image-source-control-isc' ),
					array(
						'a' => array(
							'href' => array(),
						),
					)
				),
				'<a href="' . esc_url( admin_url( 'upload.php?page=isc-sources' ) ) . '">',
				'</a>'
			);
			?>
					</p>
			<div id="isc-admin-wrap">
				<form id="image-control-form" method="post" action="options.php">
					<div id="isc-setting-group-type" class="postbox isc-setting-group"><?php // Open the div for the first settings group ?>
					<h3 class="setting-group-head"><?php esc_html_e( 'How to display source in Frontend', 'image-source-control-isc' ); ?></h3>
				<?php
					settings_fields( 'isc_options_group' );
					do_settings_sections( 'isc_settings_page' );
				?>
					</div><?php // Close the last settings group div ?>
					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
					</p>
				</form>
			</div><!-- #isc-admin-wrap -->
			<?php
	}

	/**
	 * Missing sources page callback
	 */
	public function render_sources_page() {
		require_once ISCPATH . '/admin/templates/sources.php';
	}

	/**
	 * Choose type of sources display in the frontend
	 *
	 * @since 1.7
	 */
	public function renderfield_sources_display_type() {
		$options = $this->get_isc_options();
		?>
			<p class="description"><?php esc_html_e( 'Choose where to display image sources in the frontend', 'image-source-control-isc' ); ?></p><br/>
			<div id="display_types_block">
				<input type="hidden" name="isc_options[display_type]" value=""/>

				<input type="checkbox" name="isc_options[display_type][]" id="display-types-list" value="list" <?php checked( in_array( 'list', $options['display_type'], true ), true ); ?> />
				<label for="display-types-list">
			<?php
			esc_html_e( 'list below content', 'image-source-control-isc' );
			?>
				</label>
				<p class="description"><?php esc_html_e( 'Displays a list of image sources below singular pages.', 'image-source-control-isc' ); ?></p>

				<input type="checkbox" name="isc_options[display_type][]" id="display-types-overlay" value="overlay" <?php checked( in_array( 'overlay', $options['display_type'], true ), true ); ?> />
				<label for="display-types-overlay">
				<?php
				esc_html_e( 'overlay', 'image-source-control-isc' );
				?>
				</label>
				<p class="description">
				<?php
				esc_html_e( 'Display image source as a simple overlay', 'image-source-control-isc' );
				?>
				</p>

				<p>
				<?php
				printf(
					wp_kses(
							// translators: %1$s is the beginning link tag, %2$s is the closing one.
						__( 'If you don’t want to use any of these methods, you can still place the image source list manually as described %1$shere%2$s', 'image-source-control-isc' ),
						array(
							'a' => array( 'href' ),
						)
					),
					'<a href="http://webgilde.com/en/image-source-control/image-sources-frontend/" target="_blank">',
					'</a>'
				)
				?>
				</p>
			</div>
			</td></tr></tbody></table>
			</div><!-- .postbox -->
			<div id="isc-setting-group-list" class="postbox isc-setting-group">
			<h3 class="setting-group-head"><?php esc_html_e( 'Archive Pages', 'image-source-control-isc' ); ?></h3>
			<table class="form-table"><tbody>
			<?php
	}

	/**
	 * Select the option for sources on archive pages
	 *
	 * @since 1.8
	 */
	public function renderfield_list_on_archives() {
		$options = $this->get_isc_options();
		?>
			<div id="display_types_block">
				<input type="checkbox" name="isc_options[list_on_archives]" id="list-on-archives" value="1" <?php checked( 1, $options['list_on_archives'], true ); ?> />
				<label for="list-on-archives">
			<?php
			esc_html_e( 'Display sources list below full posts', 'image-source-control-isc' );
			?>
				</label>
				<p class="description"><?php esc_html_e( 'Choose this option if you want to display the sources list attached to posts on archive and category pages that display the full content.', 'image-source-control-isc' ); ?></p>
			</div>
			<?php
	}

	/**
	 * Select the option for sources on archive pages
	 *
	 * @since 1.8
	 */
	public function renderfield_list_on_excerpts() {
		$options = $this->get_isc_options();
		?>
			<div id="display_types_block">
				<input type="checkbox" name="isc_options[list_on_excerpts]" id="list-on-excerpts" value="1" <?php checked( 1, $options['list_on_excerpts'], true ); ?> />
				<label for="list-on-excerpts">
			<?php
			esc_html_e( 'Display sources list below excerpts', 'image-source-control-isc' );
			?>
				</label>
				<p class="description"><?php esc_html_e( 'Choose this option if you want to display the source of the featured image below the post excerpt. The source will be attached to the excerpt and it might happen that you see it everywhere. If this happens you should display the source manually in your template.', 'image-source-control-isc' ); ?></p>
			</div>
			</td></tr></tbody></table>
			</div><!-- .postbox -->
			<div id="isc-setting-group-list" class="postbox isc-setting-group">
			<h3 class="setting-group-head"><?php esc_html_e( 'List below content', 'image-source-control-isc' ); ?></h3>
			<table class="form-table"><tbody>
			<?php
	}

			/**
			 * Render option to define a headline for the image list
			 */
	public function renderfield_list_headline() {
		$options = $this->get_isc_options();
		?>
			<div id="image-list-headline-block">
				<label for="list-head"><?php esc_html_e( 'Image list headline', 'image-source-control-isc' ); ?></label>
				<input type="text" name="isc_options[image_list_headline_field]" id="list-head" value="<?php echo esc_attr( $options['image_list_headline'] ); ?>" class="regular-text" />
				<p class="description"><?php esc_html_e( 'The headline of the image list added via shortcode or function in your theme.', 'image-source-control-isc' ); ?></p>
			</div>
			</td></tr></tbody></table>
			</div><!-- .postbox -->
			<div id="isc-setting-group-overlay" class="postbox isc-setting-group">
			<h3 class="setting-group-head"><?php esc_html_e( 'Overlay', 'image-source-control-isc' ); ?></h3>
			<table class="form-table"><tbody>
			<?php
	}

			/**
			 * Render option for the text preceding the source.
			 */
	public function renderfield_overlay_text() {
		$options = $this->get_isc_options();
		?>
			<div id="overlay-block">
				<input type="text" id='source-pretext' name="isc_options[source_pretext]" value="<?php echo esc_attr( $options['source_pretext'] ); ?>" />
				<p class="description"><?php esc_html_e( 'The text preceding the source.', 'image-source-control-isc' ); ?></p>
			</div>
			<?php
	}

			/**
			 * Render option for the position of the overlay on images
			 */
	public function renderfield_overlay_position() {
		$options = $this->get_isc_options();
		?>
			<div id="caption-position-block">
					<select id="caption-pos" name="isc_options[cap_pos]">
					<?php foreach ( $this->caption_position as $pos ) : ?>
							<option value="<?php echo esc_attr( $pos ); ?>" <?php selected( $pos, $options['caption_position'] ); ?>><?php echo esc_html( $pos ); ?></option>
						<?php endforeach; ?>
					</select>
				<p class="description"><?php esc_html_e( 'Position of overlay into images', 'image-source-control-isc' ); ?></p>
			</div>
			</td></tr></tbody></table>
			</div><!-- .postbox -->
			<div class="postbox isc-setting-group">
			<h3 class="setting-group-head"><?php esc_html_e( 'Full images list', 'image-source-control-isc' ); ?></h3>
			<table class="form-table"><tbody>
			<?php
	}

	/**
	 * Render option to exclude image from lists if it is makes as "by the author"
	 *
	 * @since 1.3.7
	 */
	public function renderfield_exclude_own_images() {
		$options = $this->get_isc_options();
		?>
			<div id="use-authorname-block">
				<label for="exclude_own_images"><?php esc_html_e( 'Hide sources for own images', 'image-source-control-isc' ); ?></label>
				<input type="checkbox" name="isc_options[exclude_own_images]" id="exclude_own_images" <?php checked( $options['exclude_own_images'] ); ?> />
				<p class="description"><?php esc_html_e( "Exclude images marked as 'own image' from image lists (post and full) and overlay in the frontend. You can still manage them in the dashboard.", 'image-source-control-isc' ); ?></p>
			</div>
			<?php
	}

			/**
			 * Render option to choose if the author’s public name should be displayed for their images.
			 */
	public function renderfield_use_authorname() {
		$options = $this->get_isc_options();
		?>
			<div id="use-authorname-block">
				<label for="use_authorname"><?php esc_html_e( 'Use author name', 'image-source-control-isc' ); ?></label>
				<input type="checkbox" name="isc_options[use_authorname_ckbox]" id="use_authorname" <?php checked( $options['use_authorname'] ); ?> />
				<p class="description"><?php esc_html_e( "Display the author's public name as source when the image is owned by the author (the uploader of the image, not necessarily the author of the post the image is displayed on). Uncheck to use a custom text instead.", 'image-source-control-isc' ); ?></p>
			</div>
			<?php
	}

			/**
			 * Render option to enter a string that should show instead of the author name.
			 */
	public function renderfield_byauthor_text() {
		$options = $this->get_isc_options();
		?>
			<div id="by-author-text">
				<input type="text" id="byauthor" name="isc_options[by_author_text_field]" value="<?php echo esc_attr( $options['by_author_text'] ); ?>" <?php disabled( $options['use_authorname'] ); ?> class="regular-text" />
				<p class="description"><?php esc_html_e( "Enter the custom text to display if you do not want to use the author's public name.", 'image-source-control-isc' ); ?></p>
			</div>
			<?php
	}

			/**
			 * Render option to enable the license settings.
			 */
	public function renderfield_enable_licences() {
		$options = $this->get_isc_options();
		?>
			<div id="enable-licences">
				<input type="checkbox" name="isc_options[enable_licences]" id="enable_licences" <?php checked( $options['enable_licences'] ); ?> />
				<p class="description"><?php esc_html_e( 'Enable this to be able to add and display copyright/copyleft licenses for your images and manage them in the field below.', 'image-source-control-isc' ); ?></p>
			</div>
			<?php
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

		?>
			<div id="licences">
				<textarea name="isc_options[licences]"><?php echo esc_html( $options['licences'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'List of licenses the author can choose for an image. Enter a license per line and separate the name from the optional link with a pipe symbol (e.g. CC BY 2.0|http://creativecommons.org/licenses/by/2.0/legalcode).', 'image-source-control-isc' ); ?></p>
			</div>
			</td></tr></tbody></table>
			</div><!-- .postbox -->
			<div class="postbox isc-setting-group">
			<h3 class="setting-group-head"><?php esc_html_e( 'Miscellaneous settings', 'image-source-control-isc' ); ?></h3>
			<table class="form-table"><tbody><tr>
			<?php
	}

			/**
			 * Render option to display thumbnails in the full image source list
			 */
	public function renderfield_use_thumbnail() {
		$options = $this->get_isc_options();
		?>
			<div id="use-thumbnail-block">
				<input type="checkbox" id="use-thumbnail" name="isc_options[use_thumbnail]" value="1" <?php checked( $options['thumbnail_in_list'] ); ?> />
				<select id="thumbnail-size-select" name="isc_options[size_select]" <?php disabled( ! $options['thumbnail_in_list'] ); ?>>
				<?php foreach ( $this->thumbnail_size as $size ) : ?>
						<option value="<?php echo esc_html( $size ); ?>" <?php selected( $size, $options['thumbnail_size'] ); ?>><?php echo esc_html( $size ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Display thumbnails on the list of all images in the blog.', 'image-source-control-isc' ); ?></p>
			</div>
			<?php
	}

			/**
			 * Render option to define the width of the thumbnails displayed in the full image source list.
			 */
	public function renderfield_thumbnail_width() {
		$options = $this->get_isc_options();
		?>
			<div id="thumbnail-custom-width">
				<input type="text" id="custom-width" name="isc_options[thumbnail_width]" class="small-text" value="<?php echo esc_attr( $options['thumbnail_width'] ); ?>" /> px
				<p class="description"><?php esc_html_e( 'Custom value of the maximum allowed width for thumbnail.', 'image-source-control-isc' ); ?></p>
			</div>
			<?php
	}

	/**
	 * Render option to define the height of the thumbnails displayed in the full image source list.
	 */
	public function renderfield_thumbnail_height() {
		$options = $this->get_isc_options();
		?>
			<div id="thumbnail-custom-height">
				<input type="text" id="custom-height" name="isc_options[thumbnail_height]" class="small-text" value="<?php echo esc_attr( $options['thumbnail_height'] ); ?>"/> px
				<p class="description"><?php esc_html_e( 'Custom value of the maximum allowed height for thumbnail.', 'image-source-control-isc' ); ?></p>
			</div>
			</td></tr></tbody></table>
			</div><!-- .postbox -->
			<div class="postbox isc-setting-group">
			<h3 class="setting-group-head"><?php esc_html_e( 'Licenses settings', 'image-source-control-isc' ); ?></h3>
			<table class="form-table"><tbody><tr>
			<?php
	}

	/**
	 * Render the option to display a warning in the admin area if an image source is missing.
	 */
	public function renderfield_warning_onesource_misisng() {
		$options = $this->get_isc_options();
		?>
			<div id="one-source-missing-block">
				<input type="checkbox" name="isc_options[warning_onesource_missing]" value="1" <?php checked( $options['warning_onesource_missing'] ); ?>/>
				<p class="description"><?php esc_html_e( 'Display an admin notice in admin pages when one or more image sources are missing.', 'image-source-control-isc' ); ?></p>
			</div>
			<?php
	}

	/**
	 * Render the option to log image source activity in isc.log
	 */
	public function renderfield_enable_log() {
		$options = $this->get_isc_options();
		$checked = ! empty( $options['enable_log'] );
		?>
		<input type="checkbox" name="isc_options[enable_log]" value="1" <?php checked( $checked ); ?>/>
				<p class="description">
				<?php
				echo sprintf(
						// translators: $s is replaced by starting and ending a tags to create a link
					esc_html__( 'Writes image source activity to the %sisc.log%s file.', 'image-source-control-isc' ),
					'<a href="' . ISC_Log::get_log_file_URL() . '" target="_blank">',
					'</a>'
				);
				?>
					</p>
			<?php
	}

	/**
	 * Render the option to remove all options and meta data when the plugin is deleted.
	 */
	public function renderfield_remove_on_uninstall() {
		$options = $this->get_isc_options();
		$checked = ! empty( $options['remove_on_uninstall'] );
		?>
		<input type="checkbox" name="isc_options[remove_on_uninstall]" value="1" <?php checked( $checked ); ?>/>
				<p class="description"><?php esc_html_e( 'Remove plugin options and image sources from the database when you delete the plugin.', 'image-source-control-isc' ); ?></p>
			<?php
	}

	/**
	 * Get all attachments with empty sources options.
	 *
	 * @return array with attachments.
	 */
	public static function get_attachments_with_empty_sources() {
		$args = array(
			'post_type'   => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => null,
			'meta_query'  => array(
				// image source is empty
				array(
					'key'     => 'isc_image_source',
					'value'   => '',
					'compare' => '=',
				),
				// and does not belong to an author
				array(
					'key'     => 'isc_image_source_own',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		);

		// is per function definition always returning an array, even if empty.
		return get_posts( $args );
	}

	/**
	 * Get all attachments that are not used
	 * read: they don’t have the proper meta values set up, yet.
	 *
	 * @since 1.6
	 * @return array with attachments.
	 */
	public static function get_unused_attachments() {
		$args = array(
			'post_type'   => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => null,
			'meta_query'  => array(
				// image source is empty
				array(
					'key'     => 'isc_image_source',
					'value'   => 'any', /* any string; needed prior to WP 3.9 */
					'compare' => 'NOT EXISTS',
				),
			),
		);

		// is per function definition always returning an array, even if empty.
		return get_posts( $args );
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

		die( esc_html( "$removed_rows entries deleted" ) );
	}

	/**
	 * Input validation function.
	 *
	 * @param array $input values from the admin panel.
	 * @updated 1.3.5 added licenses fields
	 */
	public function settings_validation( $input ) {
		$output = $this->get_isc_options();
		if ( ! is_array( $input['display_type'] ) ) {
			$output['display_type'] = array();
		} else {
			$output['display_type'] = $input['display_type'];
		}
		$output['list_on_archives'] = isset( $input['list_on_archives'] );
		$output['list_on_excerpts'] = isset( $input['list_on_excerpts'] );

		$output['image_list_headline'] = isset( $input['image_list_headline_field'] ) ? esc_html( $input['image_list_headline_field'] ) : '';
		if ( isset( $input['use_authorname_ckbox'] ) ) {
			// Don't worry about the custom text if the author name is selected.
			$output['use_authorname'] = true;
		} else {
			$output['use_authorname'] = false;
			$output['by_author_text'] = isset( $input['by_author_text_field'] ) ? esc_html( $input['by_author_text_field'] ) : '';
		}
		$output['exclude_own_images'] = isset( $input['exclude_own_images'] );
		$output['enable_licences']    = isset( $input['enable_licences'] );

		if ( isset( $input['licences'] ) ) {
			$output['licences'] = esc_textarea( $input['licences'] );
		} else {
			$output['licences'] = false;
		}
		if ( isset( $input['use_thumbnail'] ) ) {
			$output['thumbnail_in_list'] = true;
			if ( in_array( $input['size_select'], $this->thumbnail_size ) ) {
				$output['thumbnail_size'] = $input['size_select'];
			}
			if ( 'custom' === $input['size_select'] ) {
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
		$output['warning_onesource_missing'] = isset( $input['warning_onesource_missing'] );

		// remove the debug log file when it was disabled
        if( isset( $output['enable_log'] ) && ! isset( $input['enable_log'] ) ) {
            ISC_Log::delete_log_file();
        }
		$output['enable_log']                = isset( $input['enable_log'] );

		$output['remove_on_uninstall']       = isset( $input['remove_on_uninstall'] );
		$output['hide_list']                 = isset( $input['hide_list'] );

		if ( isset( $input['cap_pos'] ) && in_array( $input['cap_pos'], $this->caption_position, true ) ) {
			$output['caption_position'] = $input['cap_pos'];
		}
		if ( isset( $input['source_pretext'] ) ) {
			$output['source_pretext'] = esc_textarea( $input['source_pretext'] );
		}
		return $output;
	}

}
