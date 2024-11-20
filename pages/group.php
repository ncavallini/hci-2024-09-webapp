<?php
    if(!isset($_GET['id'])) {
        redirect("index.php?page=manage");
        die;
    }

    $group_id = $_GET['id'];

    $sql = "SELECT g.* FROM groups_with_coins g WHERE group_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();

    if(!$group) {
        redirect("index.php?page=manage");
        die;
    }
?>

<h1>Group <i><?php echo $group['name'] ?></i></h1>
<br>
<h2>Coins</h2>
<p class="lead">You earned <strong><?php echo $group['coins'] ?> coins.</strong> </p>
<h2>Members</h2>
<br>
<div class="list-group">
    <?php
    $sql = "SELECT m.username, u.first_name, u.last_name FROM membership m JOIN users u USING(username) WHERE group_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$group_id]);
    $members = $stmt->fetchAll();
    $max_load = UserUtils::get_max_load();

    foreach($members as $member) {
        echo "<a href=\"#\" class=\"list-group-item list-group-item-action\">";
        echo UserUtils::get_avatar($member['first_name'][0] . $member['last_name'][0]);
        echo "&nbsp;";
        echo $member['first_name'] . " " . $member['last_name'];
        echo "&nbsp;&nbsp;&nbsp;";
        $progress_width = (UserUtils::get_total_load($member['username']) / $max_load) * 100;
        echo "<span class='progress' style='height:10px; width:30%;'><span class='progress-bar' style='width: $progress_width%;'></span></span>";
        echo "</a>";
    }
    ?>
</div>
<br>
<h2>Tasks</h2>
<br>
<a href="index.php?page=add_task&group_id=<?php echo $group_id ?>" class="btn btn-primary"><i class="fa fa-plus"></i></a>
<p>&nbsp;</p>
<div class="table-responsive">
    <table class="table" id="personal-tasks">
        <thead>
            <tr>
                <th>Done?</th>
                <th>Task</th>
                <th>Due</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM group_tasks WHERE group_id = ? ORDER BY is_completed ASC, due_date ASC";
            $stmt = $dbconnection->prepare($sql);
            $stmt->execute([$group_id]);
            $tasks = $stmt->fetchAll();

            foreach( $tasks as $task ) {
                $done_class = $task['is_completed'] ? "table-success" : "";
                echo "<tr class='$done_class'>";
                echo "<td><input type='checkbox' class='form-check-input' " . ($task['is_completed'] ? "checked" : "") . " onclick=\"window.location.href='./actions/tasks/toggle_completed.php?group_id=$group_id&task_id=" . $task['group_task_id'] . "'\"></td>";
                echo "<td>" . $task['title'] . "</td>";
                echo "<td>". (new DateTimeImmutable($task['due_date']))->format("d/m/Y, H:i") ."</td>";
                echo "<td>". "<a href='index.php?page=edit_task&group_id=$group_id&task_id=" . $task['group_task_id'] . "'><i class='fa fa-edit'></i></a>    <a href='./actions/tasks/delete.php?group_id=$group_id&task_id=" . $task['group_task_id'] . "'><i class='fa fa-trash'/></a></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>
<br>

<h2>Management</h2>
<div class="list-group" role="group" aria-label="Basic example">
    <a href="index.php?page=edit_group&group_id=<?php echo $group_id ?>" class="list-group-item list-group-item-action"> <i class="fa fa-edit"></i> Edit Group</a>
    <a href="actions/groups/delete.php?id=<?php echo $group_id ?>" class="list-group-item list-group-item-actio list-group-item-danger"> <i class="fa fa-trash"></i> Delete Group</a>
</div>