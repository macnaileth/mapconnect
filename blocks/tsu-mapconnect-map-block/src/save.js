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
                            }
	} = props; 
                 
        return ( <div>
                    <div id="root"></div>
                    <script src={ ( MC_pluginURL === '' ? MC_pluginURLdef : MC_pluginURL  ) + "/app/main.js" } ></script> 
                </div> );
}