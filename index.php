<?php
    require_once __DIR__ . "/utils/init.php";
    $dbconnection = DBConnection::get_connection();

    require_once __DIR__ . "/template/header.php";

    $page = $_GET['page'] ?? 'home';
    if(!Auth::is_allowed_page($page)) {
        $page = 'login';
    }
    ?>
    <main class="container" id="container">
    <?php
    $path = __DIR__ . "/pages/$page.php";
    if(!file_exists($path)) {
        print_alert("Page not found");
        goto footer;
    }
    
    require_once $path;

    ?>
    </main> 
    <?php

    footer: 
    require_once __DIR__ . "/template/footer.php";

?>