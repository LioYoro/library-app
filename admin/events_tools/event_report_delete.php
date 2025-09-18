<?php
// DB connection
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Delete all event reports older than 1 year
$sql = "DELETE FROM event_report WHERE created_at < NOW() - INTERVAL 1 YEAR";
$stmt = $pdo->prepare($sql);
$stmt->execute();

echo "✅ Event report cleanup complete. Old records (older than 1 year) have been deleted.";
