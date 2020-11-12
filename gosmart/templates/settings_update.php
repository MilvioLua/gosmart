	<div id="screen_preloader"><h3>Modal Survey for WordPress</h3><img src="<?php print( plugins_url( '/assets/img/screen_preloader.gif' , __FILE__ ) );?>"><h5><?php esc_html_e( 'LOADING', MODAL_SURVEY_TEXT_DOMAIN );?><br><br><?php esc_html_e( 'Please wait...', MODAL_SURVEY_TEXT_DOMAIN );?></h5></div>
	<div class="wrap pantherius-jquery-ui wrap-padding">
	<br />
	<div class="title-border">
		<h3><?php esc_html_e( 'Update', MODAL_SURVEY_TEXT_DOMAIN );?></h3>
		<div class="help_link"><a target="_blank" href="http://modalsurvey.pantherius.com/documentation/#line7"><?php esc_html_e( 'Documentation', MODAL_SURVEY_TEXT_DOMAIN );?></a></div>
	</div>
	<?php
	if ( isset( $_REQUEST[ 'ms_update_db' ] ) ) {
		if ( $this->update_modal_survey_db() ) {
			print( '<br>' . esc_html__( 'Database updated successfully', MODAL_SURVEY_TEXT_DOMAIN ) );
		}
		else {
			print( '<br>' . esc_html__( 'Database already updated', MODAL_SURVEY_TEXT_DOMAIN ) );		
		}
	}
		require_once(str_replace('templates','',sprintf("%s/modules/manual.update.php", dirname(__FILE__))));
		manual_plugin_updater::getInstance(
		'modal_survey/modal_survey.php',
		'modal_survey/modal_survey.php',
		array(),
		'modal_survey'
		);

		$ms_db_version = get_option( 'setting_db_modal_survey' );
		 print( '<p>' . esc_html__( 'Current Plugin Version', MODAL_SURVEY_TEXT_DOMAIN ) . ': ' . MODAL_SURVEY_VERSION . '</p>' );
		 print( '<p>' . esc_html__( 'Plugin Database Version', MODAL_SURVEY_TEXT_DOMAIN ) . ': ' . ( ! empty( $ms_db_version ) ? $ms_db_version : 'Unknown' ) );
		 print( '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . esc_url( admin_url( 'admin.php?page=modal_survey_update&ms_update_db=true' ) ) . '">' . esc_html__( 'Update DB', MODAL_SURVEY_TEXT_DOMAIN ) . '</a>' );
		 print( '</p>' );
		if ( isset( $_REQUEST[ 'clear_part' ] ) ) {
			$this->wpdb->query("TRUNCATE TABLE " . $this->wpdb->base_prefix . 'modal_survey_participants');
			$this->wpdb->query("TRUNCATE TABLE " . $this->wpdb->base_prefix . 'modal_survey_participants_details');
			print( '<br><br>success' );
		}
		
		if ( isset( $_REQUEST[ 'create_tables' ] ) ) {
						$charset_collate = '';
			if ( ! empty( $this->wpdb->charset ) ) {
			  $charset_collate = "DEFAULT CHARACTER SET {$this->wpdb->charset}";
			}

			if ( ! empty( $wpdb->collate ) ) {
			  $charset_collate .= " COLLATE {$this->wpdb->collate}";
			}
			$sql = "CREATE TABLE IF NOT EXISTS " . $this->wpdb->base_prefix . 'modal_survey_surveys' . " (
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
			$this->wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS ".$this->wpdb->base_prefix . 'modal_survey_questions' . " (
			  id mediumint(9) NOT NULL,
			  survey_id varchar(255) NOT NULL,
			  question text NOT NULL,
			  qoptions text NOT NULL
			) $charset_collate";
			$this->wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS " . $this->wpdb->base_prefix . 'modal_survey_answers' . " (
			  survey_id varchar(255) NOT NULL,
			  question_id mediumint(9) NOT NULL,
			  answer text NOT NULL,
			  aoptions text NOT NULL,
			  count mediumint(9) DEFAULT '0' NOT NULL,
			  autoid mediumint(9) NOT NULL,
			  uniqueid varchar(255) NOT NULL
			) $charset_collate";
			$this->wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS " . $this->wpdb->base_prefix . 'modal_survey_answers_text' . " (
			id varchar(255) NOT NULL,
			survey_id varchar(255) NOT NULL,
			answertext text NOT NULL,
			count mediumint(9) DEFAULT '0' NOT NULL
			) $charset_collate";
			$this->wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS " . $this->wpdb->base_prefix . 'modal_survey_participants' . " (
			  autoid mediumint(9) NOT NULL AUTO_INCREMENT,
			  id varchar(255) NOT NULL,
			  username varchar(255) NOT NULL,
			  email varchar(255) NOT NULL,
			  name varchar(255) NOT NULL,
			  custom text NOT NULL,
			  UNIQUE KEY autoid (autoid)
			) $charset_collate";
			$this->wpdb->query( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS " . $this->wpdb->base_prefix . 'modal_survey_participants_details' . " (
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
			$this->wpdb->query( $sql );
			print( '<br><br>success' );
		}
		$mytables=$this->wpdb->get_results("SHOW TABLES");
		print( '<br><br><h4>' . esc_html__( 'Plugin tables in the database', MODAL_SURVEY_TEXT_DOMAIN ) . '</h4>' );
		foreach ( $mytables as $mytable ) {
			foreach ( $mytable as $t ) {       
				if ( strpos( $t, 'modal_survey' ) !== false ) {
					echo esc_attr( $t ) . "<br>";
				}
			}
		}
		global $wp_version;
		print( '<br><br>' . esc_html__( 'Current WP Version', MODAL_SURVEY_TEXT_DOMAIN ) . ': ' . $wp_version );

		print( '<br><br><div class="notice notice-error"><p>' . esc_html__( 'WARNING! Please only use the buttons below if you know exactly what you are doing.', MODAL_SURVEY_TEXT_DOMAIN ) . '</p></div>' ); 
		print( '<br><br><form method="POST" class="ms_iblock"><input type="submit" name="clear_part" value="' . esc_html__( 'CLEAR ALL PARTICIPANTS TABLES', MODAL_SURVEY_TEXT_DOMAIN ) . '"></form><form method="POST" class="ms_iblock"><input type="submit" name="create_tables" value="' . esc_html__( 'RECREATE MISSING TABLES', MODAL_SURVEY_TEXT_DOMAIN ) . '"></form>' );
	?>
	</div>