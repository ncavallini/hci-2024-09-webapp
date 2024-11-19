<h1 class="text-center">Manage</h1>
<h2>Groups</h2>
<a href="index.php?page=add_group" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
<?php
    $connection = DBConnection::get_connection();
    $query = "SELECT group_id, name FROM groups";
    $stmt = $connection->query($query);
    $groups = $stmt->fetchAll();
    if(count($groups) == 0) {
        echo "<p class='text-center'>There are no groups yet. Click + to create one.</p>";
    }

    foreach($groups as $group) {
        echo "<a class='btn btn-secondary' href='index.php?page=group&id={$group['group_id']}'>{$group['name']}</a><br>";
    }

    
?>