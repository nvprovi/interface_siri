<?php
$currentMicroTime = DateTime::createFromFormat('U.u', microtime(true));
$currentMicroTime->setTimezone(new DateTimeZone('Europe/Berlin'));

$RENEW_SUBSCRIBE_TIMEOUT = 60*5;

$SIRI_SUB_REF = "livemap";
$SIRI_SUBREQUEST_ID = "";
$SIRI_RETURN_URL = "";
//$SIRI_REQUEST_URL = "http://172.25.1.9:20228/nvprovi/VM/subscribe.xml";
//$SIRI_REQUEST_STATUS_URL = "http://172.25.1.9:20228/nvprovi/VM/checkstatus.xml";

$SIRI_REQUEST_URL = "";
$SIRI_REQUEST_STATUS_URL = "";

$AWS_ACCESS_KEY_ID = "";
$AWS_SECRET_ACCESS_KEY = "";
$AWS_DEFAULT_REGION = "";
$AWS_CONFIG = array(
  'version'     => 'latest',
  'region'      => $AWS_DEFAULT_REGION,
  'credentials' => array(
      'key'    => $AWS_ACCESS_KEY_ID,
      'secret' => $AWS_SECRET_ACCESS_KEY,
  ),
);