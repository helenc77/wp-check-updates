<?php

/**
 * Plugin Name: Check for Updates
 * Plugin URI: 
 * Description: Check for Updates across core, plugins, themes and sites on WAMP
 * Version: 1.0.1
 * Author: Helen Connole
 * Author URI: http://twitter.com/helenc77
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: wp-check-updates
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! function_exists( 'plugins_api' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
}

// Only load class if it hasn't already been loaded
if ( !class_exists( 'hcCheckForUpdates' ) ) {
    
    class hcCheckForUpdates{
        
        public $path_to_sites = 'C:/wamp64/www/wordpress_sites/';
        public $site_loop_data_array = array();
        public $list_all_plugins = array();
        public $list_all_themes = array();
        
        //function to get latest wp version
        public function core_update_check() {
            
            do_action( "wp_version_check" ); // force WP to check its core for updates
            $update_core = get_site_transient( "update_core" ); // get information of updates
            
            $latest_wp_version = $update_core->updates['0']->version;
            
            //var_dump($update_core);

            return $latest_wp_version;
        }
        
        function get_instance_wp_version($site){

            //go to path wite/wp-includes/version.php
            $file_path = $this->path_to_sites.$site."/wp-includes/version.php";
            
            if(file_exists($file_path)){
                
                $lines_array = file($file_path);
                
                $search_string = "wp_version";

                foreach($lines_array as $line) {
                    if(strpos($line, $search_string) !== false) {
                        $new_str = explode(" = ", $line);
                    }
                }

                //trim
                $instance_wp_version_str = $new_str['1'];
                $instance_wp_version_str2 = trim($instance_wp_version_str);
                $instance_wp_version_str3 = trim($instance_wp_version_str2,";");
                $instance_wp_version = trim($instance_wp_version_str3,"'");

                return $instance_wp_version;
                
            } else {
                
                return false;
                
            }
        }
        
        function get_all_plugin_versions(){
            foreach($this->list_all_plugins as $plugin_key => $plugin_val){
                $this->list_all_plugins[$plugin_key]['plugin_version'] = $this->get_latest_plugin_version($plugin_val['plugin_name']);
            }
        }
        
        function get_all_theme_versions(){
            
            foreach($this->list_all_themes as $theme_key => $theme_val){
                $this->list_all_themes[$theme_key]['theme_version'] = $this->get_latest_theme_version($theme_val['theme_name']);
            }
            
        }
        
        function get_all_site_plugins($site_name){
            
            $plugins_with_versions_array = array();
            
            $path_to_plugins = $this->path_to_sites.$site_name.'/wp-content/plugins/';
            
            $plugins = scandir($path_to_plugins);
            
            //loop through list
            foreach ($plugins as $plugin_key => $plugin_name) {

                //exclude stuff we don't want
                if('.' != $plugin_name && '..' != $plugin_name && 'index.php' != $plugin_name){
                    
                    //get file versions
                    $plugin_version = $this->get_plugin_file_version($site_name,$plugin_name);
                    $plugins_with_versions_array[$plugin_name] = $plugin_version; //get plugins with versions
                    //$plugins_with_versions_array[$plugin_name] = 'not checked'; //get plugins list sans versions
                    
                    //add plugins to the global plugins list
                    $this->list_all_plugins[$plugin_name] = array(
                        'plugin_name' => $plugin_name,
                        'plugin_version' => 'not checked'
                    );
                }
                
            }
            
            return $plugins_with_versions_array;
            
        }
        
        function get_all_site_themes($site_name){
            
            $themes_with_versions_array = array();
            
            $path_to_themes = $this->path_to_sites.$site_name.'/wp-content/themes/';
			
			if(file_exists($path_to_themes)){
				
				$themes = scandir($path_to_themes);
            
				//loop through list
				foreach ($themes as $theme_key => $theme_name) {

					//exclude stuff we don't want
					if('.' != $theme_name && '..' != $theme_name && 'index.php' != $theme_name){
						
						//get file versions
						$theme_version = $this->get_theme_file_version($site_name,$theme_name);
						$themes_with_versions_array[$theme_name] = $theme_version; //get themes with versions
						//$themes_with_versions_array[$theme_name] = 'not checked'; //get themes list sans versions
						
						//add themes to the global themes list
						$this->list_all_themes[$theme_name] = array(
							'theme_name' => $theme_name,
							'theme_version' => 'not checked'
						);
					}
					
				}
				
			}
                  
            return $themes_with_versions_array;
        }

        function get_plugin_file_version($site_name,$plugin_name){
            
            //write special cases for oddly named common plugin files e.g. yoast
            switch($plugin_name){
                case 'wordpress-seo': 
                    $plugin_file_name = 'wp-seo';
                    break;
                case 'contact-form-7': 
                    $plugin_file_name = 'wp-contact-form-7';
                    break;
                case 'advanced-custom-fields': 
                    $plugin_file_name = 'acf';
                    break;
                case 'advanced-custom-fields_pro': 
                    $plugin_file_name = 'acf';
                    break;
                case 'google-sitemap-generator': 
                    $plugin_file_name = 'sitemap';
                    break;
                default:
                    $plugin_file_name = $plugin_name;
            }
            
            $file_path =  $this->path_to_sites.$site_name.'/wp-content/plugins/'.$plugin_name.'/'.$plugin_file_name.'.php';

            //does file exist?
            if(file_exists($file_path)){
                
                //get plugin from another site
                $get_plugin_data = get_plugin_data( $file_path, $markup = true, $translate = true );

                return $get_plugin_data['Version'];
                
            } else {
                
                return 'unknown';
                
            }
            
        }
        
        function get_theme_file_version($site_name,$theme_name){
            
            $file_path =  $this->path_to_sites.$site_name.'/wp-content/themes/';
            
            //get theme data
            $this_theme = wp_get_theme( $theme_name, $file_path );

            return $this_theme->get( 'Version' );
            
        }

        //function to get latest version of any plugin
        public function get_latest_plugin_version($plugin_slug_name){

            $args = array(
                'slug' => $plugin_slug_name,
                'fields' => array(
                    'version' => true,
                )
            );

            $call_api = plugins_api( 'plugin_information', $args );
            
            if(isset($call_api->version)){
                
                return $call_api->version;
                
            } else {
                
                return 'unknown';
                
            }

        }
        
        //function to get latest version of any theme
        public function get_latest_theme_version($theme_slug_name){
            
            //TODO: integrate with Envato Marketplace API to get latest theme data from there
            //https://github.com/envato/envato-wordpress-toolkit

            $args = array(
                'slug' => $theme_slug_name,
                'fields' => array(
                    'version' => true,
                )
            );

            $call_api = themes_api( 'theme_information', $args );
            
            if(isset($call_api->version)){
                
                return $call_api->version;
                
            } else {
                
                return 'unknown';
                
            }

        }
        
        //get a list of all the wamp sites
        function get_all_wamp_sites(){
            
            $dir    = $this->path_to_sites;
            $files = scandir($dir);
            
            //exclude stuff we don't want
            foreach ($files as $key => $link) {
                
                //not a directory
                if(!is_dir($dir.$link)){
                    unset($files[$key]);
                }
                
                //dots
                if('.' == $link){
                    unset($files[$key]);
                }
                if('..' == $link){
                    unset($files[$key]);
                }
                
                //New folder
                if('New folder' == $link){
                    unset($files[$key]);
                }
                
                //archive
                if('old' == $link){
                    unset($files[$key]);
                }
                
                //not wordpress
                if('not_wordpress' == $link){
                    unset($files[$key]);
                }
            }
            
            return($files);
        }
        
        function sites_loop_get_data($sites){
            
            foreach($sites as $site){
                
                //if is a wp site
                if($this->get_instance_wp_version($site)){
                    
                    //get wp version
                    $this->sites_loop_data[$site]['site_wp_version'] = $this->get_instance_wp_version($site);
                    
                    //get plugins list
                    $this->sites_loop_data[$site]['plugins_list'] = $this->get_all_site_plugins($site);
                    
                    //get themes list
                    $this->sites_loop_data[$site]['themes_list'] = $this->get_all_site_themes($site);
                    
                }
            }

        }
        
    }
    
}

/* Admin page menu */
add_action( 'admin_menu', 'hc_check_for_updates_plugin_menu' );
function hc_check_for_updates_plugin_menu() {
    add_options_page( 'Check for Updates Status', 'Check for Updates', 'manage_options', 'hc_check_for_updates', 'hc_check_for_updates_plugin_options' );
	add_submenu_page( null, 'Check for Updates Status Run', 'Run Checker', 'manage_options', 'hc_check_for_updates_run', 'hc_check_for_updates_run_options' );  
}

/* add scripts and styles*/
add_action( 'admin_enqueue_scripts', 'hc_check_for_updates_add_scripts' );
function hc_check_for_updates_add_scripts(){
	wp_enqueue_style( 'check-for-updates-style', plugin_dir_url( __FILE__ ) . 'style.css');	
}

/* Admin page content */
function hc_check_for_updates_plugin_options() {
	
	if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

	echo('<h1>Wordpress Check for Updates</h1>
	<p>Be aware that checking all your plugins and themes can take a few minutes depending on how many you have! You may want to make a cup of tea...</p>
	<p><a class="hc_check_button" href="options-general.php?page=hc_check_for_updates_run">Run the checker</a></p>');
    
}

function hc_check_for_updates_run_options() {
    
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
	
	$hcCheckForUpdates = new hcCheckForUpdates();
    $core_wp_version = $hcCheckForUpdates->core_update_check();
	
    $all_wamp_sites = $hcCheckForUpdates->get_all_wamp_sites();
    $hcCheckForUpdates->sites_loop_get_data($all_wamp_sites);
	
    //possibly need to run these as scheduled tasks if it takes too long
    $hcCheckForUpdates->get_all_plugin_versions();
    $hcCheckForUpdates->get_all_theme_versions();
    
    $plugins_and_versions = $hcCheckForUpdates->list_all_plugins;
    $themes_and_versions = $hcCheckForUpdates->list_all_themes;
	
    //$hcCheckForUpdates->get_instance_wp_version('bartlett');
    //echo($hcCheckForUpdates->get_latest_plugin_version('wordpress-seo'));
    //echo($hcCheckForUpdates->get_plugin_file_version());
    
    echo('<h1>Wordpress Check for Updates</h1>
        <p>The latest stable Wordpress version is '.$core_wp_version.'</p><table>
        <tr>
            <th>Site</th>
            <th>WP version</th>
            <th>Plugins</th>
            <th>Themes</th>
        </tr>');
    
    foreach($hcCheckForUpdates->sites_loop_data as $site_key => $site_data){
        
        echo('<tr><td>'.$site_key.'</td>');        

        //is wp up to date?
        if($core_wp_version != $site_data['site_wp_version']){
            $wp_status = 'red';
        } else {
            $wp_status = 'black';
        }
        echo('<td style="color:'.$wp_status.'">'.$site_data['site_wp_version'].'</td>');
        
        echo('<td>');
        foreach($site_data['plugins_list'] as $plugin_name => $plugin_version){
            
            //is plugin up to date?
            if('unknown' == $plugin_version || $plugins_and_versions[$plugin_name]['plugin_version'] != $plugin_version){
                
                echo($plugin_name.' (<span style="color:red">'.$plugin_version.'</span> update to '.$plugins_and_versions[$plugin_name]['plugin_version'].') <br />');
                
            } else {
                
                echo($plugin_name.' ('.$plugin_version.') <br />');
                
            }
            
            
        }
        echo('</td>');
        
        echo('<td>');
        foreach($site_data['themes_list'] as $theme_name => $theme_version){
            
            //is theme up to date?
            if('unknown' == $theme_version || $themes_and_versions[$theme_name]['theme_version'] != $theme_version){
                
                echo($theme_name.' (<span style="color:red">'.$theme_version.'</span> update to '.$themes_and_versions[$theme_name]['theme_version'].') <br />');
                
            } else {
                
                echo($theme_name.' ('.$theme_version.') <br />');
                
            }
            
            
        }
        echo('</td>');
    }
    
    echo('</tr>
    <table>');
   
}
?>