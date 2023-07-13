=== Image Source Control Lite – Show Image Credits and Captions ===
Contributors: webzunft
Tags: images, credits, captions, copyrights, attributions, photos, pictures, sources, bildquellen, bilder, fotos, bildunterschriften
Requires at least: 5.3
Tested up to: 6.2
Stable tag: 2.12.0
Requires PHP: 7.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Show image credits, image captions, and copyrights. Manage image sources and warn if they are missing. The original plugin since 2012.

== Description ==

Are you concerned about being held liable for violating copyright law and would like to start crediting owners properly?

Do you want to give back to photographers and illustrators by adding image credits, so they are rightfully attributed?

Or are you a creator yourself and want to show information on the picture licenses for your image gallery under which publishers can use or purchase your work?

Image Source Control is your go-to solution when it comes to handling copyright-protected photos and illustrations.

**Image Credit layouts**

Choose between different credit displays:

* List all image sources below the content of a specific page or place the list manually
* Show an image caption overlay above the image
* Embed a complete image credit list with thumbnails on your website

**Frontend Features**

* Display image credits in the content, for image galleries, images added by shortcodes, and featured images
* Define the layout and position of those attributions
* Attach the Per-page list automatically, by using a shortcode, or with a PHP function
* Display image sources on archive pages
* Block editor: detects image sources for the image, cover image, and gallery blocks
* Link to the copyright holder and include a link to the image license

**Backend Features**

* Add credits for any image file uploaded to the Media library
* Dedicated image source fields for the following blocks: Image, Cover Image, Featured Image, Media & Text
* Quickly assign a centrally defined source to any image and choose three options: hide image sources for these images, show a specific source (e.g., your name), or the uploader’s name
* Warn about missing image sources
* Manage, display, and link available licenses

**Featured Image Caption**

ISC Lite works for Featured Images. By default, you will see the image credits options in the media library and the featured image options in the block editor.

The featured image caption shows in the Per-page list with all other image sources on the page.

Check out the premium features to display the image caption overlay for featured images.

**Premium-Features**

[Check out all features of Image Source Control](https://imagesourcecontrol.com/?utm_source=wporg&utm_medium=link&utm_campaign=all-features).

* List credits for images outside of the content
* Multiple links in the source string
* Manage image credits for images hosted outside of the media library
* Handle images without file extensions
* Bulk-edit image copyright information in the media library
* Show the standard picture credit for all images without a selected source
* Choose which data is displayed in the Global List
* List only images with a proper source in the Global List
* Show image sources for Elementor background images
* Personal email support

[See Pricing](https://imagesourcecontrol.com/pricing/?utm_source=wporg&utm_medium=link&utm_campaign=pricing).

== Instructions ==

Take a look at the [Image Source Control Documentation](https://imagesourcecontrol.com/documentation/?utm_source=wporg&utm_medium=link&utm_campaign=documentation).

Find a list of missing images sources and other debug tools under _Media > Image sources_

**Automatic image sources**

You can choose to display image sources automatically below the post content or as a small caption overlay above your images. Just visit the settings page of the plugin to enable those options.

**Manually included image sources on pages/posts**

You can add the Per-page list manually to pages or posts via the shortcode `[isc_list]` in your content editor or a text widget.

Use `[isc_list id="123]` to show the list of any post or page.

Use the PHP code `<?php if( function_exists('isc_list') ) { isc_list(); } ?>` within your template files.

**List all image sources**

You can add a paginated list with ALL attachments and sources attached to posts and pages—the Global list—using the shortcode `[isc_list_all]`.

Use `[isc_list_all per_page="25"]` to show only a limited number of images per page.

Use `[isc_list_all included="all"]` to show all attachments in the list, including those not explicitly attached to a post.

The plugin searches your post content and thumbnail for images (attachments) and lists them if you included at least the image source or marked it to use the default image source.

**Remove “nofollow” from all source links**

In order to remove “nofollow” from source links, follow the [instructions in our documentation](https://imagesourcecontrol.com/documentation/#remove-nofollow-from-all-source-links).

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `image-source-control-isc.zip` through the 'Plugin' menu in your WordPress backend
1. Activate the plugin
1. Visit _Settings > Image Control_ for the main settings

See the _Instructions_ section [here](https://wordpress.org/plugins/image-source-control-isc/#description).

== Screenshots ==

1. edit image source settings in the Image block
1. display a list with all images of your site and their sources
1. display image source within the image
1. basic settings to customize how to display image sources
1. settings to manage image source licenses

== Changelog ==

= untagged =

- Feature: (Pro) set the `isc-disable-overlay` class on specific images to prevent the overlay from showing up
- Improvement: find alignment information classes also, when multiple classes are given in the `<figure>` tag
- Improvement: added the `isc_extract_images_from_html` filter to manipulate images that use captions
- Fix: the page content was not put together correctly when `isc_stop_overlay` was added manually to it and no images were found

= 2.12.0 =

- Feature: (Pro) link different terms in the source string to different URLs
- Improvement: update missing image sources warning when an images was uploaded or deleted

= 2.11.0 =

- Improvement: (Pro) support background images of Elementor’s "Call-To-Action" widget
- Improvement: limit queries for missing sources to 100 to prevent the check from causing a timeout on sites with a lot of images
- Improvement: the source options show up in the media library overlay on post edit pages when Block options support is disabled

= 2.10.0 =

- Improvement: remove ISC fields from media frame when it is not loaded from the media library page to prevent confusions
- Improvement: remove translation for the brand ”Elementor”
- Fix: Debug log was enabled when the option was disabled and not used

= 2.9.0 =

- Improvement: (Pro) manage and display the image source for Elementor background images
- Improvement: (Pro) choose which data is displayed in the Global List
- Improvement: move the thumbnail options for global lists into the Included data settings
- Improvement: use the new Block Options setting to disable ISC fields for image blocks in the editor
- Improvement: route all calls to update_post_meta through the same internal function for better debugging
- Improvement: introduced the `isc_update_post_meta` action to hook into post meta updates
- Improvement: the new `isc_filter_image_ids_from_content` filter allows custom image ID filtering from the content

= 2.8.0 =

- Feature: (Pro) manage images without a file extension
- Feature: (Pro) an option to list only images with a proper source in the Global List
- Feature: added ISC fields to Cover blocks using featured image.
- Feature: show feature image source string in the post excerpt block when the Insert below excerpts option is enabled
- Feature: the Global List settings show options for which images are included and how many per page. The appropriate shortcode attributes override them, if set.
- Improvement: prevent losing focus on attachment fields in the media modal when saving data
- Improvement: check overlay positions after the site is fully loaded to correct misplaced overlays
- Improvement: show a default thumbnail image in the global list, when WordPress didn’t create a thumbnail
- Improvement: show a default thumbnail in WP Admin for [external images](https://imagesourcecontrol.com/documentation/#4-4-2-additional-images)
- Improvement: added CSS rule to the Overlay for better compatibility with Divi theme
- Improvement: the new `isc_public_excluded_post_ids` filter excludes posts from running ISC in the frontend
- Improvement: the new `isc_public_global_list_view_path` filter allows custom views for the Global List
- Fix: specified style for overlay links to not also style other ISC related links

= 2.7.0 =

- Feature: added ISC fields to Media & Text block
- Feature: added ISC fields to the post’s Featured Image block
- Improvement: load ISC field data in the block editor only for images in the currently edited page
- Improvement: when the source of the featured image is displayed below post excerpts, it does no longer use the pre-text defined in the Overlay options. It now says "Featured image" by default and can be changed using the `isc_featured_image_source_pre_text` filter
- Improvement: set the color for the source link in overlays to white (#fff) to be better visible by default
- Improvement: prevent line breaks for source links in overlays for the Featured Image block since WordPress 6.0
- Improvement: place the overlay correctly on the Cover block
- Improvement: show a link to disable the warning about missing images
- Fix: plugin options could miss default values on new installations

= 2.6.0 =

- Improvement: add FAL and GFDL to available licenses (clear the option to reset the list)
- Fix: Prevent premium options from becoming selectable when the [Premium version](https://imagesourcecontrol.com/?utm_source=wporg&utm_medium=link&utm_campaign=changelog-2-6-0) is missing
- Dev: rewrite WP Admin check for ISC-related pages

= 2.5.0 =

[Check out the new Premium Features](https://imagesourcecontrol.com/?utm_source=wporg&utm_medium=link&utm_campaign=changelog-2-5-0)

- Improvement: Require at least PHP 7.0
- Improvement: Added branded header with relevant links
- Improvement: Rename some methods for more clarity
- Improvement: Warn if one tries to enable multiple versions of ISC at the same time
- Fix: Make some strings translatable

= 2.4.0 =

- Feature: ISC Storage to prevent SQL queries in the frontend, [#131](https://github.com/image-source-control/image-source-control/issues/131), [#132](https://github.com/image-source-control/image-source-control/issues/133),
- Feature: List images that are not part of WP Media under Media > Image Sources
- Improvement: show image source lists and overlay strings on pages generated by the AMPforWP plugin and the reader mode in the official AMP plugin
- Improvement: add `isc_filter_image_ids_tags` filter to search for more than IMG tags
- Improvement: add $attachment_id as parameter to filters: `isc_raw_attachment_get_license`, `isc_raw_attachment_get_source`, `isc_raw_attachment_get_source_url`
- Improvement: accept line breaks in img and a tags when positioning the overlay
- Fix: PHP error in the widget block editor and Customizer

= 2.3.1 =

- fixed AMP validation errors
- prevent contributors from updating unrelated post meta, props to wpscan

= 2.3.0 =

- added `isc_source_list_line` and `isc_source_list` filter hooks to allow developers to manipulate the source list output
- added `isc_public_source_url_html` filter to manipulate the source link HTML
- allow source injection into content outside the loop when using Oxygen page builder

= 2.2.1 =

- split admin scripts to load only those that are relevant for a given page
- fixed script issue on Image Sources debug page

= 2.2.0 =

- added compatibility with the [upcoming Pro version](https://imagesourcecontrol.com/pro/)
- Pro: show an overlay for images outside of the main content (e.g., feature images and some page builder)
- add option to strip overlay output from any markup and use hooks to define own styles
- find sources for images after moving the site to another URL

= 2.1.1 =

- only load ISC-related scripts on the plugin’s admin pages
- compatibility with line breaks between link and image tags when placing the overlay
- prevent adding the overlay multiple times to images that appear more than once on a page
- fixed overlay text showing up in automatically created excerpts
- fixed jQuery shorthand warnings in the backend
- fixed source overlays for images with links

= 2.1.0 =

- rewritten jQuery to vanilla JavaScript in the frontend code
- only index the images of posts in the main loop to prevent getting it from filtered content

= 2.0.0 =

- the full source list is now updated when a post is visited for the first time not after being saved
- the "own image" option is now "standard source" to better represent its purpose and possibilities
- improved settings page visually and for more clarity
- use span instead of div container for source overlays
- rewrite of the block options to make them more stable
- introducing a model class
- introduced debug option
- introduced option to reindex image-post relations again
- introduced `isc_sources_list_override_output` filter to allow overriding the output of a source list
- introduced `isc_source_list_empty_output` filter to allow output when a list of sources is empty
- fixed showing the sources list on single pages when the "list below content" was disabled while the same option for archive pages was
- fixed indexing post content for images when the post is saved in the block editor
- fixed infinite loops breaking autosave feature in the block editor
- various fixes to debug tables
- removed unneeded code to recognize the gallery shortcode in classic editor
- removed broken PHP code causing an issue when using the `isc_images_in_posts_simple` filter
- removed option to add a link to the ISC website in the frontend

= 1.10.4 =

* fixed block editor files being loaded in the frontend

= 1.10.3 =

* fixed JavaScript bug in block editor with WordPress 5.3

= 1.10.2 =

* removed CSS class which was set automatically in the image block

= 1.10.1 =

* fix for block translations

= 1.10 =

* added image source settings to Image block

= 1.9.7 =

* changed `licence` string to `license` to match en_US language base
* extended list of copyright licenses

= 1.9.6 =

* fixed issue using [isc_list] without overlays

= 1.9.5 =

* removed additional output introduced in 1.9.3

= 1.9.4 =

* fixed index error

= 1.9.3 =

* prevent image source overlay on full image source list and below
* optimized query of missing-sources check
* only check for missing sources when an image was uploaded or once every 24 hours

= 1.9.2 =

* prevent infinite loop for posts with automatic image source lists where images are not index yet
* moved German translation to wordpress.org

= 1.9.1 =

* delayed displaying source overlay by 100 ms to give images a chance to load their height
* fixed wrong height of the overlay being used
* fixed missing textdomain code to allow translations added through wordpress.org

= 1.9 =

THIS UPDATE CONTAINS SOME BASIC CHANGES ON HOW IMAGES ARE DETECTED IN THE CONTENT. PLEASE TEST IT.

Please [reach out](https://wordpress.org/support/plugin/image-source-control-isc) in case you are suddenly missing any image sources.

* rewritten the way how images are detected in the content of the post or page
* replaced use of image url with attachment ID where possible to lift some heavy loads
* fix for image names including dimensions (e.g., 300x250)
* works with version 2.4.0 of Gutenberg plugin
* place overlay correctly even when page continues to shift after being loaded
* load public JavaScript in footer by default
* prevent saving ISC post meta information on non-public post types

= 1.8.11.2 =

* fixed align value not being understood correctly

= 1.8.11.1 =

* fixed missing index issue
* call parent class in admin class constructor

= 1.8.11 =

* cleanup
* removed log spam introduced with 1.8.10
* prevent SQL injection through crafted img src attributes

= 1.8.10.1 =

* hotfix for php below 5.3

= 1.8.10 =

* don’t list images attached to non-public posts in the full image list, when only visible images should be displayed, thanks to heiglandreas
* added fallback to read images from galleries
* added `isc_get_image_by_url_query` filter
* fixed image list being empty due to autosave
* read src from attribute according to dom documentation, thanks to heiglandreas
* tested with WordPress 4.4 beta 4

= 1.8.9 =

* rather use than error-log query

= 1.8.8 =

* search for image urls regardless of their used protocol (http or https)

= 1.8.6 | 1.8.7 =

* removed duplicate post links on full source list

= 1.8.5 =

* fixed deprecated sanitize_url()

= 1.8.4 =

* [feature] added option to display all images in the full list, not just those visible in posts
* [fixed] default author text not showing up

= 1.8.3 =

* [fixed] saving pre text for source overlay, thanks to maler.whick

= 1.8.2 =

* [feature] show image sources for changed images
* [feature] show image sources for images with query parameters

= 1.8.1 =

* [fixed] set default value for new option to prevent error message

= 1.8 =

* [feature] display image sources on archive pages (see settings)
* [feature] use `isc_thumbnail_source()` to display thumbnail source in templates
* [feature] added image sources for galleries and other shortcodes
* updated German translation

= 1.7.3 =

* [fixed] bug on ajax calls preventing the source fields to show up on ajax called pages in the dashboard
* [fixed] bug on multisite update
* [optimized] don’t hide setting boxes
* [optimized] finished moving all publically needed function to its own class

= 1.7.2 =

* [fixed] error message shown when list type settings was empty or unsaved
* [fixed] source overlay showing if no image source was set for it
* [optimized] trim source input

= 1.7.1 =

* [fixed] source list function referring to the wrong plugin class

= 1.7.0 =

* [optimized] manage different source display types on top of settings page
* [optimized] renamed settings page to "Image Sources"
* [removed] hiding source list elements in the frontend is no longer possible
* [fixed] show source list not only below posts, but every other post type
* [fixed] removed screenshots from main plugin files
