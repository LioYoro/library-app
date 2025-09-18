<?php
session_start();
include(__DIR__ . '/../includes/db.php');
include(__DIR__ . '/../views/header.php'); 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    echo "<div class='login-warning'>
            <h2>⚠ You need to log in to view this proposal.</h2>
            <a href='../login.php' class='login-btn'>Go to Login</a>
          </div>";
    include(__DIR__ . '/../views/footer.php'); 
    exit();
}

$proposalId = $_GET['id'] ?? null;
if (!$proposalId) {
    echo "<p class='error-msg'>Invalid request.</p>";
    include(__DIR__ . '/../views/footer.php'); 
    exit();
}

$stmt = $conn->prepare("SELECT * FROM propose_event WHERE id = ? AND user_email = ?");
$stmt->execute([$proposalId, $_SESSION['email']]);
$proposal = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<link rel="stylesheet" href="my_proposals.css">
<div class="content-wrapper">
    <?php if ($proposal): ?>
        <h2 class="page-title">📌 Proposal Detail</h2>
        <div class="proposal-detail">
            <h3 class="proposal-title"><?= htmlspecialchars($proposal['event_title']) ?></h3>
            
            <p class="proposal-status">
                <strong>Status:</strong> 
                <span class="status <?= strtolower($proposal['status']) ?>">
                    <?= htmlspecialchars($proposal['status']) ?>
                </span>
            </p>

            <?php if (!empty($proposal['event_date'])): ?>
                <p><strong>Event Date:</strong> <?= htmlspecialchars($proposal['event_date']) ?></p>
            <?php endif; ?>

            <?php if (!empty($proposal['event_time'])): ?>
                <p><strong>Event Time:</strong> <?= htmlspecialchars(substr($proposal['event_time'], 0, 5)) ?></p>
            <?php endif; ?>
            
            <div class="proposal-description">
                <strong>Description:</strong>
                <p><?= nl2br(htmlspecialchars($proposal['description'])) ?></p>
            </div>
            
            <p><strong>Contact:</strong> <?= htmlspecialchars($proposal['contact']) ?></p>
            <p><strong>Date Submitted:</strong> <?= htmlspecialchars($proposal['date_submitted']) ?></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($proposal['name']) ?></p>

            <?php if (!empty($proposal['file_path'])): ?>
                <p>
                    <a href="uploads/<?= htmlspecialchars($proposal['file_path']) ?>" 
                       target="_blank" 
                       class="btn-view-file">📄 View File</a>
                </p>
            <?php endif; ?>

            <?php if ($proposal['status'] === 'ACCEPTED'): ?>
                <p style="color: green;"><strong>Accepted:</strong> Please visit the library to discuss with the officer in charge.</p>
            <?php endif; ?>

            <?php if ($proposal && $proposal['status'] === 'PENDING'): ?>
                <form method="POST" action="cancel_proposal.php" onsubmit="return confirm('Are you sure you want to cancel this proposal?');">
                    <input type="hidden" name="proposal_id" value="<?= $proposal['id'] ?>">
                    <button type="submit" class="btn-cancel">❌ Cancel Proposal</button>
                </form>
            <?php endif; ?>

            <div class="back-btn-container">
                <a href="my_proposals.php" class="btn-back">⬅ Back to My Proposals</a>
            </div>
        </div>
    <?php else: ?>
        <p class="error-msg">❌ Proposal not found.</p>
    <?php endif; ?>
</div>
<?php include(__DIR__ . '/../views/footer.php'); ?>


