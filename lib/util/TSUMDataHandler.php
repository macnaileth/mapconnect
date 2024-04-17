<?php
namespace lib\util;

/**
 * Description of TSUMDataHandler
 * 
 * Class for handling external data stuff
 *
 * @author marconagel
 */
class TSUMDataHandler {
    
    private $tsumParamTable;
    private $tsumParams;
    private $tsumPFX;
    private $tsumWPDB;
    
   /**
    * 
    * @param string $table - Database table in WordPress db holding params to connect to external table
    */
    public function __construct ( $table, $params = [ 
                                                        'host' => 'ig_event_db_host', 
                                                        'db' => 'ig_event_db_name',
                                                        'user' => 'ig_event_db_user',
                                                        'password' => 'ig_event_db_password',        
                                                    ] ) {       
        global $wpdb;
        
        $this->tsumWPDB = $wpdb;
        $this->tsumParamTable = $table;
        $this->tsumParams = $params;
        $this->tsumPFX = $wpdb->prefix;
    }
    public function tsumPrintConnectionDataTable() {
        //query database
        $prefixedTable = $this->tsumPFX . $this->tsumParamTable;
        $paramData = $this->tsumWPDB->get_results( "SELECT parameter, value FROM $prefixedTable" );   
        
        if ( $paramData != null ): ?> 
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <td><?php echo esc_html__( 'Parameters Table', 'tsu-mapconnect' );  ?></td>
                        <td><?php echo $prefixedTable; ?></td>
                    </tr>
                    <?php
                        $output = '<tr><td>' . esc_html__( 'Table found but no parameters set.', 'tsu-mapconnect' ) . '</td></tr>';
                        $paramsSet = false;
                        
                        foreach($paramData as $param) {
                            
                            $paramName = '';
                            $paramVal = '';
                            
                            if( $this->tsumParams['host'] == $param->parameter ) {
                                $paramName = esc_html__( 'Database Host', 'tsu-mapconnect' );
                                $paramVal = $param->value;
                            } else if( $this->tsumParams['db'] == $param->parameter ) {
                                $paramName = esc_html__( 'Database Name', 'tsu-mapconnect' );
                                $paramVal = $param->value;
                            } else if( $this->tsumParams['user'] == $param->parameter ) {
                                $paramName = esc_html__( 'Database User', 'tsu-mapconnect' );
                                $paramVal = $param->value;
                            }  else if( $this->tsumParams['password'] == $param->parameter ) {
                                $paramName = esc_html__( 'Database Pass', 'tsu-mapconnect' );
                                $paramVal = '********';
                            }
                            if ( $paramName != '' && $paramVal != '' ) {
                                if ( !$paramsSet ) { 
                                    $output = '';
                                    $paramsSet = true;
                                }
                                
                                $html = '<tr>';
                                $html .= '<td>' . $paramName . '</td>';
                                $html .= '<td>' . $paramVal . '</td>';
                                $html .= '</tr>';
                                $output .= $html;
                            }   
                        }
                        echo $output;
                    ?>                    
                </tbody>
            </table>
            </p>
        <?php else: ?>
            <p>
                <?php 
                    echo '<b>' . ( isset( $this->tsumParamTable ) && $this->tsumParamTable != '' ? $this->tsumPFX . $this->tsumParamTable : '---' )  . '</b>: ' . 
                            esc_html__( 'Table not found or defined. No connection established.', 'tsu-mapconnect' ); 
                ?>
            </p>
        <?php endif;
    }
}
