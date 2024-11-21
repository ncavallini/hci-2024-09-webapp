<?php
    require_once __DIR__ . "/../../utils/init.php";
    Auth::logout();
    header("Location: ../../index.php?page=login");
?>