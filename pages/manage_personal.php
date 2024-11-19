<h1>Manage Personal Tasks & Profile</h1>
<br>
<h2>Tasks</h2>
<br>
<a href="index.php?page=add_task" class="btn btn-primary"><i class="fa fa-plus"></i></a>
<p>&nbsp;</p>
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Task</th>
                <th>Due</th>
                <th>Done?</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM tasks WHERE user_id = ? ORDER BY is_completed ASC, due_date ASC";
            $stmt = $dbconnection->prepare($sql);
            $stmt->execute([Auth::user()["user_id"]]);
            $tasks = $stmt->fetchAll();

            foreach( $tasks as $task ) {
                

                echo "<tr>";
                echo "<td>" . $task['title'] . "</td>";
                echo "<td>". (new DateTimeImmutable($task['due_date']))->format("d/m/Y, H:i") ."</td>";
                echo "<td><input type='checkbox' class='form-check-input' " . ($task['is_completed'] ? "checked" : "") . " onclick=\"window.location.href='./actions/tasks/toggle_completed.php?task_id=" . $task['task_id'] . "'\"></td>";
                echo "<td>". "<a href='index.php?page=edit_task&task_id=" . $task['task_id'] . "'><i class='fa fa-edit'></i></a>    <a href='./actions/tasks/delete.php?task_id=" . $task['task_id'] . "'><i class='fa fa-trash'/></a></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>
<br>
<h2>Your Profile</h2>
<br>
<?php
    $user = Auth::user();
?>
<ul class="list-group">
  <li class="list-group-item"><i class="fa fa-user me-1"></i><?php echo $user['first_name'] . " " . $user['last_name'] ?></li>
  <li class="list-group-item"><i class="fa fa-at me-1"></i><?php echo $user['email'] ?></li>
  <li class="list-group-item"><i class="fa fa-key me-1"></i><code>******</code> <a class="link-with-icon" href="index.php?page=change_password">Change</a></li>
  <li class="list-group-item bg-warning"><i class="fa fa-coins me-1"></i><?php echo $user['coins'] ?> coins</li>
</ul>