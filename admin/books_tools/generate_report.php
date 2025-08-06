<?php
include '../db.php';

// 1. Run Python to generate the chart
exec("python3 generate_chart.py");

// 2. Get last report time
$result = mysqli_query($conn, "SELECT last_generated FROM report_tracker ORDER BY id DESC LIMIT 1");
$last_generated = mysqli_fetch_assoc($result)['last_generated'] ?? '2000-01-01';

// 3. Count new books since last report
$sql_new = "SELECT COUNT(*) as count FROM books WHERE date_added > '$last_generated'";
$new_books = mysqli_fetch_assoc(mysqli_query($conn, $sql_new))['count'];

// 4. Count books this month
$current_month = date('Y-m');
$sql_month = "SELECT COUNT(*) as count FROM books WHERE DATE_FORMAT(date_added, '%Y-%m') = '$current_month'";
$month_books = mysqli_fetch_assoc(mysqli_query($conn, $sql_month))['count'];

// 5. Save new report timestamp
$now = date('Y-m-d H:i:s');
mysqli_query($conn, "INSERT INTO report_tracker (last_generated) VALUES ('$now')");

exec("python generate_chart.py 2>&1", $output);
echo "<pre>" . implode("\n", $output) . "</pre>";

?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Report</title>
</head>
<body style="font-family: sans-serif; padding: 20px;">
    <h2>📊 Library Book Report</h2>
    <p><strong><?= $new_books ?></strong> books added since last report on <strong><?= date('F j, Y', strtotime($last_generated)) ?></strong>.</p>
    <p><strong><?= $month_books ?></strong> books added this month (<?= $current_month ?>).</p>
    <a href="book_summary.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
          BACK
      </a>
    <h3>📈 Monthly Chart</h3>
    <img src="charts/monthly_chart.png" style="width: 90%; max-width: 800px; border: 1px solid #ccc;">
</body>
</html>
