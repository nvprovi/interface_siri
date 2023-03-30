<?php
require('/var/www/html/resources/config.php');
//require('../resources/config.php');

$serviceStartedTimeUnix = send_check_status_request(true);