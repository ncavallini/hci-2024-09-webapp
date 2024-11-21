<?php
require_once __DIR__ . "/../../utils/init.php";
if(!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

$connection = DBConnection::get_connection();
$group_id = $_GET['group_id'] ?? 0; 
$task_id = $_GET['task_id'];


if($group_id == 0) {
    $sql = "UPDATE tasks SET is_completed = NOT is_completed WHERE task_id = ?";
    $location = "../../index.php?page=manage_personal";
}
else {
    $sql = "UPDATE group_tasks SET is_completed = NOT is_completed WHERE group_task_id = ?";
    $location = "../../index.php?page=group&id=$group_id";

}

$stmt = $connection->prepare($sql);
$stmt->execute([$task_id]);
header("Location: $location");
?>