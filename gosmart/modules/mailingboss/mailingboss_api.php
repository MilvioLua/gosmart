<?php
$contacts = array('email' => $email);
	$contact_datas = $contacts;
	if (isset($mv)) {
		if (is_array($mv)) 
		{
			$contact_datas = array_merge($contacts, $mv);
		}
	}
	$data = array_merge( array(
		'email' => $email,
		'list_uid' => $sopts[ 166 ],
		'taginternals' => 'added by Modal Survey',
		'status' => 'confirmed' ),
		$contact_datas );
	$req = http_build_query($data);
	$curl = curl_init( 'https://member.mailingboss.com/integration/index.php/lists/subscribers/create/' . $sopts[ 165 ] );
	curl_setopt( $curl, CURLOPT_POST, 1 );
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $req );
	curl_setopt( $curl, CURLOPT_TIMEOUT, 20 );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_FORBID_REUSE, 1 );
	curl_setopt( $curl, CURLOPT_FRESH_CONNECT, 1 );
	curl_setopt( $curl, CURLOPT_HEADER, 0 );
						
	$response = curl_exec( $curl );
	curl_close( $curl );
	$resp = unserialize( $response );
	if ( isset( $resp[ 'error' ] ) ) {
		die( $resp[ 'error' ] );
	}
	else {
		$result = true;
	}
?>