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
        
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMHelpers.php';
        
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
    /**
     * tsumGetAreaByPcOrLocalityName( $PCLocality, $type = "POSTCODE")
     * 
     * @param string $PCLocality = postcode or locality name as string
     * @param string $type = POSTCODE | LOCALITY -> Defines where to look up. Default: POSTCODE 
     * @return array -> Area information as array for output via API
     */
    public function tsumGetAreaByPcOrLocalityName( $PCLocality, $type = "POSTCODE") {
        
        global $extdb;
        
        //check connection and connect $extdb if needed
        $connect = $this->tsumConnectExternal( $this->tsumLoadConnectionParams() );
        $AreaInfo = [];
        
        if( $connect === false ) {
                $AreaInfo[ 'error' ] = esc_html__('Database connection failed - data could not be retrieved. Contact API Administrator if this persists.', 'tsu-mapconnect');            
        } else {        
            //TODO: change is_numeric to regex --> because it only works for german pcs or similar
            if ( strtoupper( $type ) == "POSTCODE" && is_numeric( $PCLocality ) ){
                //fetch information from DB
                $pcRows = $extdb->get_results( $extdb->prepare( "SELECT * FROM events_ig_plz WHERE start = %s", $PCLocality ) );
                
                if( $extdb->last_error ) {
                    $AreaInfo[ 'error' ] = esc_html__('Error while requesting data for', 'tsu-mapconnect') . ': ' . $PCLocality;
                } else {
                    //insert data to return array
                    foreach ($pcRows as $pc) {
                        
                        //if we have multiple communities sharing the same postcode, build a comma-separated list.
                        $name = empty( $AreaInfo['community']['name'] ) ? $pc->name : ',' . $pc->name;
                        
                        //query ig info
                        $igInfo =  $extdb->get_results( $extdb->prepare( "SELECT * FROM events_igs WHERE id = %s", $pc->ig ) );
                        $AreaInfo['dimb-ig'] = [];
                        if( $extdb->last_error ) { 
                            $AreaInfo[ 'error' ] = esc_html__('Error while requesting data for', 'tsu-mapconnect') . ': DIMB IG';
                        } else {
                            foreach ($igInfo as $ig) {
                                $AreaInfo['dimb-ig'] = [
                                    "id" => $ig->id,
                                    "name" => $ig->name,
                                    "contact" => $ig->mail,
                                    "active" => $ig->aktiv == 1 ? true : false
                                ];                              
                            }
                        }
                        
                        $AreaInfo['community'] = [
                            "postcode" => $pc->start,
                            "name" => $name,
                            "district" => $pc->district,
                            "federalstate" => $pc->federalState
                        ];  
                    }
                    $AreaInfo['shared-entries'] = count( $pcRows );
                    //TODO: handle "nameless" cases with -1
                    
                }
                
            } else if ( strtoupper( $type ) == "LOCALITY" ) {

            } else {
                $AreaInfo[ 'error' ] = esc_html__('No valid type (Postcode or Locality) set. Contact API Administrator if this persists.', 'tsu-mapconnect');
            }
        }
        
        return $AreaInfo;
    }
    /**
     * tsumCheckAreaExists( $areaname )
     * 
     * @param string $areaname = Name of the area
     * @return bool | int = returns false or ID of area in database
     */
    public function tsumCheckAreaExists( $areaname ) {
        
        global $extdb;
        
        //check connection and connect $extdb if needed
        $connect = $this->tsumConnectExternal( $this->tsumLoadConnectionParams() );
        
        if ( $connect === false ) {
            return false;
        } else {
            //fix areaname       
            $fixedAName = \lib\util\TSUMHelpers::tsumFixDIMBQueryString($areaname);

            $areaid = $extdb->get_var( $extdb->prepare( "SELECT id FROM events_igs WHERE name = %s", $fixedAName ) );  

            if ( $areaid === null || !is_numeric( $areaid ) ) {
                return false;
            } else {
                return $areaid;
            } 
        }
        
        return false;
    }
    
    public function tsumRetrieveAvailableAreas() {
        
        global $extdb;
        
        //check connection and connect $extdb if needed
        $connect = $this->tsumConnectExternal( $this->tsumLoadConnectionParams() );
        
        if ( $connect === false ) {
            return false;
        } else {
            
            $arrayofAreas = [];
            
            //get available areas from database
            $query = "SELECT * FROM " . $this->igTable;
            //run query, no sanization needed in this case
            $result = $extdb->get_results( $query );
            
            foreach ($result as $row) {
                $igdata = [ 
                    'id' => $row->id,
                    'name' => $row->name,
                    'mail' => $row->mail,
                    'active' => $row->aktiv == 0 ? false : true
                        ];
                array_push( $arrayofAreas, $igdata );
            }
            
            return $arrayofAreas;
        }                
        return false;
    }
    
    public function tsumRetrievePostCodesByAname( $areaname ) {
        
        global $extdb;
        
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
    
    //set extendedData = true (default) to also retrieve name, district and federal state. 
    //Will use openPLZ to fill database if fields are -1. only done on first request.
    private function tsumGetPCArrayFromDB( $areaname, $db, $extendedData = true, $batchNum = 50 ) {
        
        //query ext db for the area id
        $areaid = $db->get_var( $db->prepare( "SELECT id FROM events_igs WHERE name = %s", $areaname ) ); 
        //query ext db for postcodes based on id
        $postcodes = $db->get_results( $db->prepare( "SELECT * FROM events_ig_plz WHERE ig = %d", $areaid ) ); 

        $firstRun = false;
        
        
        $arrForRegex = [];
       
        if ( $extendedData === true ) {
            foreach ($postcodes as $pc) {
                if ( $pc->name == '-1' ) {
                    $firstRun = true;
                    break;
                }
            }
            if ( $firstRun === true ) {
                //Function to retrieve all the data at once if on first run 
                //OPENPLZ needs regex, pc list should look like: ^(70173|71364|70134)
                $pccount = 1;
                $pcrowcount = $db->num_rows; //total rows in query
                $totalRequestArray = []; //total requests array
                $requestKeysNum = ceil( $pcrowcount / $batchNum ); //maximum keys
                $currentKey = 0; //key cursor of the totalRequestArray
                //build string for request first
                foreach ($postcodes as $pc) {                   
                    $arrForRegex[$currentKey] = isset ( $arrForRegex[$currentKey] ) ? $arrForRegex[$currentKey] . $pc->start . '|' : $pc->start . '|';
                    $pccount += 1;
                    
                    if ( $pccount === $batchNum ) {
                        $currentKey += 1;
                        $pccount = 1;
                    }                    
                } 
                //form & prepare the keys of the arrForRegex
                $formedArrForRegex['requeststrings'] = [];
                $formedArrForRegex['apidata'] = [];
                foreach ($arrForRegex as $key) {
                    //add request key
                    $formedArrForRegex['requeststrings'][$key] = '(' . rtrim( $key, '|' ) . ')';
                    //perform request to openplz.org
                    $jsonPLZ = \lib\util\TSUMHelpers::tsumGetLocation( '^' . $formedArrForRegex['requeststrings'][$key] );
                    $formedArrForRegex['apidata'] = empty( $formedArrForRegex['apidata'] ) ? $jsonPLZ[ 'data' ] : array_merge( $formedArrForRegex['apidata'], $jsonPLZ[ 'data' ] );
                }
                $arrForRegex = $formedArrForRegex; //overwrite array
                
                //TODO: make regex pattern shit - since openplz only supports 50 codes per request, we have to split
                $queryString = '';
                $reloadPCs = false;
                foreach ($arrForRegex['apidata'] as $key) {
                    $queryString = $db->prepare( 
                            "UPDATE events_ig_plz SET name = %s, district = %s, federalState = %s WHERE start = %s;", 
                            $key['name'], $key['district']['name'], $key['federalState']['name'],$key['postalCode']
                            );
                    //work around **FUCK WORDPRESS DB FUNCTIONS** run queries here...
                    $rowsAffected = $db->query( $queryString );
                    if ( $rowsAffected > 0 ) {
                        //we need to reload
                        $reloadPCs = true;
                    }
                }
                //if updates have been done, refresh $postcodes array before outputting
                if ( $reloadPCs === true ) {
                    $postcodes = $db->get_results( $db->prepare( "SELECT * FROM events_ig_plz WHERE ig = %d", $areaid ) ); 
                }            
                //return [ "querystring" => $queryString, "count total" => $pcrowcount, "count" => $pccount, "request keys" => $requestKeysNum ];
            }
        }
        
        //create array of postcodes
        $pcArray = [];
        //insert first run at 0
        //array_push( $pcArray, [ "dbPopulation" => $firstRun === true ? true : false ] );            
        foreach ($postcodes as $pc) {
            if ( $extendedData === true ) {
                array_push( $pcArray, [
                    "postcode" => $pc->start,
                    "name" => $pc->name,
                    "district" => $pc->district,
                    "federalstate" => $pc->federalState
                        ] );
            } else {
                array_push( $pcArray, $pc->start );
            }
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
    
    private function tsumPerformCSVImport( $tableCSV, $delete = true ) {
        
        global $extdb; 
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMMsgHandler.php';
           
        $file = fopen( TSU_MC_PLUGIN_PATH . 'data/csv/' . $tableCSV . '.csv', "r" );
        
        //handle the opened file
        $rowCount = 0;
        $data = [];
        
        while( ( $row = fgetcsv($file, 1000, ";") ) !== FALSE) {
            if( $rowCount > 0 ){
                //Sanitize Data and add
                $postCode = is_numeric( $row[0] ) ? $row[0] : false;
                $accIG = is_numeric( $row[1] ) ? $row[1] : "";
                
                if ( $postCode !== false ) {
                    $data[] = "('{$postCode}', -1, '{$accIG}')";
                }
            }
            $rowCount++;
        }  
        
        fclose( $file ); 
        //Delete file after closing
        
        if ( !unlink( TSU_MC_PLUGIN_PATH . 'data/csv/' . $tableCSV . '.csv' ) ) {
            TSUMMsgHandler::tsumLogToConsole( esc_html__('Import file could not be deleted after reading for import while import propably succeed.', 'tsu-mapconnect') );
        } else {
            TSUMMsgHandler::tsumLogToConsole( esc_html__('Import file deleted.', 'tsu-mapconnect'), 'color:green;' );
        }

        //insert data into database at according columns. Truncate first
        $query = "TRUNCATE TABLE " . $tableCSV;
        
        $result = $extdb->query( $query );
        
        if ( $result === false ) {
            return false;
        } else {
            //table truncated, continue and fill it
            
            //check if we have the plz DB and change column start to varchar for german postcodes
            
            if( $tableCSV === $this->pcTable ){
                
                $changeQuery = "ALTER TABLE " . $tableCSV . " MODIFY COLUMN start varchar(8)";
                $result = $extdb->query( $changeQuery );                 
                if ( $result === false ) {
                    return false;
                }                
            }
            
            if( count( $data ) > 0 ) {
                
                $insertDataString = implode(", ", $data); 
                //do not have to use wordpress prepare function here, because values are already sanitized above
                $insertQuery = "INSERT INTO " . $tableCSV . " (start, ende, ig) VALUES " . $insertDataString;
                
                $result = $extdb->query( $insertQuery );
                
                if ( $result === false ) {
                    return false;
                }
                        
            } else {
                return false;
            }
        }
        
        return $rowCount;
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
        
        //do import
        if ( $import[ parent::TSUM_TAB_IG_NAME ] === true || $import[ parent::TSUM_TAB_PC_NAME  ] === true ) { 
            
            $import_table = $import[ parent::TSUM_TAB_PC_NAME  ] === true ? $this->pcTable : $this->igTable;
            
            $backuped = $this->tsumCreateBackupFromTable( $import[ parent::TSUM_TAB_PC_NAME  ] === true ? 'postcodes' : 'igs' );
            
            //continue if backup worked
            if ( $backuped === true ) {
                //do the regular updating stuff
                $rowsImported = $this->tsumPerformCSVImport( $import_table );      
                
                if ( $rowsImported === false ) {
                    add_settings_error( 'tsumMCOptions', '2', esc_html__( 'No rows imported! Either file is empty or an error occured.', 'tsu-mapconnect' ) );
                }
                else {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Rows imported:', 'tsu-mapconnect') . ' ' . $rowsImported . '</p></div>';
                }
                
            } else {
                add_settings_error( 'tsumMCOptions', '2', esc_html__( 'Backup of database table failed, import was not possible!', 'tsu-mapconnect' ) );
            }               
            
            echo 'Table to import to: ' . $import_table . ' | ';
            
        }
        
        echo 'FILEIMPORT SET: IG: ' . $import[ parent::TSUM_TAB_IG_NAME ] . ' PLZ: ' . $import[ parent::TSUM_TAB_PC_NAME ];
        
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
                                if ( \lib\util\TSUMHelpers::tsumFileExists( TSU_MC_PLUGIN_PATH . 'data/csv/' . $this->igTable . '.csv' ) ) {
                                    $this->tsumRenderTableFormActionButton( parent::TSUM_TAB_IG_NAME, 'import' );      
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
                                    $this->tsumRenderTableFormActionButton( parent::TSUM_TAB_PC_NAME );
                                }
                                if ( \lib\util\TSUMHelpers::tsumFileExists( TSU_MC_PLUGIN_PATH . 'data/csv/' . $this->pcTable . '.csv' ) ) {
                                    $this->tsumRenderTableFormActionButton( parent::TSUM_TAB_PC_NAME, 'import' );
                                }
                            ?>
                        </td>
                    </tr>    
                    <tr>
                        <td><?php echo esc_html__( 'CSV import directory', 'tsu-mapconnect' );  ?></td>
                        <td>
                            <?php 
                                echo TSU_MC_PLUGIN_PATH . 'data/csv/';
                            ?>                           
                        </td>
                    </tr>                     
                </tbody>
            </table>
            <p>
                <?php if ( \lib\util\TSUMHelpers::tsumIsDirEmpty( TSU_MC_PLUGIN_PATH . 'data/csv/' )): ?>
                    <div class="notice notice-info inline"><?php echo esc_html__( 'No CSV file found in directory.', 'tsu-mapconnect' ); ?></div>
                <?php else: ?>
                    <div class="notice notice-info inline"><?php echo esc_html__( 'Files found in directory.', 'tsu-mapconnect' ); ?></div>
                    <i>
                        <?php echo esc_html__('If you press the "import"-button, data from the csv file stored at the import location of the plugin will be loaded into the according table. Only do this if you are sure what you are doing. The files must be named correctly, e.g. event_ig_plz.csv or events_igs.csv.', 'tsu-mapconnect') ?>
                    </i>                    
                <?php endif; ?>
            </p>
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
