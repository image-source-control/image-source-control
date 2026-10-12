<?php

namespace ISC\Tests\WPUnit\Admin\Templates\Sources;

use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test the Images without sources template used on the Image Sources admin page.
 */
class Images_Without_Sources_Template_Test extends WPTestCase {

	/**
	 * Test the template points users to the filtered media library instead of rendering a huge table.
	 */
	public function test_renders_button_to_filtered_media_library() {
		ob_start();
		require ISCPATH . '/admin/templates/sources/images-without-sources.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( 'button button-secondary', $output );
		$this->assertStringContainsString( 'upload.php?mode=list&isc_filter=without_source', $output );
		$this->assertStringNotContainsString( '<table', $output );
		$this->assertStringNotContainsString( 'Image title', $output );
	}
}
