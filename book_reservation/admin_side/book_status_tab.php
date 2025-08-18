<?php
session_start();
$pageTitle = "Book Status Management";

// Include database connection
include('../../includes/db.php'); // Adjust path as needed

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'], $_POST['new_status'])) {
    header('Content-Type: application/json');

    try {
        $bookId = intval($_POST['book_id']);
        $newStatus = trim($_POST['new_status']);

        $validStatuses = ['available', 'borrowed', 'reserved', 'archived'];
        if (!in_array($newStatus, $validStatuses)) throw new Exception("Invalid status");

        // Begin transaction
        $conn->beginTransaction();

        // Update book status
        $stmt = $conn->prepare("UPDATE books SET status=? WHERE id=?");
        $stmt->execute([$newStatus, $bookId]);

        // If borrowed and no existing reservation, create walk-in reservation
        if ($newStatus === 'borrowed') {
            $checkRes = $conn->prepare("
                SELECT * FROM reservations 
                WHERE book_id = ? 
                AND status IN ('pending','borrowed') 
                AND done = 0
                LIMIT 1
            ");

            $checkRes->execute([$bookId]);
            $existingRes = $checkRes->fetch(PDO::FETCH_ASSOC);

            if (!$existingRes) {
                // Get Walk-in user ID
                $stmtUser = $conn->prepare("SELECT id FROM users WHERE email='walkin@example.com' LIMIT 1");
                $stmtUser->execute();
                $walkInUserId = $stmtUser->fetchColumn();

                if (!$walkInUserId) {
                    throw new Exception("Walk-in user not found. Please create the user first.");
                }

                // Now use $walkInUserId when inserting reservation
                $stmt2 = $conn->prepare("
                    INSERT INTO reservations
                    (user_id, book_id, book_title, pickup_time, expiry_time, status, done, reservation_type)
                    VALUES
                    (?, ?, (SELECT TITLE FROM books WHERE id=?), NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'borrowed', 0, 'walk-in')
                ");
                $stmt2->execute([$walkInUserId, $bookId, $bookId]);
            }
        }

        $conn->commit();
        echo json_encode(['success'=>true, 'new_status'=>$newStatus]);

    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}

// Fetch all books
$booksQuery = "SELECT id, TITLE, AUTHOR, status FROM books ORDER BY TITLE ASC";
$stmt = $conn->query($booksQuery);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="book_status.css">
</head>
<body>
<h1><?= $pageTitle ?></h1>
<a href="../../admin/reservations.php" class="btn-back">← Back to Admin Hub</a>

<div class="search-container">
    <input type="text" id="bookSearch" placeholder="Search by book title..." onkeyup="filterBooks()">
</div>

<table id="booksTable">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($books as $book): ?>
        <tr id="row-<?= $book['id'] ?>">
            <td><?= $book['id'] ?></td>
            <td class="book-title"><?= htmlspecialchars($book['TITLE']) ?></td>
            <td><?= htmlspecialchars($book['AUTHOR']) ?></td>
            <td id="status-<?= $book['id'] ?>"><?= $book['status'] ?></td>
            <td>
                <select onchange="updateStatus(<?= $book['id'] ?>, this.value)">
                    <option value="available" <?= $book['status']=='available'?'selected':'' ?>>Available</option>
                    <option value="borrowed" <?= $book['status']=='borrowed'?'selected':'' ?>>Borrowed</option>
                    <option value="reserved" <?= $book['status']=='reserved'?'selected':'' ?>>Reserved</option>
                    <option value="archived" <?= $book['status']=='archived'?'selected':'' ?>>Archived</option>
                </select>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="book_status.js"></script>
</body>
</html>
