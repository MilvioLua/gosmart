<?php
if ( class_exists( '\MailPoet\API\API' ) ) {
$mailpoet_lists = \MailPoet\API\API::MP('v1')->getLists();
$output = '<table><tr><th>List ID</th><th>List Name</th></tr>';
	foreach ($mailpoet_lists as $key => $value) {
		$output .= '<tr><td class="getid" data-target="mailpoet_listid" data-value="' . $value[ 'id' ] . '">' . $value[ 'id' ] . '</td><td class="getid" data-target="mailpoet_listid" data-value="' . $value[ 'id' ] . '">' . $value[ 'name' ] . '</td></tr>';
	}
		$output .= '</table>';
die($output);
}
elseif ( class_exists( 'WYSIJA' ) ) {
	$model_list = WYSIJA::get('list','model');
	$mailpoet_lists = $model_list->get( array( 'name', 'list_id' ), array( 'is_enabled' => 1 ) );
	$output = '<table><tr><th>List ID</th><th>List Name</th></tr>';
		foreach ($mailpoet_lists as $key => $value) {
			$output .= '<tr><td class="getid" data-target="mailpoet_listid" data-value="' . $value[ 'list_id' ] . '">' . $value[ 'list_id' ] . '</td><td class="getid" data-target="mailpoet_listid" data-value="' . $value[ 'list_id' ] . '">' . $value[ 'name' ] . '</td></tr>';
		}
			$output .= '</table>';
	die($output); 
}
else {
	die( "MailPoet is not installed. Get it here: <a target='_blank' href='https://wordpress.org/plugins/mailpoet/'>Download MailPoet</a>" );
}
?>