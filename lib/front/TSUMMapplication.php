<?php
namespace lib\front;

/**
 * Description of TSUMMapplication
 * 
 * Class to embed the map application to display and render the map in the webpage.
 * mainly a short code which is configured via the Gutenberg Block "mapplication" to load
 * K.Huppenbauers map application. 
 *
 * @author marconagel
 */
defined( 'ABSPATH' ) or die( 'Direct access not allowed!' );

class TSUMMapplication {

    public function __construct() {
        //init mapplication shortcode
        add_shortcode( 'mapplication', array( $this, 'tsumMapApp' ) );
    }
    /**
     * tsumMapApp( $atts )
     * Shortcode for the map application
     * @param type $atts = array of parameters to hand over to the map application
     * @return string
     */
    public function tsumMapApp( $atts ) {
        
        //for resetting, setup defaults
        $defaultParams = [ 
            'metadata_url' => get_home_url() . '/wp-json/tsu-mapconnect/v1/area/name/', 
            'base_url' => get_home_url(), 
            'database_url' => '<DEFAULT>',
            'colors' => []
            ];

        $MapParams = shortcode_atts( array (
            'metadata_url' => $defaultParams['metadata_url'],
            'base_url' => $defaultParams['base_url'],
            'database_url' => $defaultParams['database_url'],
            'colors' => $defaultParams['colors']
        ), $atts );       
        return $this->tsumMapInlineJS( $MapParams, $defaultParams ) . "\n";
    }
    /**
     * tsumMapInlineJS( $params, $defaults )
     * returns array values from associative array in js object format, e.g. in pairs like this: property: "value"
     * if in $params array are certain values are empty, it will use their defaults instead (stored in $defaults).
     * 
     * @param array $params = associative array containing values. in conversion process, pairs will created like this: property ($key): "value($value)"
     * @param array $defaults = the default values to be used. Must match $params regarding key names. If a value is empty, its default key value will be used instead.
     * @param boolean $tags = When set to true, it will enclose the returned string in <script></script> tags, if false, it will return only the value pairs as a string. Default: true
     * @param boolean $log = When set to true, the created variable and data will be console log outputted.
     * @return string = the returned values - formatted or not.
     */
    private function tsumMapInlineJS( $params, $defaults, $tags = true, $log = true ) {
        
        $JSObject = '';
        
        foreach( $params as $key => $value ) {
            
            if( is_array($value) ) {
                if ( !empty($value) ) {
                    //TODO: Insert functionality here
                } else {
                    $prop = $key . ': [], ';
                    $JSObject .= $prop;                    
                }
            }
            else {
                //check if we have defaults and set them if value is empty
                $value = empty($value) ? $defaults[ $key ] : $value;
                $prop = $key . ': "' . $value . '", ';
                $JSObject .= $prop;
            }
              
        }
        
        return ( $tags == true ? '<script type="text/javascript">var mapConf = { ' : '' ) . $JSObject . ( $tags == true ? ' };' 
                . ( $log == true ? ' console.log( "%cmap-connect configuration: ", "color:green;", mapConf ); ' : '' ) . '</script>' : '' );
        
    }
}
