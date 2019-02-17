<?php
require_once 'vendor/autoload.php';
require_once 'App.php';

define('APP_ROOT', __DIR__);

$startTime = microtime(true);
App::archive_sites();
App::upload_archives();
$endTime = microtime(true);
$totalTime = $endTime - $startTime;
App::log("Backup Finished: ${totalTime}s");