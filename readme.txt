=== Map Connect Metadata and Map Application ===
Contributors:      Marco Nagel & Kerstin Huppenbauer
Tags:              block
Tested up to:      6.4
Stable tag:        0.0.9
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Map Connect plugin with metadata and map application block for WordPress using different APIs including WordPress REST API
This is the DIMB-Version: up to special demand, it doesn't use postcodes and postcode api because users should be able to modify this in WP.

UPDATE: Well. I want to do things right and not too dirty so this version will feature a settings page where you can turn the use of postcode data on or off.
Will probably be merged back into main branch ;-)

== Description ==

API ROUTES:
/tsu-mapconnect/v1/area/aname/ => lists all areas

/tsu-mapconnect/v1/area/aname/<areaname> => returns a single area by name, use '_' for whitespaces, ae/ue/oe for german Umlaute

/tsu-mapconnect/v1/area/pcode/<Postcode> => Retrieves an area by an included postcode

/tsu-mapconnect/v1/area/activity/<activity> => retrieves an area by activity

SHORTCODE (Not implemented at the moment - use the map block in Gutenberg):

If WP Classic is used, the map app can be placed using the WordPress Shortcode API. Use the following code:

[mapplication base_url="<Base URL to be used>" metadata_url="<URL to the metadata REST API route>" database_url="<URL to your Postgres SQL DB for geometry data>" colors=[<Not used atm>]]

For most cases, it will be sufficent to place the shortcode like this: [mapplication]. 
Advanced users who would like to build their stuff themsselves also refer to Kerstin Huppenbauers nextjs-dimb repo for the map application found here: https://github.com/khuppenbauer/nextjs-dimb

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/tsu-mapconnect` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress


== Frequently Asked Questions ==

none ATM

== Changelog ==
21.01.2023 - created this branch. Master/Origin contains all the stuff. This is simplified.
15.11.2023 - Did a lot of updating, added map frontend element, updated edit fpr Gutenberg
08.06.2023 - Added a skeleton block for the future map application to be placed on WP pages and posts. 
11.04.2023 - finished all basic sanitization and validation stuff. Supports now Gutenberg Block Editor aswell as Classic Editor plugin for data input: Metabox and React Block provided.
13.04.2023 - Added functionality to retrieve an area by entering a postcode
16.04.2023 - Added route for getting areas by activites, added some html status codes
18.04.2023 - Added OpenPLZ API integration and some status messages
17.06.2023 - Added configuration pane to Gutenberg react block and shortcode for app placement
04.02.2024 - Added a settings page - work in progress

= 0.0.9 =
* Forked the thing. About to add working settings page

= 0.0.3 =
* Map added, connected to khuppenbauers api found here (i hope :-)): https://github.com/khuppenbauer/fastapi-dimb

= 0.0.2 =
* Release

