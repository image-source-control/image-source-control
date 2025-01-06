=== Image Source Control Lite – Show Image Credits and Captions ===
Contributors: webzunft
Tags: credits, captions, copyrights, attributions, image sources
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 2.29.1
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
* Show image usage in the image details and the List view of the media library
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
1. List image usage in the Media Library (optional column)
1. Unused Images – Media Cleaner feature to safely remove unused images
1. Customizing the display of image captions as an overlay
1. Customizing the list of image sources displayed under posts
1. Customizing the global list of image sources
1. Manage image usage licenses

== Changelog ==

= 2.29.1 =

- Security: Limit the pretext for the caption preview in the backend to text only to prevent XSS attacks with manipulated links that could be executed by admin users
- Fix: When resizing the screen, right-aligned captions sometimes received line breaks. The new calculation prevents this

= 2.29.0 =

- Increased the required WordPress version to 6.0
- Improvement: (Pro) added Swiss and Austrian localizations
- Fix: a warning was thrown in WordPress 6.7 about text domains loaded too early
- Dev: hardened code against the_content being set to `null` by other plugins
- Dev: various code style improvements

= 2.28.2 =

- Fix: (Pro) prevent the caption overlay to always show when it is chosen to show for all images while the main option is disabled

= 2.28.1 =

- Security: admins could execute JavaScript in a manipulated URL in the backend

= 2.28.0 =

- Feature: support for WPML to [translate image sources and plugin options](https://imagesourcecontrol.com/blog/translating-image-captions-with-wpml/)
- Improvement: (Pro) the Global List and Per-page list list non-standard images (e.g., `img` tags without `src` attribute) if they would also show an overlay
- Dev: moved the code that looks for post-image relations into `ISC\Indexer`
- Dev: use the `isc_image_posts_meta_limit` filter to limit the number of posts associated with a given image; default = 10

= 2.27.0 =

- Increased the required PHP version to 7.4
- Feature: (Pro) show a list of image appearances and usages in the media library
- Feature: (Pro) added an optional column with appearances in the List view of the media library
- Dev: (Pro): use the `isc_pro_public_custom_attribute_processors` filter to process non-standard HTML containing image URLs
- Dev: (Pro) show the image source if multiple HTML tags have the same `data-isc-images` attribute
- Fix: a PHP notice was thrown for `img` tags without a `src` attribute

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

== Upgrade Notice ==

= 2.29.1 =

Security: Limit the pretext for the caption preview in the backend to text only to prevent XSS attacks with manipulated links that could be executed by admin users

= 2.28.1 =

Security: admins could execute JavaScript in a manipulated URL in the backend

= 2.28.0 =

Support for WPML. Show more images in the Global and Per-post List.