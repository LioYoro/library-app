<?php
// DB connection
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Delete records older than 1 year
$deleted = $pdo->exec("DELETE FROM post_event_report WHERE created_at < NOW() - INTERVAL 1 YEAR");

echo "✅ $deleted old records deleted from post_event_report.";
?>
