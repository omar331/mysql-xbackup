<?php
$loader = require_once __DIR__ . '/vendor/autoload.php';

include_once('./src/MysqlBackup/BackupManager.php');

include_once('config.php');

$backup = new MysqlBackup\BackupManager( $config );


$backup->run();




