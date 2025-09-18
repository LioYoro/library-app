<?php
include(__DIR__ . '/../includes/header.php');
include(__DIR__ . '/../includes/sidebar.php');
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

<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300 p-6 bg-gray-100">
    <div class="charts-row grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 📈 Books Added Per Month -->
        <div class="chart-card bg-white rounded-xl shadow p-6">
            <h3 class="text-center text-lg font-semibold text-gray-800 mb-4">📈 Books Added Per Month</h3>
            <div class="chart-container h-72 mb-4">
                <canvas id="monthlyChart"></canvas>
            </div>
            <h4 class="text-gray-700 font-medium mb-2">Library Summary</h4>
            <ul class="summary-list text-sm text-gray-600 divide-y divide-gray-300">
                <li class="py-1">📘 Total Books: <?= $totalBooks ?></li>
                <li class="py-1">📖 Unique Titles: <?= $uniqueTitles ?></li>
                <li class="py-1">📄 Duplicate Titles: <?= $duplicateTitles ?></li>
                <li class="py-1">👤 Unique Authors: <?= $uniqueAuthors ?></li>
                <li class="py-1">👥 Duplicate Authors: <?= $duplicateAuthors ?></li>
                <li class="py-1">🏷️ General Categories: <?= $uniqueCategories ?></li>
                <li class="py-1">📅 Books Added Last Month: <?= $booksLastMonth ?></li>
                <li class="py-1">🆕 Books Added Since Last Report: <?= $booksSinceReport ?></li>
            </ul>
            
            <!-- Generate Report Section -->
            <div class="mt-4 relative inline-block">
                <button id="reportBtn" type="button" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md shadow hover:bg-blue-700 text-sm">
                    GENERATE BOOKS REPORT ▼
                </button>
                <div id="reportDropdown" 
                    class="hidden absolute bg-white border border-gray-200 rounded-md mt-1 shadow z-10 min-w-[150px]">
                    <a href="generate_report.php?format=pdf" 
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">📄 Download PDF</a>
                    <a href="generate_report.php?format=excel" 
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">📊 Download Excel</a>
                </div>
            </div>
        </div>

        <!-- 🥧 Books per Category -->
        <div class="chart-card bg-white rounded-xl shadow p-6">
            <h3 class="text-center text-lg font-semibold text-gray-800 mb-4">🥧 Books per Category</h3>
            <div class="chart-container h-72 mb-4">
                <canvas id="categoryChart"></canvas>
            </div>
            <ul class="pie-legend text-sm text-gray-600 space-y-2" id="categoryLegend">
                <!-- Legend populated by JS -->
            </ul>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
                '#FF6384', '#36A2EB', '#FFCE56',
                '#4BC0C0', '#9966FF', '#FF9F40',
                '#FF8C69', '#C9CBCF', '#8FBC8F',
                '#DDA0DD', '#F0E68C', '#87CEEB'
            ]
        }]
    };

    // Line chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: monthlyChartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.1)' } },
                x: { grid: { color: 'rgba(0,0,0,0.1)' } }
            }
        }
    });

    // Pie chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: categoryChartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

    // Generate custom legend
    const legendContainer = document.getElementById('categoryLegend');
    categoryChartData.labels.forEach((label, index) => {
        const li = document.createElement('li');
        li.style.display = "flex";
        li.style.alignItems = "center";
        li.style.gap = "6px";

        const colorBox = document.createElement('div');
        colorBox.style.width = '14px';
        colorBox.style.height = '14px';
        colorBox.style.borderRadius = '3px';
        colorBox.style.backgroundColor = categoryChartData.datasets[0].backgroundColor[index];

        li.appendChild(colorBox);
        li.appendChild(document.createTextNode(` ${label}: ${categoryChartData.datasets[0].data[index]} books`));
        legendContainer.appendChild(li);
    });

    // Report dropdown
    document.getElementById("reportBtn").addEventListener("click", function(e) {
        e.preventDefault();
        const dropdown = document.getElementById("reportDropdown");
        dropdown.classList.toggle("hidden");
    });

    // Close dropdown when clicking outside
    window.addEventListener("click", function(e) {
        if (!e.target.closest("#reportBtn") && !e.target.closest("#reportDropdown")) {
            document.getElementById("reportDropdown").classList.add("hidden");
        }
    });
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
