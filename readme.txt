=== Map Connect Metadata and Map Application ===
Contributors:      Marco Nagel & Kerstin Huppenbauer
Tags:              block, database, club management, mapping, geodata, react, jsx, tsx, made for DIMB
Tested up to:      6.9.1
Stable tag:        0.1.0
Requires PHP:      8.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Map Connect plugin with metadata and map application block for WordPress using different APIs including WordPress REST API
This is the DIMB-Version: up to special demand, it doesn't use postcodes and postcode api because users should be able to modify this in WP.

UPDATE: Well. I want to do things right and not too dirty so this version will feature a settings page where you can adjust some settings.
Will probably be merged back into main branch ;-)

To use the external database connection feature, you need to set up a parameters table inside your wordpress database (correctly prefixed) holding
the connection params for the external database in following format:
	
id  parameter               value
----------------------------------------------------
1   ig_event_db_host        database host
2   ig_event_db_name        database name
3   ig_event_db_user        database user name
4   ig_event_db_password    database user password (not hash)

keep in mind, that if someone gets access to your database, he might steal the connection data for the external database including all credentials.
In most cases it is advised to use the internal database feature.

== Description ==

API ROUTES:
/tsu-mapconnect/v1/area/aname/ => lists all areas

/tsu-mapconnect/v1/area/aname/<areaname> => returns a single area by name, use '_' for whitespaces, ae/ue/oe for german Umlaute

/tsu-mapconnect/v1/area/pcode/<Postcode> => Retrieves an area by an included postcode (If postcode-handling is not deactivated)

/tsu-mapconnect/v1/area/activity/<activity> => retrieves an area by activity

SHORTCODE:

[mapplication 
    base_url="<Base URL to be used, default: https://dimb.api-spots.de>" 
    api_url="<API URL to be used, default: https://dimb.api-spots.de>"
    metadata_url="<URL to the metadata REST API route>" (not used at the moment)
    database_url="<URL to your Postgres SQL DB for geometry data>" (Not used at the moment) 
    feat_fill_rgb = "0, 94, 169",
    feat_fill_alpha = "0.3",
    feat_stroke_rgb => "0, 94, 169",
    feat_stroke_width => "2",
    feat_highlight_rgb => "236, 102, 8",
    headline_rgb => "80, 84, 86",
    text_rgb => "52, 58, 64",            
    width => "100%",
    height => "400px",
    add_classes => 'mapplication-app',
    debug => "0"
]

All colors need to be set as RGB values.

In most cases, it will be sufficent to place the shortcode like this: [mapplication]. 
Advanced users who would like to build their stuff themsselves also refer to: 
- Kerstin Huppenbauers nextjs-dimb repo for the map application found here: https://github.com/khuppenbauer/nextjs-dimb (the original source)
- My REACT element repo for the map used here: https://github.com/macnaileth/react-dimb-map

Please note that we use Open Street Map loading map tiles from the default OSM servers - if you are in DSGVO-area, you probably need to state
this somewhere on your webpages to make sure you don't violate any rights here.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/tsu-mapconnect` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

You will have to setup the external database yourself. We provide a .csv to fill it with data. 

CSV IMPORT: 
To import csv data into the database tables (internal/external database, whatever you like) you need to put your csv files into
/data/csv/. With this plugin, there are already two csv files included: the import script needs them to be named like these two, also format has to be the same (columns separated with ; no "").
When importing to external database, there will be made a backup of the table before import - internal imports will not be backuped.
IMPORTANT: Tables imported to will be TRUNCATED before import, so you cannot append data via import at the moment.

== Frequently Asked Questions ==

none ATM

== Changelog ==
16.02.2025 - Finished this not simplified but complicated plugin as a first release version. It works now with internal and external database tables and use of page data.
29.07.2024 - Main API Endpoints finished a bit: /tsu-mapconnect/v1/area/aname/ and /tsu-mapconnect/v1/area/aname/<areaname> work with external database
20.04.2024 - Updates to API Endpoints to get data from external database. now postcode retrieval can be switched to external data
21.01.2024 - created this branch. Master/Origin contains all the stuff. This is simplified.
15.11.2023 - Did a lot of updating, added map frontend element, updated edit fpr Gutenberg
08.06.2023 - Added a skeleton block for the future map application to be placed on WP pages and posts. 
11.04.2023 - finished all basic sanitization and validation stuff. Supports now Gutenberg Block Editor aswell as Classic Editor plugin for data input: Metabox and React Block provided.
13.04.2023 - Added functionality to retrieve an area by entering a postcode
16.04.2023 - Added route for getting areas by activites, added some html status codes
18.04.2023 - Added OpenPLZ API integration and some status messages
17.06.2023 - Added configuration pane to Gutenberg react block and shortcode for app placement
04.02.2024 - Added a settings page - work in progress
07.02.2024 - Updated js dependencies and rebuilt blocks

= 0.1.0 =
* Finally somehow useable ;-)
* CSV Import for external database as well as for internal tables
* commented out the Gutenberg map block in favour of the shortcode. Will be probably readded later on but has to be completely redone.

= 0.0.9 =
* Forked the thing. About to add working settings page

= 0.0.3 =
* Map added, connected to khuppenbauers api found here (i hope :-)): https://github.com/khuppenbauer/fastapi-dimb

= 0.0.2 =
* Release

