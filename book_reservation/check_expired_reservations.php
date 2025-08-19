<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/reservation_mailer.php';

// Log file for cron runs
$logFile = __DIR__ . '/cron_log.txt';
function logMessage($msg) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

$now = date('Y-m-d H:i:s');

try {
    // 1️⃣ Expire overdue reservations
    $stmt = $conn->prepare("
        SELECT reservation_id, user_id, book_title, pickup_time
        FROM reservations
        WHERE status='pending' AND done=0 AND expiry_time <= NOW()
    ");
    $stmt->execute();
    $expiredReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $expiredCount = 0;

    foreach ($expiredReservations as $res) {
        // Update reservation to expired + done
        $update = $conn->prepare("UPDATE reservations SET status='expired', done=1 WHERE reservation_id=?");
        $update->execute([$res['reservation_id']]);

        // Set book back to available
        $bookUpdate = $conn->prepare("UPDATE books SET status='available' WHERE TITLE=?");
        $bookUpdate->execute([$res['book_title']]);

        // Notify user
        $stmtUser = $conn->prepare("SELECT first_name, email FROM users WHERE id=?");
        $stmtUser->execute([$res['user_id']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['email']) {
            sendReservationExpiryEmail($user['email'], $user['first_name'], $res['book_title'], $res['pickup_time']);
        }

        $expiredCount++;
    }

    // 2️⃣ Send 1-hour reminders for upcoming expiries
    $stmt = $conn->prepare("
        SELECT reservation_id, user_id, book_title, pickup_time, expiry_time
        FROM reservations
        WHERE status='pending' AND done=0
          AND expiry_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute();
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reminderCount = 0;

    foreach ($reminders as $res) {
        $stmtUser = $conn->prepare("SELECT first_name, email FROM users WHERE id=?");
        $stmtUser->execute([$res['user_id']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['email']) {
            sendReservationReminderEmail($user['email'], $user['first_name'], $res['book_title'], $res['pickup_time']);
        }

        $reminderCount++;
    }

    logMessage("Cron run completed successfully. Expired reservations: {$expiredCount}, Reminders sent: {$reminderCount}");

} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
}
