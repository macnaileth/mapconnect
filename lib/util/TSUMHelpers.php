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
        
        $prepStr = $convToWhitespace == true ? str_replace( ['_', '+', '%20'], ' ', $string ) : $string;
        return str_replace( [ 'ae', 'Ae', 'oe', 'Oe', 'ue', 'Ue' ], [ 'ä', 'Ä', 'ö', 'Ö', 'ü', 'Ü' ], $prepStr );
        
    }
    
    /**
     * tsumGetOptionByKey
     * public function to check if key exists in options array 
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
    
    /**
     * tsumFixDIMBQueryString
     * public function to fix api requests a little bit. 
     * For example, if you Query for the DIMB IG Rems-Murr and you
     * type Rems-Murr or IG Rems-Murr instead, this function fixes it
     * to match DIMB IG Rems-Murr and give you a correct result.
     * 
     * @param type $string
     * @return string = returns empty string on error/mismatch
     */
    public static function tsumFixDIMBQueryString ( $string ) {
        
        $match_full = 'DIMB IG';
        $match_half = 'IG';
        $match_esc_full = 'DIMB%20IG%20';
        $match_exc_half = 'IG%20';
        
        if ( str_starts_with( $string, $match_full ) ) {
            return $string;
        } else if ( str_starts_with( $string, $match_half ) ) {
            if ( str_starts_with( $string, $match_exc_half ) ) {
                return str_replace( $match_exc_half, $match_full . ' ', $string);
            }
            return 'DIMB ' . $string;
        } else if ( str_starts_with( $string, $match_esc_full ) ) {
            return str_replace( $match_esc_full, $match_full . ' ', $string);
        }  else if ( str_starts_with( $string, $match_exc_half ) ) {
            return str_replace( $match_exc_half, $match_full . ' ', $string);
        }  else {
            return $match_full . ' ' . $string;
        }
        
        return '';
    }
    
    /**
     * tsumIsDirEmpty ( $dir )
     * very small little helper function to check if a directory contains some files. Clears statcache to prevent false results
     * Kudos: https://stackoverflow.com/questions/7497733/how-can-i-use-php-to-check-if-a-directory-is-empty
     * 
     * @param string $dir = directory to check for contents
     * @return boolean = true | false if empty or not. returns also false if directory not present or if string points to a file 
     */
    public static function tsumIsDirEmpty ( $dir ) {
        
        clearstatcache();
        
        $fileItr = new \FilesystemIterator( $dir );

        if ( is_dir( $dir ) ) { 
            return !$fileItr->valid();
        } else {
            return false;
        }
    }
    /**
     * tsumFileExists( $filename )
     * Simply wraps around php file_exists method. 
     * Will be probably extended later.
     * 
     * @param string $filename = file & path to check for
     * @return bool true if exists, false if not.
     */
    public static function tsumFileExists( $filename ) {
        
        clearstatcache();
        
        return file_exists( $filename );
        
    }
}
