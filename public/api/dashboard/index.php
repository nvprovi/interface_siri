<?php
$resourceFolder = "../../../resources/";
require($resourceFolder.'config.php');



$iterator = $dynamoDbClient->getIterator('Scan', array('TableName' => 'nvprovi_kinesis_posts'));


$data = array();
foreach ($iterator as $item) {
  $data[] = strtotime($item['local_timestamp']['S']);  
}
sort($data);
foreach($data as $timestamp){
  echo date("d-m-Y H:i:s", $timestamp)."\n";
}