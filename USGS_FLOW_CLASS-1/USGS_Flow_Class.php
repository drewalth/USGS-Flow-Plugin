<?php
/*
Plugin Name: USGS Flow Plugin
Plugin URI: 
Description: Maintains flow rates and weather information for play spots and rivers
Author: Travis Bock, Justin Raines & Drew Althage
Version: 1.8
Author URI: 
*/   


/**
* Guess the wp-content and plugin urls/paths
*/
// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'USGS_PLUGIN_URL' ) )
      define( 'USGS_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'USGS_PLUGIN_DIR' ) )
      define( 'USGS_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );


require_once('includes/USGSFlowClass.php');
/*include_once(dirname (__FILE__) . '/includes/flowinstall.php');*/
include_once('includes/flowinstall.php');

date_default_timezone_set('America/New_York');

if (!class_exists('USGS_Flow_Class')) {
    class USGS_Flow_Class {
        //This is where the class variables go, don't forget to use @var to tell what they're for
        /**
        * @var string The options string name for this plugin
        */
        var $optionsName = 'USGS_Flow_Class_options';
        
        /**
        * @var string $localizationDomain Domain used for localization
        */
        var $localizationDomain = "USGS_Flow_Class";
        
        /**
        * @var string $pluginurl The path to this plugin
        */ 
        var $thispluginurl = '';
        /**
        * @var string $pluginurlpath The path to this plugin
        */
        var $thispluginpath = '';
            
        /**
        * @var array $options Stores the options for this plugin
        */
        var $options = array();
        
        //Class Functions
        /**
        * PHP 4 Compatible Constructor
        */
        function USGS_Flow_Class(){$this->__construct();}
        
        /**
        * PHP 5 Constructor
        */        
        function __construct(){
            //Language Setup
            $locale = get_locale();
            $mo = dirname(__FILE__) . "/languages/" . $this->localizationDomain . "-".$locale.".mo";
            load_textdomain($this->localizationDomain, $mo);

            //"Constants" setup
            $this->thispluginurl = USGS_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
            $this->thispluginpath = USGS_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
            
            //Initialize the options
            //This is REQUIRED to initialize the options when the plugin is loaded!
            $this->getOptions();
            
            

            //Actions        
            add_action("admin_menu", array(&$this,"admin_menu_link"));

            
            //Widget Registration Actions
           // add_action('plugins_loaded', array(&$this,'register_widgets'));
            
            /*
            add_action("wp_head", array(&$this,"add_css"));
            add_action('wp_print_scripts', array(&$this, 'add_js'));
            */
            
            //Filters
            /*
            add_filter('the_content', array(&$this, 'filter_content'), 0);
            */
            add_shortcode('flowrates', array(&$this, 'flowrates_shortcode'));
            add_shortcode('playspots_map', array(&$this, 'playspots_map_shortcode'));
            add_shortcode('playspots', array(&$this, 'playspots_shortcode'));
            
            add_action('wp_ajax_my_special_action', array(&$this, 'save_table'));
            add_action('wp_ajax_add_site', array(&$this, 'add_site'));
            add_action('wp_ajax_delete_site', array(&$this, 'delete_site'));
            
            add_action('wp_ajax_add_playspot', array(&$this, 'add_playspot'));
            
            add_action('wp_ajax_update_levels',array(&$this, 'update_levels'));
            
            add_action('flow_updates', array(&$this, 'update_levels'));
       
            add_action('playspots_csv_update', array(&$this, 'playspots_to_csv'));
            
            register_activation_hook(__FILE__,'flow_install');
            
            add_option('last_flow_update', '', '', 'no');
            
            add_action('init', array(&$this, 'flow_scripts'));
            
            add_filter('cron_schedules', array(&$this, 'add_quarterhour'));    
            
            


        }

        function add_quarterhour($schedules)
        {
          $schedules['quarterhour'] = array(
            'interval'=> 900,
            'display'=>  __('Every 15 Minutes')
        );
         $schedules['halfhour'] = array(
            'interval'=> 1800,
            'display'=>  __('Every 30 Minutes')
        );
        return $schedules;
        }
        
        
        /**
        * Retrieves the plugin options from the database.
        * @return array
        */
        function getOptions() {
            //Don't forget to set up the default options
            if (!$theOptions = get_option($this->optionsName)) {
                $theOptions = array('default'=>'options');
                update_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
            
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //There is no return here, because you should use the $this->options variable!!!
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        }
        /**
        * Saves the admin options to the database.
        */
        function saveAdminOptions(){
            return update_option($this->optionsName, $this->options);
        }
        
        /**
        * @desc Adds the options subpanel
        */
        function admin_menu_link() {
            //If you change this from add_options_page, MAKE SURE you change the filter_plugin_actions function (below) to
            //reflect the page filename (ie - options-general.php) of the page your plugin is under!
            $editPage = add_menu_page('USGS Flow Plugin', 'River Levels', 10, 'USGS-Flow-menu', array(&$this,'edit_submenu'), '', 21);
            $editPlayspotPage = add_submenu_page('USGS-Flow-menu', 'Playspots', 'Playspots', 'manage_options', 'USGS-Flow-Playspots-edit', array(&$this,'edit_playspots_submenu'));
           // add_submenu_page('USGS-Flow-menu', 'Add', 'Add', 'manage_options', 'USGS-Flow-add', array(&$this,'add_submenu'));
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
            add_action("admin_print_scripts-$editPage", array(&$this, 'edit_admin_head'));
            add_action("admin_print_scripts-$editPlayspotPage", array(&$this, 'edit_admin_head'));
        }
        
        function edit_admin_head(){
            //echo USGS_PLUGIN_URL;
            //wp_deregister_script('jquery');
           // wp_enqueue_script('jquery');
            //wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');
            //wp_register_script( 'sorttableScript', plugins_url('/includes/script.js', __FILE__) );
        
            ?>
            <style>
                th
                {
                    text-align:left;
                }
                .editable
                {
                    overflow:hidden;
                    text-overflow:ellipsis;
                    width:100px;
                    white-space:nowrap;
                }
            </style>
        <?php
        }
        /**
        * @desc Adds the Settings link to the plugin activate/deactivate page
        */
        function filter_plugin_actions($links, $file) {
           //If your plugin is under a different top-level menu than Settiongs (IE - you changed the function above to something other than add_options_page)
           //Then you're going to want to change options-general.php below to the name of your top-level page
           $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
           array_unshift( $links, $settings_link ); // before other links

           return $links;
        }
        
         
        
        /**date_default_timezone_set("Europe/Helsinki");
        * Adds settings/options page
        */
        function admin_menu_page() { 
            if($_POST['USGS_Flow_Class_save']){
                if (! wp_verify_nonce($_POST['_wpnonce'], 'USGS_Flow_Class-update-options') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
                $this->options['USGS_Flow_Class_path'] = $_POST['USGS_Flow_Class_path'];                   
                $this->options['USGS_Flow_Class_allowed_groups'] = $_POST['USGS_Flow_Class_allowed_groups'];
                $this->options['USGS_Flow_Class_enabled'] = ($_POST['USGS_Flow_Class_enabled']=='on')?true:false;
                $this->options['USGS_Flow_Class_sites'] = $this->saveAdminOptions();
                
                echo '<div class="updated"><p>Success! Your changes were sucessfully saved!</p></div>';
            }
?>
                <div class="wrap">
                <h2>USGS Flow Plugin</h2>
                <form method="post" id="USGS_Flow_Class_options">
                <?php wp_nonce_field('USGS_Flow_Class-update-options'); ?>
                    <table width="100%" cellspacing="2" cellpadding="5" class="form-table"> 
                        <tr valign="top"> 
                            <th width="33%" scope="row"><?php _e('Option 1:', $this->localizationDomain); ?></th> 
                            <td><input name="USGS_Flow_Class_path" type="text" id="USGS_Flow_Class_path" size="45" value="<?php echo $this->options['USGS_Flow_Class_path'] ;?>"/>
                        </td> 
                        </tr>
                        <tr valign="top"> 
                            <th width="33%" scope="row"><?php _e('Option 2:', $this->localizationDomain); ?></th> 
                            <td><input name="USGS_Flow_Class_allowed_groups" type="text" id="USGS_Flow_Class_allowed_groups" value="<?php echo $this->options['USGS_Flow_Class_allowed_groups'] ;?>"/>
                            </td> 
                        </tr>
                        <tr valign="top"> 
                            <th><label for="USGS_Flow_Class_enabled"><?php _e('CheckBox #1:', $this->localizationDomain); ?></label></th><td><input type="checkbox" id="USGS_Flow_Class_enabled" name="USGS_Flow_Class_enabled" <?=($this->options['USGS_Flow_Class_enabled']==true)?'checked="checked"':''?>></td>
                        </tr>
                        <tr>
                            <th colspan=2><input type="submit" name="USGS_Flow_Class_save" value="Save" /></th>
                        </tr>
                    </table>
                </form>
                <?php
        }
        
        function edit_submenu()
        {
            global $wpdb;
            
            $rates = $wpdb->get_results("Select * from wp_flowrates Order By name ASC", ARRAY_A);
            //$columns =  $wpdb->get_col_info('name', -1);
    
            ?>
            <script type="text/javascript">
            /*if (jQuery) {
                alert('jQuery is loaded!');
                }
            */
                jQuery(document).ready(function($) {
                    $('.editable').click(function() {
                        
                       if ($(this).children('input').length == 0) {
                        //Get original value
                        var orig_val = $(this).html();
                        var field = $(this).attr("id");
                        var id = $(this).closest("tr").attr("id");
                        //Create the HTML to insert into the div. Escape any " characters 
                        var inputbox = '<input type="text" style="width:98%;" class="inputbox" value="'+orig_val+'">';
                        
                        //Insert the HTML into the div
                        $(this).html(inputbox);
                        
                        //Immediately give the input box focus. The user
                        //will be expecting to immediately type in the input box,
                        //and we need to give them that ability
                        $("input.inputbox").focus();
                        
                        //Once the input box loses focus, we need to replace the
                        //input box with the current text inside of it.
                        $("input.inputbox").blur(function() {
                          var value = $(this).val();
                                                  //alert(value + " " + orig_val);
                          if(value != "")
                                                  {
                                                      $(this).replaceWith(value);
                                                      
                                                      var wpnonce = $("#_wpnonce").val();
                                                  
                                                       //alert('id: ' + id + ' field: ' + field);
                                                       var data = {
                                                      action: 'my_special_action',
                                                      new_value: value,
                                                      orig_value: orig_val,
                                                      field: field,
                                                      id: id,
                                                      table_name: 'flowrates',
                                                      wpnonce: wpnonce 
                                                      };

                                                       // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                                                       if(value != orig_val)
                                                       {
                                                          jQuery.post(ajaxurl, data, function(response) {
                                                          //alert('Got this from the server: ' + response);
                                                         });
                                                      }
                                                  }
                                                  else
                                                  {
                                                      alert("This can not be left blank");
                                                  }
                                          });
                                 
                                          }
                                      });
                                      
                                      $('.add').click(function(){
                                           if ($(this).children('input').length == 0) {
                                          //Get original value
                                          var orig_html = $(this).closest("tr").html();
                                  
                        //Create the HTML to insert into the div. Escape any " characters 
                        var inputbox = '<tr><td><input name="siteid" type="text" id="siteid" size="15" /></td><td><input name="name" type="text" id="name" size="20" /></td><td><input name="awurl" type="text" id="awurl" size="20" /></td><td><input name="weatherlocale" type="text" id="weatherlocale" size="20" /></td><td><input name="weatherurl" type="text" id="weatherurl" size="20" /></td><td><input name="runnable" type="text" id="runnablelevel" size="10" /></td><td><input name="class" type="text" id="class" size="5" /></td><td><input name="travel" type="text" id="travel" size="20" /></td><td><input type="submit"/></td><td><input type="button" value="Cancel" class="cancel"/></td></tr>';
                        
                        //Insert the HTML into the div
                        $("#add").after(inputbox);
                        $("#add").toggle();
                        //Immediately give the input box focus. The user
                        //will be expecting to immediately type in the input box,
                        //and we need to give them that ability
                        $("#siteid").focus();
                        
                      
                        $("#addform").submit(function(){
                            
                            var testval = $('#addform').serialize();
                            var wpnonce = $("#_wpnonce").val();
                            var data = {
                            action: 'add_site',
                            data: testval,
                            wpnonce: wpnonce
                            }
                            jQuery.post(ajaxurl, data, function(response) {
                                    //alert('Got this from the server: ' + response);
                                     location.reload();
                                    });
                        });
                        
                        $("input.cancel").click(function(){
                            
                            $(this).closest("tr").html('');
                            $("#add").toggle();
                        });
                        }
                    });
                    
                    $('.delete').click(function(){
                        
                            var id = $(this).closest("tr").attr("id");
                            var wpnonce = $("#_wpnonce").val();
                            
                            var data = {
                            action: 'delete_site',
                            id: id,
                            table_name: 'flowrates',
                            wpnonce: wpnonce
                            }
                            
                            jQuery.post(ajaxurl, data, function(response)
                            {
                                //alert('Got this from the server: ' + response);
                                 location.reload();
                            });
                    });
                    
                    $('.getLatest').click(function(){
                            
                            var data = {
                            action: 'update_levels',
                            forced: true
                            }
                            
                            jQuery.post(ajaxurl, data, function(response)
                            {
                                 //alert('Got this from the server: ' + response);
                                 location.reload();
                            });
                    });
                    
                });
            </script>
            <form id="addform" onsubmit="return false;">
            <?php wp_nonce_field('USGS_Flow_Class-add-edit-site'); ?>
            <table width="100%">
                <tr><th>Site ID</th><th>Name</th><th>AW URL</th><th>Weather Locale</th><th>Weather URL</th><th>Runnable</th><th>Class</th><th>Travel</th><th>Level</th><td></td></tr>
            <?php
            foreach($rates as $rate){
                
             echo '<tr id="'.$rate['id'].'"><td><div id="siteid" class="editable">'.$rate['siteid'].'</div></td><td><div id="name" class="editable">'.$rate['name'].'</div></td><td><div id="awurl" class="editable">'.$rate['awurl'].'</div></td><td><div id="weatherlocale" class="editable">'.$rate['weatherlocale'].'</div></td><td><div id="weatherurl" class="editable">'.$rate['weatherurl'].'</div></td><td><div id="runnable" class="editable">'.$rate['lowerlevel'].'-'.$rate['upperlevel'].' '.$rate['flowtype'].'</div></td><td><div id="class" class="editable">'.$rate['class'].'</div></td><td><div id="travel" class="editable">'.$rate['travel'].'</div></td><td>'.$rate['latestlevel'].'</td><td><input type="button" value="Delete" class="delete"/></td></tr>';
            }
            ?>
                <tr id="add"><td colspan=2><a class="add" href="#">Click to add more sites</a></td></tr>
                <tr id="getlevel">
                    <td><input type="button" value="Update Levels" class="getLatest"/></td>
                    <td colspan=2>Last Updated: <?php if(get_option('last_flow_update') == ''){echo "Never";} else{echo date('m-d-y h:i:s', get_option('last_flow_update'));} ?></td>
                    <td colspan=2>Next Scheduled Update: <?php if(wp_next_scheduled( 'flow_updates' ) == FALSE){echo "Error!";} else{echo date('m-d-y h:i:s',wp_next_scheduled( 'flow_updates' ));} ?></td>
                </tr>
            </table>
            </form>
            
            <?php
            
            
        }
        
        function edit_playspots_submenu()
        {
            global $wpdb;
            
            $playspots = $wpdb->get_results("Select * from wp_playspots Order By name ASC", ARRAY_A);
            //$columns =  $wpdb->get_col_info('name', -1);
    
            ?>
            <script type="text/javascript">
            /*if (jQuery) {
                alert('jQuery is loaded!');
                }
            */
                jQuery(document).ready(function($) {
                    $('.editable').click(function() {
                        
                       if ($(this).children('.inputbox').length == 0) {
                        //Get original value
                        var orig_val = $(this).html();
                        var field = $(this).attr("id");
                        var id = $(this).closest("tr").attr("id");
                        //Create the HTML to insert into the div. Escape any " characters 
                        if (field == 'description')
                        {
                          var inputbox = '<textarea style="width:98%;" class="inputbox">'+orig_val+'</textarea>';
                        }
                        else
                        {
                          var inputbox = '<input type="text" style="width:98%;" class="inputbox" value="'+orig_val+'">';
                        }
                        //Insert the HTML into the div
                        $(this).html(inputbox);
                        
                        //Immediately give the input box focus. The user
                        //will be expecting to immediately type in the input box,
                        //and we need to give them that ability
                        $(".inputbox").focus();
                        
                        //Once the input box loses focus, we need to replace the
                        //input box with the current text inside of it.
                        $(".inputbox").blur(function() {
                          var value = $(this).val();
                          
                                                  //alert(value);
                          if(value != "")
                                {
                                    $(this).replaceWith(value);
                                    
                                    var wpnonce = $("#_wpnonce").val();
                                
                                     //alert('id: ' + id + ' field: ' + field);
                                     var data = {
                                    action: 'my_special_action',
                                    new_value: value,
                                    orig_value: orig_val,
                                    field: field,
                                    id: id,
                                    table_name: 'playspots',
                                    wpnonce: wpnonce 
                                    };

                                     // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                                     if(value != orig_val)
                                     {
                                        jQuery.post(ajaxurl, data, function(response) {
                                        //alert('Got this from the server: ' + response);
                                       });
                                    }
                                }
                                else
                                {
                                    alert("This can not be left blank");
                                }
                        });
               
                        }
                    });
                    
                    $('.add').click(function(){
                         if ($(this).children('input').length == 0) {
                        //Get original value
                        var orig_html = $(this).closest("tr").html();
                
                        //Create the HTML to insert into the div. Escape any " characters 
                        var inputbox = '<tr><td><input name="name" type="text" id="name" size="20" /></td><td><input name="siteid" type="text" id="siteid" size="20"/></td><td><input name="spoturl" type="text" id="name" size="20" /></td><td><textarea style="height:100px" name="description" id="description"></textarea><td><input name="runnable" type="text" id="runnablelevel" size="10" /></td><td><input name="ideal" type="text" id="ideal" size="10" /></td><td><input name="latlon" type="text" id="latlon" size="30" /></td><td><input type="submit"/></td><td><input type="button" value="Cancel" class="cancel"/></td></tr>';
                        
                        //Insert the HTML into the div
                        $("#add").after(inputbox);
                        $("#add").toggle();
                        //Immediately give the input box focus. The user
                        //will be expecting to immediately type in the input box,
                        //and we need to give them that ability
                        $("#siteid").focus();
                                          
                      
                        $("#addform").submit(function(){
                            
                            var testval = $('#addform').serialize();
                            var wpnonce = $("#_wpnonce").val();
                            var data = {
                            action: 'add_playspot',
                            data: testval,
                            wpnonce: wpnonce
                            }
                            jQuery.post(ajaxurl, data, function(response) {
                                    //alert('Got this from the server: ' + response);
                                     location.reload();
                                    });
                        });
                        
                        $("input.cancel").click(function(){
                            
                            $(this).closest("tr").html('');
                            $("#add").toggle();
                        });
                        }
                    });
                    
                    $('.delete').click(function(){
                        
                            var id = $(this).closest("tr").attr("id");
                            var wpnonce = $("#_wpnonce").val();
                            
                            var data = {
                            action: 'delete_site',
                            id: id,
                            table_name: 'playspots',
                            wpnonce: wpnonce
                            }
                            
                            jQuery.post(ajaxurl, data, function(response)
                            {
                                 //alert('Got this from the server: ' + response);
                                 location.reload();
                            });
                    });
                    
                    $('.getLatest').click(function(){
                            
                            var data = {
                            action: 'update_levels',
                            forced: true
                            }
                            
                            jQuery.post(ajaxurl, data, function(response)
                            {
                                 alert('Got this from the server: ' + response);
                                 location.reload();
                            });
                    });
                    
                });
            </script>
            <form id="addform" onsubmit="return false;">
            <?php wp_nonce_field('USGS_Flow_Class-add-edit-site'); ?>
            <table width="100%">
                <tr><th>Name</th><th>Site ID</th><th>Link</th><th>Description</th><th>Runnable</th><th>Ideal Level</th></th><th>Latitude,Longitude</th><td></td></tr>
            <?php
            foreach($playspots as $playspot){
              $siteid=000000;
              if($playspot['siteid']!= null){
                $siteid = $playspot['siteid'];
              }
              echo '<tr id="'.$playspot['id'].'"><td><div id="name" class="editable">'.$playspot['name'].'</div></td><td><div id="siteid" class="editable">'.$siteid.'</div></td><td><div id="spoturl" class="editable">'.$playspot['spoturl'].'</div></td><td><div id="description" class="editable" style="width:250px;">'.$playspot['description'].'</div></td><td><div id="runnable" class="editable">'.$playspot['lowerlevel'].'-'.$playspot['upperlevel'].' ft</div></td><td><div id="ideal" class="editable">'.$playspot['ideal'].'</div></td><td><div style="width:200px;" id="latlon" class="editable">'.$playspot['latitude'].','.$playspot['longitude'].'</div></td><td><input type="button" value="Delete" class="delete"/></td></tr>';
            }
            ?>
                <tr id="add"><td colspan=2><a class="add" href="#">Click to add more sites</a></td></tr>
                <tr id="getlevel">
                    <td><input type="button" value="Update Levels" class="getLatest"/></td>
                    <td colspan=2>Last Updated: <?php if(get_option('last_flow_update') == ''){echo "Never";} else{echo date('m-d-y h:i:s', get_option('last_flow_update'));} ?></td>
                    <td colspan=2>Next Scheduled Update: <?php echo date('m-d-y h:i:s',wp_next_scheduled( 'flow_updates' )); ?></td>
                </tr>
            </table>
            </form>
            
            <?php
            
            
        }
        
        function flowrates_shortcode()
        {
            
            global $wpdb;
            
            $rates = $wpdb->get_results("Select * from wp_flowrates Order By name ASC", ARRAY_A);
            
            $html = '<script type="text/javascript">
                        jQuery(document).ready(function($) 
                        { 
                          $.tablesorter.addParser({ 
                     // set a unique id 
                    id: "level", 
                    is: function(s) { 
                        // return false so this parser is not auto detected 
                        return false; 
                  }, 
                  format: function(s) { 
                      // format your data for normalization 
                      var flow = s.split("-");
                      
                      //alert(flow[0].trim());
                      return flow[0].trim();
                   }, 
        // set type, either numeric or text 
        type: "numeric" 
    }); 
    
                            jQuery("#FlowTable").tablesorter({headers: { 2: { sorter:"level" }, 5: {sorter:"digit"}}, 
                                 sortList: [[0,0]] }); 
                        });
                    </script>
                    <table id="FlowTable" class="tablesorter" style="line-height: 15px;" border="1"> 
                        <thead> 
                        <tr> 
                        <th style="width:200px;"><span style="font-size: large;"><strong>Run</strong></span><br /> 
                        <span style="font-size: .7em">[ click for more information ]</span></th> 
                        <th><span style="font-size: large;"><strong>Class</strong></span></th> 
                        <th style="text-align:center; padding:1px; white-space:nowrap; max-width:100px;"><strong><span style="font-size: 1em;">Current Level</span><br /> 
                        </strong></span><span style="font-size: .7em;">[ click for information ]</span></th>
                        <th class="althage-table-sorter-one"><span style="font-size: 1em;"><strong><span><span>Boatable Flows</span></span></strong></span></th> 
                        <th class="althage-table-sorter-one"><strong><span style="font-size: large;">Weather</span></strong></th> 
                        <th class="althage-table-sorter-one"><span style="font-size: 1em;"><span><strong>Time from DC</strong><span style="font-size: .7em"><br/>[ the White House ]</span></span></span></th> 
                        </tr>
                        </thead>
                        <tbody>';
                        
                        foreach($rates as $rate){
                        $rate['change'] = $this->formatNum($rate['change']);
                            $html .= '<tr> 
                            <td><a href="http://'.$rate['awurl'].' ?>" target="_blank">'.$rate['name'].'</a></td> 
                            <td><span>'.$rate['class'].'</span></td>';
                            
                            if($rate['siteid'] == '03189600')
                            {
                                $rate['flowtype'] = 'cfs';
                            }
                           
                            
                            if($rate['siteid'] == 00000000){ $html .='<td>
                                                <span style="font-size:0; visibility:hidden">3-</span>Visual';}
                            else{
                                if($rate['latestlevel'] < $rate['lowerlevel'])
                                {
                                    $html .= '<td style="text-align:center; background-color:#e74c3c;">
                                          <span style="visibility:hidden">2-</span>'; 
                                }
                                elseif($rate['latestlevel'] > $rate['upperlevel'])
                                {
                                    $html .= '<td style="text-align:center; background-color:#3498db;">
                                          <span style="visibility:hidden">1-</span>';
                                }
                                else
                                {
                                    $html .= '<td style="text-align:center; background-color:#2ecc71;">
                                          <span style="visibility:hidden">0-</span>';
                                }
                                $html .= '<a style="color:white;" href="http://waterdata.usgs.gov/usa/nwis/uv?'.$rate['siteid'].'" target="_blank">'.$rate['latestlevel'].' '.$rate['flowtype'].'</a>'; 
                            }
                            
                        if($rate['change'] > 0){
                                $html .='<br/><img class="althage-arrows" src="http://www.dckayak.com/wp-content/uploads/2016/11/new-up-arrow.svg"/><br/><span style="font-size:medium; color:white">'.$rate['change'].' '.$rate['flowtype'].'/hr</span></td>';
                        }
                        elseif($rate['change'] < 0){
                            $html .='<br/><img class="althage-arrows" src="http://www.dckayak.com/wp-content/uploads/2016/11/new-down-arrow.svg"/><br/><span style="font-size:medium; color:white">'.$rate['change'].' '.$rate['flowtype'].'/hr</span></td>';
                        }
                        else{
                         
                          // $imageSrc = $this->thispluginurl.'images/same.png';
                        }                    
                            
                            // $html .='<br/><img src="'.$imageSrc.'"/><br/><span style="font-size:medium; color:white">'.$rate['change'].' '.$rate['flowtype'].'/hr</span></td>';
                    
                            $html .=  '<td class="althage-table-sorter-one"><span style="line-height: 15px;"><span style="font-size: medium;">';
                            if($rate['flowtype'] != ''){ $html .= $rate['lowerlevel'].'-'.$rate['upperlevel'].' '.$rate['flowtype'];} else{ $html.="Visual";}
                            $html .='</span></span></td> 
                            <td class="althage-table-sorter-one"><span style="font-size: medium;"><a href="http://'.$rate['weatherurl'].'" target="_blank">'.$rate['weatherlocale'].'</a></span></td> 
                            <td class="althage-table-sorter-one"><span style="font-size: medium;">'.$rate['travel'].'</span></td> 
                            </tr>';
                        }
                            
                        $html .= '</tbody></table>
                            <span style="font-size:small;">Last Updated: ';
                            if(get_option('last_flow_update') == ''){$html .="Never";} else{ $html .= date('m-d-y h:i:s', get_option('last_flow_update'));}
                            $html .= '</span><br/><br/>';
                                                        
                return $html;
        }
        
        function playspots_map_shortcode()
        {
        
           global $wpdb;
            
            $playspots = $wpdb->get_results("Select * from wp_playspots Order By name ASC", ARRAY_A);
            $level = $wpdb->get_var($wpdb->prepare("SELECT latestlevel FROM wp_flowrates where siteid=01646500"));
          // default atts
  $html = '
        <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
        <script type="text/javascript">
        var infowindow = null;
        jQuery(document).ready(function () { initialize(); });

         function initialize() {

        var centerMap = new google.maps.LatLng(38.97812, -77.23554);

        var myOptions = {
            zoom: 15,
            center: centerMap,
            mapTypeId: google.maps.MapTypeId.SATELLITE
        }

        var map = new google.maps.Map(document.getElementById("map"), myOptions);

        setMarkers(map, sites);
    
    infowindow = new google.maps.InfoWindow({maxWidth:75});
      }

    var sites = [';
    $count = count($playspots);
    $i = 0;
    foreach($playspots as $playspot){
        
  $html .= '[\''.$playspot['name'].'\',\''.str_replace("\n", "<br/>", $playspot['description']).'\','.$playspot['latitude'].','.$playspot['longitude'].',\''.$playspot['name'].'\','.$playspot['lowerlevel'].','.$playspot['upperlevel'].','.$playspot['ideal'].',\'';
        
        
      if($level < $playspot['lowerlevel'])
        {
          $html  .= $this->thispluginurl.'images/kayakr.png\']'; 
        }
        elseif($level > $playspot['upperlevel'])
        {
          $html .= $this->thispluginurl.'images/kayakb.png\']'; 
        }
        else
        {
          $html .= $this->thispluginurl.'images/kayakg.png\']'; 
        }
        
      if($i != $count){$html .= ',';}
        $i++;
    }
  
    $html .= '];



    function setMarkers(map, markers) {
      
      for (var i = 0; i < markers.length; i++) {
            var sites = markers[i];
            var siteLatLng = new google.maps.LatLng(sites[2], sites[3]);
            var marker = new google.maps.Marker({
                position: siteLatLng,
                map: map,
                title: sites[0],
                icon: sites[8], 
                html: "<div style=\"max-height:300px;\">"+sites[4]+"<br/><br/>"+sites[1]+"<br/><br/>Playable:"+sites[5]+" ft - "+sites[6]+" ft</div>"
             
            });

            var contentString = "Some content";


            google.maps.event.addListener(marker, "click", function () {
                infowindow.setContent(this.html);
                infowindow.open(map, this);
            });
            
            if(sites[7] == '.round($level,1).')
            {
              
              marker.setAnimation(google.maps.Animation.BOUNCE);         
          }
          
        }
        
    
        
        google.maps.event.addListener(map, "click", function() {
          infowindow.close(map, this);
        });
    }
</script>

<div id="map" style="height:600px; width:auto; margin: 0 auto; "></div>';

return $html;
        }
        
        function playspots_shortcode()
        {
          global $wpdb;
            
            $rates = $wpdb->get_results("select DISTINCT(a.id), a.name, a.spoturl, a.description, a.upperlevel, a.lowerlevel, a.flowtype, a.ideal, a.class, a.latitude, a.longitude, b.latestlevel, b.weatherurl, b.change, b.weatherlocale from wp_playspots AS a left JOIN wp_flowrates AS b on a.siteid = b.siteid group by a.id order by a.name ASC", ARRAY_A);
            $lfRate = round($wpdb->get_var("Select distinct latestlevel from wp_flowrates where siteid = 01646500"), 2);
            
            $html = '<script type="text/javascript">
                        jQuery(document).ready(function($) 
                        { 
                          $.tablesorter.addParser({ 
                     // set a unique id 
                    id: "level", 
                    is: function(s) { 
                        // return false so this parser is not auto detected 
                        return false; 
                  }, 
                  format: function(s) { 
                      // format your data for normalization 
                      var flow = s.split("-");
                      
                      //alert(flow[0].trim());
                      return flow[0].trim();
                   }, 
        // set type, either numeric or text 
        type: "numeric" 
    }); 
    
                            jQuery("#FlowTable").tablesorter({headers: { 2: { sorter:"level" }}, 
                                 sortList: [[1,0]] }); 
                        });
                    </script> 
                        <span style="font-size:x-large">Current Level at Little Falls: <a style="font-size:xx-large;" href="http://waterdata.usgs.gov/usa/nwis/uv?01646500">'.$lfRate.'</a></span>
                    <table id="FlowTable" class="tablesorter" style="line-height: 10px;" border="1"> 
                        <thead> 
                        <tr> 
                        <th><span style="font-size: large;"><strong>Spot</strong></span><br /> 
                        <th style="text-align:center; padding:1px;"><strong><span style="font-size: large; color: #000000;">Ideal Level</span><br /></th> 
                        <th style="text-align:center;"><span style=" font-size: medium;"><strong><span style="font-size: medium;"><span style="color: #000000;">Boatable Flows</span></span></strong></span></th> 
                        <!--<th><strong><span style="font-size: large;">Current</span></strong></th>-->
                        
                        
                        <!-- <th><span style="font-size: 8px;"><strong><span style="font-size: medium;"><span style="color: #000000;">Description</span></span></strong></span></th> -->
                        </tr>
                        </thead>
                        <tbody>';
                        
                        foreach($rates as $rate){
                    
                            $html .= '<tr> 
                            <td style="font-size: medium; line-height:15px;">';
                            
                            if($rate['spoturl']!="enter url")
                            {
                              $html .= '<a href="'.$rate['spoturl'].'">'.$rate['name'].'</a></td>';                            
                            }
                            else
                            {
                              $html .= $rate['name'].'</td>';
                            }
                             
                                if($lfRate != $rate['ideal'])
                                {
                                    $html .= '<td style="text-align:center;">
                                          <span style="font-size:0; visibility:hidden">1-</span>'; 
                                }
                                else
                                {
                                    $html .= '<td style="text-align:center; background-color:#2ecc71; color:white;">
                                          <span style="font-size:0; visibility:hidden">0-</span>';
                                }
                                $html .= $rate['ideal'];  
                            
                            $html .='</td>';  
                              if($lfRate < $rate['lowerlevel'])
                                  {
                                      $html .= '<td style="text-align:center; background-color:#e74c3c;">
                                            <span style="font-size:medium; visibility:hidden">2-</span>'; 
                                  }
                                  elseif($lfRate > $rate['upperlevel']) 
                                  {
                                      $html .= '<td style="text-align:center; background-color:#3498db;">
                                            <span style="font-size:medium; visibility:hidden">1-</span>';
                                  }
                                  else
                                  {
                                      $html .= '<td style="text-align:center; background-color:#2ecc71;">
                                            <span style="font-size:medium; visibility:hidden">0-</span>';
                                  }
                              $html .= '<span style="color: white">'.$rate['lowerlevel'].'-'.$rate['upperlevel'].' ft</span>';
                              $html .='</span></td> 
                                  <!-- <td><span style="font-size: medium; line-height:15px;">'.$rate['description'].'</span></td> -->';
                              // $html .= '<td><a style="color:white;" href="http://waterdata.usgs.gov/usa/nwis/uv?'.$rate['siteid'].'" target="_blank">'.$rate['latestlevel'].' '.$rate['flowtype'].'</a></td>';
                              
                            // if($rate['latestlevel'] != null){
                            //       if($rate['latestlevel'] < $rate['lowerlevel'])
                            //       {
                            //           $html .= '<td style="text-align:center; background-color:#e74c3c;">
                            //                 <span style="font-size:medium; visibility:hidden">2-</span>'; 
                            //       }
                            //       elseif($rate['latestlevel'] > $rate['upperlevel'])
                            //       {
                            //           $html .= '<td style="text-align:center; background-color:#3498db;">
                            //                 <span style="font-size:medium; visibility:hidden">1-</span>';
                            //       }
                            //       else
                            //       {
                            //           $html .= '<td style="text-align:center; background-color:#2ecc71;">
                            //                 <span style="font-size:medium; visibility:hidden">0-</span>';
                            //       }
                            //       $html .= '<a style="color:white;" href="http://waterdata.usgs.gov/usa/nwis/uv?'.$rate['siteid'].'" target="_blank">'.$rate['latestlevel'].' '.$rate['flowtype'].'</a>'; 

                                      
                            //       if($rate['change'] > 0){
                            //         $imageSrc = $this->thispluginurl.'images/up.png';
                            //       }
                            //       elseif($rate['change'] < 0){
                            //         $imageSrc = $this->thispluginurl.'images/down.png';
                            //       }
                            //       else{
                            //         $imageSrc = $this->thispluginurl.'images/same.png';
                            //       }  
                            //       //  
                            //       $html .='<span style="font-size:medium; color:white">'.$rate['change'].' '.$rate['flowtype'].'/hr</span></td>';
                                            
                            //   }else{
                            //     $html.='<td></td>';
                            //   }    
                                
                            // $html .=  '<td><span style="font-size: medium;"><a href="http://'.$rate['weatherurl'].'" target="_blank">'.$rate['weatherlocale'].'</a></span></td>';
                            $html .= '</tr>';
                        }
                            
                        $html .= '</tbody></table>
                            <span style="font-size:x-small;text-transform:uppercase">Last Updated: ';
                            if(get_option('last_flow_update') == ''){$html .="Never";} else{ $html .= date('m-d-y h:i:s', get_option('last_flow_update'));}
                            $html .= '</span><br/><br/>';
                                                        
                return $html;
        }
        
        function save_table()
        {
            if (! wp_verify_nonce($_POST['wpnonce'], 'USGS_Flow_Class-add-edit-site') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
            global $wpdb;
            $newValue = $_POST['new_value'];
            $field = $_POST['field'];
            $id = $_POST['id'];
            $table_name = $_POST['table_name'];
            if($field == 'name'||$field == 'description')
            {
              
              $newValue = htmlspecialchars(stripslashes($newValue), ENT_QUOTES);
              //echo $newValue;
            }
            
            if($field == 'runnable')
            {
                $runnable = preg_split("/(?!^-[0-9])[\s]*( |-)[\s]*/", $newValue);
                //echo print_r($runnable);
                $wpdb->update( 'wp_'.$table_name, array('upperlevel' => $runnable[1],
                                                     'lowerlevel' => $runnable[0],
                                                     'flowtype' => $runnable[2]), array('id' => $id));
            }
            elseif($field == 'latlon')
            {
              $latlon = explode(",",$newValue);
              echo print_r($latlon);
              $wpdb->update( 'wp_'.$table_name, array('latitude' => $latlon[0],
                                                      'longitude' => $latlon[1]),
                                                      array('id' => $id));
            }
            else
            {
                $wpdb->update( 'wp_'.$table_name, array( $field => $newValue), array('id' => $id));
            }
           // echo date('m-d-y h:i:s',wp_next_scheduled( 'flow_updates' ));
           // wp_schedule_single_event(time(), 'flow_updates');
            die();
        }
        
        function add_site()
        {
            if (! wp_verify_nonce($_POST['wpnonce'], 'USGS_Flow_Class-add-edit-site') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 

            global $wpdb;
        
            $fields = explode("&",$_POST['data']);
        
            foreach($fields as $field){
                $field_key_value = explode("=",$field);
            
                $key = urldecode($field_key_value[0]);
        
                $value = urldecode($field_key_value[1]);    
            
                eval("$$key = \"$value\";");
                if($value == '')
                {
                    $value = "Enter Info";
                }
                $newsite[$key] = $value;
            }
            $runnable = preg_split("/(?!^-[0-9])[\s]*( |-)[\s]*/", $newsite['runnable']);
            $newsite['name'] = htmlspecialchars(stripslashes($newsite['name']), ENT_QUOTES);
            
            $wpdb->insert( 'wp_flowrates', array( 'siteid' => $newsite['siteid'], 'name' => $newsite['name'],
                                                  'flowtype' => $runnable[2], 'weatherurl' => $newsite['weatherurl'],
                                                  'upperlevel' => $runnable[1], 'lowerlevel' => $runnable[0],
                                                  'class' => $newsite['class'], 'travel' => $newsite['travel'],
                                                  'weatherlocale' => $newsite['weatherlocale'], 'awurl' => $newsite['awurl']));
  
            die();
        }
        
      function add_playspot()
        {
            if (! wp_verify_nonce($_POST['wpnonce'], 'USGS_Flow_Class-add-edit-site') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 

            global $wpdb;
          //echo "hello";
            $fields = explode("&",$_POST['data']);
        
            foreach($fields as $field){
                $field_key_value = explode("=",$field);
            
                $key = urldecode($field_key_value[0]);
        
                $value = urldecode($field_key_value[1]);    

                eval("$$key = \"$value\";");
                if($value == '')
                {
                    $value = "Enter Info";
                }
                $playspot[$key] = $value;
            }
            $runnable = preg_split("/(?!^-[0-9])[\s]*( |-)[\s]*/", $playspot['runnable']);
        
            $latlon = explode(",",$playspot['latlon']);
            
            $playspot['name'] = htmlspecialchars(stripslashes($playspot['name']), ENT_QUOTES);
      $playspot['description'] = htmlspecialchars(stripslashes($playspot['description']), ENT_QUOTES);
            $wpdb->insert( 'wp_playspots', array( 'name' => $playspot['name'], 'siteid' => $playspot['siteid'], 'description' => $playspot['description'], 'upperlevel' => $runnable[1], 'lowerlevel' => $runnable[0],
                                                  'flowtype' => $runnable['2'], 'ideal' => $playspot['ideal'], 'latitude' => $latlon[0], 'longitude' => $latlon[1], 'spoturl' => $playspot['spoturl']));
      
            die();
        }
        
        function delete_site()
        {
            if (! wp_verify_nonce($_POST['wpnonce'], 'USGS_Flow_Class-add-edit-site') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 

            $id = $_POST['id'];
            $table_name = $_POST['table_name']; 
            global $wpdb;
            
            //echo $id;
            
            $wpdb->query("DELETE FROM wp_".$table_name." WHERE id = $id");
            //echo $wpdb->last_result();
            die();
        }
        
        function update_levels()
        {
            global $wpdb;
            $flows = new newFlow;
            $forced = false;
            $forced = $_POST['forced'];
            
            if($forced)
            {
                $lastUpdate = NULL;
            }
            else
            {
                $lastUpdate = get_option('last_flow_update');
            }
        
            $sites = $wpdb->get_results("Select Distinct siteid, latestlevel, historicallevels from wp_flowrates where flowtype = 'cfs'", ARRAY_A);

            if(!is_null($sites))
            {
                $end = end($sites);
                 foreach($sites as $site){
                    $CFSsiteString .= $site['siteid'];
                    if($end['siteid'] != $site['siteid']){
                        $CFSsiteString .= ',';
                    }
                    $lastLevel[$site['siteid']] = $site['latestlevel'];
                 } 
                 // echo $lastUpdate;
                $siteFlows = $flows->GetCFS($CFSsiteString, $lastUpdate);
                $siteChanges = $flows->GetChange($CFSsiteString, 'cfs');
                //for testing 
                // echo print_r($siteChanges);
                if(!is_null($siteFlows))
                {
                    foreach($siteFlows as $cfsSite){
                      $wpdb->update( 'wp_flowrates', array('latestlevel' => $cfsSite['flow']), array('siteid' => $cfsSite['id'], 'flowtype' => 'cfs'));
                    }
                }
                
                if(!is_null($siteChanges))
                {
                  foreach($siteChanges as $cfsChange){
                        $wpdb->update( 'wp_flowrates', array('change' => $cfsChange['change']), array('siteid' => $cfsChange['id'], 'flowtype' => 'cfs'));
                  }
                }
            }
            
            $sites = $wpdb->get_results("Select Distinct siteid from wp_flowrates where flowtype = 'ft'", ARRAY_A);
            
            if(!is_null($sites))
            {   
                $end = end($sites);
                 foreach($sites as $site){
                    $GHsiteString .= $site['siteid'];
                    if($end['siteid'] != $site['siteid']){
                        $GHsiteString .= ',';
                    }
                 }
                // echo $siteString;
                $siteFlows = $flows->GetGH($GHsiteString, $lastUpdate);
                $siteChanges = $flows->GetChange($GHsiteString, 'ft');
                // echo print_r($siteFlows);
                if(!is_null($siteFlows))
                {
                    foreach($siteFlows as $ghSite){
                        $wpdb->update( 'wp_flowrates', array( 'latestlevel' => $ghSite['flow']), array('siteid' => $ghSite['id'], 'flowtype' => 'ft'));
                        if($ghSite['id'] == '03189600')
                        {
                                $g = $ghSite['flow'];
                                if ($g >= 8.9) { $x=(-0.0344*pow($g,5))+(2.6808*pow($g,4))-(77.748*pow($g,3))+(1150.7*pow($g,2))-(7873.3*$g)+19890; }
                                elseif ($g >= 7.0) { $x=(69.822*pow($g,2))-(656.23*$g)+1354.5; }
                                elseif ($g >= 6.5) { $x=(171.43*pow($g,2))-(2104.1*$g)+6474; }
                                else { $x=40; }
                                $ghSite['flow'] = number_format($x,0,'','');
                                $wpdb->update('wp_flowrates', array('latestlevel' => $ghSite['flow']), array('siteid' => $ghSite['id']));
                        }
                        if($ghSite['id'] == '03185400')
                        {
                             $latestLevel = number_format((-4.660000)+(1.330000*($ghSite['flow'])), 2);
                             $wpdb->update('wp_flowrates', array('latestlevel' => $latestLevel), array('siteid' => 00000001));
                        }
                    }
                }
                if(!is_null($siteChanges))
                {
                  foreach($siteChanges as $GHChange){
                        $wpdb->update( 'wp_flowrates', array( 'change' => $GHChange['change']), array('siteid' => $GHChange['id'], 'flowtype' => 'ft'));
          }
                }
            }
          echo "help";
          update_option('last_flow_update', time());
             die();
        }
        
        function playspots_to_csv() {
        	global $wpdb;
        	$playspots = $wpdb->get_results("Select id, name, description, upperlevel, lowerlevel, ideal, latitude, longitude FROM wp_playspots", ARRAY_A);
        	
        	$latestLevel=$wpdb->get_var("SELECT latestlevel FROM `wp_flowrates` WHERE siteid = 01646500 LIMIT 1");
        
		$fp = fopen('playspots.csv', 'w');
		
		fputcsv($fp, array('id', 'name', 'description', 'upperlevel', 'lowerlevel', 'ideal', 'latitude', 'longitude', 'display'));
		
		 foreach ($playspots as $spot) {
		 	if ($latestLevel < $spot['lowerlevel']) {
		 		$spot['display']= 'red';
		 	} elseif ($latestLevel >= $spot['lowerlevel'] and $latestLevel <= $spot['upperlevel']) {
		 		$spot['display'] = 'green';
		 	} elseif ($latestLevel > $spot['upperlevel']) {
		 		$spot['display'] = 'blue';
		 	}
		 	fputcsv($fp, $spot);
		}
		 
		fclose($fp);
		 
        	
        	
        }
        
        function flow_scripts()
        {
            //$flow_plugin_url = trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) );
            
            if (!is_admin())
            {
              wp_register_script('flow_sort_script',  plugins_url('/includes/jquery.tablesorter.min.js', __FILE__), array('jquery'));
               wp_enqueue_script('flow_sort_script', plugins_url('/includes/jquery.tablesorter.min.js', __FILE__), array('jquery'));
            }
            
            //echo '<script type="text/javascript" src="'.$this->thispluginpath.'/includes/sorttable.js"></script>'."\n";
        }
        
        protected function formatNum($num)
        {
          if($num == 0)
          {
          return $num;
      }
      else{return sprintf("%+g",$num);}
    }
  } //End Class
} //End if class exists statement


//instantiate the class
if (class_exists('USGS_Flow_Class')) {
    $USGS_Flow_Class_var = new USGS_Flow_Class();
}
?>