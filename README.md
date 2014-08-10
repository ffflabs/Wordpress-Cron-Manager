=== FFF-Cron-Manager ===
* Contributors: amenadiel
* Tags: wp-cron
* Requires at least: 3.0
* Tested up to: 3.4.1
* Stable tag: "trunk"
* License: GPLv2 or later
*License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will add an option to list, delete and restore cronjobs in WP Cron

== Description ==

WP Plugin URL: http://wordpress.org/extend/plugins/fff-cron-manager/

This plugin will add an option to list, delete and restore cronjobs in WP Cron. It's loosely based on Simon Wheatley's http://wordpress.org/extend/plugins/cron-view/ 

Compatible up to version 3.4.1

It will create a backup of your crons when you install it, in order to be able to restore anything that may get broken.

Future releases may include the capability of creating new cronjobs.
 

== Installation ==


1. Upload `cron_manager.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

That's it! You can see your cront in a new menu in your dashboard.
 

== Screenshots ==

1. 'cron_manager.jpg'

== Changelog ==

= 0.3 =
* It adds functionality to simon wheatley's plugin, which was left at v 0.2. Hence my plugin starts from 0.3

= 0.4 =
* Delete crons by ajax

= 0.5 =
* Take current cron snapshots, restore them whenever you want, or just delete them
* When a cron entry is deleted, your cron is purged to avoid having empty entries eating space.

= 0.6 =
* Add datadable treatment to the cron table, so you can filter or sort dinamically

= 0.7 =
* Most changed are now ajax based
* You can mass delete cronjobs using checkboxes
