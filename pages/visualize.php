<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Graphics Example</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
       /* body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
            */
        /*.container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
            */
        .chart-container {
            width: 45%;
            min-width: 300px;
        }
       /* ul {
            list-style-type: disc;
            padding-left: 20px;
        }
            */
        .task-item {
            margin-bottom: 20px;
            position: relative;
        }
        .mental-load-bar {
            width: 100%;
            height: 5px;
            background-color: lightgray;
            position: absolute;
            top: -10px;
        }
        .mental-load-bar span {
            display: block;
            height: 100%;
            background-color: red;
        }
        .task {
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>Various Visualizations</h1>
    <div class="container">
        <!-- List Example -->
        <div class="chart-container">
            <h2>Task List</h2>
            <div id="taskList"></div>
        </div>

        <!-- Line Graph Example -->
        <div class="chart-container">
            <h2>Line Chart</h2>
            <canvas id="lineChart"></canvas>
        </div>

        <!-- Bubble Chart Example -->
        <div class="chart-container">
            <h2>Bubble Chart</h2>
            <canvas id="bubbleChart"></canvas>
        </div>

        <!-- Pie Chart Example -->
        <div class="chart-container">
            <h2>Pie Chart</h2>
            <canvas id="pieChart"></canvas>
        </div>
    </div>

    <script>
        // Task List with Mental Load
        const tasks = [
            { name: "Task 1: Complete the report", mentalLoad: 75 },
            { name: "Task 2: Review the project plan", mentalLoad: 50 },
            { name: "Task 3: Team meeting at 3 PM", mentalLoad: 30 }
        ];

        // Sort tasks by mental load in descending order
        tasks.sort((a, b) => b.mentalLoad - a.mentalLoad);

        const taskList = document.getElementById("taskList");

        tasks.forEach(task => {
            const taskItem = document.createElement("div");
            taskItem.className = "task-item";

            // Mental load bar
            const loadBar = document.createElement("div");
            loadBar.className = "mental-load-bar";

            const loadFill = document.createElement("span");
            loadFill.style.width = task.mentalLoad + "%";
            loadBar.appendChild(loadFill);

            // Task text
            const taskText = document.createElement("div");
            taskText.className = "task";
            taskText.textContent = `${task.name} (Mental Load: ${task.mentalLoad}%)`;

            // Append to task item
            taskItem.appendChild(loadBar);
            taskItem.appendChild(taskText);

            // Append task item to the task list
            taskList.appendChild(taskItem);
        });

        // Line Chart
        const lineChartCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineChartCtx, {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June'],
                datasets: [{
                    label: 'Sales Over Time',
                    data: [10, 20, 15, 30, 25, 40],
                    borderColor: 'blue',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                }
            }
        });

        // Bubble Chart
        const bubbleChartCtx = document.getElementById('bubbleChart').getContext('2d');
        new Chart(bubbleChartCtx, {
            type: 'bubble',
            data: {
                datasets: [{
                    label: 'Bubble Dataset',
                    data: [
                        { x: 10, y: 20, r: 15 },
                        { x: 15, y: 10, r: 10 },
                        { x: 20, y: 30, r: 20 }
                    ],
                    backgroundColor: 'rgba(255, 99, 132, 0.5)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                }
            }
        });

        // Pie Chart
        const pieChartCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieChartCtx, {
            type: 'pie',
            data: {
                labels: ['Stress', 'Physical', 'Mental'],
                datasets: [{
                    data: [40, 30, 30],
                    backgroundColor: ['red', 'blue', 'green']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                }
            }
        });
    </script>
</body>