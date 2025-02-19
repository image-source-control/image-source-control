<?php

namespace ISC\Image_Sources;

/**
 * Main renderer class
 */
class Renderer {

	/**
	 * Get the ISC options
	 *
	 * @return array
	 */
	protected static function get_options(): ?array {
		return \ISC\Plugin::get_options();
	}
}
