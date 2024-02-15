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
        //register settings
        add_action( 'admin_init', array( $this, 'tsumRegisterSettings' ) );
    }
    
    public function tsumSettingsPage() {
        add_options_page( 
                esc_html__( 'MapConnect Settings', 'tsu-mapconnect' ), 
                esc_html__( 'MapConnect', 'tsu-mapconnect' ), 
                'manage_options', 
                'tsu-mapconnect', 
                array( $this, 'tsumRenderSettingsPage' ) 
            );
    }
    
    public function tsumRenderSettingsPage() {
        ?>
        <h2><?php echo esc_html__( 'MapConnect Settings', 'tsu-mapconnect' ); ?></h2>
            <form action="options.php" method="post">
                <?php 
                settings_fields( 'tsumMCOptions' );
                do_settings_sections( 'tsu-mapconnect' ); ?>
                <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
            </form>
        <?php
    }  
    public function tsumRegisterSettings() {
        register_setting( 'tsumMCOptions', 'tsumMCOptions', 'tsumMCOptionsValidate' );
        add_settings_section( 'tsum_general_settings', esc_html__( 'General Settings', 'tsu-mapconnect' ), array( $this, 'tsumGenSettingsSectionText' ), 'tsu-mapconnect' );

        add_settings_field( 'tsum_general_setting_pc_output', esc_html__( 'enable postcode field in Metabox', 'tsu-mapconnect' ), array( $this, 'tsumGenSettingsPCOutput' ), 'tsu-mapconnect', 'tsum_general_settings' );
    } 
    
    public function tsumMCOptionsValidate( $input ) {
        
        $newinput = [];
        
        if ( $input[ 'tsum_general_setting_pc_output' ] === '0' || $input[ 'tsum_general_setting_pc_output' ] === '1' ) {
            $newinput['tsum_general_setting_pc_output'] = $input[ 'tsum_general_setting_pc_output' ];
        } else {
            $newinput['tsum_general_setting_pc_output'] = '1';
        }

        return $newinput;
    }
    
    public function tsumGenSettingsSectionText() {
        echo '<p>' . esc_html__( 'Here you can do general settings for the map connect plugin, like enabling/disabling of postcode input per page.', 'tsu-mapconnect' ) . '</p>';
    }
    
    public function tsumGenSettingsPCOutput() {
        $options = get_option( 'tsumMCOptions' );
        ?> 
            <input 
                id='tsum_general_setting_pc_output' 
                name='tsumMCOptions[tsum_general_setting_pc_output]' 
                type='checkbox' 
                value='1'
                <?php checked( '1', $options['tsum_general_setting_pc_output'] ?? 0 ); ?>
            /> 
        <?php        
    }
    
}
