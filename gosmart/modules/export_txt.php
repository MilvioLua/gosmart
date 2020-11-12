<?php
if ( $_REQUEST[ 'sspcmd' ] == "aexport" ) {
	$path = str_replace( array( "\\", "/" ), array( MSDIRS, MSDIRS ), dirname( __FILE__ ) ) . MSDIRS . ".." . MSDIRS . "exports" . MSDIRS . $sid . "_" . $auid . ".txt";
	$output = '
' . $question . '

';
	foreach( $answers_text as $at ) {
$output .= '
' . $at->answertext . ' ' . $at->count;
	}
	if ( file_put_contents( $path, $output ) ) {
		$result = "success";
	}
	else {
		$result = esc_html__( 'Write error', MODAL_SURVEY_TEXT_DOMAIN );
	}	
}
else {
	$path = str_replace( array( "\\", "/" ), array( MSDIRS, MSDIRS ), dirname( __FILE__ ) ) . MSDIRS . "..". MSDIRS . "exports" . MSDIRS . $survey_exp[ 'id' ] . ".txt";
	$output = '
' . esc_html__( 'Survey ID:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $survey_exp['id'] . '
' . esc_html__( 'Survey Name:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $survey_exp['name'] . '
' . esc_html__( 'Export Time:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $survey_exp['export_time'];
if ( $personal ) {
$output .= '

' . esc_html__( 'User ID:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $survey_exp[ 'user_details' ]->autoid . '
' . esc_html__( 'Name:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . ( $survey_exp[ 'user_details' ]->name ? $survey_exp[ 'user_details' ]->name : esc_html__( 'Anonymous', MODAL_SURVEY_TEXT_DOMAIN ) ) . '
' . esc_html__( 'Username:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . ( $survey_exp[ 'user_details' ]->username ? $survey_exp[ 'user_details' ]->username : esc_html__( 'Not Specified', MODAL_SURVEY_TEXT_DOMAIN ) ) . '
' . esc_html__( 'Email:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $survey_exp[ 'user_details' ]->email;
if ( ! empty( $survey_exp[ 'user_details' ]->custom ) ) {
	foreach ( unserialize( $survey_exp[ 'user_details' ]->custom ) as $muc_index=>$muc ) {
$output .= '
' . ucfirst( strtolower( $muc_index ) ) . ': ' . $muc;
	}
}
$output .= '
' . esc_html__( 'Date:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $survey_exp[ 'user_details' ]->created . '
' . esc_html__( 'Total Score:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $survey_exp[ 'user_details' ]->allscore . '
';
if ( $survey_exp[ 'user_details' ]->alltimer > 0 ) {
$output .= esc_html__( 'Required Time:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $survey_exp[ 'user_details' ]->alltimer . esc_html__( 'sec', MODAL_SURVEY_TEXT_DOMAIN ) . '
';
}
$output .= esc_html__( 'Participant answers marked with stars: *', MODAL_SURVEY_TEXT_DOMAIN );
}
	foreach( $survey_exp[ 'questions' ] as $qkey=>$questions ) {
	$output .= '


' . $questions['name'].'

';
			foreach ( $questions as $key=>$answer ) {
				if ( is_numeric( $key ) ) {
				$thaoptions = unserialize( $answer[ "aoptions" ] );
				$marker = "";
				$admin_comment = "";
				if ( $personal ) {
					if ( in_array( $key, $user_votes[ $qkey ] ) ) {
						$marker = "* ";
						$admin_comment = '
' . $thaoptions[ 16 ];
					}
					if ( $thaoptions[ 0 ] == "open" || $thaoptions[ 0 ] == "date" || $thaoptions[ 0 ] == "select" || $thaoptions[ 0 ] == "numeric" ) {
						$opan = explode( "|", $user_votes[ $qkey ][ $key ] );
						if ( isset( $opan[ 1 ] ) ) {
							$answer[ 'answer' ] = esc_html__( 'Other', MODAL_SURVEY_TEXT_DOMAIN ) . ": " . $opan[ 1 ];
						}
					}					
				}
	$output .= '' . $marker . esc_html__( 'Answer:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $answer[ 'answer' ] . '
' . esc_html__( 'Count:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $answer[ 'count' ] . '
' . esc_html__( 'Percentage:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $answer['percentage'] . '% ' . $admin_comment . '

';
if ( $thaoptions[ 0 ] == "open" || $thaoptions[ 0 ] == "date" || $thaoptions[ 0 ] == "select" || $thaoptions[ 0 ] == "numeric" ) {
	$answers_text = $wpdb->get_results( $wpdb->prepare( "SELECT answertext, count FROM " . $this->wpdb->base_prefix . "modal_survey_answers_text WHERE `survey_id` = %d AND `id` = %s ORDER BY count DESC", $survey_exp[ 'id' ], $answer[ 'uniqueid' ] ) );
if ( ! empty( $answers_text ) ) {
$output .= 'Open Text Answers:';
	foreach( $answers_text as $at ) {
$output .= '
' . $at->answertext . ' ' . $at->count . '';
	}
	$output .= '
';
}
}
				}
			}
	$output .= '
' . esc_html__( 'Total Votes:', MODAL_SURVEY_TEXT_DOMAIN ) . ' ' . $questions[ 'count' ];
	}
	if ( file_put_contents( $path, $output ) ) {
		$result = "success";
	}
	else {
		$result = esc_html__( 'Write error', MODAL_SURVEY_TEXT_DOMAIN );
	}
}
?>