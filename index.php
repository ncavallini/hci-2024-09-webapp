<?php
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
    require_once __DIR__ . "/utils/init.php";
    $dbconnection = DBConnection::get_connection();
    echo "CIao";
    var_dump($dbconnection);
?>