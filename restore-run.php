<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$loader = require_once __DIR__ . '/vendor/autoload.php';

include_once('./src/MysqlBackup/BackupManager.php');

include_once('config.php');

$backup = new MysqlBackup\BackupManager( $config );

//$backup->restore();
