=== Query Filter ===
Contributors: marsjaninzmarsa, mainpagepl
Tags: advanced, query, WP Query, filter, search, widget, custom post type, Taxonomy, meta, custom field
Requires at least: 3.0.0
Tested up to: 4.3
Stable tag: 0.0.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Advanced taxonomy and Custom Fields CPT filtering plugin.

== Description ==

Plugin for advanced filtering of site content by taxonomies and custom fields. If you have portfolio site and need to give users power of filtering items by topic and year, or photoblog with hundreds of photos categorized by colors, place and photographer, or even shop with bikes various type, vendor and price - this is something for you. You can setup filtering of any Post Type by any tax or meta parameter, text or numeric and display to your visitors fancy and configurable filtering widget.

Development is happening [on GitHub](https://github.com/marsjaninzmarsa/WordPress-J-QueryFilter).

= Functions =

* Support for any Post Type on site and any theme - if you can display it, you can filter it
* Support for any Taxonomy and any Custom Field - if you have post with it, you can filter by it
* Support for multilingual sites (WPML and Polylang, qTranslate may work, but please don't use it)
* Uses native WordPress templates to display posts
* Toolset, ACF and WooCommerce compatible
* Easy to use for every user
* Fully extendable and customizable for developers (AJAX? No problem. Filtering of users or comments? Even easier)

= Todo =

* Fancy, drag'n'drop filter form configuration
* Full and out of the box support for filtering by text, range (with or without slider) and date
* More build in themes (Pro?)
* AJAX support for every theme (Pro?)

== Installation ==

1. Upload `j-query-filter/` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add new Widget to desired Widget Area and set options
4. Enjoy. 😄

== Frequently Asked Questions ==

= I can't choose any Post Type to filtering =

Do you have any? Plugin currently allows only to choose Post Type which is public and have archive (relays on archive template when displaying results). It may going to change in future releases.

= What is this weird "Filtering parameters" thing? How to deal with it? =

It's YAML representation of filtering parameters, [schema for it is in the code](https://github.com/marsjaninzmarsa/WordPress-J-QueryFilter/blob/master/schema.yaml), more detailed how-to is on the way. It is going to change in future releases to more user-friendly, drag'n'drop visual interface.

= You have AJAX working on your demo, I don't, how to live, what to do?  =

If you are developer, you can adapt your Theme to support it, demo code on the way. If you aren't and you don't understand what happen if you press Ctrl+U, you must hire one for it. It is going to change in future releases.

= Why not foo/bar from todo list not in plugin yet? =

Cause lack of time. You may donate me, I think, if you wanna, then I may have more time (currently you must contact me via [my profile](https://profiles.wordpress.org/marsjaninzmarsa) first).

= Why not foo/bar on your todo list? =

Cause I don't thought about it yet? Feel free to [contact me](https://profiles.wordpress.org/marsjaninzmarsa) with suggestion or [open a Issue on GitHub](https://github.com/marsjaninzmarsa/WordPress-J-QueryFilter/issues). 

== Screenshots ==

Coming soon...

== Changelog ==

= 0.0.2 =
* Fixes compatibility issue with PHP 5.3 versions.

= 0.0.1 =
* Initial release.

== Advanced integration ==

Coming soon...