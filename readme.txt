=== Image Source Control ===
Contributors: webzunft
Tags: image, images, picture, picture source, image source, mediathek, media, caption, copyright
Requires at least: 3.5
Tested up to: 5.0
Stable tag: 1.9.6
Requires PHP: 5.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The Image Source Control manages image sources, displays them and warns if they are missing.

== Description ==

Did you ever forget to add the source to an image file in the frontend and the lawyer of the copyright holder knocked on your door?

Image Source Control (ISC) helps to prevent this situation.

> <strong>github repository</strong>
> 
> I might not always be able to work on feature requests or fix buxes, but you can submit them to the github repository.
> I will definitely review them there.
> https://github.com/webgilde/image-source-control

For changes in WordPress 5.0 and the block editor see [this post](https://wordpress.org/support/topic/gutenberg-support-27/).

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
* attach lists be the content automatically or using shortcodes or template functions
* display image sources on archive pages

**Backend Features**

* manage image source within media dashboard
* mark an image to belong to the uploader
* lists images with missing sources in the backend
* warnings, if image source is missing
* link sources to any url
* manage, display and link licenses

**Localization**

English, German

== Instructions ==

Also have a look at the [image source control website](http://webgilde.com/en/image-source-control/image-source-control-manual/).

Find a list of images with missing sources under _Media > Missing sources_

**automatic image sources**

You can choose to display image sources automatically below the post content or as a small overlay above your images. Just visit the settings page of the plugin to enable those options.

**manually included image sources on pages/posts**

You can add the image source list manually to pages or post via the shortcode `[isc_list]` in your content editor. You can use `[isc_list id="123]` to show the list of any post or page.

You can also add the list with the function `isc_list()` within the loop in your template files. Use `isc_list( $post_id )` to show the image source list outside the loop.

You should also check first if the function exists before using it:
`<?php if( function_exists('isc_list') ) { isc_list(); } ?>`

**list all image sources**

You can add a paginated list with ALL attachments and sources to a post or page using the shortcode `[isc_list_all]`. Use `[isc_list_all per_page="25"]` to show only a limited number of images per page.

The plugin searches your post content and thumbnail for images (attachments) and lists them, if you included at least the image source or marked it as your own image.

== Legal Notice ==

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `wg-image-source-control`-folder to the `/wp-content/plugins/` directory
1. Activate ISC through the 'Plugins' menu in WordPress
1. Visit _Settings > Image Control_ to set up the plugin

See the _Instructions_ section [here](https://wordpress.org/plugins/image-source-control-isc/#description).

== Screenshots ==

1. display a list with all images of your site and their sources
1. display image source within the image
1. some of the settings to customize how to display image sources
1. added two new fields to media library

== Changelog ==

= untagged =

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