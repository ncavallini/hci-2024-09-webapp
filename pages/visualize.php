<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Graphics Example</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .disumano {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .chart-container {
            width: 45%;
            min-width: 300px;
            display: none; /* Initially hidden */
        }

        #taskList {
            display: block; /* Default to visible */
        }

        .view-selector {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        padding: 10px;
        border: 2px solid black;
        border-radius: 5px;
        margin-bottom: 20px;
        background-color: lightgray;
        width: 90%;
        max-width: 600px;
    }

    .view-selector button {
        flex: 1 1 calc(25% - 10px);
        margin: 5px;
        padding: 10px 20px;
        border: none;
        background-color: #007bff;
        color: white;
        cursor: pointer;
        border-radius: 5px;
        text-align: center;
    }

    .view-selector button:hover {
        background-color: #0056b3;
    }

        .task-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            width: 100%;
            background: #f9f9f9;
            border-radius: 5px;
            padding: 10px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .mental-load-bar {
            width: 70%;
            height: 10px;
            margin-left: 10px;
            background-color: lightgray;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .mental-load-bar span {
            display: block;
            height: 100%;
            background-color: turquoise;
        }

        .task-name {
            width: 30%;
        }
    </style>
</head>
<h1>Various Visualizations</h1>
<body>
    <div class="disumano">
        <!-- View Selector -->
        <div class="view-selector">
            <button onclick="showView('taskList')">Task List</button>
            <button onclick="showView('lineChart')">Line Chart</button>
            <button onclick="showView('bubbleChart')">Bubble Chart</button>
            <button onclick="showView('pieChart')">Pie Chart</button>
        </div>

        <!-- List Example -->
        <div id="taskList" class="chart-container">
            <h2>Task List</h2>
            <div></div>
        </div>

        <!-- Line Graph Example -->
        <div id="lineChart" class="chart-container">
            <h2>Line Chart</h2>
            <canvas></canvas>
        </div>

        <!-- Bubble Chart Example -->
        <div id="bubbleChart" class="chart-container">
            <h2>Bubble Chart</h2>
            <canvas></canvas>
        </div>

        <!-- Pie Chart Example -->
        <div id="pieChart" class="chart-container">
            <h2>Pie Chart</h2>
            <canvas></canvas>
        </div>
    </div>

    <script>
        function showView(viewId) {
            // Hide all chart containers
            document.querySelectorAll('.chart-container').forEach(container => {
                container.style.display = 'none';
            });

            // Show the selected view
            document.getElementById(viewId).style.display = 'block';
        }

        // Default view
        showView('taskList');

        // Task List Logic
        const tasks = [
            { name: "Task 1: Complete the report", mentalLoad: 75 },
            { name: "Task 2: Review the project plan", mentalLoad: 50 },
            { name: "Task 3: Team meeting at 3 PM", mentalLoad: 30 },
            { name: "Task 4: Prepare presentation", mentalLoad: 20 }
        ];

        // Sort tasks in ascending order of mental load
        tasks.sort((a, b) => - a.mentalLoad + b.mentalLoad);

        const taskListContainer = document.getElementById("taskList").querySelector("div");
        tasks.forEach(task => {
            const taskItem = document.createElement("div");
            taskItem.className = "task-item";

            // Task name
            const taskName = document.createElement("div");
            taskName.className = "task-name";
            taskName.textContent = task.name;

            // Mental load bar
            const loadBar = document.createElement("div");
            loadBar.className = "mental-load-bar";

            const loadFill = document.createElement("span");
            loadFill.style.width = task.mentalLoad + "%";
            loadBar.appendChild(loadFill);

            // Append elements
            taskItem.appendChild(taskName);
            taskItem.appendChild(loadBar);

            // Append to task list
            taskListContainer.appendChild(taskItem);
        });

        // Chart.js Setup (same as original code)
        const charts = {
            lineChart: new Chart(document.getElementById('lineChart').querySelector('canvas'), {
                type: 'line',
                data: { labels: ['January', 'February'], datasets: [{ data: [10, 20] }] }
            }),
            bubbleChart: new Chart(document.getElementById('bubbleChart').querySelector('canvas'), {
                type: 'bubble',
                data: { datasets: [{ data: [{ x: 10, y: 20, r: 10 }] }] }
            }),
            pieChart: new Chart(document.getElementById('pieChart').querySelector('canvas'), {
                type: 'pie',
                data: { labels: ['Stress', 'Physical'], datasets: [{ data: [50, 50] }] }
            })
        };
    </script>
</body> 
