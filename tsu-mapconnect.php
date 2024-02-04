<?php
/*
Plugin Name: Map Connect
Description: Map connect plugin to connect specific page data with frontend map application using the wordpress REST api.
Version: 0.0.9
Author: Marco Nagel
Author URI: https://tsu-nami.de/
Text Domain: tsu-mapconnect
Domain Path: /languages
*/
defined( 'ABSPATH' ) or die( 'Direct access not allowed!' );

//Plugin header translation strings
esc_html__( 'Map Connect', 'tsu-mapconnect' );
esc_html__( 'Map connect plugin to connect specific page data with frontend map application using the wordpress REST api.', 'tsu-mapconnect' );

//global plugin paths
define( 'TSU_MC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'TSU_MC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

//load textdomain
function tsumLoadInit() {
    //text domain
    load_plugin_textdomain('tsu-mapconnect', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action( 'init', 'tsumLoadInit' );

//startup
require_once TSU_MC_PLUGIN_PATH . '/lib/TSUMCCore.php';
$tsuMapConnect = new lib\TSUMCCore();