/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl } from '@wordpress/components';
import { Fragment } from "@wordpress/element"; 

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit() {
    const blockProps = useBlockProps();
    const postType = useSelect(
            ( select ) => select( 'core/editor' ).getCurrentPostType(),
            []
    );
    const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
    
    // fields object
    const areaData = { 
                        name: meta[ '_meta_fields_tsum_areaname' ],
                        pcodes: meta[ '_meta_fields_tsum_areapcs' ],
                        logo: meta[ '_meta_fields_tsum_arealogo' ],
                        description: meta[ '_meta_fields_tsum_areadesc' ],
                        contact: meta[ '_meta_fields_tsum_areacontact' ],
                        link: meta[ '_meta_fields_tsum_arealink' ],
                        activities: meta[ '_meta_fields_tsum_areaactivities' ],
                        socmedia: meta[ '_meta_fields_tsum_areasocmedia' ]
                     };
    // update function
    // newValue = value to insert, field = can be name | pcodes | logo | description | contact | link | activities | socmedia
    const updateAreaData = ( newValue, field ) => {
        switch ( field ) {
            case "name":
                setMeta( { ...meta, _meta_fields_tsum_areaname: newValue } );
                break;
            case "pcodes":
                //validate value
                let validatedList = validatePCList( newValue );                
                setMeta( { ...meta, _meta_fields_tsum_areapcs: validatedList } );
                break;    
            case "logo":
                setMeta( { ...meta, _meta_fields_tsum_arealogo: newValue } );
                break;                
            case "description":
                setMeta( { ...meta, _meta_fields_tsum_areadesc: newValue } );
                break;   
            case "contact":
                setMeta( { ...meta, _meta_fields_tsum_areacontact: newValue } );
                break;             
            case "link":
                setMeta( { ...meta, _meta_fields_tsum_arealink: newValue } );
                break;  
            case "activities":
                let validatedActivities = validateActList( newValue );
                setMeta( { ...meta, _meta_fields_tsum_areaactivities: validatedActivities } );
                break;       
            case "socmedia":
                setMeta( { ...meta, _meta_fields_tsum_areasocmedia: newValue } );
                break;   
            default:
                break;
        }        
    };
    //validate lsit of Postcodes, returns validated list
    //needs list (string)
    const validatePCList = ( list ) => {
        
        const lastChar = list.charAt( list.length-1 );
        const secLastChar = list.charAt( list.length-2 );
        lastChar === ',' && lastChar === secLastChar ? list = list.replace( /.$/, '' ) : null;
    
        return list.replace(/[^\d,]+/g, '');
    }
    //validate activities list
    //needs list (string)
    const validateActList = ( list ) => {

        const lastChar = list.charAt( list.length-1 );
        const secLastChar = list.charAt( list.length-2 );
        
        lastChar === ',' && lastChar === secLastChar ? list = list.replace( /.$/, '' ) : null;        
        lastChar === ' ' && secLastChar === ',' ? list = list.replace( /.$/, '' ) : null;        
        lastChar === ',' && secLastChar === ' ' ? list = list.slice( 0, list.length - 2 ) + list.slice( list.length - 1 ) : null;        
    
        return list;
    }  
        
    return ( 
            <div { ...blockProps }>
                <h3>{ __( 'Mapconnect Metadata block', 'tsu-mapconnect' )}</h3>
                <div className="tsum-metablock-container">
                    <div className="tsum-metablock-left">
                        <TextControl
                                className="tsum-textcontrol"
                                label={__( 'Area name', 'tsu-mapconnect' )}
                                value={ areaData.name }
                                onChange={ ( newValue ) => { updateAreaData( newValue, 'name' ) } }
                        />
                        <TextControl
                                className="tsum-textcontrol"
                                label={ __( 'Area postcodes (comma-separated)', 'tsu-mapconnect' ) }
                                value={ areaData.pcodes }
                                onChange={ ( newValue ) => { updateAreaData( newValue, 'pcodes' ) } }
                        />                    
                        <TextControl
                                className="tsum-textcontrol"
                                label={ __( 'Area link', 'tsu-mapconnect' ) }
                                value={ areaData.link }
                                onChange={ ( newValue ) => { updateAreaData( newValue, 'link' ) } }
                        />
                    </div>
                    <div className="tsum-metablock-right">
                        <TextControl
                                className="tsum-textcontrol"
                                label={ __( 'Contact email', 'tsu-mapconnect' ) }
                                value={ areaData.contact }
                                onChange={ ( newValue ) => { updateAreaData( newValue, 'contact' ) } }
                        />
                        <TextControl
                                className="tsum-textcontrol"
                                label={ __( 'Area logo (numeric id or link)', 'tsu-mapconnect' ) }
                                value={ areaData.logo }
                                onChange={ ( newValue ) => { updateAreaData( newValue, 'logo' ) } }
                        /> 
                        <TextControl
                                className="tsum-textcontrol"
                                label={ __( 'Activities (comma-separated)', 'tsu-mapconnect' ) }
                                value={ areaData.activities }
                                onChange={ ( newValue ) => { updateAreaData( newValue, 'activities' ) } }
                        /> 
                    </div>
                    <div className="tsum-metablock-wrapper">
                        <div className="tsum-pseudolabel">{ __( 'Descriptive text', 'tsu-mapconnect' ) }</div>
                        <div className="tsum-metablock-desc">
                            <RichText 
                                    className="tsum-richtext"
                                    tagName="p"
                                    onChange={ ( newValue ) => { updateAreaData( newValue, 'description' ) } }
                                    allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
                                    value={ areaData.description }
                                    placeholder={ __( 'Description of your area', 'tsu-mapconnect' ) }
                            />   
                        </div>
                    </div>
                </div>
                <div className="tsum-remark-small">
                    <i>{ __( 'This block is not rendered. It is only for storing metadata for your mapping needs which will be sent via the REST API.', 'tsu-mapconnect' )}</i>
                </div>
            </div>
           );

}
