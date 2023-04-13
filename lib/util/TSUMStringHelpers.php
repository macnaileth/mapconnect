<?php
namespace lib\util;

/**
 * Description of TSUMStringHelpers
 * 
 * Static helpers for string operations
 *
 * @author marconagel
 */
class TSUMStringHelpers {
    
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
}
