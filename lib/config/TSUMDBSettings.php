<?php
namespace lib\config;

/*
 * Contains Settings regarding the external database.
 * This is used to update database structures associated with the mapconnect plugin.
 * DO NOT CHANGE THIS PER HAND YOU MIGHT BREAK IT!
 *
 * @author marconagel
 */

class TSUMDBSettings {
    //name of the database tables
    const TSUM_TAB_PC_NAME = 'events_ig_plz'; //postcodes
    const TSUM_TAB_IG_NAME = 'events_igs'; //igs

    const TSUM_CON_SETTINGS = [
        'host' => 'ig_event_db_host',
        'db' => 'ig_event_db_name',
        'user' => 'ig_event_db_user',
        'password' => 'ig_event_db_password',
    ];
    
    //columns for actual postcode table
    const TSUM_TAB_PC_COLS = [ 'id', 'ig', 'start', 'ende', 'name', 'district', 'federalState' ];

    //columns for actual igs table
    const TSUM_TAB_IGS_COLS = [ 'id', 'name', 'mail', 'aktiv', 'sewobe_id' ];
}

