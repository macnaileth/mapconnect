<?php
namespace lib\util;

/**
 * Description of TSUMMsgHandler
 * 
 * Messaging for errors, notices and console - collection of static functions
 *
 * @author marconagel
 */
defined( 'ABSPATH' ) or die( 'Direct access not allowed!' );

class TSUMMsgHandler {
    
    public static function tsumAPIReturnMessage ( $request, $code = 200 ) {
        
        switch ( $code ) {
            case 200:
                return  [ 
                            'status' => 200,
                            'code' => 'ok',
                            'message' => esc_html__( 'Request successful. You requested data for:', 'tsu-mapconnect' ) . ' ' . $request
                        ];
            case 404:
                return  [ 
                            'status' => 404,
                            'code' => 'not_found',
                            'message' => esc_html__( 'Your request did not return any data. You requested data for:', 'tsu-mapconnect' ) . ' ' . $request
                        ];
            default:
                return  [ 
                            'status' => 400,
                            'code' => 'bad_request',
                            'message' => esc_html__( 'Something went wrong with your request. Check again. If this keeps happening, contact the site admin:', 'tsu-mapconnect' ) . ' ' . get_bloginfo('admin_email')
                        ];
                
        }
        
    }
    
}
