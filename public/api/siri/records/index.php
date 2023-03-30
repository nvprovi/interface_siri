<?php
$resourceFolder = "../../../../resources/";
require($resourceFolder.'config.php');

if(!isset($_SERVER['REQUEST_METHOD'])){echo"no requesttype";exit();}
$requestType = strtoupper($_SERVER['REQUEST_METHOD']);
if($requestType == 'GET'){
  echo"i am alive";
  exit();
}

$postBody = file_get_contents('php://input');
$postBody = str_replace("init-o:", "", $postBody);
$xmlData = get_xml($postBody);
if($xmlData === false){echo"no xml";exit();}

$isHeartbeat = isset($xmlData -> HeartbeatNotification) ? true : false;
$isDataReadyNotification = isset($xmlData -> DataReadyNotification) ? true : false;
$isDataDelivery = isset($xmlData -> ServiceDelivery) ? true : false;

if($isHeartbeat){          
  $dynamoDBdata = array(    
    'response_timestamp' => isset($xmlData -> HeartbeatNotification -> RequestTimestamp) ? $xmlData -> HeartbeatNotification -> RequestTimestamp : '-',
    'local_timestamp' => $currentMicroTime -> format("Y-m-d H:i:s.u"),
    'status' => isset($xmlData -> HeartbeatNotification -> Status) ? (string) $xmlData -> HeartbeatNotification -> Status : 'false',
    'notification_xml' => $xmlData -> asXML()
  );
  updateLastHeartbeatReceived(time());
  post_to_dyanmoDB("heartbeat", $dynamoDBdata);
  exit();
}

if($isDataReadyNotification){          
  echo print_data_ready_acknowledge();
  exit();
}

if($isDataDelivery){  
  post_to_kinesis($xmlData);
  exit();
}

