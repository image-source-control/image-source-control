<?php

namespace ISC\Tests\Functional;

/**
 * Test if ISC_Pro_Public::add_captions_to_style_blocks() finds image source information in <style> blocks and adds captions to them.
 */
class Style_Blocks_Cest {

	protected $renderer;

	public function _before( \FunctionalTester $I ) {
		// First, grab the default plugin options.
		$existingOption = $I->grabOptionFromDatabase( 'isc_options' );
		// Enable the overlay and the overlay style support
		$existingOption['display_type']              = [ 'overlay' ];
		$existingOption['overlay_included_advanced'] = [
			'style_block_data',
			'style_block_show',
		];
		// Update the option in the database with the new array.
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		// load image information
		$I->havePostmetaInDatabase( 123, 'isc_image_source', 'Team ISC' );
		$I->havePostmetaInDatabase( 123, 'isc_image_source_url', 'https://www.imagesourcecontrol.com' );
		$I->havePostInDatabase( [
			'ID'          => 123,
			'post_author' => 1,
			'post_title'  => 'Image in style tag',
			'guid'        => 'https://example.com/image-in-style-tag.jpg',
		] );

        // load image information
        // image 2
        $I->havePostmetaInDatabase(33, 'isc_image_source', 'iTeam');
        $I->havePostmetaInDatabase(33, 'isc_image_source_url', 'https://www.iteam.com');
        $I->havePostInDatabase([
            'ID' => 33,
            'post_author' => 1,
            'post_title' => 'iTeam image',
            'guid' => 'https://example.com/iteam.png',
        ]);
	}

	public function test_no_image_found( \FunctionalTester $I ) {
		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => '<style>.some-element { background: #fff; }</style>',
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		$I->seeInSource( '<style>.some-element { background: #fff; }</style>' );
	}

	public function test_style_sources( \FunctionalTester $I ) {
		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => '<style>.some-element { background: url("https://example.com/image-in-style-tag.jpg"); }</style>',
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		// see the source information
		$I->see( 'Team ISC' );
		// see the actual HTML output, including the data attribute and the overlay code following the STYLE tag
		$I->seeInSource( '<style data-isc-source-text="Quelle: &lt;a href=&quot;https://www.imagesourcecontrol.com&quot; target=&quot;_blank&quot; rel=&quot;nofollow&quot;&gt;Team ISC&lt;/a&gt;">.some-element { background: url("https://example.com/image-in-style-tag.jpg"); }</style><span class="isc-source-text">Quelle: <a href="https://www.imagesourcecontrol.com" target="_blank" rel="nofollow">Team ISC</a></span>' );
	}

    /**
     * Test multiple style tags with two creating a match
     */
    public function test_multiple_style_tags(\FunctionalTester $I)
    {
        $I->havePageInDatabase([
            'post_name' => 'test-page',
            'post_content' => <<<EOD
<style id='style-with-id'>
.some-irrelevant-element {
	background: #32373c;
	color: #ffffff;
}
</style>
<style>
.elementor-287 .elementor-element.elementor-element-2b65d67:not(.elementor-motion-effects-element-type-background), .elementor-287 .elementor-element.elementor-element-2b65d67 > .elementor-motion-effects-container > .elementor-motion-effects-layer{background-image:var(--e-bg-lazyload-loaded);--e-bg-lazyload:url("https://example.com/image-in-style-tag.jpg");}
</style>
<style id='some-other id'>
.some-selector{background:url("https://example.com/iteam.png");}
</style>
EOD,
        ]);

        // Go to the page.
        $I->amOnPage('/test-page');
        // see the source information
        $I->see('Team ISC');
        $I->see('iTeam');
        // see the actual HTML output, including the data attribute and the overlay code at the beginning of the DIV container
        $I->seeInSource( <<<EOD
<style id='style-with-id'>
.some-irrelevant-element {
	background: #32373c;
	color: #ffffff;
}
</style>
<style data-isc-source-text="Quelle: &lt;a href=&quot;https://www.imagesourcecontrol.com&quot; target=&quot;_blank&quot; rel=&quot;nofollow&quot;&gt;Team ISC&lt;/a&gt;">
.elementor-287 .elementor-element.elementor-element-2b65d67:not(.elementor-motion-effects-element-type-background), .elementor-287 .elementor-element.elementor-element-2b65d67 > .elementor-motion-effects-container > .elementor-motion-effects-layer{background-image:var(--e-bg-lazyload-loaded);--e-bg-lazyload:url("https://example.com/image-in-style-tag.jpg");}
</style><span class="isc-source-text">Quelle: <a href="https://www.imagesourcecontrol.com" target="_blank" rel="nofollow">Team ISC</a></span>
<style data-isc-source-text="Quelle: &lt;a href=&quot;https://www.iteam.com&quot; target=&quot;_blank&quot; rel=&quot;nofollow&quot;&gt;iTeam&lt;/a&gt;" id='some-other id'>
.some-selector{background:url("https://example.com/iteam.png");}
</style><span class="isc-source-text">Quelle: <a href="https://www.iteam.com" target="_blank" rel="nofollow">iTeam</a></span>
EOD
        );
    }
}
