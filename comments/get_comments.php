<?php
require '../includes/db.php';

$bookTitle = $_GET['book_title'] ?? '';
if (!$bookTitle) return;

$stmt = $conn->prepare("SELECT * FROM comments WHERE book_title = ? ORDER BY created_at DESC");
$stmt->execute([$bookTitle]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($comments as $comment): ?>
  <div class="border p-3 rounded mb-3">
    <p class="text-sm font-semibold text-gray-800">
      <?= htmlspecialchars($comment['name'] ?? 'Deleted User') ?>
    </p>
    <p class="text-gray-900 text-sm"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
    <p class="text-xs text-gray-500"><?= $comment['created_at'] ?></p>

    <?php if (isset($_SESSION['user_id'])): ?>
      <form method="POST" action="../comments/handle_reaction.php" class="flex gap-3 mt-2 text-sm">
        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
        <button type="submit" name="action" value="like" class="text-green-600">👍 <?= $comment['like_count'] ?></button>
        <button type="submit" name="action" value="dislike" class="text-red-600">👎 <?= $comment['dislike_count'] ?></button>
      </form>
    <?php else: ?>
      <p class="text-xs text-gray-500 mt-1">Login to like/dislike comments.</p>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
