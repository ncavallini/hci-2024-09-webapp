<?php
    function print_error(string $msg, bool $needs_bootstrap = false) {
        if($needs_bootstrap) {
            echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">';
            echo '<div class="container">';
        }
        echo "<div class='alert alert-danger'>$msg";
        echo "<a href='javascript:history.back()' class='alert-link' style='display:block;'>Go back</a>";
        echo "<a href='/index.php?page=home' class='alert-link' style='display:block;'>Go to the Homepage</a>";
        echo "</div>";
        if($needs_bootstrap) {
            echo '</div>';
        }
    }
?>