<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Login required.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $bookTitle = $_POST['book_title'] ?? '';
    $content = trim($_POST['content'] ?? '');

    if ($bookTitle && $content !== '') {
        $stmt = $conn->prepare("INSERT INTO comments (book_title, user_id, name, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$bookTitle, $userId, $name, $content]);
    }

    header("Location: ../views/book_detail.php?title=" . urlencode($bookTitle));
    exit;
}
?>
