<?php
require('/var/www/html/resources/config.php');
//require('../resources/config.php');

$data = array(
  'type' => 'check_heartbeat',
  'local_time' => $currentMicroTime -> format("Y-m-d H:i:s.u")
);
post_to_dyanmoDB("cronjob", $data);
$lastHeartbeat = getLastHeartbeat();
if(time() - $lastHeartbeat > $RENEW_SUBSCRIBE_TIMEOUT){
  $data = array(
    'type' => 'heartbeat_timeout_renew_subscription',
    'local_time' => $currentMicroTime -> format("Y-m-d H:i:s.u")
  );
  post_to_dyanmoDB("cronjob", $data);
  subscribe(); 
}