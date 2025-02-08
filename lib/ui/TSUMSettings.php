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
    
    private $db_pfx = '';
    
    public function __construct() {     
        //get db prefix
        global $wpdb;
        $this->db_pfx = $wpdb->prefix;
        //add menu for setting page
        add_action( 'admin_menu', array( $this, 'tsumSettingsPage' ) ); 
        //register settings
        add_action( 'admin_init', array( $this, 'tsumRegisterSettings' ) );
        //load data handler
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMDataHandler.php';
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
        
        //load options
        $options = get_option( 'tsumMCOptions' );
        $tsumDataHandler = new \lib\util\TSUMDataHandler( isset( $options['tsum_general_setting_db_table'] ) ? $options['tsum_general_setting_db_table'] : '' ); 
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'MapConnect Settings', 'tsu-mapconnect' ); ?></h1>
            <form action="options.php" method="post">
                <?php 
                settings_fields( 'tsumMCOptions' );
                do_settings_sections( 'tsu-mapconnect' ); ?>
                <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
            </form>
            <p></p>
            <hr>
            <h2><?php echo esc_html__( 'Database connection overview', 'tsu-mapconnect' ); ?></h2>
            <?php 
                if ( !isset( $options['tsum_general_setting_db_simple'] ) || $options['tsum_general_setting_db_simple'] !== '1' ) {
                    $tsumDataHandler->tsumPrintConnectionDataTable();
                }
                else {
                    $tsumDataHandler->tsumPrintSimpleConnectionDataTable();
                }
            ?>
        </div>
        <?php
    }  
    public function tsumRegisterSettings() {
        
        $args = array( 'sanitize_callback' => array( $this, 'tsumMCOptionsValidate' ) );
        
        register_setting( 'tsumMCOptions', 'tsumMCOptions', $args );
        
        add_settings_section( 
                'tsum_general_settings', 
                esc_html__( 'General Settings', 'tsu-mapconnect' ), 
                array( $this, 'tsumGenSettingsSectionText' ), 
                'tsu-mapconnect' );

        add_settings_field( 
                'tsum_general_setting_pc_output', 
                esc_html__( 'enable postcode field in Metabox', 'tsu-mapconnect' ), 
                array( $this, 'tsumGenSettingsPCOutput' ), 
                'tsu-mapconnect', 
                'tsum_general_settings' );
        
        add_settings_field( 
                'tsum_general_setting_db_simple', 
                esc_html__( 'use wordpress table instead of external database for postcode data', 'tsu-mapconnect' ), 
                array( $this, 'tsumGenSettingsDBSimple' ), 
                'tsu-mapconnect', 
                'tsum_general_settings' );         
        
        add_settings_field( 
                'tsum_general_setting_db_table', 
                esc_html__( 'database table holding parameters', 'tsu-mapconnect' ), 
                array( $this, 'tsumGenSettingsDBTable' ), 
                'tsu-mapconnect', 
                'tsum_general_settings' );                   
            
    } 
    
    //TODO: add tsum_general_setting_db_table to validation
    public function tsumMCOptionsValidate( $input ) {
        
        $newinput = [];
        $sql_table_pattern = "/^[a-zA-Z_][a-zA-Z0-9_]*$/";
        $success_msg = esc_html__( 'Settings successfully updated!', 'tsu-mapconnect' );
        $errors = false;
        $msg = [];
                
        $validate = [];
        $validate['pc'] = $input['tsum_general_setting_pc_output'] ?? null;
        $validate['simplemode'] = $input['tsum_general_setting_db_simple'] ?? null;
        $validate['tablename'] = $input['tsum_general_setting_db_table'] ?? null;
        
        //validate boolean
        if ($validate['pc'] != null) {
            if ( $input['tsum_general_setting_pc_output'] === '0' || $input['tsum_general_setting_pc_output'] === '1' ) {
                $newinput['tsum_general_setting_pc_output'] = $input[ 'tsum_general_setting_pc_output' ];       
            } else {
                $newinput['tsum_general_setting_pc_output'] = '1';
                $errors = true;
                $msg[0] = esc_html__( 'Setting for postcode output could not be validated. Resetted field to default value.', 'tsu-mapconnect' );
            }
        }
        if ($validate['simplemode'] != null) {
            if ( $input['tsum_general_setting_db_simple'] === '0' || $input['tsum_general_setting_db_simple'] === '1' ) {
                $newinput['tsum_general_setting_db_simple'] = $input[ 'tsum_general_setting_db_simple' ];       
            } else {
                $newinput['tsum_general_setting_db_simple'] = '1';
                $errors = true;
                $msg[0] = esc_html__( 'Setting for simple database could not be validated. Resetted field to default value.', 'tsu-mapconnect' );
            }
        }        
        if ($validate['tablename'] != null) {
            //validate sql table name
            if ( preg_match($sql_table_pattern, $input['tsum_general_setting_db_table']) ) {
                $newinput['tsum_general_setting_db_table'] = $input[ 'tsum_general_setting_db_table' ];          
            } else {
                $newinput['tsum_general_setting_db_table'] = "";
                $errors = true;
                $msg[1] = esc_html__( 'Inputted name of database table is not allowed.', 'tsu-mapconnect' );
            }
        }
        
        if ($errors) {
            $error_code = isset($msg[1]) ? 1 : 0;
            $error_desc = '';
            
            if (count($msg) > 0) {
                $error_desc .= '<ul>';
                $error_desc .= '<li>' . implode('</li><li>', $msg) . '</li>';
                $error_desc .= '</ul>';
            } else {
                $error_desc .= esc_html__('Undefined error(s) - contact some higher instance.', 'tsu-mapconnect');
            }

            $message = esc_html__( 'The following error(s) occured:', 'tsu-mapconnect' ) . $error_desc;            
            add_settings_error( 'tsumMCOptions', $error_code, $message );  
            
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

    public function tsumGenSettingsDBSimple() {
        $options = get_option( 'tsumMCOptions' );
        ?> 
            <input 
                id='tsum_general_setting_db_simple' 
                name='tsumMCOptions[tsum_general_setting_db_simple]' 
                type='checkbox' 
                value='1'
                <?php checked( '1', $options['tsum_general_setting_db_simple'] ?? 0 ); ?>
            /> 
        <?php        
    }    
    
    public function tsumGenSettingsDBTable() {
        
        $options = get_option( 'tsumMCOptions' );
        ?>  
            <code class="wp-prefix"><?php echo $this->db_pfx; ?></code>
            <input 
                id='tsum_general_setting_db_table' 
                name='tsumMCOptions[tsum_general_setting_db_table]' 
                type='text' 
                value='<?php echo isset( $options['tsum_general_setting_db_table'] ) ? $options['tsum_general_setting_db_table'] : '' ?>'
                placeholder='<?php echo esc_html__( 'enter db table name', 'tsu-mapconnect' ); ?>'
            /> 
            <p class="description"><?php echo esc_html__( 'database table holding parameters for external postcode database access. Prefix as shown before input field will be added automatically.', 'tsu-mapconnect' ); ?></p>
        <?php         
    }
    
}
