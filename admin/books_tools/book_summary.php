<?php
include(__DIR__ . '/../includes/header.php');
include(__DIR__ . '/../includes/sidebar.php');
include '../db.php';

// Current year (default: now)
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Helper function
function getCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (int)$row['count'];
}

// 📊 Base stats
$totalBooks = getCount($conn, "SELECT COUNT(*) as count FROM books");
$uniqueTitles = getCount($conn, "SELECT COUNT(DISTINCT TITLE) as count FROM books");
$duplicateTitles = getCount($conn, "SELECT COUNT(*) AS count FROM (SELECT TITLE FROM books GROUP BY TITLE HAVING COUNT(*) > 1) AS duplicates");
$uniqueAuthors = getCount($conn, "SELECT COUNT(DISTINCT AUTHOR) as count FROM books");
$duplicateAuthors = getCount($conn, "SELECT COUNT(*) AS count FROM (SELECT AUTHOR FROM books GROUP BY AUTHOR HAVING COUNT(*) > 1) AS duplicates");
$uniqueCategories = getCount($conn, "SELECT COUNT(DISTINCT General_Category) as count FROM books");

// 🆕 This month’s contributions
$booksThisMonth = getCount($conn, "
    SELECT COUNT(*) as count FROM books
    WHERE YEAR(date_added) = YEAR(CURRENT_DATE)
    AND MONTH(date_added) = MONTH(CURRENT_DATE)
");
$totalBeforeMonth = $totalBooks - $booksThisMonth;

$uniqueTitlesThisMonth = getCount($conn, "
    SELECT COUNT(DISTINCT TITLE) as count FROM books
    WHERE YEAR(date_added) = YEAR(CURRENT_DATE)
    AND MONTH(date_added) = MONTH(CURRENT_DATE)
");
$uniqueTitlesBeforeMonth = $uniqueTitles - $uniqueTitlesThisMonth;

$duplicateTitlesThisMonth = getCount($conn, "
    SELECT COUNT(*) as count FROM (
        SELECT TITLE FROM books 
        WHERE YEAR(date_added) = YEAR(CURRENT_DATE)
        AND MONTH(date_added) = MONTH(CURRENT_DATE)
        GROUP BY TITLE HAVING COUNT(*) > 1
    ) as d
");
$duplicateTitlesBeforeMonth = $duplicateTitles - $duplicateTitlesThisMonth;

$uniqueAuthorsThisMonth = getCount($conn, "
    SELECT COUNT(DISTINCT AUTHOR) as count FROM books
    WHERE YEAR(date_added) = YEAR(CURRENT_DATE)
    AND MONTH(date_added) = MONTH(CURRENT_DATE)
");
$uniqueAuthorsBeforeMonth = $uniqueAuthors - $uniqueAuthorsThisMonth;

$duplicateAuthorsThisMonth = getCount($conn, "
    SELECT COUNT(*) as count FROM (
        SELECT AUTHOR FROM books 
        WHERE YEAR(date_added) = YEAR(CURRENT_DATE)
        AND MONTH(date_added) = MONTH(CURRENT_DATE)
        GROUP BY AUTHOR HAVING COUNT(*) > 1
    ) as d
");
$duplicateAuthorsBeforeMonth = $duplicateAuthors - $duplicateAuthorsThisMonth;

$uniqueCategoriesThisMonth = getCount($conn, "
    SELECT COUNT(DISTINCT General_Category) as count FROM books
    WHERE YEAR(date_added) = YEAR(CURRENT_DATE)
    AND MONTH(date_added) = MONTH(CURRENT_DATE)
");
$uniqueCategoriesBeforeMonth = $uniqueCategories - $uniqueCategoriesThisMonth;

// 🆕 Books since last report
$result = mysqli_query($conn, "SELECT last_generated FROM report_tracker ORDER BY id DESC LIMIT 1");
$last_generated = mysqli_fetch_assoc($result)['last_generated'] ?? '2000-01-01';
$sql_new = "SELECT COUNT(*) as count FROM books WHERE date_added > '$last_generated'";
$booksSinceReport = mysqli_fetch_assoc(mysqli_query($conn, $sql_new))['count'];

// 📊 Monthly data (added + cumulative)
$monthlyData = [];
$runningTotal = 0;
for ($m = 1; $m <= 12; $m++) {
    $sql = "SELECT COUNT(*) as total FROM books 
            WHERE YEAR(date_added) = $selectedYear 
            AND MONTH(date_added) = $m";
    $res = mysqli_query($conn, $sql);
    $count = (int) mysqli_fetch_assoc($res)['total'];

    $runningTotal += $count;
    $monthlyData[] = [
        'month' => date('F', mktime(0, 0, 0, $m, 1)),
        'added' => $count,
        'cumulative' => $runningTotal
    ];
}

// 🏷️ Categories
$categoryData = [];
$res = mysqli_query($conn, "SELECT General_Category, COUNT(*) as total FROM books GROUP BY General_Category");
while ($row = mysqli_fetch_assoc($res)) {
    $categoryData[] = $row;
}

// 📅 Year dropdown
$years = [];
$res = mysqli_query($conn, "SELECT DISTINCT YEAR(date_added) as y FROM books ORDER BY y ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $years[] = $row['y'];
}
?>

<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300 p-6 bg-gray-100">
    <div class="charts-row grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- 📈 Books Added Per Month -->
        <div class="chart-card bg-white rounded-xl shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">📈 Books Added Per Month</h3>
                <!-- Year selector -->
                <form method="get">
                    <select name="year" onchange="this.form.submit()" class="border rounded px-2 py-1 text-sm">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="chart-container h-72 mb-4">
                <canvas id="monthlyChart"></canvas>
            </div>

            <!-- 📋 Library Summary -->
            <h4 class="text-gray-700 font-medium mb-2 flex justify-between items-center">
                Library Summary
                <!-- 🔄 Refresh button -->
                <form method="get" class="inline">
                    <button type="submit" 
                        class="ml-2 bg-green-600 text-white px-2 py-1 rounded text-xs shadow hover:bg-green-700">
                        🔄 Refresh
                    </button>
                </form>
            </h4>

            <ul class="summary-list text-sm text-gray-600 divide-y divide-gray-300">
                <li class="py-1">📘 Total Books: 
                    <?= $totalBeforeMonth ?>
                    <?php if ($booksThisMonth > 0): ?>
                        <span class="text-green-600 font-semibold" title="<?= $booksThisMonth ?> books added in <?= date('F') ?>">
                            +<?= $booksThisMonth ?>
                        </span>
                    <?php endif; ?>
                </li>
                <li class="py-1">📖 Unique Titles: 
                    <?= $uniqueTitlesBeforeMonth ?>
                    <?php if ($uniqueTitlesThisMonth > 0): ?>
                        <span class="text-green-600 font-semibold" title="<?= $uniqueTitlesThisMonth ?> new unique titles in <?= date('F') ?>">
                            +<?= $uniqueTitlesThisMonth ?>
                        </span>
                    <?php endif; ?>
                </li>
                <li class="py-1">📄 Duplicate Titles: 
                    <?= $duplicateTitlesBeforeMonth ?>
                    <?php if ($duplicateTitlesThisMonth > 0): ?>
                        <span class="text-green-600 font-semibold" title="<?= $duplicateTitlesThisMonth ?> new duplicate titles in <?= date('F') ?>">
                            +<?= $duplicateTitlesThisMonth ?>
                        </span>
                    <?php endif; ?>
                </li>
                <li class="py-1">👤 Unique Authors: 
                    <?= $uniqueAuthorsBeforeMonth ?>
                    <?php if ($uniqueAuthorsThisMonth > 0): ?>
                        <span class="text-green-600 font-semibold" title="<?= $uniqueAuthorsThisMonth ?> new unique authors in <?= date('F') ?>">
                            +<?= $uniqueAuthorsThisMonth ?>
                        </span>
                    <?php endif; ?>
                </li>
                <li class="py-1">👥 Duplicate Authors: 
                    <?= $duplicateAuthorsBeforeMonth ?>
                    <?php if ($duplicateAuthorsThisMonth > 0): ?>
                        <span class="text-green-600 font-semibold" title="<?= $duplicateAuthorsThisMonth ?> new duplicate authors in <?= date('F') ?>">
                            +<?= $duplicateAuthorsThisMonth ?>
                        </span>
                    <?php endif; ?>
                </li>
                <li class="py-1">🏷️ General Categories: 
                    <?= $uniqueCategoriesBeforeMonth ?>
                    <?php if ($uniqueCategoriesThisMonth > 0): ?>
                        <span class="text-green-600 font-semibold" title="<?= $uniqueCategoriesThisMonth ?> new categories in <?= date('F') ?>">
                            +<?= $uniqueCategoriesThisMonth ?>
                        </span>
                    <?php endif; ?>
                </li>
                <li class="py-1">🆕 Books Added Since Last Report: <?= $booksSinceReport ?></li>
            </ul>
             <!-- 📑 Generate Report Section -->
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
            <ul class="pie-legend text-sm text-gray-600 space-y-2" id="categoryLegend"></ul>
        </div>
    </div>
</div>

<!-- Pass PHP data to JS -->
<script>
    const monthlyLabels = <?= json_encode(array_column($monthlyData, 'month')) ?>;
    const monthlyAdded = <?= json_encode(array_column($monthlyData, 'added')) ?>;
    const monthlyTotals = <?= json_encode(array_column($monthlyData, 'cumulative')) ?>;

    const categoryLabels = <?= json_encode(array_column($categoryData, 'General_Category')) ?>;
    const categoryTotals = <?= json_encode(array_column($categoryData, 'total')) ?>;
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="book_report_chart.js"></script>


<script>
    // Report dropdown toggle
    document.getElementById("reportBtn").addEventListener("click", function(e) {
        e.preventDefault();
        const dropdown = document.getElementById("reportDropdown");
        dropdown.classList.toggle("hidden");
    });

    // Close dropdown if clicking outside
    window.addEventListener("click", function(e) {
        if (!e.target.closest("#reportBtn") && !e.target.closest("#reportDropdown")) {
            document.getElementById("reportDropdown").classList.add("hidden");
        }
    });
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
