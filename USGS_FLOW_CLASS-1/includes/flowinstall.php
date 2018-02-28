<?php 
//Create flow db table
global $flow_db_version;
$flow_db_version = "1.7";

function flow_install () {
   global $wpdb;
   global $flow_db_version;
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   
  $table_name = $wpdb->prefix . "flowrates";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name ." (
          id smallint(4) UNSIGNED NOT NULL AUTO_INCREMENT,
          siteid mediumint(8) UNSIGNED ZEROFILL NOT NULL,
    time bigint(11) DEFAULT '0' NOT NULL,
    flowtype tinytext NOT NULL,
    name text NOT NULL,
    weatherurl VARCHAR(55) NOT NULL,
          upperlevel float NULL,
          lowerlevel float NULL,
          class text NOT NULL,
          travel text NOT NULL,
          latestlevel float NULL,
          weatherlocale text NOT NULL,
          awurl text NOT NULL,
          change float NOT NULL,
          UNIQUE KEY id (id)
  );";

      dbDelta($sql);
   }
      $table_name = $wpdb->prefix . "playspots";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name ." (
          id smallint(4) UNSIGNED NOT NULL AUTO_INCREMENT,
          siteid mediumint(8) UNSIGNED,
          name text NOT NULL,
          description text NOT NULL,
          upperlevel float NULL,
          lowerlevel float NULL,
          flowtype tinytext NOT NULL,
          class text NOT NULL,
          latitude double NULL,
          longitude double NULL,
          UNIQUE KEY id (id)
  );";

      dbDelta($sql);
   }
      //$rows_affected = $wpdb->insert( $table_name, array( 'time' => current_time('mysql'), 'name' => $welcome_name, 'text' => $welcome_text ) );
 
      add_option("flow_db_version", $flow_db_version);
      
      wp_schedule_event(time(), 'quarterhour', 'flow_updates');
      wp_schedule_event(time(), 'quarterhour', 'playspots_csv_update');
   
   $installed_ver = get_option( "flow_db_version" );

   if( $installed_ver != $flow_db_version ) {
    
      $table_name = $wpdb->prefix . "flowrates";
      
      $wpdb->query("ALTER TABLE  `".$table_name."` ADD  `change` FLOAT NOT NULL AFTER  `awurl`");
      
      $table_name = $wpdb->prefix . "playspots";
      
      $wpdb->query("ALTER TABLE " . $table_name ." ADD spoturl TEXT NOT NULL AFTER name");
      
      $wpdb->query("UPDATE " . $table_name. " SET  spoturl =  \"enter url\"");
      
      update_option( "flow_db_version", $flow_db_version);
   }
}// END table creation

?>
