<?php
namespace lib\api;

/**
 * Description of TSUMAPIOutput
 * 
 * Class which handles the api output and the endpoints for tsu mapconnect plugin
 *
 * @author marconagel
 */
defined( 'ABSPATH' ) or die( 'Direct access not allowed!' );

class TSUMAPIOutput {
    
    private $settings = [];

    public function __construct() { 
        //load up helpers lib
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMCleanUp.php';
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMMsgHandler.php';
        //init routes
        add_action( 'rest_api_init', array( $this, 'tsumRegisterRoutes' ) );
        //load plugin settings
        $this->settings = get_option( 'tsumMCOptions' );
    }
    /**
     * tsumRegisterRoutes() 
     * Register API Endpoints/Routes
     */
    public function tsumRegisterRoutes() {
        $version = '1';
        $namespace = 'tsu-mapconnect/v' . $version;
        $base = 'area';
        
        //area name route
        register_rest_route( $namespace . '/' . $base, '/aname/(?P<areaname>[\w_-]+)', array(
          'methods' => 'GET',
          'callback' => array( $this, 'tsumAreaName' ),
          'permission_callback' => '__return_true',  
          'args' => array(
              'areaname' => array(
                'validate_callback' => function($param, $request, $key) {
                  return esc_attr( $param ); //return escaped string
                },
                'required' => true        
              ),),            
        ) );          
        //area name no string set
        register_rest_route( $namespace, '/' . $base . '/aname/', array(
          'methods' => 'GET',
          'callback' => array($this,'tsumAreaListAll'),
          'permission_callback' => '__return_true',
          ) 
        );   
        //resolve area via postcode route
        register_rest_route( $namespace, '/' . $base . '/pcode/(?P<postcode>\d+)', array(
          'methods' => 'GET',
          'callback' => array($this,'tsumAreaByPCode'),
          'permission_callback' => '__return_true',
          'args' => array(
              'postcode' => array(
                'validate_callback' => function($param, $request, $key) {
                  return is_numeric( $param );
                },
                'required' => true        
              ),),
          ) 
        );
        //resolve area(s) via activities
        register_rest_route( $namespace . '/' . $base, '/activity/(?P<activity>[\w_-]+)', array(
          'methods' => 'GET',
          'callback' => array( $this, 'tsumAreaByActivity' ),
          'permission_callback' => '__return_true',  
          'args' => array(
              'activity' => array(
                'validate_callback' => function($param, $request, $key) {
                  return esc_attr( $param ); //return escaped string
                },
                'required' => true        
              ),),            
        ) );               
    }
    /**
     * tsumAreaName( $data )
     * callback function to return a specific area's data by name
     * 
     * @param array $data data array
     * @return type
     */
    public function tsumAreaName( $data ) { 
        //load string helpers here
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMStringHelpers.php';
        
        //check if existing
        $pages = get_pages();
        $notice = [];
        
        foreach($pages as $page) {
            //get data and check if relevant stuff exists
            $reqName = \lib\util\TSUMStringHelpers::tsumConvertToUmlaute($data['areaname'], true);
            
            $pAreaName = get_post_meta( $page->ID, '_meta_fields_tsum_areaname', true );
            if ( !empty($pAreaName) && $pAreaName == $reqName ) {
                //return area to response
                return [ 
                            'response' => \lib\util\TSUMMsgHandler::tsumAPIReturnMessage($reqName), 
                            'area' => $this->tsumArea($page->ID, $reqName) 
                        ];
            } else {
                $notice = \lib\util\TSUMMsgHandler::tsumAPIReturnMessage($reqName, 404);
            }           
        }
        return $notice;
    }
    /**
     * tsumAreaListAll()
     * callback function to list all area data
     * 
     * @return array
     */
    public function tsumAreaListAll() {
        $response = [   
                        'response' => \lib\util\TSUMMsgHandler::tsumAPIReturnMessage('AREA_LIST'),
                        'areas' => []
                    ];
        
        //get the pages and return the needed params in API
        $pages = get_pages();
        
        foreach($pages as $page) {
            //get data and check if relevant stuff exists
            $pAreaName = get_post_meta( $page->ID, '_meta_fields_tsum_areaname', true );
            if (!empty($pAreaName)) {
                
                $area = $this->tsumArea( $page->ID, $pAreaName );               
                array_push($response['areas'], $area);
                
            }
        }
        
        return $response;
    }
    /**
     * tsumAreaByPCode( $data )
     * callback function to retrieve an area by entering a postcode.
     * 
     * @param array $data array of data to look through
     * @return array returns an array to be sent to the REST API of WP
     */
    public function tsumAreaByPCode( $data ) {
        
        //check if we should return postal codes at all
        if ( $this->tsumGetOptionByKey( "tsum_general_setting_pc_output", '1' ) === true ) {

            //get the pages and return the needed params in API
            $pages = get_pages();
            $reqPostCode = $data['postcode'];

            $response = [ 
                'response' => \lib\util\TSUMMsgHandler::tsumAPIReturnMessage( $data['postcode'] ), 
                'requested-pc' => $reqPostCode, 
                'location' => $this->tsumGetLocation( $reqPostCode ), 
                'match' => [] 
                ];

            foreach($pages as $page) {
                //get data and check if relevant stuff exists
                $pAreaName = get_post_meta( $page->ID, '_meta_fields_tsum_areaname', true );
                if (!empty($pAreaName)) {
                    //check for if we match
                    $pAreaCodes = get_post_meta( $page->ID, '_meta_fields_tsum_areapcs', true );

                    if (strpos($pAreaCodes, $reqPostCode) !== false) {
                        $area = $this->tsumArea( $page->ID, $pAreaName ); 
                        array_push( $response['match'], $area );
                    }                 
                }
            }       
            return empty( $response['match'] ) ? array_push($response['response'], \lib\util\TSUMMsgHandler::tsumAPIReturnMessage($reqPostCode, 404) ) : $response;
            
        } else {
            $response['response'] = \lib\util\TSUMMsgHandler::tsumAPIReturnMessage( $data['postcode'] );
            $response['match'] = [ -1, esc_html__( 'Postcode output has been disabled in settings. Retrieving matching area not possible.', 'tsu-mapconnect' ) ];
            return $response;
        }
    }  
    public function tsumAreaByActivity ( $data ) {
        //load string helpers here
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMStringHelpers.php';
        
        //check if existing
        $pages = get_pages();
        $response = [
                        'response' => [],
                        'match' => []            
                    ];
        
        foreach($pages as $page) {
            //get data and check if relevant stuff exists
            $activity = \lib\util\TSUMStringHelpers::tsumConvertToUmlaute($data['activity'], true);
            
            $pAreaName = get_post_meta( $page->ID, '_meta_fields_tsum_areaname', true );
            if ( !empty($pAreaName) &&  strlen( $data['activity'] ) > 2 ) {
                //get activities lowercase for matching
                $pAreaAct = strtolower( 'a' . get_post_meta( $page->ID, '_meta_fields_tsum_areaactivities', true ) );
                
                if ( strpos( $pAreaAct, strtolower( $data['activity'] ) ) ) {
                    $response[ 'response' ] = \lib\util\TSUMMsgHandler::tsumAPIReturnMessage($activity, 200);
                    $area = $this->tsumArea( $page->ID, $pAreaName ); 
                    array_push($response['match'], $area);    
                }    

            }       
        }
        
        if ( empty( $response[ 'response' ] )) {
                $response[ 'response' ] = \lib\util\TSUMMsgHandler::tsumAPIReturnMessage($activity, 404);
        }             
        return $response;     
    }
    /**
     * tsumArea( $id, $name )
     * function to create an array containg a single area to return to the API
     * 
     * @param int $id ID of Page/Post
     * @param string $name name of area
     * @return array of area data
     */
    private function tsumArea( $id, $name ) {

                //handle logo conversion to usable string
                $logoRAW = get_post_meta( $id, '_meta_fields_tsum_arealogo', true );
                $logoStr = '';
                if (!empty($logoRAW)){
                    $logoStr = is_numeric( $logoRAW ) ? [ 
                                                            'full' => wp_get_attachment_image_src( $logoRAW, 'full' )[0],
                                                            'medium' => wp_get_attachment_image_src( $logoRAW, 'medium' )[0],
                                                            'thumb' => wp_get_attachment_image_src( $logoRAW, 'thumbnail' )[0]
                                                        ] : $logoRAW;
                }
                //create pagedata array
                $pageData = [
                                'id' => $id,
                                'title' => esc_attr( get_the_title( $id ) ),
                                'url' => esc_url( get_page_link( $id ) ),
                                'api' => get_site_url() . '/wp-json/wp/v2/pages/' . $id            
                            ];
                
                $area = [ 
                            'name' => $name,
                            'postcodes' => $this->tsumGetOptionByKey( "tsum_general_setting_pc_output", '1' ) === true ? 
                                                explode(',', \lib\util\TSUMCleanUp::tsumTrimCommaPlus( get_post_meta( $id, '_meta_fields_tsum_areapcs', true ) ) ) :
                                                [ -1, esc_html__( 'Postcode output has been disabled in settings.', 'tsu-mapconnect' ) ],
                            'logo-url' => $logoStr,
                            'site-url' => get_post_meta( $id, '_meta_fields_tsum_arealink', true ) /* TODO: Validate Link! */,
                            'contact' => get_post_meta( $id, '_meta_fields_tsum_areacontact', true ),
                            'activities' => explode(',', \lib\util\TSUMCleanUp::tsumTrimCommaPlus( get_post_meta( $id, '_meta_fields_tsum_areaactivities', true ) ) ),
                            'description' => get_post_meta( $id, '_meta_fields_tsum_areadesc', true ),
                            'socialmedia' => get_post_meta( $id, '_meta_fields_tsum_areasocmedia', true ) /* TODO: Find a format */,
                            'page-ref' => $pageData
                        ];
                
        return $area;
    }
    /**
     * tsumGetLocation ( $string, $country = 'de' )
     * 
     * retrieve place by plz using openplzapi.org s api. At the moment, it only works with postalcodes
     * 
     * @param string $string string containing the paramter, at the moment only a postal code is accepted
     * @param string $country allows to change the country to look up. default: de
     * @return array returns json object from retrieved data
     */
    private function tsumGetLocation ( $string, $country = 'de' ) {
        
        $reqURL = 'https://openplzapi.org/' . $country . '/Localities?postalCode=' . $string;
        
        if (is_numeric( $string )) {
            //by postcode
            $json = file_get_contents( $reqURL );
            $decoded = json_decode($json);
            
            $location = [ 
                'source' => 'https://openplzapi.org/', 
                'request' => $reqURL, 
                'data' => array_key_exists( 0, $decoded ) ? $decoded[0] : $decoded
                ];
            
            return $location;
        } else {
            //TODO: Allow string search for locations later
            return [ 'TODO' => 'Service under construction.' ];
        }
        
    }
    /**
     * tsumGetOptionByKey
     * private function to check if key exists in options array 
     * and has a certain value
     * 
     * 
     * @param string $key
     * @return boolean true / false
     */
    private function tsumGetOptionByKey ( $key, $value = '1' ) { 
        
        $setting = is_array( $this->settings ) 
                && array_key_exists( $key, $this->settings ) 
                && $this->settings[ $key ] == $value ? true : false;
        
        return $setting;
        
    }
}
