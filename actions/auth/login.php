<?php
    require_once __DIR__ . "/../../utils/init.php";

    if(!isset($_POST["username"]) || !isset($_POST["password"])) {
        print_error("Username and password are required", true);
    }

    $username = $_POST["username"];
    $password = $_POST["password"];

    if(Auth::login($username, $password)) {
        header("Location: /index.php?page=home");
    } else {
        print_error("Invalid username or password", true);
    }
?>

