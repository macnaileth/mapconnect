export default function Save( props ) { 
        
        //get attributes
    	const {
		attributes: {   
                                MC_baseUrldef, 
                                MC_metaUrldef, 
                                MC_dataUrldef,
                                MC_pluginURLdef,
                                MC_baseUrlset,
                                MC_metaUrlset,
                                MC_dataUrlset,
                                MC_pluginURL,
                                MC_styleDefaults,
                                MC_styleset
                            }
	} = props; 
        
        const baseURL = MC_baseUrlset === '' || MC_baseUrlset === '<DEFAULT>' ? 'https://dimb-api-20230512.netlify.app/api/igs' : MC_baseUrlset;
        const style = MC_styleset.length === 0 || MC_styleset === undefined ?  MC_styleDefaults : MC_styleset;
        
        return ( <div>
                    <div id="root"></div>
                    <script src={ ( MC_pluginURL === '' ? MC_pluginURLdef : MC_pluginURL  ) + "/app/main.js" } ></script> 
                    <script dangerouslySetInnerHTML={{ __html:
                            `window.mapconfig = { 
                                    style: {
                                        featFillRGB: '${ style.featFillRGB }',
                                        featFillAlpha: '${ style.featFillAlpha }',
                                        featStrokeRGB: '${ style.featStrokeRGB }',
                                        featStrokeWidth: '${ style.featStrokeWidth }',
                                        featHighlightRGB: '${ style.featHighlightRGB }',
                                        headlineRGB: '${ style.headlineRGB }',
                                        textRGB: '${ style.textRGB }'
                                    }, 
                                    paths: { 
                                        api: '${ baseURL }?simplify=0.005'  
                                    },
                                    height: '500',
                                    addClasses: ''
                                };`
                    }} />        
                </div> );
}