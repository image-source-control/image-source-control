=== Image Source Control ===
Contributors: webzunft
Tags: image, images, picture, picture source, image source, mediathek, media, caption
Requires at least: 3.5
Tested up to: 4.8
Stable tag: 1.8.11.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The Image Source Control manages image sources, displays them and warns if they are missing.

== Description ==

Did you ever forget to add the source to an image file in the frontend and the lawyer of the copyright holder knocked on your door?

Image Source Control (ISC) helps to prevent this situation.

> <strong>github repository</strong>
> 
> Since I am not able to work on feature requests or fix buxes right now, you can submit them to the github repository.
> I will definitely review them there.
> https://github.com/webgilde/image-source-control

**Image Sources Lists**

You can choose between different image source list types:

* source as image caption/overlay (not compatible with all themes)
* source list below the content
* combined source list of all images

**Frontend Features**

* display sources for images in content, galleries, shortcodes and featured images
* show image source directly in the image (not working with all images and themes)
* include a list with all images and their sources of the current page/post
* include a list with all images and their sources with all images or only those included in posts
* attach lists to automatically or using shortcodes or template functions
* display image sources on archive pages

**Backend Features**

* manage image source within media dashboard
* mark an image to belong to the uploader
* lists images with missing sources in the backend
* warnings, if image source is missing
* link sources to any url
* manage, display and link licences

**Localization**

English, German

**Instructions**

Find instructions under *Other Notes* or at the [image source control website](http://webgilde.com/en/image-source-control/image-source-control-manual/).

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `wg-image-source-control`-folder to the `/wp-content/plugins/` directory
1. Activate ISC through the 'Plugins' menu in WordPress
1. Visit _Settings > Image Control_ to set up the plugin

== Screenshots ==

1. display a list with all images of your site and their sources
1. display image source within the image
1. some of the settings to customize how to display image sources
1. added two new fields to media library

== Changelog ==

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

Important: I tried to move old settings to new ones, but please check your settings and source display after updating.

= 1.6.1 =

* [feature] list image sources at the bottom of posts and pages without coding
* [feature] debug modus to check the relation between images and image posts

= 1.6 =

Some major change under the hood. On blogs with a lot of posts and images the activation crashed due to auto index of all meta fields.
If everything works as expected you might not notice a change. There is only a second list on the missing image page.
Read [this post](http://webgilde.com/en/image-source-control-1-6/) to learn more about it.

= 1.5 =

* [feature] added function to get single image source from within templates
* [feature] link the image source to a url; added url field to image source input fields
* [optimized] using just a single function to create the source string whereever needed (fixes missing licence on full image lists)

= 1.4.3 =

* [feature] hide sources caption for your own images, if option to hide sources on own images is enabled

= 1.4.2 =

* [bugfix] setting default meta values if isc functions are directly called
* [bugfix] minor error when image own checkbox is not checked
* [bugfix] don’t link to unpublished posts from the image source list
* [bugfix] check if `mb_convert_encoding()` function exists before using it

= 1.4.1 =

* [bugfix] fixing js issue breaking save and preview function

= 1.4 =

* [feature] added css classes to image lists for better css styling
* [feature] added option to hide own images from image sources lists
* [l10n] updated German translation

= 1.3.6 =

* renamed post.php.js to post.js to avoid conflicts with some (broken) caching plugin rewrites

= 1.3.5 =

* [feature] added hooks to enable developers to add their own images to the image source list – more information in the [manual](http://webgilde.com/en/image-source-control/image-source-control-manual/)
* [feature] added image licences
* updated settings page layout so it works WordPress 3.8 RC1
* [l10n] updated German translation

= 1.3.4 =

* [bugfix] fixed the problem when the plugin is used with wpdirauth plugin.

= 1.3.3 =

* [bugfix] fixed problems with special characters like German umlauts (äöü) in file names
* added .jpeg to allowed image extensions

= 1.3.2 =

* added missing files

= 1.3.1 =

* [bugfix] list featured images with the 'isc_list_all' shortcode; please resave posts with featured images to see them in the list

= 1.3.0 =

* [feature] hide the image source list under the post/page (default: visible)
* [feature] use uploader as the image author, not the posts author
* [feature] warnings, if image is saved without image source
* [feature] show image source directly within the image; you can choose the position
* [fixed] update issues

= 1.2.0.3 =

* [fixed] solid fix for an issue occurring on updating to version 1.2

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

Find a list of images with missing sources under _Media > Missing sources_

**automatic image sources**

You can choose to display image sources automatically below the post content or as a small overlay above your images. Just visit the settings page of the plugin to enable those options.

**manually included image sources on pages/posts**

You can add the image source list manually to pages or post via the shortcode [isc_list] in your content editor. You can use `[isc_list id="123]` to show the list of any post or page.

You can also add the list with the function `isc_list()` within the loop in your template files. Use `isc_list( $post_id )` to show the image source list outside the loop.

You should also check first if the function exists before using it:
`<?php if( function_exists('isc_list') ) { isc_list(); } ?>`

**list all image sources**

You can add a paginated list with ALL attachments and sources to a post or page using the shortcode [isc_list_all]. Use `[isc_list_all per_page="25"]` to show only a limited number of images per page.

The plugin searches your post content and thumbnail for images (attachments) and lists them, if you included at least the image source or marked it as your own image.
