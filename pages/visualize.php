<?php

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
$sql = "SELECT * FROM tasks WHERE user_id = ? AND is_completed = 0 ORDER BY is_completed ASC, due_date ASC";
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

<?php

// Ensure the user is logged in
if (!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

try {
    $user_id = Auth::user()['user_id'];
    $dbconnection = DBConnection::get_connection();

    
    $sql = "SELECT title, description, due_date, estimated_load, 'Personal' AS group_name 
            FROM tasks 
            WHERE user_id = ? AND is_completed = 0 
            ORDER BY estimated_load DESC, due_date ASC";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);
    $personalTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT 
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
    $groupTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tasks = array_merge($personalTasks, $groupTasks);


    // Aggregate estimated loads by group
    $sql = "
        SELECT 
            g.name AS group_name,
            SUM(gt.estimated_load) AS total_load
        FROM 
            group_tasks gt
        JOIN 
            groups g ON gt.group_id = g.group_id
        WHERE 
            gt.user_id = ?
        GROUP BY 
            g.group_id";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);
    $groupLoads = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $tasks = [];
    $groupLoads = [];
    $error = $e->getMessage();
}
?>


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

    <!-- List View -->
    <div id="listView" class="d-flex flex-column gap-3">
        <!-- Task/Group Toggle Buttons -->
        <div class="mb-3 text-center">
            <button id="taskListButton" class="btn btn-primary" onclick="showListView('tasks')">Tasks</button>
            <button id="groupListButton" class="btn btn-secondary" onclick="showListView('groups')">Groups</button>
        </div>

        <!-- Task Items Container -->
        <div id="taskItems">
            <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-item d-flex justify-content-between align-items-center p-3 border rounded"
                        onclick="showTaskDetails('<?php echo htmlspecialchars(json_encode($task), ENT_QUOTES, 'UTF-8'); ?>')">
                        <div class="task-info">
                            <h5 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h5>
                            <p class="mb-1 text-muted">Group: <?php echo htmlspecialchars($task['group_name']); ?></p>
                            <small class="text-muted">Due: <?php echo (new DateTimeImmutable($task['due_date']))->format('F j, Y, g:i A'); ?></small>
                        </div>
                        <div class="task-load text-end">
                            <span class="badge bg-primary">Load: <?php echo htmlspecialchars($task['estimated_load']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="lead text-center text-muted">No tasks found for you across any groups.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Task Details -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
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

    <!-- Modal for Group Details -->
    <div class="modal fade" id="groupDetailsModal" tabindex="-1" aria-labelledby="groupDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="groupDetailsModalLabel">Group Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5 id="groupName"></h5>
                    <ul id="groupTasksList" class="list-unstyled"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Pie Chart View -->
    <div id="pieChartView" style="display: none;">
        <div class="mb-3 text-center">
            <button id="tasksPieButton" class="btn btn-primary" onclick="showPieChart('tasks')">Tasks</button>
            <button id="groupsPieButton" class="btn btn-secondary" onclick="showPieChart('groups')">Groups</button>
        </div>
        <canvas id="pieChart" width="400" height="400"></canvas>
    </div>

    <!-- View Toggle Buttons -->
    <div class="mt-4 text-center">
        <button id="listViewButton" class="btn btn-primary" onclick="showView('listView')">List View</button>
        <button id="pieChartViewButton" class="btn btn-secondary" onclick="showView('pieChartView')">Pie Chart View</button>
    </div>
</div>

<script>
    let pieChart; // Chart.js instance

    function showView(viewId) {
    // Get references to views and buttons
    const listView = document.getElementById('listView');
    const pieChartView = document.getElementById('pieChartView');
    const listViewButton = document.getElementById('listViewButton');
    const pieChartViewButton = document.getElementById('pieChartViewButton');

    // Ensure all elements exist
    if (!listView || !pieChartView || !listViewButton || !pieChartViewButton) {
        console.error("Required elements not found!");
        return;
    }

    // Reset both views
    listView.classList.remove('visible', 'hidden');
    pieChartView.classList.remove('visible', 'hidden');

    // Show the selected view
    if (viewId === 'listView') {
        listView.classList.add('visible');
        pieChartView.classList.add('hidden');
    } else if (viewId === 'pieChartView') {
        listView.classList.add('hidden');
        pieChartView.classList.add('visible');
        showPieChart('tasks'); // Render Pie Chart in Tasks mode
    }

    // Update button styles
    listViewButton.classList.add(viewId === 'listView' ? 'btn-primary' : 'btn-secondary');
    listViewButton.classList.remove(viewId === 'listView' ? 'btn-secondary' : 'btn-primary');

    pieChartViewButton.classList.add(viewId === 'pieChartView' ? 'btn-primary' : 'btn-secondary');
    pieChartViewButton.classList.remove(viewId === 'pieChartView' ? 'btn-secondary' : 'btn-primary');
}



function showListView(mode) {
    const container = document.getElementById('taskItems');
    container.innerHTML = '';

    if (mode === 'tasks') {
        tasks.forEach(task => {
            const color = generateColor(task.group_name);
            const isCompleted = task.is_completed === 1; // Check if the task is completed

            const taskDiv = document.createElement('div');
            taskDiv.className = 'task-item d-flex justify-content-between align-items-center p-3 border rounded';
            taskDiv.innerHTML = `
                <div>
                    <h5 class="mb-1 ${isCompleted ? 'text-primary' : ''}">
                        ${task.title} ${isCompleted ? '<span class="badge bg-success">Completed</span>' : ''}
                    </h5>
                    <p class="text-muted mb-1">${task.group_name === 'Personal' ? 'Personal Task' : `Group: ${task.group_name}`}</p>
                    <p class="text-muted">Due: ${new Date(task.due_date).toLocaleString()}</p>
                </div>
                <div class="task-load text-end">
                    <span class="badge" style="background-color: ${color.base};">Load: ${task.estimated_load}</span>
                </div>
            `;
            taskDiv.onclick = () => showTaskDetails(JSON.stringify(task));
            container.appendChild(taskDiv);
        });
    } else if (mode === 'groups') {
        // Group view logic remains unchanged
    }

    // Update button styles for Task/Group toggle
    document.getElementById('taskListButton').classList.toggle('btn-primary', mode === 'tasks');
    document.getElementById('taskListButton').classList.toggle('btn-secondary', mode !== 'tasks');
    document.getElementById('groupListButton').classList.toggle('btn-primary', mode === 'groups');
    document.getElementById('groupListButton').classList.toggle('btn-secondary', mode !== 'groups');
}


function showGroupDetails(groupName, groupTasks) {
    // Populate the modal with group details
    document.getElementById('groupName').textContent = groupName;

    const groupTasksList = document.getElementById('groupTasksList');
    groupTasksList.innerHTML = ''; // Clear previous tasks

    groupTasks.forEach(task => {
        const taskItem = document.createElement('li');
        taskItem.innerHTML = `
            <strong>${task.title}</strong> (Load: ${task.estimated_load})<br>
            Due: ${new Date(task.due_date).toLocaleString()}
        `;
        groupTasksList.appendChild(taskItem);
    });

    // Show the modal
    new bootstrap.Modal(document.getElementById('groupDetailsModal')).show();
}

    function generateColor(identifier) {
        const hash = Array.from(identifier)
            .reduce((acc, char) => acc + char.charCodeAt(0), 0);
        const r = (hash * 53) % 255;
        const g = (hash * 101) % 255;
        const b = (hash * 197) % 255;
        return { base: `rgba(${r}, ${g}, ${b}, 1)`, light: `rgba(${r}, ${g}, ${b}, 0.7)` };
    }


    function showTaskDetails(taskJson) {
        const task = JSON.parse(taskJson);
        document.getElementById('taskTitle').textContent = task.title;
        document.getElementById('taskDescription').textContent = task.description || 'No description provided';
        document.getElementById('taskDueDate').textContent = new Date(task.due_date).toLocaleString();
        document.getElementById('taskEstimatedLoad').textContent = task.estimated_load;
        document.getElementById('taskGroupName').textContent = task.group_name;

        new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();
    }

    function showPieChart(mode) {
        const tasks = <?php echo json_encode($tasks); ?>;

        // Prepare data based on mode
        const data = mode === 'tasks'
            ? tasks.map(task => ({ label: task.title, value: task.estimated_load }))
            : Object.entries(tasks.reduce((acc, task) => {
                acc[task.group_name] = (acc[task.group_name] || 0) + task.estimated_load;
                return acc;
            }, {})).map(([label, value]) => ({ label, value }));

        // Update button styles for Tasks and Groups toggle
        const tasksPieButton = document.getElementById('tasksPieButton');
        const groupsPieButton = document.getElementById('groupsPieButton');

        tasksPieButton.classList.add(mode === 'tasks' ? 'btn-primary' : 'btn-secondary');
        tasksPieButton.classList.remove(mode === 'tasks' ? 'btn-secondary' : 'btn-primary');
        groupsPieButton.classList.add(mode === 'groups' ? 'btn-primary' : 'btn-secondary');
        groupsPieButton.classList.remove(mode === 'groups' ? 'btn-secondary' : 'btn-primary');

        // Destroy the existing chart instance (if any) before creating a new one
        if (pieChart) pieChart.destroy();

        // Render the new pie chart
        const ctx = document.getElementById('pieChart').getContext('2d');
        pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(d => d.label),
                datasets: [{
                    data: data.map(d => d.value),
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6610f2']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: (tooltipItem) => {
                                const label = data[tooltipItem.dataIndex].label;
                                const value = data[tooltipItem.dataIndex].value;
                                return `${label}: ${value}`;
                            }
                        }
                    }
                }
            }
        });
    }


    document.addEventListener('DOMContentLoaded', () => {
    showView('listView');
    showListView('tasks');
</script>

<style>
    .task-item:hover {
        background-color: #e9ecef; /* Light grey hover */
        cursor: pointer;
    }
    .group-item:hover {
        background-color: #e9ecef;
    }

    .hidden {
        display: none !important;
    }
    .visible {
        display: block !important;
    }
    .text-primary {
        color: #007bff !important;
    }

    .bg-success {
        background-color: #28a745 !important;
        color: white;
    }

    .task-item:hover {
        background-color: #e9ecef;
        cursor: pointer;
    }

</style>
