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

<h2>Group Management</h2>
<div class="list-group" role="group" aria-label="Basic example">
    <a href="index.php?page=edit_group&id=<?php echo $group_id ?>" class="list-group-item list-group-item-action">Edit Group</a>
    <a href="actions/groups/delete.php?id=<?php echo $group_id ?>" class="list-group-item list-group-item-actio list-group-item-danger">Delete Group</a>
</div>