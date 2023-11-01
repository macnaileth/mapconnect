/* Map Application Block
 * By Marco Nagel and Kerstin Huppenbauer
 * ---------------------------------------
 * CC-BY-SA 4.0 Made for DIMB e. V.
 * 31.10.2023
 *  
 * Map App for WordPress
 * */

//import js ressources

export default function MapBlock({ settings }) { 
        
        return (
                <>
                    { settings.nextData === undefined || Object.keys( settings.nextData ).length === 0 && settings.nextData.constructor === Object ? 
                        <div>Map block has no data - there is nothing to display at the moment.</div> :
                        <>
                            <div id="__next">
                                <link rel="preload" href={ settings.pluginURL + "/app/_next/static/css/8648986187287a5f.css" } as="style"/>
                                <link rel="stylesheet" href={ settings.pluginURL + "/app/_next/static/css/8648986187287a5f.css" } data-n-g=""/>
                                <link rel="preload" href={ settings.pluginURL + "/app/_next/static/css/c9d1f2c064b6d4b5.css" } as="style"/>
                                <link rel="stylesheet" href={ settings.pluginURL + "/app/_next/static/css/c9d1f2c064b6d4b5.css" } data-n-p=""/> 

                                <script defer="" nomodule="" src={ settings.pluginURL + "/app/_next/static/chunks/polyfills-78c92fac7aa8fdd8.js" }></script>
                                <script src={ settings.pluginURL + "/app/_next/static/chunks/webpack-b8f8d6679aaa5f42.js" } defer=""></script>
                                <script src={ settings.pluginURL + "/app/_next/static/chunks/framework-c3d692082d87967e.js" } defer=""></script>
                                <script src={ settings.pluginURL + "/app/_next/static/chunks/main-92011a1a7f336a6f.js" } defer=""></script>
                                <script src={ settings.pluginURL + "/app/_next/static/chunks/pages/_app-6d4db059040e5e6f.js" } defer=""></script>
                                <script src={ settings.pluginURL + "/app/_next/static/chunks/239-3da7105946f12e0f.js" } defer=""></script>
                                <script src={ settings.pluginURL + "/app/_next/static/chunks/pages/maps-6954633b6de13a0e.js" } defer=""></script>
                                <script src={ settings.pluginURL + "/app/_next/static/fsaXshHGTNPhlvFYUTskH/_buildManifest.js" } defer=""></script>
                                <script src={ settings.pluginURL + "/app/_next/static/fsaXshHGTNPhlvFYUTskH/_ssgManifest.js" } defer=""></script>                        
                                <div>
                                    <div id="map" style="height:100vh"></div>
                                    <div id="popup" className="ol-popup absolute m-6 right-0 bottom-0"></div>       
                                </div> 
                            </div>
                            <script id="__NEXT_DATA__" type="application/json">{ JSON.stringify({"TODO":"Stringify data","Problem":"The payload is too fucking large!"}) }</script> 
                        </>
                    }
                </>
                );
};

