=== Divisions ===
Contributors: timo.nachstedt
Donate link: http://www.nachstedt.com/
Tags: divisions, subcategories
Requires at least: 3.6
Tested up to: 3.6
Stable tag: 0.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create multiple divisions in your site with individual menus, sidebars and header.

== Description ==

The Divisions Plugin enables you to create parts, called divisions, of your
web page with an individual look and feel as determined by individual sidebars,
navigation menus and header images. At the same time, all divisions share the
same content data and a single posts can be displayed in any division.
Navigation menu entries are used to switch between divisions. Technically, the 
division is determined by an additional argument in the URL. 

== Installation ==

1. Upload the contents of the plugin archive to the `/wp-content/plugins/` directory (or alternatively use the automatic installer)
2. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Overview of divisions in your web page.

2. Edit screen for a division: Choose which navigation menus and sidebars to
replace and decide whether the division should replace the header image in your
theme.

3. Replaced sidebars are populated with widgets just as the normal ones in the
widgets screen of the Appearance settings.

4. Menu items can be configured such as to modify the current division.

5. Replaced navigation menu locations are populated with menu instances just
as normal locations.

== Changelog ==

= 0.2.2 =
* fixed bug that always forwarded pretty permalinks to startpage
* fixed bug that added division twice to pretty permalinks in menu
* added method get_current_division() to plugin item for external use

= 0.2.1 =
* Division permalinks for homepages work
* dvs_division/* permalinks are always removed from list of rewrite rules

= 0.2.0 =
* Divisions can be encoded into links as first argument of the permalink structure

= 0.1.2 =
* CSS class current_division_item is also added to menu items that have "no division" attached"
* Divisions menu entry in admin interface is displayed after second seperator
* Divisions configuration is only available for administrators

= 0.1.1 =
* fixing paths issues of php and script files
* fixing bug that prevent division option for navigation menu entries
* removed dead settings link on plugin page
* fixed typos in format mistakes of readme.txt
* removed screenshots from the plugin folder

= 0.1.0 =
* First published version

== Upgrade Notice ==

= 0.2.2 =
Fixed severe bugs related to pretty permalinks

= 0.2.1 =
Fixed bugs related to new permalink option

= 0.2.0 =
Instead of attaching "?division=xx" to your URL, you may now directly alter your permalink structure!

= 0.1.2 =
Some minor improvements.

= 0.1.1 =
This version fixes several critical bugs.

= 0.1.0 =
This is the first published version.
