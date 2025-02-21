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
            'metadata_url' => get_home_url() . '/wp-json/tsu-mapconnect/v1/area/aname', 
            'base_url' => 'https://dimb.api-spots.de', 
            'api_url' => 'https://dimb.api-spots.de', 
            'database_url' => '<DEFAULT>' /* unused at the moment 01.02.2025 */,
            'feat_fill_rgb' => '0, 94, 169',
            'feat_fill_alpha' => '0.3',
            'feat_stroke_rgb' => '0, 94, 169',
            'feat_stroke_width' => '2',
            'feat_highlight_rgb' => '236, 102, 8',
            'headline_rgb' => '80, 84, 86',
            'text_rgb' => '52, 58, 64',            
            'width' => '100%',
            'height' => '400px',
            'add_classes' => 'mapplication-app',
            'debug' => '0'
            ];

        $MapParams = shortcode_atts( array (
            'metadata_url' => $defaultParams['metadata_url'],
            'base_url' => $defaultParams['base_url'],
            'api_url' => $defaultParams['api_url'],
            'database_url' => $defaultParams['database_url'],
            'feat_fill_rgb' => $defaultParams['feat_fill_rgb'],
            'feat_fill_alpha' => $defaultParams['feat_fill_alpha'],
            'feat_stroke_rgb' => $defaultParams['feat_stroke_rgb'],
            'feat_stroke_width' => $defaultParams['feat_stroke_width'],
            'feat_highlight_rgb' => $defaultParams['feat_highlight_rgb'],
            'headline_rgb' => $defaultParams['headline_rgb'],
            'text_rgb' => $defaultParams['text_rgb'],                  
            'width' => $defaultParams['width'],
            'height' => $defaultParams['height'],   
            'add_classes' => $defaultParams['add_classes'],
            'debug' => $defaultParams['debug']
        ), $atts );       
        
        //inject needed config scripts to header
        wp_register_script( 'tsu-mapplication-config', '' );
        wp_enqueue_script( 'tsu-mapplication-config' );
        wp_add_inline_script( 'tsu-mapplication-config', $this->tsumMapConfig( $MapParams ) );
        
        //create mapapp here
        $appContainer = $this->tsumInjectMapContainer( $MapParams );
        
        return $appContainer . ( $MapParams['debug'] === '1' ? $this->tsumMapInlineJS( $MapParams, $defaultParams, true, true ) : '' ) . "\n";
    }
    /**
     * function tsumInjectMapContainer( $params )
     * 
     * function to spawn js application with map inside of a container on the page.
     * @param string $params = shortcode atts as parameters
     * @return  string = returns the ready-to-use html code
     */
    private function tsumInjectMapContainer( $params ) {
        
        $html = '<div id="root"></div>';
        $html .= '<script defer src="' . TSU_MC_PLUGIN_URL . "/app/main.js" . '"></script>';
        
	return '<div class="map-container" style="padding: 0; width: ' . $params['width'] . '; height: ' . $params['height'] . '; background: url(' . TSU_MC_PLUGIN_URL . '/data/images/preload-map.jpg) no-repeat center center / cover;">' . $html . '</div>';
    }
    
    private function tsumMapConfig( $params ) {
        
        //set height simple numeric        
        $sanitizedHeight = preg_replace("/[^0-9]/", "", $params['height'] );
        
        $configStr = "            window.mapconfig =   { 
                                    style: {
                                        featFillRGB: '". $params['feat_fill_rgb'] . "',
                                        featFillAlpha: '". $params['feat_fill_alpha'] . "',
                                        featStrokeRGB: '". $params['feat_stroke_rgb'] . "',
                                        featStrokeWidth: '". $params['feat_stroke_width'] . "',
                                        featHighlightRGB: '". $params['feat_highlight_rgb'] . "',
                                        headlineRGB: '". $params['headline_rgb'] . "',
                                        textRGB: '". $params['text_rgb'] . "'
                                    }, 
                                    paths: {
                                        baseUrl: '" .  $params['base_url'] . "',
                                        apiBaseUrl: '" .  $params['api_url'] . "',
                                        plugin: '/dimb/wp-content/plugins/tsu-mapconnect',
                                        metaDataURL: '" .  $params['metadata_url'] . "',
                                    }, 
                                    height: '" . $sanitizedHeight . "',
                                    addClasses: '" . $params['add_classes'] . "'
                                };";
        return $configStr;
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
