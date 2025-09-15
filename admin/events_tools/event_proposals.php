<?php
// DB connection
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Mark as read/unread
if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    $status = $_GET['status'] === "READ" ? "UNREAD" : "READ";
    $stmt = $pdo->prepare("UPDATE event_proposals SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);
    header("Location: manage_proposals.php?page=$page");
    exit;
}

// Delete read proposal
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("SELECT file_path FROM event_proposals WHERE id=?");
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();
    if ($file && file_exists(__DIR__ . "/uploads/" . $file)) {
        unlink(__DIR__ . "/uploads/" . $file);
    }
    $stmt = $pdo->prepare("DELETE FROM event_proposals WHERE id=?");
    $stmt->execute([$id]);
    header("Location: manage_proposals.php?page=$page");
    exit;
}

// Current month proposals (UNREAD only)
$currentMonth = date("Y-m");
$stmt = $pdo->prepare("SELECT * FROM propose_event
    WHERE DATE_FORMAT(date_submitted, '%Y-%m') = ? AND (status IS NULL OR status='UNREAD') 
    ORDER BY date_submitted DESC 
    LIMIT $limit OFFSET $offset");
$stmt->execute([$currentMonth]);
$unreadProposals = $stmt->fetchAll();

// Count total unread proposals
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM propose_event 
    WHERE DATE_FORMAT(date_submitted, '%Y-%m') = ? AND (status IS NULL OR status='UNREAD')");
$countStmt->execute([$currentMonth]);
$totalUnread = $countStmt->fetchColumn();
$totalPages = ceil($totalUnread / $limit);

// Read proposals (all, shown in a separate list)
$readStmt = $pdo->prepare("SELECT * FROM propose_event WHERE status='READ' ORDER BY date_submitted DESC");
$readStmt->execute();
$readProposals = $readStmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Event Proposals</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f9fafb; }
        h1 { color: #1d4ed8; }
        .proposal-card {
            border: 1px solid #ddd; background: #fff; padding: 15px;
            margin-bottom: 15px; border-radius: 6px;
        }
        .proposal-card h3 { margin: 0 0 10px; color: #1d4ed8; }
        .proposal-card p { margin: 5px 0; }
        .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; color: #fff; font-size: 14px; }
        .btn-green { background: green; }
        .btn-red { background: red; }
        .btn-del { background: #dc2626; }
        .pagination { margin-top: 15px; text-align: center; }
        .pagination a {
            padding: 6px 12px; margin: 0 2px; border: 1px solid #ccc; border-radius: 4px;
            text-decoration: none; color: #333;
        }
        .pagination .active { background: #1d4ed8; color: #fff; }
    </style>
</head>
<body>
    <h1>📑 Event Proposals</h1>

    <h2>📌 Current Month Proposals (Unread)</h2>
    <?php if (empty($unreadProposals)): ?>
        <p>No new proposals this month.</p>
    <?php else: ?>
        <?php foreach ($unreadProposals as $proposal): ?>
            <div class="proposal-card">
                <h3><?= htmlspecialchars($proposal['event_title']) ?></h3>
                <p><b>From:</b> <?= htmlspecialchars($proposal['name']) ?></p>
                <p><b>Contact:</b> <?= htmlspecialchars($proposal['contact']) ?></p>
                <p><?= nl2br(htmlspecialchars($proposal['description'])) ?></p>
                <p><b>File:</b> 
                    <?php if ($proposal['file_path']): ?>
                        <a href="uploads/<?= htmlspecialchars($proposal['file_path']) ?>" target="_blank">View File</a>
                    <?php else: ?>
                        No File
                    <?php endif; ?>
                </p>
                <a href="?toggle_id=<?= $proposal['id'] ?>&status=<?= $proposal['status'] ?? 'UNREAD' ?>" 
                   class="btn btn-green">Mark as READ</a>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <h2>✅ Read Proposals</h2>
    <?php if (empty($readProposals)): ?>
        <p>No proposals marked as read.</p>
    <?php else: ?>
        <?php foreach ($readProposals as $proposal): ?>
            <div class="proposal-card" style="background:#f1f5f9;">
                <h3><?= htmlspecialchars($proposal['event_title']) ?></h3>
                <p><b>From:</b> <?= htmlspecialchars($proposal['name']) ?></p>
                <p><?= nl2br(htmlspecialchars($proposal['description'])) ?></p>
                <p><b>File:</b> 
                    <?php if ($proposal['file_path']): ?>
                        <a href="uploads/<?= htmlspecialchars($proposal['file_path']) ?>" target="_blank">View File</a>
                    <?php else: ?>
                        No File
                    <?php endif; ?>
                </p>
                <a href="?toggle_id=<?= $proposal['id'] ?>&status=READ" class="btn btn-red">Mark as UNREAD</a>
                <a href="?delete_id=<?= $proposal['id'] ?>" class="btn btn-del" onclick="return confirm('Delete this proposal?')">🗑 Delete</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
