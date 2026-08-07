<?php

namespace ISC\Settings\Sections;

use ISC\Settings;

/**
 * Compatibility checks for known third-party integrations.
 */
class Compatibility extends Settings\Section {

	/**
	 * Compatibility notices for the current request.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private $notices = null;

	/**
	 * Add settings section when needed.
	 */
	public function add_settings_section() {
		if ( [] === $this->get_notices() ) {
			return;
		}

		add_settings_section(
			'isc_settings_section_compatibility',
			__( 'Compatibility', 'image-source-control-isc' ),
			[ $this, 'render_settings_section' ],
			'isc_settings_page'
		);
	}

	/**
	 * Return all compatibility notices for the current site.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_notices(): array {
		if ( null !== $this->notices ) {
			return $this->notices;
		}

		$notices = [];

		foreach ( $this->get_checks() as $check ) {
			$notice = $this->{$check}();

			if ( is_array( $notice ) ) {
				$notices[] = wp_parse_args(
					$notice,
					[
						'name'          => '',
						'description'   => '',
						'manual_url'    => '',
						'show_pro_link' => false,
					]
				);
			}
		}

		$this->notices = $notices;

		return $this->notices;
	}

	/**
	 * Render the compatibility settings section.
	 */
	public function render_settings_section() {
		$notices = $this->get_notices();

		require ISCPATH . '/admin/templates/settings/compatibility.php';
	}

	/**
	 * Return the compatibility check method names.
	 *
	 * @return string[]
	 */
	protected function get_checks(): array {
		return [
			'check_advanced_custom_fields_unused_images',
			'check_avada_builder',
			'check_blocksy_theme',
			'check_divi',
			'check_elementor',
			'check_flatsome_unused_images',
			'check_gallery_block_lightbox',
			'check_jet_engine',
			'check_kadence_carousel',
			'check_kadence_blocks',
			'check_kadence_shop_kit_unused_images',
			'check_kadence_theme_kit_pro_unused_images',
			'check_newsletter_unused_images',
			'check_polylang',
			'check_soledad',
			'check_woocommerce_unused_images',
			'check_wp_bakery',
			'check_wp_all_import',
			'check_wpml',
		];
	}

	/**
	 * Check if Advanced Custom Fields is active in combination with the Unused Images module.
	 *
	 * @return array|null
	 */
	public function check_advanced_custom_fields_unused_images() {
		if ( ! class_exists( 'ACF' ) || ! \ISC\Plugin::is_module_enabled( 'unused_images' ) ) {
			return null;
		}

		return [
			'name'          => 'Advanced Custom Fields',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#Advanced_Custom_Fields',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Avada Builder is active.
	 *
	 * @return array|null
	 */
	public function check_avada_builder() {
		if ( ! defined( 'FUSION_BUILDER_VERSION' ) || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'Avada Builder',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#Avada_Builder',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Blocksy theme is active.
	 *
	 * @return array|null
	 */
	public function check_blocksy_theme() {
		if ( ! $this->is_current_theme( 'blocksy' ) || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'Blocksy',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#Blocksy',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Divi Builder is active.
	 *
	 * @return array|null
	 */
	public function check_divi() {
		if ( ! defined( 'ET_SHORTCODES_VERSION' ) || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'Divi Builder',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#Divi',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Elementor is active.
	 *
	 * @return array|null
	 */
	public function check_elementor() {
		if ( ! defined( 'ELEMENTOR_VERSION' ) || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'Elementor',
			'description'   => sprintf(
				// translators: %s is the name of the theme or page builder, e.g. Divi.
				esc_html__( 'Enable support for %s background images.', 'image-source-control-isc' ),
				'Elementor'
			),
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#Elementor',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Flatsome UX Builder is active in combination with the Unused Images module and Pro is not.
	 *
	 * @return array|null
	 */
	public function check_flatsome_unused_images() {
		if ( ! defined( 'UX_BUILDER_VERSION' ) || \ISC\Plugin::is_pro() || ! \ISC\Plugin::is_module_enabled( 'unused_images' ) ) {
			return null;
		}

		return [
			'name'          => 'Flatsome UX Builder',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if the Gallery Block Lightbox plugin is active.
	 * No notice when Pro is enabled since it resolves the issue automatically.
	 *
	 * @return array|null
	 */
	public function check_gallery_block_lightbox() {
		if ( ! function_exists( '\Gallery_Block_Lightbox\register_assets' ) || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) || \ISC\Plugin::is_pro() ) {
			return null;
		}

		return [
			'name'          => 'Gallery Block Lightbox',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#Gallery_Block_Lightbox',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if JetEngine is active.
	 *
	 * @return array|null
	 */
	public function check_jet_engine() {
		if ( ! class_exists( 'Jet_Engine', false ) || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'JetEngine',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#JetEngine',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Kadence Related Content Carousel is active and Pro is not
	 *
	 * @return array|null
	 */
	public function check_kadence_carousel() {
		if ( ! defined( 'KTRC_VERSION' ) || \ISC\Plugin::is_pro() || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'Kadence Related Content Carousel',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#Kadence',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Kadence Blocks is active and Pro is not
	 *
	 * @return array|null
	 */
	public function check_kadence_blocks() {
		if ( ! defined( 'KADENCE_BLOCKS_VERSION' ) || \ISC\Plugin::is_pro() || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'Kadence Blocks',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#Kadence',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Kadence Shop Kit is active in combination with the Unused Images module and Pro is not.
	 *
	 * @return array|null
	 */
	public function check_kadence_shop_kit_unused_images() {
		if ( ! defined( 'KADENCE_WOO_EXTRAS_VERSION' ) || \ISC\Plugin::is_pro() || ! \ISC\Plugin::is_module_enabled( 'unused_images' ) ) {
			return null;
		}

		return [
			'name'          => 'Kadence Shop Kit',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Kadence Theme Kit Pro is active in combination with the Unused Images module and Pro is not.
	 *
	 * @return array|null
	 */
	public function check_kadence_theme_kit_pro_unused_images() {
		if ( ! defined( 'KTP_VERSION' ) || \ISC\Plugin::is_pro() || ! \ISC\Plugin::is_module_enabled( 'unused_images' ) ) {
			return null;
		}

		return [
			'name'          => 'Kadence Theme Kit Pro',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Newsletter plugin is active in combination with the Unused Images module and Pro is not.
	 *
	 * @return array|null
	 */
	public function check_newsletter_unused_images() {
		if ( ! defined( 'NEWSLETTER_VERSION' ) || \ISC\Plugin::is_pro() || ! \ISC\Plugin::is_module_enabled( 'unused_images' ) ) {
			return null;
		}

		return [
			'name'          => 'Newsletter',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if Polylang and the Image Source module are active.
	 *
	 * @return array|null
	 */
	public function check_polylang() {
		if ( ! defined( 'POLYLANG_VERSION' ) || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'Polylang',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#Polylang',
			'show_pro_link' => false,
		];
	}

	/**
	 * Check if Soledad theme is active and Pro is not.
	 *
	 * @return array|null
	 */
	public function check_soledad() {
		if ( ! defined( 'PENCI_SOLEDAD_VERSION' ) || \ISC\Plugin::is_pro() || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'Soledad',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if WooCommerce is active in combination with the Unused Images module and Pro is not.
	 *
	 * @return array|null
	 */
	public function check_woocommerce_unused_images() {
		if ( ! class_exists( 'WooCommerce' ) || \ISC\Plugin::is_pro() || ! \ISC\Plugin::is_module_enabled( 'unused_images' ) ) {
			return null;
		}

		return [
			'name'          => 'WooCommerce',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if WPBakery is active and Pro is not.
	 *
	 * @return array|null
	 */
	public function check_wp_bakery() {
		if ( ! defined( 'WPB_VC_VERSION' ) || \ISC\Plugin::is_pro() || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'WPBakery Page Builder (formerly Visual Composer)',
			'description'   => sprintf(
			// translators: %s is the name of the theme or page builder, e.g. Divi.
				esc_html__( 'Enable support for %s background images.', 'image-source-control-isc' ),
				'WPBakery Page Builder'
			),
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#WPBakery_Page_Builder',
			'show_pro_link' => true,
		];
	}

	/**
	 * Check if WP All Import and the Image Source module are active.
	 *
	 * @return array|null
	 */
	public function check_wp_all_import() {
		if ( ! defined( 'PMXI_VERSION' ) || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'WP All Import',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#WP_All_Import',
			'show_pro_link' => false,
		];
	}

	/**
	 * Check if WPML is active.
	 *
	 * @return array|null
	 */
	public function check_wpml() {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) || ! \ISC\Plugin::is_module_enabled( 'image_sources' ) ) {
			return null;
		}

		return [
			'name'          => 'WPML',
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#WPML',
			'show_pro_link' => false,
		];
	}

	/**
	 * Helper function to check if the current theme (parent or child) is a specific theme.
	 *
	 * @param string $theme_name The name of the theme to check for.
	 * @return bool True if the current theme matches the specified theme name, false otherwise.
	 */
	public function is_current_theme( string $theme_slug ): bool {
		$current_theme = wp_get_theme();

		if ( strcasecmp( $current_theme->get_stylesheet(), $theme_slug ) === 0 || strcasecmp( $current_theme->get_template(), $theme_slug ) === 0 ) {
			return true;
		}

		if ( $current_theme->parent() && ( strcasecmp( $current_theme->parent()->get_stylesheet(), $theme_slug ) === 0 || strcasecmp( $current_theme->parent()->get_template(), $theme_slug ) === 0 ) ) {
			return true;
		}

		return false;
	}
}
