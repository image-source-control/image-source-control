=== Plugin Name ===
Contributors: webzunft
# Donate link: http://example.com/
Tags: image, images, picture, picture source, image source, mediathek
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 1.1.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The Image Source Control saves the source of an image, lists them and warns if it is missing.

== Description ==

Did you ever forget to add the source to an image file in the frontend and the lawer of the copyright holder knocked on your door?

Image Source Control (ISC) helps to prevent this situation.

**Features**

* adds an extra field (custom field) to mediathek to include the image source into
* lets you mark an image as your own
* lists images with missing sources
* shortcode to include the sources in content fields
* function to include sources of a post in templates

**Localization**

English, German

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `wg-image-source-control`-folder to the `/wp-content/plugins/` directory
1. Activate ISC through the 'Plugins' menu in WordPress

== Screenshots ==

1. Missing sources page to list images with missing source.
2. Two extra fields from ISC in mediathek

== Changelog ==

= in development =

= 1.1.1 =
* [fixed] javascript file is now being loaded and missing fields can be added
* [feature] added button to reload the missing sources page after fields have been added
* [locale] updated German translation

= 1.1 =
* [fixed] show image sources under the post
* [fixed] display "checked" attribute in frontend
* [fixed] don't display image titles in image source list without a source
* [feature] show sources for all images visible in the post content, not the post gallery
* [feature] automatically load image source list first time a post is displayed after plugin installation

= 1.0 =
* initial submittion
* [feature] Added image source fields to mediathek
* [feature] List images with missing sources
* [feature] Shortcode to include the sources in content fields
* [feature] Function to include sources of a post in templates

== Instructions ==

Find a list of images with missing sources under _Mediathek > Missing sources_

You can add the image source list to pages or post via the shortcode [isc_list] in your content editor. You can use `[isc_list id="123]`to show the list of any post or page.

You can also add the list with the function `isc_list()` within the loop in your template files. Use `isc_list( $post_id )` to show the image source list outside the loop.

You should also check first if the function exists before using it:
`<?php if( function_exists('isc_list') ) { isc_list(); } ?>`

The plugin searches your post content and thumbnail for images (attachments) and lists them, if you included at least the image source or marked it as your own image.