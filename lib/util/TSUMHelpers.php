<?php
namespace lib\util;

/**
 * Description of TSUMHelpers
 * 
 * Static helpers for string operations
 *
 * @author marconagel
 */
defined( 'ABSPATH' ) or die( 'Direct access not allowed!' );

class TSUMHelpers {
    
    /**
     * tsumConvertToUmlaute( $string )
     * 
     * converts 'ue', 'oe', 'ae' capitals and lower case to the german Umlaute 'ü', 'ö', 'ä'
     * 
     * @param string $string string to convert 
     * @param boolean $convToWhitespace true/false if underscores should be converteds to whitespaces (default: false)
     * @return string Updated string
     */    
    public static function tsumConvertToUmlaute( $string, $convToWhitespace = false ) {     
        
        $prepStr = $convToWhitespace == true ? str_replace( '_', ' ', $string ) : $string;
        return str_replace( [ 'ae', 'Ae', 'oe', 'Oe', 'ue', 'Ue' ], [ 'ä', 'Ä', 'ö', 'Ö', 'ü', 'Ü' ], $prepStr );
        
    }
    
    /**
     * tsumGetOptionByKey
     * private function to check if key exists in options array 
     * and has a certain value
     * 
     * @param array $array
     * @param string $key
     * @return boolean true / false
     */
    public static function tsumGetOptionByKey ( $array, $key, $value = '1' ) { 
        
        $setting = is_array( $array ) 
                && array_key_exists( $key, $array ) 
                && $array[ $key ] == $value ? true : false;
        
        return $setting;
        
    }    
}
