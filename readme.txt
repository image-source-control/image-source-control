=== Image Source Control Lite – Show Image Credits and Captions ===
Contributors: webzunft
Tags: images, credits, captions, copyrights, media, photos, pictures, sources, bildquellen, bilder, fotos, bildunterschriften
Requires at least: 5.3
Tested up to: 5.9
Stable tag: 2.5.0
Requires PHP: 7.0
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
* Define the layout and position of the image credits
* Attach the Per-page list automatically, by using a shortcode, or with a PHP function
* Display image sources on archive pages
* Block editor: detects image sources for the image, cover image, and gallery blocks
* Link to the copyright holder and include a link to the image license

**Backend Features**

* Add credits for any image file uploaded to the Media library or as an Image or Cover Image block
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
* Manage image credits for images hosted outside of the media library
* Bulk-edit image copyright information in the media library
* Show the standard picture credit for all images without a selected source
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

- Improvement: add FAL and GFDL to available licenses (clear the option to reset the list)
- Improvement: rewrite WP Admin check for ISC-related pages
- Fix: Prevent disabled premium feature from accidentally becoming selectable

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