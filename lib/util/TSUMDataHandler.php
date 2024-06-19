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

            if ( isset($result->$column) ) {
                $res = $exist === '' ? $column : ',' . $column;
                $exist .= $res;
            } else {
                $res = $abundandt === '' ? $column : ',' . $column;
                $abundandt .= $res;
            }
        }
        return ['exist' => $exist, 'missing' => $abundandt];
        
    }
    
    private function tsumCreateBackupFromTable( $table = 'postcodes', $backup_postfix = parent::TSUM_BACKUP_PFX ) {
        
        global $extdb;
        
        //check connection to external db, connect, if needed
        if ( !isset($extdb) || empty($extdb) ) {
            //load params
            $conParams = $this->tsumLoadConnectionParams();
            $extconnection = empty( $conParams ) ? false : $this->tsumConnectExternal( $conParams );
            
            if ( $extconnection === false ) {
               return false; 
            }    
        } 
        
        $original_table = $table === 'postcodes' ? parent::TSUM_TAB_PC_NAME : parent::TSUM_TAB_IG_NAME;
        $new_table = $original_table . parent::TSUM_BACKUP_PFX;
        
        $queries = [ 
            "CREATE TABLE $new_table LIKE $original_table", 
            "INSERT INTO $new_table SELECT * FROM $original_table",
            "DROP TABLE IF EXISTS $new_table" ];
        
        //run queries for backup
        $result = $extdb->query( $queries[2] ); //drop old backup
        $result = $extdb->query( $queries[0] ); //create new backup
        
        if ( $result === false ) {
            return false;
        } else {
            $result = $extdb->query( $queries[1] );
            
            return $result === false ? false : true;
            
        }
        
        return false;
        
    }    
    
    private function tsumUpdateTable( $table = 'postcodes') {
        
        global $extdb;        
       
        $tableToUpdate = $table === 'postcodes' ? parent::TSUM_TAB_PC_NAME : parent::TSUM_TAB_IG_NAME;
        $columnDefinitions = $table === 'postcodes' ? parent::TSUM_TAB_PC_DEF_COLS : parent::TSUM_TAB_IGS_DEF_COLS;
        $missingCols = $this->tsumGetisTableUptoDate( $table );

        $appendCol = '';
        $colsSQL = '';
        
        if ( $missingCols === false ) {
            return false;
        } else {
            if ( !isset($missingCols['missing']) || empty($missingCols['missing']) ) {
                return false;
            } else {
                //create array from commasep strings
                $colsArray = [ 'exist' => explode( ",", $missingCols['exist'] ), 'missing' => explode( ",", $missingCols['missing'] )];
                //insert after this one                 
                $appendCol = isset( $missingCols['exist'] ) && !empty( $missingCols['exist'] ) ? 
                        $colsArray['exist'][ array_key_last( $colsArray['exist'] ) ] : '';
                
                //foreach cycle throhuh missing array and append to colsSQL
                foreach( $colsArray['missing'] as $column ) {
                    
                    $statement = "ADD COLUMN $column " . $columnDefinitions[ $column ] . " DEFAULT -1 AFTER $appendCol";
                    
                    if ( empty($colsSQL) ) {
                        $colsSQL = $statement;
                        $appendCol = $column;
                    }
                    else {
                        $colsSQL .= ', ' . $statement;
                        $appendCol = $column;
                    }
                }
            }
        }
        $query = "ALTER TABLE " . $tableToUpdate . " " . $colsSQL . ';';
        
        //execute query
        if ( !empty( $query ) ) {
            
            $result = $extdb->query( $query );
            
            if ( $result === false ) {
                return false;
            } else {
                return true;
            }
            
        } else {
            return false;
        } 
        return false;      
    }
    
    private function tsumPerformCSVImport( $table, $delete = true ) {
        
    }
    
    public function tsumPrintConnectionDataTable() {
        
        //get global extdb
        global $extdb;
        
        //use helpers to check paths' existance
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMHelpers.php';
        
        //query database
        $conParams = $this->tsumLoadConnectionParams();
        
        $prefixedTable = $this->tsumPFX . $this->tsumParamTable;    
        
        //error msg
        $tableError = esc_html__('Something went wrong checking the table!', 'tsu-mapconnect');
        
        //get $_POST data
        $update = [ parent::TSUM_TAB_IG_NAME => false, parent::TSUM_TAB_PC_NAME => false ]; //update
        $import = [ parent::TSUM_TAB_IG_NAME => false, parent::TSUM_TAB_PC_NAME => false ]; //import
        $fileimport = [ parent::TSUM_TAB_IG_NAME => false, parent::TSUM_TAB_PC_NAME => false ]; //csv file import
        
        //check nonce and set update
        if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'update-table-columns' ) ) {
        
            //set update
            $update[ parent::TSUM_TAB_IG_NAME ] = isset( $_POST[parent::TSUM_TAB_IG_NAME] ) 
                    && $_POST[parent::TSUM_TAB_IG_NAME] === 'UPDATE' ? true : false;    

            $update[ parent::TSUM_TAB_PC_NAME ] = isset( $_POST[parent::TSUM_TAB_PC_NAME] ) 
                    && $_POST[parent::TSUM_TAB_PC_NAME] === 'UPDATE' ? true : false;               
            
        }  
        //set import
        if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'import-table-columns' ) ) {
        
            //set import
            $import[ parent::TSUM_TAB_IG_NAME ] = isset( $_POST[parent::TSUM_TAB_IG_NAME] ) 
                    && $_POST[parent::TSUM_TAB_IG_NAME] === 'IMPORT' ? true : false;    

            $import[ parent::TSUM_TAB_PC_NAME ] = isset( $_POST[parent::TSUM_TAB_PC_NAME] ) 
                    && $_POST[parent::TSUM_TAB_PC_NAME] === 'IMPORT' ? true : false;               
            
        }  
        //set csv import
        if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'fileimport-table-columns' ) ) {
        
            //set csv import
            $fileimport[ parent::TSUM_TAB_IG_NAME ] = isset( $_POST[parent::TSUM_TAB_IG_NAME] ) 
                    && $_POST[parent::TSUM_TAB_IG_NAME] === 'FILEIMPORT' ? true : false;    

            $fileimport[ parent::TSUM_TAB_PC_NAME ] = isset( $_POST[parent::TSUM_TAB_PC_NAME] ) 
                    && $_POST[parent::TSUM_TAB_PC_NAME] === 'FILEIMPORT' ? true : false;               
            
        }           
        
        //table updating
        if ( $update[ parent::TSUM_TAB_IG_NAME ] === true || $update[ parent::TSUM_TAB_PC_NAME  ] === true ) {
            
            $update_table = $update[ parent::TSUM_TAB_PC_NAME  ] === true ? 'postcodes' : 'igs';
            $backuped = $this->tsumCreateBackupFromTable( $update_table );
            
            //continue if backup worked
            if ( $backuped === true ) {
                //do the regular updating stuff
                $colUpdate = $this->tsumUpdateTable( $update_table );      
                
                if ( $colUpdate === false ) {
                    add_settings_error( 'tsumMCOptions', '2', esc_html__( 'Database table columns could not be updated!', 'tsu-mapconnect' ) );
                }
                
            } else {
                add_settings_error( 'tsumMCOptions', '2', esc_html__( 'Backup of database table failed, update not possible!', 'tsu-mapconnect' ) );
            }    
        }    
        
        if ( $fileimport[ parent::TSUM_TAB_IG_NAME ] === true || $fileimport[ parent::TSUM_TAB_PC_NAME  ] === true ) { 
            
            $update_table = $update[ parent::TSUM_TAB_PC_NAME  ] === true ? 'postcodes' : 'igs';
            
            //TODO: perform data import, write function -> do database backup before
            $this->tsumPerformCSVImport( $update_table );
            
        }
        
        echo 'FILEIMPORT SET: IG: ' . $fileimport[ parent::TSUM_TAB_IG_NAME ] . ' PLZ: ' . $fileimport[ parent::TSUM_TAB_PC_NAME ];
        
        if ( !empty( $conParams ) ): ?> 
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
                                    $this->tsumRenderTableFormActionButton( parent::TSUM_TAB_IG_NAME );
                                }
                                $this->tsumRenderTableFormActionButton( parent::TSUM_TAB_IG_NAME, 'import' );                                
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
                                    $this->tsumRenderTableFormActionButton( parent::TSUM_TAB_PC_NAME );
                                }
                                $this->tsumRenderTableFormActionButton( parent::TSUM_TAB_PC_NAME, 'import' );
                            ?>
                        </td>
                    </tr>    
                    <tr>
                        <td><?php echo esc_html__( 'CSV import directory', 'tsu-mapconnect' );  ?></td>
                        <td>
                            <?php 
                                echo TSU_MC_PLUGIN_PATH . 'data/csv/';
                                if ( \lib\util\TSUMHelpers::tsumIsDirEmpty( TSU_MC_PLUGIN_PATH . 'data/csv/' ) ) {
                                    echo '<br><i>' . esc_html__( 'No CSV file found in directory.', 'tsu-mapconnect' ) . '</i>';
                                } else {
                                    $this->tsumRenderTableFormActionButton( parent::TSUM_TAB_PC_NAME, 'fileimport' );
                                }
                            ?>                           
                        </td>
                    </tr>                     
                </tbody>
            </table>
            <p><i><?php echo esc_html__('If you press the "import"-button, data from the csv file stored at the import location of the plugin will be loaded into the according table. Only do this if you are sure what you are doing. The files must be named correctly, e.g. csv_import_plz.csv or csv_import_section.csv.', 'tsu-mapconnect') ?></i></p>
        <?php endif;
    }
    
    //creates a form around a button to perform table ops. $type = 'update' || 'import'
    private function tsumRenderTableFormActionButton( $table, $type = 'update' ) {
        
        $label = $type ? esc_html__( ucfirst( $type ), 'tsu-mapconnect' ) : '';  
        
        if ( !empty($label) ): ?>
            <div style="padding-top: 0.5rem">
                <form id="form_<?php echo $type ?>_table_<?php echo $table ?>" method="post">
                    <?php wp_nonce_field( $type . '-table-columns' ); ?>
                    <input type="hidden" id="<?php echo $table ?>" name="<?php echo $table ?>" value="<?php echo strtoupper( $type ) ?>">
                    <button type="submit" id="submit_<?php echo $type ?>_table_<?php echo $table ?>" class="button button-small button-primary">
                        <?php echo $label; ?>
                    </button>
                </form>
            </div>
        <?php endif;
    }
}
