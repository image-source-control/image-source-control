=== Image Source Control Lite – Show Image Credits and Captions ===
Contributors: webzunft
Tags: credits, captions, copyrights, attributions, image sources
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 3.2.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Show image credits, image captions, and copyrights. Manage image sources and warn if they are missing. The original plugin since 2012.

== Description ==

Are you concerned about being held liable for violating copyright law and would like to start crediting owners properly?

Do you want to give back to photographers and illustrators by adding image credits, so they are rightfully attributed?

Or are you a creator yourself and want to show information on the picture licenses for your image gallery under which publishers can use or purchase your work?

Image Source Control is your go-to solution when it comes to **handling copyright-protected photos and delete unused images**.

[Documentation](https://imagesourcecontrol.com/documentation/?utm_source=wporg&utm_medium=link&utm_campaign=wp-linkbar-documentation) | [Support](https://wordpress.org/support/plugin/image-source-control-isc/) | [Premium Features](https://imagesourcecontrol.com/unlock-isc/?utm_source=wporg&utm_medium=link&utm_campaign=wp-linkbar-pro) | [Delete Unused Images](https://imagesourcecontrol.com/features/delete-unused-images/?utm_source=wporg&utm_medium=link&utm_campaign=wp-linkbar-delete)

> This level of personal and competent support deserves more than just five stars.
> Highly recommended!

⭐⭐⭐⭐⭐ by [eunde](https://wordpress.org/support/topic/excellent-plugin-and-absolutely-outstanding-support/)

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
* Enable the features for any files in the media library or for images only
* Filter the media library list by images with or without sources

**Featured Image Caption**

ISC Lite works for Featured Images. By default, you will see the image credits options in the media library and the featured image options in the block editor.

The featured image caption shows in the Per-page list with all other image sources on the page.

Check out the premium features to display the image caption overlay for featured images.

**Premium Features**

[Check out all features of Image Source Control](https://imagesourcecontrol.com/?utm_source=wporg&utm_medium=link&utm_campaign=all-features).

* The Indexer looks for all images in all published content in one go
* List credits for images outside the content
* Add multiple links to the source string
* Manage image credits for images hosted outside the Media Library
* Handle images without file extensions
* Show image usage in the image details and the List view of the media library
* Bulk-edit image copyright information in the media library
* Preview image credits in the media library
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
as well as with plugins like WPML, Kadence Blocks, Kadence Related Content Carousel, and Lightbox Gallery.

[See Pricing](https://imagesourcecontrol.com/pricing/?utm_source=wporg&utm_medium=link&utm_campaign=pricing).

**Unused Images**

Premium media cleaner features to remove unused images safely.

– Go to _Media > Unused Images_ to see and remove unused images
- Run an additional deep check to see if images are used in widgets, meta fields, or options
- Bulk delete unused images
- Filter the list by various states

[See Pricing](https://imagesourcecontrol.com/pricing/?utm_source=wporg&utm_medium=link&utm_campaign=pricing).

Btw., Image Source Control is a suitable alternative to the discontinued or closed plugins Image Credits, [Credit Tracker](https://imagesourcecontrol.com/blog/credit-tracker/?utm_source=wporg&utm_medium=link&utm_campaign=credit-tracker), or FSM Custom Featured Image Caption.

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
1. List image usage in the Media Library (optional column)
1. Unused Images – Media Cleaner feature to safely remove unused images
1. Customizing the display of image captions as an overlay
1. Customizing the list of image sources displayed under posts
1. Customizing the global list of image sources
1. Manage image usage licenses
1. The Indexer searches for all images in published content

== Changelog ==

= 3.2.0 =

- Feature: You can use the option “Images only” to disable features for non-images in the media library, e.g., PDF files
- Improvement: When authors change content, ISC now looks for removed or new images at the next visit of that page in the frontend. This highly improved compatibility with page builders and dynamic content like shortcodes.
- Improvement: When WP_DEBUG is enabled, show a button to list the content of the internal storage on the Tools page
- Improvement: Removes image source output on pages with the Global List on it
- Improvement (Pro): Ignores image URLs in `href` attributes when looking for image sources
- Improvement (Pro): Clears the URL storage when the Indexer runs. This can help with issues when a site was migrated to another URL
- Improvement (Pro): Extends ignored options for unused images
- Improvement (Pro): The column with image sources forms in the Media Library list view only shows if the user has the permission to edit any image information
- Improvement (Pro): The forms to edit image sources in the Media Library list view only show if the user has the permission to edit information for that given image
- Improvement (Pro): The Indexer for Unused Images now works with posts translated by WPML
- Fix: Removes old index information when the last image in a post is removed
- Fix (Pro): Some reserved characters in URLs caused (e.g., `&`) the bulk edit fields for images sources in the media library to be cut off
- Dev: Extracts post meta handling (`isc_image_posts`, `isc_post_images`) into dedicated classes.
- Dev (Pro): Disable image source form fields in the Media Library list view when submitting the filter form to prevent broken URLs. This is related to a compatibility issue caused by a third-party setup
- Dev (Pro): The Indexer now works in batches to prevent timeouts on large sites when Query Monitor is installed
- Dev (Pro): Pages with the Global List shortcode are now ignored by the Indexer.
- Dev: Adds cleanup routines for meta data for deleted and trashed posts.
- Dev: Replace some direct DB calls with WP functions
- Updates German translation

= 3.1.4 =

- Fix: PHP E_ERROR in Media Library List view when screen options are missing

= 3.1.3 =

- Fix: The filter “Images with sources” in the Media Library list view was not working correctly
- Fix: early loaded text domains caused a PHP warning
- Fix: A missed trait caused a warning in PHP 8.2

= 3.1.2 =

- Fix: Indexer not loading due to changed screen ID
- Fix: PHP warning when saving the Global List settings in PHP 8.2

= 3.1.1 =

- Fix: PHP notices for traits in PHP 8.1

= 3.1.0 =

- Improvement: (Pro) Captions are now working by default for image URLs stored outside the `src` attribute, which is often the case when using lazy loading.
- Improvement: (Pro) The indexer now works with cached frontends.
- Fix: (Pro) The IPTC options were disabled when deactivating the Unused Images module

= 3.0.0 =

3.0 rewrites a lot of classes mainly to split features into modules. Developers who used any classes and methods directly should test their code.

- Feature: You can now switch off modules you don’t need (Image Sources, Unused Images)
- Feature: (Pro) Run the [full-content indexer](https://imagesourcecontrol.com/documentation/#unused-images) to identify all images in the content. This improves compatibility with page builders and plugins that add images to the content dynamically and is useful for either Image Sources and Unused Images
- Feature: (Pro) New column with the image source preview to the Media Library list view
- Feature: New filter to list only images without sources in the Media Library list view for quickly adding missing images
- Feature: Added support for AVIF files
- Improvement: The list of images without sources now ignores images that have the standard source set
- Improvement: (Pro) Show the image path in the list of Unused Images
- Fix: Prevented a JavaScript console error in the list view
- Fix: Pages using the Global List shortcode were not counted in the page index stats
- Dev: Deprecated `ISC_Class`

= 2.29.1 =

- Security: Limit the pretext for the caption preview in the backend to text only to prevent XSS attacks with manipulated links that could be executed by admin users
- Fix: When resizing the screen, right-aligned captions sometimes received line breaks. The new calculation prevents this

= 2.29.0 =

- Increased the required WordPress version to 6.0
- Improvement: (Pro) added Swiss and Austrian localizations
- Fix: a warning was thrown in WordPress 6.7 about text domains loaded too early
- Dev: hardened code against the_content being set to `null` by other plugins
- Dev: various code style improvements

== Upgrade Notice ==

= 3.0.0 =

3.0 rewrites a lot of classes mainly to split features into modules. Developers who used any classes and methods directly should test their code.