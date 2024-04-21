<?php
namespace lib\util;

/**
 * Description of TSUMDataHandler
 * 
 * Class for handling external data stuff
 *
 * @author marconagel
 */
defined( 'ABSPATH' ) or die( 'Direct access not allowed!' );

//require config
require_once TSU_MC_PLUGIN_PATH . 'lib/config/TSUMDBSettings.php';

class TSUMDataHandler extends \lib\config\TSUMDBSettings {
    
    //parameters
    private $tsumParamTable;
    private $tsumParams;
    private $tsumPFX;
    private $tsumWPDB;
    
    //table names
    private $igTable = 'events_igs';
    private $pcTable = 'events_ig_plz';
    
   /**
    * 
    * @param string $table - Database table in WordPress db holding params to connect to external table
    */
    public function __construct ( $table, $params = parent::TSUM_CON_SETTINGS ) {       
        global $wpdb;
        
        $this->tsumWPDB = $wpdb;
        $this->tsumParamTable = $table;
        $this->tsumParams = $params;
        $this->tsumPFX = $wpdb->prefix;
    }
    
    private function tsumLoadConnectionParams() {
        
        $prefixedTable = $this->tsumPFX . $this->tsumParamTable;
               
        $paramData = $this->tsumWPDB->get_results( "SELECT parameter, value FROM $prefixedTable" );   
        
        $paramArray = [];
        
        if ( $paramData === null ) {
            
            add_settings_error( 
                        'tsumMCOptions', 
                        'db_table_not_found', 
                        esc_html__( 'Table not found or defined. No connection established.', 'tsu-mapconnect' ) 
                    ); 
            
            
        } else {
                foreach ($paramData as $param) {

                    if ($this->tsumParams['host'] == $param->parameter) {
                        
                        $paramArray['host'] = [ 
                                'label' => esc_html__('Database Host', 'tsu-mapconnect'),
                                'value' => $param->value
                            ];
                        
                    } else if ($this->tsumParams['db'] == $param->parameter) {
                        
                        $paramArray['db'] = [ 
                                'label' => esc_html__('Database Name', 'tsu-mapconnect'),
                                'value' => $param->value
                            ];                        

                    } else if ($this->tsumParams['user'] == $param->parameter) {
                        
                        $paramArray['user'] = [ 
                                'label' => esc_html__('Database User', 'tsu-mapconnect'),
                                'value' => $param->value
                            ];                           

                    } else if ($this->tsumParams['password'] == $param->parameter) {
                        
                         $paramArray['password'] = [ 
                                'label' => esc_html__('Database Pass', 'tsu-mapconnect'),
                                'value' => $param->value
                            ];                          

                    }
                    
                }               
        }
        
        return $paramArray;
     
    }
    
    private function tsumConnectExternal( $params ) {
        
        global $extdb;

        if ( empty($params) ) {
            
            return false;
            
        } else {
            
            $extdb = new \wpdb( 
                        $params['user']['value'], 
                        $params['password']['value'], 
                        $params['db']['value'], 
                        $params['host']['value'] 
                    );
            
            return true;
            
        }       
        
        return false;
      
    }
    
    public function tsumRetrievePostCodesByAname( $areaname ) {
        
        global $extdb;
        
        //use helpers to correct input
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMHelpers.php';
        
        //messages array
        $msg = [ 
            'disabled' => esc_html__('Postcode output has been disabled in settings.', 'tsu-mapconnect'), 
            'notable' => esc_html__('Parameter table for external connection not set or found', 'tsu-mapconnect'),
            'noconparams' => esc_html__('Connection impossible - No parameters for connection.', 'tsu-mapconnect'),
            'conerror' => esc_html__('Cannot connect to external database - unknown connection error.', 'tsu-mapconnect')
            ];
        
        //fix areaname
        $fixedAName = \lib\util\TSUMHelpers::tsumFixDIMBQueryString($areaname);
        
        //check how we should do it
        if ( !isset( $this->tsumParamTable ) || $this->tsumParamTable === '' || $this->tsumParamTable === false ) {
            $postcodes = [ 
                -1, 
                $fixedAName, 
                $msg['disabled'],
                $msg['notable']
                . ', prefix: ' 
                . $this->tsumPFX . ', ' . esc_html__('table', 'tsu-mapconnect') 
                . ': ' . ( $this->tsumParamTable === '' ? esc_html__('empty', 'tsu-mapconnect') : $this->tsumParamTable )
                ];
        } else {
            //get connection parameters
            $conParams = $this->tsumLoadConnectionParams();
            
            if ( empty( $conParams ) ) {
                //no params found
                $postcodes = [ -1, $fixedAName, $msg['disabled'], $msg['noconparams'] ]; 
            } else {
                //connect, we have params
                $extconnection = $this->tsumConnectExternal( $conParams );               
                //get the codes now!
                if ( $extconnection === true ) {
                    //all is good at this point, query external db
                    $postcodes = $this->tsumGetPCArrayFromDB( $fixedAName, $extdb ); 
                } else {
                    $postcodes = [ -1, $fixedAName, $msg['disabled'], $msg['conerror'] ]; 
                }
            }           
        }
        
        return $postcodes;
    }
    
    private function tsumGetPCArrayFromDB( $areaname, $db ) {
        
        //query ext db for the area id
        $areaid = $db->get_var( $db->prepare( "SELECT id FROM events_igs WHERE name = %s", $areaname ) ); 
        //query ext db for postcodes based on id
        $postcodes = $db->get_results( $db->prepare( "SELECT * FROM events_ig_plz WHERE ig = %d", $areaid ) ); 
        
        //create array of postcodes
        $pcArray = [];
        foreach ($postcodes as $pc) {
            array_push( $pcArray, $pc->start );
        }
        
        return $pcArray;
        
    }     
      
    //allowed: sections | postcodes as option for tables - global $extdb must be set before using this function
    private function tsumGetisTableUptoDate( $table = 'postcodes' ) {
        
        //get extdb
        global $extdb;
        
        //single row query to check if all is there
        $query = "SELECT * FROM";
        $tableName = $table === 'postcodes' ? parent::TSUM_TAB_PC_NAME : parent::TSUM_TAB_IG_NAME;
        $cols = $table === 'postcodes' ? parent::TSUM_TAB_PC_COLS : parent::TSUM_TAB_IGS_COLS;
        
        $result = $extdb->get_row( $query . " " . $tableName );
        
        $exist = '';
        $abundandt = '';

        foreach ($cols as $column) {

            if (isset($result->$column)) {
                $res = $exist === '' ? $column : ',' . $column;
                $exist .= $res;
            } else {
                $res = $abundandt === '' ? $column : ',' . $column;
                $abundandt .= $res;
            }
        }
        return ['exist' => $exist, 'missing' => $abundandt];
        
    }
    
    public function tsumPrintConnectionDataTable() {
        
        //get global extdb
        global $extdb;
        
        //query database
        $conParams = $this->tsumLoadConnectionParams();
        
        $prefixedTable = $this->tsumPFX . $this->tsumParamTable;    
        
        //error msg
        $tableError = esc_html__('Something went wrong checking the table!', 'tsu-mapconnect');
        
        //get $_POST data
        $update = [ parent::TSUM_TAB_IG_NAME => false, parent::TSUM_TAB_PC_NAME => false ];
        
        //check nonce and set update
        if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'update-table-columns' ) ) {
        
            $update[ parent::TSUM_TAB_IG_NAME ] = isset( $_POST[parent::TSUM_TAB_IG_NAME] ) 
                    && $_POST[parent::TSUM_TAB_IG_NAME] === 'UPDATE' ? true : false;    

            $update[ parent::TSUM_TAB_PC_NAME ] = isset( $_POST[parent::TSUM_TAB_PC_NAME] ) 
                    && $_POST[parent::TSUM_TAB_PC_NAME] === 'UPDATE' ? true : false;               
            
        }
        
        //TODO: Implement table updating
        
        if ( !empty( $conParams ) ): ?> 
            <div>
                <?php echo 'Update for ' . parent::TSUM_TAB_IG_NAME . ':' . ( $update[ parent::TSUM_TAB_IG_NAME ] === true ? ' true' : ' false' ) ?>
                <?php echo 'Update for ' . parent::TSUM_TAB_PC_NAME . ':' . ( $update[ parent::TSUM_TAB_PC_NAME  ] === true ? ' true' : ' false' ) ?>
            </div>
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <td><b><?php echo esc_html__('General Status', 'tsu-mapconnect'); ?></b></td>
                        <td>&nbsp;</td>
                    </tr>                    
                    <tr>
                        <td><?php echo esc_html__( 'Parameters Table', 'tsu-mapconnect' );  ?></td>
                        <td><?php echo $prefixedTable; ?></td>
                    </tr>    
                    <tr>
                        <td><?php echo $conParams['host']['label']; ?></td>
                        <td><?php echo $conParams['host']['value'] ?></td>
                    </tr>  
                    <tr>
                        <td><?php echo $conParams['db']['label']; ?></td>
                        <td><?php echo $conParams['db']['value'] ?></td>
                    </tr>   
                    <tr>
                        <td><?php echo $conParams['user']['label']; ?></td>
                        <td><?php echo $conParams['user']['value'] ?></td>
                    </tr>  
                    <tr>
                        <td><?php echo $conParams['password']['label']; ?></td>
                        <td>********</td>
                    </tr>      
                    <tr>
                        <td><?php echo esc_html__('Connection Status', 'tsu-mapconnect'); ?></td>
                        <td>
                            <?php 

                                //connect database
                                $extconnection = $this->tsumConnectExternal( $conParams );

                                if ( isset($extdb) && !empty($extdb) && $extconnection === true ) {
                                    echo esc_html__('Connection to external database established!', 'tsu-mapconnect');
                                }
                                else {
                                    echo esc_html__('No connection to external database.', 'tsu-mapconnect');
                                }
                                
                            ?>                            
                        </td>
                    </tr>   
                    <tr>
                        <td><b><?php echo esc_html__('Status of Tables', 'tsu-mapconnect'); ?></b></td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: middle;"><?php echo esc_html__('Sections table', 'tsu-mapconnect') . ' (' . parent::TSUM_TAB_IG_NAME . ')'; ?></td>
                        <td style="vertical-align: middle;">
                            <?php 
                                $sectionstatus = $this->tsumGetisTableUptoDate( 'sections' );
                                echo $sectionstatus === false ? $tableError : 
                                        esc_html__('Existing', 'tsu-mapconnect') . ': ' . $sectionstatus['exist'] .
                                        ( empty( $sectionstatus['missing'] ) ? '' : 
                                                ', <span style="color: #d63638;">' . 
                                                esc_html__('Missing', 'tsu-mapconnect') . ': ' . 
                                                $sectionstatus['missing'] . '</span>' ); 
                                
                                if ( !empty( $sectionstatus['missing'] ) ) { 
                                    $this->tsumRenderUpdateTableFormButton( parent::TSUM_TAB_IG_NAME );
                                }                                
                            ?>
                        </td>                        
                    </tr>   
                    <tr>
                        <td style="vertical-align: middle;"><?php echo esc_html__('Postcodes table', 'tsu-mapconnect') . ' (' . parent::TSUM_TAB_PC_NAME . ')'; ?></td>
                        <td style="vertical-align: middle;">
                            <?php 
                                $pcstatus = $this->tsumGetisTableUptoDate();
                                echo $pcstatus === false ? $tableError : 
                                        esc_html__('Existing', 'tsu-mapconnect') . ': ' . $pcstatus['exist'] . 
                                        ( empty( $pcstatus['missing'] ) ? '' : 
                                                ', <span style="color: #d63638;">' . 
                                                esc_html__('Missing', 'tsu-mapconnect') . ': ' . 
                                                $pcstatus['missing'] . '</span>' ); 
                                
                                if ( !empty( $pcstatus['missing'] ) ) { 
                                    $this->tsumRenderUpdateTableFormButton( parent::TSUM_TAB_PC_NAME );
                                }
                            ?>
                        </td>
                    </tr>                       
                </tbody>
            </table>
        <?php endif;
    }
    
    private function tsumRenderUpdateTableFormButton( $table ) {
        ?>
            <div style="padding-top: 0.5rem">
                <form id="form_update_table_<?php echo $table ?>" method="post">
                    <?php wp_nonce_field('update-table-columns'); ?>
                    <input type="hidden" id="<?php echo $table ?>" name="<?php echo $table ?>" value="UPDATE">
                    <button type="submit" id="submit_update_table_<?php echo $table ?>" class="button button-small button-primary">
                        <?php echo esc_html__('Update', 'tsu-mapconnect'); ?>
                    </button>
                </form>
            </div>
        <?php
    }
}
