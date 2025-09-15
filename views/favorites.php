<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/header.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo "<p class='text-red-600 font-semibold p-4'>You must be logged in to view your favorite books.</p>";
    require_once __DIR__ . '/footer.php';
    exit;
}

// Fetch favorited books
$stmt = $conn->prepare("
  SELECT b.* FROM books b
  JOIN favorites f ON b.TITLE = f.book_title
  WHERE f.user_id = ?
");
$stmt->execute([$userId]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch comment counts per book title
$commentCounts = [];
$commentStmt = $conn->query("SELECT book_title, COUNT(*) as count FROM comments GROUP BY book_title");
foreach ($commentStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $commentCounts[$row['book_title']] = $row['count'];
}
?>

<div class="container mx-auto px-4 py-6">
  <h2 class="text-lg font-semibold mb-4"><?= count($books) ?> Favorite Book(s)</h2>

  <div class="mb-3">
    <a href="../index.php" class="text-blue-600 hover:underline">← Back to Home</a>
  </div>

  <?php if (count($books) === 0): ?>
    <p class="text-red-600 font-medium">You have no favorite books yet.</p>
  <?php else: ?>
    <div class="results grid gap-4">
      <?php foreach ($books as $book): ?>
        <a href="book_detail.php?title=<?= urlencode($book['TITLE']) ?>" class="card flex gap-4 p-4 border rounded-md bg-white shadow hover:shadow-md transition">
          <div class="thumbnail">
            <img src="<?= !empty($book['cover_image_url']) 
                          ? '../' . htmlspecialchars($book['cover_image_url']) 
                          : '../assets/Noimage.jpg' ?>" 
                alt="Book cover" 
                class="w-16 h-24 object-cover rounded shadow">
          </div>
          <div class="info flex-1">
            <div class="meta text-lg font-semibold text-blue-700 mb-1">
              <?= htmlspecialchars($book['TITLE']) ?>
            </div>
            <div class="text-sm text-gray-600 mb-1">
              👤 <?= htmlspecialchars($book['AUTHOR'] ?? 'Unknown') ?><br>
              🔖 <?= htmlspecialchars($book['CALL NUMBER'] ?? 'N/A') ?><br>
              🏷 <?= htmlspecialchars($book['General_Category'] ?? 'Uncategorized') ?>
            </div>
            <div class="summary text-sm mt-2">
              <?= nl2br(htmlspecialchars($book['SUMMARY'] ?? 'No summary available.')) ?>
            </div>
            <div class="likes text-sm text-gray-700 mt-2">
              👍 <?= $book['Like'] ?? 0 ?> &nbsp; | 
              👎 <?= $book['Dislike'] ?? 0 ?> &nbsp; | 
              💬 <?= $commentCounts[$book['TITLE']] ?? 0 ?> comment<?= ($commentCounts[$book['TITLE']] ?? 0) == 1 ? '' : 's' ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
