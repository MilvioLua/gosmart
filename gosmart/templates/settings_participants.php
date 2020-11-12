<div id="screen_preloader"><h3>Modal Survey for WordPress</h3><img src="<?php print(plugins_url( '/assets/img/screen_preloader.gif' , __FILE__ ));?>"><h5><?php esc_html_e( 'LOADING', MODAL_SURVEY_TEXT_DOMAIN );?><br><br><?php esc_html_e( 'Please wait...', MODAL_SURVEY_TEXT_DOMAIN );?></h5></div>
<div class="wrap pantherius-jquery-ui wrap-padding">
<br />
<div class="title-border">
	<h3><?php esc_html_e( 'Participants', MODAL_SURVEY_TEXT_DOMAIN );?></h3>
	<div class="help_link"><a target="_blank" href="http://modalsurvey.pantherius.com/documentation/#line3"><?php esc_html_e( 'Documentation', MODAL_SURVEY_TEXT_DOMAIN );?></a></div>
</div>
<?php
$wp_timezone = get_option( 'timezone_string' );
if ( isset( $_REQUEST[ 'delete_samesession' ] ) ) {
	$cmsuid = explode( "-", $_REQUEST[ 'msuid' ] );
	$result = $this->wpdb->query( $this->wpdb->prepare( "DELETE FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE `uid` = %d AND `samesession` = %s", $cmsuid[ 0 ], $_REQUEST[ 'delete_samesession' ] ) );
	if ( $result ) {
		echo '<div class="updated"><p>'.esc_html__( 'Data Successfully Deleted!', MODAL_SURVEY_TEXT_DOMAIN ).'</p></div>';
	}
	else {
		echo '<div class="error"><p>'.esc_html__( 'Error Occurred During the Deletion!', MODAL_SURVEY_TEXT_DOMAIN ).'</p></div>';
	}
}

if ( isset( $_REQUEST[ 'delete_incomplete' ] ) ) {
	$deleted = 0;
	$surveys = $this->wpdb->get_results( "SELECT id, name, options FROM " . $this->wpdb->base_prefix . "modal_survey_surveys" );
		foreach( $surveys as $sv ) {
			$thopts = json_decode( stripslashes( $sv->options ) );
			if ( isset( $thopts[ 125 ] ) ) {
				$survey[ $sv->id ][ 'pform' ] = $thopts[ 125 ];
			}
			else {
				$survey[ $sv->id ][ 'pform' ] = 0;
			}
				$survey[ $sv->id ][ 'name' ] = $sv->name;
			$survey[ $sv->id ][ 'maxq' ] = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM " . $this->wpdb->base_prefix . "modal_survey_questions WHERE survey_id = %s ORDER BY id DESC LIMIT 1", $sv->id ) );
		}
		$user_surveys = $this->wpdb->get_results( "SELECT mspd.sid, msp.email, mspd.uid, mspd.samesession FROM " . $this->wpdb->base_prefix . "modal_survey_participants msp LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_participants_details mspd on mspd.uid = msp.autoid GROUP BY mspd.samesession ORDER BY mspd.qid DESC" );
		foreach( $user_surveys as $key=>$usv ) {
			$user_surveys[ $key ]->maxq = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT qid FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %s AND samesession = %s AND uid = %s ORDER BY CAST(qid AS UNSIGNED) DESC LIMIT 1", $usv->sid, $usv->samesession, $usv->uid ) );
			if ( ( $user_surveys[ $key ]->maxq < $survey[ $usv->sid ][ 'maxq' ] ) || ( $survey[ $usv->sid ][ 'pform' ] == 1 && empty( $usv->email ) ) ) {
				$delresult = $this->wpdb->query( $this->wpdb->prepare( "DELETE FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE `samesession` = %s AND `sid` = %s AND `uid` = %s", $usv->samesession, $usv->sid, $usv->uid ) );
				$deleted++;
			}
		}
		if ( $deleted > 0 && $delresult ) {
			echo '<div class="updated"><p>' . $deleted . ' ' . esc_html__( 'entries successfully deleted!', MODAL_SURVEY_TEXT_DOMAIN ).'</p></div>';
		}
		else {
			echo '<div class="error"><p>'.esc_html__( 'There is no incomplete results!', MODAL_SURVEY_TEXT_DOMAIN ).'</p></div>';
		}
}

if ( isset( $_REQUEST[ 'delete_participants' ] ) ) {
	$dp = json_decode( stripslashes( $_REQUEST[ 'delete_participants' ] ) );
	foreach( $dp as $voters ) {
		$vid = explode( "-", $voters );
		if ( $vid[ 1 ] ) {
			$result = $this->wpdb->query( $this->wpdb->prepare( "DELETE FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE `uid` = %d AND `sid` = %s", $vid[ 0 ], $vid[ 1 ] ) );
		}
		elseif( $vid[ 0 ] ) {
			$result = $this->wpdb->query( $this->wpdb->prepare( "DELETE FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE `uid` = %d", $vid[ 0 ] ) );
		}
	}
	$result2 = $this->wpdb->query( "DELETE FROM " . $this->wpdb->base_prefix . "modal_survey_participants WHERE autoid NOT IN (SELECT mspd.uid 
                        FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details mspd )" );
	if ( $result || $result2 ) {
		echo '<div class="updated"><p>'.esc_html__( 'Selected Rows Successfully Deleted!', MODAL_SURVEY_TEXT_DOMAIN ).'</p></div>';
	}
	else {
		echo '<div class="error"><p>'.esc_html__( 'Error Occurred During the Deletion!', MODAL_SURVEY_TEXT_DOMAIN ).'</p></div>';
	}
}
if ( isset( $_REQUEST[ 'msuid' ] ) ) {
	$vid = explode( "-", $_REQUEST[ 'msuid' ] );
	$ms_user = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM " . $this->wpdb->base_prefix . "modal_survey_participants WHERE autoid = %d ", $vid[ 0 ] ) );
	$ms_user_ip = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT ip FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE uid = %d GROUP BY ip ORDER BY time DESC", $vid[ 0 ] ) );
	$ms_user_post = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT postid, samesession FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE uid = %d GROUP BY postid ORDER BY time DESC", $vid[ 0 ] ) );
	$ms_user_survey = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT mspd.sid, mss.name FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_surveys mss on mspd.sid = mss.id WHERE uid = %d GROUP BY sid ORDER BY time DESC", $vid[ 0 ] ) );
	echo "<div class='ms-user-panel' id='msps-" . $_REQUEST[ 'msuid' ] . "'>";
	echo "<div class='ms-user-avatar'>" . get_avatar( $ms_user->email ) . "</div>";
	echo "<div class='ms-details'><div class='title'>" . esc_html__( 'Name', MODAL_SURVEY_TEXT_DOMAIN ) . "</div><div class='data'>". ( $ms_user->name ? $ms_user->name : esc_html__( 'Anonymous', MODAL_SURVEY_TEXT_DOMAIN ) ) . "</div></div>";
	echo "<div class='ms-details'><div class='title'>" . esc_html__( 'Username', MODAL_SURVEY_TEXT_DOMAIN ) . "</div><div class='data'>" . ( $ms_user->username ? $ms_user->username : esc_html__( 'Not Specified', MODAL_SURVEY_TEXT_DOMAIN ) ) . "</div></div>";
	echo "<div class='ms-details'><div class='title'>" . esc_html__( 'Email Address', MODAL_SURVEY_TEXT_DOMAIN ) . "</div><div class='data'>". ( $ms_user->email ? $ms_user->email : esc_html__( 'Not Specified', MODAL_SURVEY_TEXT_DOMAIN ) ) . "</div></div>";
	$ms_user_custom_uns = unserialize( $ms_user->custom );
	if ( ! empty( $ms_user_custom_uns ) ) {
		foreach ( $ms_user_custom_uns as $muc_index=>$muc ) {
			echo "<div class='ms-details'><div class='title'>" . ucfirst( strtolower( $muc_index ) ) . "</div><div class='data'>". ( $muc ? $muc : esc_html__( 'Not Specified', MODAL_SURVEY_TEXT_DOMAIN ) ) . "</div></div>";
			
		}
	}
	
	echo "<div class='ms-details'><div class='title'>" .esc_html__( 'Survey URL', MODAL_SURVEY_TEXT_DOMAIN ) . "</div>";
	$c = 0;
	foreach( $ms_user_post as $mup ) {
		$permalink = get_permalink( $mup->postid );
		if ( $permalink ) {
			echo "<div class='data'><a target='_blank' href='" . get_permalink( $mup->postid ) . "'>" . get_the_title( $mup->postid ) . "</a></div>";
			$c++;
		}
	}
	if ( $c == 0 ) {
			echo "<div class='data'>" . esc_html__( 'Not Specified', MODAL_SURVEY_TEXT_DOMAIN ) . "</div>";		
	}
	echo "</div>";
	echo "<div class='ms-details'><div class='title'>" .esc_html__( 'IP Address', MODAL_SURVEY_TEXT_DOMAIN ) . "</div>";
	foreach( $ms_user_ip as $mui ) {
		echo "<div class='data'>" . $mui->ip . "</div>";		
	}
	echo "</div>";
	echo "<div class='ms-details'><div class='title'>" .esc_html__( 'Surveys', MODAL_SURVEY_TEXT_DOMAIN ) . "</div>";
	foreach( $ms_user_survey as $mus ) {
		echo "<div class='data'><a href='" . esc_url( admin_url( "admin.php?page=modal_survey_participants&msuid=" . $vid[ 0 ] . "-" . $mus->sid . "" ) ) . "'>" . $mus->name. "</a></div>";		
	}
	echo "</div>";
	echo "</div>";
	echo "<div class='ms-userstat-panel'>";
	echo "<div class='ms-userstat-panel-buttons'>";
	$thissession = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT aid FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %s AND uid = %s ORDER BY time DESC", $vid[ 1 ], $vid[ 0 ] ) );
	if ( empty( $thissession ) ) {
		print( '<div class="nodata">' . esc_html__( 'No data available for the current selection', MODAL_SURVEY_TEXT_DOMAIN ) . '</div>' );
	}
	else {
		if ( ! isset( $_REQUEST[ 'mode' ] ) ) {
			$mode = 'rating';
		}
		else {
			$mode = sanitize_text_field( $_REQUEST[ 'mode' ] );
		}
		if ( ! isset( $_REQUEST[ 'charttype' ] ) ) {
			$charttype = 'radarchart';
		}
		else {
			$charttype = sanitize_text_field( $_REQUEST[ 'charttype' ] );
		}
		echo "<form action='" . esc_url( admin_url( 'admin.php?page=modal_survey_participants&msuid=' . $vid[ 0 ] . '-' . $vid[ 1 ] . '' ) ) . "' method='post'><select name='mode'><option " . ( $mode == 'rating' ? 'selected' : '' ) . " value='rating'>Rating</option><option " . ( $mode == 'score' ? 'selected' : '' ) . " value='score'>Score</option></select><select name='charttype'><option " . ( $charttype == 'radarchart' ? 'selected' : '' ) . " value='radarchart'>Radar Chart</option><option " . ( $charttype == 'piechart' ? 'selected' : '' ) . " value='piechart'>Pie Chart</option><option " . ( $charttype == 'polarchart' ? 'selected' : '' ) . " value='polarchart'>Polar Chart</option><option " . ( $charttype == 'barchart' ? 'selected' : '' ) . " value='barchart'>Bar Chart</option><option " . ( $charttype == 'linechart' ? 'selected' : '' ) . " value='linechart'>Line Chart</option><option " . ( $charttype == 'doughnutchart' ? 'selected' : '' ) . " value='doughnutchart'>Doughnut Chart</option></select><input type='submit' class='button button-secondary button-default' value='" . esc_html__( 'SET', MODAL_SURVEY_TEXT_DOMAIN ) . "'></form>";
		echo "</div>";
		if ( ! isset( $_REQUEST[ 'mode' ] ) ) {
			$mode = 'rating';
		}
		else {
			$mode = sanitize_text_field( $_REQUEST[ 'mode' ] );
		}
		if ( ! isset( $_REQUEST[ 'charttype' ] ) ) {
			$charttype = 'radarchart';
		}
		else {
			$charttype = sanitize_text_field( $_REQUEST[ 'charttype' ] );
		}
		if ( $mode == "rating" ) {
			$chart1 = esc_html__( 'Personal Rating Chart', MODAL_SURVEY_TEXT_DOMAIN );
			$chart2 = esc_html__( 'Global Rating Chart', MODAL_SURVEY_TEXT_DOMAIN );		
		}
		if ( $mode == "score" ) {
			$chart1 = esc_html__( 'Personal Score Chart', MODAL_SURVEY_TEXT_DOMAIN );
			$chart2 = esc_html__( 'Global Score Chart', MODAL_SURVEY_TEXT_DOMAIN );
		}
		echo "<div class='chart-personal'>";
		echo "<p>" . $chart1 . "</p>";
		echo modal_survey::survey_answers_shortcodes( 
					array ( 'id' => $vid[ 1 ], 'data' => $mode, 'style' => $charttype, 'limited' => 'no', 'uid' => $ms_user->autoid, 'session' => 'last' )
				);
		echo "</div>";
		echo "<div class='chart-global'>";
		echo "<p>" . $chart2 . "</p>";
		echo modal_survey::survey_answers_shortcodes( 
					array ( 'id' => $vid[ 1 ], 'data' => $mode, 'style' => $charttype, 'limited' => 'no', 'uid' => "false" )
				);
		echo "</div>";
		echo "<div class='text-results'>";
		$s_sql_ss = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT samesession, time FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_participants msp on mspd.uid = msp.autoid WHERE mspd.sid = %s AND msp.id = %s GROUP BY samesession ORDER BY time DESC", $vid[ 1 ], $ms_user->id ) );
		print('
		<div id="ms-participants-tabs">
		<p>' . esc_html__( 'Personal Answers', MODAL_SURVEY_TEXT_DOMAIN ) . '</p>
		  <ul>' );
		$max = count( $s_sql_ss );
		  foreach( $s_sql_ss as $key => $sessions ) {
			print( '<li><a href="#ms-participants-tabs-' . $key . '">' . ( $max - $key ) . '</a></li>' );
		  }
			print( '<li><a href="#ms-participants-tabs-' . ( $key + 1 ) . '">' . esc_html__( 'Summary', MODAL_SURVEY_TEXT_DOMAIN ) . '</a></li>' );
		print( '</ul>' );
		  foreach( $s_sql_ss as $key => $sessions ) {
			if ( ! empty( $wp_timezone ) ) {
				$newdate = new DateTime( $sessions->time, new DateTimeZone( 'UTC' ) );
				$newdate->setTimezone(new DateTimeZone( $wp_timezone ));
				$created_formatted = $newdate->format( 'Y-m-d H:i' );
			}
			  print( '<div id="ms-participants-tabs-' . $key . '"><span class="completion_time"><strong>' . esc_html__( 'Date', MODAL_SURVEY_TEXT_DOMAIN ) . ':</strong> ' . $created_formatted . '</span>' );
				$thisusurl = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT postid FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details WHERE sid = %s AND samesession = %s", $vid[ 1 ], $sessions->samesession ) );
				print( "Survey URL: <a target='_blank' href='" . get_permalink( $thisusurl ) . "'>" . get_the_title( $thisusurl ) . "</a>" );
					echo modal_survey::survey_answers_shortcodes( 
					array ( 'id' => $vid[ 1 ], 'data' => 'full', 'style' => 'plain', 'limited' => 'no', 'uid' => $ms_user->id, 'title' => '<span>', 'score' => 'true', 'session' => $sessions->samesession, 'correct' => 'true' )
					);
			echo '
			<form method="post" class="delete_samesession' . $sessions->samesession . '"><input type="hidden" name="delete_samesession" value="' . $sessions->samesession . '"></form>
			<a class="delete_samesession_link button button-primary" data-session="' . $sessions->samesession . '">' . esc_html__( 'DELETE', MODAL_SURVEY_TEXT_DOMAIN ) . '</a>
			<div class="click-nav export-personal">
			  <ul class="no-js">
					<li><div class="button button-secondary">' . esc_html__( 'Export Survey', MODAL_SURVEY_TEXT_DOMAIN ) . '</div>
						<ul>
							<li><a class="exportlink_personal" data-session="' . $sessions->samesession . '" href="csv">CSV</a></li>
							<li><a class="exportlink_personal" data-session="' . $sessions->samesession . '" href="json">JSON</a></li>
							<li><a class="pdfexportlink_personal" data-session="' . $sessions->samesession . '" href="pdf">PDF</a></li>
							<li><a class="exportlink_personal" data-session="' . $sessions->samesession . '" href="xml">XML</a></li>
							<li><a class="exportlink_personal" data-session="' . $sessions->samesession . '" href="xls">XLS</a></li>
							<li><a class="exportlink_personal" data-session="' . $sessions->samesession . '" href="txt">TXT</a></li>
						</ul>
					</li>
				</ul>
			</div>';
			print( '</div>');
		  }
			print( '<div id="ms-participants-tabs-' . ( $key + 1 ) . '">' );
				echo modal_survey::survey_answers_shortcodes( 
				array ( 'id' => $vid[ 1 ], 'data' => 'full', 'style' => 'plain', 'limited' => 'no', 'uid' => $ms_user->id, 'title' => '<span>', 'score' => 'true', 'session' => "" )
				);
				echo '<div class="click-nav export-personal">
				  <ul class="no-js">
						<li><div class="button button-secondary">' . esc_html__( 'Export Survey', MODAL_SURVEY_TEXT_DOMAIN ) . '</div>
							<ul>
								<li><a class="exportlink_personal" data-session="" href="csv">CSV</a></li>
								<li><a class="exportlink_personal" data-session="" href="json">JSON</a></li>
								<li><a class="pdfexportlink_personal" data-session="" href="pdf">PDF</a></li>
								<li><a class="exportlink_personal" data-session="" href="xml">XML</a></li>
								<li><a class="exportlink_personal" data-session="" href="xls">XLS</a></li>
								<li><a class="exportlink_personal" data-session="" href="txt">TXT</a></li>
							</ul>
						</li>
					</ul>
				</div>';
			print( '</div>');
		print( '</div>' );
		echo "</div>";
		echo "</div>";
	}
}
else {
	$limit = " LIMIT 0, 10000";
	if ( isset( $_REQUEST[ 'limit' ] ) ) {
		$lmexp = explode( "-", $_REQUEST[ 'limit' ] );
		if ( isset( $lmexp[ 1 ] ) ) {
			if ( $lmexp[ 0 ] >= 0 && $lmexp[ 1 ] > 0 ) {
				$limit = " LIMIT %d, %d";
				$startlimit = $lmexp[ 0 ];
				$endlimit = $lmexp[ 1 ];
			}
		}
		if ( $_REQUEST[ 'limit' ] == "none" ) {
			$limit = "";
		}
	}
	else {
		$lmexp = explode( ",", $limit );
	}
$filter = "";
if ( isset( $_REQUEST[ 'filter' ] ) && $_REQUEST[ 'filter' ] == "on" ) {
	echo '<div class="updated"><p>'.esc_html__( 'Results filtered to the selected answer only.', MODAL_SURVEY_TEXT_DOMAIN ).'</p></div>';
	$preparray = array( $_REQUEST[ 'sid' ], $_REQUEST[ 'qid' ], $_REQUEST[ 'aid' ] );
	if ( ! empty( $limit ) ) {
		$preparray = array( $_REQUEST[ 'sid' ], $_REQUEST[ 'qid' ], $_REQUEST[ 'aid' ], $startlimit, $endlimit );
	}
	$surveys = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT msp.autoid, msp.id as uid, max( DATE_FORMAT( mspd.time,'%Y-%m-%d %H:%i' ) ) as created, msp.name, msp.email, mspd.ip, COUNT( mspd.aid ) as SUMCOUNT, mss.name as survey, mss.id as sid, mspd.sid as pdsid FROM " . $this->wpdb->base_prefix . "modal_survey_participants msp LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_participants_details mspd on mspd.uid = msp.autoid LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_surveys mss on mspd.sid = mss.id WHERE mspd.sid = %d AND mspd.qid = %d AND mspd.aid = %d GROUP BY mss.id, msp.id ORDER BY created DESC" . $limit, $implode( $preparray ) ) );
}
else {
	if ( ! empty( $limit ) ) {
		$preparray = array( $_REQUEST[ 'sid' ], $_REQUEST[ 'qid' ], $_REQUEST[ 'aid' ], $startlimit, $endlimit );
		$surveys = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT msp.autoid, msp.id as uid, max( DATE_FORMAT( mspd.time,'%Y-%m-%d %H:%i' ) ) as created, msp.name, msp.email, mspd.ip, COUNT( mspd.aid ) as SUMCOUNT, mss.name as survey, mss.id as sid, mspd.sid as pdsid FROM " . $this->wpdb->base_prefix . "modal_survey_participants msp LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_participants_details mspd on mspd.uid = msp.autoid LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_surveys mss on mspd.sid = mss.id GROUP BY mss.id, msp.id ORDER BY created DESC" . $limit, $startlimit, $endlimit ) );
	}
	else {
		$surveys = $this->wpdb->get_results( "SELECT msp.autoid, msp.id as uid, max( DATE_FORMAT( mspd.time,'%Y-%m-%d %H:%i' ) ) as created, msp.name, msp.email, mspd.ip, COUNT( mspd.aid ) as SUMCOUNT, mss.name as survey, mss.id as sid, mspd.sid as pdsid FROM " . $this->wpdb->base_prefix . "modal_survey_participants msp LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_participants_details mspd on mspd.uid = msp.autoid LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_surveys mss on mspd.sid = mss.id GROUP BY mss.id, msp.id ORDER BY created DESC" );
	}
}
	if ( isset( $lmexp[ 1 ] ) && ( $lmexp[ 1 ] > 0 ) && ( $this->wpdb->num_rows >= $lmexp[ 1 ] ) ) {
		print( '<p>' . esc_html__( 'The list is limited to ', MODAL_SURVEY_TEXT_DOMAIN ) . $lmexp[ 1 ] . ' ' . esc_html__( 'entries', MODAL_SURVEY_TEXT_DOMAIN ) . '. ' . esc_html__( 'If you would like to see the full list, ', MODAL_SURVEY_TEXT_DOMAIN ) . '<a href="' . esc_url( admin_url( "admin.php?page=modal_survey_participants&limit=none" ) ) . '">' . esc_html__( 'please click here', MODAL_SURVEY_TEXT_DOMAIN ) . '</a></p>' );
	}
	else {
		
	}
	print('<table class="modal-survey-list-table modal-survey-list-table-participants">
		<thead>
			<tr>
				<th><input type="checkbox" name="participants_all" id="participants-select-all" value="0"></th>
				<th>' . esc_html__( 'ID', MODAL_SURVEY_TEXT_DOMAIN ) . '</th>
				<th>' . esc_html__( 'Name', MODAL_SURVEY_TEXT_DOMAIN ) . '</th>
				<th>' . esc_html__( 'E-mail', MODAL_SURVEY_TEXT_DOMAIN ) . '</th>
				<th>' . esc_html__( 'Survey', MODAL_SURVEY_TEXT_DOMAIN ) . '</th>
				<th>' . esc_html__( 'Votes', MODAL_SURVEY_TEXT_DOMAIN ) . '</th>
				<th>' . esc_html__( 'IP Address', MODAL_SURVEY_TEXT_DOMAIN ) . '</th>
				<th>' . esc_html__( 'Date', MODAL_SURVEY_TEXT_DOMAIN ) . '</th>
			</tr>
		</thead><tbody>');
	foreach( $surveys as $sv ) {
		$s_sql_ss = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT samesession FROM " . $this->wpdb->base_prefix . "modal_survey_participants_details mspd LEFT JOIN " . $this->wpdb->base_prefix . "modal_survey_participants msp on mspd.uid = msp.autoid WHERE mspd.sid = %s AND msp.id = %s GROUP BY samesession", $sv->sid, $sv->uid ) );
		$sessarray = json_decode( json_encode( $s_sql_ss ), true );
		$sasarray = array();
		foreach( $sessarray as $sa ) {
			$sasarray[] = $sa[ 'samesession' ];
		}
		$thusessions = implode( ',', $sasarray );
		if ( $sv->survey == "" && $sv->SUMCOUNT == 0 && $sv->created == "" ) {
			$this->wpdb->query( $this->wpdb->prepare( "DELETE FROM " . $this->wpdb->base_prefix . "modal_survey_participants WHERE `autoid` = %d", $sv->autoid ) );
		}
		else {
			if ( ! empty( $wp_timezone ) ) {
				$newdate = new DateTime( $sv->created, new DateTimeZone( 'UTC' ) );
				$newdate->setTimezone(new DateTimeZone( $wp_timezone ));
				$sv->created = $newdate->format( 'Y-m-d H:i' );
			}
			print('<tr id="' . $sv->autoid . '">
			<td><input type="checkbox" data-sessions="' . $thusessions . '" data-uid="' . $sv->autoid . '" name="participants[ ' . $sv->autoid . ' ]" class="participants-select" id="participants-' . $sv->autoid . '-' . $sv->pdsid . '" value="0"></td>
			<td><a href="' . esc_url( admin_url( 'admin.php?page=modal_survey_participants&msuid=' . $sv->autoid . '-' . $sv->sid . '' ) ) . '">' . $sv->autoid . '</a></td>
			<td><a href="' . esc_url( admin_url( 'admin.php?page=modal_survey_participants&msuid=' . $sv->autoid . '-' . $sv->sid . '' ) ) . '">' . ( $sv->name == '' ? 'Anonymous' : $sv->name ) . '</a></td>
			<td>' . ( $sv->email == '' ? esc_html__( 'not specified', MODAL_SURVEY_TEXT_DOMAIN ) : $sv->email ) . '</td>
			<td>' . $sv->survey . '</td>
			<td>' . $sv->SUMCOUNT . '</td>
			<td>' . $sv->ip . '</td>
			<td>' . $sv->created . '</td>
			</tr>');
		}
	}
	print('</tbody></table>');
	print('<input type="button" id="delete_allp" class="button button-secondary button-small" value="' . esc_html__( 'DELETE SELECTED', MODAL_SURVEY_TEXT_DOMAIN ) . '"><input type="button" id="bulk_export" class="button button-secondary button-small" value="' . esc_html__( 'BULK EXPORT SELECTED', MODAL_SURVEY_TEXT_DOMAIN ) . '"><form method="post" id="incomplete-deletion"><input type="hidden" name="delete_incomplete" value="1"><input type="button" id="delete_incomplete" class="button button-secondary button-small" value="' . esc_html__( 'DELETE INCOMPLETE RESULTS', MODAL_SURVEY_TEXT_DOMAIN ) . '"></form>');
	print( '<br><br><br><div class="lineprocess export-progress"><p><strong>Exporting Files</strong> <span class="process_text"></span></p> <input type="hidden" value="0" class="hiddenperc"><div class="lineprogress progress-info progress-striped"><div class="bar survey_global_percent"></div><div class="perc" id="survey_perc">0%</div></div></div>' );
}
?>
</div>
<div id="dialog-confirm5" title="<?php esc_html_e( 'Delete Participants Datas?', MODAL_SURVEY_TEXT_DOMAIN ); ?>">
  <p><span class="ui-icon ui-icon-alert"></span><?php esc_html_e( 'The selected participants datas will be permanently deleted! Are you sure?', MODAL_SURVEY_TEXT_DOMAIN );?></p>
  <p><label><input type="checkbox" name="removefromresults" class="removefromresults"><?php esc_html_e( 'Remove votes from cumulative results', MODAL_SURVEY_TEXT_DOMAIN ); ?></label></p>
</div>
<div id="dialog-confirm6" title="<?php esc_html_e( 'Export Charts', MODAL_SURVEY_TEXT_DOMAIN );?>?">
  <p><span class="ui-icon ui-icon-alert"></span><?php esc_html_e( 'Would you like to export charts to the PDF?', MODAL_SURVEY_TEXT_DOMAIN );?></p>
</div>
<div id="dialog-confirm7" title="<?php esc_html_e( 'Delete the current result?', MODAL_SURVEY_TEXT_DOMAIN );?>">
  <p><span class="ui-icon ui-icon-alert"></span><?php esc_html_e( 'Would you like to delete this participation of the current user?', MODAL_SURVEY_TEXT_DOMAIN );?></p>
</div>
<div id="dialog-confirm8" title="<?php esc_html_e( 'Export Detailed Participant Results?', MODAL_SURVEY_TEXT_DOMAIN ); ?>">
  <p><span class="ui-icon ui-icon-alert"></span><?php esc_html_e( 'The selected participants results will be exported, then you can download it as a ZIP package! This process needs more disk space than the usual, that depends on the amount of export files. Please make sure, you have enough space. Please select the export format below to start the process!', MODAL_SURVEY_TEXT_DOMAIN );?></p>
</div>
<div id="dialog-confirm9" title="<?php esc_html_e( 'Delete Incomplete Results?', MODAL_SURVEY_TEXT_DOMAIN ); ?>">
  <p><span class="ui-icon ui-icon-alert"></span><?php esc_html_e( 'All results that has not contains email address with enabled participants form in the current survey or the last question has not been answered count as incomplete result. The incompleted results will be permanently deleted! Are you sure?', MODAL_SURVEY_TEXT_DOMAIN );?></p>
</div>
<div id="dialog-message">
	<p class="icon"><img src="<?php print( plugins_url( '/assets/img/info-icon.png', __FILE__ ) ); ?>"></p>
	<p class="content"></p>
</div>