<?php
namespace lib;

/**
 * Description of TSUMPCCore
 * 
 * Core class which is instanciated when the plugin is loaded
 *
 * @author marconagel
 */
defined( 'ABSPATH' ) or die( 'Direct access not allowed!' );

class TSUMCCore {
    public function __construct() {
        //libs
        require_once TSU_MC_PLUGIN_PATH . '/lib/ui/TSUMMetaboxes.php';
        require_once TSU_MC_PLUGIN_PATH . '/lib/api/TSUMAPIOutput.php';
        
        //register metafields for Guteberg blocks
        add_action( 'init', array( $this, 'tsumRegisterMetafields' ) );
        //load Gutenberg blocks
        add_action( 'init', array( $this, 'tsumRegisterBlocks' ) );
        //create metabox for classic editor
        $tsumMBXPage = new ui\TSUMMetaboxes();
        //start broadcasting data to api endpoints       
        $tsumAPI = new api\TSUMAPIOutput();
    }  
    /**
     * tsumRegisterMetafields()
     * 
     * function to register the metafields needed in Gutenberg on init
     */
    public function tsumRegisterMetafields() {
        
        $metafields =   [
                            '_meta_fields_tsum_areaname',
                            '_meta_fields_tsum_areapcs',
                            '_meta_fields_tsum_arealogo',
                            '_meta_fields_tsum_areadesc',
                            '_meta_fields_tsum_areacontact',
                            '_meta_fields_tsum_arealink',
                            '_meta_fields_tsum_areaactivities',
                            '_meta_fields_tsum_areasocmedia'                            
                        ];
        
        foreach( $metafields as $metafield ){
            //setup sanization
            $sanitizeType = 'sanitize_text_field';
            
            if ( $metafield == '_meta_fields_tsum_areacontact' ){
                $sanitizeType = 'sanitize_email';
            } else if ( $metafield == '_meta_fields_tsum_arealink' ){
                $sanitizeType = 'sanitize_url';
            } else if ( $metafield == '_meta_fields_tsum_areadesc' ){
                $sanitizeType = 'meta_box_sanitize';
            }
            
            // Pass an empty string to register the meta key across all existing post types. TODO: CHANGE THIS TO PAGE later
            register_post_meta( '', $metafield, array(
                'show_in_rest' => true,
                'type' => 'string',
                'single' => true,
                'sanitize_callback' => $sanitizeType,
                'auth_callback' => function() { 
                    return current_user_can( 'edit_posts' );
                }
            ));
        }
    }
    /**
     * tsumRegisterBlocks()
     * 
     * function to register Gutenberg blocks belonging to plugin
     */
    function tsumRegisterBlocks() {
            register_block_type( TSU_MC_PLUGIN_PATH. 'blocks/tsu-mapconnect-meta-block/' ); //metadata block
            register_block_type( TSU_MC_PLUGIN_PATH. 'blocks/tsu-mapconnect-map-block/' ); //mapplication block for map placement
    }    
}
