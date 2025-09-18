<?php
session_start();
include(__DIR__ . '/../includes/db.php');
require_once __DIR__ . '/../includes/event_mailer.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    echo "<div class='login-warning'>
            <h2>⚠ You need to log in to cancel a proposal.</h2>
            <a href='../login.php' class='login-btn'>Go to Login</a>
          </div>";
    exit();
}

$proposalId = $_POST['proposal_id'] ?? null;
$userEmail = $_SESSION['email'];

if ($proposalId) {
    // Check ownership
    $stmt = $conn->prepare("SELECT * FROM propose_event WHERE id = ? AND user_email = ?");
    $stmt->execute([$proposalId, $userEmail]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($proposal && $proposal['status'] === 'PENDING') {
        // Update status
        $update = $conn->prepare("UPDATE propose_event SET status = 'CANCELLED' WHERE id = ?");
        $update->execute([$proposalId]);

        // Send email notification
        sendCancelledProposalEmail(
            $userEmail,
            $proposal['name'],
            $proposal['event_title'],
            $proposal['description'],
            $proposal['contact'],
            $proposal['date_submitted']
        );

        header("Location: my_proposals.php?msg=cancelled");
        exit();
    } else {
        echo "<p class='error-msg'>❌ Proposal not found or cannot be cancelled.</p>";
    }
} else {
    echo "<p class='error-msg'>❌ Invalid request.</p>";
}
?>
