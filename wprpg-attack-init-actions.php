<?php
add_action('admin_init', array($rpgAttack,'wpRPG_attack_load_plugin'));
add_action('admin_init', array($rpgAttack,'RegisterSettings'));
add_action('wp_ajax_attack', array($rpgAttack,'attack_callback'));
add_action('wp_ajax_nopriv_attack', array($rpgAttack,'attack_callback'));
add_action('admin_menu', array($rpgAttack,'Attack_add_attack_to_rpg_menu')); 
add_filter('wpRPG_add_admin_tab_header', array($rpgAttack, 'Attack_add_admin_tab_header'));
add_filter('wpRPG_add_admin_tabs', array($rpgAttack, 'Attack_add_admin_tab'));
add_filter('listPlayers_Loggedin_Actions', array($rpgAttack, 'Attack_ListPlayers_Action_Button'),1,1);
add_filter('profile_section_top_right', array($rpgAttack, 'Attack_add_actions_profile'),2,2);
add_filter('wpRPG_add_plugin_code', array($rpgAttack, 'Attack_Jquery_Code'));
if (!is_admin()) 
{
	add_action('wp_loaded', array($rpgAttack,'Attack_flush_rules') );
	add_filter('rewrite_rules_array', array($rpgAttack, 'Attack_insert_rewrite_rules') );
	add_filter('query_vars',array($rpgAttack, 'Attack_insert_query_vars') );
	
	if (isset($_POST['attacking']) && $_POST['attacking'] == 1) {
		$rpgAttack->Attack($_POST['attacker'], $_POST['defender']);
	}
}
?>