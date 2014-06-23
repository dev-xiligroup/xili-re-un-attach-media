=== xili re/un-attach media ===
Contributors: michelwppi
Tags: media, media library, upload, attach, unattach, reattach, dashboard, attachment
Donate link: http://dev.xiligroup.com/
Requires at least: 3.8.3
Tested up to: 4.0-alpha
Stable tag: 0.9.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Unattach, Reattach new actions in Media Library Table list to manage attachments

== Description ==

This plugin named - xili re/un-attach media - adds *Unattach*, *Reattach* new actions in Media Library Table list to manage attachments in the File column just after *View* action.
This plugin is only active in admin (dashboard) side.

Link to [Github by dev.xiligroup](https://github.com/dev-xiligroup/xili-re-un-attach-media "and other xili plugins or themes in dev").

Link to [other plugins made by dev.xiligroup](http://wordpress.org/plugins/search.php?q=xili&sort= "Other xili-plugins").

German translation by by YogieAnamCara of [sensorgrafie](http://www.sensorgrafie.de)

== Installation ==

Note: if downloaded and unzipped from GitHub - delete "master" suffix - the plugin folder name must be /xili-re-un-attach-media/

1. Upload the folder /xili-re-un-attach-media/ containing files including "xili-re-un-attach-media.php" to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to Media Library settings page and move your mouse in the File column.

== Frequently Asked Questions ==
= Why add these actions? =
Because unattach is useful when you do a mistake when linking a media to a post. Default action only offers to erase the media (file) :-(.

= What about the source? =
The goal to create this plugin came when reading the source of davidn.de (which was not OOP made and not maintained since one year): here no too hacky approach, only good hooks, security (nonce, check_admin_referer) and class active only in admin side.
Inline 'translation ready' help or pointer (35% of the lines).

= What about WP 4.0-alpha? =
To recover the list, you must be in table mode (see icon on top right).

== Screenshots ==
1. The new actions in Media Library list in column File.
2. The new action in Media Library list in column File with only unattached files selection.
3. Icon list or thumbnail mode in WP 4.0-alpha

== Changelog ==
= 0.9.0 (2014-06-15) =
* First release

== Upgrade Notice ==
First public version on WP repository
