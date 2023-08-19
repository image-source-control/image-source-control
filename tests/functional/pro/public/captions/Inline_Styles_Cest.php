<?php

namespace ISC\Tests\Functional;

/**
 * Test if ISC_Pro_Public::add_captions_for_inline_styles() finds image source information in the "style" attribute of HTML elements and adds captions to them.
 */
class Inline_Styles_Cest
{

    protected $renderer;

    public function _before(\FunctionalTester $I)
    {
        // First, grab the default plugin options.
        $existingOption = $I->grabOptionFromDatabase('isc_options');
        // Enable the overlay and the overlay inline style support
        $existingOption['display_type'] = ['overlay'];
        $existingOption['overlay_included_advanced'] = [
            'inline_style_data',
            'inline_style_show',
        ];
        // Update the option in the database with the new array.
        $I->haveOptionInDatabase('isc_options', $existingOption);

        // load image information
        // image 1
        $I->havePostmetaInDatabase(123, 'isc_image_source', 'Team ISC');
        $I->havePostmetaInDatabase(123, 'isc_image_source_url', 'https://www.imagesourcecontrol.com');
        $I->havePostInDatabase([
            'ID' => 123,
            'post_author' => 1,
            'post_title' => 'Image in style attribute',
            'guid' => 'https://example.com/image-in-style-attribute.jpg',
        ]);

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

    public function test_no_image_found(\FunctionalTester $I)
    {
        $I->havePageInDatabase([
            'post_name' => 'test-page',
            'post_content' => '<div style="background: #fff;">Some Content</div>',
        ]);

        // Go to the page.
        $I->amOnPage('/test-page');
        $I->seeInSource('<div style="background: #fff;">Some Content</div>');
    }

    public function test_style_sources(\FunctionalTester $I)
    {
        $I->havePageInDatabase([
            'post_name' => 'test-page',
            'post_content' => '<div style="background: url(https://example.com/image-in-style-attribute.jpg);">Some Content</div>',
        ]);

        // Go to the page.
        $I->amOnPage('/test-page');
        // see the source information
        $I->see('Team ISC');
        // see the actual HTML output, including the data attribute and the overlay code at the beginning of the DIV container
        $I->seeInSource('<div style="background: url(https://example.com/image-in-style-attribute.jpg);" data-isc-source-text="Quelle: &lt;a href=&quot;https://www.imagesourcecontrol.com&quot; target=&quot;_blank&quot; rel=&quot;nofollow&quot;&gt;Team ISC&lt;/a&gt;" data-isc-images="123"><span class="isc-source-text">Quelle: <a href="https://www.imagesourcecontrol.com" target="_blank" rel="nofollow">Team ISC</a></span>Some Content</div>');
    }

    /**
     * Test that multiple url() information in the style attribute return only the first URL found.
     */
    public function test_multiple_urls(\FunctionalTester $I)
    {
        $I->havePageInDatabase([
            'post_name' => 'test-page',
            'post_content' => '<div style="background: url(https://example.com/image-in-style-attribute.jpg); cursor: background-image: (https://example.com/iteam.png);">Some Content</div>',
        ]);

        // Go to the page.
        $I->amOnPage('/test-page');
        // see the source information
        $I->see('Team ISC');
        // see the actual HTML output, including the data attribute and the overlay code at the beginning of the DIV container
        $I->seeInSource('<div style="background: url(https://example.com/image-in-style-attribute.jpg); cursor: background-image: (https://example.com/iteam.png);" data-isc-source-text="Quelle: &lt;a href=&quot;https://www.imagesourcecontrol.com&quot; target=&quot;_blank&quot; rel=&quot;nofollow&quot;&gt;Team ISC&lt;/a&gt;" data-isc-images="123"><span class="isc-source-text">Quelle: <a href="https://www.imagesourcecontrol.com" target="_blank" rel="nofollow">Team ISC</a></span>Some Content</div>');
    }

    /**
     * Test multiple tags with style attributes
     */
    public function test_multiple_tags_with_styles(\FunctionalTester $I)
    {
        $I->havePageInDatabase([
            'post_name' => 'test-page',
            'post_content' => <<<EOD
<div style="background: url(https://example.com/image-in-style-attribute.jpg);">First Container</div>
<div style="color: #fff;">Seconds Container</div>
<p style="background: url(https://example.com/iteam.png);">Paragraph</p>
EOD,
        ]);

        // Go to the page.
        $I->amOnPage('/test-page');
        // see the source information
        $I->see('Team ISC');
        $I->see('iTeam');
        // see the actual HTML output, including the data attribute and the overlay code at the beginning of the DIV container
        $I->seeInSource( <<<EOD
<div style="background: url(https://example.com/image-in-style-attribute.jpg);" data-isc-source-text="Quelle: &lt;a href=&quot;https://www.imagesourcecontrol.com&quot; target=&quot;_blank&quot; rel=&quot;nofollow&quot;&gt;Team ISC&lt;/a&gt;" data-isc-images="123"><span class="isc-source-text">Quelle: <a href="https://www.imagesourcecontrol.com" target="_blank" rel="nofollow">Team ISC</a></span>First Container</div>
<div style="color: #fff;">Seconds Container</div>
<p style="background: url(https://example.com/iteam.png);" data-isc-source-text="Quelle: &lt;a href=&quot;https://www.iteam.com&quot; target=&quot;_blank&quot; rel=&quot;nofollow&quot;&gt;iTeam&lt;/a&gt;" data-isc-images="33"><span class="isc-source-text">Quelle: <a href="https://www.iteam.com" target="_blank" rel="nofollow">iTeam</a></span>Paragraph</p>
EOD
        );
    }
}