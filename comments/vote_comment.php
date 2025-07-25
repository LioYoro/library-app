<?php
// vote_comment.php
session_start();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$commentId = $_POST['comment_id'] ?? null;
$action = $_POST['action'] ?? null; // like or dislike

if (!$commentId || !in_array($action, ['like', 'dislike'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// Check if user already voted
$stmt = $conn->prepare("SELECT vote FROM comment_votes WHERE comment_id = ? AND user_id = ?");
$stmt->execute([$commentId, $userId]);
$existing = $stmt->fetchColumn();

if ($existing === $action) {
    // Undo vote
    $conn->prepare("DELETE FROM comment_votes WHERE comment_id = ? AND user_id = ?")
         ->execute([$commentId, $userId]);

    $conn->prepare("UPDATE comments SET {$action}_count = {$action}_count - 1 WHERE id = ?")
         ->execute([$commentId]);

    echo json_encode(['status' => 'success', 'action' => 'removed', 'type' => $action]);
    exit;
} elseif ($existing) {
    // Switch vote
    $conn->prepare("UPDATE comment_votes SET vote = ? WHERE comment_id = ? AND user_id = ?")
         ->execute([$action, $commentId, $userId]);

    $opposite = $existing === 'like' ? 'dislike' : 'like';

    $conn->prepare("UPDATE comments SET {$opposite}_count = {$opposite}_count - 1, {$action}_count = {$action}_count + 1 WHERE id = ?")
         ->execute([$commentId]);

    echo json_encode(['status' => 'success', 'action' => 'switched', 'type' => $action]);
    exit;
} else {
    // New vote
    $conn->prepare("INSERT INTO comment_votes (comment_id, user_id, vote) VALUES (?, ?, ?)")
         ->execute([$commentId, $userId, $action]);

    $conn->prepare("UPDATE comments SET {$action}_count = {$action}_count + 1 WHERE id = ?")
         ->execute([$commentId]);

    echo json_encode(['status' => 'success', 'action' => 'added', 'type' => $action]);
    exit;
}
