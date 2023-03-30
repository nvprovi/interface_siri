<?php

function siri_api_request($params, $isStatusRequest=false){  
  global $SIRI_REQUEST_URL;      
  global $SIRI_REQUEST_STATUS_URL;
  $requestURL = $isStatusRequest ? $SIRI_REQUEST_STATUS_URL : $SIRI_REQUEST_URL;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $requestURL);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params['body']);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml')); 
  $server_output = curl_exec($ch);  
  curl_close ($ch);         
  return $server_output;
}

function send_check_status_request($returnServiceStartTime = false){
  global $SIRI_SUB_REF;
  $requestTime = new DateTime('now');
  $requestTime->setTimezone(new DateTimeZone('Europe/Berlin'));
  $messageID = uniqid();
  $body =
  "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
  <Siri version='2.0' xmlns='http://www.siri.org.uk/siri' xmlns:ns2='http://www.ifopt.org.uk/acsb' xmlns:ns3='http://www.ifopt.org.uk/ifopt' xmlns:ns4='http://datex2.eu/schema/2_0RC1/2_0'>
    <CheckStatusRequest>>
      <RequestTimestamp>".$requestTime -> format('c')."</RequestTimestamp>
      <RequestorRef>".$SIRI_SUB_REF."</RequestorRef>
    </CheckStatusRequest>>
  </Siri>";
  $params = array('body' => $body, 'type' => 'data_supply');
  $server_response = siri_api_request($params, true);
  $xmlData = get_xml($server_response);
  $status = isset($xmlData -> CheckStatusResponse -> Status) ? (string) $xmlData -> CheckStatusResponse -> Status : 'false';
  $responseTimestamp = isset($xmlData -> CheckStatusResponse -> ResponseTimestamp) ? $xmlData -> CheckStatusResponse -> ResponseTimestamp : '-';
  $serviceStartedTime = isset($xmlData -> CheckStatusResponse -> ServiceStartedTime) ? $xmlData -> CheckStatusResponse -> ServiceStartedTime : '-';
  $serviceStartedTimeUnix = ($serviceStartedTime != '-') ? strtotime($serviceStartedTime) : 0;  
  $serviceStartedTimeUnix = $serviceStartedTimeUnix - ($serviceStartedTimeUnix % 3600); // abrunden auf volle Stunde
  $dynamoDBdata = array(
    'status_response' => $status,
    'response_timestamp' => $responseTimestamp,
    'service_started_time' => $serviceStartedTime,
    'service_started_time_unix' => $serviceStartedTimeUnix,
    'request_xml' => $body,
    'server_response' => $server_response
  );
  post_to_dyanmoDB("status_request", $dynamoDBdata); 
  updateCurrentServiceStartTime($serviceStartedTimeUnix);
  if($returnServiceStartTime){return $timestamp;}
  return $server_response;
}

function send_data_supply_request(){      
  $requestTime = new DateTime('now');    
  $requestTime->setTimezone(new DateTimeZone('Europe/Berlin'));  
  $messageID = uniqid();
  $body = 
  "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
  <Siri version='2.0' xmlns='http://www.siri.org.uk/siri' xmlns:ns2='http://www.ifopt.org.uk/acsb' xmlns:ns3='http://www.ifopt.org.uk/ifopt' xmlns:ns4='http://datex2.eu/schema/2_0RC1/2_0'>
    <DataSupplyRequest>
      <RequestTimestamp>".$requestTime -> format('c')."</RequestTimestamp>
      <AllData>false</AllData>
    </DataSupplyRequest>
  </Siri>";
  $params = array('body' => $body, 'type' => 'data_supply');          
  return siri_api_request($params);
}

function subscribe(){
  global $SIRI_SUBREQUEST_ID;
  global $SIRI_SUB_REF;
  global $SIRI_RETURN_URL;
  global $currentMicroTime;
  $requestTime = new DateTime('now');
  $requestTime->setTimezone(new DateTimeZone('Europe/Berlin'));
  $terminateTime = new DateTime('+7 days');
  $terminateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
  $messageID = uniqid();
  $body = 
  "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
  <Siri version='2.0' xmlns='http://www.siri.org.uk/siri' xmlns:ns2='http://www.ifopt.org.uk/acsb' xmlns:ns3='http://www.ifopt.org.uk/ifopt' xmlns:ns4='http://datex2.eu/schema/2_0RC1/2_0'>
      <SubscriptionRequest>
          <RequestTimestamp>".$requestTime -> format('c')."</RequestTimestamp>
          <RequestorRef>".$SIRI_SUB_REF."</RequestorRef>
          <MessageIdentifier>".$messageID."</MessageIdentifier>
          <ConsumerAddress>".$SIRI_RETURN_URL."</ConsumerAddress>
          <SubscriptionContext>
              <HeartbeatInterval>PT30S</HeartbeatInterval>
          </SubscriptionContext>
          <VehicleMonitoringSubscriptionRequest>
              <SubscriberRef>".$SIRI_SUB_REF."</SubscriberRef>
              <SubscriptionIdentifier>".$SIRI_SUBREQUEST_ID."</SubscriptionIdentifier>
              <InitialTerminationTime>".$terminateTime -> format('c')."</InitialTerminationTime>
              <VehicleMonitoringRequest version='2.0'>
                  <RequestTimestamp>".$requestTime -> format('c')."</RequestTimestamp>
              </VehicleMonitoringRequest>
          </VehicleMonitoringSubscriptionRequest>
      </SubscriptionRequest>
  </Siri>";
  $params = array('body' => $body, 'type' => 'subscribe');  
  $server_response = siri_api_request($params);      
    
  $dynamoDBdata = array(
    'subscription_id' => $SIRI_SUBREQUEST_ID,
    'local_time' => $currentMicroTime -> format("Y-m-d H:i:s.u"),
    'request_action' => 'subscribe',
    'request_xml' => $body,
    'server_response' => $server_response
  );
  updateCurrentSubscriptionVal($SIRI_SUBREQUEST_ID);
  post_to_dyanmoDB("subscription", $dynamoDBdata);    
  return $server_response;
}

function terminate(){
  global $SIRI_SUBREQUEST_ID;
  global $SIRI_SUB_REF;
  global $currentMicroTime;
  $requestTime = new DateTime('now');    
  $requestTime->setTimezone(new DateTimeZone('Europe/Berlin'));  
  $messageID = uniqid();
  $body = 
  "<?xml version='1.0' encoding='UTF-8'?>
  <Siri version='1.4' xmlns='http://www.siri.org.uk/siri' xmlns:ns2='http://www.ifopt.org.uk/acsb' xmlns:ns3='http://www.ifopt.org.uk/ifopt' xmlns:ns4='http://datex2.eu/schema/2_0RC1/2_0'>
      <TerminateSubscriptionRequest>
          <RequestTimestamp>".$requestTime -> format('c')."</RequestTimestamp>
          <RequestorRef>".$SIRI_SUB_REF."</RequestorRef>
          <MessageIdentifier>".$messageID."</MessageIdentifier>
          <SubscriptionRef>".$SIRI_SUBREQUEST_ID."</SubscriptionRef>
      </TerminateSubscriptionRequest>
  </Siri>";
  $params = array('body' => $body, 'type' => 'terminate');    
  $server_response = siri_api_request($params);   
  $dynamoDBdata = array(
    'subscription_id' => $SIRI_SUBREQUEST_ID,
    'local_time' => $currentMicroTime -> format("Y-m-d H:i:s.u"),
    'request_action' => 'terminate',
    'request_xml' => $body,
    'server_response' => $server_response
  );
  post_to_dyanmoDB("subscription", $dynamoDBdata);      
  return $server_response;
}

function send_alive(){
  return "i am alive";
}

function print_data_ready_acknowledge(){  
  global $SIRI_SUB_REF;
  $requestTime = new DateTime('now');  
  $requestTime->setTimezone(new DateTimeZone('Europe/Berlin'));
  $body = 
  "<DataReadyAcknowledgement>
    <ResponseTimestamp>".$requestTime -> format('c')."</ResponseTimestamp>
      <ConsumerRef>".$SIRI_SUB_REF."</ConsumerRef>
    <Status>true</Status>
  </DataReadyAcknowledgement>";
  return $body;
}

function post_to_kinesis($content){   
  global $kinesisClient;  
  global $currentMicroTime;
  $datastream = "nvprovi_siri_datastream";        
  try {    
    $result = $kinesisClient->PutRecord([
      'Data' => $content -> asXML(),
      'StreamName' => $datastream,
      'PartitionKey' => '1'
    ]);    
    $dbData = array(
      'response_timestamp' => isset($content -> ServiceDelivery -> ResponseTimestamp) ? json_encode($content -> ServiceDelivery -> ResponseTimestamp) : '-',
      'local_timestamp' => $currentMicroTime->format("Y-m-d H:i:s.u"),
      'request_message_ref' => isset($content -> ServiceDelivery -> RequestMessageRef) ? json_encode($content -> ServiceDelivery -> RequestMessageRef) : '-',
      'response_message_identifier' => isset($content -> ServiceDelivery -> ResponseMessageIdentifier) ? json_encode($content -> ServiceDelivery -> ResponseMessageIdentifier) : '-',      
      'subscriber_ref' => isset($content -> ServiceDelivery -> VehicleMonitoringDelivery -> SubscriberRef) ? json_encode($content -> ServiceDelivery -> VehicleMonitoringDelivery -> SubscriberRef) : '-',      
      'subscription_ref' => isset($content -> ServiceDelivery -> VehicleMonitoringDelivery -> SubscriptionRef) ? json_encode($content -> ServiceDelivery -> VehicleMonitoringDelivery -> SubscriptionRef) : '-',      
      'kinesis_shard'=> $result["ShardId"]
    );    
    post_to_dyanmoDB("kinesis_post", $dbData);
    return $result;
  } 
  catch (AwsException $e) {
    // echo $e->getMessage();       
    return false;
  }
}

function get_xml($requestBody){  
  libxml_use_internal_errors(true);
  $data = simplexml_load_string($requestBody);
  if ($data === false) {return false;}
  return new SimpleXMLElement($requestBody);  
}

function getRndHash($len) {
  $characters = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
  $string = "";
  for($p = 0; $p < $len ; $p++) {
      $string = $string.$characters[mt_rand(0, strlen($characters)-1)];
  }
  return strtolower($string);
}

function post_to_dyanmoDB($type, $data){
  global $dynamoDbClient;
  $table = false;
  if($type == 'subscription'){$table = "nvprovi_subscription_requests";}
  if($type == 'kinesis_post'){$table = "nvprovi_kinesis_posts";}  
  if($type == 'status_request'){$table = "nvprovi_status_requests";}    
  if($type == 'heartbeat'){$table = "nvprovi_heartbeats";}    
  if($type == 'cronjob'){$table = "nvprovi_cronjobs";}    
  
  
  if(!$table){return false;}

  $putData = array(
    'id' => array('S' => getRndHash(10).".".uniqid()),  
  );
  foreach($data as $index => $value){
    $putData[$index] = array('S' => (string) $value);
  }
  $putRecord = array(
    'TableName' => $table,
    'Item' => $putData,
  );
  return $dynamoDbClient->putItem($putRecord);
}

function updateLastHeartbeatReceived($time){
  global $sqLite;
  $sqLite->exec("UPDATE localvals SET value = '".$time."' WHERE id='lastHeartBeatReceived'");
}

function updateCurrentServiceStartTime($value){
  global $sqLite;
  $sqLite->exec("UPDATE localvals SET value = '".$value."' WHERE id='currentServiceStartTime'");
}

function updateCurrentSubscriptionVal($value){
  global $sqLite;
  $sqLite->exec("UPDATE localvals SET value = '".$value."' WHERE id='currentSubscriptionID'");
}

function getLastHeartbeat(){
  global $sqLite;
  $result = $sqLite->query("SELECT value FROM localvals WHERE id='lastHeartBeatReceived'");
  $row = $result->fetchArray();
  return $row['value'];
}