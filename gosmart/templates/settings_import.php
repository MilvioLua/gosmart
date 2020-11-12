<div id="screen_preloader"><h3>Modal Survey for WordPress</h3><img src="<?php print(plugins_url( '/assets/img/screen_preloader.gif' , __FILE__ ));?>"><h5><?php esc_html_e( 'LOADING', MODAL_SURVEY_TEXT_DOMAIN );?><br><br><?php esc_html_e( 'Please wait...', MODAL_SURVEY_TEXT_DOMAIN );?></h5></div>
<div class="wrap pantherius-jquery-ui wrap-padding">
	<?php
	$ierror = ""; $result = "";
	if ( isset( $_REQUEST[ 'modal-survey-import-nonce' ] ) ) {
		if ( isset( $_REQUEST[ 'survey_name' ] ) ) {
			$survey_name = sanitize_text_field( $_REQUEST[ 'survey_name' ] );
		}
		else {
			$survey_name = "";
		}
		if ( isset( $_REQUEST[ 'import_modal_survey_id' ] ) ) {
			$survey_id = sanitize_text_field( $_REQUEST[ 'import_modal_survey_id' ] );
		}
		else {
			$survey_id = "";
		}
		if ( ! empty( $_FILES[ 'modalsurvey_importfile' ][ 'tmp_name' ] ) ) {
			$json_content = json_decode( file_get_contents( $_FILES[ 'modalsurvey_importfile' ][ 'tmp_name' ] ) );
		}
		else {
			$json_content = "";
		}
		if ( isset( $_REQUEST[ 'modal-survey-import-nonce' ] ) ) {
			$nonce = sanitize_text_field( $_REQUEST[ 'modal-survey-import-nonce' ] );
		}
		else {
			$nonce = "";
		}
		if ( ! wp_verify_nonce( $nonce, 'modal-survey-import' ) ) {
			$ierror = esc_html__( 'Security check failed.', MODAL_SURVEY_TEXT_DOMAIN );	
		}
		global $wpdb;
		$checksurvey = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) AS COUNT FROM " . $wpdb->base_prefix . "modal_survey_surveys WHERE `id` = %d", $survey_id ) );
		if ( $survey_name == "" ) {
			$ierror = esc_html__( 'Survey name is required. Import failed.', MODAL_SURVEY_TEXT_DOMAIN );
		}
		if ( $survey_id == "" ) {
			$ierror = esc_html__( 'Survey ID generation failed. Symbols and special characters are not allowed.', MODAL_SURVEY_TEXT_DOMAIN );
		}
		if ( isset( $checksurvey[ 'COUNT' ] ) ) {
			if ( $checksurvey[ 'COUNT' ] > 0 ) {
				$ierror = esc_html__( 'Survey name is already exists. Import failed.', MODAL_SURVEY_TEXT_DOMAIN );
			}
		}
		if ( $json_content == "" || ! isset( $json_content->questions ) ) {
			$ierror = esc_html__( 'File empty or not valid JSON file. Import failed.', MODAL_SURVEY_TEXT_DOMAIN );
		}
		if ( $ierror == "" ) {
			$wpdb->insert( $wpdb->base_prefix . "modal_survey_surveys", array( 
			'id' => $survey_id, 
			'name' => $survey_name, 
			'options' => $json_content->options, 
			'start_time' => $json_content->start_time,
			'expiry_time'=> $json_content->expiry_time,
			'created'=> date("Y-m-d H:i:s"),
			'updated'=> date("Y-m-d H:i:s"),
			'owner'=> get_current_user_id(),
			'global'=> $json_content->global
			) );
			$c = 0;
			foreach ( $json_content->questions as $jcq ) {
						$wpdb->insert( $wpdb->base_prefix . "modal_survey_questions", array( 
							'id' => ($c+1), 
							'survey_id' => $survey_id, 
							'question' => $jcq->name,
							'qoptions' => $jcq->qoptions
							) );
							$qid = $wpdb->insert_id;
						foreach ( $jcq as $keya => $jca ) {
			if ( isset( $jca->answer ) ) {
				$uid = (floor(rand(1000000,9999999) * 1000000 + microtime()));
				$aopts = unserialize( $jca->aoptions );
				$aopts[1] = $uid;
						$wpdb->insert( $wpdb->base_prefix . "modal_survey_answers", array( 
							'survey_id' => $survey_id, 
							'question_id' => ($c+1),
							'answer' => $jca->answer,
							'count' => 0,
							'autoid' => $keya,
							'aoptions' => serialize( $aopts ),
							'uniqueid' => $uid
							) );					
						}
					}
				$c++;
			}
		}
	}
	?>
	<br />
	<div class="title-border">
		<h3><?php esc_html_e( 'Import', MODAL_SURVEY_TEXT_DOMAIN );?></h3>
		<div class="help_link"><a target="_blank" href="http://modalsurvey.pantherius.com/documentation/#line6"><?php esc_html_e( 'Documentation', MODAL_SURVEY_TEXT_DOMAIN );?></a></div>	
	</div>
		<form method="post" name="modal_survey_import_form" id="modal_survey_import_form" enctype="multipart/form-data">
		<?php
		if ( $ierror == "" && isset( $qid ) ) {
		?>
		<div class="updated">
			<p><?php esc_html_e( 'File successfully imported!', MODAL_SURVEY_TEXT_DOMAIN ); ?> <a href="<?php echo esc_url( admin_url('admin.php?page=modal_survey_savedforms&modal_survey_id=' . $survey_id ) );?>"><?php esc_html_e( 'Edit', MODAL_SURVEY_TEXT_DOMAIN ); ?></a></p>
		</div>
		<?php
		}
		?>
		<div class="modal_survey_import_form">
			<div class="modal-survey-import-row">
				<div class="settings_field"><?php esc_html_e( 'Imported Survey Name:', MODAL_SURVEY_TEXT_DOMAIN );?></div>
				<input type="text" id="survey_name" name="survey_name" value="" size="50" placeholder="Type the survey name here" />
			</div>
			<div class="modal-survey-import-row">
				<input type="hidden" name="import_modal_survey" value="upload">
				<input type="hidden" name="import_modal_survey_id" id="import_modal_survey_id" value="">
				<input type="hidden" name="modal-survey-import-nonce" value="<?php echo wp_create_nonce("modal-survey-import");?>">
				<div class="settings_field"><?php esc_html_e( 'Browse the import JSON file:', MODAL_SURVEY_TEXT_DOMAIN );?></div>
				<input type="file" name="modalsurvey_importfile">
				<a href="Javascript: void(0);" class="button button-secondary button-small import_survey-submit"><?php esc_html_e( 'UPLOAD', MODAL_SURVEY_TEXT_DOMAIN );?></a>
			</div>
		</div>
		<span class="import-notice"><?php echo esc_attr( $ierror ); ?></span>
	</form>
	<?php
		$surveys = $this->wpdb->get_results( "SELECT mss.id FROM " . $this->wpdb->base_prefix . "modal_survey_surveys mss");
		foreach($surveys as $sv) {
			print('<div id="' . $sv->id . '" class="ms-hidden"></div>');
		}
	?>
</div>