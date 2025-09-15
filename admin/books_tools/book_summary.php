<?php
include '../db.php'; // Adjust path if needed

function getCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Current date for "As of"
$reportDate = date('F d, Y');

// 📊 Summary queries
$totalBooks = getCount($conn, "SELECT COUNT(*) as count FROM books");
$uniqueTitles = getCount($conn, "SELECT COUNT(DISTINCT TITLE) as count FROM books");
$duplicateTitles = getCount($conn, "SELECT COUNT(*) AS count FROM (SELECT TITLE FROM books GROUP BY TITLE HAVING COUNT(*) > 1) AS duplicates");
$uniqueAuthors = getCount($conn, "SELECT COUNT(DISTINCT AUTHOR) as count FROM books");
$duplicateAuthors = getCount($conn, "SELECT COUNT(*) AS count FROM (SELECT AUTHOR FROM books GROUP BY AUTHOR HAVING COUNT(*) > 1) AS duplicates");
$uniqueCategories = getCount($conn, "SELECT COUNT(DISTINCT General_Category) as count FROM books");
$booksLastMonth = getCount($conn, "
    SELECT COUNT(*) as count FROM books
    WHERE DATE_ADDED >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01')
      AND DATE_ADDED < DATE_FORMAT(NOW(), '%Y-%m-01')
");

// 🆕 Books added since last report
$result = mysqli_query($conn, "SELECT last_generated FROM report_tracker ORDER BY id DESC LIMIT 1");
$last_generated = mysqli_fetch_assoc($result)['last_generated'] ?? '2000-01-01';
$sql_new = "SELECT COUNT(*) as count FROM books WHERE date_added > '$last_generated'";
$booksSinceReport = mysqli_fetch_assoc(mysqli_query($conn, $sql_new))['count'];

// 📊 Data for charts
$categoryData = [];
$res = mysqli_query($conn, "SELECT General_Category, COUNT(*) as total FROM books GROUP BY General_Category");
while ($row = mysqli_fetch_assoc($res)) {
    $categoryData[] = $row;
}

$monthlyData = [];
$res = mysqli_query($conn, "
    SELECT DATE_FORMAT(date_added, '%Y-%m') as month, COUNT(*) as total
    FROM books
    GROUP BY DATE_FORMAT(date_added, '%Y-%m')
    ORDER BY month ASC
");
while ($row = mysqli_fetch_assoc($res)) {
    $monthlyData[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
            color: #333;
        }

        .charts-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            flex: 1;
            min-height: 500px;
        }

        .chart-card h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 1.2em;
            font-weight: 600;
            text-align: center;
        }

        .chart-card h4 {
            margin: 20px 0 10px 0;
            color: #34495e;
            font-size: 1em;
            font-weight: 500;
        }

        .chart-container {
            width: 100%;
            height: 300px;
            margin-bottom: 20px;
        }

        canvas {
            max-width: 100%;
            max-height: 100%;
        }

        .summary-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .summary-list li {
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
            font-size: 0.9em;
            color: #555;
        }

        .summary-list li:last-child {
            border-bottom: none;
        }

        .pie-legend {
            list-style: none;
            padding: 0;
            margin: 20px 0 0 0;
        }

        .pie-legend li {
            padding: 8px 0;
            font-size: 0.9em;
            display: flex;
            align-items: center;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 8px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .charts-row {
                flex-direction: column;
            }
            
            body {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- 📈 Books Added Per Month and 🥧 Books per Category (side by side) -->
    <div class="charts-row">
        <div class="chart-card line-card">
            <h3>📈 Books Added Per Month</h3>
            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>
            <h4>Library Summary</h4>
            <ul class="summary-list">
                <li>📘 Total Books: <?= $totalBooks ?></li>
                <li>📖 Unique Titles: <?= $uniqueTitles ?></li>
                <li>📄 Duplicate Titles: <?= $duplicateTitles ?></li>
                <li>👤 Unique Authors: <?= $uniqueAuthors ?></li>
                <li>👥 Duplicate Authors: <?= $duplicateAuthors ?></li>
                <li>🏷️ General Categories: <?= $uniqueCategories ?></li>
                <li>📅 Books Added Last Month: <?= $booksLastMonth ?></li>
                <li>🆕 Books Added Since Last Report: <?= $booksSinceReport ?></li>
            </ul>
            
            <!-- Generate Report Section -->
            <div style="margin-top: 20px;">
                <!-- Generate Report Button with Dropdown -->
                <div style="position: relative; display: inline-block;">
                    <button id="reportBtn" type="button" 
                        style="background-color: #007bff; color: white; padding: 10px 15px; border: none; cursor: pointer; border-radius: 5px; font-size: 0.9em;">
                        GENERATE BOOKS REPORT ▼
                    </button>
                    <div id="reportDropdown" 
                        style="display: none; position: absolute; background: white; border: 1px solid #ccc; border-radius: 5px; margin-top: 5px; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-width: 150px;">
                        <a href="generate_report.php?format=pdf" 
                           style="display: block; padding: 8px 12px; text-decoration: none; color: black; border-bottom: 1px solid #eee;">📄 Download PDF</a>
                        <a href="generate_report.php?format=excel" 
                           style="display: block; padding: 8px 12px; text-decoration: none; color: black;">📊 Download Excel</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="chart-card pie-card">
            <h3>🥧 Books per Category</h3>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
            <ul class="pie-legend" id="categoryLegend">
                <!-- Legend will be populated by JavaScript -->
            </ul>
        </div>
    </div>

    <script>
        // Real data from PHP database
        const monthlyChartData = {
            labels: <?= json_encode(array_column($monthlyData, 'month')) ?>,
            datasets: [{
                label: 'Books Added',
                data: <?= json_encode(array_column($monthlyData, 'total')) ?>,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        const categoryChartData = {
            labels: <?= json_encode(array_column($categoryData, 'General_Category')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($categoryData, 'total')) ?>,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#FF8C69',
                    '#C9CBCF',
                    '#8FBC8F',
                    '#DDA0DD',
                    '#F0E68C',
                    '#87CEEB'
                ]
            }]
        };

        // Create line chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: monthlyChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                }
            }
        });

        // Create pie chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'pie',
            data: categoryChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Generate custom legend with real data
        const legendContainer = document.getElementById('categoryLegend');
        categoryChartData.labels.forEach((label, index) => {
            const li = document.createElement('li');
            const colorBox = document.createElement('div');
            colorBox.className = 'legend-color';
            colorBox.style.backgroundColor = categoryChartData.datasets[0].backgroundColor[index];
            
            li.appendChild(colorBox);
            li.appendChild(document.createTextNode(`${label}: ${categoryChartData.datasets[0].data[index]} books`));
            legendContainer.appendChild(li);
        });

        // Report dropdown functionality
        document.getElementById("reportBtn").addEventListener("click", function(e) {
            e.preventDefault();
            const dropdown = document.getElementById("reportDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        });

        // Close dropdown when clicking outside
        window.addEventListener("click", function(e) {
            if (!e.target.closest("#reportBtn") && !e.target.closest("#reportDropdown")) {
                document.getElementById("reportDropdown").style.display = "none";
            }
        });
    </script>
</body>
</html>