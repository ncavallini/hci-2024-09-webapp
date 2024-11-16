<?php
    require_once __DIR__ . "/utils/init.php";
    $dbconnection = DBConnection::get_connection();
    echo "Hello World!";
    var_dump($dbconnection);
?>