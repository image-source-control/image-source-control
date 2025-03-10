<?php

namespace ISC;

/**
 * Render the settings page
 */
class Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'settings_init' ] );
		add_action( 'admin_menu', [ $this, 'add_menu_item' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_scripts' ] );
		add_action( 'admin_print_scripts', [ $this, 'admin_head_scripts' ] );
	}

	/**
	 * Initialize settings
	 */
	public function settings_init() {
		$this->upgrade_settings();
		register_setting( 'isc_options_group', 'isc_options', [ $this, 'settings_validation' ] );

		new \ISC\Settings\Sections\Newsletter();
		new \ISC\Settings\Sections\Plugin_Options();
		new \ISC\Settings\Sections\Caption();
		new \ISC\Settings\Sections\Page_List();
		new \ISC\Settings\Sections\Global_List();
		new \ISC\Settings\Sections\Licenses();
		new \ISC\Settings\Sections\Miscellaneous();
	}

	/**
	 * Register the settings page
	 */
	public function add_menu_item() {
		add_options_page(
			__( 'Image Source Control Settings', 'image-source-control-isc' ),
			__( 'Image Sources', 'image-source-control-isc' ),
			'edit_others_posts',
			'isc-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Render the settings page
	 */
	public function render_settings_page() {
		global $wp_settings_sections;

		if ( ! isset( $wp_settings_sections['isc_settings_page'] ) ) {
			return;
		}

		$page             = 'isc_settings_page';
		$settings_section = $wp_settings_sections[ $page ];

		require_once ISCPATH . '/admin/templates/settings/settings.php';
	}

	/**
	 * Add scripts to ISC-related pages
	 */
	public function add_admin_scripts() {
		$screen = get_current_screen();

		if ( isset( $screen->id ) && $screen->id === 'settings_page_isc-settings' ) {
			wp_enqueue_script( 'isc_settings_script', ISCBASEURL . '/admin/assets/js/settings.js', [], ISCVERSION, true );
		}
	}

	/**
	 * Display scripts in <head></head> section of the settings page.
	 * Useful for creating js variables in the js global namespace.
	 */
	public function admin_head_scripts() {
		$screen = get_current_screen();
		if ( isset( $screen->id ) && $screen->id === 'settings_page_isc-settings' ) {
			?>
			<script>
				isc_settings = {
					baseurl: '<?php echo esc_url( ISCBASEURL ); ?>'
				};
			</script>
			<style>
				#isc-settings-caption-pos-options {
					background: url(<?php echo esc_url( ISCBASEURL ) . 'admin/templates/settings/preview/image-for-position-preview.jpg'; ?>) no-repeat center/cover;
				}
			</style>
			<?php
		}
	}

	/**
	 * Manage data structure upgrading of outdated versions
	 */
	public function upgrade_settings() {
		$default_options = Plugin::default_options();

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
	 * Input validation function.
	 *
	 * @param array $input values from the admin panel.
	 */
	public function settings_validation( $input ) {
		$output = Plugin::get_options();

		// the display_type option array is split between various sections, so best to validate it here
		if ( ! is_array( $input['display_type'] ) ) {
			$output['display_type'] = [];
		} else {
			$output['display_type'] = $input['display_type'];
		}

		// add activation date when the settings are saved for the first time
		if ( ! array_key_exists( 'activated', $output ) ) {
			$output['activated'] = time();
		}

		/**
		 * Allow our setting section classes to validate or manipulate settings on save
		 */
		return apply_filters( 'isc_settings_on_save_after_validation', $output, $input );
	}
}
