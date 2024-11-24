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

<?php
require_once __DIR__ . "/../utils/init.php";

if (!Auth::is_logged_in()) {
    header("Location: ../../index.php?page=login");
    die;
}

try {
    $user_id = Auth::user()['user_id'];
    $dbconnection = DBConnection::get_connection();

    // Fetch personal tasks
    $sql = "SELECT title, description, due_date, estimated_load, 'Personal' AS group_name, is_completed 
            FROM tasks 
            WHERE user_id = ? 
            ORDER BY is_completed ASC, estimated_load DESC, due_date ASC";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);
    $personalTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch group tasks
    $sql = "SELECT 
                gt.title, 
                gt.description, 
                gt.due_date, 
                gt.estimated_load, 
                g.name AS group_name, 
                gt.is_completed 
            FROM 
                group_tasks gt
            JOIN 
                groups g ON gt.group_id = g.group_id
            WHERE 
                gt.user_id = ?
            ORDER BY 
                gt.is_completed ASC, gt.estimated_load DESC, gt.due_date ASC";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);
    $groupTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge tasks
    $tasks = array_merge($personalTasks, $groupTasks);

    // Calculate total mental load
    $total_load = array_sum(array_column($tasks, 'estimated_load'));

    // Fetch and update maximum load
    $sql = "SELECT max_load FROM users WHERE user_id = ?";
    $stmt = $dbconnection->prepare($sql);
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $max_load = $row['max_load'] ?? 0;
    if ($total_load > $max_load) {
        $max_load = $total_load;
        $sql = "UPDATE users SET max_load = ? WHERE user_id = ?";
        $stmt = $dbconnection->prepare($sql);
        $stmt->execute([$max_load, $user_id]);
    }

    $load_percentage = ($max_load > 0) ? ($total_load / $max_load) * 100 : 0;

} catch (Exception $e) {
    $tasks = [];
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
                id="loadProgressBar" 
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

    <!-- Buttons Row -->
    <div class="d-flex justify-content-between mb-3">
        <!-- Left-aligned Task/Group Buttons -->
        <div>
            <button id="taskListButton" class="btn btn-primary" onclick="showListView('tasks')">Tasks</button>
            <button id="groupListButton" class="btn btn-secondary" onclick="showListView('groups')">Groups</button>
        </div>

        <!-- Right-aligned List/Pie Chart View Buttons -->
        <div>
            <button id="listViewButton" class="btn btn-primary" onclick="showView('listView')">List View</button>
            <button id="pieChartViewButton" class="btn btn-secondary" onclick="showView('pieChartView')">Pie Chart View</button>
        </div>
    </div>


    <!-- List View -->
    <div id="listView" class="d-flex flex-column gap-3">
        <div id="taskItems">
            <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-item d-flex justify-content-between align-items-center p-3 border rounded"
                        onclick="showTaskDetails('<?php echo htmlspecialchars(json_encode($task), ENT_QUOTES, 'UTF-8'); ?>')">
                        <div class="task-info">
                            <h5 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h5>
                            <p class="mb-1 text-muted">Group: <?php echo htmlspecialchars($task['group_name']); ?></p>
                            <small class="text-muted">Due: <?php echo (new DateTimeImmutable($task['due_date']))->format('Y-m-d H:i:s'); ?></small>
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

    <!-- Modal for Task Details OPENS OVERLAY DETAILS-->
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

    <!-- Modal for Group Details OPENS OVERLAY DETAILS-->
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
        <canvas id="pieChart" width="400" height="400"></canvas>
    </div>
</div>


    <!-- Pie Chart View -->
    <div id="pieChartView" style="display: none;">
        <canvas id="pieChart" width="400" height="400"></canvas>
    </div>

</div>

<script>
    let pieChart; // Chart.js instance
    let currentMode = 'tasks'; // Default to 'tasks'
    

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

        // Update button styles
        listViewButton.classList.add(viewId === 'listView' ? 'btn-primary' : 'btn-secondary');
        listViewButton.classList.remove(viewId === 'listView' ? 'btn-secondary' : 'btn-primary');

        pieChartViewButton.classList.add(viewId === 'pieChartView' ? 'btn-primary' : 'btn-secondary');
        pieChartViewButton.classList.remove(viewId === 'pieChartView' ? 'btn-secondary' : 'btn-primary');

        // Show the selected view
        if (viewId === 'listView') {
            listView.classList.add('visible');
            pieChartView.classList.add('hidden');
            showListView(currentMode); // Respect the current mode
        } else if (viewId === 'pieChartView') {
            listView.classList.add('hidden');
            pieChartView.classList.add('visible');
            showPieChart(currentMode); // Render Pie Chart in Tasks mode
        }

    }




    function showListView(mode) {
        const container = document.getElementById("taskItems");
        container.innerHTML = "";

        const tasks = <?php echo json_encode($tasks); ?>;

        if (mode === "tasks") {
            tasks.forEach(task => {
                const isCompleted = task.is_completed === 1;
                const dueDate = new Date(task.due_date);
                const now = new Date();
                const isOverdue = dueDate < now && !isCompleted;

                const taskDiv = document.createElement("div");
                taskDiv.className = "task-item d-flex justify-content-between align-items-center p-3 border rounded";
                taskDiv.style.backgroundColor = isOverdue ? "lightcoral" : "";
                taskDiv.onclick = () => showTaskDetails(JSON.stringify(task));
                taskDiv.innerHTML = `
                    <div class="task-info">
                        <h5 class="${isCompleted ? 'text-primary' : ''} mb-1">
                            ${task.title} ${isCompleted ? '<span class="badge bg-success">Completed</span>' : ''}
                        </h5>
                        <p class="mb-1 text-muted">${task.group_name === 'Personal' ? 'Personal Task' : `Group: ${task.group_name}`}</p>
                        <small class="text-muted">Due: ${dueDate.toLocaleString()}</small>
                    </div>
                    <div class="task-load text-end">
                        <span class="badge bg-primary">Load: ${task.estimated_load}</span>
                    </div>
                `;
                container.appendChild(taskDiv);
            });
        } else if (mode === "groups") {
            const groupedTasks = tasks.reduce((acc, task) => {
                acc[task.group_name] = acc[task.group_name] || [];
                acc[task.group_name].push(task);
                return acc;
            }, {});

            Object.entries(groupedTasks).forEach(([groupName, groupTasks]) => {
                const groupDiv = document.createElement("div");
                groupDiv.className = "group-item border rounded p-3 mb-3";
                groupDiv.onclick = () => showGroupDetails(groupName, groupTasks);
                groupDiv.innerHTML = `
                    <h5>${groupName}</h5>
                    <p>${groupTasks.length} tasks in this group</p>
                `;
                container.appendChild(groupDiv);
            });
    }

    document.getElementById("taskListButton").classList.toggle("btn-primary", mode === "tasks");
    document.getElementById("taskListButton").classList.toggle("btn-secondary", mode !== "tasks");
    document.getElementById("groupListButton").classList.toggle("btn-primary", mode === "groups");
    document.getElementById("groupListButton").classList.toggle("btn-secondary", mode !== "groups");
}





    function showGroupDetails(groupName, groupTasks) {
        // Populate the modal with group details
        document.getElementById('groupName').textContent = groupName;

        const groupTasksList = document.getElementById('groupTasksList');
        groupTasksList.innerHTML = '';

        // Add tasks to the group list
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




    function showTaskDetails(taskJson) {
        const task = JSON.parse(taskJson);
        document.getElementById('taskTitle').textContent = task.title;
        document.getElementById('taskDescription').textContent = task.description;
        document.getElementById('taskDueDate').textContent = new Date(task.due_date).toLocaleString();
        document.getElementById('taskEstimatedLoad').textContent = task.estimated_load;
        document.getElementById('taskGroupName').textContent = task.group_name;
        new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();
    }

    function showPieChart(mode) {
        currentMode = mode; // Remember the current mode
        const tasks = <?php echo json_encode($tasks); ?>;

        let data;
        if (mode === 'tasks') {
            data = tasks.map(task => ({ label: task.title, value: task.estimated_load }));
        } else if (mode === 'groups') {
            const groupData = tasks.reduce((acc, task) => {
                acc[task.group_name] = (acc[task.group_name] || 0) + task.estimated_load;
                return acc;
            }, {});
            data = Object.entries(groupData).map(([label, value]) => ({ label, value }));
        }

        const ctx = document.getElementById('pieChart').getContext('2d');
        if (pieChart) pieChart.destroy();
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
                    legend: { position: 'top' },
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

        // Update button styles for Task/Group toggle
        document.getElementById('tasksPieButton').classList.toggle('btn-primary', mode === 'tasks');
        document.getElementById('tasksPieButton').classList.toggle('btn-secondary', mode !== 'tasks');
        document.getElementById('groupsPieButton').classList.toggle('btn-primary', mode === 'groups');
        document.getElementById('groupsPieButton').classList.toggle('btn-secondary', mode !== 'groups');
    }

    function updateProgressBar(loadPercentage) {
        const progressBar = document.querySelector(".progress-bar");

        if (!progressBar) {
            console.error("Progress bar element not found!");
            return;
        }

        // Set the progress bar width and aria attributes
        progressBar.style.width = `${loadPercentage}%`;
        progressBar.setAttribute("aria-valuenow", loadPercentage);

        // Change the progress bar's background color based on the percentage
        if (loadPercentage >= 80) {
            progressBar.style.backgroundColor = "darkred"; // High load
        } else if (loadPercentage >= 50) {
            progressBar.style.backgroundColor = "orange"; // Moderate load
        } else {
            progressBar.style.backgroundColor = "lightgreen"; // Low load
        }
    }

    function highlightOverdueTasks() {
        const taskItems = document.querySelectorAll(".task-item");

        if (!taskItems) {
            console.error("Task items not found!");
            return;
        }

        // Get the current date and time
        const now = new Date();

        // Loop through each task item
        taskItems.forEach(taskItem => {
            // Extract the due date from the task's data attribute or inner HTML
            const dueDateElement = taskItem.querySelector(".task-info small");
            if (dueDateElement) {
                const dueDateText = dueDateElement.textContent.replace("Due: ", "");
                const dueDate = new Date(dueDateText);


            }
        });
    }



    document.addEventListener('DOMContentLoaded', () => {
        // Default to List View and Tasks mode
        showView('listView');
        showListView('tasks');

        // Example: Use PHP to pass the `load_percentage` to JavaScript
        const progressBar = document.getElementById("loadProgressBar");
        const loadPercentage = <?php echo round($load_percentage); ?>;

        // Call the updateProgressBar function with the initial load percentage
        updateProgressBar(loadPercentage);

        if (progressBar) {
            progressBar.addEventListener("click", () => {
                window.location.href = "index.php?page=pastLoad";
            });
        }
        // Event listeners for switching between views
        document.getElementById('listViewButton').addEventListener('click', () => showView('listView'));
        document.getElementById('pieChartViewButton').addEventListener('click', () => showView('pieChartView'));

        // Event listeners for toggling tasks and groups in List View
        document.getElementById('taskListButton').addEventListener('click', () => {
            console.log("Switching to Task View in List Chart");
            showListView('tasks')
        });
        document.getElementById('groupListButton').addEventListener('click', () => showListView('groups'));

        // Event listeners for toggling tasks and groups in Pie Chart View
        document.getElementById('taskListButton').addEventListener('click', () => {
            console.log("Switching to Task View in Pie Chart");
            showPieChart('tasks');
        });
        document.getElementById('groupListButton').addEventListener('click', () => {
            console.log("Switching to Group View in Pie Chart");
            showPieChart('groups');
        });

        highlightOverdueTasks();
    });

</script>

<style>
    .task-item:hover {
        background-color: #e9ecef; /* Light grey hover */
        color: inherit;
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