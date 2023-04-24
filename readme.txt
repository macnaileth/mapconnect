=== Map Connect Metadata ===
Contributors:      Marco Nagel
Tags:              block
Tested up to:      6.1
Stable tag:        0.0.1
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Metadata for use with map connect plugin

== Description ==

API ROUTES:
/tsu-mapconnect/v1/area/aname/ => lists all areas

/tsu-mapconnect/v1/area/aname/<areaname> => returns a single area by name, use '_' for whitespaces, ae/ue/oe for german Umlaute

/tsu-mapconnect/v1/area/pcode/<Postcode> => Retrieves an area by an included postcode

/tsu-mapconnect/v1/area/activity/<activity> => retrieves an area by activity

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/tsu-mapconnect` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress


== Frequently Asked Questions ==

none ATM

== Changelog ==
11.04.2023 - finished all basic sanitization and validation stuff. Supports now Gutenberg Block Editor aswell as Classic Editor plugin for data input: Metabox and React Block provided.
13.04.2023 - Added functionality to retrieve an area by entering a postcode
16.04.2023 - Added route for getting areas by activites, added some html status codes
18.04.2023 - Added OpenPLZ API integration and some status messages


= 0.0.1 =
* Release

