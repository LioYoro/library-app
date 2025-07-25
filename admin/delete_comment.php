<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
  http_response_code(403);
  die("Access denied.");
}

require_once __DIR__ . '/../includes/db.php';

$commentId = $_POST['comment_id'] ?? null;

if (!$commentId) {
  http_response_code(400);
  echo "Missing comment ID";
  exit;
}

// First delete feedback to maintain integrity
$stmt = $conn->prepare("DELETE FROM comment_feedback WHERE comment_id = ?");
$stmt->execute([$commentId]);

// Then delete the comment itself
$stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$commentId]);

echo "deleted";
