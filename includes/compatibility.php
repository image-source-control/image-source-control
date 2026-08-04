<?php

namespace ISC;

/**
 * Compatibility checks for known third-party integrations.
 */
class Compatibility {

	/**
	 * Return all compatibility notices for the current site.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_notices(): array {
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

		return $notices;
	}

	/**
	 * Return the compatibility check method names.
	 *
	 * @return string[]
	 */
	protected function get_checks(): array {
		return [
			'check_wp_bakery',
		];
	}

	/**
	 * Check if WPBakery is active and requires a manual compatibility step.
	 *
	 * @return array<string,mixed>|null
	 */
	public function check_wp_bakery() {
		if ( ! defined( 'WPB_VC_VERSION' ) ) {
			return null;
		}

		return [
			'name'          => 'WPBakery Page Builder',
			'description'   => __( 'Background images used by WPBakery require a manual setup.', 'image-source-control-isc' ),
			'manual_url'    => 'https://imagesourcecontrol.com/documentation/compatibility/#WPBakery_Page_Builder',
			'show_pro_link' => false,
		];
	}
}
