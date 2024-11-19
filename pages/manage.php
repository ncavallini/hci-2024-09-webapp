<h1 class="text-center">Manage</h1>

<h2>Personal</h2>
<div class="d-grid gap-2">
    <a href="index.php?page=manage_personal" class="btn btn-outline-primary">Manage your personal tasks & profile</a>
</div>

<br>

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

   else {
       echo "<br>&nbsp;<br><div class='d-grid gap-2'>";
       foreach($groups as $group) {
              echo "<a class='btn btn-outline-primary' href='index.php?page=group&id=" . $group['group_id'] . "' class='btn btn-primary'>" . $group['name'] . "</a>"; 
        }
        echo "</div>";
   }
    
?>