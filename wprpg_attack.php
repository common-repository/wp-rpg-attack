<?php
/*
  Plugin Name: WP RPG Attack Module
  Plugin URI: http://wordpress.org/extend/plugins/wp-rpg-attack/
  Version: 1.0.6
  wpRPG: 1.0.11
  Author: <a href="http://tagsolutions.tk">Tim G.</a>
  Description: RPG Attack Elements added to WP by means of wpRPG
  Text Domain: wp-rpg-attack
  License: GPL2
 */
 /*
	Globals
 */
global $wpdb;
 
/*
	Definitions
	@since 1.0.0
*/
define('WPRPG_Attack_Plugin_File', plugin_basename( __FILE__ ));
define('WPRPG_Attack_Version', '1.0.6');

 /*
	WPRPG Class Loader
	@since 1.0.0
*/
function Attack_wpRPGcheck() {
	if ( class_exists( 'wpRPG' ) ) {
		if(!class_exists('wpRPG_Attack')){
			include(__DIR__. '/wprpg-attack-class.php');
		}
   		$rpgAttack = new wpRPG_Attack;
   		include ( __DIR__.'/wprpg-attack-library.php');
	}
}
add_action( 'plugins_loaded', 'Attack_wpRPGcheck' );
/*
	Plugin Activations / Uninstall
	@since 1.0.0
*/
function Attack_Activate()
{
	add_option('Activated_Plugin','wpRPG-Attack');
}

register_activation_hook( __FILE__, 'Attack_Activate');
register_deactivation_hook(__FILE__, 'wpRPG_Attack_on_deactivation');
register_uninstall_hook(__FILE__, 'wpRPG_Attack_on_uninstall');

function wpRPG_Attack_on_deactivation() {
    if (!current_user_can('activate_plugins'))
        return;
    $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
	update_option('WPRPG_Attack_installed', 0);
}

function wpRPG_Attack_on_uninstall() {
    global $wpdb;
    if (!current_user_can('activate_plugins'))
        return;
		
    check_admin_referer('bulk-plugins');
   
   if (__FILE__ != WP_UNINSTALL_PLUGIN)
        return;
	if ($wpdb->query("DROP TABLE `" . $wpdb->prefix . "_attack_log`") == FALSE) {
		$errors[] = "You had an error occur! Attack Log wasn't DROPPED!<br />";
		$errors[] = $wpdb->last_error;
	}
}

?>