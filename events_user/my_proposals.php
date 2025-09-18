<?php
session_start();
include(__DIR__ . '/../includes/db.php');
include(__DIR__ . '/../views/header.php'); 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    echo "<div class='login-warning'>
            <h2>⚠ You need to log in to view your proposals.</h2>
            <a href='../login.php' class='login-btn'>Go to Login</a>
          </div>";
    include(__DIR__ . '/../views/footer.php'); 
    exit();
}

$userEmail = $_SESSION['email'];
$statusFilter = $_GET['status'] ?? 'All';

$sql = "SELECT * FROM propose_event WHERE user_email = ?";
$params = [$userEmail];

if ($statusFilter !== 'All') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

// Sort by most recent first
$sql .= " ORDER BY date_submitted DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Event Proposals</title>
    <link rel="stylesheet" href="my_proposals.css">
</head>
<body>
<div class="content-wrapper">
    <h2 class="page-title">📑 My Event Proposals</h2>

    <!-- Filter -->
    <form method="get" class="filter-form">
        <label for="status">Filter by Status:</label>
        <select name="status" id="status" onchange="this.form.submit()">
            <option value="All" <?= $statusFilter=='All'?'selected':'' ?>>All</option>
            <option value="PENDING" <?= $statusFilter=='PENDING'?'selected':'' ?>>Pending</option>
            <option value="ACCEPTED" <?= $statusFilter=='ACCEPTED'?'selected':'' ?>>Accepted</option>
            <option value="REJECTED" <?= $statusFilter=='REJECTED'?'selected':'' ?>>Rejected</option>
            <option value="CANCELLED" <?= $statusFilter=='CANCELLED'?'selected':'' ?>>Cancelled</option>
        </select>
    </form>

    <div class="proposals-container">
        <?php if ($proposals): ?>
            <?php foreach ($proposals as $event): ?>
                <div class="proposal-card" onclick="window.location.href='proposal_detail.php?id=<?= $event['id'] ?>'">
                    <h3><?= htmlspecialchars($event['event_title']) ?></h3>
                    
                    <p><strong>Date Submitted:</strong> <?= htmlspecialchars($event['date_submitted']) ?></p>
                    
                    <?php if (!empty($event['event_date'])): ?>
                        <p><strong>Event Date:</strong> <?= htmlspecialchars($event['event_date']) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['event_time'])): ?>
                        <p><strong>Event Time:</strong> <?= date("g:i A", strtotime($event['event_time'])) ?></p>
                    <?php endif; ?>
                    
                    <p class="status <?= strtolower($event['status']) ?>">Status: <?= htmlspecialchars($event['status']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty-msg">No proposals found.</p>
        <?php endif; ?>
    </div>
</div>
<?php include(__DIR__ . '/../views/footer.php'); ?>
</body>
</html>
