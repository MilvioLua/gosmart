<?php
require_once( sprintf( "%s/klick-tipp-api.php", dirname( __FILE__ ) ) );
$result = false;
$connector = new KlicktippConnector(); 
$logres = $connector->login( $sopts[ 168 ], $sopts[ 169 ] );
$subscriber_id = $connector->subscriber_search( $email );
$mv[ 'fieldFirstName' ] = $mv[ 'fname' ];
$mv[ 'fieldLastName' ] = $mv[ 'lname' ];
if ( ! $subscriber_id ) {
	$res = $connector->signin( $sopts[ 170 ], $email, $mv );
	if ( ! $res ) {
		print( 'error: ' . $connector->get_last_error() );
	}
}
else {
}
$logged_out = $connector->logout();

if ($logged_out) {
	if ( $res ) {
		$result = true;
	}
} else {
  echo esc_url( $connector->get_last_error() );
}
?>