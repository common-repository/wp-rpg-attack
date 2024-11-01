<?php
class wpRPG_Attack extends wpRPG
{
	protected $file = __FILE__;
	protected $plugslug = '';
	function __construct()
	{
		parent::__construct();
		global $wp_rewrite;
		$this->wpRPG = new wpRPG;
		$this->plugslug = basename(dirname(__FILE__));
		$this->version = WPRPG_Attack_Version;
		add_shortcode('permalink',array($this, 'custom_permalink') );
	}

	function Attack_Jquery_Code($code)
	{
		global $current_user;
		$attack_code = array(
			"$('button#attack').click(function() {
				var them = $(this).attr('name');
				var you = ".$current_user->ID." ;
				$.ajax({
					method: 'post',
					url: '". site_url('wp-admin/admin-ajax.php')."',
					data: {
						'action': 'attack',
						'attacker': you,
						'defender': them,
						'ajax': true,
						'attacking': true
					},
					success: function(data) {
						$('#rpg_area').empty();
						$('#rpg_area').html(data);
					}
				});
			});"
		);
		return array_merge($code, $attack_code);
	}
	
	function Attack_add_actions_profile($actions)
	{
		global $current_user;
		$rpgProfile = new wpRPG_Profiles;
		$player = $rpgProfile->get_viewed_player();
		if(is_user_logged_in()){
			if($current_user->ID != $player->ID){
				$attack_button = '<button id="attack" name="'.$player->ID.'">Attack</button>';
				return array(
					 'attack' =>($current_user?$attack_button:null),
				);
			}
		}
		return array();
	}
	
	function Attack_ListPlayers_Action_Button($actions)
	{
		global $wpdb, $current_user;
		
		$res = get_users();
		foreach ($res as $u) {
			if(is_user_logged_in()){
				if($u->ID != $current_user->ID){
					$button = '<button id="attack" name="'.$actions['id'].'">Attack</button>';
					$attack_action = array('attack'=>$button);
					//return $attack_action;
					return array_merge($actions, $attack_action);
				}
			}
		}
		return $actions;
	}
	function Attack_add_admin_tab($tabs)
	{
		$tab_page = array('attack'=>$this->Attack_options(1));
		return array_merge($tabs, $tab_page);
	}
	
	function Attack_add_admin_tab_header($tabs)
	{
		$attack_tabs = array('attack'=>'Attack Settings');
		return array_merge($tabs, $attack_tabs);
	}
	
	function AttackFormula($attacker, $defender)
	{
		global $wpdb;
		$attack = array();
		$defend = array();
		$attack['result'] = $this->get_player_meta($attacker);
		$defend['result'] = $this->get_player_meta($defender);
		$attack['level'] = $this->wpRPG_player_level($attack['result']->xp);
		$defend['level'] = $this->wpRPG_player_level($defend['result']->xp);
		$attack['score'] = $this->Attack_calculate_scores($attack['result']->xp, $attack['result']->hp, $attack['level'], $attacker);
		$defend['score'] = $this->Attack_calculate_scores($defend['result']->xp, $defend['result']->hp, $defend['level'], $defender);
		
		if ($attack['result']->hp == 0) {
			return 'You can not attack with 0 HP!<br /><a href="#" onclick="location.reload(true); return false;">Reload Members List</a>';
		} elseif ($defend['result']->hp == 0) {
			$defend['score'] = $defend['score'] * '.75'; //Attacker bonus! 
		}
		if ($attack['score'] >= $defend['score']) { //attacker wins
			$loser['pid'] = $defender;
			$loser['score'] = $defend['score'];
			$loser['level'] = $defend['level'];
			$loser['min'] = max(0, ($defend['result']->strength + $defend['result']->defense) - 3);
			$loser['max'] = $defend['result']->strength + $defend['result']->defense + 2;
			$loser['damage'] = mt_rand ($loser['min'], $loser['max']);
			$loser['hp'] = $defend['result']->hp - $loser['damage'];
			if ($loser['hp'] <= 0) {
				$loser['hp'] = 0;
			}
			$winner['pid'] = $attacker;
			$winner['score'] = $attack['score'];
			$winner['level'] = $attack['level'];
			$winner['min'] = max(0, ($attack['result']->strength + $attack['result']->defense) - 3);
			$winner['max'] = $attack['result']->strength + $attack['result']->defense + 2;
			$winner['damage'] = mt_rand ($winner['min'], $winner['max']);
			$winner['hp'] = $attack['result']->hp - $winner['damage'];
			if ($winner['hp'] <= 0) {
				$winner['hp'] = 0;
			}
			if($loser['level'] >= $winner['level']){
				if($loser['level'] == $winner['level']){
					//echo 'Round( (1)*'. mt_rand (1,4) .'+ ( xp '. mt_rand ($defend['result']->xp,$attack['result']->xp) .'/2))+'.$attack['result']->xp;
					if ($defend['result']->xp < $attack['result']->xp)
						$winner['xp'] = round( mt_rand (1, 4) + ( mt_rand ($defend['result']->xp,($attack['result']->xp<=2?2:$attack['result']->xp)/2)))+ $attack['result']->xp;
					else
						$winner['xp'] = round( mt_rand (1, 4) + ( mt_rand (0,($attack['result']->xp<=12?12:$attack['result']->xp)/2)))+ $attack['result']->xp;
				}else{
					//echo 'Round( ('. $loser['level'] .'-'.$winner['level'].')*'. mt_rand (1,4) .'+ ( xp '. mt_rand ($defend['result']->xp,$attack['result']->xp) .'/2))+'.$attack['result']->xp;
					$winner['xp'] = round(  ( $loser['level'] - $winner['level']) * mt_rand (1, 4) + ( mt_rand ($defend['result']->xp,($attack['result']->xp<=2?2:$attack['result']->xp)/2)))+ $attack['result']->xp;
				}
			}else{ 
				$winner['xp'] = round ( (mt_rand (1,6) +( $attack['result']->xp/2))+ $attack['result']->xp);
			}
			$this->Attack_update_results_battle($winner, $loser, $attacker, $defender);
			return '<p>' . $this->get_player_meta($attacker)->nickname . ' Won!</p>' . $this->Attack_print_results_battle($winner, $loser, $attacker, $defender);
		} else { //attacker loses
			$loser['pid'] = $attacker;
			$loser['score'] = $attack['score'];
			$loser['level'] = $attack['level'];
			$loser['min'] = max(0, ($attack['result']->strength + $attack['result']->defense) - 3);
			$loser['max'] = $attack['result']->strength + $attack['result']->defense + 2;
			$loser['damage'] = mt_rand ($loser['min'], $loser['max']);
			$loser['hp'] = $attack['result']->hp - $loser['damage'];
			if ($loser['hp'] <= 0) {
				$loser['hp'] = 0;
			}
			$winner['pid'] = $defender;
			$winner['score'] = $defend['score'];
			$winner['level'] = $defend['level'];
			$winner['min'] = max(0, ($defend['result']->strength + $defend['result']->defense) - 3);
			$winner['max'] = $defend['result']->strength + $defend['result']->defense + 2;
			$winner['damage'] = mt_rand ($winner['min'], $winner['max']);
			$winner['hp'] = $defend['result']->hp - $winner['damage'];
			if ($winner['hp'] <= 0) {
				$winner['hp'] = 0;
			}
			$winner['xp'] = mt_rand (1, 5) + $defend['result']->xp;

			$this->Attack_update_results_battle($winner, $loser, $attacker, $defender);
			return '<p>' . $this->get_player_meta($defender)->nickname . ' Won!</p>' . $this->Attack_print_results_battle($winner, $loser, $attacker, $defender);
		}
	}
	function Attack($attacker, $defender) 
	{
		//wp_die('Attack');
		global $wpdb;
		$wpdb->query('SELECT * FROM '. $wpdb->prefix . 'rpg_attack_log WHERE attacker='.$attacker.' AND defender='.$defender. ' AND DATE >= ( NOW( ) - INTERVAL 1 DAY ) AND DATE <= NOW( ) ' );
		if($wpdb->num_rows <=5)
		{
			return $this->AttackFormula($attacker, $defender);
		}else{
			return 'You\'ve Attacked This Player Enough Today <br /></p><br /><a href="#" onclick="location.reload(true); return false;">Reload Members List</a>';
		}
	}
		
		
	function Attack_print_results_battle($winner, $loser, $attacker, $defender) {
		$a_name = $this->get_player_meta($attacker)->nickname;
		$d_name = $this->get_player_meta($defender)->nickname;
		$result = '<strong><h2>Battle Results</h2></strong><p>';
		$result .= $a_name . ' attacked ' . $d_name . ' and ' . ($winner['pid'] == $attacker ? 'won.' : 'lost.') . '<br />';
		$result .= $a_name . ' attacked with a score of ' . ($winner['pid'] == $attacker ? $winner['score'] : $loser['score']) . '<br />';
		$result .= $d_name . ' defended against the attack with a score of ' . ($winner['pid'] == $defender ? $winner['score'] : $loser['score']) . '<br />';
		$result .= $a_name . ' suffered ' . ($winner['pid'] == $attacker ? $winner['damage'] : $loser['damage']) . ' damage. <br />';
		$result .= $d_name . ' suffered ' . ($winner['pid'] == $defender ? $winner['damage'] : $loser['damage']) . ' damage. <br />';
		if ($winner['level'] < $this->wpRPG_player_level($winner['xp'])) {
			if ($winner['pid'] == $attacker) {
				$result .= 'Congrats! You earned a new level! <br /> Now you\'re level ' . $this->wpRPG_player_level($winner['xp']);
			} else {
				$result .= 'Congrats, attacking ' . $d_name . ' and losing, earned them a new level! <br /> They are now level ' . $this->wpRPG_player_level($winner['xp']);
			}
		}
		$result .= '</p><br /><a href="#" onclick="location.reload(true); return false;">Reload Members List</a>';
		return $result;
	}

	function Attack_update_results_battle($winner, $loser, $attacker, $defender) {
		global $wpdb;
		$wpdb->insert(
				$wpdb->prefix . "rpg_attack_log", array(
			'attacker' => $attacker,
			'defender' => $defender,
			'winner' => $winner['pid']
				), array(
			'%d',
			'%d',
			'%d'
				)
		);
		if($winner['xp'] == 0)
			$winner['xp'] += 4;
		
		update_user_meta($winner['pid'], 'xp', $winner['xp']);
		update_user_meta($winner['pid'], 'hp', $winner['hp']);
		update_user_meta($loser['pid'], 'hp', $loser['hp']);
	}

	function Attack_calculate_scores($xp, $hp, $level) {
		$xp_seed = (($xp * mt_rand (2, 4))/($xp==0?1:$xp)) + mt_rand (1,5);
		$hp_seed = (($hp * mt_rand (1, 3))/($hp==0?1:$hp)) + mt_rand (1,6);
		$level_seed = ($level * mt_rand (1, 3))/$level;
		$score = $xp_seed + $hp_seed + $level_seed;
		$score = $score * mt_rand (1, 2);
		return $score;
	}

	function attack_callback() {
		echo $this->Attack($_POST['attacker'], $_POST['defender']);
		die();
	}

	function Attack_add_attack_to_rpg_menu() {
		return null;//add_submenu_page('wpRPG_menu', 'WP-RPG Attack Module', 'wpRPG Attack', 'manage_options', 'wpRPG_Attack_menu', array($this,'Attack_options'));
	}


	function Attack_options($opt = 0) {
		$html = "<tr>";
		$html .= "<td>";
		$html .= "<h3>Welcome to Wordpress RPG Attack Module!</h3>";
		$html .= "</td>";
		$html .= "</tr>";
		$html .= "<tr>";
		$html .= "<td>";
		$html .= "<span class='description'>Nothing To See Here Yet</span>";
		$html .= "</td>";
		$html .= "</tr>";
		$html .= "<tr><td><span class='description'>Version: ".$this->version."</span></td></tr>";
		if(!$opt)
			echo $html;
		else
			return $html;
	}


	function wpRPG_attack_load_plugin() 
	{ 
		if ( ! current_user_can( 'activate_plugins' ) ) 
			return; 
		if(is_admin()&&get_option('Activated_Plugin')=='wpRPG-Attack') 
		{ 
			delete_option('Activated_Plugin'); 
			add_action( 'admin_notices', array($this,'wpRPG_attack_check_admin_notices'), 0 ); 
		}
	}

	function wpRPG_attack_check_admin_notices()
	{
		$errors = $this->WpRPG_Attack_check_plugin_requirements();
		if ( empty ( $errors ) )
			return;

		// Suppress "Plugin activated" notice.
		unset( $_GET['activate'] );

		// this plugin's name
		$name = get_file_data( __FILE__, array ( 'Plugin Name' ), 'plugin' );

		printf(
			'<div class="error"><p>%1$s</p>
			<p><i>%2$s</i> has been deactivated.</p></div>',
			join( '</p><p>', $errors ),
			$name[0]
		);
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
	function check_tables() 
	{
		global $wpdb;
		$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "rpg_attack_log (
								id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
								attacker int(11) NOT NULL,
								defender int(11) NOT NULL,
								winner int(11) NOT NULL,
								date TIMESTAMP DEFAULT NOW())";
		$wpdb->query($sql);
		return true;
	}

	function check_column($table, $col_name) {
		global $wpdb;
		if ($table != null) {
			$results = $wpdb->get_results("DESC $table");
			if ($results != null) {
				foreach ($results as $row) {
					if ($row->Field == $col_name) {
						return true;
					}
				}
				return false;
			}
			return false;
		}
		return false;
	}
	function WpRPG_Attack_check_plugin_requirements() 
	{
		$errors = array();
		if (!class_exists('wpRPG')) {
			$errors[] = "WPRPG must be installed!<br />";
			deactivate_plugins(WPRPG_Attack_Plugin_File);
		}elseif (!get_option('WPRPG_Attack_installed')) {
			 if ($this->check_tables() != FALSE) {
				update_option('WPRPG_Attack_installed', "1");
			} else {
				$errors[] = "You had an error occur!<br />";
			}
		}else{
			//die(get_option('WPRPG_rpg_installed'));
		}
		return $errors;
	}

	function RegisterSettings() 
	{
		// Add options to database if they don't already exist
		add_option('WPRPG_Attack_installed', "", "", "yes");
		
		// Register settings that this form is allowed to update
		register_setting('rpg_settings', 'WPRPG_Attack_installed');

	}
	
	static function Attack_get_profile_url() {
		//wp_die('get_profile_Url');
		return get_page(get_option('wpRPG_Profile_Page'))->post_name;
		
	}
	
	function Attack_flush_rules() {
		$rules = get_option( 'rewrite_rules' );
		if ( ! isset( $rules['attack/(.+)$'] ) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
			//wp_die(var_dump($wp_rewrite->rules));
		}
	}

	function Attack_insert_rewrite_rules( $rules ) {
		global $wp_rewrite;
		$newrules = array();
		//$newrules['(profile)/(.+)$'] = 'index.php?pagename=$matches[1]&username=$matches[2]';
		//$newrules['attack/(.+)$'] = 'index.php?getAttackLog=1&f=$matches[1]'; 
		return $newrules + $rules;
	}

	function Attack_insert_query_vars( $vars ) {
	 
		//array_push($vars, 'username');
		//array_push($vars, 'id'); 
		array_push($vars, 'getAttackLog');
		//wp_die(print_r($vars));
		return $vars;
	}
}
?>