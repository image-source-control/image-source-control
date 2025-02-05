<?php

namespace ISC;

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
		return Plugin::get_options();
	}
}
