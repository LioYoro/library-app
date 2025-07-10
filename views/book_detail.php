<?php
session_start();
$sessionId = session_id();

// DB connection
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['book_title'])) {
    $action = $_POST['action']; // 'like' or 'dislike'
    $title = $_POST['book_title'];

    // Check for existing feedback
    $stmt = $pdo->prepare("SELECT feedback FROM book_feedback WHERE session_id = ? AND book_title = ?");
    $stmt->execute([$sessionId, $title]);
    $existing = $stmt->fetchColumn();

    if ($existing === $action) {
        // Same vote → remove
        $pdo->prepare("DELETE FROM book_feedback WHERE session_id = ? AND book_title = ?")
            ->execute([$sessionId, $title]);

        $col = $action === 'like' ? 'Like' : 'Dislike';
        $pdo->prepare("UPDATE books SET `$col` = `$col` - 1 WHERE TITLE = :title")
            ->execute(['title' => $title]);

    } elseif ($existing) {
        // Switch vote
        $pdo->prepare("UPDATE book_feedback SET feedback = ? WHERE session_id = ? AND book_title = ?")
            ->execute([$action, $sessionId, $title]);

        $fromCol = $existing === 'like' ? 'Like' : 'Dislike';
        $toCol   = $action === 'like' ? 'Like' : 'Dislike';

        $sql = "UPDATE books SET `$fromCol` = `$fromCol` - 1, `$toCol` = `$toCol` + 1 WHERE TITLE = :title";
        $pdo->prepare($sql)->execute(['title' => $title]);

    } else {
        // New vote
        $pdo->prepare("INSERT INTO book_feedback (session_id, book_title, feedback) VALUES (?, ?, ?)")
            ->execute([$sessionId, $title, $action]);

        $col = $action === 'like' ? 'Like' : 'Dislike';
        $pdo->prepare("UPDATE books SET `$col` = `$col` + 1 WHERE TITLE = :title")
            ->execute(['title' => $title]);
    }

    header("Location: book_detail.php?title=" . urlencode($title));
    exit;
}

// Fetch book data
$title = $_GET['title'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM books WHERE TITLE = ?");
$stmt->execute([$title]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if ($book) {
    $_SESSION['last_viewed_title'] = $book['TITLE'];
}

// Get user vote
$voteStmt = $pdo->prepare("SELECT feedback FROM book_feedback WHERE session_id = ? AND book_title = ?");
$voteStmt->execute([$sessionId, $title]);
$userVote = $voteStmt->fetchColumn();

require __DIR__ . '/header.php';
?>

<main class="max-w-3xl mx-auto px-4 py-8">
  <?php if (!$book): ?>
    <p class="text-red-600 font-semibold">Book not found.</p>
  <?php else: ?>
    <h1 class="text-2xl font-bold mb-2"><?= htmlspecialchars($book['TITLE']) ?></h1>
    <p class="text-sm text-gray-600 mb-4">
      👤 <strong>Author:</strong> <?= htmlspecialchars($book['AUTHOR']) ?><br>
      🔖 <strong>Call Number:</strong> <?= htmlspecialchars($book['CALL NUMBER']) ?><br>
      📚 <strong>Accession No.:</strong> <?= htmlspecialchars($book['ACCESSION NO.']) ?><br>
      🏷 <strong>General Category:</strong> <?= htmlspecialchars($book['General_Category']) ?><br>
      🔖 <strong>Sub-Category:</strong> <?= htmlspecialchars($book['Sub_Category']) ?><br>
      🧠 <strong>Keywords:</strong> <?= htmlspecialchars($book['KEYWORDS']) ?>
    </p>

    <div class="bg-gray-50 border rounded p-4 text-gray-800 mb-6">
      <h2 class="font-semibold mb-2">📘 Summary</h2>
      <p class="text-sm leading-relaxed whitespace-pre-line"><?= nl2br(htmlspecialchars($book['SUMMARY'])) ?></p>
    </div>

    <!-- Like / Dislike buttons -->
    <div class="flex gap-4">
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

    <hr class="my-8">

    <!-- 📌 Because you viewed [book] -->
    <section class="mb-6">
      <h2 class="text-lg font-semibold mb-2">📌 Because you viewed <em><?= htmlspecialchars($book['TITLE']) ?></em></h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php
        $stmt = $pdo->prepare("SELECT * FROM books WHERE TITLE != ? AND MATCH(KEYWORDS) AGAINST(?) LIMIT 3");
        $stmt->execute([$book['TITLE'], $book['KEYWORDS']]);
        foreach ($stmt as $b): ?>
          <a href="book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="block border rounded hover:shadow-md p-3 bg-gray-50">
            <strong><?= htmlspecialchars($b['TITLE']) ?></strong><br>
            👤 <?= htmlspecialchars($b['AUTHOR']) ?><br>
            🔖 <?= htmlspecialchars($b['CALL NUMBER']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- 🔥 Trending in Same Category -->
    <section class="mb-6">
      <h2 class="text-lg font-semibold mb-2">🔥 Trending in <?= htmlspecialchars($book['General_Category']) ?></h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php
        $stmt = $pdo->prepare("SELECT * FROM books WHERE General_Category = ? AND TITLE != ? ORDER BY `Like` DESC LIMIT 3");
        $stmt->execute([$book['General_Category'], $book['TITLE']]);
        foreach ($stmt as $b): ?>
          <a href="book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="block border rounded hover:shadow-md p-3 bg-yellow-50">
            <strong><?= htmlspecialchars($b['TITLE']) ?></strong><br>
            👍 <?= $b['Like'] ?? 0 ?> likes<br>
            🔖 <?= htmlspecialchars($b['CALL NUMBER']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ✍️ Other Works by Author -->
    <section class="mb-6">
      <h2 class="text-lg font-semibold mb-2">✍️ Other Works by <?= htmlspecialchars($book['AUTHOR']) ?></h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php
        $stmt = $pdo->prepare("SELECT * FROM books WHERE AUTHOR = ? AND TITLE != ? LIMIT 3");
        $stmt->execute([$book['AUTHOR'], $book['TITLE']]);
        foreach ($stmt as $b): ?>
          <a href="book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="block border rounded hover:shadow-md p-3 bg-purple-50">
            <strong><?= htmlspecialchars($b['TITLE']) ?></strong><br>
            📖 <?= htmlspecialchars($b['CALL NUMBER']) ?><br>
            🏷 <?= htmlspecialchars($b['Sub_Category']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

  <?php endif; ?>
</main>

<?php require __DIR__ . '/footer.php'; ?>
