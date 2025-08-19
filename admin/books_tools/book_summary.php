<?php
include '../db.php'; // Adjust path if needed

function getCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Current date for “As of”
$reportDate = date('F d, Y'); // e.g., August 19, 2025

// 📊 Summary queries
$totalBooks = getCount($conn, "SELECT COUNT(*) as count FROM books");
$uniqueTitles = getCount($conn, "SELECT COUNT(DISTINCT TITLE) as count FROM books");
$duplicateTitles = getCount($conn, "SELECT COUNT(*) AS count FROM (SELECT TITLE FROM books GROUP BY TITLE HAVING COUNT(*) > 1) AS duplicates");
$uniqueAuthors = getCount($conn, "SELECT COUNT(DISTINCT AUTHOR) as count FROM books");
$duplicateAuthors = getCount($conn, "SELECT COUNT(*) AS count FROM (SELECT AUTHOR FROM books GROUP BY AUTHOR HAVING COUNT(*) > 1) AS duplicates");
$uniqueCategories = getCount($conn, "SELECT COUNT(DISTINCT General_Category) as count FROM books");
$uniqueSubCategories = getCount($conn, "SELECT COUNT(DISTINCT Sub_Category) as count FROM books");
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
  <title>Book Summary</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial, sans-serif; padding: 2rem; }
    .summary-box {
      background: #f8f9fa; border: 1px solid #ddd; padding: 10px 15px;
      border-radius: 8px; min-width: 200px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .btn {
      padding: 10px 20px; background-color: #007BFF; color: white;
      border: none; border-radius: 4px; cursor: pointer; text-decoration: none;
    }
    .btn:hover { background-color: #0056b3; }
    .summary-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
    canvas { margin: 20px 0; max-width: 800px; }
  </style>
</head>
<body>

  <h1>📚 Book Summary</h1>
  <div style="margin-bottom:10px; font-size:0.9em; color:#555;">
      🔒 <strong>As of:</strong> <?= $reportDate ?>
  </div>

  <a href="../books.php" class="btn">BACK</a>
  
  <div class="summary-container">
    <div class="summary-box">📘 <strong>Total Books:</strong> <?= $totalBooks ?></div>
    <div class="summary-box">📖 <strong>Unique Titles:</strong> <?= $uniqueTitles ?></div>
    <div class="summary-box">📄 <strong>Duplicate Titles:</strong> <?= $duplicateTitles ?></div>
    <div class="summary-box">👤 <strong>Unique Authors:</strong> <?= $uniqueAuthors ?></div>
    <div class="summary-box">👥 <strong>Duplicate Authors:</strong> <?= $duplicateAuthors ?></div>
    <div class="summary-box">🏷️ <strong>General Categories:</strong> <?= $uniqueCategories ?></div>
    <div class="summary-box">🗂️ <strong>Subcategories:</strong> <?= $uniqueSubCategories ?></div>
    <div class="summary-box">📅 <strong>Books Added Last Month:</strong> <?= $booksLastMonth ?></div>
    <div class="summary-box">🆕 <strong>Books Added Since Last Report:</strong> <?= $booksSinceReport ?></div>
  </div>

<!-- Generate Report Button with Dropdown -->
<div style="position: relative; display: inline-block;">
    <button id="reportBtn" type="button" 
        style="background-color: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer;">
        GENERATE BOOKS REPORT ▼
    </button>
    <div id="reportDropdown" 
        style="display: none; position: absolute; background: white; border: 1px solid #ccc; margin-top: 5px; z-index: 1000;">
        <a href="generate_report.php?format=pdf" 
           style="display: block; padding: 8px 12px; text-decoration: none; color: black;">📄 Download PDF</a>
        <a href="generate_report.php?format=excel" 
           style="display: block; padding: 8px 12px; text-decoration: none; color: black;">📊 Download Excel</a>
    </div>
</div>

<!-- Unified Full Report Button -->
<div style="position: relative; display: inline-block; margin-left:10px;">
    <button id="fullReportBtn" type="button"
        style="background-color: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer;">
        UNIFIED FULL REPORT WITH BOOK RESERVATION DATA ▼
    </button>
    <div id="fullReportDropdown"
        style="display: none; position: absolute; background: white; border: 1px solid #ccc; margin-top: 5px; z-index: 1000;">
        <a href="full_report.php?format=pdf"
           style="display: block; padding: 8px 12px; text-decoration: none; color: black;">📄 Download PDF</a>
        <a href="full_report.php?format=excel"
           style="display: block; padding: 8px 12px; text-decoration: none; color: black;">📊 Download Excel</a>
    </div>
</div>

<script>
document.getElementById("fullReportBtn").addEventListener("click", function(e){
    e.preventDefault();
    const dropdown = document.getElementById("fullReportDropdown");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
});
window.addEventListener("click", function(e){
    if(!e.target.closest("#fullReportBtn") && !e.target.closest("#fullReportDropdown")){
        document.getElementById("fullReportDropdown").style.display = "none";
    }
});
</script>


<script>
document.getElementById("reportBtn").addEventListener("click", function(e) {
    e.preventDefault(); // ✅ stops form submission or page reload
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

  <h2>📊 Charts</h2>

  <h3>Books per Category</h3>
  <canvas id="categoryChart"></canvas>

  <h3>Books Added Per Month</h3>
  <canvas id="monthlyChart"></canvas>

  <script>
    const categoryLabels = <?= json_encode(array_column($categoryData, 'General_Category')) ?>;
    const categoryCounts = <?= json_encode(array_column($categoryData, 'total')) ?>;
    new Chart(document.getElementById('categoryChart'), {
      type: 'bar',
      data: {
        labels: categoryLabels,
        datasets: [{
          label: 'Books per Category',
          data: categoryCounts,
          backgroundColor: 'skyblue'
        }]
      }
    });

    const monthLabels = <?= json_encode(array_column($monthlyData, 'month')) ?>;
    const monthCounts = <?= json_encode(array_column($monthlyData, 'total')) ?>;
    new Chart(document.getElementById('monthlyChart'), {
      type: 'line',
      data: {
        labels: monthLabels,
        datasets: [{
          label: 'Books Added',
          data: monthCounts,
          borderColor: 'blue',
          fill: false
        }]
      }
    });
  </script>

</body>
</html>
