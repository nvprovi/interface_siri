<?php
require('/var/www/html/resources/config.php');

$server_response = subscribe();
$data = array(
  'type' => 'renew_subscription',
  'local_time' => $currentMicroTime -> format("Y-m-d H:i:s.u"),
  'server_response' => $server_response 
);
post_to_dyanmoDB("cronjob", $data);