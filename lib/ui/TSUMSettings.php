<?php

namespace lib\ui;

/**
 * Description of TSUMSettings
 * 
 * Class to display the settings menu for the plugin to the frontend user. You can turn some things which the API will render on or off.
 * At the moment (feb 2024) this is only the postcode on demand of our client.
 * 
 * @author marconagel
 */
defined( 'ABSPATH' ) or die( 'Direct access not allowed!' );

class TSUMSettings {
    
    public function __construct() {      
        //add menu for setting page
        add_action( 'admin_menu', array( $this, 'tsumSettingsPage' ) );       
    }
    
    public function tsumSettingsPage() {
        add_options_page( 
                esc_html__( 'MapConnect Settings', 'tsu-mapconnect' ), 
                esc_html__( 'MapConnect Settings Page', 'tsu-mapconnect' ), 
                'manage_options', 
                'tsu-mapconnect', 
                array( $this, 'tsumRenderSettingsPage' ) 
            );
    }
    
    public function tsumRenderSettingsPage() {
        ?>
        <h2><?php echo esc_html__( 'MapConnect Settings', 'tsu-mapconnect' ); ?></h2>

        <?php
    }    
    
}
