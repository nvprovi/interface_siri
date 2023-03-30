<?php
$resourceFolder = "../resources/";
require($resourceFolder.'config.php');
echo"checking status\n";
echo send_check_status_request();