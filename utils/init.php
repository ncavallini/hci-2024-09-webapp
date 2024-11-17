<?php
session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();



require_once __DIR__ . '/DBConnection.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/functions.php';
?>

