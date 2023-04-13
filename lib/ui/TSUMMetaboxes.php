<?php
namespace lib\ui;

/**
 * Description of TSUMMetaboxes
 * 
 * Class to create Metaboxes fpr setting up data to be transfered to API. UI stuff mostly
 * constructor needs a string containing the tpye of box to be created. Possible:
 * 
 * PCAREA = [default] An area defined by a bunch of postcodes with title, description and some other features appended to a page
 *
 * @author marconagel
 */
class TSUMMetaboxes {
    
    private $boxType;
    
    public function __construct( $box = 'PCAREA' ) {
        $this->boxType = $box;
        
        //add action here
        add_action( 'add_meta_boxes', array( $this, 'tsumMetabox' ) ); //metabox
        add_action( 'save_post', array( $this, 'tsumMetaboxSave' ) ); //save_post
        
    }
    /**
     * tsumMetabox()
     * Creates metabox based on input from constructor ($boxType)
     * 
     */
    public function tsumMetabox() {
        
        //box for post code area stuff for pages
        if ( $this->boxType == 'PCAREA' ) {
            add_meta_box(
                    'tsum_page_area_box', 
                    esc_html__( 'MapConnect area box', 'tsu-mapconnect' ), 
                    array( $this, 'tsumMetaboxPCAreaCB' ), 
                    'page',
                    'advanced',
                    'default',
                    array('__back_compat_meta_box' => true) //do not show in Gutenberg
             );            
        }
        
    }
    /**
     * tsumMetaboxPCAreaCB()
     * Callback function for tsumMetabox || PCAREA
     */
    public function tsumMetaboxPCAreaCB( $post ) {
        
	  wp_nonce_field( 'meta_fields_save_meta_box_data', 'meta_fields_meta_box_nonce' );
	  $area =   [ 
                        "name" => get_post_meta( $post->ID, '_meta_fields_tsum_areaname', true ),
                        "postcode" => get_post_meta( $post->ID, '_meta_fields_tsum_areapcs', true ),
                        "logo" => get_post_meta( $post->ID, '_meta_fields_tsum_arealogo', true ),
                        "desc" => get_post_meta( $post->ID, '_meta_fields_tsum_areadesc', true ),
                        "contact" => get_post_meta( $post->ID, '_meta_fields_tsum_areacontact', true ),
                        "link" => get_post_meta( $post->ID, '_meta_fields_tsum_arealink', true ),
                        "activities" => get_post_meta( $post->ID, '_meta_fields_tsum_areaactivities', true ),
                        "socmedia" => get_post_meta( $post->ID, '_meta_fields_tsum_areasocmedia', true ),              
                    ];
	  ?>
	  <div class="inside">
                <table class="form-table tsum-metabbox-inner">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Name of area', 'tsu-mapconnect' ); ?></th>
                        <td>
                            <input type="text" id="meta_fields_tsum_areaname" name="meta_fields_tsum_areaname" value="<?php echo esc_attr( $area["name"] ); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Area postcodes (comma-separated)', 'tsu-mapconnect' ); ?></th>
                        <td>
                            <input type="text" id="meta_fields_tsum_areapcs" name="meta_fields_tsum_areapcs" value="<?php echo esc_attr( $area["postcode"]  ); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Logo or other image', 'tsu-mapconnect' ); ?></th>
                        <td>
                            <?php if (!empty($area["logo"])) : ?>
                            <div class="tsum-logo-container"><img src="<?php echo is_numeric( $area["logo"] ) ? wp_get_attachment_image_src( $area["logo"] )[0] : $area["logo"]; ?>" alt="<?php esc_html_e( 'logo image', 'tsu-mapconnect' ); ?>"></div>
                            <?php endif; ?>
                            <input type="text" id="meta_fields_tsum_arealogo" name="meta_fields_tsum_arealogo" value="<?php echo esc_attr( $area["logo"]  ); ?>" />
                        </td>
                    </tr>   
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Contact email address', 'tsu-mapconnect' ); ?></th>
                        <td>
                            <input type="text" id="meta_fields_tsum_areacontact" name="meta_fields_tsum_areacontact" value="<?php echo esc_attr( $area["contact"]  ); ?>" />
                        </td>
                    </tr>    
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Link to area webpage', 'tsu-mapconnect' ); ?></th>
                        <td>
                            <input type="text" id="meta_fields_tsum_arealink" name="meta_fields_tsum_arealink" value="<?php echo esc_attr( $area["link"]  ); ?>" />
                        </td>
                    </tr>  
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Activities in area (comma-separated)', 'tsu-mapconnect' ); ?></th>
                        <td>
                            <input type="text" id="meta_fields_tsum_areaactivities" name="meta_fields_tsum_areaactivities" value="<?php echo esc_attr( $area["activities"]  ); ?>" />
                        </td>
                    </tr>     
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Socialmedia presence', 'tsu-mapconnect' ); ?></th>
                        <td>
                            <input type="text" id="meta_fields_tsum_areasocmedia" name="meta_fields_tsum_areasocmedia" value="<?php echo esc_attr( $area["socmedia"]  ); ?>" />
                        </td>
                    </tr>                     
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Descriptive text', 'tsu-mapconnect' ); ?></th>
                        <td>
                            <textarea id="meta_fields_tsum_areadesc" name="meta_fields_tsum_areadesc" rows="4" cols="50"><?php echo esc_attr( $area["desc"]  ); ?></textarea>
                        </td>
                    </tr>                     
                </table>   
                <p><i><?php esc_html_e( 'You are currently using the Map Connect plugin with the Classic Editor plugin for WordPress. We strongly recommend using Gutenberg instead, since Classic Editor is deprecated and we will stop supporting it in the near future.', 'tsu-mapconnect' ); ?></i></p>
	  </div>
	  <?php  
          
    }
    /**
     * tsumMetaboxSave( $post_id ) filter_input(INPUT_POST, 'var_name')
     * Function hooking into saving post, updating metadata if needed
     */
    public function tsumMetaboxSave( $post_id ) {
        
        //get cleanup stuff
        require_once TSU_MC_PLUGIN_PATH . '/lib/util/TSUMCleanUp.php';
        
         if ( $this->boxType == 'PCAREA' ) {
             
            if ( ! isset( $_POST['meta_fields_meta_box_nonce'] ) )
                    return;
            if ( ! wp_verify_nonce( $_POST['meta_fields_meta_box_nonce'], 'meta_fields_save_meta_box_data' ) )
                    return;
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                    return;
            if ( ! current_user_can( 'edit_post', $post_id ) )
                    return;
            //check set fields
            if ( ! isset( $_POST['meta_fields_tsum_areaname'] ) )
                    return;
            if ( ! isset( $_POST['meta_fields_tsum_areapcs'] ) )
                    return;
            if ( ! isset( $_POST['meta_fields_tsum_arealogo'] ) )
                    return;
            if ( ! isset( $_POST['meta_fields_tsum_areadesc'] ) )
                    return;
            if ( ! isset( $_POST['meta_fields_tsum_areacontact'] ) )
                    return;
            if ( ! isset( $_POST['meta_fields_tsum_arealink'] ) )
                    return;
            if ( ! isset( $_POST['meta_fields_tsum_areaactivities'] ) )
                    return;
            if ( ! isset( $_POST['meta_fields_tsum_areasocmedia'] ) )
                    return;
            
            //TODO: server side check data
            $messages = [];
            //postcodes
            $valPostCodes = \lib\util\TSUMCleanUp::tsumCleanPCString( $_POST['meta_fields_tsum_areapcs'] );
            $valPostCodes[1] != '' && array_push( $messages, $valPostCodes[1] ); 
            
            //set data array
            $area = [ 
                        "name" => sanitize_text_field( $_POST['meta_fields_tsum_areaname'] ),
                        "postcode" => sanitize_text_field( $valPostCodes[0] ),  
                        "logo" => sanitize_text_field( $_POST['meta_fields_tsum_arealogo'] ),
                        "desc" => sanitize_text_field( $_POST['meta_fields_tsum_areadesc'] ),
                        "contact" => sanitize_email( $_POST['meta_fields_tsum_areacontact'] ),
                        "link" => sanitize_url( $_POST['meta_fields_tsum_arealink'] ),
                        "activities" => sanitize_text_field( \lib\util\TSUMCleanUp::tsumTrimCommaPlus( $_POST['meta_fields_tsum_areaactivities'] ) ),
                        "socmedia" => sanitize_text_field( $_POST['meta_fields_tsum_areasocmedia'] ),                  
                    ];

            //do updates to metadata
            update_post_meta( $post_id, '_meta_fields_tsum_areaname', $area["name"] );
            update_post_meta( $post_id, '_meta_fields_tsum_areapcs', $area["postcode"] ); 
            update_post_meta( $post_id, '_meta_fields_tsum_arealogo', $area["logo"] );  
            update_post_meta( $post_id, '_meta_fields_tsum_areadesc', $area["desc"] );  
            update_post_meta( $post_id, '_meta_fields_tsum_areacontact', $area["contact"] );  
            update_post_meta( $post_id, '_meta_fields_tsum_arealink', $area["link"] );  
            update_post_meta( $post_id, '_meta_fields_tsum_areaactivities', $area["activities"] );  
            update_post_meta( $post_id, '_meta_fields_tsum_areasocmedia', $area["socmedia"] );             
         }   
    }
    
}
