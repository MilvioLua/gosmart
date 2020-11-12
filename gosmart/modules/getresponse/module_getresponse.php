<?php
if ( empty( $sopts[ 55 ] ) ) die("You must specify the API Key.");
if ( empty( $sopts[ 56 ] ) ) die("You must specify the Campaign ID.");

$params = array (
	'campaign'  => $sopts[ 56 ],
	'email'     => $_REQUEST[ 'email' ],
	'cycle_day' => 0
);
if ( isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
	$params[ 'ip' ] = $_SERVER[ 'REMOTE_ADDR' ];
}
if ( isset( $mv ) ) {
	if ( is_array( $mv ) ) {
	$c=0;
		foreach( $mv as $key=>$mvitem ) {
		$c++;
			if ( ! empty( $mvitem ) ) {
				if ( $key != "conf" ) {
					$params[ 'customs' ][] = array( 
						'customFieldId'=>$key, 
						'href'=> 'https://api.getresponse.com/v3/custom-fields/'.$customfld . $c, 
						'fieldType'=> 'text',
						'format'=> 'text',
						'valueType'=> 'string',
						'type'=> 'string',
						'hidden'=> 'false',
						'values'=> array( $mvitem )
					);
				}
			}
		}
	}
	if ( isset( $mv['name'] ) ) {
		$params[ 'name' ] = $mv[ 'name' ];
	}
}
$getfullname = 'Raju Harry'; // Your Name e.g. Raju Harry
$mobilecode = '+xx'; // Your Country code e.g. +91
$mobile = 'xxxxxxxxxx'; // Your 10 digit mobile code e.g. 9999999999
$addcontacturl = 'https://api.getresponse.com/v3/contacts/';
$getcontacturl = 'https://api.getresponse.com/v3/contacts?query[email]=' . $_REQUEST[ 'email' ];
$data = array (
'name' => $params[ 'name' ],
'email' => $_REQUEST[ 'email' ],
'dayOfCycle' => 0,
'campaign' => array('campaignId'=>$sopts[ 56 ]),  // Your Valid Email e.g. ThwHa
'ipAddress'=>  $_SERVER['REMOTE_ADDR'], // set the server address: $_SERVER['REMOTE_ADDR'] 

);  

$data_string = json_encode($data); 

$ch = curl_init($addcontacturl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
    'Content-Type: application/json',
    'X-Auth-Token: api-key ' . $sopts[ 55 ],
)           
);                                                                                                                   
                                                                                                                     
$result = curl_exec( $ch ); // Print this If you want to verfify
$result_array = json_decode( $result ); // Print this If you want to verfify

/*print('result: <pre>');
print_r($result_array);
print('</pre>');*/


$chmmn = curl_init($getcontacturl );
curl_setopt($chmmn, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
curl_setopt($chmmn, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($chmmn, CURLOPT_HTTPHEADER, array(                                                                          
    'Content-Type: application/json',
    'X-Auth-Token: api-key ' . $sopts[ 55 ],
)           
);                                                                                                                   
$resultmn = curl_exec( $chmmn );
$resultmn = array_shift( json_decode( $resultmn, true ) ); // Print this If you want to verfify


$contactId = trim($resultmn['contactId']);
$datamn = array (
'contactId' => $contactId,
'customFieldValues'=> $params[ 'customs' ]
); 

$data_stringmn = json_encode($datamn);                                                                                   
$mnurl = 'https://api.getresponse.com/v3/contacts/'.$contactId.'/custom-fields/';  
$chfeld = curl_init($mnurl);
curl_setopt($chfeld, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
curl_setopt($chfeld, CURLOPT_POSTFIELDS, $data_stringmn);                                                                  
curl_setopt($chfeld, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($chfeld, CURLOPT_HTTPHEADER, array(                                                                          
    'Content-Type: application/json',
    'X-Auth-Token: api-key ' . $sopts[ 55 ],
)           
);                                                                                                                   
                                                                                                                     
$resultcustomfld = curl_exec($chfeld); // Print this If you want to verfify

curl_close($ch);
curl_close($chmmn);
curl_close($chfeld);

if ( $result_array->message == 'Contact already added' ) {
	$result = true;
}
else {
	if ( ! empty( $result_array->message ) ) {
		die( 'GetResponse: Error Creating Contact' );
	}
	else {
		$result = true;
	}
}
?>