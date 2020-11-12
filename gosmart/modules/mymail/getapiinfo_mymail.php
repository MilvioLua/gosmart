<?php
if ( function_exists( 'mailster' ) ) {
	$lists = mailster( 'lists' )->get();
	$output = '<table><tr><th>List ID</th><th>List Name</th></tr>';
	foreach ( $lists as $key => $value ) {
		$output .= '<tr><td class="getid" data-target="mymail_listid" data-value="' . $value->ID . '">' . $value->ID . '</td><td class="getid" data-target="mymail_listid" data-value="' . $value->ID . '">' . $value->name . '</td></tr>';
	}
	$output .= '</table>';
	die( $output );
}
else {
	die("Mailster is not installed. Get it here: <a target='_blank' href='1.envato.market/q9r0g'>Download Mailster</a>");
}
?>