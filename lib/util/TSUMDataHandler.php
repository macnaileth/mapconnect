<?php
namespace lib\util;

/**
 * Description of TSUMDataHandler
 * 
 * Class for handling external data stuff, like exporting geojson from external api
 * non-static, must be normally invoked. Needs an URI to fetch from to be passed on construction.
 *
 * @author marconagel
 */
class TSUMDataHandler {
    
    private $tsumFetchURL;
    private $tsumAppend;            
    private $tsumVarArr; 
    
   /**
    * 
    * @param string $url - URL needs to be passed on construct. This is NOT being escaped or sanitized.
    *                      URL should lead to place where stuff could be taken.
    * @param boolean $append - true if parameter should be appended to url. if set to false, you can define an
    *                          array of variables attached to query string.
    */
    public function __construct ( $url, $append = true, $vars = [] ) {       
        $this->tsumFetchURL = $url;
        $this->tsumAppend = $append;
        !empty($vars) ? $this->tsumVarArr = $vars : null;
    }
    public function tsumFetchJSONAreaData ( $area ) {
        
    }
}
