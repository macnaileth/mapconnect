import { useEffect } from "react";
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
import { useBlockProps, 
         RichText, 
         InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl, 
         Panel, 
         PanelBody, 
         PanelRow,
         Button } from '@wordpress/components';
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

//import map data
import mapData from "../data/mapdata.json";

export default function Edit ( props ) {
        
        //get attributes
    	const {
		attributes: {   
                                MC_baseUrldef, 
                                MC_metaUrldef, 
                                MC_dataUrldef,
                                MC_baseUrlset,
                                MC_metaUrlset,
                                MC_dataUrlset,
                                MC_pluginURL,
                                MC_NextData
                            }
	} = props; 
        
    const blockProps = useBlockProps();
    
    //get path running on
    console.log( '*** App running @URL: ' + MC_pluginURL + ' ***');
    
    //set data for output block if needed - TODO: make this dynamic in the future and add a reset button to interface
    const mapJson = mapData;
    
    useEffect(() => {
        MC_NextData === undefined || Object.keys( MC_NextData ).length === 0 && MC_NextData.constructor === Object && props.setAttributes( { MC_NextData: mapJson } ); 
        console.log( '*** App data for frontend set! ***');
        console.log( MC_NextData );
    },[]);
    
  
    return ( 
            <div { ...blockProps }>
                <InspectorControls>
                    <PanelBody title={ __( 'Settings' ) + ': ' + __( 'Paths', 'tsu-mapconnect' ) } initialOpen={ false }>
                        <PanelRow>
                            <TextControl
                                className = 'tsum-metablock-input'
                                label={ __('Base URL', 'tsu-mapconnect') }
                                value={ MC_baseUrlset === '' ? MC_baseUrldef : MC_baseUrlset }
                                onChange={ (newValue) => props.setAttributes( { MC_baseUrlset: newValue } ) }
                                />    
                        </PanelRow> 
                        <PanelRow>
                            <TextControl
                                className = 'tsum-metablock-input'
                                label={ __('URL for Metadata', 'tsu-mapconnect') }
                                value={ MC_metaUrlset === '' ? MC_metaUrldef : MC_metaUrlset }
                                onChange={ (newValue) => props.setAttributes( { MC_metaUrlset: newValue } ) }
                                />    
                        </PanelRow> 
                        <PanelRow>
                            <TextControl
                                className = 'tsum-metablock-input'
                                label={ __('URL for Database', 'tsu-mapconnect') }
                                value={ MC_dataUrlset === '' ? MC_dataUrldef : MC_dataUrlset }
                                onChange={ (newValue) => props.setAttributes( { MC_dataUrlset: newValue } ) }
                                />    
                        </PanelRow>                         
                        <PanelRow>                      
                            <p>
                                { __('Warning: Only change values here if you know what you are doing. If changed by accident, you can reset the values to the defaults using the reset button below.', 'tsu-mapconnect') }
                            </p>
                        </PanelRow>
                        <PanelRow>
                            <Button 
                                className="is-secondary" 
                                variant="secondary" 
                                onClick={ () => props.setAttributes({ MC_baseUrlset: "", MC_metaUrlset: "", MC_dataUrlset: "", MC_NextData: undefined }) }>
                                    { __('Reset', 'tsu-mapconnect') }
                            </Button>  
                        </PanelRow>
                        <PanelRow>
                            <Button 
                                className="is-secondary" 
                                variant="secondary" 
                                onClick={ () => props.setAttributes({ MC_baseUrlset: "", MC_metaUrlset: "", MC_dataUrlset: "", MC_NextData: mapJson }) }>
                                    { __('Set App Data', 'tsu-mapconnect') }
                            </Button>  
                        </PanelRow>                        
                    </PanelBody> 
                </InspectorControls>
                <h3>{ __( 'Mapconnect Mapplication', 'tsu-mapconnect' )}</h3>
                <div className="tsum-metablock-container">
                    <div className="tsum-metablock-left">
                        <h4>{ __( 'Colours', 'tsu-mapconnect' )}</h4>
                    </div>
                    <div className="tsum-metablock-right">
                        <h4>{ __( 'Styling options', 'tsu-mapconnect' )}</h4>                    
                    </div>                    
                </div>
            </div>
           );

}
