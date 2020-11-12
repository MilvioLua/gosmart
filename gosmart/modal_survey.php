<?php
defined( 'ABSPATH' ) OR exit;
/**
 * Plugin Name: Modal Survey
 * Plugin URI: http://modalsurvey.pantherius.com
 * Description: Manage Surveys, Polls and Quizzies
 * Author: Pantherius
 * Version: 2.0.1.6.1
 * Author URI: http://pantherius.com
 */

define( 'MODAL_SURVEY_TEXT_DOMAIN' , 'modal_survey' );
define( 'GRID_ITEMS' , '' );
define( 'MODAL_SURVEY_VERSION' , '2.0.1.6.1' );
define( 'MSDIRS' , '/' );
define( 'MODAL_SURVEY_NAMESPACE' , 'modalsurvey' );
define( 'MSREST_VERSION' , 'v1' );

if( ! class_exists( 'modal_survey' ) ) {
	class modal_survey {
		protected static $instance = null;
		var $auto_embed = 'false';
		var $mscontentinit = 'false';
		var $mspreinit = 'false';
		var $modalscript = '';
		var $scripts = array( 'msdev' => 'modal_survey.js', 'msmin' => 'modal_survey.min.js', 'msadev' => 'modal_survey_answer.js', 'msamin' => 'modal_survey_answer.min.js' );
		var $mainscript = '';
		var $answerscript = '';
		var $esurvey = array();
		var $script = '';
		var $postid = '';
		var $postcharts = array();
		var $msplugininit_array = array();
		public $msplugininit_answer_array = array();
		/**
		 * Construct the plugin object
		 */
		public function __construct() {
			global $wpdb;
			// installation and uninstallation hooks
			register_activation_hook(__FILE__, array( 'modal_survey', 'activate' ) );
			register_deactivation_hook(__FILE__, array( 'modal_survey', 'deactivate' ) );
			register_uninstall_hook(__FILE__, array( 'modal_survey', 'uninstall' ) );
			add_action( 'plugins_loaded', array( $this, 'modalsurvey_localization' ) );
			if ( get_option( 'setting_restapi' ) == 'on' ) {
				add_action( 'rest_api_init', array( $this, 'modalsurvey_register_restapiroute' ) );
			}
			if ( is_admin() ) {
				if ( get_option( 'setting_remember_users' ) == "" ) {
					update_option( 'setting_remember_users', 'on' );
				}
				require_once( sprintf( "%s/settings.php", dirname( __FILE__ ) ) );
				$modal_survey_settings = new modal_survey_settings();
				$plugin = plugin_basename( __FILE__ );
				add_filter( "plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ) );
				add_action( 'admin_notices', array( $this, 'deactivation_notice' ) );
			}
			else {
				$modal_survey_url = $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
				$modal_survey_load = true;
				if ( ( strpos( $modal_survey_url, 'wp-login' ) ) !== false ) {
						$modal_survey_load = false;
				}
				if ( ( strpos( $modal_survey_url, 'wp-admin' ) ) !== false ) {
					$modal_survey_load = false;
				}
				if ( $modal_survey_load || isset( $_REQUEST[ 'sspcmd' ] ) ) {
					//integrate the public functions
					add_action( 'init', array( $this, 'enqueue_custom_scripts_and_styles' ), 1 );
					add_shortcode( 'survey', array( $this, 'survey_shortcodes' ) );
					add_shortcode( 'modalsurvey', array( $this, 'survey_shortcodes' ) );
					add_shortcode( 'modal_survey', array( $this, 'survey_shortcodes' ) );
					add_shortcode( 'survey_calculation', array( $this, 'survey_calculation_shortcodes' ) );
					add_shortcode( 'survey_compare_chart', array( $this, 'survey_compare_chart_shortcodes' ) );
					add_shortcode( 'survey_answers', array( $this, 'survey_answers_shortcodes' ) );
					add_shortcode( 'survey_records', array( $this, 'survey_records_shortcodes' ) );
					add_shortcode( 'survey_open_answers', array( $this, 'survey_open_answers_shortcodes' ) );
					add_shortcode( 'survey_conditions', array( $this, 'survey_conditions_shortcodes' ) );
					add_shortcode( 'survey_useraction', array( $this, 'survey_useraction' ) );
					add_shortcode( 'survey_datetoday', array( $this, 'survey_displayTodaysDate' ) );
					add_filter( 'widget_text', 'do_shortcode' );
					add_filter( 'bbp_get_reply_content', array( $this, 'enable_modalsurvey_shortcode' ), 1 );
					add_filter( 'bbp_get_topic_title', array( $this, 'add_modalsurvey_shortcode_to_topics' ), 1 );
					add_filter( 'the_content', array( $this, 'extend_the_content' ) );
					if ( get_option( 'setting_plugininit' ) == 'getfooter' ) {
						add_action( 'get_footer' , array( $this, 'initialize_plugin' ), 175 );
					}
					elseif ( get_option( 'setting_plugininit' ) == 'wpfooter' ) {
						add_action( 'wp_footer' , array( $this, 'initialize_plugin' ), 175 );
					}
					else {
						add_action( 'get_footer' , array( $this, 'initialize_plugin' ), 175 );						
					}
					add_action( 'wp_head', array( $this, 'add_social_metas' ) );
				}
				if ( get_option( 'setting_minify' ) == 'on' ) {
					$this->mainscript = $this->scripts[ 'msmin' ];
					$this->answerscript = $this->scripts[ 'msamin' ];
				}
				else {
					$this->mainscript = $this->scripts[ 'msdev' ];				
					$this->answerscript = $this->scripts[ 'msadev' ];
				}
				$this->check_participants( get_option( 'setting_participants_entries' ) );
			}
		}

		function check_participants( $interval = '30' ) {
			global $wpdb;
			if ( empty( $interval ) ) {
				$interval = '30';
			}
			$ctime = current_time( 'timestamp' );
			$dbtime = get_option( 'ms_last_dbclear' );
			if ( ! $dbtime ) {
				update_option( 'ms_last_dbclear', current_time( 'timestamp' ) );
			}
			if ( abs( $ctime - $dbtime ) / 3600 > 12 ) { /* run it once in every 12 hours) */
				$date = date( "Y-m-d", strtotime( '-' . $interval . ' day' ) );
				$delresult = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE DATE_FORMAT( time, '%%Y-%%m-%%d' ) < %s", $date ) );
				$delresult2 = $wpdb->query( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE `autoid` NOT IN ( SELECT spd.uid FROM " . $wpdb->base_prefix . "modal_survey_participants_details spd" );
				update_option( 'ms_last_dbclear', current_time( 'timestamp' ) );
			}
			else {
			}
		}
		
		function check_privileges( $role ) {
		$this->cu_capability = 'public';
			if ( $role == 'superadmin' ) {
				$this->cu_capability = 'create_sites';
			}
			if ( $role == 'administrator' ) {
				$this->cu_capability = 'manage_options';
			}
			if ( $role == 'editor' ) {
				$this->cu_capability = 'edit_pages';
			}
			if ( $role == 'author' ) {
				$this->cu_capability = 'publish_posts';
			}
			if ( $role == 'contributor' ) {
				$this->cu_capability = 'edit_posts';
			}
			if ( $role == 'subscriber' ) {
				$this->cu_capability = 'read';
			}
			if ( $role == 'public' ) {
				$this->cu_capability = 'public';
			}
			return $this->cu_capability;
		}
		
		function modalsurvey_register_restapiroute( $request ) {
		$restapi_privileges = get_option( 'setting_restapi_privileges' );
		$cu_capability = $this->check_privileges( $restapi_privileges );
		$restapi_config = array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'ms_rest_api_get_survey' ),
				'permission_callback' => function() {
						return current_user_can( $this->cu_capability );
				}
			);
			if ( $cu_capability == 'public' ) {
				unset( $restapi_config[ 'permission_callback' ] );
			}
			register_rest_route( MODAL_SURVEY_NAMESPACE . '/' . MSREST_VERSION, '/survey/(?P<sid>\w+)', $restapi_config );
			$restapi_config[ 'callback' ] = array( $this, 'ms_rest_api_get_participant' );
			register_rest_route( MODAL_SURVEY_NAMESPACE . '/' . MSREST_VERSION, '/user/(?P<uid>\d+)', $restapi_config );
			$restapi_config[ 'callback' ] = array( $this, 'ms_rest_api_get_allusers' );
			register_rest_route( MODAL_SURVEY_NAMESPACE . '/' . MSREST_VERSION, '/allusers/(?P<limit>\d+)', $restapi_config );
			$restapi_config[ 'callback' ] = array( $this, 'ms_rest_api_get_surveyresult' );
			register_rest_route( MODAL_SURVEY_NAMESPACE . '/' . MSREST_VERSION, '/surveyresult/(?P<sid>\w+)', $restapi_config );
			$restapi_config[ 'callback' ] = array( $this, 'ms_rest_api_get_userresult' );
			register_rest_route( MODAL_SURVEY_NAMESPACE . '/' . MSREST_VERSION, '/userresult/(?P<sid>\w+)/(?P<uid>\w+)', $restapi_config );
		}

		function ms_rest_api_get_survey( $request ) {
		global $wpdb;
			$sid = sanitize_text_field( $request[ 'sid' ] );
			$survey = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_surveys mss WHERE mss.id = %d ORDER BY autoid DESC", $sid ) );
			if ( empty( $request[ 'sid' ] ) || empty( $survey ) ) {
				return new WP_Error( 'modalsurvey_restapi_error', 'Invalid Survey ID', array( 'status' => 404 ) );
			}
			$options = json_decode( stripslashes( $survey->options ) );
			$survey->options = array(
				"display_style" => $options[ 0 ],
				"animation_easing" => $options[ 1 ],
				"font_family" => $options[ 2 ],
				"bgcolor" => $options[ 3 ],
				"font_color" => $options[ 4 ],
				"border_color" => $options[ 5 ],
				"border_width" => $options[ 6 ],
				"border_radius" => $options[ 7 ],
				"font_size" => $options[ 8 ],
				"padding" => $options[ 9 ],
				"line_height" => $options[ 10 ],
				"animation_speed" => $options[ 11 ],
				"thankyou" => $options[ 12 ],
				"lock_bg" => $options[ 13 ],
				"closeable" => $options[ 14 ],
				"atbottom" => $options[ 15 ],
				"text_align" => $options[ 16 ],
				"survey_mode" => $options[ 17 ],
				"loggedin" => $options[ 18 ],
				"redirecturl" => $options[ 19 ],
				"progressbar" => $options[ 20 ],
				"conditions" => $options[ 21 ],
				"listlayout" => $options[ 22 ],
				"end_delay" => $options[ 23 ],
				"activecampaign" => $options[ 24 ],
				"activecampaign_url" => $options[ 25 ],
				"activecampaign_apikey" => $options[ 26 ],
				"activecampaign_listid" => $options[ 27 ],
				"aweber" => $options[ 28 ],
				"aweber_authorizationcode" => $options[ 29 ],
				"aweber_consumerkey" => $options[ 30 ],
				"aweber_consumersecret" => $options[ 31 ],
				"aweber_accesskey" => $options[ 32 ],
				"aweber_accesssecret" => $options[ 33 ],
				"aweber_listid" => $options[ 34 ],
				"benchmark" => $options[ 35 ],
				"benchmark_doubleoptin" => $options[ 36 ],
				"benchmark_apikey" => $options[ 37 ],
				"benchmark_listid" => $options[ 38 ],
				"campaignmonitor" => $options[ 39 ],
				"campaignmonitor_apikey" => $options[ 40 ],
				"campaignmonitor_listid" => $options[ 41 ],
				"campayn" => $options[ 42 ],
				"campayn_domain" => $options[ 43 ],
				"campayn_apikey" => $options[ 44 ],
				"campayn_listid" => $options[ 45 ],
				"constantcontact" => $options[ 46 ],
				"constantcontact_apikey" => $options[ 47 ],
				"constantcontact_accesstoken" => $options[ 48 ],
				"constantcontact_listid" => $options[ 49 ],
				"freshmail" => $options[ 50 ],
				"freshmail_apikey" => $options[ 51 ],
				"freshmail_apisecret" => $options[ 52 ],
				"freshmail_listhash" => $options[ 53 ],
				"getresponse" => $options[ 54 ],
				"getresponse_apikey" => $options[ 55 ],
				"getresponse_campaignid" => $options[ 56 ],
				"madmimi" => $options[ 70 ],
				"madmimi_username" => $options[ 71 ],
				"madmimi_apikey" => $options[ 72 ],
				"madmimi_listname" => $options[ 73 ],
				"mailchimp" => $options[ 74 ],
				"mailchimp_apikey" => $options[ 75 ],
				"mailchimp_listid" => $options[ 76 ],
				"mailpoet" => $options[ 88 ],
				"mailpoet_listid" => $options[ 89 ],
				"mymail" => $options[ 94 ],
				"mymail_listid" => $options[ 95 ],
				"simplycast" => $options[ 117 ],
				"simplycast_publickey" => $options[ 118 ],
				"simplycast_secretkey" => $options[ 119 ],
				"simplycast_listid" => $options[ 120 ],
				"ymlp" => $options[ 121 ],
				"ymlp_username" => $options[ 122 ],
				"ymlp_apikey" => $options[ 123 ],
				"ymlp_groupid" => $options[ 124 ],
				"participants_form_status" => $options[ 125 ],
				"participants_form_name_field" => $options[ 126 ],
				"participants_form_email_field" => $options[ 127 ],
				"shadow_horizontal" => $options[ 128 ],
				"shadow_vertical" => $options[ 129 ],
				"shadow_blur" => $options[ 130 ],
				"shadow_spread" => $options[ 131 ],
				"shadow_color" => $options[ 132 ],
				"preloader" => $options[ 133 ],
				"hover" => $options[ 134 ],
				"display_timer" => $options[ 135 ],
				"endchart_status" => $options[ 136 ],
				"endchart_style" => $options[ 137 ],
				"endchart_type" => $options[ 138 ],
				"endchart_datatype" => $options[ 139 ],
				"endchart_advancedchart" => $options[ 140 ],
				"closeicon" => $options[ 141 ],
				"grid_items" => $options[ 142 ],
				"cookie_expiration" => $options[ 143 ],
				"notification_email" => $options[ 144 ],
				"closeiconsize" => $options[ 145 ],
				"participants_form_email_validate" => $options[ 146 ],
				"autoresponse" => $options[ 147 ],
				"autoresponse_sendername" => $options[ 148 ],
				"autoresponse_senderemail" => $options[ 149 ],
				"autoresponse_subject" => $options[ 150 ],
				"next_button_style" => $options[ 151 ],
				"always_show_next_button" => $options[ 152 ],
				"rating_question_style" => $options[ 153 ],
				"enable_back_button" => $options[ 154 ],
				"remember_and_continue" => $options[ 155 ],
				"quiz_timer_value" => $options[ 156 ],
				"question_timer" => $options[ 157 ],
				"animation_type" => $options[ 158 ],
				"custom_fields" => $options[ 159 ],
				"participants_form_confirmation" => $options[ 160 ],
				"participants_form_signup_wo_confirm" => $options[ 161 ],
				"process_allconditions" => $options[ 162 ],
				"show_admin_comments" => $options[ 163 ],
				"mailingboss" => $options[ 164 ],
				"mailingboss_integrationkey" => $options[ 165 ],
				"mailingboss_listid" => $options[ 166 ]
			);
			$survey->questions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_questions WHERE `survey_id`= %d ORDER BY id ASC", $sid ) );
			foreach( $survey->questions as $key=>$qs ) {
				$qoptions = unserialize( $qs->qoptions );
			$survey->questions[ $key ]->options = array(
					"optional_answers" => $qoptions[ 0 ],
					"required_answers" => $qoptions[ 1 ],
					"image_url" => $qoptions[ 2 ],
					"rating_question" => $qoptions[ 3 ],
					"tooltip" => $qoptions[ 4 ],
					//"category" => $qoptions[ 5 ],
					"image_width" => $qoptions[ 6 ],
					"image_height" => $qoptions[ 7 ],
					"image_align" => $qoptions[ 8 ]
				);
				unset( $survey->questions[ $key ]->qoptions );
				$survey->questions[ $key ]->answers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE `survey_id`= %d AND `question_id`=%d ORDER BY autoid ASC", $sid, $qs->id ) );
				foreach( $survey->questions[ $key ]->answers as $key2=>$as ) {
					$aoptions = unserialize( $as->aoptions );
					$survey->questions[ $key ]->answers[ $key2 ]->id = $survey->questions[ $key ]->answers[ $key2 ]->autoid;
					unset( $survey->questions[ $key ]->answers[ $key2 ]->autoid );
					$survey->questions[ $key ]->answers[ $key2 ]->options = array(
						"answer_type" => $aoptions[ 0 ],
						"answer_uniqueid" => $aoptions[ 1 ],
						"autocomplete" => $aoptions[ 2 ],
						"image_url" => $aoptions[ 3 ],
						"tooltip" => $aoptions[ 4 ],
						"score" => $aoptions[ 5 ],
						"correct" => $aoptions[ 6 ],
						"image_width" => $aoptions[ 7 ],
						"image_height" => $aoptions[ 8 ],
						"hidden" => $aoptions[ 9 ],
						"textarea" => $aoptions[ 10 ],
						"tooltip" => $aoptions[ 11 ],
						"redirection" => $aoptions[ 12 ],
						//"category" => $aoptions[ 13 ],
						"hidelabel" => $aoptions[ 14 ],
						"image_align" => $aoptions[ 15 ],
						"tooltip" => $aoptions[ 16 ],
						"admin_comment" => $aoptions[ 17 ]
					);
					unset( $survey->questions[ $key ]->answers[ $key2 ]->aoptions );					
				}
			}
			return new WP_REST_Response( $survey, 200 );
		}

		function ms_rest_api_get_participant( $request ) {
		global $wpdb;
			$uid = sanitize_text_field( $request[ 'uid' ] );
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE autoid = %d ", $uid ) );
			if ( ! empty( $data ) ) {
				$data->uniqueid = $data->id;
				$data->id = $data->autoid;
				$custom_data = unserialize( $data->custom );
				if ( empty( $custom_data ) ) {
					$custom_data = array();
				}
				$data->custom_data = $custom_data;
				unset( $data->autoid );
				unset( $data->custom );
				$userdetails->data = $data;
			}
			if ( empty( $request[ 'uid' ] ) || empty( $data ) ) {
				return new WP_Error( 'modalsurvey_restapi_error', 'Invalid User ID', array( 'status' => 404 ) );
			}
			$ip = $wpdb->get_results( $wpdb->prepare( "SELECT ip FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE uid = %d GROUP BY ip ORDER BY time DESC", $uid ) );
			if ( ! empty( $ip ) ) {
				foreach( $ip as $i ) {
					$userdetails->ip[] = $i->ip;
				}
			}
			else {
				$userdetails->ip = false;
			}
			$posts = $wpdb->get_results( $wpdb->prepare( "SELECT postid, samesession FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE uid = %d GROUP BY postid ORDER BY time DESC", $uid ) );
			if ( ! empty( $posts ) ) {
				foreach( $posts as $ps ) {
					$userdetails->posts[] = array(
						"postid" => $ps->postid,
						"session" => $ps->samesession,
						"permalink" => get_permalink( $ps->postid )
					);
				}
			}
			$surveys = $wpdb->get_results( $wpdb->prepare( "SELECT mspd.sid, mss.name FROM " . $wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $wpdb->base_prefix . "modal_survey_surveys mss on mspd.sid = mss.id WHERE uid = %d GROUP BY sid ORDER BY time DESC", $uid ) );
			if ( ! empty( $surveys ) ) {
				foreach( $surveys as $ss ) {
					$userdetails->surveys[] = array(
						"id" => $ss->sid,
						"name" => $ss->name
					);
				}
			}
			return new WP_REST_Response( $userdetails, 200 );
		}

		function ms_rest_api_get_allusers( $request ) {
		global $wpdb;
			$limit = sanitize_text_field( $request[ 'limit' ] );
			$setlimit = "";
			if ( isset( $limit ) && ! empty( $limit ) ) {
				$setlimit = " LIMIT " . $limit;
			}
			$surveys = $wpdb->get_results( $wpdb->prepare( "SELECT msp.autoid, msp.id as uid, DATE_FORMAT( mspd.time,'%%Y-%%m-%%d %%H:%%i' ) as created, msp.name, msp.email, mspd.ip, COUNT( mspd.aid ) as SUMCOUNT, mss.name as survey, mss.id as sid, mspd.sid as pdsid FROM " . $wpdb->base_prefix . "modal_survey_participants msp LEFT JOIN " . $wpdb->base_prefix . "modal_survey_participants_details mspd on mspd.uid = msp.autoid LEFT JOIN " . $wpdb->base_prefix . "modal_survey_surveys mss on mspd.sid = mss.id GROUP BY mss.id, msp.id ORDER by autoid DESC %d", $setlimit ) );
			if ( empty( $surveys ) ) {
				return new WP_Error( 'modalsurvey_restapi_error', 'Invalid Request', array( 'status' => 404 ) );
			}
			else {
				foreach( $surveys as $key=>$ss ) {
					unset( $surveys[ $key ]->pdsid );
					$surveys[ $key ]->votes = $surveys[ $key ]->SUMCOUNT;
					unset( $surveys[ $key ]->SUMCOUNT );
					$surveys[ $key ]->id = $surveys[ $key ]->autoid;
					unset( $surveys[ $key ]->autoid );
				}
			}
			return new WP_REST_Response( $surveys, 200 );
		}
	
		function ms_rest_api_get_surveyresult( $request ) {
		global $wpdb;
			$sid = sanitize_text_field( $request[ 'sid' ] );
			$result = modal_survey::survey_answers_shortcodes(
						array ( 'id' => $sid, 'data' => 'full-records', 'style' => 'plain', 'pure' => 'true' )
					);
			if ( ! empty( $result ) ) {
				$surveyresults = $result;
			}
			if ( empty( $request[ 'sid' ] ) || empty( $result ) ) {
				return new WP_Error( 'modalsurvey_restapi_error', 'Invalid Survey ID', array( 'status' => 404 ) );
			}
			foreach( $surveyresults as $key3=>$sr ) {
				foreach( $sr[ "datas" ] as $key4=>$ds ) {
					unset( $surveyresults[ $key3 ][ "datas" ][ $key4 ][ "survey" ] );
					$surveyresults[ $key3 ][ "datas" ][ $key4 ][ "aggretaged_votes" ] = $ds[ "votes" ];
					unset( $surveyresults[ $key3 ][ "datas" ][ $key4 ][ "votes" ] );
					$surveyresults[ $key3 ][ "datas" ][ $key4 ][ "aggretaged_percentage" ] = $ds[ "percentage" ];
					unset( $surveyresults[ $key3 ][ "datas" ][ $key4 ][ "percentage" ] );
					unset( $surveyresults[ $key3 ][ "datas" ][ $key4 ][ "selected" ] );
				}
			}
			$userdetails->results[ $key ][ "votes" ] = $votes;
			return new WP_REST_Response( $surveyresults, 200 );			
		}
		
		function ms_rest_api_get_userresult( $request ) {
		global $wpdb;
			$sid = sanitize_text_field( $request[ 'sid' ] );
			$uid = sanitize_text_field( $request[ 'uid' ] );
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT samesession, time FROM " . $wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $wpdb->base_prefix . "modal_survey_participants msp on mspd.uid = msp.autoid WHERE mspd.sid = %s AND msp.autoid = %s GROUP BY samesession ORDER BY time DESC",  $sid, $uid ) );
			if ( ! empty( $result ) ) {
				$survey_results = $result;
			}
			if ( empty( $request[ 'sid' ] ) ||  empty( $request[ 'uid' ] ) || empty( $result ) ) {
				return new WP_Error( 'modalsurvey_restapi_error', 'Invalid Survey or User ID', array( 'status' => 404 ) );
			}
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE autoid = %d ", $uid ) );
			if ( ! empty( $data ) ) {
				$data->uniqueid = $data->id;
				$data->id = $data->autoid;
				$custom_data = unserialize( $data->custom );
				if ( empty( $custom_data ) ) {
					$custom_data = array();
				}
				$data->custom_data = $custom_data;
				unset( $data->autoid );
				unset( $data->custom );
				$userdetails->data = $data;
			}
			foreach( $survey_results as $key=>$sr ) {
				$votes = modal_survey::survey_answers_shortcodes(
					array ( 'id' => $sid, 'data' => 'full-records', 'style' => 'plain', 'uid' => $data->uniqueid, 'pure' => 'true', 'session' => $sr->samesession  )
				);
				$userdetails->results[ $key ][ "id" ] = $sid;
				$userdetails->results[ $key ][ "survey" ] = $votes[ 0 ][ "datas" ][ 0 ][ "survey" ];
				$userdetails->results[ $key ][ "session" ] = $sr->samesession;
				$userdetails->results[ $key ][ "date" ] = $sr->time;
				$thisusurl = $wpdb->get_var( $wpdb->prepare( "SELECT postid FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %s AND samesession = %s", $sid, $sr->samesession ) );
				$userdetails->results[ $key ][ "survey_pageurl" ] = get_permalink( $thisusurl );
				$userdetails->results[ $key ][ "survey_pagename" ] = get_the_title( $thisusurl );
				foreach( $votes as $key3=>$vs ) {
					foreach( $vs[ "datas" ] as $key4=>$ds ) {
						unset( $votes[ $key3 ][ "datas" ][ $key4 ][ "survey" ] );
						$votes[ $key3 ][ "datas" ][ $key4 ][ "aggretaged_votes" ] = $ds[ "votes" ];
						unset( $votes[ $key3 ][ "datas" ][ $key4 ][ "votes" ] );
						$votes[ $key3 ][ "datas" ][ $key4 ][ "aggretaged_percentage" ] = $ds[ "percentage" ];
						unset( $votes[ $key3 ][ "datas" ][ $key4 ][ "percentage" ] );
					}
				}
				$userdetails->results[ $key ][ "votes" ] = $votes;
			}		
			return new WP_REST_Response( $userdetails, 200 );						
		}
		
		function survey_displayTodaysDate( $atts )	{
			return date( get_option( 'date_format' ) );
		}
		
		function add_modalsurvey_shortcode_to_topics( $title ) {
			global $post;
			if ( $this->auto_embed == 'false' && ! empty( $title ) && ! empty( $this->esurvey ) ) {
				$this->auto_embed = 'true';
				if ( $post->post_type == "topic" && $this->esurvey[ 'style' ] == 'embed_topics' ) {
					$title .= modal_survey::survey_shortcodes( 
					array ( 'id' => $this->esurvey[ 'survey_id' ], 'style' => 'flat', 'customclass' => 'autoembed-msurvey' )
					);
				}
			}
				return $title;
		}

		function enable_modalsurvey_shortcode( $content ) {
			$reply_author_id = get_post_field( 'post_author', bbp_get_reply_id() );
			$user_data = get_userdata( $reply_author_id );
			if ( user_can( $user_data, 'edit_others_forums' ) ) {
				preg_match_all( '/\[modalsurvey (.*)]/', $content, $matches );
				foreach( $matches[ 0 ] as $match ) {
					$content = str_replace( $match, do_shortcode( $match ), $content );
				}
			}
			return $content;
		}	
	
		function call_modalsurvey_shortcode( $content ) {
				add_shortcode( 'survey_answers', array( $this, 'survey_answers_shortcodes' ) );
				preg_match_all( '/\[survey_answers (.*)]/', $content, $matches );
				foreach( $matches[ 0 ] as $match ) {
					$content = str_replace( $match, do_shortcode( $match ), $content );
				}
			return $content;
		}	

		function call_modalsurvey_conditions_shortcode( $content ) {
				add_shortcode( 'survey_conditions', array( $this, 'survey_conditions_shortcodes' ) );
				$content = trim( preg_replace( '/\s+/', ' ', nl2br( $content ) ) );
				preg_match_all( '/\[survey_conditions (.*?)](.*?)\[\/survey_conditions]/', $content, $matches );
				foreach( $matches[ 0 ] as $key=>$match ) {
					$content = str_replace( $match, do_shortcode( $match ), $content );
				}
				$breaks = array( "<br />", "<br>", "<br/>" );  
				$content = str_ireplace( $breaks, "\r\n", $content );
			return $content;
		}	

		function call_modalsurvey_records_shortcode( $content ) {
				add_shortcode( 'survey_records', array( $this, 'survey_records_shortcodes' ) );
				$content = trim( preg_replace( '/\s+/', ' ', nl2br( $content ) ) );
				preg_match_all( '/\[survey_records (.*)]/', $content, $matches );
				foreach( $matches[ 0 ] as $key=>$match ) {
					$content = str_replace( $match, do_shortcode( $match ), $content );
				}
			return $content;
		}	

		function call_modalsurvey_date_shortcode( $content ) {
				add_shortcode( 'survey_datetoday', array( $this, 'survey_displayTodaysDate' ) );
				$content = trim( preg_replace( '/\s+/', ' ', nl2br( $content ) ) );
				preg_match_all( '/\[survey_datetoday (.*)]/', $content, $matches );
				foreach( $matches[ 0 ] as $key=>$match ) {
					$content = str_replace( $match, do_shortcode( $match ), $content );
				}
			return $content;
		}	

		function get_featured_image() {
			if ( has_post_thumbnail( get_the_ID() ) ) {
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'single-post-thumbnail' );
				return $image[0];
			}
			else {
				return false;
			}
		}
		
		function get_short_desc() {
			global $post;
			if ( ! empty( $post ) ) {
				return $post->post_excerpt;
			}
			else {
				if ( $post ) {
					$content = $post->post_content;
					$content = strip_shortcodes( strip_tags( $content ) );
					$excerpt = wp_trim_words( $content, 100 );
					return $excerpt;
				}
				else {
					return '';
				}
			}
		}
		
		function add_social_metas() {
			global $wp;
			$socialmeta = '<meta name="generator" content="Powered by Modal Survey ' . MODAL_SURVEY_VERSION . ' - Survey, Poll and Quiz builder plugin for WordPress with interactive charts and detailed results." />
';
			
			if ( get_option( 'setting_social_metas' ) == "on" ) {
				$socialmeta .= '<meta property="og:title" content="' . strip_tags( get_the_title() ) . '" />
';
				if ( get_option( 'setting_fbappid' ) != "" ) {
				$socialmeta .= '<meta property="fb:app_id" content="' . get_option( 'setting_fbappid' ) . '" />
';
				}
				$socialmeta .= '<meta property="og:description" content="' . strip_tags( $this->get_short_desc() ) . '" />
';
				if ( isset( $_REQUEST[ 'msid' ] ) ) {
				$socialmeta .= '<meta property="og:url" data-react-helmet="true" content="' . ( home_url(add_query_arg(array(),$wp->request)) ) . '?msid=' . $_REQUEST[ 'msid' ] . '" />
';
				$socialmeta .= '<link rel="canonical" href="' . esc_url( ( home_url( add_query_arg( array(), $wp->request ) ) ) ) . '?msid=' . $_REQUEST[ 'msid' ] . '" />
';
				}
				else {
				$socialmeta .= '<meta property="og:url" content="' . esc_url( ( home_url( add_query_arg( array(), $wp->request ) ) ) ). '/" />
';				
				}
				if ( isset( $_REQUEST[ 'msid' ] ) ) {
				$socialmeta .= '<meta property="og:type" content="article" />
';
				}
				else {
				$socialmeta .= '<meta property="og:type" content="website" />
';				
				}
				$socialmeta .= '<meta property="og:site_name" content="' . strip_tags( get_the_title() ) . '" />
';
				if ( isset( $_REQUEST[ 'msid' ] ) ) {
					list( $width, $height, $type, $attr ) = getimagesize( base64_decode( $_REQUEST[ 'msid' ] ) );
					
					$socialmeta .= '<meta property="og:image" data-react-helmet="true" content="' . base64_decode( $_REQUEST[ 'msid' ] ) . '" /><meta property="og:image:width" data-react-helmet="true" content="' . $width . '" /><meta property="og:image:height" data-react-helmet="true" content="' . $height . '" />
';
				}
				else {
					$feat_image = $this->get_featured_image();
					if ( ! empty( $feat_image ) ) {
						$socialmeta .= '<meta property="og:image" content="' . $feat_image . '" />
';
					}
				}
			}
			return print( strip_tags( $socialmeta, '<meta>' ) );
		}
		
		public static function getInstance() {
			if ( ! isset( $instance ) ) {
				$instance = new modal_survey;
			}
		return $instance;
		}
		function deactivation_notice() {
			global $wpdb;
			$e_sql = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers LIMIT %d", '1' ) );
			if ( ! empty( $e_sql ) ) {
				if ( ! isset( $e_sql[ 0 ]->uniqueid ) ) {
				print( '<div class="error">
					<p>' . esc_html__( 'Modal Survey needs to be reactivated to initialize the new updates and keep your existing settings. Please ',MODAL_SURVEY_TEXT_DOMAIN ) . '<a href="' . esc_url( admin_url( 'plugins.php#modal-survey' ) ) . '">' . esc_html__( 'click here to go to the Plugins page ',MODAL_SURVEY_TEXT_DOMAIN ) . '</a>' . esc_html__( ', deactivate the plugin, then click on the Activate.',MODAL_SURVEY_TEXT_DOMAIN ) . '</strong></p>
				</div>' );
				}
			}
		}
		
		/**
		* Activate the plugin
		**/
		public static function activate() {
			global $wpdb;
			$db_info = array();
			//define custom data tables
			$charset_collate = '';
			if ( ! empty( $wpdb->charset ) ) {
			  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
			}

			if ( ! empty( $wpdb->collate ) ) {
			  $charset_collate .= " COLLATE {$wpdb->collate}";
			}
			$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->base_prefix . 'modal_survey_surveys' . " (
			  id varchar(255) NOT NULL,
			  name varchar(255) NOT NULL,
			  options text NOT NULL,
			  start_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  expiry_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  global tinyint(1) NOT NULL,
			  autoid mediumint(9) NOT NULL AUTO_INCREMENT,
			  created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  owner bigint NOT NULL,
			  UNIQUE KEY autoid (autoid)
			) $charset_collate";
			$wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix . 'modal_survey_questions' . " (
			  id mediumint(9) NOT NULL,
			  survey_id varchar(255) NOT NULL,
			  question text NOT NULL,
			  qoptions text NOT NULL
			) $charset_collate";
			$wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->base_prefix . 'modal_survey_answers' . " (
			  survey_id varchar(255) NOT NULL,
			  question_id mediumint(9) NOT NULL,
			  answer text NOT NULL,
			  aoptions text NOT NULL,
			  count mediumint(9) DEFAULT '0' NOT NULL,
			  autoid mediumint(9) NOT NULL,
			  uniqueid varchar(255) NOT NULL
			) $charset_collate";
			$wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->base_prefix . 'modal_survey_answers_text' . " (
			id varchar(255) NOT NULL,
			survey_id varchar(255) NOT NULL,
			answertext text NOT NULL,
			count mediumint(9) DEFAULT '0' NOT NULL
			) $charset_collate";
			$wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->base_prefix . 'modal_survey_participants' . " (
			  autoid mediumint(9) NOT NULL AUTO_INCREMENT,
			  id varchar(255) NOT NULL,
			  username varchar(255) NOT NULL,
			  email varchar(255) NOT NULL,
			  name varchar(255) NOT NULL,
			  custom text NOT NULL,
			  UNIQUE KEY autoid (autoid)
			) $charset_collate";
			$wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->base_prefix . 'modal_survey_participants_details' . " (
			  uid varchar(255) NOT NULL,
			  sid varchar(255) NOT NULL,
			  qid varchar(255) NOT NULL,
			  aid text NOT NULL,
			  postid bigint NOT NULL,
			  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  ip varchar(255) NOT NULL,
			  samesession varchar(255) NOT NULL,
			  timer int NULL
			) $charset_collate";
			$wpdb->query( $sql );

			if ( ! get_option( 'setting_keep_settings' ) ) {
				update_option( 'setting_keep_settings', 'off' );
			}
			if ( ! get_option( 'setting_globalsurvey' ) ) {
				update_option( 'setting_globalsurvey', 'on' );
			}
			if ( ! get_option( 'setting_minify' ) ) {
				update_option('setting_minify', 'on');
			}
			if ( ! get_option( 'setting_save_votes' ) ) {
				update_option('setting_save_votes', 'on');
			}	
			if ( ! get_option( 'setting_remember_users' ) ) {
				update_option('setting_remember_users', 'on');
			}
			if ( ! get_option( 'setting_display_once' ) ) {
				update_option( 'setting_display_once' , 'off' );
			}
			if ( ! get_option( 'setting_display_once_per_filled' ) ) {
				update_option( 'setting_display_once_per_filled' , 'off' );
			}
			if ( ! get_option( 'setting_plugininit' ) ) {
				update_option( 'setting_plugininit' , 'aftercontent' );
			}
			if ( ! get_option( 'setting_restapi' ) ) {
				update_option( 'setting_restapi' , 'off' );
			}
			if ( ! get_option( 'setting_restapi_privileges' ) ) {
				update_option( 'setting_restapi_privileges' , 'administrator' );
			}
			if ( ! get_option( 'setting_pdf_font' ) ) {
				update_option( 'setting_pdf_font' , 'dejavusans' );
			}
			if ( ! get_option( 'setting_pdf_header' ) || get_option( 'setting_pdf_header' ) == "" ) {
				update_option( 'setting_pdf_header' , 'generated by Modal Survey
http://pantherius.com/modal-survey' );
			}
			if ( ! get_option( 'setting_customcss' ) ) {
				add_option( 'setting_customcss' , '' );
			}
			if ( ! get_option( 'setting_db_modal_survey' ) ) {
				update_option( 'setting_db_modal_survey', MODAL_SURVEY_VERSION );
			}
			modal_survey::update_modal_survey_db();
		}
		/**
		* Deactivate the plugin
		**/
		public static function deactivate() {
			wp_unregister_sidebar_widget('modal_survey');
			unregister_setting('modal_survey-group', 'setting_display_once');
			unregister_setting('modal_survey-group', 'setting_display_once_per_filled');
			unregister_setting('modal_survey-group', 'setting_keep_settings');
			unregister_setting('modal_survey-group', 'setting_globalsurvey');
			unregister_setting('modal_survey-group', 'setting_minify');
			unregister_setting('modal_survey-group', 'setting_save_votes');
			unregister_setting('modal_survey-group', 'setting_remember_users');
			unregister_setting('modal_survey-group', 'setting_save_votes');
			unregister_setting('modal_survey-group', 'setting_pdf_header');
			unregister_setting('modal_survey-group', 'setting_plugininit');
			unregister_setting('modal_survey-group', 'setting_restapi');
			unregister_setting('modal_survey-group', 'setting_restapi_privileges');
			unregister_setting('modal_survey-group', 'setting_pdf_font');
			unregister_setting('modal_survey-group', 'setting_custom_individual_export');
			unregister_setting('modal_survey_social-group', 'setting_social');
			unregister_setting('modal_survey_social-group', 'setting_social_sites');
			unregister_setting('modal_survey_social-group', 'setting_social_metas');
			unregister_setting('modal_survey_social-group', 'setting_social_style');
			unregister_setting('modal_survey_social-group', 'setting_social_pos');
			unregister_setting('modal_survey_social-group', 'setting_fbappid');
			unregister_setting('modal_survey_customcss-group', 'setting_customcss');
		}
		
		/**
		* Uninstall the plugin
		**/
		public static function uninstall() {
			if ( get_option( "setting_keep_settings" ) != "on" ) {
				global $wpdb;
				$db_info = array();
				//define custom data tables
				$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->base_prefix . 'modal_survey_surveys' );
				$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->base_prefix . 'modal_survey_questions' );
				$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->base_prefix . 'modal_survey_answers' );
				$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->base_prefix . 'modal_survey_answers_text' );
				$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->base_prefix . 'modal_survey_participants' );
				$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->base_prefix . 'modal_survey_participants_details' );
				delete_option( 'setting_display_once' );
				delete_option( 'setting_display_once_per_filled' );
				delete_option( 'setting_keep_settings' );
				delete_option( 'setting_globalsurvey' );
				delete_option( 'setting_minify' );
				delete_option( 'setting_save_votes' );
				delete_option( 'setting_remember_users' );
				delete_option( 'setting_save_ip' );
				delete_option( 'setting_pdf_header' );
				delete_option( 'setting_plugininit' );
				delete_option( 'setting_restapi' );
				delete_option( 'setting_restapi_privileges' );
				delete_option( 'setting_pdf_font' );
				delete_option( 'setting_custom_individual_export' );
				delete_option( 'setting_social' );
				delete_option( 'setting_social_sites' );
				delete_option( 'setting_social_metas' );
				delete_option( 'setting_social_style' );
				delete_option( 'setting_social_pos' );
				delete_option( 'setting_fbappid' );
				delete_option( 'setting_customcss' );
				delete_option( 'setting_db_modal_survey' );
			}
		}
			
		/**
		* Enable Localization
		**/
		public function modalsurvey_localization() {
			// Localization
			load_plugin_textdomain( 'modal_survey', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		function analyze_advanced_conditions( $args, $fullrecords ) {
			$cpoints = 0;
			if ( $args[ 'condition' ] == "finalscore" ) {
				foreach( $fullrecords as $fr ) {
					foreach( $fr[ 'datas' ] as $frd ) {
						if ( $frd[ 'selected' ] == "true" ) {
							if ( ! isset( $frd[ 'status' ] ) || ( $frd[ 'status' ] != "inactive" )  ) {
								$cpoints += $frd[ 'score' ];
							}
						}
					}
				}
			}
			elseif ( $args[ 'condition' ] == "correctanswers" ) {
				foreach( $fullrecords as $fr ) {
					foreach( $fr[ 'datas' ] as $frd ) {
						if ( $frd[ 'selected' ] == "true" && $frd[ 'correct' ] == "true" ) {
							if ( ! isset( $frd[ 'status' ] ) || ( $frd[ 'status' ] != "inactive" )  ) {
								$cpoints++;
							}
						}
					}
				}					
			}
			elseif ( strpos( $args[ 'condition' ], 'questionscore' ) !== false ) {
				$index = explode( "_", $args[ 'condition' ] );
				foreach( $fullrecords as $key => $fr ) {
					if ( $key == ( $index[ 1 ] - 1 ) ) {
						foreach( $fr[ 'datas' ] as $frd ) {
							if ( $frd[ 'selected' ] == "true" ) {
								if ( ! isset( $frd[ 'status' ] ) || ( $frd[ 'status' ] != "inactive" )  ) {
									$cpoints = $frd[ 'score' ];
								}
							}
						}
					}
				}
			}
			elseif ( strpos( $args[ 'condition' ], 'questionanswer' ) !== false ) {
				$index = explode( "_", $args[ 'condition' ] );
				foreach( $fullrecords as $key => $fr ) {
					if ( $key == ( $index[ 1 ] - 1 ) ) {
						$aid = 0;
						foreach( $fr[ 'datas' ] as $frd ) {
							$aid++;
							if ( $frd[ 'selected' ] == "true" ) {
								if ( ! isset( $frd[ 'status' ] ) || ( $frd[ 'status' ] != "inactive" )  ) {
									$cpoints = $aid;
								}
							}
						}
					}
				}
			}
			elseif ( strpos( $args[ 'condition' ], 'categoryscore' ) !== false ) {
				$c_math = explode( "+", $args[ 'condition' ] ); $cpoints = 0;
				foreach ( $c_math as $math_elements ) {
					$cats = array();
					$index = explode( "_", $math_elements );
					foreach( $fullrecords as $fr ) {
						$category = "";
						preg_match_all( "/\[([^\]]*)\]/", $fr[ 'title' ], $cat );
						if ( $cat[ 1 ][ 0 ] ) {
							$category = strtolower( $cat[ 1 ][ 0 ] );
						}
						foreach( $fr[ 'datas' ] as $frd ) {
							if ( ! isset( $frd[ 'status' ] ) || ( $frd[ 'status' ] != "inactive" )  ) {
								preg_match_all( "/\[([^\]]*)\]/", $frd[ 'answer' ], $acat );
								if ( isset( $acat[ 1 ][ 0 ] ) ) {
									$category = strtolower( $acat[ 1 ][ 0 ] );
								}
								if ( $frd[ 'selected' ] == "true" && ! empty( $category ) ) {
									if ( ! isset( $cats[ $category ] ) ) {
										$cats[ $category ] = 0;
									}
									$cats[ $category ] += $frd[ 'score' ];
								}
							}
						}
					}				
					if ( isset( $cats[ strtolower( $index[ 1 ] ) ] ) ) {
						$cpoints += $cats[ strtolower( $index[ 1 ] ) ];
					}
				}
			}
			if ( $args[ 'relation' ] == "highest" ) {
				$max = array_keys( $cats, max( $cats ));
				if ( $max[ 0 ] ==  strtolower( $index[ 1 ] ) ) {
					return true;
				}
			}
			if ( $args[ 'relation' ] == "lowest" ) {
				$min = array_keys( $cats, min( $cats ) );
				if ( $min[ 0 ] ==  strtolower( $index[ 1 ] ) ) {
					return true;
				}
			}
			if ( $args[ 'relation' ] == "higher" ) {
				if ( $cpoints > $args[ 'value' ] ) {
					return true;
				}
			}
			if ( $args[ 'relation' ] == "equal" ) {
				$between = explode( "-", $args[ 'value' ] );
				if ( is_array( $between ) && isset( $between[ 1 ] ) ) {
					if ( $cpoints >= $between[ 0 ] && $cpoints <= $between[ 1 ] ) {
						return true;
					}							
				}
				else {
					if ( $cpoints == $args[ 'value' ] ) {
						return true;
					}
				}
			}
			if ( $args[ 'relation' ] == "notequal" ) {
				if ( $cpoints != $args[ 'value' ] ) {
					return true;
				}
			}
			if ( $args[ 'relation' ] == "lower" ) {
				if ( $cpoints < $args[ 'value' ] ) {
					return true;
				}
			}			
		}
		
		public function survey_conditions_shortcodes( $atts, $content = null ) {
			global $wpdb;
			$args =  shortcode_atts( array(
					'id' => '',
					'condition' => '',
					'relation' => '',
					'value' => '',
					'advanced' => '',
					'uid' => 'true',
					'filter' => '',
					'session' => 'last'
				), $atts );
			if ( empty( $args[ 'id' ] ) ) {
				return( esc_html__( 'Conditional Shortcode must contain the survey ID!', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			if ( ( empty( $args[ 'condition' ] ) && empty( $args[ 'relation' ] ) && empty( $args[ 'value' ] ) ) && empty( $args[ 'advanced' ] ) ) {
				return( esc_html__( 'Conditional Shortcode must contain the simple or the advanced conditions!', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			$cpoints = 0;$mcpoints = array();
			$mscuid = "";
			if ( $args[ 'session' ] == "last" ) {
				if ( $args[ 'uid' ] == "true" ) {
					if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {			
						$mscuid = $_COOKIE[ 'ms-uid' ];
					}
				}
				elseif ( $args[ 'uid' ] != "" ) {
						$mscuid = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE autoid = %s ", $args[ 'uid' ] ) );				
				}
				$last_session = "SELECT samesession FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND uid = %s ORDER BY time DESC";
				$args[ 'session' ] = $wpdb->get_var( $wpdb->prepare( $last_session, $args[ 'id' ], $mscuid ) );
				$lastvotes = " AND mspd.samesession = %s ";
			}
			$fullrecords = modal_survey::survey_answers_shortcodes( 
				array ( 'id' => $args[ 'id' ], 'data' => 'full-records', 'style' => 'plain', 'uid' => 'true', 'pure' => 'true', 'filter' => $args[ 'filter' ], 'session' => 'last'  )
			);
			if ( $args[ 'advanced' ] != '' ) {
				if ( strpos( strtolower( $args[ 'advanced' ] ), ' and ' ) !== false ) {
					$adv_conds_and = explode( " and ", strtolower( $args[ 'advanced' ] ) );
				}
				if ( strpos( strtolower( $args[ 'advanced' ] ), ' or ' ) !== false ) {
					$adv_conds_or = explode( " or ", strtolower( $args[ 'advanced' ] ) );
				}
				if ( ! empty( $adv_conds_and ) && ! empty( $adv_conds_or ) ) {
					return( esc_html__( 'Using AND OR in the same condition is currently not supported, you can use one of them.', MODAL_SURVEY_TEXT_DOMAIN ) );
				}
				if ( ! empty( $adv_conds_and ) && empty( $adv_conds_or ) ) {
					$adv_conds = $adv_conds_and;
					$adv_conds_type = "AND";
				}
				if ( empty( $adv_conds_and ) && ! empty( $adv_conds_or ) ) {
					$adv_conds = $adv_conds_or;
					$adv_conds_type = "OR";
				}
				$adv_cond_result = false;
				if ( isset( $adv_conds ) ) {
					foreach( $adv_conds as $ac ) {
						if ( strpos( $ac, "&lt;" ) !== false ) {
							$adv_relation = '&lt;';
							$adv_relation_text = "lower";
						}
						elseif ( strpos( $ac, "<" ) !== false ) {
							$adv_relation = '&lt;';
							$adv_relation_text = "lower";
						}
						elseif ( strpos( $ac, "!=" ) !== false ) {
							$adv_relation = '=';
							$adv_relation_text = "notequal";
						}
						elseif ( strpos( $ac, "=" ) !== false ) {
							$adv_relation = '=';
							$adv_relation_text = "equal";
						}
						elseif ( strpos( $ac, "&gt;" ) !== false ) {
							$adv_relation = '&gt;';
							$adv_relation_text = "higher";
						}
						elseif ( strpos( $ac, ">" ) !== false ) {
							$adv_relation = '&gt;';
							$adv_relation_text = "higher";
						}
						elseif ( ! isset( $adv_relation ) ) {
							return( esc_html__( 'Conditional relation sign is required to create a condition.', MODAL_SURVEY_TEXT_DOMAIN ) );
						}
						$adv_elements = explode( $adv_relation, $ac );
						$args[ 'condition' ] = trim( $adv_elements[ 0 ] );
						$args[ 'value' ] = trim( $adv_elements[ 1 ] );
						$args[ 'relation' ] = $adv_relation_text;
						$adv_res = $this->analyze_advanced_conditions( $args, $fullrecords);
						if ( $adv_conds_type == "OR" && $adv_res == true ) {
							return( do_shortcode( $content ) );
						}
						if ( $adv_conds_type == "AND" && $adv_res == false ) {
							return false; //replaces break; PHP7+
						}
					}
				}
				if ( ! isset( $adv_conds_type ) ) {
					return;
				}
				if ( $adv_conds_type == "AND" && $adv_res == true ) {
					return( do_shortcode( $content ) );
				}
			}
			else {
				if ( $args[ 'condition' ] == "finalscore" ) {
					foreach( $fullrecords as $fr ) {
						foreach( $fr[ 'datas' ] as $frd ) {
							if ( $frd[ 'selected' ] == "true" ) {
								if ( ! isset( $frd[ 'status' ] ) || ( $frd[ 'status' ] != "inactive" )  ) {
									$cpoints += $frd[ 'score' ];
								}
							}
						}
					}
				}
				elseif ( $args[ 'condition' ] == "correctanswers" ) {
					foreach( $fullrecords as $fr ) {
						foreach( $fr[ 'datas' ] as $frd ) {
							if ( $frd[ 'selected' ] == "true" && $frd[ 'correct' ] == "true" ) {
								if ( ! isset( $frd[ 'status' ] ) || ( $frd[ 'status' ] != "inactive" )  ) {
									$cpoints++;
								}
							}
						}
					}					
				}
				elseif ( strpos( $args[ 'condition' ], 'questionscore' ) !== false ) {
					$index = explode( "_", $args[ 'condition' ] );
					foreach( $fullrecords as $key => $fr ) {
						if ( $key == ( $index[ 1 ] - 1 ) ) {
							foreach( $fr[ 'datas' ] as $frd ) {
								if ( $frd[ 'selected' ] == "true" ) {
									if ( ! isset( $frd[ 'status' ] ) || ( $frd[ 'status' ] != "inactive" )  ) {
										$cpoints += $frd[ 'score' ];
									}
								}
							}
						}
					}
				}
				elseif ( strpos( $args[ 'condition' ], 'questionanswer' ) !== false ) {
					$index = explode( "_", $args[ 'condition' ] );
					foreach( $fullrecords as $key => $fr ) {
						if ( $key == ( $index[ 1 ] - 1 ) && isset( $fr[ 'datas' ] ) ) {
							$aid = 0;
							foreach( $fr[ 'datas' ] as $frd ) {
								$aid++;
								if ( $frd[ 'selected' ] == "true" ) {
									if ( ! isset( $frd[ 'status' ] ) || ( $frd[ 'status' ] != "inactive" )  ) {
										$cpoints = $aid;
										$mcpoints[] = $aid;
									}
								}
							}
						}
					}
				}
				elseif ( strpos( $args[ 'condition' ], 'categoryscore' ) !== false ) {
					$c_math = explode( "+", $args[ 'condition' ] );
					foreach ( $c_math as $math_elements ) {
						$index = explode( "_", $math_elements );
						foreach( $fullrecords as $fr ) {
							preg_match_all( "/\[([^\]]*)\]/", $fr[ 'title' ], $cat );
							foreach( $fr[ 'datas' ] as $frd ) {
								preg_match_all( "/\[([^\]]*)\]/", $frd[ 'answer' ], $acat );
								if ( isset( $acat[ 1 ][ 0 ] ) ) {
									$acat_list = explode( ",", $acat[ 1 ][ 0 ] );
									foreach ( $acat_list as $acal ) {
										if ( isset( $acal ) ) {
											if ( ! empty( $acal ) && ! is_numeric( $acal ) && $frd[ 'selected' ] == "true" && $frd[ 'score' ] ) {
												if ( ! isset( $cats[ strtolower( trim( $acal ) ) ] ) ) {
													$cats[ strtolower( trim( $acal ) ) ] = 0;
												}
												if ( isset( $cats[ strtolower( trim( $acal ) ) ] ) ) {
													$cats[ strtolower( trim( $acal ) ) ] += $frd[ 'score' ];
												}
											}
										}
									}
								}
							}
						}				
						if ( isset( $cats[ strtolower( $index[ 1 ] ) ] ) ) {
							$cpoints += $cats[ strtolower( $index[ 1 ] ) ];
						}
					}
				}
				elseif ( strpos( $args[ 'condition' ], 'categoryavgscore' ) !== false ) {
					$c_math = explode( "+", $args[ 'condition' ] );
					foreach ( $c_math as $math_elements ) {
						$index = explode( "_", $math_elements );
						foreach( $fullrecords as $fr ) {
							preg_match_all( "/\[([^\]]*)\]/", $fr[ 'title' ], $cat );
							foreach( $fr[ 'datas' ] as $frd ) {
								preg_match_all( "/\[([^\]]*)\]/", $frd[ 'answer' ], $acat );
								if ( ! isset( $acat[ 1 ][ 0 ] ) ) {
									$acat[ 1 ][ 0 ] = $frd[ 'category' ];
								}
								if ( isset( $acat[ 1 ][ 0 ] ) ) {
									$acat_list = explode( ",", $acat[ 1 ][ 0 ] );
									foreach ( $acat_list as $acal ) {
										if ( isset( $acal ) ) {
											if ( ! empty( $acal ) && ! is_numeric( $acal ) && $frd[ 'selected' ] == "true" && $frd[ 'score' ] ) {
												if ( ! isset( $cats[ strtolower( trim( $acal ) ) ] ) ) {
													$cats[ strtolower( trim( $acal ) ) ] = 0;
												}
												if ( ! isset( $cats_count[ strtolower( trim( $acal ) ) ] ) ) {
													$cats_count[ strtolower( trim( $acal ) ) ] = 0;
												}
												if ( isset( $cats[ strtolower( trim( $acal ) ) ] ) ) {
													$cats[ strtolower( trim( $acal ) ) ] += $frd[ 'score' ];
												}
												if ( isset( $cats_count[ strtolower( trim( $acal ) ) ] ) ) {
													$cats_count[ strtolower( trim( $acal ) ) ]++;
												}
											}
										}
									}
								}
							}
						}				
						if ( isset( $cats[ strtolower( $index[ 1 ] ) ] ) ) {
							$cpoints += round( $cats[ strtolower( $index[ 1 ] ) ] / $cats_count[ strtolower( $index[ 1 ] ) ], 2 );
						}
					}
				}
				$catsfilter = array_map( function( $item ) {
				return ( strtolower( trim( $item ) ) );
				}, explode( ',', $args[ 'filter' ] ) );
				if ( is_array( $catsfilter ) && isset( $cats ) ) {
					foreach( $cats as $key => $sditems ) {
						if ( ! in_array( strtolower( $key ), $catsfilter ) ) {
							unset( $cats[ $key ] );
						}
					}
				}
				if ( $args[ 'relation' ] == "highest" ) {
					if ( ! isset( $cats ) ) {
						return;
					}
					$tempcats = $cats;
					if ( ( $key = array_search( "-", $tempcats ) ) !== false ) {
						unset( $tempcats[ $key ] );
					}
					$max = array_keys( $tempcats, max( $tempcats ));
					if ( in_array( strtolower( $index[ 1 ] ), $max ) ) {
						return( do_shortcode( $content ) );
					}
				}
				if ( $args[ 'relation' ] == "lowest" ) {
					if ( ! isset( $cats ) ) {
						return;
					}
					$tempcats = $cats;
					if ( ( $key = array_search( "-", $tempcats ) ) !== false ) {
						unset( $tempcats[ $key ] );
					}
					$min = array_keys( $tempcats, min( $tempcats ) );
					if ( $min[ 0 ] ==  strtolower( $index[ 1 ] ) ) {
						return( do_shortcode( $content ) );
					}
				}
				if ( $args[ 'relation' ] == "higher" ) {
					if ( $cpoints > $args[ 'value' ] ) {
						return( do_shortcode( $content ) );
					}
				}
				if ( $args[ 'relation' ] == "equal" ) {
					$between = explode( "-", $args[ 'value' ] );
					if ( count( $mcpoints ) > 1 ) {
							if ( in_array( $args[ 'value' ], $mcpoints ) ) {
								return( do_shortcode( $content ) );
							}						
					}
					else {
						if ( is_array( $between ) && isset( $between[ 1 ] ) ) {
							if ( $cpoints >= $between[ 0 ] && $cpoints <= $between[ 1 ] ) {
								return( do_shortcode( $content ) );
							}							
						}
						else {
							if ( $cpoints == $args[ 'value' ] ) {
								return( do_shortcode( $content ) );
							}
						}
					}
				}
				if ( $args[ 'relation' ] == "notequal" ) {
					if ( $cpoints != $args[ 'value' ] ) {
						return( do_shortcode( $content ) );
					}
				}
				if ( $args[ 'relation' ] == "lower" ) {
					if ( $cpoints < $args[ 'value' ] ) {
							return( do_shortcode( $content ) );
					}
				}
			}
		}


		public function survey_useraction( $atts ) {
			global $wpdb;
			extract( shortcode_atts( array(
					'id' => '-1',
					'action' => '',
					'type' => 'button',
					'session' => 'last',
					'redirection' => '',
					'text' => 'DELETE'
				), $atts, 'survey_useraction' ) );	
				if ( ! isset( $atts[ 'action' ] ) ) {
					$atts[ 'action' ] = 'delete';
				}
				if ( ! isset( $atts[ 'type' ] ) ) {
					$atts[ 'type' ] = 'button';
				}
				if ( ! isset( $atts[ 'session' ] ) ) {
					$atts[ 'session' ] = 'last';
				}
				if ( ! isset( $atts[ 'redirection' ] ) ) {
					$atts[ 'redirection' ] = '';
				}
				if ( ! isset( $atts[ 'text' ] ) ) {
					$atts[ 'text' ] = 'DELETE';
				}
			if ( empty( $atts[ 'id' ] ) )	{
				return esc_html__( 'Survey ID parameter is empty, please specify.', MODAL_SURVEY_TEXT_DOMAIN );
			}
			if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {
				$ssuid = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE id = %s ", $_COOKIE[ 'ms-uid' ] ) );
				$user_sessions = $wpdb->get_results( $wpdb->prepare( "SELECT samesession FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE uid = %s AND sid = %s GROUP BY samesession ORDER BY time DESC", $ssuid, $atts[ 'id' ] ) );
				$last_session = $user_sessions[ 0 ]->samesession;
				if ( isset( $_REQUEST[ 'modalsurvey_useraction_button' ] ) ) {
					$newatts = json_decode( stripslashes( $_REQUEST[ 'modalsurvey_useraction_value' ] ), true );
					if ( $_REQUEST[ 'modalsurvey_useraction_button' ] == "delete" ) {
						setcookie( 'ms-uid' , '' , time() - 3600, COOKIEPATH, COOKIE_DOMAIN, false );
						setcookie( 'modal_survey' , '' , time() - 3600, COOKIEPATH, COOKIE_DOMAIN, false );
						setcookie( 'ms-session' , '' , time() - 3600, COOKIEPATH, COOKIE_DOMAIN, false );
						if ( $atts[ 'session' ] == "last" ) {
							$result = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE `uid` = %d AND `sid` = %d AND `samesession` = %s", $ssuid, $newatts[ "id" ], $last_session ) );							
						}
						else {
							$result = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE `uid` = %d AND `sid` = %d", $ssuid, $newatts[ "id" ] ) );							
						}
						if ( $result ) {
							echo '<div class="updated"><p>'.esc_html__( 'Successfully Deleted!', MODAL_SURVEY_TEXT_DOMAIN ).'</p></div>';
						}
						else {
							echo '<div class="error"><p>'.esc_html__( 'Error Occurred During the Deletion!', MODAL_SURVEY_TEXT_DOMAIN ).'</p></div>';
						}
						if ( ! empty( $newatts[ "redirection" ] ) ) {
							print('<script type="text/JavaScript">window.location.replace("' . $newatts[ "redirection" ] . '");</script>');
						}
					}
				}
				else {
					if ( ! empty( $atts[ "action" ] ) && ! empty( $last_session ) ) {
						if ( $atts[ "type" ] == "button" ) {
							return "<form method='post' id='ms-user-action-form'><input type='hidden' name='modalsurvey_useraction_button' value='" . $atts[ 'action' ] . "'><input type='hidden' name='modalsurvey_useraction_value' value='" . json_encode( $atts ) . "'><input type='submit' class='button' name='send_ms_ua_from' value='" . $atts[ 'text' ] . "'></form>";
						}
						if ( $atts[ "type" ] == "link" ) {
							return "<form method='post' id='ms-user-action-form'><input type='hidden' name='modalsurvey_useraction_button' value='" . $atts[ 'action' ] . "'><input type='hidden' name='modalsurvey_useraction_value' value='" . json_encode( $atts ) . "'><a href='#' class='send_ms_ua_form' id='send_ms_ua_form_link'>" . $atts[ 'text' ] . "</a></form>";
						}
					}
				}
			}
		}
				
		public function survey_calculation_shortcodes( $atts ) {
			global $wpdb, $msplugininit_answer_array;
			extract( shortcode_atts( array(
					'id' => '-1',
					'math' => '',
					'decimal' => '2',
					'uid' => 'true',
					'session' => 'last'
				), $atts, 'survey_calculation' ) );	
				if ( ! isset( $atts[ 'math' ] ) ) {
					$atts[ 'math' ] = '';
				}
				if ( ! isset( $atts[ 'decimal' ] ) ) {
					$atts[ 'decimal' ] = 2;
				}
				if ( ! isset( $atts[ 'uid' ] ) ) {
					$atts[ 'uid' ] = 'true';
				}
				if ( ! isset( $atts[ 'session' ] ) ) {
					$atts[ 'session' ] = 'last';
				}
			if ( empty( $atts[ 'math' ] ) )	{
				return esc_html__( 'Math parameter is empty, please specify.', MODAL_SURVEY_TEXT_DOMAIN );
			}
				$ssuid = "";
			$process_math = preg_split( "/ (\*|\-|\+|\%) /", $atts[ 'math' ], 0, PREG_SPLIT_DELIM_CAPTURE );
			$calc_results = 0;
			foreach( $process_math as $key => $pm ) {
				//get answer of specific question
				if ( strpos( $pm, 'question' ) !== false && strpos( $pm, '_score' ) === false ) {
					$score = modal_survey::survey_records_shortcodes( 
								array ( 'id' => $atts[ 'id' ], 'qid' => str_replace( "question", "", $pm ), 'aid' => 'selected', 'data' => 'answer', 'uid' => $atts[ 'uid' ], 'session' => $atts[ 'session' ] ) );
				}
				elseif ( strpos( $pm, 'question' ) !== false && strpos( $pm, '_score' ) !== false ) {
				//get score of specific question
					$score = modal_survey::survey_answers_shortcodes( 
								array ( 'id' => $atts[ 'id' ], 'qid' => str_replace( "_score", "", str_replace( "question", "", $pm ) ), 'style' => 'plain', 'data' => 'score', 'uid' => $atts[ 'uid' ], 'session' => $atts[ 'session' ] ) );					
				}
				elseif ( strpos( $pm, 'category_' ) !== false ) {
				//get score of specific category
					$score = modal_survey::survey_answers_shortcodes( 
								array ( 'id' => $atts[ 'id' ], 'qid' => str_replace( "category_", "", $pm ), 'style' => 'plain', 'data' => 'score', 'uid' => $atts[ 'uid' ], 'session' => $atts[ 'session' ] ) );					
				}
				elseif ( strpos( $pm, 'categoryavg_' ) !== false ) {
				//get score of specific category
					$score = modal_survey::survey_answers_shortcodes( 
								array ( 'id' => $atts[ 'id' ], 'qid' => str_replace( "categoryavg_", "", $pm ), 'style' => 'plain', 'data' => 'average-score', 'uid' => $atts[ 'uid' ], 'session' => $atts[ 'session' ] ) );					
				}
				elseif ( strpos( $pm, 'totalscore' ) !== false ) {
				//get score of specific category
					$score = modal_survey::survey_answers_shortcodes( 
								array ( 'id' => $atts[ 'id' ], 'style' => 'plain', 'data' => 'score', 'uid' => $atts[ 'uid' ], 'session' => $atts[ 'session' ] ) );					
				}
				elseif ( ! is_numeric( floatval( $score ) ) ) {
					return esc_html__( $score . ' is not numeric, it is not possible to create calculations with non-numeric values.', MODAL_SURVEY_TEXT_DOMAIN );
				}
				elseif ( is_numeric( floatval( $pm ) ) ) {
					$score = $pm;
				}
				if ( $key == 0 ) {
					$calc_results = $score;
				}
				else {
					if ( isset( $process_math[ $key - 1 ] ) ) {
						if ( $process_math[ $key - 1 ] == "*" ) {
							$calc_results = $calc_results * $score;
							$score = 0;
						}
						if ( $process_math[ $key - 1 ] == "-" ) {
							$calc_results -= $score;
							$score = 0;
						}
						if ( $process_math[ $key - 1 ] == "+" ) {
							$calc_results += $score;
							$score = 0;
						}
						if ( $process_math[ $key - 1 ] == "%" ) {
							if ( $score > 0 ) {
								$calc_results = $calc_results / $score;
								$score = 0;
							}
							else {
								$calc_results = 0;
							}
						}
					}
				}
			}
			$decs = abs($calc_results) - floor(abs($calc_results));
			if ( $decs > 0 ) {
				return number_format( $calc_results, $atts[ 'decimal' ] );
			}
			if ( is_numeric( $calc_results ) ) {
				return number_format( $calc_results, 0 );
			}
			return esc_html__( $calc_results . ' result is not numeric, it is possible to create calculations with numeric values only.', MODAL_SURVEY_TEXT_DOMAIN );
		}
		
		public function survey_open_answers_shortcodes( $atts ) {
			global $wpdb, $msplugininit_answer_array;
			extract( shortcode_atts( array(
					'id' => '-1',
					'qid' => ''
				), $atts, 'survey_open_answers' ) );	
				if ( ! isset( $atts[ 'qid' ] ) ) {
					$atts[ 'qid' ] = '';
				}
				$list_openanswers = array();
				if ( $atts[ 'qid' ] == '' ) {
					$list_openanswers = $wpdb->get_results( $wpdb->prepare( "SELECT msat.answertext FROM " . $wpdb->base_prefix . "modal_survey_answers_text msat LEFT JOIN " . $wpdb->base_prefix . "modal_survey_answers msa on msat.id = msa.uniqueid WHERE msat.survey_id = %s ORDER BY msa.question_id ASC", $atts[ 'id' ] ) );
				}
				else {
					$list_openanswers = $wpdb->get_results( $wpdb->prepare( "SELECT msat.answertext FROM " . $wpdb->base_prefix . "modal_survey_answers_text msat LEFT JOIN " . $wpdb->base_prefix . "modal_survey_answers msa on msat.id = msa.uniqueid WHERE msat.survey_id = %s AND msa.question_id = %s", $atts[ 'id' ], $atts[ 'qid' ] ) );					
				}
			$result = "<div id='ms-survey-openanswers-" . $atts[ 'id' ] . "'>";
			if ( ! empty( $list_openanswers ) ) {
				foreach( $list_openanswers as $key=>$lo ) {
					$result .= "<div class='each-open-answer ms-ota-" . $key . "'>" . $lo->answertext . "</div>";
				}
			}
			else {
				$result .= "<div class='each-open-answer ms-ota-" . $key . "'>" . esc_html__( 'Data doesn\'t exists', MODAL_SURVEY_TEXT_DOMAIN ) . "</div>";				
			}
			$result .= "</div>";
			return $result;
		}
		
		public function survey_records_shortcodes( $atts ) {
			global $wpdb, $msplugininit_answer_array;
			extract( shortcode_atts( array(
					'id' => '-1',
					'data' => 'name',
					'qid' => '',
					'aid' => '',
					'uid' => 'true',
					'session' => 'last'
				), $atts, 'survey_records' ) );	
				if ( ! isset( $atts[ 'data' ] ) ) {
					$atts[ 'data' ] = 'name';
				}
				if ( ! isset( $atts[ 'uid' ] ) ) {
					$atts[ 'uid' ] = 'true';
				}
				if ( ! isset( $atts[ 'session' ] ) ) {
					$atts[ 'session' ] = 'last';
				}
				if ( ! isset( $atts[ 'qid' ] ) ) {
					$atts[ 'qid' ] = '';
				}
				if ( ! isset( $atts[ 'aid' ] ) ) {
					$atts[ 'aid' ] = '';
				}
				$ssuid = "";
			if ( $atts[ 'session' ] == "last" ) {
				if ( $atts[ 'uid' ] == "true" ) {
					if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {
						$ssuid = $_COOKIE[ 'ms-uid' ];
					}
				}
				elseif ( $atts[ 'uid' ] != "" ) {
						$ssuid = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE autoid = %s ", $atts[ 'uid' ] ) );
				}
				$current_autoid = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE id = %s ", $ssuid ) );
				$last_session = "SELECT samesession FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND uid = %s ORDER BY time DESC";
				$atts[ 'session' ] = $wpdb->get_var( $wpdb->prepare( $last_session, $atts[ 'id' ], $current_autoid ) );
				$lastvotes = " AND mspd.samesession = %s ";
			}
			$records = modal_survey::survey_answers_shortcodes( 
					array ( 'id' => $atts[ 'id' ], 'data' => 'full-records', 'style' => 'plain', 'limited' => 'no', 'uid' => $ssuid, 'title' => '<span>', 'score' => 'true', 'session' => $atts[ 'session' ] )
					);
			$sql_u_t = "SELECT DATE_FORMAT( time,'%%Y-%%m-%%d') as date, DATE_FORMAT( time,'%%Y-%%m-%%d %%H:%%i') as datetime FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND uid = %s ORDER BY time DESC";
			$a_sql_u_t = $wpdb->get_row( $wpdb->prepare( $sql_u_t, $atts[ 'id' ], $ssuid ) );
			$sql_u = "SELECT autoid as id, username, email, name, custom FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE id = %s";
			$a_sql_u = $wpdb->get_row( $wpdb->prepare( $sql_u, $ssuid ) );
			if ( isset( $a_sql_u_t->date ) ) {
				$a_sql_u->date = $a_sql_u_t->date;
			}
			if ( isset( $a_sql_u_t->datetime ) ) {
				$a_sql_u->datetime = $a_sql_u_t->datetime;
			}
			if ( ! empty( $a_sql_u->{$atts[ 'data' ]} ) ) {
				return $a_sql_u->{$atts[ 'data' ]};
			}
			$u_custom = array();
			if ( ! empty( $a_sql_u->custom ) ) {
				$u_custom = unserialize( $a_sql_u->custom );
			}
			if ( ! empty( $u_custom ) ) {
				foreach( $u_custom as $cskey => $ucs) {
					if ( strtolower( $cskey ) == strtolower( $atts[ 'data' ] ) ) {
						return $ucs;
					}
				}
			}
			
			if ( $atts[ 'qid' ] != "" ) {
				if ( ! empty( $records[ $atts[ 'qid' ] - 1 ][ $atts[ 'data' ] ] ) ) {
					return $records[ $atts[ 'qid' ] - 1 ][ $atts[ 'data' ] ];
				}
			}
			$sr_selans = "";
			if ( $atts[ 'qid' ] != "" && $atts[ 'aid' ] != "" ) {
				if ( $atts[ 'aid' ] == "selected" ) {
					foreach( $records[ $atts[ 'qid' ] - 1 ][ 'datas' ] as $qs ) {
						if ( $qs[ 'selected' ] == "true" ) {
							$sr_selans .= $qs[ $atts[ 'data' ] ] . ", ";
						}
					}
					return substr( $sr_selans, 0, ( strlen( $sr_selans ) - 2 ) );
				}
				else {
					if ( ! empty( $records[ $atts[ 'qid' ] - 1 ][ 'datas' ][ $atts[ 'aid' ] ][ $atts[ 'data' ] ] ) ) {
						return $records[ $atts[ 'qid' ] - 1 ][ 'datas' ][ $atts[ 'aid' ] ][ $atts[ 'data' ] ];
					}
				}
			}
			return esc_html__( 'Data doesn\'t exists', MODAL_SURVEY_TEXT_DOMAIN );
		}
		
		public function survey_compare_chart_shortcodes( $atts ) {
			global $wpdb, $msplugininit_answer_array, $current_user;
			$atts = shortcode_atts( array(
					'id' => '-1',
					'style' => 'barchart',
					'data' => 'score',
					'bgcolor' => '',
					'cbgcolor' => '',
					'color' => '',
					'filter' => '',
					'max' => '',
					'sort' => '',
					'title' => '',
					'labels' => '',
					'hidequestion' => 'no',
					'legend' => 'false',
					'percentage' => 'false',
					'showhidden' => 'false',
					'printable' => 'false'
				), $atts, 'survey_compare_chart' );
			if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {
				$ssuid = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE id = %s ", $_COOKIE[ 'ms-uid' ] ) );
			}
			else {
				return false;
			}
			$sql_sessions = $wpdb->get_results( $wpdb->prepare( "SELECT samesession, time as created FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE uid = %s AND sid = %s GROUP BY samesession ORDER BY time ASC", $ssuid, $atts[ 'id' ] ) );
			foreach( $sql_sessions as $key=>$ss ) {
				$records = self::survey_answers_shortcodes( 
					array ( 'id' => $atts[ 'id' ], 'data' => $atts[ 'data' ], 'style' => 'radarchart', 'filter' => $atts[ 'filter' ], 'uid' => 'true', 'session' => $ss->samesession, 'pure' => 'true'  )
				);
			$wp_timezone = get_option( 'timezone_string' );
			$ctime = $ss->created;
			if ( ! empty( $wp_timezone ) ) {
				$newdate = new DateTime( $ctime, new DateTimeZone( 'UTC' ) );
				$newdate->setTimezone(new DateTimeZone( $wp_timezone ));
				$ctime = $newdate->format( 'd/m/Y H:i' );
			}
				$times[] = $ctime;
				$fullrecords[] = $records[ 0 ];
			}
			if ( ! empty( $atts[ 'labels' ] ) ) {
					$labels = explode( ",", $atts[ 'labels' ] );
					foreach( $labels as $ks=>$ls ) {
						if ( trim( $ls ) == 'auto' ) {
							$labels[ $ks ] = $times[ $ks ];
						}
					}
			}
			$filtered = explode( ",", $atts[ 'filter' ] );
			foreach( $fullrecords as $key=>$frs ) {
				foreach( $frs as $key2=>$data ) {
					if ( in_array( $data[ 'answer' ], $filtered ) || $atts[ 'filter' ] == '' ) {
						if ( $labels ) {
							if ( isset( $labels[ $key ] ) ) {
								$data[ 'answer' ] = $labels[ $key ];
							}
						}
						if ( isset( $labels[ $key ] ) ) {
							$sdatas[ $key2 ][ $key ] = $data;
						}
					}
				}
			}
			$results = "";
			$titles = explode( ",", $atts[ 'title' ] );
			foreach( $sdatas as $key=>$sd ) {
			$unique_key = mt_rand();
			if ( ! isset( $titles[ $key ] ) ) {
				$titles[ $key ] = "";
			}
			if ( ! isset( $result ) ) {
				$result = "";
			}
			$result .= '<div class="ms-chart-row"><h4>' . $titles[ $key ] . '</h4><div id="survey-results-' . $atts[ 'id' ] . '-' . $unique_key . '" class="survey-results chart-container' . $key . '"><div class="modal-survey-chart0 ms-chart"><div class="legendDiv"></div><canvas></canvas></div></div></div>';
				$msplugininit_answer_array[ $atts[ 'id' ] . '-' . $unique_key ] = array( 
																					"printable" => $atts[ 'printable' ],
																					"style" => array( 
																						"style" => $atts[ 'style' ],
																						"max" => $atts[ 'max' ],
																						"bgcolor" => $atts[ 'bgcolor' ],
																						"cbgcolor" => $atts[ 'cbgcolor' ],
																						"legend" => $atts[ 'legend' ]
																						),
																					"datas" => array( $sd )
																				);
			}
			return $result;
		}
		
		public function survey_answers_shortcodes( $atts ) {
			global $wpdb, $msplugininit_answer_array, $current_user;
			$unique_key = mt_rand();
			$result = "";$cat_count = array();
			if ( isset( $_REQUEST[ 'sspcmd' ] ) && $_REQUEST[ 'sspcmd' ] == "displaychart" ) {
				$unique_key = "endcontent";
			}
			extract( shortcode_atts( array(
					'id' => '-1',
					'style' => 'progressbar',
					'data' => 'full',
					'qid' => '1',
					'aid' => '',
					'titles' => '',
					'compare' => '',
					'bgcolor' => '',
					'cbgcolor' => '',
					'color' => '',
					'hidecounter' => 'no',
					'uid' => 'false',
					'limited' => 'no',
					'max' => '',
					'sort' => '',
					'title' => '<h3>',
					'init' => '',
					'hidequestion' => 'no',
					'pure' => 'false',
					'alternativedatas' => 'true',
					'score' => 'false',
					'top' => '',
					'session' => '',
					'legend' => 'false',
					'tooltip' => 'false',
					'percentage' => 'false',
					'showhidden' => 'false',
					'progress' => 'false',
					'catmax' => 'false',
					'after' => '',
					'filter' => '',
					'decimal' => '2',
					'correct' => 'false',
					'printable' => 'false'
				), $atts, 'survey_answers' ) );
				if ( ! isset( $atts[ 'style' ] ) ) {
					$atts[ 'style' ] = 'progressbar';
				}
				if ( ! isset( $atts[ 'sort' ] ) ) {
					$atts[ 'sort' ] = '';
				}
				if ( ! isset( $atts[ 'filter' ] ) ) {
					$atts[ 'filter' ] = '';
				}
				if ( ! isset( $atts[ 'decimal' ] ) ) {
					$atts[ 'decimal' ] = 2;
				}
				if ( ! isset( $atts[ 'title' ] ) ) {
					$atts[ 'title' ] = '<h3 class="survey_header">';
				}
				if ( ! isset( $atts[ 'qid' ] ) && ( $atts[ 'style' ] != "plain" ) ) {
					$atts[ 'qid' ] = '1';
				}
				else {
					if ( ! isset( $atts[ 'qid' ] ) ) {
						$atts[ 'qid' ] = "";
					}
				}
				if ( ! isset( $atts[ 'aid' ] ) ) {
					$atts[ 'aid' ] = '';
				}
				if ( ! isset( $atts[ 'titles' ] ) ) {
					$atts[ 'titles' ] = '';
				}
				if ( ! isset( $atts[ 'compare' ] ) ) {
					$atts[ 'compare' ] = 'false';
				}
				if ( ! isset( $atts[ 'data' ] ) ) {
					$atts[ 'data' ] = 'full';
				}
				if ( ! isset( $atts[ 'hidecounter' ] ) ) {
					$atts[ 'hidecounter' ] = 'no';
				}
				if ( ! isset( $atts[ 'uid' ] ) ) {
					$atts[ 'uid' ] = 'false';
				}
				if ( ! isset( $atts[ 'limited' ] ) ) {
					$atts[ 'limited' ] = 'no';
				}
				if ( ! isset( $atts[ 'max' ] ) ) {
					$atts[ 'max' ] = '0';
				}
				if ( ! isset( $atts[ 'postid' ] ) ) {
					$atts[ 'postid' ] = '';
				}
				if ( ! isset( $atts[ 'hidequestion' ] ) ) {
					$atts[ 'hidequestion' ] = 'no';
				}
				if ( ! isset( $atts[ 'bgcolor' ] ) ) {
					$atts[ 'bgcolor' ] = '';
				}
				if ( ! isset( $atts[ 'cbgcolor' ] ) ) {
					$atts[ 'cbgcolor' ] = '';
				}
				if ( ! isset( $atts[ 'color' ] ) ) {
					$atts[ 'color' ] = '';
				}
				if ( ! isset( $atts[ 'init' ] ) ) {
					$atts[ 'init' ] = '';
				}
				if ( ! isset( $atts[ 'pure' ] ) ) {
					$atts[ 'pure' ] = 'false';
				}
				if ( ! isset( $atts[ 'alternativedatas' ] ) ) {
					$atts[ 'alternativedatas' ] = 'true';
				}
				if ( ! isset( $atts[ 'percentage' ] ) ) {
					$atts[ 'percentage' ] = 'false';
				}
				if ( ! isset( $atts[ 'after' ] ) ) {
					$atts[ 'after' ] = '';
				}
				if ( ! isset( $atts[ 'score' ] ) ) {
					$atts[ 'score' ] = 'false';
				}
				if ( ! isset( $atts[ 'top' ] ) ) {
					$atts[ 'top' ] = '';
				}
				if ( ! isset( $atts[ 'session' ] ) ) {
					$atts[ 'session' ] = '';
				}
				if ( ! isset( $atts[ 'legend' ] ) ) {
					$atts[ 'legend' ] = 'false';
				}
				if ( ! isset( $atts[ 'tooltip' ] ) ) {
					$atts[ 'tooltip' ] = 'false';
				}
				if ( ! isset( $atts[ 'showhidden' ] ) ) {
					$atts[ 'showhidden' ] = 'false';
				}
				if ( ! isset( $atts[ 'progress' ] ) ) {
					$atts[ 'progress' ] = 'false';
				}
				if ( ! isset( $atts[ 'catmax' ] ) ) {
					$atts[ 'catmax' ] = 'false';
				}
				if ( ! isset( $atts[ 'correct' ] ) ) {
					$atts[ 'correct' ] = 'false';
				}
				if ( ! isset( $atts[ 'printable' ] ) ) {
					$atts[ 'printable' ] = 'false';
				}
				if ( ! is_single() && !is_page() && $atts[ 'limited' ] == "yes" ) {
					return('');
				}
				$args = array(
					'id' => $atts[ 'id' ],
					'style' => $atts[ 'style' ],
					'sort' => $atts[ 'sort' ],
					'title' => $atts[ 'title' ],
					'data' => $atts[ 'data' ],
					'qid' => $atts[ 'qid' ],
					'aid' => $atts[ 'aid' ],
					'hidecounter' => $atts[ 'hidecounter' ],
					'max' => $atts[ 'max' ],
					'postid' => $atts[ 'postid' ],
					'hidequestion' => $atts[ 'hidequestion' ],
					'uid' => $atts[ 'uid' ],
					'limited' => $atts[ 'limited' ],
					'bgcolor' => $atts[ 'bgcolor' ],
					'cbgcolor' => $atts[ 'cbgcolor' ],
					'color' => $atts[ 'color' ],
					'titles' => $atts[ 'titles' ],
					'init' => $atts[ 'init' ],
					'compare' => $atts[ 'compare' ],
					'percentage' => $atts[ 'percentage' ],
					'after' => $atts[ 'after' ],
					'pure' => $atts[ 'pure' ],
					'alternativedatas' => $atts[ 'alternativedatas' ],
					'score' => $atts[ 'score' ],
					'top' => $atts[ 'top' ],
					'session' => $atts[ 'session' ],
					'legend' => $atts[ 'legend' ],
					'tooltip' => $atts[ 'tooltip' ],
					'showhidden' => $atts[ 'showhidden' ],
					'progress' => $atts[ 'progress' ],
					'filter' => $atts[ 'filter' ],
					'decimal' => $atts[ 'decimal' ],
					'catmax' => $atts[ 'catmax' ],
					'correct' => $atts[ 'correct' ],
					'printable' => $atts[ 'printable' ]
					);
			if ( $args[ 'postid' ] == 'postid' ) {
				if ( isset( $_REQUEST[ 'postid' ] ) ) {
					$args[ 'postid' ] = (int) $_REQUEST[ 'postid' ];
				}
				$postid = get_the_ID();
				if ( is_numeric( $postid ) ) {
					$args[ 'postid' ] = $postid;
				}
			}
			$catsfilter = array_map( function( $item ) {
				return ( strtolower( trim( $item ) ) );
			}, explode( ',', $args[ 'filter' ] ) );
			if ( ( $args[ 'data' ] == 'score' || $args[ 'data' ] == 'average-score' || $args[ 'data' ] == 'rating' ) && ( $args[ 'style' ] != "plain" ) ) {
				$atts[ 'qid' ] = '';
				$args[ 'qid' ] = '';
			}
			//retrieve last survey completion for the current user
			if ( ! empty( $current_user->user_login ) && $args[ 'uid' ] != "false" && ! isset( $_COOKIE[ 'ms-uid' ] ) && ( $atts[ 'session' ] == "last" || is_numeric( $atts[ 'session' ] ) ) && $args[ 'uid' ] == "true" ) {
				$args[ 'uid' ] = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE username = %s", $current_user->user_login ) );
			}
			$answercats = array();
			$answercats_counts = array();
			$ssuid = "";
			$lastvotes = "";
			$timer = array();
			$already_added = array();
			$args[ 'title' ] = html_entity_decode( $args[ 'title' ] );
			$title_c = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', $args['title']);
			$args[ 'title_c' ] = str_replace( "<", "</", $title_c );
			$sdatas = array();
			if( strtoupper( $args[ 'sort' ] ) == "DESC" ) {
				$sort = "count DESC";
			}
			elseif( strtoupper( $args[ 'sort' ] ) == "ASC" ) {
				$sort = "count ASC";
			}
			else {
				$sort = "autoid ASC";
			}
			if ( $atts[ 'session' ] == "last" || is_numeric( $atts[ 'session' ] ) ) {
				if ( $args[ 'uid' ] == "true" ) {
					if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {
						$ssuid = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE id = %s ", $_COOKIE[ 'ms-uid' ] ) );
					}
				}
				elseif ( $args[ 'uid' ] != "" ) {
					$ssuid = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE autoid = %s ", $args[ 'uid' ] ) );
					if ( empty( $ssuid ) ) {
						$ssuid = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE id = %s ", $args[ 'uid' ] ) );
					}
				}
				if ( is_numeric( $atts[ 'session' ] ) ) {
					$args[ 'session' ] = $atts[ 'session' ];
				}
				else {
					$last_session = "SELECT samesession FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND uid = %s ORDER BY time DESC";
					$args[ 'session' ] = $wpdb->get_var( $wpdb->prepare( $last_session, $args[ 'id' ], $ssuid ) );
				}
				$lastvotes = " AND mspd.samesession = %s ";
			}
			if ( $args[ 'style' ] == 'plain' ) {
				if ( $args[ 'data' ] == 'full' || $args[ 'data' ] == 'full-records' ) {
				if ( $args[ 'data' ] == 'full-records' ) {
					$result = array();
				}
				$sql = "SELECT *, msq.id as question_id FROM " . $wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $wpdb->base_prefix . "modal_survey_questions msq on mss.id = msq.survey_id WHERE mss.id = %d ORDER BY msq.id ASC";
				$q_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ] ) );
				if ( $args[ 'data' ] != 'full-records' ) {
					$result = "<div id='survey-results-" . $args[ 'id' ] . "-" . $unique_key . "' class='ms-plain-results'>";
					if ( $args[ 'printable' ] == "true" ) {
						$result .= '<input type="button" class="ms-print-results button button-secondary" data-id="survey-results-' . $args[ 'id' ] . '-' . $unique_key . '" value="' . esc_html__( 'PRINT', MODAL_SURVEY_TEXT_DOMAIN ) . '" />';
					}
				}
						$finaltimer = 0;
						$finalscore = 0;
						foreach( $q_sql as $key1=>$ars ) {
						//display individual records start
						if ( $args[ 'uid' ] != "false" ) {
							if ( $args[ 'uid' ] == "true" ) {
								if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {
									$args[ 'uid' ] = $_COOKIE[ 'ms-uid' ];
								}
							}
							$samesession = "";
							$prearray = array( $args[ 'id' ], $ars->question_id, $args[ 'uid' ] );
							if ( ! empty( $args[ 'session' ] ) ) {
								$samesession = " AND mspd.samesession = %s";
								$prearray = array( $args[ 'id' ], $ars->question_id, $args[ 'uid' ], $args[ 'session' ] );
							}
							$sql_u = "SELECT mspd.qid, mspd.aid, mspd.timer FROM " . $wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $wpdb->base_prefix . "modal_survey_participants msp on mspd.uid = msp.autoid WHERE mspd.sid = %d AND mspd.qid = %d AND msp.id = %s " . $samesession . " ORDER BY autoid ASC";
							$a_sql_u = $wpdb->get_results( $wpdb->prepare( $sql_u, implode( $prearray ) ) );
							if ( ! empty( $a_sql_u ) ) {
								foreach( $a_sql_u as $key2u=>$asu ) {
									$user_votes[ $asu->qid ][] = $asu->aid;
									$timer[ $asu->qid ] = $asu->timer;
								}
							}
							else {
								if ( $args[ 'alternativedatas' ] == "false" ) {
									return ( "" );
								}
							}
							if ( $args[ 'alternativedatas' ] == "real" ) {
								foreach( $a_sql_u as $fkey=>$fsql ) {
									if ( ! isset( $user_votes[ $fsql->question_id ] ) ) {
										$a_sql_u[ $fkey ]->count = 0;
									}
								}
							}
						}
						if ( $args[ 'data' ] == 'full-records' ) {
						//display individual records end
								if ( $args[ 'pure' ] == "false" ) {
									$result[ $key1 ][ 'title' ] = preg_replace( '/\[.*\]/', '', $ars->question );
								}
								else {
									$result[ $key1 ][ 'title' ] = $ars->question;
								}
									$result[ $key1 ][ 'id' ] = ( $key1 + 1 );
							$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY %s";
							$a_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ], $ars->question_id, $sort ) );
								foreach( $a_sql as $key2=>$as ) {
									$allcount = 0;
									$aoptions = unserialize( $as->aoptions );
									foreach($a_sql as $aas){
										$allcount = $allcount + $aas->count;
									}
									$uv_ans = array();$uv_ans_rec = "";$ans_open = array();
									$selected = "false";
									if ( isset( $user_votes ) ) {
										$thisuv = $user_votes;
										if ( $aoptions[ 0 ] == "open" || $aoptions[ 0 ] == "date" || $aoptions[ 0 ] == "numeric" || $aoptions[ 0 ] == "select" ) {
											if ( isset( $thisuv[ $ars->question_id ] ) ) {
												foreach( $thisuv[ $ars->question_id ] as $key=>$uvarray ) {
													$uv_ans = explode( "|", $uvarray );
													if ( ! in_array( $uv_ans[ 0 ], $thisuv[ $ars->question_id ] ) ) {
														$thisuv[ $ars->question_id ][ $key ] = $uv_ans[ 0 ];
														if ( ! isset( $ans_open[ $uv_ans[ 0 ] ] ) ) {
															$ans_open[ $uv_ans[ 0 ] ] = $uv_ans[ 1 ];
														}
													}
												}
											}
										}
										else {
												if ( isset( $thisuv[ $ars->question_id ] ) ) {
													foreach( $thisuv[ $ars->question_id ] as $key=>$uvarray ) {
														$uv_ans_rec = explode( "|", $uvarray );
														if ( ! in_array( $uv_ans_rec[ 0 ], $thisuv[ $ars->question_id ] ) && isset( $uv_ans_rec[ 1 ] ) ) {
															$thisuv[ $ars->question_id ][ ] = $uv_ans_rec[ 0 ];
														}
													}
												}
										}
										if ( isset( $thisuv[ $ars->question_id ] ) && is_array( $thisuv[ $ars->question_id ] ) && ( in_array( $as->autoid, $thisuv[ $ars->question_id ] ) ) ) {
											$selected = "true";
											if ( isset( $ans_open[ $as->autoid ] ) ) {
												preg_match_all( "/\[([^\]]*)\]/", $as->answer, $acats );
												$as->answer = $ans_open[ $as->autoid ];
												if ( isset( $acats[ 1 ][ 0 ] ) ) {
													$result[ $key1 ][ 'datas' ][ $key2 ][ 'category' ] = $acats[ 1 ][ 0 ];
												}
												else {
													$result[ $key1 ][ 'datas' ][ $key2 ][ 'category' ] = "false";
												}											}
										}
									}
									if ( $args[ 'pure' ] == "false" ) {
										$result[ $key1 ][ 'datas' ][ $key2 ][ 'answer' ] = ( preg_replace( '/\[.*\]/', '', $as->answer ) ? ( preg_replace( '/\[.*\]/', '', $as->answer ) ) : esc_html__( 'Not Specified', MODAL_SURVEY_TEXT_DOMAIN ) );
									}
									else {
										$result[ $key1 ][ 'datas' ][ $key2 ][ 'answer' ] = $as->answer;
									}
									preg_match_all( "/\[([^\]]*)\]/", $as->answer, $acats );
									if ( ! isset( $result[ $key1 ][ 'datas' ][ $key2 ][ 'category' ] ) ) {
										if ( isset( $acats[ 1 ][ 0 ] ) ) {
											$result[ $key1 ][ 'datas' ][ $key2 ][ 'category' ] = $acats[ 1 ][ 0 ];
										}
										else {
											$result[ $key1 ][ 'datas' ][ $key2 ][ 'category' ] = "false";
										}
									}
									$result[ $key1 ][ 'datas' ][ $key2 ][ 'id' ] = ( $key2 + 1 );
									$result[ $key1 ][ 'datas' ][ $key2 ][ 'survey' ] = $q_sql[ 0 ]->name;
									$result[ $key1 ][ 'datas' ][ $key2 ][ 'selected' ] = $selected;
									$result[ $key1 ][ 'datas' ][ $key2 ][ 'votes' ] = $as->count;
									if ( $aoptions[ 0 ] == "numeric" && $aoptions[ 4 ] == 0 && is_numeric( $result[ $key1 ][ 'datas' ][ $key2 ][ 'answer' ] ) ) {
										$result[ $key1 ][ 'datas' ][ $key2 ][ 'score' ] = $result[ $key1 ][ 'datas' ][ $key2 ][ 'answer' ];										
									}
									else {
										$result[ $key1 ][ 'datas' ][ $key2 ][ 'score' ] = $aoptions[ 4 ];
									}
									$result[ $key1 ][ 'datas' ][ $key2 ][ 'correct' ] = ( ! empty( $aoptions[ 5 ] ) ? 'true' : 'false' );
									$result[ $key1 ][ 'datas' ][ $key2 ][ 'status' ] = ( ( ! isset( $aoptions[ 8 ] ) || ( $aoptions[ 8 ] == "1" ) ) ? 'inactive' : 'active' );
									$result[ $key1 ][ 'datas' ][ $key2 ][ 'percentage' ] = ( $allcount > 0 ? ( round( ( $as->count / $allcount ) * 100, 2 ) ) : '0' ) . "%";
								}
						}
						else {
							//display individual records end
							if ( isset( $timer[ $key1 + 1 ] ) && $timer[ $key1 + 1 ] >= 0 ) {
								$finaltimer += $timer[ $key1 + 1 ];
							}
							if ( $atts[ 'titles' ] != '' ) {
								$ctitles = explode( ',', $atts[ 'titles' ] );
								if ( ! empty( $ctitles[ $key1 ] ) ) {
									$ars->question = $ctitles[ $key1 ];
								}
							}
							$result .= "<div class='question-onerow'><div class='ms-question-row'><div class='ms-question-text'>" . $args[ 'title' ] . preg_replace( '/\[.*\]/', '', $ars->question ) . $args[ 'title_c' ] . "</div><div class='ms-question-block1'></div><div class='ms-question-block2'>" . ( isset( $timer[ $key1 + 1 ] ) && $finaltimer > 0  ? ( esc_html__( 'Time Required', MODAL_SURVEY_TEXT_DOMAIN ) . ": ". $timer[ $key1 + 1 ] . esc_html__( 'sec', MODAL_SURVEY_TEXT_DOMAIN ) ) : '' ) . "</div></div>";
								$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY %s";
								$a_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ], $ars->question_id, $sort ) );
								//shortcode extension to get votes by post ID
								if ( $args[ 'postid' ] != '' ) {
									foreach( $a_sql as $aaskey=>$bas ) {
										$a_sql[ $aaskey ]->count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( aid ) FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND qid = %d AND aid = %d AND postid = %d", $args[ 'id' ], $ars->question_id, $bas->autoid, $args[ 'postid' ] ) );
									}
								}
								//start - remove inactive answers
								foreach($a_sql as $aaskey=>$bas){
									$baoptions = unserialize( $bas->aoptions );								
									if ( isset( $baoptions[ 8 ] ) && $baoptions[ 8 ] == "1" ) {
										unset( $a_sql[ $aaskey ] );
									}
								}
								//end - remove inactive answers					
								foreach( $a_sql as $key2=>$as ) {
									$allcount = 0;
									$aoptions = unserialize( $as->aoptions );
									foreach( $a_sql as $aas ) {
										$allcount = $allcount + $aas->count;
									}
									$uv_ans = array();$uv_ans_rec = "";$ans_open = array();
									$selected = "";
									if ( isset( $user_votes ) ) {
										$thisuv = $user_votes;
										if ( $aoptions[ 0 ] == "open" || $aoptions[ 0 ] == "numeric" || $aoptions[ 0 ] == "date" || $aoptions[ 0 ] == "select"  ) {
											if ( isset( $thisuv[ $ars->question_id ] ) ) {
												foreach( $thisuv[ $ars->question_id ] as $key=>$uvarray ) {
													$uv_ans = explode( "|", $uvarray );
													if ( ! in_array( $uv_ans[ 0 ], $thisuv[ $ars->question_id ] ) ) {
														$thisuv[ $ars->question_id ][ $key ] = $uv_ans[ 0 ];
														if ( ! $ans_open[ $uv_ans[ 0 ] ] ) {
															$ans_open[ $uv_ans[ 0 ] ] = $uv_ans[ 1 ];
														}
													}
												}
											}
										}
										else {
											if ( isset( $thisuv[ $ars->question_id ] ) ) {
												foreach( $thisuv[ $ars->question_id ] as $key=>$uvarray ) {
													$uv_ans_rec = explode( "|", $uvarray );
													if ( ! in_array( $uv_ans_rec[ 0 ], $thisuv[ $ars->question_id ] ) && isset( $uv_ans_rec[ 1 ] ) ) {
														$thisuv[ $ars->question_id ][ ] = $uv_ans_rec[ 0 ];
													}
												}
											}
										}
										$selectedstyle = "";
										if ( isset( $thisuv[ $ars->question_id ] ) && is_array( $thisuv[ $ars->question_id ] ) && ( in_array( $as->autoid, $thisuv[ $ars->question_id ] ) ) ) {
											$selected = " ms-answer-row-selected";
											$selectedstyle ="selected-row-style";
											if ( isset( $ans_open[ $as->autoid ] ) ) {
												$as->answer .= ': ' . $ans_open[ $as->autoid ];
											}
											$finalscore += $aoptions[ 4 ];
										}
									}
									$score_output = "";
									if ( $args[ 'score' ] == 'true' ) {
										$score_output = "<div class='ms-answer-score modal_survey_tooltip' title='" . esc_html__( 'Answer Score', MODAL_SURVEY_TEXT_DOMAIN ) . "'>" . $aoptions[ 4 ] . "</div>";
									}
									$checkcorrect = '';										
									if ( $aoptions[ 5 ] == '1' ) {
										$checkcorrect = '<div class="correct-mark modal_survey_tooltip" title="' . esc_html__( 'Correct Answer', MODAL_SURVEY_TEXT_DOMAIN ) . '"><img src="' . plugins_url( '/templates/assets/img/correct-icon.png' , __FILE__ ) . '"></div>';
									}
									$result .= "<div class='ms-answer-row" . $selected . " " . $selectedstyle . "'>" . $checkcorrect . "<div class='ms-answer-text'>" . ( preg_replace( '/\[.*\]/', '', $as->answer ) ? ( preg_replace( '/\[.*\]/', '', $as->answer ) ) : esc_html__( 'Not Specified', MODAL_SURVEY_TEXT_DOMAIN ) ) . "</div><div class='ms-answer-count modal_survey_tooltip' title='" . esc_html__( 'Global Votes', MODAL_SURVEY_TEXT_DOMAIN ) . "'>" . $as->count . "</div><div class='ms-answer-percentage modal_survey_tooltip' title='" . esc_html__( 'Global Percentage', MODAL_SURVEY_TEXT_DOMAIN ) . "'>" . ( $allcount > 0 ? ( round( ( $as->count / $allcount ) * 100, 2 ) ) : '0' ) . "%" . "</div>" . $score_output . "</div>";
								}
								$result .= "</div>";
								if ( $key1 == count( $q_sql ) - 1 ) {
									$ftimerhtml = "<span class='final-time-title'>" . esc_html__( 'Final Time', MODAL_SURVEY_TEXT_DOMAIN ) . ":</span> <span class='final-time'>" . $finaltimer . "" . esc_html__( 'sec', MODAL_SURVEY_TEXT_DOMAIN ) . "</span>";
									$result .= "<div class='final-result'>";
									if ( $finalscore != "" ) {
										$result .= "<span class='final-score-title'>" . esc_html__( 'Total Score', MODAL_SURVEY_TEXT_DOMAIN ) . ":</span> <span class='final-score'>" . $finalscore . "</span> ";
									}
									$result .= ( $finaltimer > 0 ? $ftimerhtml : "" );
									$result .= "</div>";
								}
							}
						}
				}
				if ( $args[ 'data' ] == 'question' ) {
				$sql = "SELECT *, msq.id as question_id FROM " . $wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $wpdb->base_prefix . "modal_survey_questions msq on mss.id = msq.survey_id WHERE mss.id= %d ORDER BY msq.id ASC";
				$q_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ] ) );
					foreach( $q_sql as $key1=>$ars ) {
						if ( ( $key1 + 1 ) == $args[ 'qid' ] ) {
							if ( $atts[ 'init' ] == "true" ) {
								$this->initialize_plugin();
							}
							return(preg_replace('/\[.*\]/', '', $ars->question));
						}
					}
				}
				if ( $args[ 'data' ] == 'answer' || $args[ 'data' ] == 'answer_count' || $args[ 'data' ] == 'answer_percentage' ) {
						if ( $args[ 'aid' ] == '' && $args[ 'uid' ] == "true" ) {
							if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {
								$cmsuid = $_COOKIE[ 'ms-uid' ];
							}
							else {
								$cmsuid = "";
							}
								$fullrecords = modal_survey::survey_answers_shortcodes( 
									array ( 'id' => $args[ 'id' ], 'data' => 'full-records', 'style' => 'plain', 'uid' => $cmsuid, 'pure' => 'true'  )
								);
								$uans = array();$uans_output = "";
								foreach( $fullrecords[ $args[ 'qid' ] - 1 ][ 'datas' ] as $qss ) {
									if ( $qss[ 'selected' ] == "true" ) {
										$uans[] = $qss[ 'answer' ];
									}
								}
								foreach( $uans as $key=>$userans ) {
									$uans_output .= $userans;
									if ( $key + 1 < count( $uans ) ) {
										$uans_output .= ", ";
									}
								}
								return $uans_output;
						}					
						$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY %s";
						$a_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ], $args[ 'qid' ], $sort ) );
						//shortcode extension to get votes by post ID
						if ( $args[ 'postid' ] != '' ) {
							foreach( $a_sql as $aaskey=>$bas ){
								$a_sql[ $aaskey ]->count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( aid ) FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND qid = %d AND aid = %d AND postid = %d", $args[ 'id' ], $ars->question_id, $bas->autoid, $args[ 'postid' ] ) );
							}
						}
						$allcount = 0;
						foreach( $a_sql as $aas ) {
							$allcount = $allcount + $aas->count;
						}
						foreach( $a_sql as $key2 => $as ) {
							if ( ( ( $key2 + 1 ) == $args[ 'aid' ] ) && ( ! empty( $args[ 'aid' ] ) ) ) {
								if ( $args[ 'data' ] == 'answer' ) {
									if ( $atts[ 'init' ] == "true" ) {
										$this->initialize_plugin();
									}
									return( preg_replace( '/\[.*\]/', '', $as->answer ) );
								}
								if ( $args[ 'data' ] == 'answer_count' ) {
									if ( $atts[ 'init' ] == "true" ) {
										$this->initialize_plugin();
									}
									return( $as->count );
								}
								if ( $args[ 'data' ] == 'answer_percentage' ) {
									if ( $allcount > 0 ) {
										if ( $atts[ 'init' ] == "true" ) {
											$this->initialize_plugin();
										}
										return( round( ( $as->count / $allcount ) * 100, 2 ) . '%' );
									}
									else {
										if ( $atts[ 'init' ] == "true" ) {
											$this->initialize_plugin();
										}
										return( '0%' );
									}
								}
							}
						}
						if ( $args[ 'data' ] == 'answer_count' ) {
							if ( $atts[ 'init' ] == "true" ) {
								$this->initialize_plugin();
							}
							return( $allcount );
						}
				}
				if ( $args[ 'data' ] == 'score' || $args[ 'data' ] == 'average-score' || $args[ 'data' ] == 'rating' ) {
					$totalsumscore = 0;
					$sql = "SELECT *,msq.id as question_id, msq.qoptions FROM " . $wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $wpdb->base_prefix . "modal_survey_questions msq on mss.id = msq.survey_id WHERE mss.id = %d ORDER BY msq.id ASC";
					$q_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ] ) );
					foreach( $q_sql as $key1 => $ars ) {
						$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY %s";
						$a_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ], $ars->question_id, $sort ) );
						//shortcode extension to get votes by post ID
						if ( $args[ 'postid' ] != '' ) {
							foreach($a_sql as $aaskey=>$bas){
								$a_sql[ $aaskey ]->count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( aid ) FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND qid = %d AND aid = %d AND postid = %d", $args[ 'id' ], $ars->question_id, $bas->autoid, $args[ 'postid' ] ) );
							}
						}
						//display individual records start
						if ( $args[ 'uid' ] != "false" ) {
							if ( $args[ 'uid' ] == "true" ) {
								if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {
									$args[ 'uid' ] = $_COOKIE[ 'ms-uid' ];
								}
							}
							if ( is_numeric( $args[ 'uid' ] ) ) {
								$args[ 'uid' ] = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE autoid = %d ", $args[ 'uid' ] ) );
							}
							$preparray = array( $args[ 'id' ], $ars->question_id, $args[ 'uid' ] );
							if ( ! empty( $lastvotes ) ) {
								$preparray = array( $args[ 'id' ], $ars->question_id, $args[ 'uid' ], $args[ 'session' ] );
							}
							$sql_u = "SELECT mspd.qid, mspd.aid FROM " . $wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $wpdb->base_prefix . "modal_survey_participants msp on mspd.uid = msp.autoid WHERE mspd.sid = %d AND mspd.qid = %d AND msp.id = %s " . $lastvotes . " ORDER BY autoid ASC";
							$a_sql_u = $wpdb->get_results( $wpdb->prepare( $sql_u, implode( $preparray ) ) );
							if ( ! empty( $a_sql_u ) ) {
								foreach( $a_sql_u as $key2u=>$asu ) {
									$uv_ans = explode( "|", $asu->aid );
									$user_votes[ $asu->qid ][] = $uv_ans[ 0 ];
									$user_votes_values[ $asu->qid ][] = $uv_ans[ 1 ];
								}
								foreach( $a_sql as $key2o=>$aso ) {
									if ( isset( $user_votes[ $aso->question_id ] ) && ( in_array( $aso->autoid, $user_votes[ $aso->question_id ] ) ) ) {
										$a_sql[ $key2o ]->count = 1;
									}
									else {
										$a_sql[ $key2o ]->count = 0;										
									}
								}
							}
							else {
								if ( $args[ 'alternativedatas' ] == "false" ) {
									return ( "" );
								}
							}
							if ( $args[ 'alternativedatas' ] == "real" ) {
								foreach( $a_sql_u as $fkey=>$fsql ) {
									if ( isset( $fsql->question_id ) ) {
										if ( ! isset( $user_votes[ $fsql->question_id ] ) ) {
											$a_sql_u[ $fkey ]->count = 0;
										}
									}
								}
							}
						}
						//display individual records end
						//start - remove inactive answers
						foreach($a_sql as $aaskey=>$bas){
							$baoptions = unserialize( $bas->aoptions );								
							if ( isset( $baoptions[ 8 ] ) && $baoptions[ 8 ] == "1" ) {
								unset( $a_sql[ $aaskey ] );
							}
						}
						//end - remove inactive answers					
						$qscore = 0;
						$summary = 0; $allratings = 0;
						foreach( $a_sql as $key2=>$as ) {
							if ( isset( $as->aoptions ) ) {
								$aoptions = unserialize( $as->aoptions );
								if ( $aoptions[ 0 ] == "numeric" && $aoptions[ 4 ] == 0 && is_numeric( $user_votes_values[ $key1 + 1 ][ 0 ] ) ) {
									$aoptions[ 4 ] += $user_votes_values[ $key1 + 1 ][ 0 ];
								}
								if ( is_numeric( $aoptions[ 4 ] ) ) {
									preg_match_all( "/\[([^\]]*)\]/", $as->answer, $acats );
									if ( ! empty( $acats[ 1 ] ) ) {
										$acats_list = explode( ",", $acats[ 1 ][ 0 ] );
										foreach ( $acats_list as $aca ) {
											if ( isset( $aca ) ) {
												if ( ! empty( $aca ) && ! is_numeric( $aca )  ) {
													if ( ! isset( $answercats[ trim( $aca ) ] ) ) {
														$answercats[ trim( $aca ) ] = 0;
													}
													if ( ! isset( $answercats_counts[ trim( $aca ) ] ) ) {
														$answercats_counts[ trim( $aca ) ] = 0;
													}
													if ( isset( $answercats[ trim( $aca ) ] ) ) {
														$answercats[ trim( $aca ) ] += $aoptions[ 4 ] * $as->count;
													}
													if ( isset( $answercats_counts[ trim( $aca ) ] ) ) {
														$answercats_counts[ trim( $aca ) ] += $as->count;
													}
												}
											}
										}
									}
								}
								if ( $aoptions[ 0 ] == "open" || $aoptions[ 0 ] == "date" || $aoptions[ 0 ] == "numeric" || $aoptions[ 0 ] == "select" ) {
									$as->answer = esc_html__( 'Other', MODAL_SURVEY_TEXT_DOMAIN );
								}
								if ( ! empty( $aoptions[ 3 ] ) ) {
								//including image to the answer: $as->answer = '<img src="' . $aoptions[ 3 ] . '">' . $as->answer;
								}
							}
							else {
								$aoptions[ 4 ] = 0;
							}
							if ( isset( $args[ 'titles' ] ) && $args[ 'titles' ] != "" ) {
								$titles = explode( ",", $args[ 'titles' ] );
							}
							else {
								$titles = array();
							}
							if ( ! isset( $titles[ $key1 ] ) || empty( $titles[ $key1 ] ) || $titles[ $key1 ] == "" ) {
								$titles[ $key1 ] = nl2br( $ars->question );
							}
							if ( $args[ 'data' ] == 'score' || $args[ 'data' ] == 'average-score' ) {
							if ( isset( $args[ 'titles' ] ) && $args[ 'titles' ] != "" ) {
								$titles = explode( ",", $args[ 'titles' ] );
							}
							else {
								$titles = array();
							}
							if ( ! isset( $titles[ $key1 ] ) || empty( $titles[ $key1 ] ) || $titles[ $key1 ] == "" ) {
								$titles[ $key1 ] = nl2br( $ars->question );
							}
							if ( isset( $aoptions[ 4 ] ) && is_numeric( $aoptions[ 4 ] ) ) {
									if ( ! isset( $user_votes[ $key1 + 1 ] ) && $atts[ 'uid' ] != "false" ) {
										$qscore += 0;
									}
									else {
										$qscore += $as->count * $aoptions[ 4 ];
									}
								}
								else {
									$qscore += 0;								
								}
							}
							if ( $args[ 'data' ] == 'rating' ) {
							$summary += ( $key2 + 1 ) * $as->count;
							$allratings += $as->count;
							}
						}
						if ( $args[ 'data' ] == 'rating' ) {
							if ( $allratings == 0 ) {
								$exactvalue =  0;
								$decvalue = 0;
								$intvalue = 0;
							}
							else {
								$exactvalue =  ( $summary / $allratings );
								$decvalue = ceil( ( $summary / $allratings ) * 2 ) / 2;
								$intvalue = ( int ) $decvalue;
							}
							$allans_count = count( $a_sql ) - $intvalue;
							$qscore = number_format( $exactvalue, $atts[ 'decimal' ], '.', '' );
						}
						preg_match_all( "/\[([^\]]*)\]/", $titles[ $key1 ], $ques );
						if ( isset( $ques[ 1 ] ) && ! empty( $ques[ 1 ] ) ) {
							if ( ! empty( $ques[ 1 ] ) ) {
								foreach( $ques[ 1 ] as $perscat ) {
									$titles[ $key1 ] = str_replace( $perscat, "", $titles[ $key1 ] );
									if ( ! empty( $perscat ) ) {
										$titles[ $key1 ] = str_replace( array( "[", "]" ), "", trim( $perscat ) );
									}
								}
							}
						}
						$valexist = 0;
						if ( ! empty( $sdatas[ 0 ] ) ) {
								foreach ( $sdatas[ 0 ] as $qstkey=>$qst ) {
									if ( $qst[ 'answer' ] == $titles[ $key1 ] ) {
										 if ( $args[ 'data' ] == 'average-score' ) {
											$allcount = 0;
											foreach($a_sql as $aas){
												$allcount = $allcount + $aas->count;
											}
											if ( $allcount > 0 ) {
												$qscore = number_format( $qscore / $allcount, $atts[ 'decimal' ], '.', '' );
											}
											else {
												$qscore = 0;
											}
										 }
										$sdatas[ 0 ][ $qstkey ][ 'count' ] = $sdatas[ 0 ][ $qstkey ][ 'count' ] + $qscore;
										$valexist = 1;
									}
								}
						}
						if ( $valexist == 0 ) {
							if ( strlen( $titles[ $key1 ] ) > 50 ) {
								$titles[ $key1 ] = substr( $titles[ $key1 ], 0, 50 ) . "...";
							}
							if ( $titles[ $key1 ] != "-" ) {
								 if ( $args[ 'data' ] == 'average-score' ) {
									$allcount = 0;
									foreach($a_sql as $aas){
										$allcount = $allcount + $aas->count;
									}
									if ( $allcount > 0 ) {
										$qscore = number_format( $qscore / $allcount, $atts[ 'decimal' ], '.', '' );
									}
									else {
										$qscore = 0;
									}
								 }
								$sdatas[ 0 ][ $key1 ] = array( 'answer' => $titles[ $key1 ], 'count'=> $qscore );
							}
						}
					}
					if ( ! empty( $answercats ) && ( $args[ 'data' ] == 'score' || $args[ 'data' ] == 'average-score' || $args[ 'data' ] == 'rating' ) ) {
						foreach( $answercats as $ackey=>$ac ) {
							if ( ! empty( $ackey ) && ! in_array( $ackey, $already_added ) ) {
								if ( $args[ 'data' ] == 'average-score' ) {
									if ( isset( $answercats_counts[ $ackey ] ) && $answercats_counts[ $ackey ] > 0 ) {
										$sdatas[ 0 ][] = array( 'answer' => $ackey, 'count'=> round( $ac / $answercats_counts[ $ackey ], 2 ) );
									}
								}
								else {
									$sdatas[ 0 ][] = array( 'answer' => $ackey, 'count'=> $ac );
								}
								$already_added [] = $ackey;
							}
						}
					}
					if ( $args[ 'qid' ] == "" && $args[ 'aid' ] == "" && ! empty( $sdatas ) ) {
						foreach( $sdatas[ 0 ] as $sd ) {
							if ( $aoptions[ 0 ] == "numeric" && $aoptions[ 4 ] == 0 && is_numeric( $user_votes_values[ $key1 + 1 ][ 0 ] ) ) {
								$totalsumscore += $user_votes_values[ $key1 + 1 ][ 0 ];
							}
							else {
								$totalsumscore += $sd[ 'count' ];
							}
						}
						if ( $atts[ 'init' ] == "true" ) {
							$this->initialize_plugin();
						}
						if ( ! empty( $args[ 'max' ] ) ) {
							if ( $args[ 'progress' ] == "true" ) {
								$additional_params = "";
								if ( $args[ 'bgcolor' ] ) {
									$bgcls = explode( ",", $args[ 'bgcolor' ] );
									if ( isset( $bgcls[ 0 ] ) ) {
										$additional_params .= ' data-foregroundColor="' . $bgcls[ 0 ] . '"';
									}
									if ( isset( $bgcls[ 1 ] ) ) {
										$additional_params .= ' data-backgroundColor="' . $bgcls[ 1 ] . '"';
									}
									if ( isset( $bgcls[ 2 ] ) ) {
										$additional_params .= ' data-targetColor="' . $bgcls[ 2 ] . '"';
									}
									if ( isset( $bgcls[ 3 ] ) ) {
										$additional_params .= ' data-fontColor="' . $bgcls[ 3 ] . '"';
									}
								}
								return ( '<div id="ms-progress-circle' . $args[ 'id' ] . '" class="modalsurvey-progress-circle" data-animation="1" ' . $additional_params . ' data-animationStep="5" data-percent="' . ( intval( ( $totalsumscore / $args[ 'max' ] ) * 100 ) ) . '"></div>' );
							}
							else {
								return ( intval( ( $totalsumscore / $args[ 'max' ] ) * 100 ) );
							}
						}
						if ( empty( $totalsumscore ) ) {
							$totalsumscore = 0;
						}
						return ( $totalsumscore );
					}
					else {
						if ( empty( $args[ 'qid' ] ) ) {
							return 0;
						}
					}
					if ( $args[ 'qid' ] != "" && $args[ 'aid' ] == "" ) {
						if ( ! is_numeric( $args[ 'qid' ] ) ) {
							if ( $args[ 'uid' ] != "false" ) {
								$fullrecords = modal_survey::survey_answers_shortcodes( 
									array ( 'id' => $args[ 'id' ], 'data' => 'full-records', 'style' => 'plain', 'uid' => $args[ 'uid' ] , 'pure' => 'true', 'session' => $atts[ 'session' ] )
								);
								foreach( $fullrecords as $fr ) {
									preg_match_all( "/\[([^\]]*)\]/", $fr[ 'title' ], $cat );
									foreach( $fr[ 'datas' ] as $frd ) {
										if ( $aoptions[ 0 ] == "numeric" && $aoptions[ 4 ] == 0 && is_numeric( $frd[ 'answer' ] ) ) {
											$frd[ 'score' ] = $frd[ 'answer' ];
										}
										preg_match_all( "/\[([^\]]*)\]/", $frd[ 'answer' ], $acat );
										if ( ! isset( $acat[ 1 ][ 0 ] ) && ! empty( $frd[ 'category' ] )) {
											$acat[ 1 ][ 0 ] = $frd[ 'category' ];
										}
										if ( isset( $acat[ 1 ][ 0 ] ) ) {
											$acat_list = explode( ",", $acat[ 1 ][ 0 ] );
											foreach ( $acat_list as $acal ) {
												if ( isset( $acal ) ) {
													if ( ! empty( $acal ) && ! is_numeric( $acal ) && $frd[ 'selected' ] == "true" && $frd[ 'score' ] ) {
														if ( ! isset( $cats[ trim( $acal ) ] ) ) {
															$cats[ trim( $acal ) ] = 0;
														}
														if ( ! isset( $cats_count[ trim( $acal ) ] ) ) {
															$cats_count[ trim( $acal ) ] = 0;
														}
														if ( isset( $cats[ trim( $acal ) ] ) ) {
															$cats[ trim( $acal ) ] += $frd[ 'score' ];
														}
														if ( isset( $cats_count[ trim( $acal ) ] ) ) {
															$cats_count[ trim( $acal ) ]++;
														}
													}
												}
											}
										}
									}
								}
								if ( isset( $cats[ $args[ 'qid' ] ] ) ) {
									if ( $args[ 'data' ] == 'average-score' ) {
										$totalsumscore = round( $cats[ $args[ 'qid' ] ] / $cats_count[ $args[ 'qid' ] ], 2 );
									}
									else {					
										$totalsumscore = $cats[ $args[ 'qid' ] ];
									}
								}
							}
							else {
								if ( ! is_numeric( $qid ) && $args[ 'data' ] == "score" ) {
									$get_results = modal_survey::survey_answers_shortcodes(
														array ( 'id' => $args[ 'id' ], 'data' => 'score', 'style' => 'radarchart', 'pure' => 'true' )
														);
									foreach( $get_results[ 0 ] as $gs ) {
										if ( $gs[ 'answer' ] == $args[ 'qid' ] ) {
											return $gs[ 'count' ];
										}
									}
								}
								if ( ! is_numeric( $qid ) && $args[ 'data' ] == "average-score" ) {
									$get_results = modal_survey::survey_answers_shortcodes(
														array ( 'id' => $args[ 'id' ], 'data' => 'average-score', 'style' => 'radarchart', 'pure' => 'true' )
														);
									foreach( $get_results[ 0 ] as $gs ) {
										if ( $gs[ 'answer' ] == $args[ 'qid' ] ) {
											return $gs[ 'count' ];
										}
									}
								}
								if ( $args[ 'alternativedatas' ] == "false" ) {
									return ( "" );
								}
							}
						}
						else {
							$fullrecords = modal_survey::survey_answers_shortcodes( 
								array ( 'id' => $args[ 'id' ], 'data' => 'full-records', 'style' => 'plain', 'uid' => $args[ 'uid' ] , 'pure' => 'true', 'session' => $atts[ 'session' ] )
							);
							$totalsumscore = 0;
							if ( ! empty( $fullrecords[ $qid - 1 ] ) ) {
								foreach( $fullrecords[ $qid - 1 ][ 'datas' ] as $sdkey => $sd ) {
									if ( $sd[ 'selected' ] == 'true' ) {
										$totalsumscore += $sd[ 'score' ];
									}
								}
							}
							if ( $atts[ 'init' ] == "true" ) {
								$this->initialize_plugin();
							}
						}
						if ( ! empty( $args[ 'max' ] ) ) {
							if ( $args[ 'progress' ] == "true" ) {
								$additional_params = "";
								if ( $args[ 'bgcolor' ] ) {
									$bgcls = explode( ",", $args[ 'bgcolor' ] );
									if ( isset( $bgcls[ 0 ] ) ) {
										$additional_params .= ' data-foregroundColor="' . $bgcls[ 0 ] . '"';
									}
									if ( isset( $bgcls[ 1 ] ) ) {
										$additional_params .= ' data-backgroundColor="' . $bgcls[ 1 ] . '"';
									}
									if ( isset( $bgcls[ 2 ] ) ) {
										$additional_params .= ' data-targetColor="' . $bgcls[ 2 ] . '"';
									}
									if ( isset( $bgcls[ 3 ] ) ) {
										$additional_params .= ' data-fontColor="' . $bgcls[ 3 ] . '"';
									}
								}
								return ( '<div id="ms-progress-circle' . $args[ 'id' ] . '" class="modalsurvey-progress-circle" data-animation="1" ' . $additional_params . ' data-animationStep="5" data-percent="' . ( intval( ( $totalsumscore / $args[ 'max' ] ) * 100 ) ) . '"></div>' );
							}
							else {
								return ( intval( ( $totalsumscore / $args[ 'max' ] ) * 100 ) );
							}
						}
						return ( $totalsumscore );
					}
				}
			}
			if ( $args[ 'style' ] == 'progressbar' || $args[ 'style' ] == 'linebar' ) {
				$msplugininit_answer_array[ $args[ 'id' ] . '-' . $unique_key ] = array(
					"style" => array(
						"printable" => $args[ 'printable' ],
						"style" => $args[ 'style' ],
						"max" => $args[ 'max' ],
						"bgcolor" => $args[ 'bgcolor' ]
						)
					);
				$result = '<div id="survey-results-' . $args[ 'id' ] . '-' . $unique_key . '" class="survey-results">';
				if ( $args[ 'printable' ] == "true" ) {
					$result .= '<input type="button" class="ms-print-results button button-secondary" data-id="survey-results-' . $args[ 'id' ] . '-' . $unique_key . '" value="' . esc_html__( 'PRINT', MODAL_SURVEY_TEXT_DOMAIN ) . '" />';
				}
				if ( $args[ 'data' ] == "score" || $args[ 'data' ] == "avegare-score" ) {
					if ( $args[ 'session' ] != "all" ) {
						$args[ 'session' ] == "last";
					}
					$customdata = modal_survey::survey_answers_shortcodes( 
						array ( 'id' => $args[ 'id' ], 'data' => $args[ 'data' ], 'style' => 'barchart', 'uid' => $args[ 'uid' ], 'pure' => 'true', 'sort' => $args[ 'sort' ], 'top' => $args[ 'top' ], 'filter' => $args[ 'filter' ], 'session' => $args[ 'session' ]  )
					);
					$allcount = 0;
					foreach( $customdata[ 0 ] as $key=>$cd  ) {
						$allcount += $cd[ 'count' ];
					}
					foreach( $customdata[ 0 ] as $key=>$cd  ) {
						if ( $args[ 'hidecounter' ] == 'no' ) {
							$counter = '<span class="process_text"></span> <span class="badge badge-info right">' . $cd[ 'count' ] . ' / ' . $allcount . '</span></p>';
						}
						else {
							$counter = '<span class="process_text"></span> <span class="badge badge-info right"></span></p>';
						}
						if ( $args[ 'bgcolor' ] == "random" ) {
							$bgcolor = $this->random_color();
						}
						else {
							$bgcolor = $args[ 'bgcolor' ];
						}
						if ( $args[ 'color' ] == "random" ) {
							$color = $this->random_color();
						}
						else {
							$color = $args[ 'color' ];
						}
						if ( $allcount == 0 ) {
							$acr = '0';
						}
						else {
							$acr = round( ( $cd[ 'count' ] / $allcount ) * 100, 2 );
						}
						if ( $args[ 'style' ] == 'progressbar' ) {
							$result .= '<div class="process"><p><strong>' . $cd[ 'answer' ] . '</strong> ' . $counter . ' <input type="hidden" class="hiddenperc" value="' . $acr . '" /><div class="progress progress-info progress-striped"><div class="bar survey_global_percent" style="background-color:' . $bgcolor . ';color:' . $color . ';">' . $acr . '%</div></div>';
						}
						if ( $args[ 'style' ] == 'linebar' ) {
							$result .= '<div class="lineprocess"><p><strong>' . $cd[ 'answer' ] . '</strong> ' . $counter . ' <input type="hidden" value="' . $acr . '" class="hiddenperc" /><div class="lineprogress progress-info progress-striped"><div class="bar survey_global_percent" style="background-color:' . $bgcolor . ';color:' . $color . ';"></div><div class="perc" id="survey_perc">0%</div></div>';
						}
					}

				}
				else {
					$sql = "SELECT *,msq.id as question_id, msq.qoptions FROM " . $wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $wpdb->base_prefix . "modal_survey_questions msq on mss.id = msq.survey_id WHERE mss.id = %d ORDER BY msq.id ASC";
					$q_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ] ) );		
					foreach( $q_sql as $key1 => $ars ) {
						$qoptions = unserialize( $ars->qoptions );
						if ( ( $args[ 'data' ] == 'full' || ( ( $key1 + 1 ) == $args[ 'qid' ] ) ) ) {
							preg_match( '/\[.*\]/', $ars->question, $ques );
							if ( ! empty( $ques ) ) {
								$ars->question = str_replace( $ques[ 0 ], "", $ars->question );
							}
						if ( $args[ 'hidequestion' ] == 'no' ) {
							$result .= $args[ 'title' ] . nl2br( $ars->question ) . $args[ 'title_c' ];
						}
						if ( $args[ 'data' ] == 'question' ) {
							$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY %s";
							$a_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ], $args[ 'qid' ], $sort ) );
						}
						else {
							$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY %s";
							$a_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ], $ars->question_id, $sort ) );
						}
						//shortcode extension to get votes by post ID
						if ( $args[ 'postid' ] != '' ) {
							foreach($a_sql as $aaskey=>$bas){
								$a_sql[ $aaskey ]->count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( aid ) FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND qid = %d AND aid = %d AND postid = %d", $args[ 'id' ], $ars->question_id, $bas->autoid, $args[ 'postid' ] ) );
							}
						}
						//start - remove inactive answers
						foreach($a_sql as $aaskey=>$bas){
							$baoptions = unserialize( $bas->aoptions );								
							if ( isset( $baoptions[ 8 ] ) && $baoptions[ 8 ] == "1" ) {
								unset( $a_sql[ $aaskey ] );
							}
						}
						//end - remove inactive answers
							if ( isset( $qoptions[ 3 ] ) && ( $qoptions[ 3 ] == 1 ) ) {
								$summary = 0; $allratings = 0;
								$tooltip = array();
								foreach( $a_sql as $key2=>$as ) {
									$summary += ( $key2 + 1 ) * $as->count;
									$allratings += $as->count;
										if ( preg_replace( '/\[.*\]/', '', $as->answer ) == "" ) {
											$tooltip[ $key2 + 1 ] = '';
										}
										else {
											$tooltip[ $key2 + 1 ] = ' data-tooltip="' . preg_replace('/\[.*\]/', '', $as->answer) . '"';
										}
								}
								if ( $allratings == 0 ) {
									$exactvalue =  0;
									$decvalue = 0;
									$intvalue = 0;
								}
								else {
									$exactvalue =  ( $summary / $allratings );
									$decvalue = ceil( ( $summary / $allratings ) * 2 ) / 2;
									$intvalue = ( int ) $decvalue;
								}
								$allans_count = count( $a_sql ) - $intvalue;
								$output = "<div class='question_rating_output'>";
								for ( $x = 1; $x <= $intvalue; $x++ ) {
									$output .= '<span ' . $tooltip[ $x ] . '><img class="rating_output" src="'.plugins_url( "/templates/assets/img/star-icon.png" , __FILE__ ).'"></span>'; 
								}
								if ( $decvalue > $intvalue ) {
									$output .= '<span ' . $tooltip[ $x  ] . '><img class="rating_output" src="'.plugins_url( "/templates/assets/img/star-icon-half.png" , __FILE__ ).'"></span>';
									$allans_count--;
									$x++;
								}
								for ( $y = 1; $y <= $allans_count; $y++ ) {
									$output .= '<span ' . $tooltip[ $y + $x - 1 ] . '><img class="rating_output" src="'.plugins_url( "/templates/assets/img/star-icon-empty.png" , __FILE__ ).'"></span>'; 
								}
								if ( $args[ 'hidecounter' ] == 'no' ) {
									$output .= "<span class='ms_ratingvalue'>" . number_format( $exactvalue, $atts[ 'decimal' ], '.', '' ) . " / " . $allratings . " " . esc_html__( 'votes', MODAL_SURVEY_TEXT_DOMAIN ) . "</span>";
								}
								$output .= "</div>";
								$result .= $output;
							}
							else {
								foreach( $a_sql as $key2 => $as ) {
									$aoptions = unserialize( $as->aoptions );
									if ( $aoptions[ 0 ] == "open" || $aoptions[ 0 ] == "date" || $aoptions[ 0 ] == "numeric" || $aoptions[ 0 ] == "select" ) {
										$as->answer = esc_html__( 'Other', MODAL_SURVEY_TEXT_DOMAIN );
									}
									if ( ! empty( $aoptions[ 3 ] ) ) {
										$as->answer = '<img src="' . $aoptions[ 3 ] . '">' . $as->answer;
									}
									$allcount = 0;
									foreach( $a_sql as $aas ) {
										$allcount = $allcount + $aas->count;
									}
									if ( $allcount == 0 ) {
										$acr = '0';
									}
									else {
										$acr = round( ( $as->count / $allcount ) * 100, 2 );
									}
									if ( $args[ 'data' ] == 'full' || ( ( $key1 + 1 ) == $args[ 'qid' ] || $args[ 'data' ] == 'question' ) ) {
										if ( ( is_numeric( $args[ 'aid' ] ) && ( ( $key2 + 1 ) == $args[ 'aid' ] ) ) || ( ! is_numeric( $args[ 'aid' ] ) || $args[ 'aid' ] == '' ) ) {
											if ( $args[ 'hidecounter' ] == 'no' ) {
												$counter = '<span class="process_text"></span> <span class="badge badge-info right">' . $as->count . ' / ' . $allcount . '</span></p>';
											}
											else {
												$counter = '<span class="process_text"></span> <span class="badge badge-info right"></span></p>';
											}
											if ( $args[ 'bgcolor' ] == "random" ) {
												$bgcolor = $this->random_color();
											}
											else {
												$bgcolor = $args[ 'bgcolor' ];
											}
											if ( $args[ 'color' ] == "random" ) {
												$color = $this->random_color();
											}
											else {
												$color = $args[ 'color' ];
											}
											if ( $aoptions[ 10 ] != "" && $args[ 'tooltip' ] == "true" ) {
												$atooltip = 'data-tooltip="' . $aoptions[ 10 ] . '"';
											}
											else {
												$atooltip = "";
											}
											if ( $args[ 'style' ] == 'progressbar' ) {
												if ( ( $args[ 'filter' ] != '' && in_array( strtolower( $as->answer ), $catsfilter ) ) || ( $args[ 'filter' ] == '' ) ) {
													$result .= '<div class="process"><p><strong ' .$atooltip. '>' . preg_replace( '/\[.*\]/', '', $as->answer ) . '</strong> ' . $counter . ' <input type="hidden" class="hiddenperc" value="' . $acr . '" /><div class="progress progress-info progress-striped"><div class="bar survey_global_percent" style="background-color:' . $bgcolor . ';color:' . $color . ';">' . $acr . '%</div>';
												}
											}
											if ( $args[ 'style' ] == 'linebar' ) {
												if ( ( $args[ 'filter' ] != '' && in_array( strtolower( $as->answer ), $catsfilter ) ) || ( $args[ 'filter' ] == '' ) ) {
													$result .= '<div class="lineprocess"><p><strong ' .$atooltip. '>' . preg_replace( '/\[.*\]/', '', $as->answer ) . '</strong> ' . $counter . ' <input type="hidden" value="' . $acr . '" class="hiddenperc" /><div class="lineprogress progress-info progress-striped"><div class="bar survey_global_percent" style="background-color:' . $bgcolor . ';color:' . $color . ';"></div><div class="perc" id="survey_perc">0%</div>';
												}
											}
											if ( ( $args[ 'filter' ] != '' && in_array( strtolower( $as->answer ), $catsfilter ) ) || ( $args[ 'filter' ] == '' ) ) {
												$result .= '</div></div>';
											}
										}
										if ( ( $key2 + 1 ) == $args[ 'aid' ] ) {
											return false; //replaces break; PHP7+
										}
									}
								}
								if ( ( $key1 + 1 ) == $args[ 'qid' ] && $args[ 'data' ] != 'full' ) {
									//apply filter to remove items from the chart
									if ( $args[ 'filter' ] != '' ) {
										$sdatas = modal_survey::filter_result( $args[ 'filter' ], $sdatas );
									}
									$msplugininit_answer_array[ $args[ 'id' ] . '-' . $unique_key ] = array( 
										"printable" => $args[ 'printable' ],
										"style" => array( 
											"style" => $args[ 'style' ],
											"max" => $args[ 'max' ],
											"bgcolor" => $args[ 'bgcolor' ],
											"cbgcolor" => $args[ 'cbgcolor' ],
											"legend" => $args[ 'legend' ]
											),
										"datas" => $sdatas
									);
									$result .= '</div>';
									if ( $atts[ 'init' ] == "true" ) {
										$this->initialize_plugin();
									}
									return( $result );
								}
							}
						}
					}
					//apply filter to remove items from the chart
					if ( $args[ 'filter' ] != '' ) {
						$sdatas = modal_survey::filter_result( $args[ 'filter' ], $sdatas );
					}
			}
			$msplugininit_answer_array[ $args['id'] . '-' . $unique_key ] = array(
					"printable" => $args[ 'printable' ],
					"style" => array(
						"style" => $args[ 'style' ],
						"max" => $args[ 'max' ],
						"bgcolor" => $args[ 'bgcolor' ],
						"cbgcolor" => $args[ 'cbgcolor' ]
						),
					"datas" => $sdatas
				);
				$result .= '</div>';
				if ( isset( $_REQUEST[ 'sspcmd' ] ) && $_REQUEST[ 'sspcmd' ] == "displaychart" ) {
					$result .= "|endcontent-params|" . json_encode( $msplugininit_answer_array[ $args['id'] . '-' . $unique_key ] );
				}
				if ( $atts[ 'init' ] == "true" ) {
					$this->initialize_plugin();
				}
				return($result);
			}
			if ( $args[ 'style' ] == 'piechart' || $args[ 'style' ] == 'barchart' || $args[ 'style' ] == 'horizontalbarchart' || $args[ 'style' ] == 'doughnutchart' || $args[ 'style' ] == 'linechart' || $args[ 'style' ] == 'polarchart' || $args[ 'style' ] == 'radarchart' ) {
				$result = '<div id="survey-results-' . $args[ 'id' ] . '-' . $unique_key . '" class="survey-results">';
				if ( $args[ 'printable' ] == "true" ) {
					$result .= '<input type="button" class="ms-print-results button button-secondary" data-id="survey-results-' . $args[ 'id' ] . '-' . $unique_key . '" value="' . esc_html__( 'PRINT', MODAL_SURVEY_TEXT_DOMAIN ) . '" />';
				}
				$sql = "SELECT *,msq.id as question_id, msq.qoptions FROM " . $wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $wpdb->base_prefix . "modal_survey_questions msq on mss.id = msq.survey_id WHERE mss.id = %d ORDER BY msq.id ASC";
				$q_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ] ) );
				foreach( $q_sql as $key1 => $ars ) {
					if ( $args[ 'data' ] == 'full' || ( ( $key1 + 1 ) == $args[ 'qid' ] ) ) {
					$result .= '<div class="modal-survey-chart' . $key1 . ' ms-chart">';
						preg_match( '/\[.*\]/', $ars->question, $ques );
						if ( ! empty( $ques ) ) {
							$ars->question = str_replace( $ques[ 0 ], "", $ars->question );
						}
						if ( $args[ 'hidequestion' ] == 'no' ) {
							$result .= $args[ 'title' ] . nl2br( $ars->question ) . $args[ 'title_c' ];			
						}
						$result .= '<div class="legendDiv"></div><canvas></canvas></div>';
						if ($args['data']=='question') {
							$ars->question_id = $args[ 'qid' ];
						}			
						$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY %s";								
						$a_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ], $ars->question_id, $sort ) );
						//shortcode extension to get votes by post ID
						if ( $args[ 'postid' ] != '' ) {
							foreach($a_sql as $aaskey=>$bas){
								$a_sql[ $aaskey ]->count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( aid ) FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND qid = %d AND aid = %d AND postid = %d", $args[ 'id' ], $ars->question_id, $bas->autoid, $args[ 'postid' ] ) );
							}
						}
						//start - remove inactive answers
						foreach($a_sql as $aaskey=>$bas){
							$baoptions = unserialize( $bas->aoptions );								
							if ( isset( $baoptions[ 8 ] ) && $baoptions[ 8 ] == "1" ) {
								unset( $a_sql[ $aaskey ] );
							}
						}
						//end - remove inactive answers		
						foreach( $a_sql as $key2=>$as ) {
							if ( isset( $as->aoptions ) ) {
								$aoptions = unserialize( $as->aoptions );								
								if ( is_numeric( $aoptions[ 4 ] ) ) {
									preg_match_all( "/\[([^\]]*)\]/", $as->answer, $acats );
									if ( isset( $acats[ 1 ][ 0 ] ) ) {
										$acats_list = explode( ",", $acats[ 1 ][ 0 ] );
										foreach ( $acats_list as $aca ) {
											if ( isset( $aca ) ) {
												if ( ! empty( $aca ) && ! is_numeric( $aca )  ) {
													if ( ! isset( $answercats[ trim( $aca ) ] ) ) {
														$answercats[ trim( $aca ) ] = 0;
													}
													if ( isset( $answercats[ trim( $aca ) ] ) ) {
														$answercats[ trim( $aca ) ] += $aoptions[ 4 ] * $as->count;
													}
													if ( ! isset( $answercats_counts[ trim( $aca ) ] ) ) {
														$answercats_counts[ trim( $aca ) ] = 0;
													}
													if ( isset( $answercats_counts[ trim( $aca ) ] ) ) {
														$answercats_counts[ trim( $aca ) ] += $as->count;
													}
												}
											}
										}
									}
								}
								if ( $aoptions[ 0 ] == "open" || $aoptions[ 0 ] == "date" || $aoptions[ 0 ] == "numeric" || $aoptions[ 0 ] == "select" ) {
									$as->answer = esc_html__( 'Other', MODAL_SURVEY_TEXT_DOMAIN );
								}
								if ( ! empty( $aoptions[ 3 ] ) ) {
								// including image to the answer: $as->answer = '<img src="' . $aoptions[ 3 ] . '">' . $as->answer;
								}
							}
							if ( isset( $args[ 'titles' ] ) && $args[ 'titles' ] != "" ) {
								$titles = explode( ",", $args[ 'titles' ] );
							}
							else {
								$titles = array();
							}
							if ( ! isset( $titles[ $key2 ] ) || empty( $titles[ $key2 ] ) || $titles[ $key2 ] == "" ) {
									$titles[ $key2 ] = nl2br( $as->answer );
							}
							$thisans = "";
							if ( ! empty( $titles[ $key2 ] ) ) {
								$thisans = $titles[ $key2 ];
							}
							else {
								$thisans = preg_replace( '/\[.*\]/', '', $as->answer );
							}
							$sdatas[ $key1 ][ $key2 ] = array( 'answer' => $thisans, 'count'=> $as->count );
						}
						if ( ( $key1 + 1 ) == $args[ 'qid' ] && $args[ 'data' ] != 'full' && $args[ 'data' ] != 'question' ) {
							return false; //replaces break; PHP7+
						}
					}
					if ( $args[ 'data' ] == 'score' || $args[ 'data' ] == 'average-score' || $args[ 'data' ] == 'rating' ) {
						if ( $key1 == 0 ) {
							$result .= '<div class="modal-survey-chart' . $key1 . ' ms-chart">';
							$result .= '<div class="legendDiv"></div><canvas></canvas></div>';
						}
						$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY %s";
						$a_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args[ 'id' ], $ars->question_id, $sort ) );
						//shortcode extension to get votes by post ID
						if ( $args[ 'postid' ] != '' ) {
							foreach($a_sql as $aaskey=>$bas){
								$a_sql[ $aaskey ]->count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( aid ) FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND qid = %d AND aid = %d AND postid = %d", $args[ 'id' ], $ars->question_id, $bas->autoid, $args[ 'postid' ] ) );
							}
						}
						//remove inactive answers start
						if ( $args[ 'showhidden' ] == "false" ) {
							foreach($a_sql as $aaskey=>$bas){
								$baoptions = unserialize( $bas->aoptions );								
								if ( isset( $baoptions[ 8 ] ) && $baoptions[ 8 ] == "1" ) {
									unset( $a_sql[ $aaskey ] );
								}
							}
						}
						//remove inactive answers end
						//keep the cumulative results to display multiple results on the same chart
						if ( $args[ 'compare' ] == "true" ) {
							$cum_a_sql = array();
							foreach ($a_sql as $k => $v) {
								$cum_a_sql[ $k ] = clone $v;
							}
						}
						else {
							$cum_a_sql[ 0 ] = ( object ) array( "count" => 0 );
						}
						//display individual records start
						if ( $args[ 'uid' ] != "false" ) {
							if ( $args[ 'uid' ] == "true" ) {
								if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {
									$args[ 'uid' ] = $_COOKIE[ 'ms-uid' ];
								}
							}
							if ( is_numeric( $args[ 'uid' ] ) ) {
								$args[ 'uid' ] = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE autoid = %d ", $args[ 'uid' ] ) );
							}
							$preparray = array( $args[ 'id' ], $ars->question_id, $args[ 'uid' ] );
							if ( ! empty( $lastvotes ) ) {
								$preparray = array( $args[ 'id' ], $ars->question_id, $args[ 'uid' ], $args[ 'session' ] );
							}
							$sql_u = "SELECT mspd.qid, mspd.aid FROM " . $wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $wpdb->base_prefix . "modal_survey_participants msp on mspd.uid = msp.autoid WHERE mspd.sid = %d AND mspd.qid = %d AND msp.id = %s " . $lastvotes . " ORDER BY autoid ASC";
							$a_sql_u = $wpdb->get_results( $wpdb->prepare( $sql_u, implode( $preparray ) ) );
							if ( ! empty( $a_sql_u ) ) {
								foreach( $a_sql_u as $key2u=>$asu ) {
									$uv_ans = explode( "|", $asu->aid );
									if ( is_array( $uv_ans ) ) {
										if ( ! isset( $user_votes[ $asu->qid ] ) ) {
											$user_votes[ $asu->qid ] = array();
										}
										$user_votes[ $asu->qid ][] = $uv_ans[ 0 ];
										if ( ! isset( $user_votes_values[ $asu->qid ] ) ) {
											$user_votes_values[ $asu->qid ] = array();
										}
										if ( ! isset( $uv_ans[ 1 ] ) ) {
											$uv_ans[ 1 ] = '';
										}
										$user_votes_values[ $asu->qid ][] = $uv_ans[ 1 ];
									}
								}
								//sum up multiple completions from the same user same survey
								if ( $atts[ 'session' ] == 'all' ) {
									foreach( $user_votes as $ukey=>$uvotes ) {
										if ( count( $uvotes ) > 0 ) {
											foreach( $uvotes as $uvkey=>$uves ) {
												$user_votes[ $ukey ][ $uvkey ] = count( $uvotes );
											}
										}
									}
								}
								/* reorganize user votes array START */
								$reor_user_v = array();
								foreach( $user_votes[ $ars->question_id ] as $uvrekey => $uvre ) {
									$reor_user_v[ $uvre ] = $user_votes_values[ $ars->question_id ][ $uvrekey ];
								}
								ksort($reor_user_v);
								$ruvmkey = 0;
								foreach( $reor_user_v as $ruvkey=>$ruv ) {
									$user_votes[ $ars->question_id ][ $ruvmkey ] = $ruvkey;
									$user_votes_values[ $ars->question_id ][ $ruvmkey ] = $ruv;
									$ruvmkey++;
								}
								/* reorganize user votes array END */
							foreach( $a_sql as $key2o=>$aso ) {
									if ( isset( $user_votes[ $aso->question_id ] ) && ( in_array( $aso->autoid, $user_votes[ $aso->question_id ] ) ) ) {
										if ( $atts[ 'session' ] != 'all' ) {
											$a_sql[ $key2o ]->count = 1;
										}
										else {
											//sum up multiple completions from the same user same survey
											$a_sql[ $key2o ]->count = ( int )$user_votes[ $aso->question_id ][ $key2o ];
										}
									}
									else {
										$a_sql[ $key2o ]->count = 0;										
									}
								}
							}
							else {
								if ( $args[ 'alternativedatas' ] == "false" ) {
									return ( "" );
								}
							}
							if ( $args[ 'alternativedatas' ] == "real" ) {
								foreach( $a_sql as $fkey=>$fsql ) {
									if ( ! isset( $user_votes[ $fsql->question_id ] ) ) {
										$a_sql[ $fkey ]->count = 0;
									}
								}
							}
						}
						//remove inactive answers start
						if ( $args[ 'showhidden' ] == "false" ) {
							foreach($a_sql as $aaskey=>$bas){
								$baoptions = unserialize( $bas->aoptions );								
								if ( isset( $baoptions[ 8 ] ) && $baoptions[ 8 ] == "1" ) {
									unset( $a_sql[ $aaskey ] );
								}
							}
						}
						//remove inactive answers end
						//display individual records end
						$qscore = 0; $gqscore = 0;
						$summary = 0; $gsummary = 0;
						$allratings = 0;$gallratings = 0;$key2 = 0;
						foreach( $a_sql as $key2=>$as ) {
							if ( isset( $as->aoptions ) ) {
								$aoptions = unserialize( $as->aoptions );
								if ( $aoptions[ 0 ] == "numeric" && $aoptions[ 4 ] == 0 ) {
									if ( ! isset( $user_votes_values[ $as->question_id ][ $key2 - 1 ] ) ) {
									   $user_votes_values[ $as->question_id ][ $key2 - 1 ] = 0;
									}
									$aoptions[ 4 ] = $user_votes_values[ $as->question_id ][ $key2 - 1 ];
									$as->count = 1;
								}
								if ( is_numeric( $aoptions[ 4 ] ) ) {
									preg_match_all( "/\[([^\]]*)\]/", $as->answer, $acats );
									if ( isset( $acats[ 1 ][ 0 ] ) ) {
										$acats_list = explode( ",", $acats[ 1 ][ 0 ] );
										foreach ( $acats_list as $aca ) {
											if ( isset( $aca ) ) {
												if ( ! empty( $aca ) && ! is_numeric( $aca )  ) {
													if ( ! isset( $answercats[ trim( $aca ) ] ) ) {
														$answercats[ trim( $aca ) ] = 0;
													}
													if ( ! isset( $answercats_counts[ trim( $aca ) ] ) ) {
														$answercats_counts[ trim( $aca ) ] = 0;
													}
													if ( isset( $answercats[ trim( $aca ) ] ) ) {
														$answercats[ trim( $aca ) ] += $aoptions[ 4 ] * $as->count;
													}
													if ( isset( $answercats_counts[ trim( $aca ) ] ) ) {
														$answercats_counts[ trim( $aca ) ] += $as->count;
													}
												}
											}
										}
									}
								}
							}
							if ( ! isset( $cum_a_sql[ $key2 ] ) ) {
								$cum_a_sql[ $key2 ] = new stdClass();
								$cum_a_sql[ $key2 ]->count = 0;
							}
							if ( isset( $as->aoptions ) ) {
								$aoptions = unserialize( $as->aoptions );
								if ( $aoptions[ 0 ] == "open" || $aoptions[ 0 ] == "date" || $aoptions[ 0 ] == "numeric" || $aoptions[ 0 ] == "select" ) {
									$as->answer = esc_html__( 'Other', MODAL_SURVEY_TEXT_DOMAIN );
								}
								if ( ! empty( $aoptions[ 3 ] ) ) {
								// including image to the answer: $as->answer = '<img src="' . $aoptions[ 3 ] . '">' . $as->answer;
								}
							}
							else {
								$aoptions[ 4 ] = 0;
							}
							if ( isset( $args[ 'titles' ] ) && $args[ 'titles' ] != "" ) {
								$titles = explode( ",", $args[ 'titles' ] );
							}
							else {
								$titles = array();
							}
							if ( ! isset( $titles[ $key1 ] ) || empty( $titles[ $key1 ] ) || $titles[ $key1 ] == "" ) {
								if ( strpos( nl2br( $ars->question ), '[-]' ) === false ) {
									$titles[ $key1 ] = nl2br( $ars->question );
								}
							}
							if ( $args[ 'data' ] == 'score' || $args[ 'data' ] == 'average-score' ) {
								if ( isset( $aoptions[ 4 ] ) && is_numeric( $aoptions[ 4 ] ) ) {
									$qscore += $as->count * $aoptions[ 4 ];
									$gqscore += $cum_a_sql[ $key2 ]->count * $aoptions[ 4 ];
								}
								else {
									$qscore += 0;								
									$gqscore += 0;								
								}
							}
							if ( $args[ 'data' ] == 'rating' ) {
							$summary += ( $key2 + 1 ) * $as->count;
							$allratings += $as->count;
							$gsummary += ( $key2 + 1 ) * $cum_a_sql[ $key2 ]->count;
							$gallratings += $cum_a_sql[ $key2 ]->count;
							}
						}
						if ( $args[ 'data' ] == 'rating' ) {
							if ( $allratings == 0 ) {
								$exactvalue = 0;
								$decvalue = 0;
								$intvalue = 0;
							}
							else {
								$exactvalue =  ( $summary / $allratings );
								$decvalue = ceil( ( $summary / $allratings ) * 2 ) / 2;
								$intvalue = ( int ) $decvalue;
							}
							$allans_count = count( $a_sql ) - $intvalue;
							$qscore = number_format( $exactvalue, $atts[ 'decimal' ], '.', '' );
							if ( $gallratings == 0 ) {
								$gexactvalue = 0;
								$gdecvalue = 0;
								$gintvalue = 0;
							}
							else {
								$gexactvalue =  ( $gsummary / $gallratings );
								$gdecvalue = ceil( ( $gsummary / $gallratings ) * 2 ) / 2;
								$gintvalue = ( int ) $gdecvalue;
							}
							$gallans_count = count( $cum_a_sql ) - $gintvalue;
							$gqscore = number_format( $gexactvalue, $atts[ 'decimal' ], '.', '' );
						}
						if ( isset( $titles[ $key1 ] ) ) {
							preg_match_all( "/\[([^\]]*)\]/", $titles[ $key1 ], $ques );
						}
						if ( isset( $ques[ 1 ] ) ) {
							if ( ! empty( $ques[ 1 ] ) ) {
								foreach( $ques[ 1 ] as $perscat ) {
									$titles[ $key1 ] = str_replace( $perscat, "", $titles[ $key1 ] );
									if ( ! empty( $perscat ) ) {
										$titles[ $key1 ] = str_replace( array( "[", "]" ), "", trim( $perscat ) );
										if ( ! isset( $cat_count[ $titles[ $key1 ] ] ) ) {
											$cat_count[ $titles[ $key1 ] ] = 1;
										}
										else {
											$cat_count[ $titles[ $key1 ] ]++;
										}
									}
								}
							}
						}
						$valexist = 0;
						if ( ! empty( $sdatas[ 0 ] ) ) {
								foreach ( $sdatas[ 0 ] as $qstkey=>$qst ) {
									if ( isset( $titles[ $key1 ] ) ) {
										if ( $qst[ 'answer' ] == $titles[ $key1 ] ) {
											 if ( $args[ 'data' ] == 'average-score' ) {
												$allcount = 0;
												foreach($a_sql as $aas){
													$allcount = $allcount + $aas->count;
												}
												if ( $allcount > 0 ) {
													$qscore = number_format( $qscore / $allcount, $atts[ 'decimal' ], '.', '' );
												}
												else {
													$qscore = 0;
												}
											 }
											 if ( $args[ 'compare' ] == 'true' && $args[ 'data' ] == "score"  ) {
												$gallcount = 0;
												foreach($cum_a_sql as $caas){
													$gallcount = $gallcount + $caas->count;
												}
												if ( $gallcount > 0 ) {
													$gqscore = number_format( $gqscore / $gallcount, $atts[ 'decimal' ], '.', '' );
												}
												else {
													$gqscore = 0;
												}
											 }
											$sdatas[ 0 ][ $qstkey ][ 'count' ] = $sdatas[ 0 ][ $qstkey ][ 'count' ] + $qscore;
											if ( $args[ 'compare' ] == "true" ) {
												$sdatas[ 0 ][ $qstkey ][ 'gcount' ] = $sdatas[ 0 ][ $qstkey ][ 'gcount' ] + $gqscore;
											}
											$valexist = 1;
										}
									}
								}
						}
						if ( $valexist == 0 ) {
							if ( isset( $titles[ $key1 ] ) ) {
								if ( strlen( $titles[ $key1 ] ) > 50 ) {
									$titles[ $key1 ] = substr( $titles[ $key1 ], 0, 50 ) . "...";
								}
								if ( $titles[ $key1 ] != "-" ) {
									 if ( $args[ 'data' ] == 'average-score' ) {
										$allcount = 0;
										foreach($a_sql as $aas){
											$allcount = $allcount + $aas->count;
										}
										if ( $allcount > 0 ) {
											$qscore = number_format( $qscore / $allcount, $atts[ 'decimal' ], '.', '' );
										}
										else {
											$qscore = 0;
										}
									 }
									 if ( $args[ 'compare' ] == 'true' && $args[ 'data' ] == "score" ) {
										$gallcount = 0;
										foreach($cum_a_sql as $caas){
											$gallcount = $gallcount + $caas->count;
										}
										if ( $gallcount > 0 ) {
											$gqscore = number_format( $gqscore / $gallcount, $atts[ 'decimal' ], '.', '' );
										}
										else {
											$gqscore = 0;
										}
									 }
									 if ( isset( $key1 ) && ! empty( $qscore ) && ! empty( $titles[ $key1 ] ) ) {
										$sdatas[ 0 ][ $key1 ] = array( 'answer' => $titles[ $key1 ], 'count'=> $qscore );
									 }
									if ( $args[ 'compare' ] == "true" ) {
										$sdatas[ 0 ][ $key1 ][ 'gcount' ] = $gqscore;
									}
								}
							}
						}
					}
				}
			}
			if ( ! empty( $answercats ) && ( $args[ 'data' ] == 'score' || $args[ 'data' ] == 'average-score' || $args[ 'data' ] == 'rating' ) ) {
				foreach( $answercats as $ackey=>$ac ) {
					if ( ! empty( $ackey ) && ! in_array( $ackey, $already_added ) ) {
						if ( $args[ 'data' ] == 'average-score' ) {
							if ( $answercats_counts[ $ackey ] > 0 ) {
								$sdatas[ 0 ][] = array( 'answer' => $ackey, 'count'=> round( $ac / $answercats_counts[ $ackey ], 2 ) );
								$already_added [] = $ackey;
							}
						}
						else {
							$sdatas[ 0 ][] = array( 'answer' => $ackey, 'count'=> $ac );
							$already_added [] = $ackey;
					}
					}
				}
			}
			if ( $args[ 'data' ] == "score" || $args[ 'data' ] == "average-score" ) {
				if ( $args[ 'sort' ] == "asc" ) {
					usort( $sdatas[ 0 ], function ( $item1, $item2 ) {
						if ( $item1[ 'count' ] == $item2[ 'count' ] ) return 0;
						return $item1[ 'count' ] < $item2[ 'count' ] ? -1 : 1;
					});
				}
				if ( $args[ 'sort' ] == "desc" ) {
					usort( $sdatas[ 0 ], function ( $item1, $item2 ) {
						if ( $item1[ 'count' ] == $item2[ 'count' ] ) return 0;
						return $item1[ 'count' ] < $item2[ 'count' ] ? -1 : 1;
					});
					$sdatas[ 0 ] = array_reverse( $sdatas[ 0 ] );
				}
			}
			if ( $args[ 'filter' ] != '' ) {
				$sdatas = modal_survey::filter_result( $args[ 'filter' ], $sdatas );
			}
			// start - extension to display top results only
			if ( is_numeric( $args[ 'top' ] ) && ( $args[ 'data' ] == "score" || $args[ 'data' ] == "average-score" ) ) {
				usort( $sdatas[ 0 ], function ( $item1, $item2 ) {
					if ( $item1[ 'count' ] == $item2[ 'count' ] ) return 0;
					return $item1[ 'count' ] < $item2[ 'count' ] ? -1 : 1;
				});
				$sdatas[ 0 ] = array_slice( array_reverse( $sdatas[ 0 ] ), 0, $args[ 'top' ] );
			}
			// end - extension to display top results only
			// start - extension to display percentages instead of scores or votes
			if ( $args[ 'percentage' ] == "true" && ( $args[ 'data' ] == "score" || $args[ 'data' ] == "average-score" ) ) {
				$tsumscore = 0;
				foreach( $sdatas[ 0 ] as $sd ) {
					$tsumscore += $sd[ 'count' ];
				}
				if ( strpos( $args[ 'catmax' ], ',' ) !== false) {
					$args[ 'catmax' ] = explode( ",", $args[ 'catmax' ] );
				}
				foreach( $sdatas[ 0 ] as $sdkey=>$sd ) {
					$thispercentage = 0;
					if ( $sd[ 'count' ] > 0 ) {
						if ( $args[ 'catmax' ] == 'false' ) {
							$thispercentage = round( ( ( $sd[ 'count' ] / $tsumscore ) * 100 ), 2 );
						}
						else {
							if ( is_numeric( $args[ 'catmax' ] ) ) {
								$thispercentage = round( ( ( $sd[ 'count' ] / $args[ 'catmax' ]  ) * 100 ), 2 );
							}
							if ( is_array( $args[ 'catmax' ] ) ) {
								if ( is_numeric( $args[ 'catmax' ][ $sdkey ] ) ) {
									$thispercentage = round( ( ( $sd[ 'count' ] / $args[ 'catmax' ][ $sdkey ] ) * 100 ), 2 );
								}
							}
						}
					}
					$sdatas[ 0 ][ $sdkey ][ 'count' ] = $thispercentage;
				}
			}
			if ( $args[ 'percentage' ] == "true" && $args[ 'data' ] == "question" ) {
				$tqallvotes = 0;
				foreach( $sdatas as $sdkey=>$sd ) {
					foreach( $sd as $sdkey2=>$sditem ) {
						$tqallvotes += $sditem[ "count" ];
					}
				}
				if ( is_array( $sdatas ) ) {
					foreach( $sdatas as $sdkey=>$sd ) {
						foreach( $sd as $sdkey2=>$sditem ) {
							$sdatas[ $sdkey ][ $sdkey2 ][ "count" ] = round( ( ( $sditem[ 'count' ] / $tqallvotes ) * 100 ), 2 );
						}
					}
				}
			}
			
			if ( $args[ 'uid' ] != "false" && $args[ 'data' ] == "average-score" ) {
				foreach( $sdatas[ 0 ] as $sdkey=>$sd ) {
					if ( isset( $cat_count[ $sd[ 'answer' ] ] ) && $cat_count[ $sd[ 'answer' ] ] > 0 ) {
						$thisavg = round( ( $sd[ 'count' ] / $cat_count[ $sd[ 'answer' ] ] ), 2 );
						$sdatas[ 0 ][ $sdkey ][ 'count' ] = $thisavg;
					}
				}
			}
			// end - extension to display percentages instead of scores or votes
			if ( $atts[ 'pure' ] == "true" && $style != "plain" ) {
				if ( $atts[ 'data' ] == "score" && empty( $answercats ) ) {
					return false;
				}
				else {
					return $sdatas;
				}
			}
			$msplugininit_answer_array[ $args['id'] . '-' . $unique_key ] = array( "printable" => $args[ 'printable' ], "style" => array( "style" => $args[ 'style' ], "max" => $args[ 'max' ], "bgcolor" => $args[ 'bgcolor' ], "cbgcolor" => $args[ 'cbgcolor' ], "percentage" => $args[ 'percentage' ], "after" => $args[ 'after' ], "legend" => $args[ 'legend' ] ), "datas" => $sdatas );
			if ( $args[ 'compare' ] == "true" ) {
				$msplugininit_answer_array[ $args['id'] . '-' . $unique_key ][ "style" ][ "lng" ] = array( "label1" => esc_html__( 'Personal: ', MODAL_SURVEY_TEXT_DOMAIN ), "label2" => esc_html__( 'Average: ', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			else {
				$msplugininit_answer_array[ $args['id'] . '-' . $unique_key ][ "style" ][ "lng" ] = array( "label1" => "", "label2" => "" );
			}
			if ( $args[ 'data' ] != 'full-records' ) {
				$result .= '</div>';
			}
			if ( isset( $_REQUEST[ 'sspcmd' ] ) && $_REQUEST[ 'sspcmd' ] == "displaychart" ) {
				$result .= "|endcontent-params|" . json_encode( $msplugininit_answer_array[ $args['id'] . '-' . $unique_key ] );
			}
			if ( $atts[ 'init' ] == "true" ) {
				$this->initialize_plugin();
			}
			return( $result );
		}
	
		function filter_result( $filter, $result ) {
			$catsfilter = array_map( function( $item ) {
				return ( strtolower( trim( $item ) ) );
			}, explode( ',', $filter ) );
			if ( is_array( $catsfilter ) && isset( $result[ 0 ] ) ) {
				foreach( $result[ 0 ] as $key => $sditems ) {
					if ( ! in_array( strtolower( $sditems[ "answer" ] ), $catsfilter ) ) {
						unset( $result[ 0 ][ $key ] );
					}
				}
			}
			return $result;
		}

		function random_color() {
			return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
		}

		public function survey_shortcodes( $atts ) {
			global $wpdb, $wp, $script, $msplugininit_array, $postid, $current_user;
			$unique_key = mt_rand();
			$postid = get_the_id();
			extract( shortcode_atts( array(
					'id' => '-1',
					'style' => 'modal',
					'width' => '',
					'align' => 'left',
					'textalign' => 'center',
					'message' => esc_html__( 'You already filled out this survey!', MODAL_SURVEY_TEXT_DOMAIN ),
					'filtered' => 'false',
					'social' => '',
					'init' => '',
					'visible' => 'true',
					'sociallink' => '',
					'socialimage' => '',
					'socialtitle' => '',
					'socialdesc' => '',
					'socialstyle' => '',
					'socialpos' => '',
					'form' => '',
					'pform' => '',
					'enddelay' => '',
					'display' => '',
					'unique' => 'false',
					'scrollto' => 'false',
					'alwaysdisplayed' => 'false',
					'boxed' => 'false',
					'questionbg' => 'false',
					'progressbar' => 'default',
					'confirmation' => 'false',
					'removesame' => 'false',
					'emailto' => '',
					'emailsendername' => ''
				), $atts, 'survey' ) );
			if ( ! isset( $atts[ 'style' ] ) ) {
				$atts[ 'style' ] = 'modal';
			}
			if ( ! isset( $atts[ 'align' ] ) ) {
				$atts[ 'align' ] = '';
			}
			if ( ! isset( $atts[ 'textalign' ] ) ) {
				$atts[ 'textalign' ] = '';
			}
			if ( ! isset( $atts[ 'width' ] ) ) {
				$atts[ 'width' ] = '100%';
			}
			if ( ! isset( $atts[ 'filtered' ] ) ) {
				$atts[ 'filtered' ] = 'false';
			}
			if ( ! isset( $atts[ 'init' ] ) ) {
				$atts[ 'init' ] = '';
			}
			if ( ! isset( $atts[ 'social' ] ) ) {
				$atts[ 'social' ] = '';
			}
			if ( ! isset( $atts[ 'visible' ] ) ) {
				$atts[ 'visible' ] = 'true';
			}
			if ( ! isset( $atts[ 'sociallink' ] ) ) {
				$atts[ 'sociallink' ] = '';
			}
			if ( ! isset( $atts[ 'socialimage' ] ) ) {
				$atts[ 'socialimage' ] = '';
			}
			if ( ! isset( $atts[ 'socialtitle' ] ) ) {
				$atts[ 'socialtitle' ] = '';
			}
			if ( ! isset( $atts[ 'socialdesc' ] ) ) {
				$atts[ 'socialdesc' ] = '';
			}
			if ( ! isset( $atts[ 'socialstyle' ] ) ) {
				$atts[ 'socialstyle' ] = '';
			}
			if ( ! isset( $atts[ 'fbappid' ] ) ) {
				$atts[ 'fbappid' ] = '';
			}
			if ( ! isset( $atts[ 'socialpos' ] ) ) {
				$atts[ 'socialpos' ] = '';
			}
			if ( ! isset( $atts[ 'form' ] ) ) {
				$atts[ 'form' ] = '';
			}
			if ( ! isset( $atts[ 'pform' ] ) ) {
				$atts[ 'pform' ] = '';
			}
			if ( ! isset( $atts[ 'enddelay' ] ) ) {
				$atts[ 'enddelay' ] = '';
			}
			if ( ! isset( $atts[ 'display' ] ) ) {
				$atts[ 'display' ] = '';
			}
			if ( ! isset( $atts[ 'message' ] ) ) {
				$atts[ 'message' ] = '';
			}
			if ( ! isset( $atts[ 'customclass' ] ) ) {
				$atts[ 'customclass' ] = '';
			}
			if ( ! isset( $atts[ 'unique' ] ) ) {
				$atts[ 'unique' ] = 'false';
			}
			if ( ! isset( $atts[ 'scrollto' ] ) ) {
				$atts[ 'scrollto' ] = 'false';
			}
			if ( ! isset( $atts[ 'alwaysdisplayed' ] ) ) {
				$atts[ 'alwaysdisplayed' ] = 'false';
			}
			if ( ! isset( $atts[ 'boxed' ] ) ) {
				$atts[ 'boxed' ] = 'false';
			}
			if ( ! isset( $atts[ 'questionbg' ] ) ) {
				$atts[ 'questionbg' ] = 'false';
			}
			if ( ! isset( $atts[ 'progressbar' ] ) ) {
				$atts[ 'progressbar' ] = 'default';
			}
			if ( ! isset( $atts[ 'confirmation' ] ) ) {
				$atts[ 'confirmation' ] = 'false';
			}
			if ( ! isset( $atts[ 'removesame' ] ) ) {
				$atts[ 'removesame' ] = 'false';
			}
			if ( ! isset( $atts[ 'emailto' ] ) ) {
				$atts[ 'emailto' ] = '';
			}
			if ( ! isset( $atts[ 'emailsendername' ] ) ) {
				$atts[ 'emailsendername' ] = '';
			}
			if ( ( ! isset( $atts[ 'message' ] ) || $atts[ 'message' ] == '' ) && ( $atts[ 'style' ] == 'click' || $atts[ 'style' ] == 'flat' ) ) {
				$atts[ 'message' ] = esc_html__( 'You already filled out this survey!', MODAL_SURVEY_TEXT_DOMAIN );
			}
			if ( ! isset( $atts[ 'id' ] ) ) {
				$atts[ 'id' ] = '';
			}
			$args = array(
				'id' => $atts[ 'id' ],
				'style' => $atts[ 'style' ],
				'init' => $atts[ 'init' ],
				'align' => $atts[ 'align' ],
				'textalign' => $atts[ 'textalign' ],
				'width' => $atts[ 'width' ],
				'filtered' => $atts[ 'filtered' ],
				'social' => $atts[ 'social' ],
				'visible' => $atts[ 'visible' ],
				'sociallink' => $atts[ 'sociallink' ],
				'socialimage' => $atts[ 'socialimage' ],
				'socialtitle' => strip_tags( $atts[ 'socialtitle' ] ),
				'socialdesc' => strip_tags( $atts[ 'socialdesc' ] ),
				'socialstyle' => $atts[ 'socialstyle' ],
				'fbappid' => $atts[ 'fbappid' ],
				'socialpos' => $atts[ 'socialpos' ],
				'form' => $atts[ 'form' ],
				'pform' => $atts[ 'pform' ],
				'enddelay' => $atts[ 'enddelay' ],
				'display' => $atts[ 'display' ],
				'message' => $atts[ 'message' ],
				'customclass' => $atts[ 'customclass' ],
				'unique' => $atts[ 'unique' ],
				'scrollto' => $atts[ 'scrollto' ],
				'alwaysdisplayed' => $atts[ 'alwaysdisplayed' ],
				'boxed' => $atts[ 'boxed' ],
				'questionbg' => $atts[ 'questionbg' ],
				'progressbar' => $atts[ 'progressbar' ],
				'confirmation' => $atts[ 'confirmation' ],
				'removesame' => $atts[ 'removesame' ],
				'emailto' => $atts[ 'emailto' ],
				'emailsendername' => $atts[ 'emailsendername' ]
			);
			$customadminemail = "";
			if ( ! empty( $args[ 'emailto' ] ) ) {
				$emailcut = explode( "@", $args[ 'emailto' ] );
				$customadminemail .= base64_encode( $emailcut[ 1 ] ) . '|' . base64_encode( $emailcut[ 0 ] ) . '|';
			}
			if ( ! empty( $args[ 'emailsendername' ] ) ) {
				$customadminemail .= base64_encode( $args[ 'emailsendername' ] ) . '|';
			}
			$survey[ 'currenturl' ] = home_url( $wp->request );
			if ( $atts[ 'filtered' ] == "true" ) {
				if ( ! is_single() && ! is_page() ) {
				//	return('');
				}
			}
			$survey_viewed = array();$sv_condition = '';
			if ( $atts[ 'style' ] = 'click' ) {
				if ( isset( $_COOKIE[ 'modal_survey' ] ) ) {
					if ( $_COOKIE[ 'modal_survey' ] != "undefined" ) {
						$survey_viewed = json_decode( stripslashes( $_COOKIE[ 'modal_survey' ] ) );
					}
				}
				$sql = "SELECT *,msq.id as question_id FROM " . $wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $wpdb->base_prefix . "modal_survey_questions msq on mss.id = msq.survey_id WHERE (`expiry_time`>'" . current_time( 'mysql', 1 ) . "' OR `expiry_time`='0000-00-00 00:00:00') AND (`start_time`<'" . current_time( 'mysql', 1 ) . "' OR `start_time`='0000-00-00 00:00:00') AND mss.id = %d ORDER BY msq.id ASC";
			}
			else {
				if ( isset( $_COOKIE[ 'modal_survey' ] ) ) {
					if ( $_COOKIE[ 'modal_survey' ] != "undefined" ) {
						$survey_viewed = json_decode( stripslashes( $_COOKIE[ 'modal_survey' ] ) );
					}
				}
				if ( ! empty( $survey_viewed ) ) {
					$sv_condition = "AND (mss.autoid NOT IN ( '" . implode( $survey_viewed, "', '" ) . "' ))";
				}
				$sql = "SELECT *,msq.id as question_id FROM " . $wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $wpdb->base_prefix . "modal_survey_questions msq on mss.id = msq.survey_id WHERE (`expiry_time`>'" . current_time( 'mysql', 1 ) . "' OR `expiry_time`='0000-00-00 00:00:00') AND (`start_time` < '" . current_time( 'mysql', 1 ) . "' OR `start_time`='0000-00-00 00:00:00') AND mss.id = %d '" . $sv_condition . "' ORDER BY msq.id ASC";
			}
			$questions_sql = $wpdb->get_results( $wpdb->prepare( $sql, $args['id'] ) );
			if ( ! empty( $questions_sql ) ) {
			$survey = array();
			if ( $atts[ 'social' ] == "" ) {
				$social = get_option( 'setting_social' );
			}
			else {
				$social = $atts[ 'social' ];
			}
			if ( $atts[ 'socialstyle' ] == "" ) {
				$socialstyle = get_option( 'setting_social_style' );
			}
			else {
				$socialstyle = $atts[ 'socialstyle' ];
			}
			if ( $atts[ 'socialpos' ] == "" ) {
				$socialpos = get_option( 'setting_social_pos' );
			}
			else {
				$socialpos = $atts[ 'socialpos' ];
			}
			if ( $atts[ 'fbappid' ] == "" ) {
				$fbappid = get_option( 'setting_fbappid' );
			}
			else {
				$fbappid = $atts[ 'fbappid' ];
			}
			if ( $atts[ 'socialtitle' ] == "" ) {
				$soctit_temp = get_the_title();
			}
			else {
				$soctit_temp = $atts[ 'socialtitle' ];
			}
			if ( $atts[ 'socialdesc' ] == "" ) {
				$socdes_temp = $this->get_short_desc();
			}
			else {
				$socdes_temp = $atts[ 'socialdesc' ];
			}
			if ( $atts[ 'sociallink' ] == "" ) {
				$soclink_temp = home_url( add_query_arg( array(), $wp->request ) ) . '/';
			}
			else {
				$soclink_temp = $atts[ 'sociallink' ];
			}
			if ( $atts[ 'socialimage' ] == "" ) {
				$socimg_temp = $this->get_featured_image();
			}
			else {
				$socimg_temp = $atts[ 'socialimage' ];
			}
			if ( $fbappid != "" ) {
				$socfbappid_temp = $fbappid;
			}
			else {
				$socfbappid_temp = "";
			}
			$survey[ 'social' ] = array( $social, get_option( 'setting_social_sites' ), $socialstyle, $socialpos, $soclink_temp, $socimg_temp, strip_tags( $soctit_temp ) , strip_tags( $socdes_temp ), $socfbappid_temp );
			$survey[ 'visible' ] = $args[ 'visible' ];
			$survey[ 'form' ] = $args[ 'form' ];
			$survey[ 'pform' ] = $args[ 'pform' ];
			$survey[ 'boxed' ] = $args[ 'boxed' ];
			$survey[ 'progressbar' ] = $args[ 'progressbar' ];
			$survey[ 'confirmation' ] = $args[ 'confirmation' ];
			$survey[ 'removesame' ] = $args[ 'removesame' ];
			$survey[ 'questionbg' ] = $args[ 'questionbg' ];
			$survey[ 'display' ] = $args[ 'display' ];
			$survey[ 'postid' ] = $postid;
			$survey[ 'cae' ] = base64_encode( $customadminemail );
			foreach( $questions_sql as $key=>$qs ) {
				if ( $key == 0 ) {
					if ( $args[ 'unique' ] == "true" ) {
						$qs->autoid = $qs->autoid . $postid;
					}
					if ( ! empty( $survey_viewed ) ) {
						if ( in_array( $qs->autoid, $survey_viewed ) ) {
							$sv_condition = "expired";
						}
					}
					$survey[ 'options' ] = stripslashes( str_replace( '\\\'', '|', $qs->options ) );
					if ( $atts[ 'enddelay' ] != "" ) {
						$ssoa = json_decode( $survey[ 'options' ] );
						$ssoa[ 23 ] = $atts[ 'enddelay' ];
						$survey[ 'options' ] = json_encode( $ssoa );
					}
					$survey[ 'plugin_url' ] = plugins_url( '' , __FILE__ );
					$survey[ 'admin_url' ] = admin_url( 'admin-ajax.php' );
					$survey[ 'survey_id' ] = $qs->survey_id;
					$survey[ 'auto_id' ] = $qs->autoid;
					$survey[ 'align' ] = $args[ 'align' ];
					$survey[ 'textalign' ] = $args[ 'textalign' ];
					$survey[ 'width' ] = $args[ 'width' ];
					$survey[ 'style' ] = $args[ 'style' ];
					$survey[ 'scrollto' ] = $args[ 'scrollto' ];
					$survey[ 'grid_items' ] = GRID_ITEMS;
					if ( $survey[ 'grid_items' ] == "" && $survey[ 'options' ][ 142 ] != "" ) {
						$ssoa = json_decode( $survey[ 'options' ] );
						$survey[ 'grid_items' ] = $ssoa[ 142 ];
						$survey[ 'options' ] = json_encode( $ssoa );
					}
					if ( $sv_condition != "expired" ) {
						$survey[ 'expired' ] = 'false';
					}
					else {
						$survey[ 'expired' ] = 'true';
					}
					if ( $args[ 'style' ] == 'click' ) {
						$survey[ 'message' ] = $atts[ 'message' ];
					}
				}
				$survey[ 'questions' ][ $key ][] = nl2br( $qs->question );
				do_action( 'wpml_register_single_string', MODAL_SURVEY_TEXT_DOMAIN, 'Modal Survey - ' . $qs->survey_id . ' . question ' . $key, $qs->question );
							$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY autoid ASC";
							$answers_sql = $wpdb->get_results( $wpdb->prepare( $sql, $qs->survey_id, $qs->question_id ) );
							foreach( $answers_sql as $key2 => $as ) {
								$survey[ 'questions' ][ $key ][] = $as->answer;
								do_action( 'wpml_register_single_string', MODAL_SURVEY_TEXT_DOMAIN, 'Modal Survey - ' . $qs->survey_id . ' . question ' . $key . ' answer ' . $key2, $as->answer );
								$survey[ 'ao' ][ ( $key + 1 ) . "_" . ( $key2 + 1 ) ] = unserialize( $as->aoptions );
							}
				$survey[ 'qo' ][ $key ] = unserialize( $qs->qoptions );
			}
			$soa = json_decode( $survey[ 'options' ] );
			if ( ! isset( $soa[ 18 ] ) ) {
				$soa[ 18 ] = "";
			}
			if ( $soa[18] == 1 ) {
				if ( ! is_user_logged_in() ) return;
				if ( get_option( 'setting_display_once_per_filled' ) == "on" ) {
					$max_question = $wpdb->get_var( $wpdb->prepare( "SELECT question_id FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %s ORDER BY question_id DESC", $qs->survey_id ) );
					$user_max_question = $wpdb->get_var( $wpdb->prepare( "SELECT mspd.qid FROM " . $wpdb->base_prefix . "modal_survey_participants msp LEFT JOIN " . $wpdb->base_prefix . "modal_survey_participants_details mspd on msp.autoid = mspd.uid WHERE mspd.sid = %s AND msp.username = %s ORDER BY mspd.qid DESC", $qs->survey_id, $current_user->user_login ) );
					if ( $max_question == $user_max_question ) {
						return( '<div class="ms-completed-message">' . htmlspecialchars_decode( $args[ 'message' ] ) . '</div>' );
					}
				}
			}
				if ( ( $args[ 'style' ] == 'flat' && in_array( $survey[ 'auto_id' ], $survey_viewed ) ) && ( $soa[ 18 ] != 1 ) ) {
						return( '<div class="ms-completed-message">' . htmlspecialchars_decode( $args[ 'message' ] ) . '</div>' );
				}
				else
				{
					if ( get_option( 'setting_display_once' ) == "on" ) {
						$survey_viewed = array();
						if ( isset( $_COOKIE[ 'modal_survey' ] ) ) {
							if ( $_COOKIE[ 'modal_survey' ] != "undefined" ) {
								$survey_viewed = json_decode( stripslashes( $_COOKIE[ 'modal_survey' ] ) );
							}
							if ( ! in_array( $survey[ 'auto_id' ], $survey_viewed ) ) {
								if ( $args[ 'alwaysdisplayed' ] != "true" ) {
									$survey_viewed[] = $survey[ 'auto_id' ];
								}
								if ( ! empty( $survey_viewed ) ) {
									$survey[ 'display_once' ] = json_encode( $survey_viewed );
								}
							}
						}
						else {
							if ( $args[ 'alwaysdisplayed' ] != "true" ) {
								$survey_viewed[] = $survey[ 'auto_id' ];
							}
							if ( ! empty( $survey_viewed ) ) {
								$survey[ 'display_once' ] = json_encode( $survey_viewed );
							}
						}
					}
					else {
						$survey['display_once'] = '';
					}
					$answers_text_sql = $wpdb->get_results( $wpdb->prepare( "SELECT msat.survey_id, msat.id, msat.answertext, msa.aoptions FROM ".$wpdb->base_prefix."modal_survey_answers_text msat INNER JOIN ".$wpdb->base_prefix."modal_survey_answers msa on msat.id = msa.uniqueid WHERE msat.survey_id = %d ORDER BY answertext ASC", $survey[ 'survey_id' ] ) );
					$datalist = array();
					$dlist = "";
					if ( ! empty( $answers_text_sql ) ) {
						foreach( $answers_text_sql as $atkey => $ats ) {
							if ( isset( $ats->aoptions ) ) {
								$aoptions = unserialize( $ats->aoptions );
								if ( isset( $aoptions[ 2 ] ) ) {
									if ( $aoptions[ 2 ] == "1" ) {
										$arraykey = $ats->survey_id . "_" . $ats->id;
										$datalist[ $arraykey ][] = $ats->answertext;
									}
								}
							}
						}
						foreach( $datalist as $dlkey => $dl ) {
							$dlist .= '<datalist id="ms_answers_' . $dlkey . '">';
							foreach( $dl as $answer ) {
								$dlist .= '<option value="' . $answer . '">';
							}
							$dlist .= '</datalist>';							
						}
					}
					$survey[ 'lastsessionqid' ] = $this->get_last_qid( $survey[ 'survey_id' ] );
					$msplugininit_array[ $survey[ 'survey_id' ] . '-' . $unique_key ] = array( "survey_id" => $survey[ 'survey_id' ], "unique_key" => $unique_key, "survey_options" => json_encode( $survey ) );
					if ( !empty( $args[ 'customclass' ] ) ) {
						$custom_class = ' ' . str_replace( ",", " ", $args[ 'customclass' ] );
					}
					else {
						$custom_class = '';
					}
					if ( $atts[ 'init' ] == "true" ) {
						$this->initialize_plugin();
					}
					return( $dlist . '<div id="survey-' . $survey[ 'survey_id' ] . '-' . $unique_key . '" class="modal-survey-container modal-survey-embed' . $custom_class . '"></div>' );
				}
			}
		}
		
		function extend_the_content( $content ) {
			global $wpdb;
			if ( $this->auto_embed == 'false' && ! empty( $content ) ) {
				$this->auto_embed = 'true';
				$sql = "SELECT id, options FROM " . $wpdb->base_prefix . "modal_survey_surveys mss WHERE global = 1 AND (`expiry_time`>'" . current_time( 'mysql', 1 ) . "' OR `expiry_time`='0000-00-00 00:00:00') AND (`start_time`<'" . current_time( 'mysql', 1 ) . "' OR `start_time`='0000-00-00 00:00:00')";
				$s_sql = $wpdb->get_results( $sql );
				if ( ! empty( $s_sql ) ) {
					$survey = array();
					foreach( $s_sql as $key=>$ss ) {	
						$block = 0;
						$thisoptions = json_decode( stripslashes( str_replace( '\\\'', '|', $ss->options ) ) );
						for ( $x = 1; $x <= 100; $x++ ) {
							if ( ! isset( $thisoptions[ $x ] ) ) {
								$thisoptions[ $x ] = '';
							}
						}
						if ( is_page() && ( $thisoptions[ 17 ] != "embed_start_pages" && $thisoptions[ 17 ] != "embed_end_pages" ) ) {
							$block = 1;
						}
						if ( is_single() && ! is_page() && ( $thisoptions[ 17 ] != "embed_start" && $thisoptions[ 17 ] != "embed_end" ) ) {
							$block = 1;
						}
						if ( $block != 1 ) {
							if ( strpos( $thisoptions[ 17 ], "end"  ) !== false ) {
								$content .= modal_survey::survey_shortcodes( 
											array ( 'id' => $ss->id, 'style' => 'flat', 'customclass' => 'autoembed-msurvey', 'unique' => 'true' )
											);					
							}
							if ( strpos( $thisoptions[ 17 ], "start"  ) !== false ) {
								$content = modal_survey::survey_shortcodes( 
											array ( 'id' => $ss->id, 'style' => 'flat', 'customclass' => 'autoembed-msurvey', 'unique' => 'true' )
											) . $content;				
							}
						}
					}
				}
			}
			return $content;
		}

		function enqueue_custom_scripts_and_styles() {
			global $wpdb, $wp, $script, $msplugininit_array, $postid, $current_user;
			$postid = url_to_postid( site_url( $_SERVER['REQUEST_URI'] ) );
			//retrieve last survey completions for the current user
			if ( ! empty( $current_user->user_login ) ) {
				$ms_reg_user_details = $wpdb->get_row( $wpdb->prepare( "SELECT mspd.samesession, msp.autoid, msp.id FROM " . $wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $wpdb->base_prefix . "modal_survey_participants msp on mspd.uid = msp.autoid WHERE msp.username = %s ORDER BY mspd.time DESC", $current_user->user_login ) );
				if ( ! empty( $ms_reg_user_details->samesession ) ) {
					setcookie( 'ms-session', $ms_reg_user_details->samesession, time() + 31536000, COOKIEPATH, COOKIE_DOMAIN, false);
					setcookie( 'ms-uid', $ms_reg_user_details->id, time() + 31536000, COOKIEPATH, COOKIE_DOMAIN, false);
				}
			}

			if ( get_option( 'setting_remember_users' ) != "off" ) {
				if ( ! isset( $_COOKIE[ 'ms-uid' ] ) ) {
					if( session_id() == '' ) {
						session_start();
					}
					$id = session_id();
					setcookie( 'ms-uid', session_id(), time() + 31536000, COOKIEPATH, COOKIE_DOMAIN, false);
				}
			}
			$unique_key = mt_rand();
			wp_enqueue_style( 'modal_survey_style', plugins_url( '/templates/assets/css/modal_survey.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_style( 'circliful_style', plugins_url( '/templates/assets/css/jquery.circliful.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_style( 'ms-jquery-ui', plugins_url( '/templates/assets/css/ms-jquery-ui.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_script( 'jquery' );
			if ( get_option( 'setting_social' ) == "on" ) {
				wp_enqueue_style('social_sharing_buttons_style', plugins_url( '/templates/assets/css/social-buttons.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
				wp_enqueue_script( 'social_sharing_buttons_script',plugins_url('/templates/assets/js/social-buttons.js', __FILE__ ), array( 'jquery' ), MODAL_SURVEY_VERSION );				
			}		
			wp_enqueue_script( 'jquery-ui-core', array( 'jquery' ) );
			wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery-ui-core' ) );
			wp_enqueue_script( 'jquery-ui-slider', array( 'jquery-ui-core' ) );
			wp_enqueue_script( 'jquery-effects-core', array( 'jquery' ) );
			wp_enqueue_script( 'jquery-effects-drop', array( 'jquery-effects-core' ) );
			wp_enqueue_script( 'jquery-effects-fade', array( 'jquery-effects-core' ) );
			wp_enqueue_script( 'jquery-effects-slide', array( 'jquery-effects-core' ) );
			wp_enqueue_script( 'jquery-visible', plugins_url( '/templates/assets/js/jquery.visible.min.js', __FILE__ ), array( 'jquery' ), '1.10.2' );
			wp_enqueue_script( 'jquery-mschartjs', plugins_url( '/templates/assets/js/Chart.min.js', __FILE__ ), array( 'jquery' ), '1.10.3' );
			wp_enqueue_script( 'printthis', plugins_url( '/templates/assets/js/printthis.js', __FILE__ ), array( 'jquery' ), '1.0.0' );
			wp_enqueue_script( 'modal_survey_answer_script',plugins_url('/templates/assets/js/' . $this->answerscript, __FILE__ ), array( 'jquery', 'jquery-mschartjs' ), MODAL_SURVEY_VERSION, true);
			wp_enqueue_script( 'modal_survey_script', plugins_url('/templates/assets/js/' . $this->mainscript , __FILE__ ), array( 'jquery' ), MODAL_SURVEY_VERSION );
			wp_enqueue_script( 'jquery-circliful', plugins_url( '/templates/assets/js/jquery.circliful.min.js', __FILE__ ), array( 'jquery', 'modal_survey_answer_script' ), '1.0.2' );
				$survey_viewed = array();$sv_condition = '';
					if ( isset( $_COOKIE[ 'modal_survey' ] ) ) {
						if ( $_COOKIE[ 'modal_survey' ] != "undefined" ) {
							$survey_viewed = json_decode( stripslashes( $_COOKIE[ 'modal_survey' ] ) );
						}
					}
					if ( ! empty( $survey_viewed ) ) {
						$sv_condition = "AND (mss.autoid NOT IN ( '" . implode( $survey_viewed, "', '" ) . "' ))";
					}
			$sql = "SELECT *,msq.id as question_id FROM " . $wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $wpdb->base_prefix . "modal_survey_questions msq on mss.id = msq.survey_id WHERE global = 1 AND (`expiry_time`>'" . current_time( 'mysql', 1 ) . "' OR `expiry_time`='0000-00-00 00:00:00') AND (`start_time`<'" . current_time( 'mysql', 1 ) . "' OR `start_time`='0000-00-00 00:00:00') " . $sv_condition . " ORDER BY msq.id ASC";
			$questions_sql = $wpdb->get_results( $sql );
			if ( ! empty( $questions_sql ) ) {
			$survey = array();
			$survey[ 'social' ] = array( get_option( 'setting_social' ), get_option( 'setting_social_sites' ), get_option( 'setting_social_style' ), get_option( 'setting_social_pos' ), ( home_url(add_query_arg(array(),$wp->request ) ) . '/' ), $this->get_featured_image(), get_the_title(), $this->get_short_desc(), get_option( 'setting_fbappid' ), get_option( 'setting_fbappid' ) );
			foreach( $questions_sql as $key=>$qs ) {
				if ( $key == 0 ) {
					$survey['options'] = stripslashes( str_replace( '\\\'', '|', $qs->options ) );
					$survey['plugin_url'] = plugins_url( '' , __FILE__ );
					$survey['admin_url'] = admin_url( 'admin-ajax.php');
					$survey['survey_id'] = $qs->survey_id;
					$survey['auto_id'] = $qs->autoid;
					$survey['style'] = 'modal';
					$survey['expired'] = 'false';
					$survey['debug'] = 'true';
					$survey['form'] = '';
					$survey[ 'scrollto' ] = 'true';
					$survey[ 'grid_items' ] = GRID_ITEMS;
					$survey[ 'lastsessionqid' ] = $this->get_last_qid( $survey[ 'survey_id' ] );
					if ( $survey[ 'grid_items' ] == "" && $survey[ 'options' ][ 142 ] != "" ) {
						$ssoa = json_decode( $survey[ 'options' ] );
						if ( isset( $ssoa[ 142 ] ) ) {
							$survey[ 'grid_items' ] = $ssoa[ 142 ];
						}
						$survey[ 'options' ] = json_encode( $ssoa );
					}
				}
				$survey[ 'questions' ][ $key ][] = nl2br( $qs->question );
							$sql = "SELECT * FROM ".$wpdb->base_prefix."modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY autoid ASC";
							$answers_sql = $wpdb->get_results( $wpdb->prepare( $sql, $qs->survey_id, $qs->question_id ) );
							foreach( $answers_sql as $key2=>$as ) {
								$survey[ 'questions' ][ $key ][] = $as->answer;
								$survey[ 'ao' ][ ( $key + 1 ) . "_" . ( $key2 + 1 ) ] = unserialize( $as->aoptions );
							}
				$survey[ 'qo' ][ $key ] = unserialize( $qs->qoptions );
			}
			$soa = json_decode( $survey[ 'options' ] );
			for ( $x = 1; $x <= 100; $x++ ) {
				if ( ! isset( $soa[ $x ] ) ) {
					$soa[ $x ] = '';
				}
			}
			if ( $soa[ 18 ] == 1 ) {
				if ( ! is_user_logged_in() ) return;
				if ( get_option( 'setting_display_once_per_filled' ) == "on" ) {
					$check_user = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants msp LEFT JOIN " . $wpdb->base_prefix . "modal_survey_participants_details mspd on msp.autoid = mspd.uid WHERE mspd.sid = %s AND msp.username = %s", $qs->survey_id, $current_user->user_login ) );
					if ( ! empty( $check_user ) ) {
						return;
					}
				}
			}
			$survey[ 'display_once' ] = '';
			$survey[ 'postid' ] = $postid;
			if ( $soa[ 17 ] == "embed_topics" ) {
				$survey[ 'style' ] = $soa[ 17 ];
				$this->esurvey = $survey;
			}
			if ( $soa[ 17 ] == "modal" ) {
				$msplugininit_array[ $survey[ 'survey_id' ] . '-' . $unique_key ] = array( "survey_id" => $survey[ 'survey_id' ], "unique_key" => $unique_key, "survey_options" => json_encode( $survey ) );
			}
			if ( get_option( 'setting_display_once' ) == "on" ) {
				$survey_viewed = array();
				if ( isset( $_COOKIE[ 'modal_survey' ] ) ) {
					$survey_viewed = json_decode( stripslashes( $_COOKIE[ 'modal_survey' ] ) );
					if ( ! in_array( $survey[ 'auto_id' ], $survey_viewed ) ) {
						$survey_viewed[] = $survey[ 'auto_id' ];
						if ( ! empty( $survey_viewed ) ) {
							setcookie( "modal_survey", json_encode( $survey_viewed ), time() + ( $soa[ 143 ] * 3600 ), COOKIEPATH, COOKIE_DOMAIN );
						}
					}
				}
				else {
					$survey_viewed[] = $survey[ 'auto_id' ];
					if ( ! empty( $survey_viewed ) ) {
						setcookie( "modal_survey", json_encode( $survey_viewed ), time() + ( $soa[ 143 ] * 3600 ), COOKIEPATH, COOKIE_DOMAIN );
					}
				}
			}
				$answers_text_sql = $wpdb->get_results( $wpdb->prepare( "SELECT msat.survey_id, msat.id, msat.answertext, msa.aoptions FROM ".$wpdb->base_prefix."modal_survey_answers_text msat INNER JOIN ".$wpdb->base_prefix."modal_survey_answers msa on msat.id = msa.uniqueid WHERE msat.survey_id = %d ORDER BY answertext ASC", $survey[ 'survey_id' ] ) );
				$datalist = array();
				$dlist = "";
				if ( ! empty( $answers_text_sql ) ) {
					foreach( $answers_text_sql as $atkey => $ats ) {
						if ( isset( $ats->aoptions ) ) {
							$aoptions = unserialize( $ats->aoptions );
							if ( isset( $aoptions[ 2 ] ) ) {
								if ( $aoptions[ 2 ] == "1" ) {
									$arraykey = $ats->survey_id . "_" . $ats->id;
									$datalist[ $arraykey ][] = $ats->answertext;
								}
							}
						}
					}
					foreach( $datalist as $dlkey => $dl ) {
						$dlist .= '<datalist id="ms_answers_' . $dlkey . '">';
						foreach( $dl as $answer ) {
							$dlist .= '<option value="' . $answer . '">';
						}
						$dlist .= '</datalist>';							
					}
				}
				$script = $dlist;
			}
			wp_enqueue_style( 'modal_survey_themes', plugins_url( '/templates/assets/css/themes.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			$custom_css = get_option( 'setting_customcss' );
			if ( $custom_css != ""  ) {
				wp_enqueue_style( 'modal-survey-custom-style', plugins_url( '/templates/assets/css/custom_ms.css', __FILE__ ) );
				wp_add_inline_style( 'modal-survey-custom-style', $custom_css );
			}
		}
		/**
		* Add the settings link to the plugins page
		**/
		function plugin_settings_link($links) {
			$settings_link = '<a href="options-general.php?page=modal_survey">' . esc_html__( 'Settings', MODAL_SURVEY_TEXT_DOMAIN ) . '</a>';
			array_unshift($links, $settings_link); 
			return $links; 
		}
		
		function add_localization( $ma ) {
		global $current_user;
			foreach( $ma as $key => $array ) {
			$so = json_decode( $array['survey_options'] );
				$so->languages = array(
					"pform_description" => esc_html__( 'Please enter your details below to continue.', MODAL_SURVEY_TEXT_DOMAIN ),
					"name_placeholder" => esc_html__( 'Enter your name', MODAL_SURVEY_TEXT_DOMAIN ),
					"email_placeholder" => esc_html__( 'Enter your email address', MODAL_SURVEY_TEXT_DOMAIN ),
					"send_button" => esc_html__( 'SEND', MODAL_SURVEY_TEXT_DOMAIN ),
					"confirm_button" => esc_html__( 'CONFIRM', MODAL_SURVEY_TEXT_DOMAIN ),
					"confirm_title" => esc_html__( 'OVERVIEW OF ALL GIVEN ANSWERS', MODAL_SURVEY_TEXT_DOMAIN ),
					"confirm_question" => esc_html__( 'QUESTION', MODAL_SURVEY_TEXT_DOMAIN ),
					"confirm_answer" => esc_html__( 'ANSWER', MODAL_SURVEY_TEXT_DOMAIN ),
					"confirm_edit" => esc_html__( 'EDIT', MODAL_SURVEY_TEXT_DOMAIN ),
					"next_button" => esc_html__( 'NEXT', MODAL_SURVEY_TEXT_DOMAIN ),
					"back_button" => esc_html__( 'BACK', MODAL_SURVEY_TEXT_DOMAIN ),
					"success" => esc_html__( 'SUCCESS', MODAL_SURVEY_TEXT_DOMAIN ),
					"shortname" => esc_html__( 'Name too short', MODAL_SURVEY_TEXT_DOMAIN ),
					"invalidemail" => esc_html__( 'Invalid Email Address', MODAL_SURVEY_TEXT_DOMAIN ),
					"alreadyfilled" => esc_html__( 'You already filled out this survey!', MODAL_SURVEY_TEXT_DOMAIN ),
					"campaignerror" => esc_html__( 'Connection Error', MODAL_SURVEY_TEXT_DOMAIN ),
					"timeisup" => esc_html__( 'Time is up!', MODAL_SURVEY_TEXT_DOMAIN ),
					"mailconfirmation" => esc_html__( 'Subscribe to our mailing list', MODAL_SURVEY_TEXT_DOMAIN ),
					"checkboxvalue" => esc_html__( 'Yes', MODAL_SURVEY_TEXT_DOMAIN ),
					"checkboxoffvalue" => esc_html__( 'No', MODAL_SURVEY_TEXT_DOMAIN ),
					"share" => esc_html__( 'Share', MODAL_SURVEY_TEXT_DOMAIN ),
					"tweet" => esc_html__( 'Tweet', MODAL_SURVEY_TEXT_DOMAIN ),
					"plusone" => esc_html__( '+1', MODAL_SURVEY_TEXT_DOMAIN ),
					"pinit" => esc_html__( 'Pin it', MODAL_SURVEY_TEXT_DOMAIN )
					);
				$so->user = array( "email" => "", "name" => "" );
				if ( ! empty( $current_user->user_email ) ) {
					$so->user[ "email" ] = $current_user->user_email;
				}
				if ( ( ! empty( $current_user->user_firstname ) && ! empty( $current_user->user_lastname ) ) || ( ! empty( $current_user->display_name ) ) ) {
					if ( ( ! empty( $current_user->user_firstname ) ) && ! empty( $current_user->user_lastname ) ) {
						$so->user[ "name" ] = $current_user->user_firstname . ' ' . $current_user->user_lastname;
					}
					elseif ( ! empty( $current_user->display_name ) ) {
						$so->user[ "name" ] = $current_user->display_name;
					}
				}
				$ma[ $key ][ "survey_options" ] = json_encode( $so );
			}
			return $ma;
		}
		
		function get_last_qid( $sid ) {
		global $wpdb;
		$current_question = '-1';
			if ( isset( $_COOKIE[ 'ms-session' ] ) ) {
				$ms_session = $_COOKIE[ 'ms-session' ];
			}
			if ( ! empty( $ms_session ) ) {
				$last_vote = $wpdb->get_row( $wpdb->prepare( "SELECT `qid`,`aid` FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE `sid` = %d AND `samesession` = %d ORDER BY `time` DESC", $sid, $ms_session ) );
				if ( $last_vote ) {
					$last_aopts = $wpdb->get_var( $wpdb->prepare( "SELECT `aoptions` FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE `survey_id` = %d AND `question_id` = %d AND `autoid` = %d", $sid, $last_vote->qid, $last_vote->aid ) );
					$laopts = unserialize( $last_aopts );
					if ( $laopts[ 11 ] > 0 ) {
						$current_question = $laopts[ 11 ] - 1;
					}
					else {
						$current_question = $last_vote->qid;
					}
				}
				return $current_question;
			}
		}		
		
		function initialize_plugin() {
		global $msplugininit_array, $msplugininit_answer_array, $script;
			if ( $this->mspreinit == "false" ) {
				$this->mspreinit = "true";
				if ( ! empty( $script ) ) {
					echo esc_js( $script );
				}
				if ( ! empty( $msplugininit_array ) ) {
					$msplugininit_array = $this->add_localization( $msplugininit_array );
					foreach( $msplugininit_array as $key=>$ma ) {
						$sop = json_decode( $ma[ 'survey_options' ] );
						$sop->current_score = 0;
						if ( isset( $_COOKIE[ 'ms-cqn-' . $ma[ 'survey_id' ] ] ) ) {
							$sop->current_score = modal_survey::survey_answers_shortcodes(
								array ( 'id' => $ma[ 'survey_id' ], 'data' => 'score', 'style' => 'plain', 'uid' => 'true', 'session' => 'last' )
							);
						}
						$msplugininit_array[ $key ][ 'survey_options' ] = json_encode( $sop );
					}
					wp_register_script( 'modal_survey_script_init', plugins_url( '/templates/assets/js/modal_survey_init.js', __FILE__ ), array( 'jquery', 'modal_survey_script' ), MODAL_SURVEY_VERSION, true );
					wp_localize_script( 'modal_survey_script_init', 'ms_init_params', $msplugininit_array );
					wp_enqueue_script( 'modal_survey_script_init' );
					do_action( 'modal_survey_action_init', $msplugininit_array );
				}
				if ( ! empty( $msplugininit_answer_array ) ) {
					wp_register_script( 'modal_survey_answer_script_init', plugins_url( '/templates/assets/js/modal_survey_answer_init.js', __FILE__ ), array( 'jquery' ), MODAL_SURVEY_VERSION, true );
					wp_localize_script( 'modal_survey_answer_script_init', 'ms_answer_init_params', $msplugininit_answer_array );
					wp_enqueue_script( 'modal_survey_answer_script_init' );			
					do_action( 'modal_survey_action_answer_init', $msplugininit_answer_array );
				}
			}
		}

		public static function update_modal_survey_db() {
			global $wpdb;
			$updated = false;
			try {
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_questions'."' 
						AND COLUMN_NAME = 'qoptions'";
				$res[ 'qoptions' ] = $wpdb->query( $sql );
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_answers'."' 
						AND COLUMN_NAME = 'aoptions'";
				$res[ 'aoptions' ] = $wpdb->query( $sql );
					if ( $res[ 'qoptions' ] == '0' ) {
						$wpdb->query( "ALTER IGNORE TABLE " . $wpdb->base_prefix . 'modal_survey_questions' . " ADD qoptions text" );
						$updated = true;
					}
					if ( $res[ 'aoptions' ] == '0' ) {
						$wpdb->query( "ALTER IGNORE TABLE " . $wpdb->base_prefix . 'modal_survey_answers' . " ADD aoptions text" );
						$updated = true;
					}
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_surveys'."' 
						AND COLUMN_NAME = 'created'";
				$res[ 'created' ] = $wpdb->query( $sql );
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_surveys'."' 
						AND COLUMN_NAME = 'updated'";
				$res[ 'updated' ] = $wpdb->query( $sql );
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_surveys'."' 
						AND COLUMN_NAME = 'owner'";
				$res[ 'owner' ] = $wpdb->query( $sql );
					if ( $res[ 'created' ] == '0' ) {
						$wpdb->query( "ALTER IGNORE TABLE " . $wpdb->base_prefix . 'modal_survey_surveys' . " ADD created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL" );
						$wpdb->update( $wpdb->base_prefix . "modal_survey_surveys", array( "created" => date( "Y-m-d H:i:s" ) ), array( 'created' => "0000-00-00 00:00:00" ) );
						$updated = true;
					}
					if ( $res[ 'updated' ] == '0' ) {
						$wpdb->query("ALTER IGNORE TABLE ".$wpdb->base_prefix.'modal_survey_surveys'." ADD updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
						$updated = true;
					}
					if ( $res[ 'owner' ] == '0' ) {
						$wpdb->query("ALTER IGNORE TABLE ".$wpdb->base_prefix.'modal_survey_surveys'." ADD owner bigint NOT NULL");
						$wpdb->update( $wpdb->base_prefix."modal_survey_surveys", array( "owner" => get_current_user_id() ),array('owner' => "NULL"));
						$updated = true;
					}
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_answers'."' 
						AND COLUMN_NAME = 'uniqueid'";
				$res[ 'uniqueid' ] = $wpdb->query( $sql );
					if ( $res[ 'uniqueid' ] == '0' ) {
						$wpdb->query( "ALTER IGNORE TABLE " . $wpdb->base_prefix . 'modal_survey_answers' . " ADD uniqueid varchar(255) NOT NULL" );
						$updated = true;
					}
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_participants_details'."' 
						AND COLUMN_NAME = 'postid'";
				$res[ 'postid' ] = $wpdb->query( $sql );
					if ( $res[ 'postid' ] == '0' ) {
						$wpdb->query( "ALTER IGNORE TABLE " . $wpdb->base_prefix . 'modal_survey_participants_details' . " ADD postid bigint NOT NULL" );
						$updated = true;
					}

				/** 1.9.5 START updating participants_details table if necessary **/
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_participants_details'."' 
						AND COLUMN_NAME = 'samesession'";
				$res[ 'samesession' ] = $wpdb->query( $sql );
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_participants_details'."' 
						AND COLUMN_NAME = 'timer'";
				$res[ 'timer' ] = $wpdb->query( $sql );
				if ( $res[ 'samesession' ] == '0' ) {
					$wpdb->query( "ALTER IGNORE TABLE " . $wpdb->base_prefix . 'modal_survey_participants_details'." ADD samesession varchar(255) NOT NULL" );
					$updated = true;
				}
				if ( $res[ 'timer' ] == '0' ) {
					$wpdb->query( "ALTER IGNORE TABLE " . $wpdb->base_prefix . 'modal_survey_participants_details'." ADD timer int NULL" );
					$updated = true;
				}
				
				/** 1.9.7.2 START updating participants table if necessary **/
				$sql = "SELECT * 
						FROM information_schema.COLUMNS 
						WHERE 
							TABLE_SCHEMA = '" . $wpdb->dbname . "' 
						AND TABLE_NAME = '" . $wpdb->base_prefix . 'modal_survey_participants'."' 
						AND COLUMN_NAME = 'custom'";
				$res[ 'custom' ] = $wpdb->query( $sql );
				if ( $res[ 'custom' ] == '0' ) {
					$wpdb->query( "ALTER IGNORE TABLE " . $wpdb->base_prefix . 'modal_survey_participants' . " ADD custom TEXT NOT NULL" );
					$updated = true;
				}
				/** END updating participants_details table if necessary **/
				
				/** START CHECKING CHANGES **/
				$ms_db_version = get_option( 'setting_db_modal_survey' );
				update_option( 'setting_db_modal_survey', MODAL_SURVEY_VERSION );				
				return true;
				/** END CHECKING CHANGES **/
			}
			catch ( Exception $e ) {
				echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
		}
	}
}
if ( class_exists( 'modal_survey' ) ) {
	// call the main class
	$modal_survey = modal_survey::getInstance();
}
?>