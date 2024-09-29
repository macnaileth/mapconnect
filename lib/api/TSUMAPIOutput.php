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
        //load up helpers, message handler and clean up lib
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMCleanUp.php';
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMMsgHandler.php';
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMHelpers.php';
        //load data handler lib
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMDataHandler.php';
        
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
        
        //area name route - old regex: (?P<areaname>([\w\/\+\-.:_äöüÄÖÜß]|%20)+) other: (?P<areaname>[\w].+)
        register_rest_route( $namespace . '/' . $base, '/aname/(?P<areaname>[\w].+)', array(
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
        
        //connect to database
        $connect = new \lib\util\TSUMDataHandler( isset( $this->settings['tsum_general_setting_db_table'] ) ? $this->settings['tsum_general_setting_db_table'] : '' );
        
        $areaID = $connect->tsumCheckAreaExists( urldecode( $data['areaname'] ) );
        $fixedAreaName = \lib\util\TSUMHelpers::tsumFixDIMBQueryString( $data['areaname'] );
        
        //check if existing
        $pages = get_pages();
        $notice = [];
        $exists = false;
       
        if ( $areaID !== false ){
            $exists = true;
        }        
 
        $reqName = urldecode( \lib\util\TSUMHelpers::tsumConvertToUmlaute($data['areaname'], true) );
        
        //check for meta information on pages
        foreach($pages as $page) {
            //get data and check if relevant stuff exists
            
            $pAreaName = get_post_meta( $page->ID, '_meta_fields_tsum_areaname', true );
            if ( !empty($pAreaName) && 
                    ( $pAreaName == $reqName 
                    || 'DIMB ' . $pAreaName == $reqName 
                    || 'DIMB IG ' . $pAreaName == $reqName 
                    || $pAreaName == 'DIMB ' . $reqName 
                    || $pAreaName == 'DIMB IG ' . $reqName
                    || $pAreaName == 'IG ' . $reqName) ) {
                //return area to response
                return [ 
                            'response' => \lib\util\TSUMMsgHandler::tsumAPIReturnMessage($reqName), 
                            'area' => $this->tsumArea($page->ID, $reqName) 
                        ];
            } else {
                $notice = \lib\util\TSUMMsgHandler::tsumAPIReturnMessage($reqName, 404);
            }           
        }   
        
        if ( $exists === true ) {
            //return area to response
            return [
                'response' => \lib\util\TSUMMsgHandler::tsumAPIReturnMessage($reqName),
                'area' => $this->tsumArea(0, $reqName, true)
            ];
        }
        
        return $notice;
    }
    /**
     * tsumAreaListAll()
     * callback function to list all area data
     * TODO: Rewrite function to display all areas
     * 
     * @return array
     */
    public function tsumAreaListAll() {
        $response = [   
                        'response' => \lib\util\TSUMMsgHandler::tsumAPIReturnMessage('AREA_LIST'),
                        'areas' => []
                    ];
        //init data handler
        $tsumDataHandler = new \lib\util\TSUMDataHandler( isset( $this->settings['tsum_general_setting_db_table'] ) ? $this->settings['tsum_general_setting_db_table'] : '' ); 
        
        //fetch available areas from database
        $response['areas'] = $tsumDataHandler->tsumRetrieveAvailableAreas();
        
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
        //initalize db handler
        $tsumDataHandler = new \lib\util\TSUMDataHandler( isset( $this->settings['tsum_general_setting_db_table'] ) ? $this->settings['tsum_general_setting_db_table'] : '' ); 
        
        //check if we should return postal codes at all TODO: USE STATIC HELPER
        if ( \lib\util\TSUMHelpers::tsumGetOptionByKey( $this->settings, "tsum_general_setting_pc_output", '1' ) === true ) {
        
            //get the pages and return the needed params in API
            $pages = get_pages();
            $reqPostCode = $data['postcode'];

            $response = [ 
                'response' => \lib\util\TSUMMsgHandler::tsumAPIReturnMessage( $data['postcode'] ), 
                'requested-pc' => $reqPostCode, 
                'location' => \lib\util\TSUMHelpers::tsumGetLocation( $reqPostCode ), 
                'match' => [], 
                'data-extent' => 'full'
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
            //TODO: Check database, then query openPLZ if needed
            $areaData = $tsumDataHandler->tsumGetAreaByPcOrLocalityName( $data['postcode'] );
            
            $response['response'] = \lib\util\TSUMMsgHandler::tsumAPIReturnMessage( $data['postcode'] );
            $response['data-extent'] = 'reduced';
            $response['match'] = $areaData;//\lib\util\TSUMHelpers::tsumGetLocation( $data['postcode'], 'de', '' );
            return $response;
        }
    }  
    public function tsumAreaByActivity ( $data ) {
        //load string helpers here
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMHelpers.php';
        
        //check if existing
        $pages = get_pages();
        $response = [
                        'response' => [],
                        'match' => []            
                    ];
        
        foreach($pages as $page) {
            //get data and check if relevant stuff exists
            $activity = \lib\util\TSUMHelpers::tsumConvertToUmlaute($data['activity'], true);
            
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
     * @param bool $reducedData only uses data from the database directly, no metadata from pages. Default: false
     * @return array of area data
     */
    private function tsumArea( $id, $name, $reducedData = false ) {
        
        //init data handler
        $tsumDataHandler = new \lib\util\TSUMDataHandler( isset( $this->settings['tsum_general_setting_db_table'] ) ? $this->settings['tsum_general_setting_db_table'] : '' ); 

        if ( $reducedData === false ){
            //handle logo conversion to usable string
            $logoRAW = get_post_meta($id, '_meta_fields_tsum_arealogo', true);
            $logoStr = '';
            if (!empty($logoRAW)) {
                $logoStr = is_numeric($logoRAW) ? [
                    'full' => wp_get_attachment_image_src($logoRAW, 'full')[0],
                    'medium' => wp_get_attachment_image_src($logoRAW, 'medium')[0],
                    'thumb' => wp_get_attachment_image_src($logoRAW, 'thumbnail')[0]
                        ] : $logoRAW;
            }
            //create pagedata array
            $pageData = [
                'id' => $id,
                'title' => esc_attr(get_the_title($id)),
                'url' => esc_url(get_page_link($id)),
                'api' => get_site_url() . '/wp-json/wp/v2/pages/' . $id
            ];
        }
        
        if ( $reducedData === true ){
            return [
                'name' => \lib\util\TSUMHelpers::tsumFixDIMBQueryString( $name ),
                'postcodes' => \lib\util\TSUMHelpers::tsumGetOptionByKey($this->settings, "tsum_general_setting_pc_output", '1') === true ?
                explode(',', \lib\util\TSUMCleanUp::tsumTrimCommaPlus(get_post_meta($id, '_meta_fields_tsum_areapcs', true))) :
                $tsumDataHandler->tsumRetrievePostCodesByAname( $name ),                
                'data-extent' => 'reduced'
            ];
        }
        
        $area = [
            'name' => \lib\util\TSUMHelpers::tsumFixDIMBQueryString( $name ),
            'postcodes' => \lib\util\TSUMHelpers::tsumGetOptionByKey($this->settings, "tsum_general_setting_pc_output", '1') === true ?
            explode(',', \lib\util\TSUMCleanUp::tsumTrimCommaPlus(get_post_meta($id, '_meta_fields_tsum_areapcs', true))) :
            $tsumDataHandler->tsumRetrievePostCodesByAname( $name ),
            'logo-url' => $logoStr,
            'site-url' => get_post_meta($id, '_meta_fields_tsum_arealink', true) /* TODO: Validate Link! */,
            'contact' => get_post_meta($id, '_meta_fields_tsum_areacontact', true),
            'activities' => explode(',', \lib\util\TSUMCleanUp::tsumTrimCommaPlus(get_post_meta($id, '_meta_fields_tsum_areaactivities', true))),
            'description' => get_post_meta($id, '_meta_fields_tsum_areadesc', true),
            'socialmedia' => get_post_meta($id, '_meta_fields_tsum_areasocmedia', true) /* TODO: Find a format */,
            'page-ref' => $pageData,
            'data-extent' => 'full'
        ];

        return $area;
    }
}
