<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Login required.");
}

$commentId = $_POST['comment_id'] ?? '';
$action = $_POST['action'] ?? '';

if ($commentId && in_array($action, ['like', 'dislike'])) {
    $column = $action === 'like' ? 'like_count' : 'dislike_count';
    $stmt = $conn->prepare("UPDATE comments SET $column = $column + 1 WHERE id = ?");
    $stmt->execute([$commentId]);
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
