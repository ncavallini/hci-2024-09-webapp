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
        g.name AS group_name,
        g.group_id
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
    <h1 class="mb-4">All Tasks</h1>

    <div class="mb-4 position-relative">
        <h5>Your Mental Load
            <a class= "nav-link" href="index.php?page=pastLoad"> 
                <button 
                    class="btn btn-sm btn-info position-absolute top-0 end-0" >
                    Past Mental Load
                </button>
            </a>
        </h5>
        <div class="progress mt-3">
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
    <div class="d-flex justify-content-between mb-3 gap-2">
        <!-- Left-aligned Task/Group Buttons -->
        <div class="d-flex flex-wrap gap-2">
        <button id="taskListButton" class="btn btn-secondary" onclick="window.location.href='index.php?page=visualize'">Tasks</button>
        <button id="groupListButton" class="btn btn-primary" onclick="showListView('groups')">Groups</button>
        </div>

        <!-- Right-aligned List/Pie Chart View Buttons -->
        <div class="d-flex flex-wrap gap-2">
            <button id="listViewButton" class="btn btn-primary" onclick="showView('listView')">List View</button>
            <button id="pieChartViewButton" class="btn btn-secondary" onclick="showView('pieChartView')">Pie Chart View</button>
            <button id="bubbleChartViewButton" class="btn btn-secondary" onclick="showView('bubbleChartView')">Bubble Chart View</button>
        </div>
    </div>


    <!-- List View -->
    <div id="listView" class="d-flex flex-column gap-3 overflow-auto" style="max-height: 80vh;">
        <div id="taskItems">
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

    <!-- Bubble Chart View -->
    <div id="bubbleChartView" style="display: none;">
        <canvas id="bubbleChart" width="400" height="400"></canvas>
    </div>
</div>



<script>
    function showBubbleChart(mode) {
        currentMode = mode;
        const tasks = <?php echo json_encode($tasks); ?>;
        const ctx = document.getElementById("bubbleChart").getContext("2d");

        const activeTasks = tasks.filter(task => parseInt(task.is_completed, 10) === 0);

        let bubbleData;
        let groupData = {};

        const colorPalette = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#E7E9ED', '#B39DDB', '#9CCC65', '#FF7043',
        ];

        let groupColorMap = {};
        let colorIndex = 0;

        if (mode === 'tasks') {
            activeTasks.forEach(task => {
                if (!groupColorMap[task.group_name]) {
                    groupColorMap[task.group_name] = colorPalette[colorIndex % colorPalette.length];
                    colorIndex++;
                }
            });

            bubbleData = {
                datasets: [{
                    label: 'Tasks',
                    data: activeTasks.map(task => ({
                        x: task.due_date,
                        y: task.estimated_load,
                        r: task.estimated_load * 2, // Adjust as needed
                        taskTitle: task.title,
                        groupName: task.group_name,
                        backgroundColor: groupColorMap[task.group_name],
                    })),
                    backgroundColor: activeTasks.map(task => groupColorMap[task.group_name]),
                }]
            };
        } else if (mode === 'groups') {
            activeTasks.forEach(task => {
                if (!groupColorMap[task.group_name]) {
                    groupColorMap[task.group_name] = colorPalette[colorIndex % colorPalette.length];
                    colorIndex++;
                }
            });

            activeTasks.forEach(task => {
                if (!groupData[task.group_name]) {
                    groupData[task.group_name] = {
                        group_id: task.group_id,
                        total_load: 0,
                        task_count: 0,
                        group_name: task.group_name,
                        backgroundColor: groupColorMap[task.group_name],
                    };
                }
                groupData[task.group_name].total_load += task.estimated_load;
                groupData[task.group_name].task_count += 1;
            });

            const groupDataArray = Object.values(groupData);

            bubbleData = {
                datasets: [{
                    label: 'Groups',
                    data: groupDataArray.map(group => ({
                        x: group.task_count, // Number of tasks in the group
                        y: group.total_load, // Total estimated load
                        r: group.total_load * 2, // Adjust as needed
                        groupName: group.group_name,
                        backgroundColor: group.backgroundColor,
                    })),
                    backgroundColor: groupDataArray.map(group => group.backgroundColor),
                }]
            };
        } else {
            console.error("Invalid mode provided for bubble chart");
            return;
        }

        if (bubbleChart) bubbleChart.destroy();

        bubbleChart = new Chart(ctx, {
            type: 'bubble',
            data: bubbleData,
            options: {
                scales: {
                    x: {
                        type: mode === 'tasks' ? 'time' : 'linear',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'MMM d, yyyy',
                        },
                        title: {
                            display: true,
                            text: mode === 'tasks' ? 'Due Date' : 'Number of Tasks',
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Estimated Load',
                        },
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const index = context.dataIndex;
                                const dataPoint = context.dataset.data[index];
                                if (mode === 'tasks') {
                                    return `${dataPoint.taskTitle} (${dataPoint.groupName}): Load ${dataPoint.y}`;
                                } else if (mode === 'groups') {
                                    return `${dataPoint.groupName}: ${dataPoint.x} tasks, Total Load ${dataPoint.y}`;
                                }
                            },
                        },
                    },
                    legend: {
                        display: true,
                        labels: {
                            generateLabels: function(chart) {
                                const labels = [];
                                if (mode === 'groups') {
                                    chart.data.datasets[0].data.forEach((dataPoint, index) => {
                                        labels.push({
                                            text: dataPoint.groupName,
                                            fillStyle: dataPoint.backgroundColor,
                                            strokeStyle: dataPoint.backgroundColor,
                                            hidden: false,
                                            index: index,
                                        });
                                    });
                                } else if (mode === 'tasks') {
                                    const uniqueGroups = [...new Set(activeTasks.map(task => task.group_name))];
                                    uniqueGroups.forEach((groupName, idx) => {
                                        labels.push({
                                            text: groupName,
                                            fillStyle: groupColorMap[groupName],
                                            strokeStyle: groupColorMap[groupName],
                                            hidden: false,
                                            index: idx,
                                        });
                                    });
                                }
                                return labels;
                            },
                        },
                    },
                },
            },
        });
    }



        let pieChart; // Chart.js instance
        let currentMode = 'groups'; // Default to 'tasks'
        let bubbleChart;

        
        function showView(viewId) {
            const listView = document.getElementById('listView');
            const pieChartView = document.getElementById('pieChartView');
            const bubbleChartView = document.getElementById('bubbleChartView');
            const listViewButton = document.getElementById('listViewButton');
            const pieChartViewButton = document.getElementById('pieChartViewButton');
            const bubbleChartViewButton = document.getElementById('bubbleChartViewButton');

            // Reset views
            listView.classList.remove('visible', 'hidden');
            pieChartView.classList.remove('visible', 'hidden');
            bubbleChartView.classList.remove('visible', 'hidden');

            // Update button styles
            listViewButton.classList.toggle('btn-primary', viewId === 'listView');
            listViewButton.classList.toggle('btn-secondary', viewId !== 'listView');

            pieChartViewButton.classList.toggle('btn-primary', viewId === 'pieChartView');
            pieChartViewButton.classList.toggle('btn-secondary', viewId !== 'pieChartView');

            bubbleChartViewButton.classList.toggle('btn-primary', viewId === 'bubbleChartView');
            bubbleChartViewButton.classList.toggle('btn-secondary', viewId !== 'bubbleChartView');

            // Show the selected view
            if (viewId === 'listView') {
                listView.classList.add('visible');
                pieChartView.classList.add('hidden');
                bubbleChartView.classList.add('hidden');
                showListView(currentMode);
            } else if (viewId === 'pieChartView') {
                listView.classList.add('hidden');
                pieChartView.classList.add('visible');
                bubbleChartView.classList.add('hidden');
                showPieChart(currentMode);
            } else if (viewId === 'bubbleChartView') {
                listView.classList.add('hidden');
                pieChartView.classList.add('hidden');
                bubbleChartView.classList.add('visible');
                showBubbleChart(currentMode);
            }
        }





        function showListView(mode) {
    const container = document.getElementById("taskItems");
    container.innerHTML = "";

    const tasks = <?php echo json_encode($tasks); ?>;
    
    const activeTasks = tasks.filter(task => task.is_completed !== 1);

    if (mode === "groups") {
        const groupedTasks = activeTasks.reduce((acc, task) => {
            // Initialize the group if it doesn't exist
            if (!acc[task.group_name]) {
                acc[task.group_name] = { group_id: task.group_id, tasks: [], total_load: 0 };
            }
            // Add task to the group
            acc[task.group_name].tasks.push(task);
            // Accumulate the load
            acc[task.group_name].total_load += task.estimated_load;
            return acc;
        }, {});

        Object.entries(groupedTasks).forEach(([groupName, groupData]) => {
            const groupDiv = document.createElement("div");
            groupDiv.className = "group-item border rounded p-3 mb-3";
            groupDiv.innerHTML = `
                <div class"flex-grow-1 me-3">
                    <h5 class="justify-content-between">
                        ${groupName}
                        <button class="btn btn-primary btn-sm view-group-button">View Group</button>
                    </h5>
                    <p>${groupData.tasks.length} tasks</p>
                </div>
                <div>
                    <span class="badge bg-primary">Load: ${groupData.total_load}</span>
                </div>
            `;

            // Add click event listener for the button
            groupDiv.querySelector(".view-group-button").addEventListener("click", (event) => {
                event.preventDefault(); // Prevent default behavior
                if (groupData.group_id != 0) {
                    // Redirect to the group-specific URL
                    window.location.href = `index.php?page=groupview&id=${groupData.group_id}`;
                } else {
                    // Redirect to the personal group visualization page
                    window.location.href = `index.php?page=visualize_personal`;
                }
            });

            container.appendChild(groupDiv);
        });
    }

    document.getElementById("taskListButton").classList.toggle("btn-primary", mode === "tasks");
    document.getElementById("taskListButton").classList.toggle("btn-secondary", mode !== "tasks");
    document.getElementById("groupListButton").classList.toggle("btn-primary", mode === "groups");
    document.getElementById("groupListButton").classList.toggle("btn-secondary", mode !== "groups");
}


// Function to populate and show the Group Details Modal
function showGroupDetailsModal(groupName, tasks) {
    const groupNameElement = document.getElementById("groupName");
    const groupTasksList = document.getElementById("groupTasksList");

    // Populate group name
    groupNameElement.textContent = groupName;

    // Populate task list
    groupTasksList.innerHTML = ""; // Clear previous content
    tasks.forEach(task => {
        const taskItem = document.createElement("li");
        taskItem.className = "mb-2";
        taskItem.innerHTML = `
            <strong>${task.title}</strong>
            <p class="mb-0 text-muted">Due: ${new Date(task.due_date).toLocaleString()}</p>
            <p class="mb-0 text-muted">Load: ${task.estimated_load}</p>
        `;
        groupTasksList.appendChild(taskItem);
    });

    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById("groupDetailsModal"));
    modal.show();
}



    function showPieChart(mode) {
    currentMode = mode; // Remember the current mode
    const tasks = <?php echo json_encode($tasks); ?>;
    const ctx = document.getElementById("pieChart").getContext("2d");

    // Filter tasks to exclude completed ones
    const activeTasks = tasks.filter(task => parseInt(task.is_completed, 10) === 0);

    console.log("Active tasks for pie chart:", activeTasks); // Debugging active tasks

    let pieData;

    if (mode === 'tasks') {
        // Prepare data for tasks
        pieData = {
            labels: activeTasks.map(task => task.title),
            datasets: [{
                data: activeTasks.map(task => task.estimated_load),
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6610f2']
            }]
        };
    } else if (mode === 'groups') {
        // Prepare data for groups
        const groupData = activeTasks.reduce((acc, task) => {
            if (!acc[task.group_name]) {
                acc[task.group_name] = { group_id: task.group_id, load: 0 };
            }
            acc[task.group_name].load += task.estimated_load;
            return acc;
        }, {});

        console.log("Group data for pie chart:", groupData); // Debugging grouped data

        pieData = {
            labels: Object.keys(groupData),
            datasets: [{
                data: Object.values(groupData).map(group => group.load),
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#6610f2']
            }]
        };
    } else {
        console.error("Invalid mode provided for pie chart");
        return;
    }

    // Destroy previous chart instance if it exists
    if (pieChart) pieChart.destroy();

    // Create the new pie chart
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
        showListView('groups');

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
            showListView('tasks');
            showPieChart('tasks');
            showBubbleChart('tasks');
        });
        document.getElementById('groupListButton').addEventListener('click', () => {
            showListView('groups');
            showPieChart('groups');
            showBubbleChart('groups');
        });

        document.addEventListener('DOMContentLoaded', () => {
            showView('listView');
            showListView('tasks');
        });

        highlightOverdueTasks();
    });

</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/date-fns"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

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

    .btn-uniform {
        min-width: 120px; /* Set a uniform minimum width */
        text-align: center; /* Center align text */
    }

    @media (max-width: 576px) {
        .btn-uniform {
            min-width: 100px; /* Adjust size for smaller screens */
        }
    }

    .d-flex .btn {
        flex-grow: 1; /* Ensures buttons expand equally */
    }

</style>