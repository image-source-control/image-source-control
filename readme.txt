=== Image Source Control Lite – Show Image Credits and Captions ===
Contributors: webzunft
Tags: credits, captions, copyrights, attributions, image sources
Requires at least: 5.3
Tested up to: 6.6
Stable tag: 2.26.0
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
* Show an image caption overlay above or below the image
* Embed a complete image credit list with thumbnails on your website

**Frontend Features**

* Display image credits in the content, for image galleries, images added by shortcodes, and featured images
* … see more listed under Premium features below
* Define the layout and position of the caption overlay
* Show the image source fully, or only on click or mouseover
* Attach the Per-page list automatically, by using a shortcode, or with a PHP function
* Display image sources on archive pages
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

**Premium Features**

[Check out all features of Image Source Control](https://imagesourcecontrol.com/?utm_source=wporg&utm_medium=link&utm_campaign=all-features).

* List credits for images outside the content
* Add multiple links to the source string
* Manage image credits for images hosted outside the Media Library
* Handle images without file extensions
* Bulk-edit image copyright information in the media library
* Show the standard picture credit for all images without a selected source
* [Display IPTC copyright metadata](https://imagesourcecontrol.com/blog/iptc-copyright-information-image-caption-wordpress/) in the backend and automatically as a standard source in the frontend
* Show the full text only after a click or on mouseover on the caption overlay
* Choose which data is displayed in the Global List
* List only images with a proper source in the Global List
* Show image sources for Elementor background images, images in Kadence Blocks Galleries, and Kadence Related Content Carousel
* Developer options to show overlay captions for CSS background images
* Support for [background images of the Group block](https://imagesourcecontrol.com/blog/group-block-background-image/)
* Exclude certain images from showing the overlay by adding the `isc-disable-overlay` class
* Unused Images (see below)
* Personal email support

Extended compatibility with Elementor, Avada, WP Bakery, and other page builders
as well as with plugins like Kadence Blocks, Kadence Related Content Carousel, and Lightbox Gallery.

[See Pricing](https://imagesourcecontrol.com/pricing/?utm_source=wporg&utm_medium=link&utm_campaign=pricing).

**Unused Images**

Premium media cleaner features to remove unused images safely.

– Go to _Media > Unused Images_ to see and remove unused images
- Run an additional deep check to see if images are used in widgets, meta fields, or options
- Bulk delete unused images
- Filter the list by various states

[See Pricing](https://imagesourcecontrol.com/pricing/?utm_source=wporg&utm_medium=link&utm_campaign=pricing).

Btw., Image Source Control is a suitable alternative to the discontinued or closed plugins Image Credits, Credit Tracker, or FSM Custom Featured Image Caption.

== Instructions ==

Take a look at the [Image Source Control Documentation](https://imagesourcecontrol.com/documentation/?utm_source=wporg&utm_medium=link&utm_campaign=documentation).

Find a list of missing images sources and other debug tools under _Media > Image sources_

You can choose to display image sources below the post content or as a small caption overlay above your images. Just visit the settings page of the plugin to enable those options.

**Manually included image sources on pages/posts**

You can add the Per-page list manually to pages or posts via the shortcode `[isc_list]` in your content editor or a text widget.

Use `[isc_list id="123"]` to show the list of any post or page.

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
1. Visit _Settings > Image Sources_ for the main settings

See the _Instructions_ section [here](https://wordpress.org/plugins/image-source-control-isc/#description).

== Screenshots ==

1. Display image attribution captions as an overlay above the image
1. Display a list of all images of your site and their sources on a dedicated page
1. Edit image source settings in the Image block
1. Bulk-editing image sources in the Media Library
1. Unused Images – Media Cleaner feature to safely remove unused images
1. Customizing the display of image captions as an overlay
1. Customizing the list of image sources displayed under posts
1. Customizing the global list of image sources
1. Manage image usage licenses

== Changelog ==

= 2.26.0 =

- Feature: (Pro) search for attachment IDs in the content when looking for [unused images](https://imagesourcecontrol.com/features/delete-unused-images/). Enable this deep check feature in the plugin settings.
- Feature: (Pro) load the WordPress caption as the standard image source
- Feature: (Pro) compatibility with the Lightbox Gallery plugin
- Improvement: (Pro) highlight in the deep check for unused images, whether the image URL or the attachment ID was found
- Improvement: enable ISC fields in the Image block of the GenerateBlocks plugin
- Fix: the displayed number of individual unused image files was the same as unused images due to a wrong variable

= 2.25.0 =

- Improvement (Pro): ignore unused images in post revisions. irrelevant options and some post meta entries
- Improvement: block option fields work properly with custom post types
- Improvement: list the number of [unused images](https://imagesourcecontrol.com/features/delete-unused-images/) and image files separately
- Improvement: ignore image URLs above 1000 characters since they could be encoded images and not file paths
- Dev: made Global List thumbnail options translatable
- Dev: set backend-only options to autoload=false

= 2.24.1 =

- Improvement (Pro): catch more background images added by WP Bakery
- Fix: a wrong format of the `isc_post_images` post meta value could cause a PHP error

= 2.24.0 =

- Feature: (Pro) support for background images added by the WP Bakery page builder
- Improvement: allow to remove individual entries from the image-posts and post-images indices on the Tools page
- Improvement: include all page types in the post-image index list, not only posts
- Dev: added the `isc_add_sources_to_content_ignore_post_images_index` filter to allow users to manually ignore the post-images index on all page views in case another plugin or page builder indexes the wrong content
- Dev: speed up the query for images without sources
- Dev: added debug log entries and log parameters

= 2.23.1 =

- Fix: a fatal error prevented the settings page from loading fully when Elementor was enabled
- Fix: (Pro) license activations on subsites on a multisite didn’t work reliably

= 2.23.0 =

- Feature: (Pro) two new overlay behaviors: show the full caption only after a click or on mouseover
- Improvement: rearranged the settings page for more clarity
- Improvement: renamed the "pre-text" option for overlay text to "prefix"
- Improvement: added a button to close the newsletter signup form
- Dev: rewritten the code that renders and handles the settings page

= 2.22.0 =

- Note about WordPress 6.5: The lightbox feature in WordPress core changed significantly and no longer supports image source overlays out of the box. I am working on a solution.
- Improvement: (Pro) removed the hotfix for Elementor’s Image Optimization feature after they fixed the issue on their end
- Improvement: (Pro) added PHP translation files as supported in WP 6.5
- Fix: (Pro) the bulk edit fields could show a wrong default value in the backend for certain options
- Dev: (Pro) added the `isc_unused_images_per_page` filter to adjust the number of items per page in the [Unused Images list](https://imagesourcecontrol.com/features/delete-unused-images/)
- Dev: adjusted the URL sanitization check to allow special characters in image URLs, like "©"
- Dev: prevent a PHP deprecation warning when the `isc_public_global_list_view_path` filter returns an expected value
- Dev: remove cached options in `Standard_Source` class to prevent caching of old options

= 2.21.0 =

- Improvement: (Pro) the bulk edit options are visually more compact now
- Improvement: (Pro) Elementor: disable the Image Optimization module in Elementor when checking images in the whole content. This module is known for conflicting with many plugins and in review by the Elementor team
- Improvement: (Pro) Avada: recognize the `data-preload-img` attribute for Avada background images
- Improvement: (Pro) add a link to some words in the image source, but not to words at the end of the caption
- Improvement: show a feedback form when disabling the plugin
- Fix: (Pro) comma-separated URLs in the bulk edit fields were wrongly sanitized
- Fix: the image source fields in the media edit page disappeared when saving the page
- Fix: labels in the block editor were not translated
- Fix: newsletter subscription returned an error for subscribed users
- Dev: `ISC_Block_Options` threw a PHP warning due to an early hook
- Dev: introduced `ISC\User` to gather user-based helper functions
- Dev: add an output buffer handle to better analyze a conflict with other output buffers

= 2.20.1 =

- Fix: image source fields for image blocks in the site editor threw a JavaScript error
- Dev: resolved a PHP 8.2 encoding warning
- Dev: improved code style in the caption frontend script

= 2.20.0 =

- Improvement: (Pro) the deep check for image usages looks for attachment IDs in options, e.g., to find site logos
- Fix: unused images table layout was shifted for non-English backends
- Dev: added a helper class for utility functions
- Dev: use a custom unserialize function to prevent object injection

= 2.19.0 =

- Feature: (Pro) show caption overlay for [Avada Builder background images](https://imagesourcecontrol.com/documentation/compatibility/#Avada_Builder)
- Improvement: the check for unused images became faster and more reliable by excluding non-images
- Improvement: updated the uninstall script to remove recently added data when the plugin is deleted
- Improvement: (Pro) allow editors to use the bulk edit feature for image sources
- Improvement: (Pro) introduced pagination for the list of unused images
- Improvement: (Pro) bulk selecting enabled for checking unused images and deleting them
- Improvement: (Pro) introduced various filters to the list of unused images
- Fix: a possible empty post ID for the excerpt block caused a PHP warning
- Fix: (Pro) a PHP warning appeared when an unused image was associated with a deleted post
- Dev: introducing the ISC\Unused_Images class with improvements to unused images code
- Dev: (Pro) use WP_List_Table when displaying the Unused Images list

= 2.18.1 =

- Improvement: show a hint about where to edit image sources in the block editor, depending on The Block Options settings
- Fix: the Global List didn’t show any images when _Miscellaneous settings > Standard source > Exclude from lists_ was enabled

= 2.18.0 =

- Feature (Pro): show the IPTC copyright or credit meta information as a standard source. See this [blog post](https://imagesourcecontrol.com/blog/iptc-copyright-information-image-caption-wordpress/)
- Feature (Pro): authors now see the IPTC copyright and credit meta information in the backend and can copy it to the image source field with one click
- Feature (Pro): the standard source appears as a placeholder in the image source field in media list overview, when no individual source is entered
- Fix: pre-select the "custom text" standard source option when the plugin options weren’t saved yet to reflect how this state behaves in the frontend
- Dev: added an autoloader for plugin classes
- Dev: move all features related to the standard source into ISC\Standard_Source
- Dev: replace the `isc_raw_attachment_use_standard_source` and `isc_public_attachment_use_standard_source` with the `isc_use_standard_source_for_attachment` filter
- Dev: added the `isc_can_load` filter to allow developers to disable ISC on certain pages in the frontend
- Dev: added the `isc_force_block_options` filter hook to enable ISC fields in the block options and media modal in the block editor at the same time

= 2.17.1 =

- Improvement: create a unique name for the log file
- Improvement: show the overlay caption in the bottom left in the lightbox (added in WordPress 6.4) where the current position doesn’t show up

= 2.17.0 =

- Improvement: detect images file names generated by DALL·E
- Improvement: (Pro) support for [background images for the Group block](https://imagesourcecontrol.com/blog/group-block-background-image/) in WordPress 6.4

= 2.16.0 =

- Feature (Pro): Unused Images feature to help clean up the media library
- Improvement: combine images without sources and with empty sources in the "Images without sources" list on the Tools page instead of having two separate tables

= 2.15.0 =

- Improvement: (Pro) Support for dynamic background images in Elementor templates, e.g., featured images
- Improvement: (Pro) Disable links in overlays, e.g., when Elementor assigns a background image to `<a>` tags
- Fix: The `<style>` block detection could sometimes cover multiple blocks and add the caption only to the last one
- Dev: Added argument to `render_image_source_string()` that allows to disable links in the caption output