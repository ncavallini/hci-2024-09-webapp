<?php
    require_once __DIR__ . "/utils/init.php";
    $dbconnection = DBConnection::get_connection();
    if(Auth::is_logged_in()) {
        $user = Auth::user();
    }

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


<script>
    const urlSearchParam = new URLSearchParams(window.location.search);
    if(urlSearchParam.has('message')) {
        const message = urlSearchParam.get('message');
        const style = urlSearchParam.get('message_style').toUpperCase() || "INFO";
        console.log(TOAST_STATUS[style]);
        const toast = {
        title: "Ciao",
        message: message,
        status: TOAST_STATUS.DANGER,
        timeout: 5000
    };
    Toast.create(toast);
    
    }
</script>