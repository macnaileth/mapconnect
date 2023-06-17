

export default function Save( props ) { 
        
        //get attributes
    	const {
		attributes: {   
                                MC_baseUrldef, 
                                MC_metaUrldef, 
                                MC_dataUrldef,
                                MC_baseUrlset,
                                MC_metaUrlset,
                                MC_dataUrlset
                            }
	} = props; 
        
        return '[mapplication base_url="' + MC_baseUrlset + '" metadata_url="' + MC_metaUrlset + '" database_url="' + MC_dataUrlset + '"]';
}