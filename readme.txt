=== Plugin Name ===
Contributors: webzunft
Tags: image, images, picture, picture source, image source, mediathek
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 1.2.0.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The Image Source Control saves the source of an image, lists them and warns if it is missing.

== Description ==

Did you ever forget to add the source to an image file in the frontend and the lawyer of the copyright holder knocked on your door?

Image Source Control (ISC) helps to prevent this situation.

**Features**

* adds an extra field (custom field) to mediathek to include the image source into
* lets you mark an image to belong to the posts author
* lists images with missing sources in the backend
* shortcode to list the sources in the editor
* shortcode to list all images in the blog as a paginated list
* function to include sources of a post in templates

**Localization**

English, German

**Instructions**

Find instructions under *Other Notes* or at the <a href="http://webgilde.com/en/image-source-control/image-source-control-manual/">image source control website</a>.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `wg-image-source-control`-folder to the `/wp-content/plugins/` directory
1. Activate ISC through the 'Plugins' menu in WordPress

== Screenshots ==

1. Missing sources page to list images with missing source.
2. Two extra fields from ISC in mediathek

== Changelog ==

= 1.2.0.3 =

* [fixed] solid fix for an issue occuring on updating to version 1.2

= 1.2.0.2 =

* broken fix, DON'T use this version

= 1.2.0.1 =

* really hot fix for problems some of you experienced after updating to version 1.2.

= 1.2 =

* [feature] added an option panel under settings to customize most frontend texts
* [feature] show image thumbnails in the list of all images
* [feature] you can add a backlink to the authors page under the image list (optional)
* [feature] you can now display the real posts author name when an image is marked as "by the author"
* [feature] link to posts from the image list
* [fixed] added a more flexible post-image connection to list all posts with a specific image in the list with all images
* [fixed] image list: don't list images that are currently not used in any post
* [fixed] no need to initially run the image index after first installing the plugin
* [l10n] updated German localization

= 1.1.3 =

* [feature] shortcode to list all images conntected to all posts and pages as a paginated list
* [fixed] content filter now finds images that have been edited (like rotated)
* [fixed] checking for missing key to prevent error message
* [fixed] added link to plugin homepage with more details

= 1.1.2.1 =

* [fixed] wrong user level check caused shortcode to not work for normal visitors

= 1.1.2 =
* [fixed] wrong version number in main file so wordpress.org didn't inform about updates
* [fixed] some minor coding standard issues
* [fixed] small text for donate link
* [fixed] plugin url
* [removed] donate link (will come back after 10.000 downloads ;)

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

Also have a look at the <a href="http://webgilde.com/en/image-source-control/image-source-control-manual/">image source control manual</a>.

Find a list of images with missing sources under _Mediathek > Missing sources_

**image sources on pages/posts**

You can add the image source list to pages or post via the shortcode [isc_list] in your content editor. You can use `[isc_list id="123]` to show the list of any post or page.

You can also add the list with the function `isc_list()` within the loop in your template files. Use `isc_list( $post_id )` to show the image source list outside the loop.

You should also check first if the function exists before using it:
`<?php if( function_exists('isc_list') ) { isc_list(); } ?>`

**list all image sources**

You can add a paginated list with ALL attachments and sources to a post or page using the shortcode [isc_list_all]. Use `[isc_list per_page="25]` to show only a limited number of images per page.

The plugin searches your post content and thumbnail for images (attachments) and lists them, if you included at least the image source or marked it as your own image.