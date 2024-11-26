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
// Fetch personal tasks only
// Fetch personal tasks
$sql = "
    SELECT 
        t.user_id, 
        t.title, 
        t.created_at, 
        'tasks' AS source_table
    FROM 
        tasks t
    LEFT JOIN 
        group_tasks gt
    ON 
        t.user_id = gt.user_id AND 
        t.title = gt.title AND 
        t.created_at = gt.created_at
    WHERE 
        gt.user_id IS NULL

    UNION

    SELECT 
        gt.user_id, 
        gt.title, 
        gt.created_at, 
        'group_tasks' AS source_table
    FROM 
        group_tasks gt
    LEFT JOIN 
        tasks t
    ON 
        gt.user_id = t.user_id AND 
        gt.title = t.title AND 
        gt.created_at = t.created_at
    WHERE 
        t.user_id IS NULL
";
$stmt = $dbconnection->prepare($sql);
$stmt->execute();
$non_merged_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Calculate total mental load for personal tasks
$total_load = array_sum(array_map(function ($task) {
    return !$task['is_completed'] ? $task['estimated_load'] : 0;
}, $tasks));

// Fetch and update maximum mental load
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
?>

<?php
try {
    $user_id = Auth::user()['user_id'];
    $dbconnection = DBConnection::get_connection();

    // Fetch personal tasks
    $sql = "SELECT title, description, due_date, estimated_load, 'Personal' AS group_name, 0 as group_id, is_completed 
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
                g.group_id,
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
    $total_load = array_sum(array_map(function ($task) {
        return !$task['is_completed'] ? $task['estimated_load'] : 0;
    }, $tasks));

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
    <h1 class="mb-4">Personal Tasks</h1>

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

    <!-- List View -->
    <div id="listView" class="d-flex flex-column gap-3">
        <div id="taskItems"></div>
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




    function showListView() {
        const container = document.getElementById("taskItems");
        container.innerHTML = "";

        const tasks = <?php echo json_encode($tasks); ?>;

        const activeTasks = tasks.filter(task => task.is_completed === 0); // Exclude completed tasks

        activeTasks.forEach(task => {
            const dueDate = new Date(task.due_date);
            const now = new Date();
            const isOverdue = dueDate < now;

            const taskDiv = document.createElement("div");
            taskDiv.className = "task-item d-flex justify-content-between align-items-center p-3 border rounded";
            taskDiv.style.backgroundColor = isOverdue ? "lightcoral" : "";
            taskDiv.onclick = () => showTaskDetails(JSON.stringify(task));
            taskDiv.innerHTML = `
                <div class="task-info">
                    <h5 class="mb-1">${task.title}</h5>
                    <small class="text-muted">Due: ${dueDate.toLocaleString()}</small>
                </div>
                <div class="task-load text-end">
                    <span class="badge bg-primary">Load: ${task.estimated_load}</span>
                </div>`;
            container.appendChild(taskDiv);
        });
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

    function showPieChart() {
    const tasks = <?php echo json_encode($tasks); ?>;
    const ctx = document.getElementById("pieChart").getContext("2d");

    // Filter for active (incomplete) tasks
    const activeTasks = tasks.filter(task => task.is_completed === 0);

    const pieData = {
        labels: activeTasks.map(task => task.title),
        datasets: [{
            data: activeTasks.map(task => task.estimated_load),
            backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6610f2']
        }]
    };

    if (pieChart) pieChart.destroy();

    pieChart = new Chart(ctx, {
        type: 'pie',
        data: pieData,
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (tooltipItem) => {
                            const label = pieData.labels[tooltipItem.dataIndex];
                            const value = pieData.datasets[0].data[tooltipItem.dataIndex];
                            return `${label}: ${value}`;
                        }
                    }
                }
            }
        }
    });
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

        // Example: Use PHP to pass the load_percentage to JavaScript
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
