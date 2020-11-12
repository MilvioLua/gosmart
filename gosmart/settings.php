<?php
if ( ! class_exists( 'modal_survey_settings' ) ) {
	class modal_survey_settings extends modal_survey {
	/**
	* Construct the plugin object
	**/
		public function __construct() {
		global $wpdb;
			$this->wpdb =& $wpdb;
			/**
			* register actions, hook into WP's admin_init action hook
			**/
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'wp_ajax_ajax_survey', array( $this, 'ajax_survey'));
			add_action( 'wp_ajax_nopriv_ajax_survey', array( $this, 'ajax_survey' ) );
			add_action( 'wp_ajax_ajax_survey_answer', array( $this, 'ajax_survey_answer' ) );
			add_action( 'wp_ajax_nopriv_ajax_survey_answer', array( $this, 'ajax_survey_answer' ) );
			add_action( 'wp_ajax_ajax_survey_back', array( $this, 'ajax_survey_back' ) );
			add_action( 'wp_ajax_nopriv_ajax_survey_back', array( $this, 'ajax_survey_back' ) );
			add_action( 'wp_dashboard_setup', array( $this, 'modal_survey_add_dashboard_widgets' ) );
			add_action( 'wp_ajax_ajax_ms_campaigns', array( $this, 'ajax_ms_campaigns'));
			add_action( 'wp_ajax_nopriv_ajax_ms_campaigns', array( $this, 'ajax_ms_campaigns' ) );
			add_action( 'current_screen', array( $this, 'thisScreen' ));
			add_action( 'add_meta_boxes',  array( $this, 'modalsurvey_add_meta_box' ) );
			add_action( 'admin_footer', array( $this, 'modalsurvey_admin_footer' ) );
			add_action( 'save_post', array( $this, 'modalsurvey_save_meta_box_data' ) );
		}

		function modalsurvey_add_meta_box( $posttype ) {
			global $postcharts, $wpdb;
			$screens = array( 'post', 'page', 'topic', 'forum' );
			$postid = get_the_ID();
			$s_sql = $wpdb->get_results( $wpdb->prepare( "SELECT mspd.sid, mss.name, mspd.postid FROM " . $wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_surveys mss on mss.id = mspd.sid WHERE postid = %d GROUP BY mspd.sid", $postid ) );
			foreach ( $screens as $screen ) {
				foreach( $s_sql as $surveys ) {
					add_meta_box(
						'modalsurvey_postresults-' . $surveys->sid,
						$surveys->name,
						array( $this, 'modalsurvey_meta_box_callback' ),
						$screen,
						'side',
						'low'
					);
					$postcharts[ $surveys->sid ] = $postid;
				}
			}
		}
		
		function modalsurvey_meta_box_callback( $post ) {
		global $postcharts;
		reset($postcharts);
			wp_nonce_field( 'modalsurvey_save_meta_box_data', 'modalsurvey_meta_box_nonce' );
			$value = get_post_meta( $post->ID, 'modal_survey_postchart_' . key( $postcharts ), true );
			echo "<select class='postchart' id='survey_chart_style[" . key( $postcharts ) . "]' name='survey_chart_style[" . key( $postcharts ) . "]'>
			<option " . selected( $value, 'full', false ) . " value='full'>" . esc_html__( 'Default', MODAL_SURVEY_TEXT_DOMAIN ) . "</option>
			<option " . selected( $value, 'score', false ) . " value='score'>" . esc_html__( 'Score', MODAL_SURVEY_TEXT_DOMAIN ) . "</option>
			<option " . selected( $value, 'rating', false ) . " value='rating'>" . esc_html__( 'Rating', MODAL_SURVEY_TEXT_DOMAIN ) . "</option></select>";
			foreach( $postcharts as $key=>$pc ) {
				echo modal_survey::survey_answers_shortcodes( 
						array ( 'id' => $key, 'data' => ( $value != "" ) ? $value : 'full', 'style' => 'piechart', 'limited' => 'no', 'postid' => $pc )
					);
				unset( $postcharts[ $key ] );
				return false; // replaces break; PHP7+
			}
		}
		
		function modalsurvey_save_meta_box_data( $post_id ) {
			if ( ! isset( $_POST['modalsurvey_meta_box_nonce'] ) ) {
				return;
			}
			if ( ! wp_verify_nonce( $_POST['modalsurvey_meta_box_nonce'], 'modalsurvey_save_meta_box_data' ) ) {
				return;
			}
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}
			if ( ! isset( $_POST['survey_chart_style'] ) ) {
				return;
			}
			// Sanitize user input.
			$modal_survey_postchart_data = sanitize_text_field( $_POST['survey_chart_style'] );

			// Update the meta field in the database.
			foreach( $modal_survey_postchart_data as $key=>$mspd ) {
				update_post_meta( $post_id, 'modal_survey_postchart_' . $key, sanitize_text_field( $mspd ) );
			}
		}

		function thisScreen() {
			$currentScreen = get_current_screen();
			if( $currentScreen->id == "dashboard" || $currentScreen->id == "modal-survey_page_modal_survey_participants" || $currentScreen->id == "page" || $currentScreen->id == "post"  || $currentScreen->id == "forum"  || $currentScreen->id == "topic" ) {
			// Run some code, only on the admin widgets page
			wp_enqueue_style('modal_survey_style', plugins_url( '/templates/assets/css/modal_survey.css' , __FILE__ ));
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-chartjs', plugins_url( '/templates/assets/js/Chart.min.js' , __FILE__ ), array( 'jquery' ), MODAL_SURVEY_VERSION );
			wp_enqueue_script( 'modal_survey_answer_script', plugins_url('/templates/assets/js/modal_survey_answer.js', __FILE__ ), array( 'jquery', 'jquery-chartjs' ), MODAL_SURVEY_VERSION, true );
			wp_register_script( 'modal_survey_answer_script_init', plugins_url( '/templates/assets/js/modal_survey_answer_init.js', __FILE__ ), array( 'jquery', 'jquery-chartjs', 'modal_survey_answer_script' ), MODAL_SURVEY_VERSION, true );
			}
		}		
		/**
		 * Add a widget to the dashboard.
		 *
		 * This function is hooked into the 'wp_dashboard_setup' action below.
		 */
		function modal_survey_add_dashboard_widgets() {
			add_meta_box(
						'modal_survey_votes_by_surveys',
						esc_html__( 'Modal Survey - Votes by Surveys', MODAL_SURVEY_TEXT_DOMAIN ),
						array( $this, 'modal_survey_votes_by_surveys_dashboard_widget_function' ), // Display function.
						'dashboard',
						'side',
						'default'
				);	
			add_meta_box(
						'modal_survey_votes_by_days',
						esc_html__( 'Modal Survey - Votes by Days', MODAL_SURVEY_TEXT_DOMAIN ),
						array( $this, 'modal_survey_votes_by_days_dashboard_widget_function' ), // Display function.
						'dashboard',
						'side',
						'default'
				);	
		}

		function modalsurvey_admin_footer() {
		global $msplugininit_answer_array;
			if ( ! empty( $msplugininit_answer_array ) ) {
				wp_localize_script( 'modal_survey_answer_script_init', 'ms_answer_init_params', $msplugininit_answer_array );
				wp_enqueue_script( 'modal_survey_answer_script_init' );
			}			
		}
		
		/**
		 * Create the function to output the contents of our Dashboard Widget.
		 */
		function modal_survey_votes_by_surveys_dashboard_widget_function() {
		global $wpdb, $msplugininit_answer_array;
			$s_sql = $wpdb->get_results( "SELECT mss.id, mss.name, IF((`expiry_time`>'" . current_time( 'mysql', 1 ) . "' OR `expiry_time`='0000-00-00 00:00:00'), 'false', 'true') as expired,SUM(msa.count) as sumcount FROM " . $this->wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_answers msa on mss.id = msa.survey_id GROUP BY mss.id ORDER BY mss.autoid DESC" );
			$scount = 0;
			foreach( $s_sql as $key=>$sd ) {
				if ( $sd->expired == 'false' ) {
					$sdatas[ 0 ][ $key ] = array( 'answer' => $sd->name, 'count'=> $sd->sumcount );
					$scount += $sd->sumcount;
				}
			}
			if ( $scount > 0 ) {
				$result = '<div id="survey-results-msdash-1" class="survey-results">';
				$result .= '<div class="modal-survey-chart0 ms-chart">';
				$result .= '<canvas></canvas>';
				$result .= '</div></div>';
				echo strip_tags( $result, '<div>, <canvas>' );
				$msplugininit_answer_array[ 'msdash-1' ] = array( "style" => array( "style" => 'piechart', "max" => '0', "bgcolor" => '' ), "datas" => $sdatas );
			}
			else {
				echo esc_html__( 'Not enough votes to display summarized chart', MODAL_SURVEY_TEXT_DOMAIN );
			}
		}
		/**
		 * Create the function to output the contents of our Dashboard Widget.
		 */
		function modal_survey_votes_by_days_dashboard_widget_function() {
		global $wpdb, $msplugininit_answer_array;
			// Display whatever it is you want to show.
			$s_sql = $wpdb->get_results( "SELECT COUNT( uid ) as count, DATE_FORMAT( time,'%d-%m-%Y') as date  FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE ( time > NOW() - INTERVAL 6 DAY ) GROUP BY DATE_FORMAT( time,'%d-%m-%Y') ORDER BY time ASC" );
			if ( $s_sql ) {
				foreach( $s_sql as $key=>$sd ) {
						$sdatas[ 1 ][ $key ] = array( 'answer' => date( 'l', strtotime( $sd->date ) ), 'count'=> $sd->count );
				}
				$result = '<div id="survey-results-msdash-2" class="survey-results">';
				$result .= '<div class="modal-survey-chart1 ms-chart">';
				$result .= '<canvas></canvas>';
				$result .= '</div></div>';
				echo strip_tags( $result, '<div>, <canvas>' );
				$msplugininit_answer_array[ 'msdash-2' ] = array( "style" => array( "style" => 'linechart', "max" => '0', "bgcolor" => '' ), "datas" => $sdatas );
			}
			else {
				echo esc_html__( 'Not enough votes to display summarized chart', MODAL_SURVEY_TEXT_DOMAIN );
			}
		}
		public function mshashCode($str) {
			$str = (string)$str;
			$hash = 0;
			$len = strlen($str);
			if ($len == 0 )
				return abs( $hash );
		 
			for ($i = 0; $i < $len; $i++) {
				$h = $hash << 5;
				$h -= $hash;
				$h += ord($str[$i]);
				$hash = $h;
				$hash &= 0xFFFFFFFF;
			}
			return abs( $hash );
		}
		/**
		* include custom scripts and style to the posts & pages page
		**/
		function enqueue_adminpost_custom_scripts_and_styles() {
			wp_enqueue_style( 'modal_survey_post_style', plugins_url( '/templates/assets/css/editor-style.css', __FILE__ ) );
		}
		/**
		* include custom scripts and style to the admin page
		**/
		function enqueue_admin_custom_scripts_and_styles() {
			wp_enqueue_style( 'modal_survey_admin_style', plugins_url( '/templates/assets/css/modal_survey_settings.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_style( 'modal_survey_ui_style', plugins_url( '/templates/assets/css/jquery-ui.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_style( 'pantherius_ui_theme', plugins_url( '/templates/assets/css/pantherius-jquery-ui.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_style( 'colorpicker_style', plugins_url( '/templates/assets/css/colorpicker.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_style( 'gradx_style', plugins_url( '/templates/assets/css/gradX.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_style( 'datatables_style', plugins_url( '/templates/assets/css/datatables.min.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_style( 'modal_survey_style', plugins_url( '/templates/assets/css/modal_survey.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_style( 'modal_survey_wizard_script', plugins_url( '/templates/assets/css/modal_survey_wizard.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
			wp_enqueue_media();
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-tooltip' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-slider' );
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( 'jquery-ui-widget' );
			wp_enqueue_script( 'jquery-ui-accordion' );
			wp_enqueue_script( 'jquery-effects-core', array( 'jquery' ) );
			wp_enqueue_script( 'jquery-effects-fade', array( 'jquery-effects-core' ) );
			wp_enqueue_script( 'jquery-effects-drop', array( 'jquery-effects-core' ) );
			wp_enqueue_script( 'jquery-effects-slide', array( 'jquery-effects-core' ) );
			wp_enqueue_script( 'image_uploader_ms', plugins_url( '/templates/assets/js/image-uploader.js', __FILE__ ), array('jquery'), MODAL_SURVEY_VERSION );
			wp_enqueue_script('jquery-chartjs', plugins_url( '/templates/assets/js/Chart.min.js' , __FILE__ ), array('modal_survey_admin'), MODAL_SURVEY_VERSION);
			wp_enqueue_script( "jquery_timepicker_script", plugins_url('/templates/assets/js/jquery.timepicker.js', __FILE__ ), array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'), MODAL_SURVEY_VERSION, true);
			wp_enqueue_script( "colorpicker_script", plugins_url('/templates/assets/js/colorpicker.js', __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( "domdrag_script", plugins_url('/templates/assets/js/dom-drag.js', __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( "gradx_script", plugins_url('/templates/assets/js/gradX.js', __FILE__ ), array( 'jquery' ) );
			wp_enqueue_script( 'datatables_script', plugins_url( '/templates/assets/js/datatables.min.js', __FILE__ ) , array( 'jquery' ), MODAL_SURVEY_VERSION, true );
			wp_enqueue_script( 'modal_survey_script', plugins_url('/templates/assets/js/modal_survey.js', __FILE__ ), array( 'jquery' ), MODAL_SURVEY_VERSION );
			wp_register_script('modal_survey_admin', plugins_url( '/templates/assets/js/modal_survey_admin.js', __FILE__ ) , array( 'jquery','jquery_timepicker_script', 'jquery-effects-drop', 'jquery-ui-accordion', 'jquery-ui-tabs', 'jquery-ui-sortable', 'jquery-ui-dialog', 'jquery-ui-slider', 'jquery-ui-tooltip', 'gradx_script', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-autocomplete', 'jquery-ui-widget', 'datatables_script'), MODAL_SURVEY_VERSION, true );
			wp_enqueue_script( "animatenumber_script", plugins_url('/templates/assets/js/jquery.animateNumber.min.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( 'modal_survey_admin', 'sspa_params', array( 
				'plugin_url' => plugins_url( '' , __FILE__ ), 
				'status' => sanitize_text_field( $_REQUEST[ 'status' ] ), 
				'admin_url' => admin_url( 'admin-ajax.php'), 
				'adminpage_url' => admin_url( 'admin.php?page=modal_survey_savedforms' ),
				"languages"=>array( "chartalready" => esc_html__( 'Another chart has been already added!', MODAL_SURVEY_TEXT_DOMAIN ),
									 "conditionsmust" => esc_html__( 'You must set all condition parameters!', MODAL_SURVEY_TEXT_DOMAIN ),
									 "conditionemptyorconflict" => esc_html__( 'Advanced conditions bulk set is empty or it has a conflict. Each advanced conditions must contain AND or OR statements only, it is not possible to mix them in the same condition!', MODAL_SURVEY_TEXT_DOMAIN ),
									 "conditionmissingandor" => esc_html__( 'Missing AND/OR from the end of the bulk condition set!', MODAL_SURVEY_TEXT_DOMAIN ),
									 "conditionalreadyhasandor" => esc_html__( 'The bulk set already has AND/OR statement at the end, please add a condition or rebuild the set.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "finaltime" => esc_html__( 'Time is Up', MODAL_SURVEY_TEXT_DOMAIN ),
									 "redirectioninfo" => esc_html__( 'If you set the End Delay to 0sec, then the redirection will be automatically disabled!', MODAL_SURVEY_TEXT_DOMAIN ),
									 "finalscore" => esc_html__( 'Final Score', MODAL_SURVEY_TEXT_DOMAIN ),
									 "advcondblockedit" => esc_html__( 'Editing Advanced Condition is not available, please remove and add it again.', MODAL_SURVEY_TEXT_DOMAIN ),			 
									 "correctanswers" => esc_html__( 'Correct Answers', MODAL_SURVEY_TEXT_DOMAIN ),
									 "score" => esc_html__( 'Score', MODAL_SURVEY_TEXT_DOMAIN ),
									 "avgscore" => esc_html__( 'Average Score', MODAL_SURVEY_TEXT_DOMAIN ),
									 "failedtoexport" => esc_html__( 'Failed to Export', MODAL_SURVEY_TEXT_DOMAIN ),
									 "question" => esc_html__( 'Question', MODAL_SURVEY_TEXT_DOMAIN ),
									 "category" => esc_html__( 'Category', MODAL_SURVEY_TEXT_DOMAIN ),
									 "higherthan" => esc_html__( 'higher than', MODAL_SURVEY_TEXT_DOMAIN ),
									 "equalwith" => esc_html__( 'equal with', MODAL_SURVEY_TEXT_DOMAIN ),
									 "lowerthan" => esc_html__( 'lower than', MODAL_SURVEY_TEXT_DOMAIN ),
									 "invalidurl" => esc_html__( 'Invalid URL has been specified!', MODAL_SURVEY_TEXT_DOMAIN ),
									 "then" => esc_html__( 'then', MODAL_SURVEY_TEXT_DOMAIN ),
									 "redirectto" => esc_html__( 'redirect to', MODAL_SURVEY_TEXT_DOMAIN ),
									 "dmes" => esc_html__( 'display message at the end', MODAL_SURVEY_TEXT_DOMAIN ),
									 "dsoct" => esc_html__( 'set social sharing title to', MODAL_SURVEY_TEXT_DOMAIN ),
									 "dsocd" => esc_html__( 'set social sharing description to', MODAL_SURVEY_TEXT_DOMAIN ),
									 "dsoci" => esc_html__( 'set social sharing image URL to', MODAL_SURVEY_TEXT_DOMAIN ),
									 "dindr" => esc_html__( 'display individual rating chart at the end', MODAL_SURVEY_TEXT_DOMAIN ),
									 "dinds" => esc_html__( 'display individual score chart at the end', MODAL_SURVEY_TEXT_DOMAIN ),
									 "dindc" => esc_html__( 'display individual correct answers chart at the end', MODAL_SURVEY_TEXT_DOMAIN ),
									 "remove" => esc_html__( 'Remove', MODAL_SURVEY_TEXT_DOMAIN ),
									 "addimage" => esc_html__( 'Add Image', MODAL_SURVEY_TEXT_DOMAIN ),
									 "newsurvey" => esc_html__( 'New Survey', MODAL_SURVEY_TEXT_DOMAIN ),
									 "saveerror" => esc_html__( 'Error during the save process', MODAL_SURVEY_TEXT_DOMAIN ),
									 "serror" => esc_html__( 'ERROR', MODAL_SURVEY_TEXT_DOMAIN ),
									 "successcreate" => esc_html__( 'Successful! Redirecting to the survey form.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "answeroptions" => esc_html__( 'Answer Options', MODAL_SURVEY_TEXT_DOMAIN ),
									 "answer" => esc_html__( 'Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "removeanswer" => esc_html__( 'Remove Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgwidthhint" => esc_html__( 'Set the width of the image in pixel or percentage. Eg.: 150px or 50%', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgheighthint" => esc_html__( 'Set the width of the image in pixel or percentage. Eg.: 150px or 50%', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgwidth" => esc_html__( 'Image Width', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgheight" => esc_html__( 'Image Height', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgwidthp" => esc_html__( '120px', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgheightp" => esc_html__( '120px', MODAL_SURVEY_TEXT_DOMAIN ),
									 "scorehint" => esc_html__( 'Enter the score number for this answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "answerscore" => esc_html__( 'Answer Score', MODAL_SURVEY_TEXT_DOMAIN ),
									 "number" => esc_html__( 'Number', MODAL_SURVEY_TEXT_DOMAIN ),
									 "setcorrect" => esc_html__( 'Set this answer as correct answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "setcorrect2" => esc_html__( 'Set as Correct', MODAL_SURVEY_TEXT_DOMAIN ),
									 "alreadyopena" => esc_html__( 'You already added an open answer to this question.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "alreadydatea" => esc_html__( 'You already added a date answer to this question.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "alreadynuma" => esc_html__( 'You already added a numeric answer to this question.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "alreadyselecta" => esc_html__( 'You already added a select answer to this question.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "openta" => esc_html__( 'Open Text Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "typea" => esc_html__( 'Type your answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "minvalue" => esc_html__( 'Minimum value', MODAL_SURVEY_TEXT_DOMAIN ),
									 "maxvalue" => esc_html__( 'Maximum value', MODAL_SURVEY_TEXT_DOMAIN ),
									 "stepvalue" => esc_html__( 'Step', MODAL_SURVEY_TEXT_DOMAIN ),
									 "listvalues" => esc_html__( 'List of values', MODAL_SURVEY_TEXT_DOMAIN ),									 
									 "selectvalues" => esc_html__( 'Enter comma separated list of the selectable values', MODAL_SURVEY_TEXT_DOMAIN ),
									 "selectdate" => esc_html__( 'Select a date', MODAL_SURVEY_TEXT_DOMAIN ),
									 "selectnum" => esc_html__( 'Set the number', MODAL_SURVEY_TEXT_DOMAIN ),
									 "selectanswer" => esc_html__( 'Select from the list', MODAL_SURVEY_TEXT_DOMAIN ),
									 "answer_normal_title" => esc_html__( 'Normal Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "answer_opentext_title" => esc_html__( 'Open Text Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "answer_date_title" => esc_html__( 'Date Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "answer_numeric_title" => esc_html__( 'Numeric Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "answer_select_title" => esc_html__( 'List Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "autocomplete" => esc_html__( 'Autocomplete', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtooltip" => esc_html__( 'Custom Tooltip', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtooltipph" => esc_html__( 'Enter the answer tooltip here or leave it empty.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtooltipphq" => esc_html__( 'Enter the question tooltip here or leave it empty.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "converttextarea" => esc_html__( 'Multiline', MODAL_SURVEY_TEXT_DOMAIN ),
									 "questionoptions" => esc_html__( 'Question Options', MODAL_SURVEY_TEXT_DOMAIN ),
									 "specopta" => esc_html__( 'Specify how many answers can be selectable by the users', MODAL_SURVEY_TEXT_DOMAIN ),
									 "optionalanswers" => esc_html__( 'Optional Answers', MODAL_SURVEY_TEXT_DOMAIN ),
									 "specreqa" => esc_html__( 'Define how many answers needs to be selected by the user', MODAL_SURVEY_TEXT_DOMAIN ),
									 "requireda" => esc_html__( 'Required Answers', MODAL_SURVEY_TEXT_DOMAIN ),
									 "setrating" => esc_html__( 'Set this question as a Rating Question, it will convert answers to stars', MODAL_SURVEY_TEXT_DOMAIN ),
									 "setratingq" => esc_html__( 'Set as Rating Question', MODAL_SURVEY_TEXT_DOMAIN ),
									 "removequestion" => esc_html__( 'Remove Question', MODAL_SURVEY_TEXT_DOMAIN ),
									 "duplicatequestion" => esc_html__( 'Duplicate Question', MODAL_SURVEY_TEXT_DOMAIN ),
									 "no" => esc_html__( 'No', MODAL_SURVEY_TEXT_DOMAIN ),
									 "addopen" => esc_html__( 'Add New Open Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "addanswer" => esc_html__( 'Add New Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "demoq1" => esc_html__( 'Was this information helpful?', MODAL_SURVEY_TEXT_DOMAIN ),
									 "demoq2" => esc_html__( 'Do you like this website?', MODAL_SURVEY_TEXT_DOMAIN ),
									 "demoq3" => esc_html__( 'Did you find this website easily?', MODAL_SURVEY_TEXT_DOMAIN ),
									 "demoq4" => esc_html__( 'Did you find this website through Search Engine?', MODAL_SURVEY_TEXT_DOMAIN ),
									 "demoq5" => esc_html__( 'Did you already bookmark this website?', MODAL_SURVEY_TEXT_DOMAIN ),
									 "demoq6" => esc_html__( 'Do you like this survey?', MODAL_SURVEY_TEXT_DOMAIN ),
									 "demoq7" => esc_html__( 'Do you visit this website first time?', MODAL_SURVEY_TEXT_DOMAIN ),
									 "demoq8" => esc_html__( 'Are you employed?', MODAL_SURVEY_TEXT_DOMAIN ),
									 "save" => esc_html__( 'SAVE', MODAL_SURVEY_TEXT_DOMAIN ),
									 "update" => esc_html__( 'UPDATE', MODAL_SURVEY_TEXT_DOMAIN ),
									 "saved" => esc_html__( 'SAVED', MODAL_SURVEY_TEXT_DOMAIN ),
									 "updated" => esc_html__( 'UPDATED', MODAL_SURVEY_TEXT_DOMAIN ),
									 "tryagain" => esc_html__( 'TRY AGAIN', MODAL_SURVEY_TEXT_DOMAIN ),
									 "deletesurvey" => esc_html__( 'Delete Survey', MODAL_SURVEY_TEXT_DOMAIN ),
									 "cancel" => esc_html__( 'Cancel', MODAL_SURVEY_TEXT_DOMAIN ),
									 "reset" => esc_html__( 'Reset Survey', MODAL_SURVEY_TEXT_DOMAIN ),
									 "exportcharts" => esc_html__( 'Export Charts', MODAL_SURVEY_TEXT_DOMAIN ),
									 "exportwcharts" => esc_html__( 'Export without Charts', MODAL_SURVEY_TEXT_DOMAIN ),
									 "duplicate" => esc_html__( 'Duplicate', MODAL_SURVEY_TEXT_DOMAIN ),
									 "cdelete" => esc_html__( 'Delete', MODAL_SURVEY_TEXT_DOMAIN ),
									 "revert" => esc_html__( 'Yes, I want the default configuration.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "yes" => esc_html__( 'Yes', MODAL_SURVEY_TEXT_DOMAIN ),
									 "cexporttocsv" => esc_html__( 'Export to CSV', MODAL_SURVEY_TEXT_DOMAIN ),
									 "cexporttojson" => esc_html__( 'Export to JSON', MODAL_SURVEY_TEXT_DOMAIN ),
									 "cexporttopdf" => esc_html__( 'Export to PDF', MODAL_SURVEY_TEXT_DOMAIN ),
									 "cexporttoxml" => esc_html__( 'Export to XML', MODAL_SURVEY_TEXT_DOMAIN ),
									 "cexporttoxls" => esc_html__( 'Export to XLS', MODAL_SURVEY_TEXT_DOMAIN ),
									 "cexporttotxt" => esc_html__( 'Export to TXT', MODAL_SURVEY_TEXT_DOMAIN ),
									 "surveyexists1" => esc_html__( 'Survey name already exists, import failed!', MODAL_SURVEY_TEXT_DOMAIN ),
									 "surveyexists2" => esc_html__( 'Survey name is required, import failed!', MODAL_SURVEY_TEXT_DOMAIN ),
									 "isready" => esc_html__( 'is ready', MODAL_SURVEY_TEXT_DOMAIN ),
									 "dhere" => esc_html__( 'download from here.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "activeanswer" => esc_html__( 'Active Answer - click to change', MODAL_SURVEY_TEXT_DOMAIN ),
									 "inactiveanswer" => esc_html__( 'Inactive Answer - click to change', MODAL_SURVEY_TEXT_DOMAIN ),
									 "redirecttooltip" => esc_html__( 'Specify the question number to redirect the survey in case the user choose this answer.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "clredirection" => esc_html__( 'Redirection', MODAL_SURVEY_TEXT_DOMAIN ),
									 "redplaceholder" => esc_html__( 'Enter the question number or leave it empty.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "category_tooltip" => esc_html__( 'Specify the category name to cumulate the score into categories.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "category" => esc_html__( 'Category', MODAL_SURVEY_TEXT_DOMAIN ),
									 "category_placeholder" => esc_html__( 'Enter the category name or leave it empty.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "qoptwidth" => esc_html__( 'Set the width of the image in pixel or percentage. Eg.: 150px or 50%', MODAL_SURVEY_TEXT_DOMAIN ),
									 "qimgwidth" => esc_html__( 'Image Width', MODAL_SURVEY_TEXT_DOMAIN ),
									 "qimgwidth_pl" => esc_html__( '120px', MODAL_SURVEY_TEXT_DOMAIN ),
									 "qoptheight" => esc_html__( 'Set the height of the image in pixel or percentage. Eg.: 150px or 50%', MODAL_SURVEY_TEXT_DOMAIN ),
									 "qimgheight" => esc_html__( 'Image Height', MODAL_SURVEY_TEXT_DOMAIN ),
									 "qimgheight_pl" => esc_html__( '120px', MODAL_SURVEY_TEXT_DOMAIN ),
									 "qcategory_tooltip" => esc_html__( 'Specify the category name to cumulate the score into categories.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "qcategory" => esc_html__( 'Category', MODAL_SURVEY_TEXT_DOMAIN ),
									 "qcategory_pl" => esc_html__( 'Enter the category name or leave it empty.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "floatsave" => esc_html__( 'SAVE SURVEY', MODAL_SURVEY_TEXT_DOMAIN ),
									 "noselected" => esc_html__( 'Nothing has been selected', MODAL_SURVEY_TEXT_DOMAIN ),
									 "addanswer_normal" => esc_html__( 'Normal Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "addanswer_open" => esc_html__( 'Open Text Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "addanswer_numeric" => esc_html__( 'Numeric Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "addanswer_date" => esc_html__( 'Date Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "addanswer_select" => esc_html__( 'List Answer', MODAL_SURVEY_TEXT_DOMAIN ),
									 "failedzip" => esc_html__( 'Failed to create the ZIP file', MODAL_SURVEY_TEXT_DOMAIN ),
									 "hidelabel" => esc_html__( 'Hide Label', MODAL_SURVEY_TEXT_DOMAIN ),
									 "hidelabel_hint" => esc_html__( 'Hide the answer text when you using image instead', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtext_hint" => esc_html__( 'ID of input field, eg.: FNAME', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtext_name_hint" => esc_html__( 'Name of custom field, eg.: First Name', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtext_name_placeholder" => esc_html__( 'Name', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtext_warning" => esc_html__( 'Warning text for the field if it is required, eg.: Firstname field is mandatory', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtext_warning_placeholder" => esc_html__( 'Warning', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtext_min" => esc_html__( 'Minimum character length for required field', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtext_required" => esc_html__( 'Check this if the field is mandatory', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtext_remove" => esc_html__( 'Remove Text Field', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customradio_hint" => esc_html__( 'ID of radio field, eg.: GENDER', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customradio_name_hint" => esc_html__( 'Name and value pair for custom field, eg.: Female:female,Male:male', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customradio_name_placeholder" => esc_html__( 'Female:female,Male:male', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customradio_required" => esc_html__( 'Check this if the field is mandatory', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customradio_remove" => esc_html__( 'Remove Radio Field', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtextarea_hint" => esc_html__( 'ID of textarea field, eg.: Description', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtextarea_name_hint" => esc_html__( 'Placeholder for custom field, eg.: Description', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtextarea_name_placeholder" => esc_html__( 'Description', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtextarea_warning" => esc_html__( 'Warning text for the field if it is required, eg.: Description field is mandatory', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtextarea_warning_placeholder" => esc_html__( 'Warning', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtextarea_min" => esc_html__( 'Minimum character length for required field', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtextarea_required" => esc_html__( 'Check this if the field is mandatory', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customtextarea_remove" => esc_html__( 'Remove Textarea Field', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customselect_hint" => esc_html__( 'ID of select field, eg.: FRUITS', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customselect_name_hint" => esc_html__( 'Name and value pair for custom field, eg.: Select from the list,Apple:apple,Orange:orange,Lemon:lemon', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customselect_name_placeholder" => esc_html__( 'Select from the list,Apple:applevalue,Orange:orangevalue,Lemon:lemonvalue', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customselect_required" => esc_html__( 'Check this if the field is mandatory', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customselect_remove" => esc_html__( 'Remove Select Field', MODAL_SURVEY_TEXT_DOMAIN ),
 									 "customhidden_hint" => esc_html__( 'ID of hidden field, eg.: SIGNUP', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customhidden_name_hint" => esc_html__( 'Value of the field, eg.: blog name', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customhidden_name_placeholder" => esc_html__( 'blog name', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customhidden_remove" => esc_html__( 'Remove Hidden Field', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customcheckbox_hint" => esc_html__( 'ID of checkbox field, eg.: CONFIRMATION', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customcheckbox_name_hint" => esc_html__( 'Text for checkbox field, eg.: Confirm to subscribe to our Mail List', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customcheckbox_name_placeholder" => esc_html__( 'Please confirm your subscription', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customcheckbox_required" => esc_html__( 'Check this if the field is mandatory', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customcheckbox_remove" => esc_html__( 'Remove Checkbox Field', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customhtml_remove" => esc_html__( 'Remove HTML Field', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customhtml_pos1" => esc_html__( 'At the top of the form', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customhtml_pos2" => esc_html__( 'Above the Send button', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customhtml_pos3" => esc_html__( 'Below the Send button', MODAL_SURVEY_TEXT_DOMAIN ),
									 "customhtml_tip" => esc_html__( 'Tip: Save the HTML Box with any content then refresh the page to see the WYSIWYG / HTML editor instead of this textarea', MODAL_SURVEY_TEXT_DOMAIN ),
									 "rem_cond" => esc_html__( 'Remove Condition', MODAL_SURVEY_TEXT_DOMAIN ),
									 "ed_cond" => esc_html__( 'Edit Condition', MODAL_SURVEY_TEXT_DOMAIN ),
									 "add_cond" => esc_html__( 'Add Condition', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgpos" => esc_html__( 'Image Position', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgdef" => esc_html__( 'Default', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgtop" => esc_html__( 'Top', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgbot" => esc_html__( 'Bottom', MODAL_SURVEY_TEXT_DOMAIN ),
									 "imgontop" => esc_html__( 'Text on Top', MODAL_SURVEY_TEXT_DOMAIN ),
				  					 "next_button" => esc_html__( 'NEXT', MODAL_SURVEY_TEXT_DOMAIN ),
									 "back_button" => esc_html__( 'BACK', MODAL_SURVEY_TEXT_DOMAIN ),
									 "admincomment" => esc_html__( 'Admin Comment', MODAL_SURVEY_TEXT_DOMAIN ),
									 "admincomment_placeholder" => esc_html__( 'Enter answer comment to appear on the reports or leave it empty.', MODAL_SURVEY_TEXT_DOMAIN ),
									 "play_survey" => esc_html__( 'PLAY SURVEY', MODAL_SURVEY_TEXT_DOMAIN ),
									 "close_survey" => esc_html__( 'CLOSE SURVEY', MODAL_SURVEY_TEXT_DOMAIN ),
									 "section_content" => esc_html__( 'Section Content (displayed above the question)', MODAL_SURVEY_TEXT_DOMAIN ),
									 "section_desc" => esc_html__( 'Enter the plain or HTML content here to show it before the question or leave it empty.', MODAL_SURVEY_TEXT_DOMAIN )
									)
				) );
			wp_enqueue_script( 'modal_survey_admin' );
			wp_enqueue_script('modal_survey_wizard_script', plugins_url('/templates/assets/js/modal_survey_wizard.js' , __FILE__ ), array( 'jquery', 'jquery-ui-tooltip', 'jquery-effects-drop' ), MODAL_SURVEY_VERSION, true );
			wp_enqueue_style( 'modal_survey_themes', plugins_url( '/templates/assets/css/themes.css', __FILE__ ), array(), MODAL_SURVEY_VERSION );
		}

		/**
		* initialize datas on wp admin
		**/
		public function admin_init() {
		$settings_page = '';
			if ( isset( $_REQUEST[ 'page' ] ) ) {
				$settings_page = sanitize_text_field( $_REQUEST[ 'page' ] );
			}
			if ( strpos( $settings_page, 'modal_survey' ) !== false ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_custom_scripts_and_styles' ) );
			}
			else {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_adminpost_custom_scripts_and_styles' ) );
			}
			// Possibly do additional admin_init tasks
			// register your custom settings - general settings
			register_setting('modal_survey-group', 'setting_globalsurvey');
			register_setting('modal_survey-group', 'setting_display_once');
			register_setting('modal_survey-group', 'setting_display_once_per_filled');
			register_setting('modal_survey-group', 'setting_keep_settings');
			register_setting('modal_survey-group', 'setting_minify');
			register_setting('modal_survey-group', 'setting_save_votes');
			register_setting('modal_survey-group', 'setting_remember_users');
			register_setting('modal_survey-group', 'setting_save_ip');
			register_setting('modal_survey-group', 'setting_pdf_header');
			register_setting('modal_survey-group', 'setting_pdf_font');
			register_setting('modal_survey-group', 'setting_custom_individual_export');
			register_setting('modal_survey-group', 'setting_plugininit');
			register_setting('modal_survey-group', 'setting_restapi');
			register_setting('modal_survey-group', 'setting_restapi_privileges');
			register_setting('modal_survey-group', 'setting_participants_entries');
			// add your settings section
			add_settings_section('modal_survey-section', '', array($this, 'settings_section_modal_survey'), 'modal_survey');
			// add your setting's fields
			add_settings_field('modal_survey-setting_globalsurvey', esc_html__( 'Set Global Survey', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_globalsurvey', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN ) => "on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Automatically set the Global Survey checkbox in a new survey', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_display_once', esc_html__( 'Display Once per User', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_display_once', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Display the survey only on first visit. OFF will show to the same user more than just once.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_display_once_per_filled', esc_html__( 'Display Once per Filled Out', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_display_once_per_filled', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Do not display if already filled out. OFF will display the same survey again for users who already filled it out. ', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_keep_settings', esc_html__( 'Keep Settings after Uninstall', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_keep_settings', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Keeps your settings during uninstall. Helps to protect your saved datas when updating to a new version.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_minify', esc_html__( 'Minify Scripts', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_minify', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Use minified and obfuscated JavaScript files on frontend.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_save_votes', esc_html__( 'Save Participants Votes', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_save_votes', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Save the participants votes to display them in the Participants page on the admin', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_remember_users', esc_html__( 'Remember Users', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_remember_users', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Using cookies to remember users from the same computer', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_save_ip', esc_html__( 'Save IP Address', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_save_ip', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Turning ON or OFF saving the IP Address of the participants', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_pdf_header', esc_html__( 'PDF Export Header Text', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_textarea'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_pdf_header', 'field_value' => '', 'other' => 'rows="10" cols="70"'));
			add_settings_field('modal_survey-setting_pdf_font', esc_html__( 'PDF Export Font Family', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_select'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_pdf_font', 'field_value' => '', 'other' => 'pdffont', 'extrahtml' => '<div class="arrow_box2"><p>' . esc_html__( 'Set the Font Family to display special characters correctly.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_custom_individual_export', esc_html__( 'PDF Individual Export', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_checkbox'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_custom_individual_export', 'field_value' => '', 'options' => array( esc_html__( 'Total Votes', MODAL_SURVEY_TEXT_DOMAIN )=>"totalvotes", esc_html__( 'Other Answers', MODAL_SURVEY_TEXT_DOMAIN )=>"otheranswers" ), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Customize the content of the Individual Export file.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_plugininit', esc_html__( 'Initialize plugin', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_select'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_plugininit', 'field_value' => '', 'other' => 'plugininit', 'extrahtml' => '<div class="arrow_box2"><p>' . esc_html__( 'Change the initialization hook if you have trouble with the survey display ', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_restapi', esc_html__( 'Rest API', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_restapi', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Turning ON or OFF providing Endpoints for Rest API', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_restapi_privileges', esc_html__( 'Rest  API Privileges', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_select'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_restapi_privileges', 'field_value' => '', 'other' => 'restapi_privileges', 'extrahtml' => '<div class="arrow_box2"><p>' . esc_html__( 'Configure the access to the Rest API data', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_participants_entries', esc_html__( 'Delete Participants older than', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_select'), 'modal_survey', 'modal_survey-section', array('field' => 'setting_participants_entries', 'field_value' => '', 'other' => 'participants_entries', 'extrahtml' => '<div class="arrow_box2"><p>' . esc_html__( 'Automatically check and removes the old participants in each 12 hours to keep the database clean.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));

			register_setting('modal_survey_social-group', 'setting_social');
			register_setting('modal_survey_social-group', 'setting_social_sites');
			register_setting('modal_survey_social-group', 'setting_social_metas');
			register_setting('modal_survey_social-group', 'setting_social_style');
			register_setting('modal_survey_social-group', 'setting_social_pos');
			register_setting('modal_survey_social-group', 'setting_fbappid');
			// add your settings section
			add_settings_section('modal_survey_social-section', '', array($this, 'settings_section_modal_survey'), 'modal_survey_social');
			// add your setting's fields
			add_settings_field('modal_survey-setting_social', esc_html__( 'Social Sharing', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey_social', 'modal_survey_social-section', array('field' => 'setting_social', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Enable Social Sharing at the end of the Surveys.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_social_metas', esc_html__( 'Embed Social Metas', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_radio'), 'modal_survey_social', 'modal_survey_social-section', array('field' => 'setting_social_metas', 'field_value' => '', 'options' => array(esc_html__( 'On', MODAL_SURVEY_TEXT_DOMAIN )=>"on",esc_html__( 'Off', MODAL_SURVEY_TEXT_DOMAIN )=>"off"), 'other' => '', 'extrahtml' => '<div class="arrow_box"><p>' . esc_html__( 'Automatically embed og:description, og:url, og:image, og:type and og:site_name based on the current page .', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_social_sites', esc_html__( 'Social Sites', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_checkbox'), 'modal_survey_social', 'modal_survey_social-section', array('field' => 'setting_social_sites', 'field_value' => '', 'options' => array( "Facebook"=>"facebook", "Twitter"=>"twitter", "Pinterest"=>"pinterest", "Google Plus"=>"googleplus", "LinkedIn"=>"linkedin" ), 'other' => '', 'extrahtml' => '<div class="arrow_box arrow_box_special"><p>' . esc_html__( 'Set the available social sites for sharing.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_social_style', esc_html__( 'Social Buttons Style', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_select'), 'modal_survey_social', 'modal_survey_social-section', array('field' => 'setting_social_style', 'field_value' => '', 'other' => 'social_style', 'extrahtml' => '<div class="arrow_box2"><p>' . esc_html__( 'Set the style of the Social Buttons', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_social_pos', esc_html__( 'Social Buttons Position', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_select'), 'modal_survey_social', 'modal_survey_social-section', array('field' => 'setting_social_pos', 'field_value' => '', 'other' => 'social_pos', 'extrahtml' => '<div class="arrow_box2"><p>' . esc_html__( 'Position of Social Buttons', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			add_settings_field('modal_survey-setting_fbappid', esc_html__( 'Facebook APP ID', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_text'), 'modal_survey_social', 'modal_survey_social-section', array('field' => 'setting_fbappid', 'field_value' => '', 'other' => 'size="32"', 'extrahtml' => '<div class="arrow_box2"><p>' . esc_html__( 'Specify your FB App ID to set custom title and description for sharing.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>'));
			// register your custom settings - custom CSS settings
			register_setting('modal_survey_customcss-group', 'setting_customcss');
			// add your settings section
			add_settings_section('modal_survey_customcss-section', '', array($this, 'settings_section_modal_survey'), 'modal_survey_customcss');
			add_settings_field('modal_survey-setting_customcss', esc_html__( 'Enter you custom CSS code', MODAL_SURVEY_TEXT_DOMAIN ), array($this, 'settings_field_input_textarea'), 'modal_survey_customcss', 'modal_survey_customcss-section', array('field' => 'setting_customcss', 'field_value' => '', 'other' => 'rows="20" cols="100" placeholder=".class {
	color: #000000;
}"'));
			}
		/**
		* This function provides text inputs for settings fields
		**/
		public function settings_field_input_text( $args ) {
			$other = $args[ 'other' ];
			// Get the field name from the $args array or get the value of this setting
			$field = $args[ 'field' ];
			if ( $args[ 'field_value' ] ) {
				$value = $args[ 'field_value' ];
			}
			else {
				$value = get_option( $field );
			}
			// echo a proper input type="text"
			if ( ! empty( $other ) ) {
				echo sprintf( '<div class="ee-row shortinput"><input type="text" class="ee-control" name="%s" id="%s" value="%s" %s />', $field, $field, $value, $other );
			}
			else {
				echo sprintf( '<div class="ee-row shortinput"><input type="text" class="ee-control" name="%s" id="%s" value="%s" />', $field, $field, $value );
			}
			if ( isset( $args[ 'extrahtml' ] ) ) {
				echo( $args[ 'extrahtml' ] );
			}
			echo '</div>';
		}
		/**
		* This function provides select inputs for settings fields
		**/
		public function settings_field_input_select( $args ) {
			// Get the field name from the $args array or get the value of this setting
			$field = $args['field'];
			if ($args['field_value']) $value = $args['field_value'];
			else $value = get_option($field);
			if ( $args['other'] == 'plugininit' ) {
					echo sprintf('<select name="%s" id="%s">', $field, $field);
					echo('<option value="getfooter" ' . selected( $value, 'getfooter', false ) . '>' . esc_html__( 'when calling get_footer() hook', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="wpfooter" ' . selected( $value, 'wpfooter', false ) . '>' . esc_html__( 'when calling wp_footer() hook', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="aftercontent" ' . selected( $value, 'aftercontent', false ) . '>' . esc_html__( 'when print the content', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('</select>');				
			}
			elseif ( $args['other'] == 'restapi_privileges' ) {
					echo sprintf('<select name="%s" id="%s">', $field, $field);
					echo('<option value="administrator" ' . selected( $value, 'administrator', false ) . '>' . esc_html__( 'Administrator', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="public" ' . selected( $value, 'public', false ) . '>' . esc_html__( 'Public - Without Login', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="editor" ' . selected( $value, 'editor', false ) . '>' . esc_html__( 'Editor', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="author" ' . selected( $value, 'author', false ) . '>' . esc_html__( 'Author', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="contributor" ' . selected( $value, 'contributor', false ) . '>' . esc_html__( 'Contributor', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="subscriber" ' . selected( $value, 'subscriber', false ) . '>' . esc_html__( 'Subscriber', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="superadmin" ' . selected( $value, 'superadmin', false ) . '>' . esc_html__( 'Super Admin', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('</select>');				
			}
			elseif ( $args['other'] == 'participants_entries' ) {
					echo sprintf('<select name="%s" id="%s">', $field, $field);
					echo('<option value="30" ' . selected( $value, '30', false ) . '>' . esc_html__( '30 days', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="1" ' . selected( $value, '1', false ) . '>' . esc_html__( '1 day', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="3" ' . selected( $value, '3', false ) . '>' . esc_html__( '3 days', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="7" ' . selected( $value, '7', false ) . '>' . esc_html__( '7 days', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="60" ' . selected( $value, '60', false ) . '>' . esc_html__( '60 days', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="90" ' . selected( $value, '90', false ) . '>' . esc_html__( '90 days', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="never" ' . selected( $value, 'never', false ) . '>' . esc_html__( 'Never - Use it on your own risk', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('</select>');				
			}
			elseif ( $args['other'] == 'social_style' ) {
					echo sprintf('<select name="%s" id="%s">', $field, $field);
					echo('<option value="default" ' . selected( $value, 'default', false ) . '>' . esc_html__( 'Default', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="default_large" ' . selected( $value, 'default_large', false ) . '>' . esc_html__( 'Default Large', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="clean" ' . selected( $value, 'clean', false ) . '>' . esc_html__( 'Clean', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="clean_large" ' . selected( $value, 'clean_large', false ) . '>' . esc_html__( 'Clean Large', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('</select>');				
			}
			elseif ( $args['other'] == 'social_pos' ) {
					echo sprintf('<select name="%s" id="%s">', $field, $field);
					echo('<option value="bottom" ' . selected( $value, 'bottom', false ) . '>' . esc_html__( 'Always visible', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="end" ' . selected( $value, 'end', false ) . '>' . esc_html__( 'Display at the end only', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('<option value="endcontent" ' . selected( $value, 'endcontent', false ) . '>' . esc_html__( 'Display at the end in the content', MODAL_SURVEY_TEXT_DOMAIN ) . '</option>' );
					echo('</select>');				
			}
			elseif ( $args['other'] == 'pdffont' ) {
					echo sprintf('<select name="%s" id="%s">', $field, $field);
					echo('<option value="aealarabiya" ' . selected( $value, 'aealarabiya', false ) . '>AlArabiya</option>' );
					echo('<option value="courier" ' . selected( $value, 'courier', false ) . '>Courier</option>' );
					echo('<option value="dejavusans" ' . selected( $value, 'dejavusans', false ) . '>DejaVuSans</option>' );
					echo('<option value="dejavuserif" ' . selected( $value, 'dejavuserif', false ) . '>DejaVuSerif</option>' );
					echo('<option value="aefurat" ' . selected( $value, 'aefurat', false ) . '>Furat</option>' );
					echo('<option value="freemono" ' . selected( $value, 'freemono', false ) . '>FreeMono</option>' );
					echo('<option value="helvetica" ' . selected( $value, 'helvetica', false ) . '>Helvetica</option>' );
					echo('<option value="hysmyeongjostdmedium" ' . selected( $value, 'hysmyeongjostdmedium', false ) . '>MyungJo Medium (Korean)</option>' );
					echo('<option value="msungstdlight" ' . selected( $value, 'msungstdlight', false ) . '>MSung Light (Trad. Chinese)</option>' );
					echo('<option value="kozminproregular" ' . selected( $value, 'kozminproregular', false ) . '>Kozuka Mincho Pro (Japanese Serif)</option>' );
					echo('<option value="stsongstdlight" ' . selected( $value, 'stsongstdlight', false ) . '>STSong Light (Simp. Chinese)</option>' );
					echo('<option value="pdfasymbol" ' . selected( $value, 'pdfasymbol', false ) . '>Symbol</option>' );
					echo('<option value="pdfatimes" ' . selected( $value, 'pdfatimes', false ) . '>Times</option>' );
					echo('<option value="times" ' . selected( $value, 'times', false ) . '>Times-Roman</option>' );
					echo('<option value="pdfazapfdingbats" ' . selected( $value, 'pdfazapfdingbats', false ) . '>Zapfdingbats</option>' );
					echo('</select>');				
			}
			else
			{
			if (isset($args['min'])) $field_min = $args['min'];
			if (isset($args['max'])) $field_max = $args['max'];
			if (isset($args['default'])) $field_default = $args['default'];
				if (!isset($field_min)) $field_min = 1;
				if (!isset($field_max)) $field_max = 10;
				if (!isset($field_default)) $field_default = 5;
			// echo a proper select element
				echo sprintf('<select name="%s" id="%s">', $field, $field);
				for($i=$field_min;$i<=$field_max;$i++) {
					$selected = '';
					if ($value==$i) $selected = 'selected = "true"';
					if (!$value AND $i==$field_default) $selected = 'selected = "true"';
					echo('<option value="'.$i.'" '.$selected.'>'.$i.'</option>');
				}
				echo('</select>');
			}
			if ( isset( $args[ 'extrahtml' ] ) ) {
				echo( $args[ 'extrahtml' ] );
			}
		}
		/**
		* This function provides radio inputs for settings fields
		**/
        public function settings_field_input_checkbox($args) {
			$key = '';
             $other = $args[ 'other' ];
            $options = $args[ 'options' ];
 			// Get the field name from the $args array or get the value of this setting
			$field = $args['field'];
			if ( $args[ 'field_value' ] ) {
				$value = $args[ 'field_value' ];
			}
			else {
				$value = get_option($field);
			}
            // echo a proper input type="checkbox"
			foreach ( $options as $key=>$opt ) {
				if ( is_array( $value ) ) {
					if ( in_array( $opt, $value ) ) {
						$selected = 'checked="true"';
					}
					else {
						$selected = '';
					}
				}
				else {
					$selected = '';
				}
				echo sprintf('<input type="checkbox" class="ms-settings-checkbox" name="%s[]" id="%s%s" '.$selected.' value="%s" /><label for="%s%s"> ' . $key . '</label>', $field, $field, $opt, $opt, $field, $opt);
			}
			if (isset($args['extrahtml'])) echo($args['extrahtml']);
		}
		/**
		* This function provides radio inputs for settings fields
		**/
        public function settings_field_input_radio($args) {
			$key = '';
             $other = $args['other'];
            $options = $args['options'];
 			// Get the field name from the $args array or get the value of this setting
			$field = $args['field'];
			if ($args['field_value']) $value = $args['field_value'];
			else $value = get_option($field);
            // echo a proper input type="radio"
			foreach( $options as $key=>$opt ) {
				if ( $value == $opt OR ( ! $value AND $opt == "off" ) ) {
					$selected = 'checked="true"';
				}
				else {
					$selected = "";
				}
				echo sprintf('<input type="radio" name="%s" id="%s%s" '.$selected.' value="%s" /><label for="%s%s"> '.$key.'</label> ', $field, $field, $opt, $opt, $field, $opt );
			}
			if (isset($args['extrahtml'])) echo($args['extrahtml']);
		}

		/**
		* This function provides textarea inputs for settings fields
		**/
		public function settings_field_input_textarea($args) {
			$other = $args['other'];
			// Get the field name from the $args array or get the value of this setting
			$field = $args['field'];
			if ($args['field_value']) $value = $args['field_value'];
			else $value = get_option($field);
			// echo a proper input type="textarea"
			if ( ! empty( $other ) ) {
				echo sprintf( '<textarea name="%s" id="%s" %s />%s</textarea>', $field, $field, $other, $value );
			}
			else {
				echo sprintf( '<textarea name="%s" id="%s" />%s</textarea>', $field, $field, $value );
			}
		}
		/**
		* add a menu
		**/		
		public function add_menu() {
			add_menu_page('Modal Survey', 'Modal Survey', 'manage_options', 'modal_survey', array($this, 'plugin_settings_page'),'dashicons-groups','65.012');
			add_submenu_page('modal_survey', 'Modal Survey', esc_html__( 'Create Survey', MODAL_SURVEY_TEXT_DOMAIN ), 'manage_options', 'modal_survey', array($this, 'plugin_settings_page'));
			add_submenu_page('modal_survey', 'Modal Survey', esc_html__( 'Saved Surveys', MODAL_SURVEY_TEXT_DOMAIN ), 'manage_options', 'modal_survey_savedforms', array($this, 'plugin_settings_page_savedforms'));
			add_submenu_page('modal_survey', 'Modal Survey', esc_html__( 'Participants', MODAL_SURVEY_TEXT_DOMAIN ), 'manage_options', 'modal_survey_participants', array($this, 'plugin_settings_page_participants'));
			add_submenu_page('modal_survey', 'Modal Survey', esc_html__( 'General Settings', MODAL_SURVEY_TEXT_DOMAIN ), 'manage_options', 'modal_survey_generalsettings', array($this, 'plugin_settings_page_generalsettings'));
			add_submenu_page('modal_survey', 'Modal Survey', esc_html__( 'Social Settings', MODAL_SURVEY_TEXT_DOMAIN ), 'manage_options', 'modal_survey_socialsettings', array($this, 'plugin_settings_page_socialsettings'));
			add_submenu_page('modal_survey', 'Modal Survey', esc_html__( 'Import', MODAL_SURVEY_TEXT_DOMAIN ), 'manage_options', 'modal_survey_import', array($this, 'plugin_settings_page_import'));
			add_submenu_page('modal_survey', 'Modal Survey', esc_html__( 'Custom CSS', MODAL_SURVEY_TEXT_DOMAIN ), 'manage_options', 'modal_survey_customcss', array($this, 'plugin_settings_page_customcss'));
			add_submenu_page('modal_survey', 'Modal Survey', esc_html__( 'Update', MODAL_SURVEY_TEXT_DOMAIN ), 'manage_options', 'modal_survey_update', array($this, 'plugin_settings_page_update'));
			add_submenu_page('modal_survey', 'Modal Survey', esc_html__( 'Help', MODAL_SURVEY_TEXT_DOMAIN ), 'manage_options', 'modal_survey_help', array($this, 'plugin_settings_page_help'));
		}
		/**
		* Menu Callback
		**/		
		public function plugin_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			// Render the settings template
			include(sprintf("%s/templates/settings_newform.php", dirname(__FILE__)));
		}
		public function plugin_settings_page_savedforms() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			// Render the settings template
			include(sprintf("%s/templates/settings_savedforms.php", dirname(__FILE__)));
		}
		public function plugin_settings_page_participants() {
			global $msplugininit_answer_array;
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			// Render the settings template
			include(sprintf("%s/templates/settings_participants.php", dirname(__FILE__)));
			wp_localize_script( 'modal_survey_answer_script_init', 'ms_answer_init_params', $msplugininit_answer_array );
			wp_enqueue_script( 'modal_survey_answer_script_init' );
		}
		public function plugin_settings_page_update() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			// Render the settings template
			include(sprintf("%s/templates/settings_update.php", dirname(__FILE__)));
		}
		public function plugin_settings_page_generalsettings() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			// Render the settings template
			include(sprintf("%s/templates/settings_general.php", dirname(__FILE__)));
		}
		public function plugin_settings_page_socialsettings() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			// Render the settings template
			include(sprintf("%s/templates/settings_social.php", dirname(__FILE__)));
		}
		public function plugin_settings_page_help() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			// Render the settings template
			include(sprintf("%s/templates/settings_help.php", dirname(__FILE__)));
		}
		public function plugin_settings_page_customcss() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			// Render the settings template
			include(sprintf("%s/templates/settings_customcss.php", dirname(__FILE__)));
		}
		public function plugin_settings_page_import() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', MODAL_SURVEY_TEXT_DOMAIN ) );
			}
			// Render the settings template
			include(sprintf("%s/templates/settings_import.php", dirname(__FILE__)));
		}

		public function settings_section_modal_survey() {
		
		}

		public function send_admin_email( $endcontent, $sopts, $request, $survey_id ) {
		global $wpdb;
			if ( $endcontent == "true" && ! empty( $sopts[ 144 ] ) && isset( $request[ 'endcontent' ] ) ) {
				$headers = array();
				$attachments = "";
				if ( strpos( $sopts[ 144 ], ',' ) === false ) {
					if ( ! filter_var( $sopts[ 144 ], FILTER_VALIDATE_EMAIL ) && $sopts[ 146 ] == 1 ) {
						die( 'success: Invalid Admin Email Address Format' );
					}
				}
				if ( get_option( 'setting_save_votes' ) != 'on' ) {
					die( 'success: Participants Votes Disabled' );
				}
				if ( ! isset( $_COOKIE[ 'ms-uid' ] ) ) {
					die( 'success: Missing User Cookie' );
				}
				$puid = $wpdb->get_row( $wpdb->prepare( "SELECT autoid, name, email, custom FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE id = %s ", $_COOKIE[ 'ms-uid' ] ) );
				if ( ! isset( $puid->autoid ) ) {
					print( "uid not available " );
					return false;
				}
				$this->expresult = $this->export_survey( 'personal', $puid->autoid . '-' . $survey_id, 'pdf', 'last' );
				if ( file_exists( dirname( __FILE__ ) . '/exports/' . $puid->autoid . '-' . $sid . '.pdf' ) ) {
					$attachments = array( dirname( __FILE__ ) . '/exports/' . $puid->autoid . '-' . $sid . '.pdf' );				
				}
				else {
					print( ' autoresponse attachment failed ' );
				}
				$fullrecords = modal_survey::survey_answers_shortcodes( 
					array ( 'id' => $survey_id, 'data' => 'full-records', 'style' => 'plain', 'limited' => 'no', 'uid' => $_COOKIE[ 'ms-uid' ], 'title' => '<span>', 'session' => 'last' )
				);
				if ( ! empty( $request[ 'cae' ] ) ) {
					$cae = base64_decode( $request[ 'cae' ] );
					$cae_exp = explode( "|", $cae );
					if ( count( $cae_exp ) == 1 ) {
						$sendername = base64_decode( $cae_exp[ 0 ] );
					}
					if ( count( $cae_exp ) > 2 ) {
						$sendername = base64_decode( $cae_exp[ 2 ] );
					}
					if ( count( $cae_exp ) > 1 ) {
						$emailto = base64_decode( $cae_exp[ 1 ] ) . '@' . base64_decode( $cae_exp[ 0 ] );
					}
				}
				if ( empty( $sendername ) ) {
					$sendername = "Modal Survey";
				}
				if ( empty( $emailto ) ) {
					$emailto = $sopts[ 144 ];
				}
//				$headers[] = 'MIME-Version: 1.0' . '\r\n';
				$headers[] = 'From: ' . $sendername . ' <noreply@' . str_replace( "www.", "", $_SERVER[ 'HTTP_HOST' ] ) . '>';
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
				$message = '
				
				' . esc_html__( 'Survey Name:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . ( $fullrecords[ 0 ][ 'datas' ][ 0 ][ 'survey' ] ? $fullrecords[ 0 ][ 'datas' ][ 0 ][ 'survey' ] : '' ) . '
				' . esc_html__( 'Survey URL:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . ( $request[ 'postid' ] ? get_permalink( $request[ 'postid' ] ) : '' ) . ' '. ( $request[ 'postid' ] ? '( ' . get_the_title( $request[ 'postid' ] ) . ' )' : '' ) .'
				' . esc_html__( 'Participant Name:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . ( $puid->name ? $puid->name : esc_html__( 'Anonymous', MODAL_SURVEY_TEXT_DOMAIN ) ) . '
				' . esc_html__( 'Participant Email Address:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . ( $puid->email ? $puid->email : esc_html__( 'Not Specified', MODAL_SURVEY_TEXT_DOMAIN ) ) . '
				';
				if ( ! empty( $puid->custom ) ) {
					foreach ( unserialize( $puid->custom ) as $muc_index=>$muc ) {
						$message .= '' . ucfirst( strtolower( $muc_index ) ) . ': ' . ( ! empty( $muc ) ? $muc : esc_html__( 'Not Specified', MODAL_SURVEY_TEXT_DOMAIN ) ) . '
						';
					}
				}
				$message .= '
				
				';
				$final_score = 0;
				$correct_answers = 0;
				foreach( $fullrecords as $key=>$fr ) {
					$message .= "
					<div style='font-weight: bold;'>" . $fr[ 'title' ] . "</div>";
						foreach( $fr[ 'datas' ] as $ans ) {
								$selectedstyle = "";
							if ( $ans[ 'selected' ] == "true" ) {
								$selectedstyle = " style='background-color: rgb(154, 154, 154);border-radius: 4px;border: 1px solid #000;'";
								$final_score += $ans[ 'score' ];
								if ( $ans[ 'correct' ] == "true" ) {
									$correct_answers++;
								}
							}
							if ( $ans[ 'status' ] == "active" ) {
								$message .= "<div" . $selectedstyle . ">";
								$message .= "<div style='width: 65%;display: inline-block;'>" . $ans[ 'answer' ] . "</div>";	
								$message .= "<div style='width: 15%;display: inline-block;'>" . $ans[ 'votes' ] . "</div>";	
								$message .= "<div style='width: 15%;display: inline-block;'>" . $ans[ 'percentage' ] . "</div>";	
								$message .= "</div>";
							}
						}
					$message .= "</div>
					
					";
				}
				$message .= "<p>" . esc_html__( 'Final Score', MODAL_SURVEY_TEXT_DOMAIN ) . ": " . $final_score . "
					" . esc_html__( 'Correct Answers', MODAL_SURVEY_TEXT_DOMAIN ) . ": " . $correct_answers . "
					
					" . esc_html__( 'Participant votes marked with grey rows.', MODAL_SURVEY_TEXT_DOMAIN ) . " <a target='_blank' href='" . esc_url( admin_url( "admin.php?page=modal_survey_participants&msuid=" . $puid->autoid . "-" . $survey_id . "" ) ) . "'>" . esc_html__( 'Click here to see the full details.', MODAL_SURVEY_TEXT_DOMAIN ) . "</a></p>
				
				
				";
				if ( isset( $puid->autoid ) ) {
					$wpsendmessage = wp_mail( $emailto, esc_html__( 'Survey Completed', MODAL_SURVEY_TEXT_DOMAIN ), apply_filters( 'modal_survey_filter_admin_email', nl2br( $message ) ), $headers, $attachments );
				}
				if ( ! $wpsendmessage ) {
					print( "done - admin mail sending failure " );
					global $ts_mail_errors;
					global $phpmailer;
					if ( ! isset( $ts_mail_errors ) ) {
						$ts_mail_errors = array();
					}
					if ( isset( $phpmailer ) ) {
						$ts_mail_errors[] = $phpmailer->ErrorInfo;
					}
					print_r( $ts_mail_errors );
					return false;
				}
				else {
					do_action( 'modal_survey_action_admin_email', array( "recipient" => $sopts[ 144 ], "subject" => esc_html__( 'Survey Completed', MODAL_SURVEY_TEXT_DOMAIN ), "message" => $message, "headers" => $headers, "attachment" => $attachments ) );
					print( "done - admin mail sent " );
					return true;					
				}
			}
		}

		function parse_shortcode_content( $content ) { 
		 
			/* Parse nested shortcodes and add formatting. */ 
			$content = trim( wpautop( do_shortcode( $content ) ) ); 
		 
			/* Remove '</p>' from the start of the string. */ 
			if ( substr( $content, 0, 4 ) == '</p>' ) 
				$content = substr( $content, 4 ); 
		 
			/* Remove '<p>' from the end of the string. */ 
			if ( substr( $content, -3, 3 ) == '<p>' ) 
				$content = substr( $content, 0, -3 ); 
		 
			/* Remove any instances of '<p></p>'. */ 
			$content = str_replace( array( '<p></p>' ), '', $content ); 
		 
			return $content; 
		}
		
		public function send_autoresponse( $content, $sender, $senderemail, $subject, $recipient, $sid ) {
			global $wpdb;
				if ( empty( $content ) || empty( $sender ) || empty( $senderemail ) || empty( $subject ) || empty( $recipient ) ) {
					print( "Auto-Response: Not enough parameters. " );
					return true;
				}
				$headers = array();
				$attachments = "";
				$puid = $wpdb->get_row( $wpdb->prepare( "SELECT autoid, name, email, custom FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE id = %s ", $_COOKIE[ 'ms-uid' ] ) );
				$ms_user_custom_uns = unserialize( $puid->custom );
				if ( strpos( $content, '[score]' ) !== false || strpos( $content, '[correct]' ) !== false ) {
					$fullrecords = modal_survey::survey_answers_shortcodes( 
						array ( 'id' => $sid, 'data' => 'full-records', 'style' => 'plain', 'uid' => $_COOKIE[ 'ms-uid' ], 'pure' => 'true', 'session' => 'last'  )
					);
					$totalscore = 0; $correct = 0;
					foreach( $fullrecords as $fr ) {
						foreach( $fr[ 'datas' ] as $frd ) {
							if ( $frd[ 'selected' ] == "true" ) {
								$totalscore += $frd[ 'score' ];
								if ( $frd[ 'correct' ] == "true" ) {
									$correct++;
								}
							}
						}
					}
					$content = str_replace( '[score]', $totalscore, $content );
					$content = str_replace( '[correct]', $correct, $content );
				}
					$content = str_replace( '[name]', $puid->name, $content );
					if ( ! empty( $ms_user_custom_uns ) ) {
						foreach ( $ms_user_custom_uns as $muc_index=>$muc ) {
							$content = str_replace( '[' . $muc_index . ']', $ms_user_custom_uns['' . $muc_index . ''], $content );					
						}
					}
				
				$attach = "false";
				if ( strpos( $content, '[attachment]' ) !== false ) {
					$attach = "true";
					$content = str_replace( '[attachment]', '', $content );
				}
				$ncontent = $this->call_modalsurvey_records_shortcode( $this->call_modalsurvey_records_shortcode( $content ) );
				$content2 = $this->call_modalsurvey_shortcode( $this->call_modalsurvey_conditions_shortcode( $ncontent ) );
				$newcontent = $this->call_modalsurvey_shortcode( $this->call_modalsurvey_date_shortcode( $content2 ) );
				if ( ! filter_var( $senderemail, FILTER_VALIDATE_EMAIL ) ) {
					$senderemail = get_option( 'admin_email' );
				}
				if ( empty( $sender ) ) {
					$sender = get_option( 'blogname' );
				}
				if ( empty( $subject ) ) {
					$subject = esc_html__( 'Survey Result', MODAL_SURVEY_TEXT_DOMAIN );
				}
				if ( $attach == "true" ) {
					if ( ! isset( $puid->autoid ) ) {
						print( "uid not available " );
						return false;
					}
					$this->expresult = $this->export_survey( 'personal', $puid->autoid . '-' . $sid, 'pdf', 'last' );
					if ( file_exists( WP_CONTENT_DIR . '/plugins/modal_survey/exports/' . $puid->autoid . '-' . $sid . '.pdf' ) ) {
						$attachments = array( WP_CONTENT_DIR . '/plugins/modal_survey/exports/' . $puid->autoid . '-' . $sid . '.pdf' );				
					}
					else {
						print( ' autoresponse attachment failed ' );
					}
				}
				$newcontent = $this->parse_shortcode_content( $newcontent );
				$headers[] = 'MIME-Version: 1.0' . '\r\n';
				$headers[] = 'From: ' . $sender . ' < ' . $senderemail . '>';
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
				$wpsendautoresponse = wp_mail( $puid->email, $subject, apply_filters( 'modal_survey_filter_autoresponse_email', $newcontent ), $headers, $attachments );
				do_action( 'modal_survey_action_autoresponse', array( "recipient" => $puid->email, "subject" => $subject, "message" => $newcontent, "headers" => $headers, "attachment" => $attachments ) );
				print( " autoresponse sent " );
		}

		public function ajax_survey_back() {
		global $wpdb, $current_user;
		$qid = "";
		$sid = "";
		$preview = "";
		$la = array();
		$msuser_cookie = $_COOKIE[ 'ms-uid' ];
			if ( isset( $_REQUEST[ 'qid' ] ) ) {
				$qid = sanitize_text_field( $_REQUEST[ 'qid' ] );
			}
			if ( isset( $_REQUEST[ 'sid' ] ) ) {
				$sid = sanitize_text_field( $_REQUEST[ 'sid' ] );
			}
			if ( isset( $_REQUEST[ 'preview' ] ) ) {
				$preview = sanitize_text_field( $_REQUEST[ 'preview' ] );
			}
			if ( isset( $_COOKIE[ 'ms-session'] ) ) {
				$ms_session = $_COOKIE[ 'ms-session'];
			}
			if ( isset( $_REQUEST[ 'la' ] ) ) {
				$la = sanitize_text_field( $_REQUEST[ 'la' ] );
			}
			if ( isset( $_REQUEST[ 'lo' ] ) ) {
				$lo = sanitize_text_field( $_REQUEST[ 'lo' ] );
			}
			/* START REMOVING ANSWER FROM THE CUMULATED RESULTS */
			foreach( $la as $answer ) {
				$current_count = $wpdb->get_var( $wpdb->prepare( "SELECT `count` FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE `survey_id` = %d AND `question_id` = %d AND `autoid` = %d", $sid, $qid, $answer ) );
				if ( $current_count > 0 ) {
					$wpdb->update( $wpdb->base_prefix . "modal_survey_answers", array( "count" => $current_count - 1 ),array( 'survey_id' => $sid, 'question_id' => $qid, 'autoid' => $answer ) );
				}
				if ( isset( $lo ) ) {
					$current_open_count = $wpdb->get_var( $wpdb->prepare( "SELECT `count` FROM " . $wpdb->base_prefix . "modal_survey_answers_text WHERE `survey_id` = %s AND `answertext` = %s", $sid, $this->format_open_answer( $lo ) ) );
					if ( $current_open_count > 0 ) {
						if ( $current_open_count > 1 ) {
							$wpdb->update( $wpdb->base_prefix . "modal_survey_answers_text", array( "count" => $current_open_count - 1 ), array( 'survey_id' => $sid, 'answertext' => $this->format_open_answer( $lo ) ) );
						}
						else {
							$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_answers_text WHERE `survey_id` = %s AND `answertext` = %s", $sid, $this->format_open_answer( $lo ) ) );
						}
					}
				}
			}
			/* END REMOVING ANSWER FROM THE CUMULATED RESULTS */
			/* START REMOVING ANSWER FROM THE INDIVIDUAL RESULTS */
			$check_user = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE `id` = %s", $msuser_cookie ) );
			if ( ! empty( $check_user ) ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_participants_details WHERE `uid` = %d AND `sid` = %s AND `qid` = %s AND `aid` = %s AND `samesession` = %s", $check_user, $sid, $qid, $answer, $ms_session ) );
			}
			die( 'success' );
		}
		
		function include_apis( $sopts, $email, $mv ) {
			if ( $sopts[ 24 ] == '1' ) {
				require_once( sprintf( "%s/modules/activecampaign/module_activecampaign.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 28 ] == '1' ) {
				require_once( sprintf( "%s/modules/aweber/module_aweber.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 35 ] == '1' ) {
				require_once( sprintf( "%s/modules/benchmark/module_benchmark.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 39 ] == '1' ) {
				require_once( sprintf( "%s/modules/campaignmonitor/module_campaignmonitor.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 42 ] == '1' ) {
				require_once( sprintf( "%s/modules/campayn/module_campayn.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 46 ] == '1' ) {
				require_once( sprintf( "%s/modules/constantcontact/module_constantcontact.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 50 ] == '1' ) {
				require_once( sprintf( "%s/modules/freshmail/module_freshmail.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 54 ] == '1' ) {
				require_once( sprintf( "%s/modules/getresponse/module_getresponse.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 57 ] == '1' ) {
				require_once( sprintf( "%s/modules/icontact/module_icontact.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 62 ] == '1' ) {
				require_once( sprintf( "%s/modules/infusionsoft/module_infusionsoft.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 66 ] == '1' ) {
				require_once( sprintf( "%s/modules/interspire/module_interspire.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 70 ] == '1' ) {
				require_once( sprintf( "%s/modules/madmimi/module_madmimi.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 74 ] == '1' ) {
				$mailchimp_listid = $sopts[ 76 ];
				require_once( sprintf( "%s/modules/mailchimp/module_mailchimp.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 77 ] == '1' ) {
				require_once( sprintf( "%s/modules/mailerlite/module_mailerlite.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 80 ] == '1' ) {
				require_once( sprintf( "%s/modules/mailigen/module_mailigen.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 84 ] == '1' ) {
				require_once( sprintf( "%s/modules/mailjet/module_mailjet.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 88 ] == '1' ) {
				require_once( sprintf( "%s/modules/mailpoet/module_mailpoet.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 90 ] == '1' ) {
				require_once( sprintf( "%s/modules/emma/module_emma.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 94 ] == '1' ) {
				require_once( sprintf( "%s/modules/mymail/module_mymail.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 96 ] == '1' ) {
				require_once( sprintf( "%s/modules/ontraport/module_ontraport.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 101 ] == '1' ) {
				require_once( sprintf( "%s/modules/pinpointe/module_pinpointe.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 105 ] == '1' ) {
				require_once( sprintf( "%s/modules/sendinblue/module_sendinblue.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 108 ] == '1' ) {
				require_once( sprintf( "%s/modules/sendreach/module_sendreach.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 113 ] == '1' ) {
				require_once( sprintf( "%s/modules/sendy/module_sendy.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 117 ] == '1' ) {
				require_once( sprintf( "%s/modules/simplycast/module_simplycast.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 121 ] == '1' ) {
				require_once( sprintf( "%s/modules/ymlp/module_ymlp.php", dirname( __FILE__ ) ) );
			}			
			if ( $sopts[ 164 ] == '1' ) {
				require_once( sprintf( "%s/modules/mailingboss/module_mailingboss.php", dirname( __FILE__ ) ) );
			}
			if ( $sopts[ 167 ] == '1' ) {
				require_once( sprintf( "%s/modules/klicktipp/module_klicktipp.php", dirname( __FILE__ ) ) );
			}
					print('subscribed');
		}
		
		public function ajax_survey_answer() {
		global $wpdb, $current_user;
		if( session_id() == '' ) {
			session_start();
		}
		$adminemail = false;
		$conf = false;
		$ms_user_form_data = array();
		if ( isset( $_REQUEST[ 'preview' ] ) && ( $_REQUEST[ 'preview' ] == "true" ) && $_REQUEST['sspcmd'] != "displaychart" ) {
			die("success");
		}
		$endcontent = false;
		if ( isset( $_REQUEST[ 'endcontent' ] ) ) {
			$endcontent = trim( $_REQUEST[ 'endcontent' ] );
		}
		if ( isset( $_REQUEST[ 'sid' ] ) ) {
			$survey_id = sanitize_text_field( $_REQUEST[ 'sid' ] );
		}
		if ( isset( $_REQUEST[ 'options' ] ) ) {
			$opt = json_decode( stripslashes( $_REQUEST[ 'options' ] ) );
			$options = ( array ) $opt[0];
			$survey_id = $options[ 'sid' ];
			if ( isset( $options[ 'time' ] ) ) {
				$qztime = $options[ 'time' ];
			}
			else {
				$qztime = '';				
			}
		}
		else {
			$options = array();
		}
		if ( isset( $_REQUEST[ 'email' ] ) && ! empty( $_REQUEST[ 'email' ] ) ) {
			$email = sanitize_text_field( $_REQUEST[ 'email' ] );
			$ms_user_form_data[ 'email' ] = $email;
		}
		$survey_opts = $wpdb->get_var( $wpdb->prepare( "SELECT options FROM " . $wpdb->base_prefix . "modal_survey_surveys WHERE `id` = %s", $survey_id ) );
		$sopts = json_decode( stripslashes( $survey_opts ) );
		$pform_enabled = false;
		if ( isset( $sopts[ '125' ] ) ) {
			if ( $sopts[ '125' ] == '1' ) {
				$pform_enabled = true;
			}
		}
		if ( isset( $_REQUEST[ 'sspcmd' ] ) && $_REQUEST[ 'sspcmd' ] == "form" ) {
			if ( isset( $_REQUEST[ 'conf' ] ) ) {
				$conf = sanitize_text_field( $_REQUEST[ 'conf' ] );
			}
			if ( isset( $_COOKIE[ 'ms-uid' ] ) ) {
				$mv = array();
				$name = "Anonymous";
				if ( isset( $_REQUEST[ 'name' ] ) && ! empty( $_REQUEST[ 'name' ] ) ) {
					$name = sanitize_text_field( $_REQUEST[ 'name' ] );
					$ms_user_form_data[ 'name' ] = sanitize_text_field( $_REQUEST[ 'name' ] );
					if ( $sopts[ 28 ] != '1' ) {
						$mv[ 'name' ] = sanitize_text_field( $_REQUEST[ 'name' ] );
						$mv[ 'fullname' ] = sanitize_text_field( $_REQUEST[ 'name' ] );
						$expname = explode( " ", sanitize_text_field( $_REQUEST[ 'name' ] ) );
						$mv[ 'firstname' ] = $expname[ 0 ];
						$mv[ 'fname' ] = $expname[ 0 ];
						if ( isset( $expname[ 1 ] ) ) {
							$mv[ 'lastname' ] = $expname[ 1 ];
							$mv[ 'lname' ] = $expname[ 1 ];
						}
						else {
							$mv[ 'lastname' ] = "-";
							$mv[ 'lname' ] = "-";
						}
					}
				}
				$customfields = '';
				if ( isset( $_REQUEST[ 'customfieldsarray' ] ) ) {
					if ( ! empty( $_REQUEST[ 'customfieldsarray' ] ) ) {
						foreach( $_REQUEST[ 'customfieldsarray' ] as $cfa ) {
							$customf[ $cfa ] = sanitize_text_field( $_REQUEST[ $cfa ] );
						}
					}
				}
				else {
					$customf = array();
				}
				$this->custom = array_merge( $mv, $customf );
				$mv = $this->custom;
				$ms_user_form_data[ 'custom' ] = serialize( $customf );
				if ( isset( $_REQUEST[ 'email' ] ) || isset( $_REQUEST[ 'name' ] ) ) {
					$wpdb->update( $wpdb->base_prefix . "modal_survey_participants", $ms_user_form_data, array( 'id' => $_COOKIE[ 'ms-uid' ] ) );			
					do_action( 'modal_survey_action_participants_update', $ms_user_form_data );
				}
			}
			if ( $_REQUEST[ 'pform_pos' ] == "start" && $_REQUEST[ 'pform' ] == "true" ) {
			}
			else {
				/* passing the subscription data for campaign */
				if ( isset( $survey_id ) && isset( $email ) ) {
					if ( $sopts[ 160 ] != "1" ) {
						$this->include_apis( $sopts, $email, $mv );
					}
					elseif ( $sopts[ 160 ] == "1" ) {
						if ( $conf != "false" ) {
							$this->include_apis( $sopts, $email, $mv );
						}
					}
				}
			}
			if ( $_REQUEST[ 'pform_pos' ] == "start" && $_REQUEST[ 'pform' ] == "false" ) {
			}
			else {
				if ( ! $adminemail ) {
					$adminemail = $this->send_admin_email( $endcontent, $sopts, $_REQUEST, $survey_id );
				}
				if ( isset( $survey_id ) ) {
					if ( ! ( $sopts ) ) {
						$survey_opts = $wpdb->get_var( $wpdb->prepare( "SELECT options FROM " . $wpdb->base_prefix . "modal_survey_surveys WHERE `id` = %s", $survey_id ) );
						$sopts = json_decode( stripslashes( $survey_opts ) );
					}
					$this->send_autoresponse( $sopts[ 147 ], $sopts[ 148 ], $sopts[ 149 ] . '@' . str_replace( "www.", "", $_SERVER[ 'HTTP_HOST' ] ), $sopts[ 150 ], $email, $survey_id );
				}
				else {
					die( "success" );				
				}
			}
			if ( ! isset( $error ) ) {
				die( "success" );
			}
			else {
				die( $error );
			}
		}
		if ( ! $adminemail && ! isset( $options[ 'bulkans' ] ) && ! isset( $options[ 'bulkans' ] ) && $_REQUEST[ 'sspcmd' ] != "form" && $_REQUEST[ 'sspcmd' ] != "save" ) {
			$adminemail = $this->send_admin_email( $endcontent, $sopts, $_REQUEST, $survey_id );
		}
			if ( $_REQUEST['sspcmd'] == "displaychart" ) {
				if ( isset( $_REQUEST[ 'sid' ] ) ) {
					if ( ! ( $sopts ) ) {
						$survey_opts = $wpdb->get_var( $wpdb->prepare( "SELECT options FROM " . $wpdb->base_prefix . "modal_survey_surveys WHERE `id` = %s", $_REQUEST[ 'sid' ] ) );
						$sopts = json_decode( stripslashes( $survey_opts ) );
					}
					do_action( 'modal_survey_action_complete', array( "sid" => $_REQUEST[ 'sid' ], "ms-uid" => $_COOKIE[ 'ms-uid' ], "sopts" => $sopts ) );
					if ( ! isset( $sopts[ 136 ] ) || $sopts[ 136 ] != 1 ) {
						die();
					}
					if ( empty( $sopts[ 140 ] ) ) {
						if ( $sopts[ 139 ] == "individual" ) {
							$ind = "uid='true' session='last'";
						}
						else {
							$ind = "";
						}
						die( $this->call_modalsurvey_shortcode( "[survey_answers style='" . $sopts[ 137 ] . "' data='" . $sopts[ 138 ] . "' " . $ind . " id='" . $_REQUEST[ 'sid' ] . "']" ) );
					}
					else {
						die( $this->call_modalsurvey_shortcode( $sopts[ 140 ] ) );						
					}
				}
				setcookie( 'ms-session', null, -1, COOKIEPATH, COOKIE_DOMAIN, false );
			}
			if ( isset( $options[ 'bulkans' ] ) ) {
				$msuser_cookie = $_COOKIE[ 'ms-uid' ];
				mt_srand( $this->make_seed() );
				$ms_session = mt_rand();
				setcookie( 'ms-session', $ms_session, time() + 31536000, COOKIEPATH, COOKIE_DOMAIN, false);
				if ( get_option( 'setting_remember_users' ) == "off" ) {
						$msrand = "ms" . mt_rand();
						setcookie( 'ms-uid', $msrand, time() + 31536000, COOKIEPATH, COOKIE_DOMAIN, false);
						$msuser_cookie = $msrand;
				}
				foreach( $options[ 'bulkans' ] as $qid => $aids ) {
					foreach( $aids as $key => $aid_item ) {
						$current_count = $wpdb->get_row( $wpdb->prepare( "SELECT `count`, `aoptions`, `uniqueid` FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE `survey_id` = %d AND `question_id` = %d AND `autoid` = %d", $options[ 'sid' ], $qid, $aid_item ) );
						$wpdb->update( $wpdb->base_prefix . "modal_survey_answers", array( "count" => $current_count->count + 1 ),array( 'survey_id' => $options[ 'sid' ], 'question_id' => $qid, 'autoid' => $aid_item ) );
						if ( get_option( 'setting_save_votes' ) == 'on' && isset( $msuser_cookie ) ) {
							$check_user = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE `id` = %s", $msuser_cookie ) );
							if ( $key == 0 && $qid == 1 ) {
								if ( ! $check_user ) {
									$current_user = wp_get_current_user();
									$username = '';
									$email = '';
									$name = '';
									if ( isset( $current_user->user_login ) && ! empty ( $current_user->user_login ) ) {
										$username = $current_user->user_login;
										if ( ! empty( $current_user->user_email ) ) {
											$email = $current_user->user_email;
										}
										if ( ( ! empty( $current_user->user_firstname ) && ! empty( $current_user->user_lastname ) ) || ( ! empty( $current_user->display_name ) ) ) {
											if ( ( ! empty( $current_user->user_firstname ) ) && ! empty( $current_user->user_lastname ) ) {
												$name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
											}
											elseif ( ! empty( $current_user->display_name ) ) {
												$name = $current_user->display_name;
											}
										}
									}
									if ( ! isset( $customf ) ) {
										$customf = '';
									}
									$ins = $wpdb->insert( $wpdb->base_prefix . "modal_survey_participants", 
											array(
											'id' => $msuser_cookie,
											'username' => $username, 
											'email' => $email,
											'name' => $name,
											'custom' => serialize( $customf )
											), 
											array( 
												'%s', 
												'%s',
												'%s',
												'%s',
												'%s'
											)  );
											if ( $ins ) {
												do_action( 'modal_survey_action_participants_create', array( "username" => $username, "email" => $email, "name" => $name, "custom" => $customf ) );
												$check_user = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE `id` = %s", $msuser_cookie ) );
											}
								}
							}
							if ( $check_user > 0 ) {
								if ( isset( $options[ 'bulkopen' ] ) && ! empty ( $options[ 'bulkopen' ]->{ $qid }[ $key ] ) ) {
									$aid_item .= '|' . $this->format_open_answer( $options[ 'bulkopen' ]->{ $qid }[ $key ] );
								}
							if ( get_option( 'setting_save_ip' ) == "off" ) {
								$userip = esc_html__( 'disabled', MODAL_SURVEY_TEXT_DOMAIN );
							}
							else {
								$userip = $_SERVER[ 'REMOTE_ADDR' ];								
							}
								$pdetails_array = array(
										'uid' => $check_user,
										'sid' => $options[ 'sid' ], 
										'qid' => $qid,
										'aid' => $aid_item,
										'time' => date( "Y-m-d H:i:s" ),
										'ip' => $userip,
										'postid' => $options[ 'postid' ],
										'samesession' => $ms_session,
										'timer' => $qztime
										);
								$insdet = $wpdb->insert( $wpdb->base_prefix . "modal_survey_participants_details", 
										$pdetails_array, 
										array(
											'%s', 
											'%s',
											'%d',
											'%s',
											'%s',
											'%s',
											'%d',
											'%s',
											'%d'
										)  );
										if ( ! isset( $options[ 'endstep' ] ) ) {
											$options[ 'endstep' ] = count( ( array ) $options[ 'bulkans' ] );
										}
										if ( ! isset( $options[ 'questions_max' ] ) ) {
											$options[ 'questions_max' ] = count( ( array ) $options[ 'bulkans' ] );
										}
								$pdetails_array[ 'played_question' ] = $qid;
								$pdetails_array[ 'endstep' ] = $options[ 'endstep' ];
								$pdetails_array[ 'questions_max' ] = $options[ 'questions_max' ];
								$pdetails_array[ 'options_aid' ] = $options[ 'bulkans' ];
								$pdetails_array[ 'bulk' ] = 'true';
								do_action( 'modal_survey_action_participant_vote', $pdetails_array );
							}
						}
						$aopts = ( array ) unserialize( $current_count->aoptions );
						if ( ! empty( $options[ 'bulkopen' ]->{ $qid }[ $key ] ) && ( $aopts[ 0 ] == 'open' || $aopts[ 0 ] == 'numeric' || $aopts[ 0 ] == 'date' || $aopts[ 0 ] == 'select' ) ) {
							$current_open_count = $wpdb->get_row( $wpdb->prepare( "SELECT `count`, `id`, `answertext` FROM " . $wpdb->base_prefix . "modal_survey_answers_text WHERE (`survey_id` = %d) AND (`id` = %s) AND (`answertext` = %s)", $options[ 'sid' ], $current_count->uniqueid, $this->format_open_answer( $options[ 'bulkopen' ]->{ $qid }[ $key ] ) ) );
							if ( isset( $current_open_count->count ) && ( $current_open_count->count > 0 ) ) {
								$wpdb->update( $wpdb->base_prefix . "modal_survey_answers_text", array( "count" => $current_open_count->count + 1 ), array( 'survey_id' => $options[ 'sid' ], 'id' => $current_count->uniqueid, 'answertext' => $current_open_count->answertext ) );
								do_action( 'modal_survey_action_open_text_answer', array( 'id' => $current_count->uniqueid, 'survey_id' => $options[ 'sid' ], 'answertext' => $current_open_count->answertext, 'count' => $current_open_count->count + 1 ) );
							}
							else {
								$wpdb->insert( $wpdb->base_prefix . "modal_survey_answers_text", array(
															'id' => $current_count->uniqueid,
															'survey_id' => $options[ 'sid' ], 
															'answertext' => $this->format_open_answer( $options[ 'bulkopen' ]->{ $qid }[ $key ] ),
															'count' => 1
															), 
															array( 
																'%s', 
																'%d',
																'%s',
																'%d'
															)  );					
								do_action( 'modal_survey_action_open_text_answer', array( 'id' => $current_count->uniqueid, 'survey_id' => $options[ 'sid' ], 'answertext' => $this->format_open_answer( $options[ 'bulkopen' ]->{ $qid }[ $key ] ), 'count' => 1 ) );
							}
						}
					}
				}
				if ( ! $adminemail && $pform_enabled != true ) {
					$adminemail = $this->send_admin_email( $endcontent, $sopts, $_REQUEST, $survey_id );
				}
			}
			else {
				$msuser_cookie = $_COOKIE[ 'ms-uid' ];
				if ( ( get_option( 'setting_remember_users' ) == "off" ) && $options[ 'qid' ] == 1 ) {
					if ( get_option( 'setting_remember_users' ) == "off" ) {
							$msrand = "ms" . mt_rand();
							setcookie( 'ms-uid', $msrand, time() + 31536000, COOKIEPATH, COOKIE_DOMAIN, false);
							$msuser_cookie = $msrand;
					}
				}
				if ( $options[ 'qid' ] == 1 ) {
					mt_srand( $this->make_seed() );
					$ms_session = mt_rand();
					setcookie( 'ms-session', $ms_session, time() + 31536000, COOKIEPATH, COOKIE_DOMAIN, false);					
				}
				else {
					$ms_session = $_COOKIE[ 'ms-session' ];
				}
				foreach( $options[ 'aid' ] as $key=>$aid_item ) {
					$current_count = $wpdb->get_row( $wpdb->prepare( "SELECT `count`,`aoptions`,`uniqueid` FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE `survey_id` = %d AND `question_id` = %d AND `autoid` = %d", $options[ 'sid' ], $options[ 'qid' ], $aid_item ) );
					$wpdb->update( $wpdb->base_prefix . "modal_survey_answers", array( "count" => $current_count->count + 1 ),array( 'survey_id' => $options['sid'],'question_id' => $options['qid'],'autoid' => $aid_item));
					if ( get_option( 'setting_save_votes' ) == 'on' && isset( $msuser_cookie ) ) {
						$check_user = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE `id` = %s", $msuser_cookie ) );
						if ( $key == 0 && $options[ 'qid' ] == 1 ) {
							if ( ! $check_user ) {
								$current_user = wp_get_current_user();
								$username = '';
								$email = '';
								$name = '';
								if ( isset( $current_user->user_login ) && ! empty ( $current_user->user_login ) ) {
									$username = $current_user->user_login;
									if ( ! empty( $current_user->user_email ) ) {
										$email = $current_user->user_email;
									}
									if ( ( ! empty( $current_user->user_firstname ) && ! empty( $current_user->user_lastname ) ) || ( ! empty( $current_user->display_name ) ) ) {
										if ( ( ! empty( $current_user->user_firstname ) ) && ! empty( $current_user->user_lastname ) ) {
											$name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
										}
										elseif ( ! empty( $current_user->display_name ) ) {
											$name = $current_user->display_name;
										}
									}
								}
								if ( ! isset( $customf ) ) {
									$customf = '';
								}
								$ins = $wpdb->insert( $wpdb->base_prefix . "modal_survey_participants", 
										array(
										'id' => $msuser_cookie,
										'username' => $username, 
										'email' => $email,
										'name' => $name,
										'custom' => serialize( $customf )
										), 
										array( 
											'%s', 
											'%s',
											'%s',
											'%s',
											'%s'
										)  );
										if ( $ins ) {
										do_action( 'modal_survey_action_participants_create', array( "username" => $username, "email" => $email, "name" => $name, "custom" => $customf ) );
										$check_user = $wpdb->get_var( $wpdb->prepare( "SELECT autoid FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE `id` = %s", $msuser_cookie ) );
										}
							}
						}
						if ( $check_user > 0 ) {
							if ( isset( $options[ 'open' ] ) && ! empty ( $options[ 'open' ] ) ) {
								foreach( $options[ 'open' ] as $opop ) {
									if ( $opop->unique == $current_count->uniqueid ) {
										$current_opop = $this->format_open_answer( $opop->value );
									}
								}
								if ( isset( $current_opop ) ) {
									$aid_item .= '|' . $current_opop;
								}
							}
							if ( get_option( 'setting_save_ip' ) == "off" ) {
								$userip = esc_html__( 'disabled', MODAL_SURVEY_TEXT_DOMAIN );
							}
							else {
								$userip = $_SERVER[ 'REMOTE_ADDR' ];								
							}
							$pdetails_array = array(
									'uid' => $check_user,
									'sid' => $options[ 'sid' ], 
									'qid' => $options['qid'],
									'aid' => $aid_item,
									'time' => date( "Y-m-d H:i:s" ),
									'ip' => $userip,
									'postid' => $options[ 'postid' ],
									'samesession' => $ms_session,
									'timer' => $qztime									
									);
							$insdet = $wpdb->insert( $wpdb->base_prefix . "modal_survey_participants_details", 
									$pdetails_array, 
									array(
										'%s', 
										'%s',
										'%d',
										'%s',
										'%s',
										'%s',
										'%d',
										'%s',
										'%d'
									)  );
							if ( $insdet ) {
								$pdetails_array[ 'played_question' ] = $options[ 'played_question' ];
								$pdetails_array[ 'endstep' ] = $options[ 'endstep' ];
								$pdetails_array[ 'questions_max' ] = $options[ 'questions_max' ];
								$pdetails_array[ 'options_aid' ] = $options[ 'aid' ];
								$pdetails_array[ 'bulk' ] = 'false';
								do_action( 'modal_survey_action_participant_vote', $pdetails_array );
							}
						}
					}
					$aopts = ( array ) unserialize( $current_count->aoptions );
					if ( ! empty( $options[ 'open' ] ) && ( $aopts[ 0 ] == 'open' || $aopts[ 0 ] == 'numeric' || $aopts[ 0 ] == 'date' || $aopts[ 0 ] == 'select' ) ) {
						foreach( $options[ 'open' ] as $opop ) {
							if ( $opop->unique == $current_count->uniqueid ) {
								$current_opop = $this->format_open_answer( $opop->value );
							}
						}
						$current_open_count = $wpdb->get_row( $wpdb->prepare( "SELECT `count`, `id`, `answertext` FROM " . $wpdb->base_prefix . "modal_survey_answers_text WHERE (`survey_id` = %d) AND (`id` = %s) AND (`answertext` = %s)", $options[ 'sid' ], $current_count->uniqueid, $current_opop ) );
						if ( isset( $current_open_count->count ) && $current_open_count->count > 0 ) {
							$wpdb->update( $wpdb->base_prefix . "modal_survey_answers_text", array( "count" => $current_open_count->count + 1 ), array( 'survey_id' => $options[ 'sid' ], 'id' => $current_count->uniqueid, 'answertext' => $current_open_count->answertext ) );
							do_action( 'modal_survey_action_open_text_answer', array( 'id' => $current_count->uniqueid, 'survey_id' => $options[ 'sid' ], 'answertext' => $current_open_count->answertext, 'count' => $current_open_count->count + 1 ) );
						}
						else {
							$wpdb->insert( $wpdb->base_prefix . "modal_survey_answers_text", array(
														'id' => $current_count->uniqueid,
														'survey_id' => $options[ 'sid' ], 
														'answertext' => $current_opop,
														'count' => 1
														), 
														array( 
															'%s', 
															'%d',
															'%s',
															'%d'
														)  );					
							do_action( 'modal_survey_action_open_text_answer', array( 'id' => $current_count->uniqueid, 'survey_id' => $options[ 'sid' ], 'answertext' => $current_opop, 'count' => 1 ) );
						}
					}
				}
				if ( ! $adminemail ) {
					$adminemail = $this->send_admin_email( $endcontent, $sopts, $_REQUEST, $survey_id );
				}
			}
			if ( ( $_REQUEST[ 'sspcmd' ] == "displaychart" ) || ( $sopts[ 18 ] != 1 ) ) {
				$survey_viewed = array();
				if ( isset( $_COOKIE[ 'modal_survey' ] ) ) {
					$survey_viewed = json_decode( stripslashes( $_COOKIE[ 'modal_survey' ] ) );
					if ( ! in_array( $options[ 'auto_id' ], $survey_viewed ) ) {
						if ( get_option( 'setting_display_once_per_filled' ) == "on" ) {
							$survey_viewed[] = $options[ 'auto_id' ];
						}
						if ( get_option( 'setting_display_once_per_filled' ) == "on" ) {
							setcookie( "modal_survey", json_encode( $survey_viewed ), time() + ( $sopts[ 143 ] * 3600 ), COOKIEPATH, COOKIE_DOMAIN );
						}
					}
				}
				else {
					if ( get_option( 'setting_display_once_per_filled' ) == "on" ) {
						$survey_viewed[] = $options[ 'auto_id' ];
					}
					if ( get_option( 'setting_display_once_per_filled' ) == "on" ) {
						setcookie( "modal_survey", json_encode( $survey_viewed ), time() + ( $sopts[ 143 ] * 3600 ), COOKIEPATH, COOKIE_DOMAIN );
					}
				}
			}
			die( "success" );
		}
		
		function format_open_answer( $text ) {
			return substr( sanitize_text_field( $text ), 0, 600 );
		}
		
		function make_seed() {
		  list( $usec, $sec ) = explode( ' ', microtime() );
		  return ( float ) $sec + ( ( float ) $usec * 100000 );
		}
			//get_gmt_from_date
		function get_datetime_from_date( $string, $format = 'Y-m-d H:i:s' ) {
			$tz = get_option( 'timezone_string' );
			if ( $tz ) {
				$datetime = date_create( $string, new DateTimeZone( $tz ) );
				if ( ! $datetime )
					return gmdate( $format, 0 );
				$datetime->setTimezone( new DateTimeZone( 'UTC' ) );
				$string_gmt = $datetime->format( $format );
			} else {
				if ( ! preg_match( '#([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $string, $matches ) )
					return gmdate( $format, 0 );
				$string_time = gmmktime( $matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1] );
				$string_gmt = gmdate( $format, $string_time - get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			}
			return $string_gmt;
		}
		
		function get_date_datetime( $string ) {
			$date_time = explode(" ",$string);
			$dates = explode("-",$date_time[0]);
			$times = explode(":",$date_time[1]);
				return date("Y-m-d H:i:s",mktime($times[0],$times[1],0,$dates[1],$dates[2],$dates[0]));
		}

		function get_datetime_date( $string ) {
			$date_time = explode(" ",$string);
			$dates = explode("-",$date_time[0]);
			$times = explode(":",$date_time[1]);
				return date("Y-m-d H:i:s",mktime($times[0],$times[1],0,$dates[1],$dates[2],$dates[0]));
		}

		public function create_zip( $files = array(), $destination = '', $overwrite = false ) {
			//if the zip file already exists and overwrite is false, return false
			if ( file_exists( $destination ) && ! $overwrite ) { 
				return false; 
			}
			//vars
			$valid_files = array();
			//if files were passed in...
			if ( is_array( $files ) ) {
				//cycle through each file
				foreach( $files as $file ) {
					//make sure the file exists
					if ( file_exists( $file ) ) {
						$valid_files[] = $file;
					}
				}
			}
			//if we have good files...
			if ( count( $valid_files ) ) {
				//create the archive
				$zip = new ZipArchive();
				if ( file_exists( $destination ) ) {
					unlink( $destination );
				}
				if( $zip->open( $destination, ZIPARCHIVE::CREATE ) !== true ) {
					return false;
				}
				//add the files
				foreach( $valid_files as $file ) {
					$zip->addFile( $file, basename( $file ) );
				}
				//debug
				//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
				
				//close the zip -- done!
				$zip->close();
				
				//check to make sure the file exists
				return file_exists( $destination );
			}
			else {
				return false;
			}
		}		
		
		function export_survey( $mode, $survey_id, $type, $samesession ) {
			global $wpdb;
			$result = '';
			if ( isset( $mode ) && $mode == "personal" ) {
				$personal = true;
				$s_id_expl = explode( "-", $survey_id );
				if ( isset( $s_id_expl[ 1 ] ) ) {
					$survey_id = $s_id_expl[ 1 ];
				}
			}
			else {
				$personal = false;
			}
			if ( ! file_exists( sprintf( "%s/exports", dirname( __FILE__ ) ) ) ) {
				mkdir( sprintf( "%s/exports", dirname( __FILE__ ) ), 0777, true );
			}
			if ( isset( $type ) ) {
				$exporttype = $type;
			}
			else {
				$exporttype = "txt";
			}
			$sql = "SELECT *,msq.qoptions, msq.id as question_id FROM " . $wpdb->base_prefix . "modal_survey_surveys mss LEFT JOIN " . $wpdb->base_prefix . "modal_survey_questions msq on mss.id = msq.survey_id WHERE mss.id = %d ORDER BY msq.id ASC";
			$q_sql = $wpdb->get_results( $wpdb->prepare( $sql, $survey_id ) );
			$survey_exp[ 'name' ] = $q_sql[ 0 ]->name;
			if ( $personal ) {
				$survey_exp[ 'id' ] = $s_id_expl[ 0 ] . '-' . $s_id_expl[ 1 ];
			}
			else {
				$survey_exp[ 'id' ] = $survey_id;
			}
			if ( $samesession != "last" && ! empty( $samesession ) ) {
				$survey_exp[ 'id' ] .= '-' . $samesession;
			}
			$survey_exp[ 'options' ] = $q_sql[ 0 ]->options;
			$survey_exp[ 'global' ] = $q_sql[ 0 ]->global;
			$survey_exp[ 'start_time' ] = $q_sql[ 0 ]->start_time;
			$survey_exp[ 'expiry_time' ] = $q_sql[ 0 ]->expiry_time;
			$survey_exp[ 'export_time' ] = date( "Y-m-d H:i" );
			$personal_created = "";$personal_timer = "";$personal_alltimer = 0;$personal_allscore = 0;
			foreach ( $q_sql as $key1 => $ars ) {
				$survey_exp[ 'questions' ][ $ars->question_id ][ 'name' ] = $ars->question;
				if ( $personal ) {
					$samesession_cond = "";
					if ( ! empty( $samesession ) ) {
						if ( $samesession == "last" ) {
							$last_session = "SELECT samesession FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %d AND uid = %s ORDER BY time DESC";
							$samesession = $this->wpdb->get_var( $wpdb->prepare( $last_session, $s_id_expl[ 1 ], $s_id_expl[ 0 ] ) );
						}
						$samesession_cond = " AND mspd.samesession = %s";
					}
					$preparray = array( $s_id_expl[ 1 ], $ars->question_id, $s_id_expl[ 0 ] );
					if ( ! empty( $samesession_cond ) ) {
						$preparray = array( $s_id_expl[ 1 ], $ars->question_id, $s_id_expl[ 0 ], $samesession );
					}
					$sql_u = "SELECT mspd.qid, mspd.aid, mspd.timer, DATE_FORMAT( mspd.time,'%Y-%m-%d %H:%i' ) as created FROM " . $wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $wpdb->base_prefix . "modal_survey_participants msp on mspd.uid = msp.autoid WHERE mspd.sid = %d AND mspd.qid = %d AND msp.autoid = %s " . $samesession_cond . " ORDER BY autoid ASC";
					$a_sql_u = $wpdb->get_results( $wpdb->prepare( $sql_u, implode( $preparray ) ) );
					if ( ! empty( $a_sql_u ) ) {
						foreach( $a_sql_u as $key2u=>$asu ) {
							$tempex = explode( "|", $asu->aid );
							if ( is_array( $tempex ) && ! empty( $tempex[ 0 ] ) ) {
								$user_votes[ $asu->qid ][ $tempex[ 0 ] ] = $asu->aid;
							}
							else {
								$user_votes[ $asu->qid ][] = $asu->aid;
							}
							$personal_timer[ $asu->qid ] = $asu->timer;
							$personal_alltimer += $asu->timer;
							if ( empty( $personal_created ) && ! empty( $asu->created ) ) {
								$personal_created = $asu->created;
							}
						}
					}
				}
				$sql = "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE survey_id = %d AND question_id = %d ORDER BY autoid ASC";
				$a_sql = $wpdb->get_results( $wpdb->prepare( $sql, $survey_id, $ars->question_id ) );
				//start - remove inactive answers
				foreach($a_sql as $aaskey=>$bas){
					$baoptions = unserialize( $bas->aoptions );								
					if ( isset( $baoptions[ 8 ] ) && $baoptions[ 8 ] == "1" ) {
						unset( $a_sql[ $aaskey ] );
					}
				}
				//end - remove inactive answers	
				foreach( $a_sql as $key2 => $as ) {
					$allcount = 0;
					$aoptions = unserialize( $as->aoptions );
					foreach ( $a_sql as $aas ) {
						$allcount = $allcount + $aas->count;
					}
					if ( $allcount == 0 ) {
						$acr = '0';
					}
					else {
						$acr = round( ( $as->count / $allcount ) * 100, 2 );
					}
					$survey_exp[ 'questions' ][ $ars->question_id ][ 'count' ] = $allcount;
					$survey_exp[ 'questions' ][ $ars->question_id ][ 'qoptions' ] = $ars->qoptions;
					$survey_exp[ 'questions' ][ $ars->question_id ][ $as->autoid ] = array( "answer" => $as->answer, "count" => $as->count, "aoptions" => $as->aoptions, "percentage" => $acr, "uniqueid" => $as->uniqueid );				
					if ( $personal ) {
						if ( isset( $user_votes[ $ars->question_id ] ) && is_array( $user_votes[ $ars->question_id ] ) && ( in_array( $as->autoid, $user_votes[ $ars->question_id ] ) ) ) {
							$personal_allscore += $aoptions[ 4 ];
						}
					}
				}
			}
			if ( $personal ) {				
				$survey_exp[ 'user_votes' ] = $user_votes;
				$survey_exp[ 'user_details' ] = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_participants WHERE autoid = %d ", $s_id_expl[ 0 ] ) );
				$survey_exp[ 'user_details' ]->created = $personal_created;
				$survey_exp[ 'user_details' ]->timer = $personal_timer;
				$survey_exp[ 'user_details' ]->alltimer = $personal_alltimer;
				$survey_exp[ 'user_details' ]->allscore = $personal_allscore;				
			}
			if ( ! empty( $exporttype ) ) {
				require_once( sprintf( "%s/modules/export_" . $exporttype . ".php", dirname( __FILE__ ) ) );
			}
			return $result;
		}
		
		public function ajax_ms_campaigns() {
			if ( $_REQUEST[ 'sspcmd' ] == "connect_campaign" ) {
				if ( file_exists( sprintf( "%s/modules/" . $_REQUEST[ 'campaign' ] . "/connect_" . $_REQUEST[ 'campaign' ] . ".php", dirname( __FILE__ ) ) ) ) {
					require_once( sprintf( "%s/modules/" . $_REQUEST[ 'campaign' ] . "/connect_" . $_REQUEST[ 'campaign' ] . ".php", dirname( __FILE__ ) ) );					
				}
			}
		}
		
		public function ajax_survey() {
			global $wpdb;
			if ( isset( $_REQUEST[ 'sspcmd' ] ) ) {
			if ( $_REQUEST['sspcmd'] == "getapiinfo" ) {
					if ( isset( $_REQUEST[ 'field1' ] ) ) {
						$field1 = sanitize_text_field( $_REQUEST[ 'field1' ] );
					}
					else {
						$field1 = '';
					}
					if ( isset( $_REQUEST[ 'field2' ] ) ) {
						$field2 = sanitize_text_field( $_REQUEST[ 'field2' ] );
					}
					else {
						$field2 = '';
					}
					if ( isset( $_REQUEST[ 'field3' ] ) ) {
						$field3 = sanitize_text_field( $_REQUEST[ 'field3' ] );
					}
					else {
						$field3 = '';
					}
					if ( isset( $_REQUEST[ 'field4' ] ) ) {
						$field4 = sanitize_text_field( $_REQUEST[ 'field4' ] );
					}
					else {
						$field4 = '';
					}
					if ( $_REQUEST[ 'id' ] == 'aweberlists' ) {
						require_once( sprintf( "%s/modules/aweber/getapiinfo_aweber.php", dirname( __FILE__ ) ) );
					}
					if ( $_REQUEST[ 'id' ] == 'benchmarklists' ) {
						require_once( sprintf( "%s/modules/benchmark/getapiinfo_benchmark.php", dirname( __FILE__ ) ) );
					}
					if ( $_REQUEST[ 'id' ] == 'campaynlists' ) {
						require_once( sprintf( "%s/modules/campayn/getapiinfo_campayn.php", dirname( __FILE__ ) ) );
					}
					if ( $_REQUEST[ 'id' ] == 'constantcontactlists' ) {
						require_once( sprintf( "%s/modules/constantcontact/getapiinfo_constantcontact.php", dirname( __FILE__ ) ) );
					}
					if ( $_REQUEST[ 'id' ] == 'getresponselists' ) {
						require_once( sprintf( "%s/modules/getresponse/getapiinfo_getresponse.php", dirname( __FILE__ ) ) );
					}
					if ( $_REQUEST[ 'id' ] == 'mymaillists' ) {
						require_once( sprintf( "%s/modules/mymail/getapiinfo_mymail.php", dirname( __FILE__ ) ) );
					}
					if ( $_REQUEST[ 'id' ] == 'mailpoetlists' ) {
						require_once( sprintf( "%s/modules/mailpoet/getapiinfo_mailpoet.php", dirname( __FILE__ ) ) );
					}
					if ( $_REQUEST[ 'id' ] == 'ymlplists' ) {
						require_once( sprintf( "%s/modules/ymlp/getapiinfo_ymlp.php", dirname( __FILE__ ) ) );
					}
				
				}
				$survey_id = "";
				$survey_name = "";
				$survey_start_time = "";
				$survey_expiry_time = "";
				$survey_global = "";
				if ( isset( $_REQUEST[ 'survey_id' ] ) ) {
					$survey_id = sanitize_text_field($_REQUEST['survey_id']);
				}
				else {
					$survey_id = "";
				}
				if ( isset( $_REQUEST[ 'unique_id' ] ) ) {
					$unique_id = sanitize_text_field( $_REQUEST[ 'unique_id' ] );
				}
				else {
					$unique_id = "";
				}
				if ( isset( $_REQUEST[ 'text' ] ) ) {
					$open_answer_text = sanitize_text_field( $_REQUEST[ 'text' ] );
				}
				else {
					$open_answer_text = "";
				}
				if ( isset( $_REQUEST[ 'survey_nid' ] ) ) {
					$survey_nid = sanitize_text_field( $_REQUEST[ 'survey_nid' ] );
				}
				else {
					$survey_nid = "";
				}
				if ( isset( $_REQUEST[ 'survey_name' ] ) ) {
					$survey_name = sanitize_text_field( $_REQUEST[ 'survey_name' ] );
				}
				else {
					$survey_name = "";
				}
				if ( empty( $survey_name ) ) {
					$survey_name = date( "dmYHis" );
				}
				if ( isset( $_REQUEST[ 'start_time' ] ) && ( ! empty( $_REQUEST[ 'start_time' ] ) ) ) {
					$survey_start_time = $this->get_datetime_date( sanitize_text_field( $_REQUEST[ 'start_time' ] ) );
				}
				else {
					$survey_start_time = "";
				}
				if ( isset( $_REQUEST[ 'expiry_time' ] ) && ( ! empty( $_REQUEST[ 'expiry_time' ] ) ) ) {
					$survey_expiry_time = $this->get_datetime_date( sanitize_text_field( $_REQUEST[ 'expiry_time' ] ) );
				}
				else {
					$survey_expiry_time = "";
				}
				if ( isset( $_REQUEST[ 'global_use' ] ) ) {
					$survey_global = sanitize_text_field( $_REQUEST[ 'global_use' ] );
				}
				else {
					$survey_global = "";
				}
				if ( isset( $_REQUEST[ 'keep_votes' ] ) ) {
					$keep_votes = sanitize_text_field( $_REQUEST[ 'keep_votes' ] );
				}
				else {
					$keep_votes = 0;
				}
				if ( isset( $_REQUEST[ 'options' ] ) ) {
					$survey_options = trim( $_REQUEST[ 'options' ] );
				}
				else {
					$survey_options = "";
				}
				if ( isset( $_REQUEST[ 'qa' ] ) ) {
					$survey_qa = nl2br( $_REQUEST[ 'qa' ] );
				}
				else {
					$survey_qa = "";
				}
				if ( isset( $_REQUEST[ 'mode' ] ) ) {
					$survey_mode = sanitize_text_field( $_REQUEST[ 'mode' ] );
				}
				else {
					$survey_mode = "";
				}
				if ( isset( $_REQUEST[ 'type' ] ) ) {
					$survey_type = sanitize_text_field( $_REQUEST[ 'type' ] );
				}
				else {
					$survey_type = "";
				}
				if ( isset( $_REQUEST[ 'samesession' ] ) ) {
					$samesession = sanitize_text_field( $_REQUEST[ 'samesession' ] );
				}
				if ( isset( $_REQUEST[ 'qo' ] ) ) {
					$survey_qo = json_decode( stripslashes( sanitize_text_field( $_REQUEST[ 'qo' ] ) ) );
				}
				else {
					$survey_qo = array();
				}
				if ( isset( $_REQUEST[ 'ao' ] ) ) {
					$survey_ao = json_decode( stripslashes( $_REQUEST[ 'ao' ] ) );
					$survey_ao = ( array ) $survey_ao[ 0 ];
				}
				else {
					$survey_ao = array();
				}
				$survey_check = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . $wpdb->base_prefix . "modal_survey_surveys WHERE `id` = %d", $survey_id ) );
				if ( $_REQUEST[ 'sspcmd' ] == "zip" ) {
					$files = json_decode( stripslashes( $_REQUEST[ 'files' ] ) );
					$filestoexport = array();
					foreach( $files as $fs ) {
						$filestoexport[] = sprintf( "%s" . MSDIRS . "exports" . MSDIRS, dirname( __FILE__ ) ) . $fs[ 2 ] . "-" . $fs[ 0 ] . "-" . $fs[ 1 ] . "." . $fs[ 3 ];
					}
					$unid = date( "YmdHis" );
					$destination = sprintf( "%s" . MSDIRS . "exports" . MSDIRS . "modalsurvey-export-" . $unid . ".zip", dirname( __FILE__ ) );
					if ( $this->create_zip( $filestoexport, $destination, true ) ) {
						foreach( $filestoexport as $fte ) {
							@unlink( $fte );
						}
						die( "success:" . $unid );
					}
				}

				if ( $_REQUEST[ 'sspcmd' ] == "remove_results" ) {
					if ( isset( $_REQUEST[ 'part' ] ) ) {
						$parttemp = explode( "-", sanitize_text_field( $_REQUEST[ 'part' ] ) );
						$pvotes = $wpdb->get_results( $wpdb->prepare( "SELECT qid, aid FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE `sid` = %s AND uid = %s", $parttemp[ 1 ], $parttemp[ 0 ] ) );
						$indvotes = array();
						foreach( $pvotes as $pv ) {
							if ( ! isset( $indvotes[ $pv->qid ][ $pv->aid ] ) ) {
								$indvotes[ $pv->qid ][ $pv->aid ] = 1;
							}
							else {
								$indvotes[ $pv->qid ][ $pv->aid ]++;
							}
						}
						foreach( $indvotes as $qkey => $qiv ) {
							foreach( $qiv as $akey => $aiv ) {
								$current_votes = $wpdb->get_var( $wpdb->prepare( "SELECT `count` FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE `survey_id` = %s AND `question_id` = %d AND `autoid` = %d", $parttemp[ 1 ], $qkey, $akey ) );
								if ( ( $current_votes - $aiv ) < 0 ) {
									$aiv = $current_votes;
								}
								if ( $current_votes > 0 ) {
									$set_votes = $wpdb->query( $wpdb->prepare( "UPDATE " . $wpdb->base_prefix . "modal_survey_answers SET 
										count = ( count - %d )
										WHERE survey_id = %s AND question_id = %d AND autoid = %d ", $aiv, $parttemp[ 1 ], $qkey, $akey ) );
								}
								else {
									$set_votes = "true";
								}
							}
						}
					}
					if ( $set_votes ) {
						die( "success" );
					}
					else {
						die( "SQL error" );
					}
				}

				if ( $_REQUEST[ 'sspcmd' ] == "export" ) {
					if ( ! isset( $samesession ) ) {
						$samesession = '';
					}
					$this->result = $this->export_survey( $survey_mode, $survey_id, $survey_type, $samesession );
					die( $this->result );
				}
				if ($_REQUEST['sspcmd']=="aexport") {
					if ( ! file_exists( sprintf( "%s/exports", dirname( __FILE__ ) ) ) ) {
						mkdir( sprintf( "%s/exports", dirname( __FILE__ ) ), 0777, true );
					}
					if ( isset( $_REQUEST[ 'type' ] ) ) {
						$exporttype = sanitize_text_field( $_REQUEST[ 'type' ] );
					}
					else {
						$exporttype = 'txt';
					}
					if ( isset( $_REQUEST[ 'sid' ] ) ) {
						$sid = sanitize_text_field( $_REQUEST[ 'sid' ] );
					}
					if ( isset( $_REQUEST[ 'qid' ] ) ) {
						$qid = sanitize_text_field( $_REQUEST[ 'qid' ] );
					}
					if ( isset( $_REQUEST[ 'auid' ] ) ) {
						$auid = sanitize_text_field( $_REQUEST[ 'auid' ] );
					}
					$sql = "SELECT question FROM " . $wpdb->base_prefix . "modal_survey_questions WHERE survey_id = %d AND id = %d";
					$question = $wpdb->get_var( $wpdb->prepare( $sql, $sid, $qid ) );
					$answers_text = $wpdb->get_results( $wpdb->prepare( "SELECT answertext, count, id FROM " . $wpdb->base_prefix . "modal_survey_answers_text WHERE `survey_id` = %d AND `id` = %d ORDER BY count DESC", $sid, $auid ) );
					if ( ! empty( $exporttype ) ) {
						require_once( sprintf( "%s/modules/export_" . $exporttype . ".php", dirname( __FILE__ ) ) );
					}
					die($result);
				}
				if ( $_REQUEST[ 'sspcmd' ] == "duplicate" ) {
						$sql_ch = "SELECT id FROM " . $wpdb->base_prefix . "modal_survey_surveys WHERE id = %d";
						$ch_srvy = $wpdb->get_var( $wpdb->prepare( $sql_ch, $survey_nid ) );
						if ( $ch_srvy ) {
							die( "exists" );
						}
						$dsurveys = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_surveys mss WHERE mss.id = %d", $survey_id ) );
						$wpdb->insert( $wpdb->base_prefix . "modal_survey_surveys", array( 
							'id' => $survey_nid, 
							'name' => $survey_name, 
							'options' => $dsurveys[ 0 ]->options, 
							'start_time' => $dsurveys[ 0 ]->start_time,
							'expiry_time'=> $dsurveys[ 0 ]->expiry_time,
							'created'=> date( "Y-m-d H:i:s" ),
							'updated'=> date( "Y-m-d H:i:s" ),
							'owner'=> get_current_user_id(),
							'global'=> $dsurveys[ 0 ]->global
							) );
						$dsurveys_q = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_questions msq WHERE msq.survey_id = %d ORDER BY msq.id ASC", $survey_id ) );
						foreach( $dsurveys_q as $keyq=>$qr ) {
							$wpdb->insert( $wpdb->base_prefix . "modal_survey_questions", array( 
								'id' => $qr->id, 
								'survey_id' => $survey_nid, 
								'question' => $qr->question,
								'qoptions' => $qr->qoptions
								) );
						}
						$dsurveys_a = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_answers msa WHERE msa.survey_id = %d ORDER BY msa.autoid ASC", $survey_id ) );
						foreach( $dsurveys_a as $keya=>$ar ) {
							if ( $keep_votes == 1 ) {
								$arcount = $ar->count;
							}
							else {
								$arcount = 0;
							}
							$thisaoptions = unserialize( $ar->aoptions );
							$thisaoptions[ 1 ] = str_replace( ".", "", uniqid( rand(), true ) );
							$wpdb->insert( $wpdb->base_prefix . "modal_survey_answers", array( 
								'survey_id' => $survey_nid, 
								'question_id' => $ar->question_id,
								'answer' => $ar->answer,
								'aoptions' => serialize( $thisaoptions ),
								'count' => $arcount,
								'autoid' => $ar->autoid,
								'uniqueid' => $thisaoptions[ 1 ]
								) );				
						}
					die( 'duplicated' );
				}
				if ( $_REQUEST[ 'sspcmd' ] == "delete_open_answer" ) {
					if ( $open_answer_text != "" && $survey_id != "" && $unique_id != "" ) {
						$result = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_answers_text WHERE `survey_id` = %d AND `id` = %s AND `answertext` = %s", $survey_id, $unique_id, $open_answer_text ) );
						if ( $result ) {
							die('success');
						}
					}
				}
				if ( $_REQUEST[ 'sspcmd' ]== "add" ) {
					if ( strlen( $survey_name ) < 3 ) {
							die( "Name too short" );
					}
					if ( $survey_check > 0 ) {
							die( "Exists" );
					}
					else {
						$autogs = get_option( 'setting_globalsurvey' );
						$wpdb->insert( $wpdb->base_prefix . "modal_survey_surveys", array( 
						'id' => $survey_id, 
						'name' => $survey_name, 
						'created' => date( "Y-m-d H:i:s" ),
						'updated' => date( "Y-m-d H:i:s" ),
						'owner' => get_current_user_id(),
						'global' => ( $autogs != "off" ? '1' : '0' ),
						'options' => '["bottom","easeInOutBack","","-webkit-linear-gradient(top , rgb(244, 244, 244) 97% , rgb(244, 244, 244) 100%);-moz-linear-gradient(top , rgb(244, 244, 244) 97% , rgb(244, 244, 244) 100%);-ms-linear-gradient(top , rgb(244, 244, 244) 97% , rgb(244, 244, 244) 100%);-o-linear-gradient(top , rgb(244, 244, 244) 97% , rgb(244, 244, 244) 100%);linear-gradient(top , rgb(244, 244, 244) 97% , rgb(244, 244, 244) 100%);","rgb(31, 31, 31)","rgb(206, 196, 196)","1","3","16","20","16",500,"Thank you for your feedback!","0","0","0","center","modal","0","","0","","0","3000"]'
						) );
					$wpdb->insert( $wpdb->base_prefix."modal_survey_questions", array( 
						'id' => 1,
						'survey_id' => $survey_id, 
						'question' => esc_html__( 'Do you like this website?', MODAL_SURVEY_TEXT_DOMAIN ),
						'qoptions' => ''
						) );

					$wpdb->insert( $wpdb->base_prefix."modal_survey_answers", array(
						'survey_id' => $survey_id, 
						'question_id' => 1,
						'answer' => 'Yes',
						'aoptions' => serialize( array(
							'default', '', '0', '', '1', '', '', '', '0', '0', '', '', '', '', ''
						) ),
						'autoid' => 1,
						'uniqueid' => md5( 'Do you like this website?Yes' )
						) );
					$wpdb->insert( $wpdb->base_prefix."modal_survey_answers", array(
						'survey_id' => $survey_id, 
						'question_id' => 1,
						'answer' => 'No',
						'aoptions' => serialize( array(
							'default', '', '0', '', '1', '', '', '', '0', '0', '', '', '', '', ''
						) ),
						'autoid' => 2,
						'uniqueid' => md5( 'Do you like this website?No' )
						) );
					die( admin_url( 'admin.php?page=modal_survey_savedforms&modal_survey_id=' . $survey_id ) . '&status=newsuccess');
					}
				}
				if ( $_REQUEST[ 'sspcmd' ] == "save" ) {
					if ( $survey_check > 0 ) {
					//update survey
						$wpdb->update( $wpdb->base_prefix."modal_survey_surveys", array( 
															'name' => $survey_name, 
															"options" => $survey_options, 
															"start_time" => $survey_start_time, 
															'expiry_time' => $survey_expiry_time, 
															'updated' => date("Y-m-d H:i:s"), 
															'owner' => get_current_user_id(), 
															'global' => $survey_global
															),array('id' => $survey_id));
						$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_questions WHERE `survey_id` = %d", $survey_id ) );
						$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE `survey_id` = %d", $survey_id ) );
							$qa_object = (array)json_decode( stripslashes( $survey_qa ) );
							$qa_array = (array)$qa_object;
							foreach( $qa_array as $keyq=>$qr ) {
								foreach( $qr as $key=>$oa ) {
									if ( $key == 0 ) {
										$wpdb->insert( $wpdb->base_prefix . "modal_survey_questions", array( 
											'id' => ( $keyq + 1 ), 
											'survey_id' => $survey_id, 
											'question' => $oa,
											'qoptions' => serialize( $survey_qo[ $keyq ] )
											) );
									}
									else {
									$oans = explode( "->", $oa );
									$wpdb->insert( $wpdb->base_prefix . "modal_survey_answers", array( 
										'survey_id' => $survey_id, 
										'question_id' => ( $keyq + 1 ),
										'answer' => $oans[ 0 ],
										'aoptions' => serialize( $survey_ao[ ( $keyq + 1 ) . "_" . $key ] ),
										'count' => $oans[ 1 ],
										'autoid' => $key,
										'uniqueid' => $survey_ao[ ( $keyq + 1 ) . "_" . $key ][ 1 ]
										) );
									}
								}
							}
						$wpdb->query( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_answers_text WHERE `id` NOT IN  (SELECT msa.uniqueid FROM " . $wpdb->base_prefix . "modal_survey_answers msa)" );
						die( "updated" );
					}
					else {
					//insert survey
						$wpdb->insert( $wpdb->base_prefix."modal_survey_surveys", array( 
							'id' => $survey_id, 
							'name' => $survey_name, 
							'options' => $survey_options, 
							'start_time' => $survey_start_time,
							'expiry_time'=> $survey_expiry_time,
							'created'=> date( "Y-m-d H:i:s" ),
							'updated'=> date( "Y-m-d H:i:s" ),
							'owner'=> get_current_user_id(),
							'global'=> $survey_global
							) );
							$qa_object = (array)json_decode( stripslashes( $survey_qa ) );
							$qa_array = (array)$qa_object;
							foreach( $qa_array as $keyq=>$qr ) {
								foreach($qr as $key=>$oa) {
									if ( $key == 0 ) {
										$wpdb->insert( $wpdb->base_prefix . "modal_survey_questions", array( 
											'id' => ( $keyq + 1 ), 
											'survey_id' => $survey_id, 
											'question' => $oa,
											'qoptions' => serialize( $survey_qo[ $keyq ] )
											) );
									}
									else {
									$oans = explode( "->", $oa );
									$wpdb->insert( $wpdb->base_prefix . "modal_survey_answers", array( 
										'survey_id' => $survey_id, 
										'question_id' => ( $keyq + 1 ),
										'answer' => $oans[ 0 ],
										'aoptions' => serialize( $survey_ao[ ( $keyq + 1 ) . "_" . $key ] ),
										'autoid' => $key,
										'uniqueid' => $survey_ao[ ( $keyq + 1 ) . "_" . $key ][ 1 ]
										) );					
									}
								
								}
							}
						die( 'success' );
					}
				}
				elseif( $_REQUEST[ 'sspcmd' ] == "delete" ) {
					if ($survey_check>0) {
						$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_surveys WHERE `id` = %d", $survey_id ) );
						$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_questions WHERE `survey_id` = %d", $survey_id ) );
						$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_answers WHERE `survey_id` = %d", $survey_id ) );
						$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_answers_text WHERE `survey_id` = %d", $survey_id ) );
						die( "deleted" );
					}
				}
				elseif( $_REQUEST[ 'sspcmd' ] == "reset" ) {
				$wpdb->update( $wpdb->base_prefix . "modal_survey_answers", array( "count" => "0"), array( 'survey_id' => $survey_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_answers_text WHERE `survey_id` = %d", $survey_id ) );
				$get_survey = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "modal_survey_surveys WHERE `id` = %d", $survey_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "modal_survey_surveys WHERE `id` = %d", $survey_id ) );
				$wpdb->insert( $wpdb->base_prefix . "modal_survey_surveys", array( 
					'id' => $get_survey->id, 
					'name' => $get_survey->name, 
					'options' => $get_survey->options, 
					'start_time' => $get_survey->start_time,
					'expiry_time'=> $get_survey->expiry_time,
					'updated'=> date( "Y-m-d H:i:s" ),
					'owner'=> get_current_user_id(),
					'global'=> $get_survey->global
					) );		
					die( "reset" );
				}
			}
		}
	}
}
?>