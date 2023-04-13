<?php
namespace lib\util;

/**
 * Description of TSUMCleanUp
 * 
 * Static helper functions for clean up, sanitize and so on...
 *
 * @author marconagel
 */
class TSUMCleanUp {
    /**
     * tsumCleanPCString ( $string )
     * --
     * cleans up postcode string to match format.
     * Will convert comma-separated string to array, remove false elements and return
     * array with cleaned string at [0] and notice at [1] - notice will be empty on success
     * 
     * @param string $string = comma-separated string with postcodes
     * @param int $length = postcode length. Default: 5 for germany
     * @return array
     */
    public static function tsumCleanPCString ( $string, $length = 5 ) {
        
        $pcArray = explode(',', self::tsumTrimCommaPlus( $string ) );
        $cleanArr = [];
        $notes = '';
        $msg = esc_html__( 'Error with handling input according postcodes. Some data has been filtered out.', 'tsu-mapconnect' );
        
        foreach( $pcArray as $pcCode ) { 
            
            $valCode = trim($pcCode);
            
            if ( is_numeric( $valCode ) ){
                if ( strlen( $valCode ) <= $length ) {
                    //valid pc - append to string
                    array_push($cleanArr, $valCode);
                }
                else {
                    if ( $notes == '' ) {
                        $notes = $msg;
                    }
                }
            }
            else {
                if ( $notes == '' ) {
                    $notes = $msg;
                }                
            }
        }
        
        return [ 0 => implode( ',', $cleanArr ), 1 => $notes ];
    }
    /**
     * tsumTrimCommaPlus ( $string )
     * --
     * Double trim: first trims leading and trailing " \n\r\t\v\x00" (default trim),
     * then trims leading or trailing commas to prevent empty array keys in API
     * 
     * @param string $string = comma-separated string
     * @return string = trimmed string
     */
    public static function tsumTrimCommaPlus ( $string ) {
        //perform double trim on comma-separated string to prevent empty array keys and other stuff
        $trimmedString = trim( $string );
        return trim( $trimmedString, ',' );
    }
}
