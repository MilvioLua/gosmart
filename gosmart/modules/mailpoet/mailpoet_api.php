<?php
$user_data = array( 'email' => $_REQUEST[ 'email' ] );
if ( is_array( $mv ) ) {
	$user_params = array_merge( $user_data, $mv );
}
else {
	$user_params = $user_data;
}
unset( $user_params[ 'name' ] );
unset( $user_params[ 'fullname' ] );
unset( $user_params[ 'fname' ] );
unset( $user_params[ 'lname' ] );

    $data_subscriber = array(
      'user' => $user_params,
      'user_list' => array( 'list_ids' => array( $sopts[ 89 ] ) )
    );
 if ( class_exists( '\MailPoet\API\API' ) ) {
	try {
		if ( $user_params[ 'firstname' ] ) {
			$user_params[ 'first_name' ] = $user_params[ 'firstname' ];
			unset( $user_params[ 'firstname' ] );
		}
		if ( $user_params[ 'lastname' ] ) {
			$user_params[ 'last_name' ] = $user_params[ 'lastname' ];
			unset( $user_params[ 'lastname' ] );
		}
	  $subscriber = \MailPoet\API\API::MP( 'v1' )->addSubscriber( $user_params, array( $sopts[ 89 ] ), array(
		'send_confirmation_email' => false,
		'schedule_welcome_email' => false
	));
	$success = "true";
	} catch( Exception $exception ) {
		if ( $exception->getMessage() == "This subscriber already exists." ) {
			try {
			  $subscriber = \MailPoet\API\API::MP('v1')->subscribeToList( $user_params[ 'email' ], $sopts[ 89 ], array(
				'send_confirmation_email' => false,
				'schedule_welcome_email' => false
			));
			$success = "true";
			} catch(Exception $exception) {
			  die( $exception->getMessage() );
			}
		}
		else {
			die( $exception->getMessage() );
	}	 
 }
 }
 else {
   $helper_user = WYSIJA::get( 'user', 'helper' );
    $success = $helper_user->addSubscriber( $data_subscriber );
 }
if ( $success ) {
	$result = true;
}
else {
	die( 'MailPoet Error: Couldn\'t add user' );
}
?>