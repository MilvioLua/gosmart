<?php
if ( class_exists( 'WYSIJA' ) || class_exists( '\MailPoet\API\API' ) ) {
   require_once(sprintf("%s/mailpoet_api.php", dirname(__FILE__)));
}
?>