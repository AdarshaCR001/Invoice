<?php
require_once realpath(__DIR__ . '/vendor/autoload.php');

#Load Environments
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
?>