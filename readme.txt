=== Post Attached Media Downloads ===
Contributors: Clorith
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8VLNZ3434PBZQ
Tags: post, media, attachment, shortcode, download, link
Requires at least: 3.9.0
Tested up to: 4.1
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily add media downloads to posts and pages


== Description ==

Add media to posts and pages (even Custom Post Types!) to quickly and easily generate download lists for your content.

= Features include =
* Simple per-post management of downloadable media
* Use the familiar WordPress media screen to upload or add files
* Use a single shortcode, `[pamd]`, to add downloads to your content
* Advanced users may call functions directly in their themes to implement download areas outside the post content

= Advanced use =
If you wish to make use of media downloads in your theme, you can call the `get_downloads()` function directly in the following way;

`$pamd->get_downloads( $postID, $echo, $return_format, $target )`

All parameters are optional, and if no downloads are found the function will return false. The parameters control the following;


**$postID** *integer*

The ID of the post you wish to fetch, if none is provided the id form The Loop will be used


**$echo** *boolean*

Should the content be echoed or returned


**$return_format** *string*

What format should the list be returned in, the default is an unordered list

* `array` returns an array of the media
* `table` returns a table with the media
* `pamd` (default) returns an unordered list with the media


**$target** *string*

This attribute is only used if you are returning a table or the default behavior is left, and will determine the target attribute of the link


== Installation ==

1. Upload the `post-attached-media-downloads` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Find the `Media Downloads` box under the content editor when writing your posts or pages
4. Use the `[pamd]` shortcode in your post where ever you want the list of downloads to appear

== Frequently Asked Questions ==

= Can I use this plugin on multisites? =
Yes, the plugin uses post meta so it doesn't matter what setup you use

== Screenshots ==

1. The media downloads list under the content editor
2. The shortcode output in a post


== Changelog ==

= 1.2.1 =
* Updated the minimum requirement to WordPress 3.9 with the introduction of context buttons for the editor
* Modified the behavior of the `get_downloads` function to return `false` if no downloads are found

= 1.2 =
* Added context button to the editor
* Added support for changing link targets
* Tested with the upcoming WordPress 4.0

= 1.1 =
* Tested with WordPress 3.9
* Re-ordering download lists by drag and drop
* Ability to edit download labels

= 1.0.1 =
* Fix warning output showing on post pages if no files are added

= 1.0.0 =
* Initial release

== Upgrade Notice ==

Fix for posts using the plugin shortcode without any available downloads