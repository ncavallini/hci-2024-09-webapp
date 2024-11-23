<?php
require_once __DIR__ . "/../utils/init.php";

// Ensure the user is logged in
if (!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

try {
    // Add 'max_load' column if it doesn't exist
    $dbconnection->exec("ALTER TABLE users ADD COLUMN max_load INT DEFAULT 0;");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") === false) {
        die("Error updating the database: " . $e->getMessage());
    }
}

// Get the current user's ID
$user_id = Auth::user()['user_id'];

// Fetch all tasks for the current user across all groups
$sql = "
    SELECT 
        gt.title, 
        gt.description, 
        gt.due_date, 
        gt.estimated_load, 
        g.name AS group_name 
    FROM 
        group_tasks gt
    JOIN 
        groups g ON gt.group_id = g.group_id
    WHERE 
        gt.user_id = ?
    ORDER BY 
        gt.estimated_load DESC, 
        gt.due_date ASC";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate the total mental load
$total_load = array_sum(array_column($tasks, 'estimated_load'));

// Fetch the maximum mental load ever recorded (store in database or session)
$sql = "SELECT max_load FROM users WHERE user_id = ?";
$stmt = $dbconnection->prepare($sql);
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Update the maximum if the current total load exceeds it
$max_load = $row['max_load'] ?? 0; // Default to 0 if no record exists
if ($total_load > $max_load) {
    $max_load = $total_load;

    // Update the maximum in the database
    $sql = "UPDATE users SET max_load = ? WHERE user_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$max_load, $user_id]);
}

// Calculate the percentage of the current load relative to the maximum
$load_percentage = ($max_load > 0) ? ($total_load / $max_load) * 100 : 0;
?>

<!DOCTYPE html>
<html>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">All Tasks</h1>

        <!-- Mental Load Bar -->
        <div class="mb-4">
            <h5>Your Mental Load</h5>
            <div class="progress">
                <div 
                    class="progress-bar" 
                    role="progressbar" 
                    style="width: <?php echo $load_percentage; ?>%;" 
                    aria-valuenow="<?php echo $total_load; ?>" 
                    aria-valuemin="0" 
                    aria-valuemax="<?php echo $max_load; ?>">
                    <?php echo round($load_percentage); ?>%
                </div>
            </div>
            <p class="mt-2">Current Load: <?php echo $total_load; ?> / Maximum Load: <?php echo $max_load; ?></p>
        </div>

        <!-- Task Table -->
        <?php if (!empty($tasks)): ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Estimated Load</th>
                        <th>Group</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr data-bs-toggle="modal" data-bs-target="#taskDetailsModal"
                            onclick="showTaskDetails('<?php echo htmlspecialchars(json_encode($task), ENT_QUOTES, 'UTF-8'); ?>')">
                            <td><?php echo htmlspecialchars($task['title']); ?></td>
                            <td><?php echo htmlspecialchars($task['description']); ?></td>
                            <td><?php echo (new DateTimeImmutable($task['due_date']))->format('d/m/Y, H:i'); ?></td>
                            <td><?php echo htmlspecialchars($task['estimated_load']); ?></td>
                            <td><?php echo htmlspecialchars($task['group_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="lead">No tasks found for you across any groups.</p>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskDetailsModalLabel">Task Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Title:</strong> <span id="taskTitle"></span></p>
                    <p><strong>Description:</strong> <span id="taskDescription"></span></p>
                    <p><strong>Due Date:</strong> <span id="taskDueDate"></span></p>
                    <p><strong>Estimated Load:</strong> <span id="taskEstimatedLoad"></span></p>
                    <p><strong>Group:</strong> <span id="taskGroupName"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showTaskDetails(taskJson) {
        const task = JSON.parse(taskJson);
        document.getElementById('taskTitle').textContent = task.title;
        document.getElementById('taskDescription').textContent = task.description;
        document.getElementById('taskDueDate').textContent = new Date(task.due_date).toLocaleString();
        document.getElementById('taskEstimatedLoad').textContent = task.estimated_load;
        document.getElementById('taskGroupName').textContent = task.group_name;
    }
    </script>
</body>
</html>
