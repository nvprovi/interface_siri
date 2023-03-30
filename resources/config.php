<?php
date_default_timezone_set('Europe/Berlin');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require(__DIR__.'/constants.php');
require(__DIR__.'/functions.php');
require(__DIR__.'/../vendor/autoload.php');

use Aws\Kinesis\KinesisClient; 
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

$dynamoDbClient = new DynamoDbClient($AWS_CONFIG);
$kinesisClient = new KinesisClient($AWS_CONFIG);
$sqLite = new SQLite3(__DIR__.'/localstorage.db');

updateLastHeartbeatReceived(time());
