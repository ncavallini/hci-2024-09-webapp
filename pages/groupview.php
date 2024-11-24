<?php
require_once __DIR__ . '/../utils/init.php'; // Include your initialization logic


// Fetch the group ID from the URL
$group_id = $_GET['id'] ?? null;
if (!$group_id) {
    die('No group ID provided.');
}

try {
    $dbconnection = DBConnection::get_connection();

    // Fetch group details
    $groupQuery = "SELECT name FROM groups WHERE group_id = ?";
    $stmt = $dbconnection->prepare($groupQuery);
    $stmt->execute([$group_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        die('Group not found.');
    }

    // Fetch tasks for this group
    $taskQuery = "
        SELECT 
            title, 
            description, 
            due_date, 
            estimated_load, 
            is_completed 
        FROM 
            group_tasks 
        WHERE 
            group_id = ? 
        ORDER BY 
            due_date ASC";
    $stmt = $dbconnection->prepare($taskQuery);
    $stmt->execute([$group_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

<h1>Tasks for Group: <?php echo htmlspecialchars($group['name']); ?></h1>
<div class="container">
    <?php if (!empty($tasks)): ?>
        <ul>
            <?php foreach ($tasks as $task): ?>
                <li>
                    <strong>Title:</strong> <?php echo htmlspecialchars($task['title']); ?><br>
                    <strong>Description:</strong> <?php echo htmlspecialchars($task['description']); ?><br>
                    <strong>Due Date:</strong> <?php echo htmlspecialchars($task['due_date']); ?><br>
                    <strong>Load:</strong> <?php echo htmlspecialchars($task['estimated_load']); ?><br>
                    <strong>Status:</strong> <?php echo $task['is_completed'] ? 'Completed' : 'Pending'; ?><br>
                </li>
                <hr>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No tasks found for this group.</p>
    <?php endif; ?>
</div>
