import MapBlock from './mapblock';

export default function Save( props ) { 
        
        //get attributes
    	const {
		attributes: {   
                                MC_baseUrldef, 
                                MC_metaUrldef, 
                                MC_dataUrldef,
                                MC_baseUrlset,
                                MC_metaUrlset,
                                MC_dataUrlset,
                                MC_pluginURL
                            }
	} = props; 
                 
        return <MapBlock settings={ { baseUrl: MC_baseUrlset, metaUrl: MC_metaUrlset, pluginURL: MC_pluginURL } } />;
}