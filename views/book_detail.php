<?php
session_start();
$userId = $_SESSION['user_id'] ?? null;

$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle book like/dislike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['book_title']) && in_array($_POST['action'], ['like', 'dislike'])) {
    if (!$userId) die("You must be logged in to like or dislike.");
    $action = $_POST['action'];
    $title = $_POST['book_title'];

    $stmt = $pdo->prepare("SELECT feedback FROM book_feedback WHERE user_id = ? AND book_title = ?");
    $stmt->execute([$userId, $title]);
    $existing = $stmt->fetchColumn();

    if ($existing === $action) {
        $pdo->prepare("DELETE FROM book_feedback WHERE user_id = ? AND book_title = ?")->execute([$userId, $title]);
        $col = $action === 'like' ? 'Like' : 'Dislike';
        $pdo->prepare("UPDATE books SET `$col` = `$col` - 1 WHERE TITLE = ?")->execute([$title]);
    } elseif ($existing) {
        $pdo->prepare("UPDATE book_feedback SET feedback = ? WHERE user_id = ? AND book_title = ?")->execute([$action, $userId, $title]);
        $fromCol = $existing === 'like' ? 'Like' : 'Dislike';
        $toCol = $action === 'like' ? 'Like' : 'Dislike';
        $pdo->prepare("UPDATE books SET `$fromCol` = `$fromCol` - 1, `$toCol` = `$toCol` + 1 WHERE TITLE = ?")->execute([$title]);
    } else {
        $pdo->prepare("INSERT INTO book_feedback (user_id, book_title, feedback) VALUES (?, ?, ?)")->execute([$userId, $title, $action]);
        $col = $action === 'like' ? 'Like' : 'Dislike';
        $pdo->prepare("UPDATE books SET `$col` = `$col` + 1 WHERE TITLE = ?")->execute([$title]);
    }

    header("Location: book_detail.php?title=" . urlencode($title));
    exit;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && $userId) {
    $content = trim($_POST['comment']);
    $title = $_POST['book_title'];
    if ($content !== '') {
        $name = $_SESSION['first_name'] ?? 'Anonymous';
        $stmt = $pdo->prepare("INSERT INTO comments (book_title, user_id, name, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $userId, $name, $content]);
        header("Location: book_detail.php?title=" . urlencode($title));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['favorite_action'], $_POST['book_title']) && $userId) {
    $bookTitle = $_POST['book_title'];
    $action = $_POST['favorite_action'];

    if ($action === 'favorite') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, book_title) VALUES (?, ?)");
        $stmt->execute([$userId, $bookTitle]);
    } elseif ($action === 'unfavorite') {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND book_title = ?");
        $stmt->execute([$userId, $bookTitle]);
    }

    header("Location: book_detail.php?title=" . urlencode($bookTitle));
    exit;
}

// Handle comment like/dislike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_action'], $_POST['comment_id']) && $userId) {
    $commentId = $_POST['comment_id'];
    $action = $_POST['comment_action'];

    $stmt = $pdo->prepare("SELECT feedback FROM comment_feedback WHERE user_id = ? AND comment_id = ?");
    $stmt->execute([$userId, $commentId]);
    $existing = $stmt->fetchColumn();

    if ($existing === $action) {
        $pdo->prepare("DELETE FROM comment_feedback WHERE user_id = ? AND comment_id = ?")->execute([$userId, $commentId]);
        $col = $action === 'like' ? 'like_count' : 'dislike_count';
        $pdo->prepare("UPDATE comments SET `$col` = `$col` - 1 WHERE id = ?")->execute([$commentId]);
    } elseif ($existing) {
        $pdo->prepare("UPDATE comment_feedback SET feedback = ? WHERE user_id = ? AND comment_id = ?")->execute([$action, $userId, $commentId]);
        $fromCol = $existing === 'like' ? 'like_count' : 'dislike_count';
        $toCol = $action === 'like' ? 'like_count' : 'dislike_count';
        $pdo->prepare("UPDATE comments SET `$fromCol` = `$fromCol` - 1, `$toCol` = `$toCol` + 1 WHERE id = ?")->execute([$commentId]);
    } else {
        $pdo->prepare("INSERT INTO comment_feedback (user_id, comment_id, feedback) VALUES (?, ?, ?)")->execute([$userId, $commentId, $action]);
        $col = $action === 'like' ? 'like_count' : 'dislike_count';
        $pdo->prepare("UPDATE comments SET `$col` = `$col` + 1 WHERE id = ?")->execute([$commentId]);
    }

    header("Location: book_detail.php?title=" . urlencode($_GET['title']));
    exit;
}

// Fetch book info
$title = $_GET['title'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM books WHERE TITLE = ?");
$stmt->execute([$title]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);
if ($book) $_SESSION['last_viewed_title'] = $book['TITLE'];

// Get user vote
$voteStmt = $pdo->prepare("SELECT feedback FROM book_feedback WHERE user_id = ? AND book_title = ?");
$voteStmt->execute([$userId, $title]);
$userVote = $voteStmt->fetchColumn();

// Fetch comments
$commentsStmt = $pdo->prepare("SELECT * FROM comments WHERE book_title = ? ORDER BY created_at DESC");
$commentsStmt->execute([$title]);
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
$commentCount = count($comments);

// Get comment feedbacks for logged-in user
$commentVotes = [];
if ($userId) {
    $voteQuery = $pdo->prepare("SELECT comment_id, feedback FROM comment_feedback WHERE user_id = ?");
    $voteQuery->execute([$userId]);
    foreach ($voteQuery as $row) {
        $commentVotes[$row['comment_id']] = $row['feedback'];
    }
}

require __DIR__ . '/header.php';
?>

<?php
// Pagination
$commentsPerPage = 5;
$page = max((int)($_GET['page'] ?? 1), 1);
$offset = ($page - 1) * $commentsPerPage;

// Fetch paginated comments
$commentsStmt = $pdo->prepare("SELECT * FROM comments WHERE book_title = ? ORDER BY created_at DESC LIMIT $commentsPerPage OFFSET $offset");
$commentsStmt->execute([$title]);
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Count total for pagination display
$totalCommentsStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE book_title = ?");
$totalCommentsStmt->execute([$title]);
$commentCount = (int)$totalCommentsStmt->fetchColumn();

$totalPages = max(1, ceil($commentCount / $commentsPerPage));
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
?>

<?php
$isFavorited = false;
if ($userId) {
    $stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND book_title = ?");
    $stmt->execute([$userId, $title]);
    $isFavorited = $stmt->fetchColumn();
}
?>

<?php if ($userId): ?>
  <form method="post" class="mb-4">
    <input type="hidden" name="favorite_action" value="<?= $isFavorited ? 'unfavorite' : 'favorite' ?>">
    <input type="hidden" name="book_title" value="<?= htmlspecialchars($book['TITLE']) ?>">
    <button type="submit" class="text-sm px-3 py-1 rounded <?= $isFavorited ? 'bg-red-500 text-white' : 'bg-gray-200 text-black hover:bg-gray-300' ?>">
      <?= $isFavorited ? '🗑 Remove from Favorites' : '❤️ Add to Favorites' ?>
    </button>
  </form>
<?php endif; ?>

<?php require __DIR__ . '/search_bar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    
    <!-- LEFT SIDE: Book + Comments in one bordered section -->
    <div class="md:col-span-2">
      <section class="border rounded-lg p-6 bg-white shadow-sm">
        <?php if (!$book): ?>
          <p class="text-red-600 font-semibold">Book not found.</p>
        <?php else: ?>
          <!-- 📖 Book Title & Info -->
          <h1 class="text-2xl font-bold mb-2"><?= htmlspecialchars($book['TITLE']) ?></h1>
          <p class="text-sm text-gray-600 mb-4">
            👤 <strong>Author:</strong> <?= htmlspecialchars($book['AUTHOR']) ?><br>
            🔖 <strong>Call Number:</strong> <?= htmlspecialchars($book['CALL NUMBER']) ?><br>
            📚 <strong>Accession No.:</strong> <?= htmlspecialchars($book['ACCESSION NO.']) ?><br>
            🏷 <strong>General Category:</strong> <?= htmlspecialchars($book['General_Category']) ?><br>
            🔖 <strong>Sub-Category:</strong> <?= htmlspecialchars($book['Sub_Category']) ?><br>
            🧠 <strong>Keywords:</strong> <?= htmlspecialchars($book['KEYWORDS']) ?>
          </p>

          <!-- 📘 Summary -->
          <div class="bg-gray-50 border rounded p-4 text-gray-800 mb-6">
            <h2 class="font-semibold mb-2">📘 Summary</h2>
            <p class="text-sm leading-relaxed whitespace-pre-line"><?= nl2br(htmlspecialchars($book['SUMMARY'])) ?></p>
          </div>

          <!-- 👍👎 Votes -->
          <div class="flex gap-4 mb-6">
            <form method="post">
              <input type="hidden" name="action" value="like">
              <input type="hidden" name="book_title" value="<?= htmlspecialchars($book['TITLE']) ?>">
              <button class="px-4 py-2 rounded text-sm transition <?= $userVote === 'like' ? 'bg-green-500 text-white' : 'bg-gray-100 text-green-700 hover:bg-green-200' ?>">
                👍 Like (<?= $book['Like'] ?? 0 ?>)
              </button>
            </form>
            <form method="post">
              <input type="hidden" name="action" value="dislike">
              <input type="hidden" name="book_title" value="<?= htmlspecialchars($book['TITLE']) ?>">
              <button class="px-4 py-2 rounded text-sm transition <?= $userVote === 'dislike' ? 'bg-red-500 text-white' : 'bg-gray-100 text-red-700 hover:bg-red-200' ?>">
                👎 Dislike (<?= $book['Dislike'] ?? 0 ?>)
              </button>
            </form>
          </div>
<?php
// Determine book reservation availability
date_default_timezone_set('Asia/Manila'); // set to PH time
$currentTime = date('H:i'); // current server time
$canReserveNow = true;
//$canReserveNow = ($currentTime >= '07:30' && $currentTime <= '19:00');


$disableReserve = false;
$reservationMessage = '';

// 1. Walk-in status
switch ($book['status']) {
    case 'borrowed':
        $disableReserve = true;
        $reservationMessage = "❌ Book Currently Borrowed";
        break;
    case 'archived':
        $disableReserve = true;
        $reservationMessage = "❌ Book Unavailable";
        break;
    case 'reserved':
        $reservationMessage = "⚠️ Book Reserved by Walk-in";
        break;
}

// 2. Check latest reservation
$resStmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE book_id = ? 
    AND status IN ('pending','borrowed')
    AND done = 0
    ORDER BY created_at ASC 
    LIMIT 1
");
$resStmt->execute([$book['id']]);
$currentReservation = $resStmt->fetch(PDO::FETCH_ASSOC);

if (!$disableReserve && $currentReservation) {
    if ($currentReservation['status'] === 'borrowed') {
        $disableReserve = true;
        if ($currentReservation['user_id'] == $userId) {
            $reservationMessage = "✅ You have borrowed this book via reservation";
        } else {
            $reservationMessage = "⚠️ Book Already Borrowed via Reservation";
        }
    } elseif ($currentReservation['status'] === 'pending') {
        if ($currentReservation['user_id'] == $userId) {
            $disableReserve = true;
            $reservationMessage = "⏳ You already have a pending reservation for this book";
        } else {
            $disableReserve = true;
            $reservationMessage = "⏳ This book has a pending reservation by another user. Please try again later.";
        }
    }
}

?>

<div class="mb-6 p-4 border rounded bg-white shadow">
    <h2 class="font-semibold mb-2">📖 Book Reservation Status</h2>

    <?php if ($reservationMessage): ?>
        <p class="<?= $book['status'] === 'Borrowed' ? 'text-red-600' : 'text-yellow-600' ?> font-bold">
            <?= htmlspecialchars($reservationMessage) ?>
        </p>
    <?php endif; ?>

    <?php if ($userId): ?>
        <form method="post" action="../book_reservation/reserve.php">
            <input type="hidden" name="book_title" value="<?= htmlspecialchars($book['TITLE']) ?>">
            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
            <button type="submit" 
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                <?= $disableReserve ? 'disabled' : '' ?>>
                📌 Reserve Book
            </button>
        </form>
    <?php else: ?>
        <p class="text-sm text-gray-600">🔒 <a href="../login/login.php" class="text-blue-600 underline">Log in</a> to reserve this book.</p>
    <?php endif; ?>
</div>

          <!-- 🗨️ Comments -->
          <section class="mb-6">
            <h2 class="text-lg font-semibold mb-2">🗨️ Comments (<?= $commentCount ?>)</h2>
            <?php if ($userId): ?>
              <form method="post" class="mb-4">
                <input type="hidden" name="book_title" value="<?= htmlspecialchars($book['TITLE']) ?>">
                <textarea name="comment" rows="3" class="w-full border rounded p-2 text-sm" placeholder="Leave a comment..."></textarea>
                <button type="submit" class="mt-2 bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700 text-sm">Post Comment</button>
              </form>
            <?php else: ?>
              <p class="text-sm text-gray-600 mb-3">🔒 <a href="../login/login.php" class="text-blue-600 underline">Log in</a> to comment or like/dislike.</p>
            <?php endif; ?>

            <?php foreach ($comments as $c): ?>
              <div class="border rounded p-3 mb-2 bg-white shadow-sm">
                <p class="text-sm font-semibold"><?= htmlspecialchars($c['name']) ?> 
                  <span class="text-gray-400 text-xs">(<?= $c['created_at'] ?>)</span>
                </p>
                <p class="text-sm mt-1"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
              </div>
            <?php endforeach; ?>
          </section>
        <?php endif; ?>
      </section>
    </div>

    <!-- RIGHT SIDE: Sidebar -->
    <aside class="space-y-6">
      <!-- 📌 Related Books -->
      <section class="border rounded-lg p-4 bg-gray-50 shadow-sm">
        <h2 class="text-lg font-semibold mb-2">📌 Related Books</h2>
        <div class="grid gap-4">
          <?php
          $stmt = $pdo->prepare("SELECT * FROM books WHERE TITLE != ? AND MATCH(KEYWORDS) AGAINST(?) LIMIT 3");
          $stmt->execute([$book['TITLE'], $book['KEYWORDS']]);
          foreach ($stmt as $b): ?>
            <a href="book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="block border rounded hover:shadow-md p-3 bg-white">
              <strong><?= htmlspecialchars($b['TITLE']) ?></strong><br>
              👤 <?= htmlspecialchars($b['AUTHOR']) ?><br>
              🔖 <?= htmlspecialchars($b['CALL NUMBER']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- 🔥 Trending -->
      <section class="border rounded-lg p-4 bg-gray-50 shadow-sm">
        <h2 class="text-lg font-semibold mb-2">🔥 Trending in <?= htmlspecialchars($book['General_Category']) ?></h2>
        <div class="grid gap-4">
          <?php
          $stmt = $pdo->prepare("SELECT * FROM books WHERE General_Category = ? AND TITLE != ? ORDER BY `Like` DESC LIMIT 3");
          $stmt->execute([$book['General_Category'], $book['TITLE']]);
          foreach ($stmt as $b): ?>
            <a href="book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="block border rounded hover:shadow-md p-3 bg-white">
              <strong><?= htmlspecialchars($b['TITLE']) ?></strong><br>
              👍 <?= $b['Like'] ?? 0 ?> likes<br>
              🔖 <?= htmlspecialchars($b['CALL NUMBER']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- ✍️ Other Works -->
      <section class="border rounded-lg p-4 bg-gray-50 shadow-sm">
        <h2 class="text-lg font-semibold mb-2">✍️ Other Works by <?= htmlspecialchars($book['AUTHOR']) ?></h2>
        <div class="grid gap-4">
          <?php
          $stmt = $pdo->prepare("SELECT * FROM books WHERE AUTHOR = ? AND TITLE != ? LIMIT 3");
          $stmt->execute([$book['AUTHOR'], $book['TITLE']]);
          foreach ($stmt as $b): ?>
            <a href="book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="block border rounded hover:shadow-md p-3 bg-white">
              <strong><?= htmlspecialchars($b['TITLE']) ?></strong><br>
              📖 <?= htmlspecialchars($b['CALL NUMBER']) ?><br>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    </aside>
  </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
