<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/event_mailer.php';

$logFile = __DIR__ . '/cron_event_log.txt';
function logMessage($msg) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

$daysToExpire = 14; // pending proposals older than 14 days expire

try {
    $stmt = $conn->prepare("
        SELECT id, user_email, name, event_title, description, event_date, event_time
        FROM propose_event
        WHERE status='PENDING'
          AND date_submitted <= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$daysToExpire]);
    $expiredProposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $expiredCount = 0;

    foreach ($expiredProposals as $proposal) {
        // Update status to EXPIRED
        $update = $conn->prepare("UPDATE propose_event SET status='EXPIRED' WHERE id=?");
        $update->execute([$proposal['id']]);

        // Send expiry email
        sendProposalExpiryEmail(
            $proposal['user_email'],
            $proposal['name'],
            $proposal['event_title'],
            $proposal['description'],
            $proposal['event_date'],
            $proposal['event_time']
        );

        $expiredCount++;
    }

    logMessage("Cron run completed. Expired proposals: {$expiredCount}");

} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
}
