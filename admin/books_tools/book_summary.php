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
    SELECT COUNT(DISTINCT General_Category) as count 
    FROM books b1
    WHERE YEAR(b1.date_added) = YEAR(CURRENT_DATE)
      AND MONTH(b1.date_added) = MONTH(CURRENT_DATE)
      AND NOT EXISTS (
          SELECT 1 FROM books b2
          WHERE b2.General_Category = b1.General_Category
          AND b2.date_added < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
      )
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
                <form method="get" class="inline">
                    <button type="submit" 
                        class="ml-2 bg-green-600 text-white px-2 py-1 rounded text-xs shadow hover:bg-green-700">
                        🔄 Refresh
                    </button>
                </form>
            </h4>

            <ul class="summary-list text-sm text-gray-600 divide-y divide-gray-300">
                <li class="py-1">📘 Total Books: <?= $totalBeforeMonth ?> <?php if ($booksThisMonth > 0): ?><span class="text-green-600 font-semibold">+<?= $booksThisMonth ?></span><?php endif; ?></li>
                <li class="py-1">📖 Unique Titles: <?= $uniqueTitlesBeforeMonth ?> <?php if ($uniqueTitlesThisMonth > 0): ?><span class="text-green-600 font-semibold">+<?= $uniqueTitlesThisMonth ?></span><?php endif; ?></li>
                <li class="py-1">📄 Duplicate Titles: <?= $duplicateTitlesBeforeMonth ?> <?php if ($duplicateTitlesThisMonth > 0): ?><span class="text-green-600 font-semibold">+<?= $duplicateTitlesThisMonth ?></span><?php endif; ?></li>
                <li class="py-1">👤 Unique Authors: <?= $uniqueAuthorsBeforeMonth ?> <?php if ($uniqueAuthorsThisMonth > 0): ?><span class="text-green-600 font-semibold">+<?= $uniqueAuthorsThisMonth ?></span><?php endif; ?></li>
                <li class="py-1">👥 Duplicate Authors: <?= $duplicateAuthorsBeforeMonth ?> <?php if ($duplicateAuthorsThisMonth > 0): ?><span class="text-green-600 font-semibold">+<?= $duplicateAuthorsThisMonth ?></span><?php endif; ?></li>
                <li class="py-1">🏷️ General Categories: <?= $uniqueCategoriesBeforeMonth ?> <?php if ($uniqueCategoriesThisMonth > 0): ?><span class="text-green-600 font-semibold">+<?= $uniqueCategoriesThisMonth ?></span><?php endif; ?></li>
                <li class="py-1">🆕 Books Added Since Last Report: <?= $booksSinceReport ?></li>
            </ul>

            <div class="mt-4">
                <button id="reportBtn" type="button" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md shadow hover:bg-blue-700 text-sm">
                    GENERATE BOOKS REPORT
                </button>
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

<!-- 📌 Modal Popup (2-step) -->
<div id="reportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
        <h2 class="text-lg font-semibold mb-4">📑 Generate Report</h2>

        <!-- Step 1 -->
        <div id="step1">
            <label class="block mb-2 font-medium">Choose Report Type:</label>
            <div class="space-y-2 mb-4">
                <button class="report-type w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded" data-type="monthly">📅 Monthly Report</button>
                <button class="report-type w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded" data-type="yearly">📆 Yearly Report</button>
            </div>
        </div>

        <!-- Step 2 -->
        <div id="step2" class="hidden">
            <h3 class="font-semibold mb-2">🔎 Preview</h3>
            <p id="previewText" class="text-sm text-gray-700 mb-3"></p>
            <div class="flex gap-2 mb-3">
                <button id="backBtn" class="flex-1 text-center bg-gray-400 hover:bg-gray-500 text-white py-2 rounded">⬅ Back</button>
            </div>
            <div class="flex gap-2">
                <a id="confirmPdf" href="#" class="flex-1 text-center bg-red-600 hover:bg-red-700 text-white py-2 rounded">Download PDF</a>
                <a id="confirmExcel" href="#" class="flex-1 text-center bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded">Download Excel</a>
            </div>
        </div>

        <!-- Close Button -->
        <button id="closeModal" class="absolute top-2 right-2 text-gray-500 hover:text-black">&times;</button>
    </div>
</div>

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
const reportBtn = document.getElementById("reportBtn");
const reportModal = document.getElementById("reportModal");
const closeModal = document.getElementById("closeModal");
const step1 = document.getElementById("step1");
const step2 = document.getElementById("step2");
const previewText = document.getElementById("previewText");
const confirmPdf = document.getElementById("confirmPdf");
const confirmExcel = document.getElementById("confirmExcel");
const backBtn = document.getElementById("backBtn");
let selectedType = null;

// Open
reportBtn.addEventListener("click", () => {
    reportModal.classList.remove("hidden");
    step1.classList.remove("hidden");
    step2.classList.add("hidden");
});

// Close
closeModal.addEventListener("click", () => {
    reportModal.classList.add("hidden");
    step1.classList.remove("hidden");
    step2.classList.add("hidden");
    selectedType = null;
});

// Select type
document.querySelectorAll(".report-type").forEach(btn => {
    btn.addEventListener("click", () => {
        selectedType = btn.dataset.type;
        if (selectedType === "monthly") {
            previewText.textContent = "📅 Monthly report will include: books added this month, new unique titles, new authors, categories, and cumulative totals.";
        } else {
            previewText.textContent = "📆 Yearly report will include: books added since January, unique titles, authors, categories, and overall statistics.";
        }
        step1.classList.add("hidden");
        step2.classList.remove("hidden");

        confirmPdf.href = "generate_report.php?format=pdf&type=" + selectedType;
        confirmExcel.href = "generate_report.php?format=excel&type=" + selectedType;
    });
});

// Back
backBtn.addEventListener("click", () => {
    step1.classList.remove("hidden");
    step2.classList.add("hidden");
});
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>
