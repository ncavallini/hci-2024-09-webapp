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
                echo "<td>" . ($task['is_completed'] == 1 ? "Yes" : "No") . "</td>";
                echo "<td>". "ACTIONS" ."</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>
