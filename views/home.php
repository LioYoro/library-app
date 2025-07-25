<?php
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['advanced'])) {
    // Save the query temporarily and redirect via POST using a form
    $_SESSION['advanced_query'] = $_POST['search_query'] ?? '';
    echo '<form id="redirectForm" action="ask.php" method="post">';
    echo '<input type="hidden" name="question" value="' . htmlspecialchars($_SESSION['advanced_query']) . '">';
    echo '</form>';
    echo '<script>document.getElementById("redirectForm").submit();</script>';
    exit;
  }

  // Standard search logic can go here
  $standardSearch = $_POST['search_query'] ?? '';
  // Search filtering logic using $standardSearch
}

// Recommendation logic
$lastViewedTitle = $_SESSION['last_viewed_title'] ?? null;
$viewedBook = null;
$recommendations = [];
$trending = [];
$otherWorks = [];

if ($lastViewedTitle) {
  $stmt = $pdo->prepare("SELECT * FROM books WHERE TITLE = ? LIMIT 1");
  $stmt->execute([$lastViewedTitle]);
  $viewedBook = $stmt->fetch();

  if ($viewedBook) {
    $recStmt = $pdo->prepare("SELECT * FROM books WHERE (General_Category = :cat OR AUTHOR = :author) AND TITLE != :title LIMIT 3");
    $recStmt->execute([
      'cat' => $viewedBook['General_Category'],
      'author' => $viewedBook['AUTHOR'],
      'title' => $viewedBook['TITLE']
    ]);
    $recommendations = $recStmt->fetchAll();

    $trendStmt = $pdo->prepare("SELECT * FROM books WHERE General_Category = :cat ORDER BY `Like` DESC LIMIT 3");
    $trendStmt->execute(['cat' => $viewedBook['General_Category']]);
    $trending = $trendStmt->fetchAll();

    $authorStmt = $pdo->prepare("SELECT * FROM books WHERE AUTHOR = ? AND TITLE != ? LIMIT 2");
    $authorStmt->execute([$viewedBook['AUTHOR'], $viewedBook['TITLE']]);
    $otherWorks = $authorStmt->fetchAll();
  }
}
?>

<div class="max-w-[1200px] mx-auto px-4 md:px-6 py-4 space-y-6">

  <!-- Welcome and Carousel -->
  <div class="text-center py-6">
    <h1 class="text-2xl font-bold">Welcome to Kaban ng Hiyas Congressional Library</h1>
    <p class="text-gray-600">Explore academic knowledge, discover resources, and ask questions.</p>
  </div>

  <section class="border border-black rounded-md relative select-none">
    <div class="absolute top-1 left-1 text-black text-xl cursor-pointer"><i class="fas fa-volume-up"></i></div>
    <div class="flex items-center justify-between px-2 py-2">
      <button class="text-gray-400 hover:text-gray-700"><i class="fas fa-chevron-left text-2xl"></i></button>
      <div class="flex gap-4 max-w-[100%]">
        <img src="https://storage.googleapis.com/a1aa/image/8535a2ea-c68e-47a1-475e-c583ecea6076.jpg" class="object-contain max-h-[500px]" width="250" height="400" />
        <div class="text-base leading-tight max-w-[60%] text-gray-700">
          <p>Something Something Something</p>
          <p>Something Something Something</p>
          <p>Something Something Something</p>
          <p>Something Something Something</p>
        </div>
      </div>
      <button class="text-gray-400 hover:text-gray-700"><i class="fas fa-chevron-right text-2xl"></i></button>
    </div>
    <div class="flex justify-center gap-2 py-1">
      <span class="w-3 h-3 rounded-full bg-gray-700"></span>
      <span class="w-3 h-3 rounded-full bg-gray-300"></span>
    </div>
  </section>

  <!-- Main Content -->
  <div class="flex flex-col md:flex-row gap-6">

    <!-- Recommendations -->
    <aside class="w-full md:w-2/3 border border-black rounded-lg px-4 py-4 space-y-6 text-sm bg-white">

  <?php
  function getCommentCount($pdo, $title) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE book_title = ?");
    $stmt->execute([$title]);
    return (int)$stmt->fetchColumn();
  }
  ?>

  <?php if ($viewedBook): ?>

    <!-- Because you viewed -->
    <div>
      <h2 class="text-base font-bold mb-2"> Because you viewed <?= htmlspecialchars($viewedBook['TITLE']) ?></h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($recommendations as $b): ?>
          <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>"
             class="block border border-gray-300 rounded-lg px-3 py-2 hover:ring-2 hover:ring-blue-400 hover:bg-blue-50 transition cursor-pointer space-y-1">
            <div class="font-semibold"><?= htmlspecialchars($b['TITLE']) ?></div>
            <?php if (!empty($b['AUTHOR'])): ?><div>👤 <?= htmlspecialchars($b['AUTHOR']) ?></div><?php endif; ?>
            <?php if (!empty($b['CALL NUMBER'])): ?><div>🔖 <?= htmlspecialchars($b['CALL NUMBER']) ?></div><?php endif; ?>
            <div class="text-xs text-gray-600">👍 <?= $b['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $b['TITLE']) ?> Comments</div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 🔥 Trending in [Category] -->
    <div>
      <h2 class="text-base font-bold mb-2">Trending in <?= htmlspecialchars($viewedBook['General_Category']) ?></h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($trending as $t): ?>
          <a href="views/book_detail.php?title=<?= urlencode($t['TITLE']) ?>"
             class="block border border-gray-300 rounded-lg px-3 py-2 hover:ring-2 hover:ring-yellow-400 hover:bg-yellow-50 transition cursor-pointer space-y-1">
            <div class="font-semibold"><?= htmlspecialchars($t['TITLE']) ?></div>
            <?php if (!empty($t['CALL NUMBER'])): ?><div> <?= htmlspecialchars($t['CALL NUMBER']) ?></div><?php endif; ?>
            <div class="text-xs text-gray-600">👍 <?= $t['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $t['TITLE']) ?> Comments</div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ✍️ Other Works by Author -->
    <?php if (!empty($otherWorks)): ?>
    <div>
      <h2 class="text-base font-bold mb-2">✍️Other Works by <?= htmlspecialchars($viewedBook['AUTHOR']) ?></h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($otherWorks as $w): ?>
          <a href="views/book_detail.php?title=<?= urlencode($w['TITLE']) ?>"
             class="block border border-gray-300 rounded-lg px-3 py-2 hover:ring-2 hover:ring-purple-400 hover:bg-purple-50 transition cursor-pointer space-y-1">
            <div class="font-semibold"><?= htmlspecialchars($w['TITLE']) ?></div>
            <?php if (!empty($w['CALL NUMBER'])): ?><div> <?= htmlspecialchars($w['CALL NUMBER']) ?></div><?php endif; ?>
            <div class="text-xs text-gray-600">👍 <?= $w['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $w['TITLE']) ?> Comments</div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  <?php endif; ?>

  <!-- 🔥 Top Trending Books (Always shown) -->
  <div>
    <h2 class="text-lg font-semibold mb-3"> Top Trending Books</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
      <?php
      $stmt = $pdo->query("SELECT * FROM books ORDER BY `Like` DESC LIMIT 6");
      foreach ($stmt as $b): ?>
        <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>"
           class="block border border-gray-300 rounded-lg px-3 py-2 hover:ring-2 hover:ring-orange-400 hover:bg-orange-50 transition cursor-pointer space-y-1 text-center">
          <div class="font-semibold text-sm"><?= htmlspecialchars($b['TITLE']) ?></div>
          <?php if (!empty($b['AUTHOR'])): ?><div class="text-xs text-gray-600">👤 <?= htmlspecialchars($b['AUTHOR']) ?></div><?php endif; ?>
          <div class="text-xs text-gray-500">👍 <?= $b['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $b['TITLE']) ?> Comments</div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</aside>


    <!-- Sidebar -->
    <aside class="w-full md:w-1/3 flex flex-col gap-4 text-sm">

    <?php
if (isset($_SESSION['user_id'])):
  $userId = $_SESSION['user_id'];
  $likesPerPage = 5;
  $likePage = max((int)($_GET['like_page'] ?? 1), 1);
  $offset = ($likePage - 1) * $likesPerPage;

  // Fetch paginated liked books
  $likedStmt = $pdo->prepare("
    SELECT b.* FROM book_feedback bf
    JOIN books b ON b.TITLE = bf.book_title
    WHERE bf.user_id = ? AND bf.feedback = 'like'
    ORDER BY bf.id DESC
    LIMIT $likesPerPage OFFSET $offset
  ");
  $likedStmt->execute([$userId]);
  $likedBooks = $likedStmt->fetchAll();

  // Get total count for pagination
  $countStmt = $pdo->prepare("SELECT COUNT(*) FROM book_feedback WHERE user_id = ? AND feedback = 'like'");
  $countStmt->execute([$userId]);
  $totalLikes = (int)$countStmt->fetchColumn();
  $totalLikePages = max(1, ceil($totalLikes / $likesPerPage));
?>

  <!-- ✅ Your Likes Section -->
  <section class="border border-black rounded-lg max-h-[300px] overflow-y-auto scrollbar-thin p-3">
    <h3 class="font-semibold text-base mb-2">👍 Your Likes</h3>
    
    <?php if (empty($likedBooks)): ?>
      <p class="text-sm text-gray-600">You haven't liked any books yet.</p>
    <?php else: ?>
      <?php foreach ($likedBooks as $b): ?>
        <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="block mb-3 hover:bg-blue-50 transition rounded px-2 py-1">
          <div class="flex gap-2 items-center">
            <img src="https://storage.googleapis.com/a1aa/image/9512dff8-dde3-4812-5c14-1588768a98ca.jpg" class="w-10 h-14 object-cover border" alt="Book cover">
            <div>
              <div class="font-bold"><?= htmlspecialchars($b['TITLE']) ?></div>
              <div class="text-gray-500">Author: <?= htmlspecialchars($b['AUTHOR']) ?></div>
              <div class="text-gray-400 text-sm">Likes: <?= $b['Like'] ?></div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>

      <!-- 🔢 Pagination Links -->
      <div class="mt-2 flex gap-1 flex-wrap justify-center text-xs">
        <?php for ($i = 1; $i <= $totalLikePages; $i++): ?>
          <a href="?like_page=<?= $i ?>"
            class="px-2 py-1 rounded <?= $i === $likePage ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </section>

<?php endif; ?>



      <?php
$commentedPage = max((int)($_GET['commented_page'] ?? 1), 1);
$commentsPerPage = 5;
$commentOffset = ($commentedPage - 1) * $commentsPerPage;

// Fetch most commented books (paginated)
$commentedStmt = $pdo->prepare("
  SELECT b.*, COUNT(c.id) as comment_count
  FROM comments c
  JOIN books b ON b.TITLE = c.book_title
  GROUP BY c.book_title
  ORDER BY comment_count DESC
  LIMIT $commentsPerPage OFFSET $commentOffset
");
$commentedStmt->execute();
$topCommented = $commentedStmt->fetchAll();

// Get total for pagination
$totalCommentsStmt = $pdo->query("SELECT COUNT(DISTINCT book_title) FROM comments");
$totalCommentedBooks = (int)$totalCommentsStmt->fetchColumn();
$totalCommentedPages = max(1, ceil($totalCommentedBooks / $commentsPerPage));
?>

<!-- 💬 Top Commented Books -->
<section class="border border-black rounded-lg max-h-[300px] overflow-y-auto scrollbar-thin p-3">
  <h3 class="font-semibold text-base mb-2">💬 Top Commented Books</h3>
  <?php if (empty($topCommented)): ?>
    <p class="text-sm text-gray-600">No books have comments yet.</p>
  <?php else: ?>
    <?php foreach ($topCommented as $b): ?>
      <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="block mb-3 hover:bg-gray-50 transition rounded px-2 py-1">
        <div class="flex gap-2 items-center">
          <img src="https://storage.googleapis.com/a1aa/image/9512dff8-dde3-4812-5c14-1588768a98ca.jpg" class="w-10 h-14 object-cover border" alt="Book cover">
          <div>
            <div class="font-bold"><?= htmlspecialchars($b['TITLE']) ?></div>
            <div class="text-gray-500">Author: <?= htmlspecialchars($b['AUTHOR']) ?></div>
            <div class="text-gray-400 text-sm">💬 <?= $b['comment_count'] ?> comment<?= $b['comment_count'] == 1 ? '' : 's' ?></div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>

    <!-- 🔢 Pagination -->
    <div class="mt-2 flex gap-1 flex-wrap justify-center text-xs">
      <?php for ($i = 1; $i <= $totalCommentedPages; $i++): ?>
        <a href="?commented_page=<?= $i ?>"
           class="px-2 py-1 rounded <?= $i === $commentedPage ? 'bg-purple-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</section>


      <!-- External Resources -->
      <section class="border border-black rounded-lg px-3 py-2">
        <h3 class="font-semibold text-base mb-2">External Resources</h3>
        <ul class="list-disc list-inside space-y-1 text-gray-700">
          <li><a href="#" class="hover:underline text-blue-600">Online Journals</a></li>
          <li><a href="#" class="hover:underline text-blue-600">Educational Databases</a></li>
          <li><a href="#" class="hover:underline text-blue-600">E-book Platforms</a></li>
        </ul>
      </section>

      <!-- Library Guidelines -->
      <section class="border border-black rounded-lg px-3 py-2">
        <h3 class="font-semibold text-base mb-2">Library Guidelines</h3>
        <ul class="list-disc list-inside text-gray-700 space-y-1">
          <li>Maintain silence.</li>
          <li>Handle books with care.</li>
          <li>Return books on time.</li>
          <li>No food or drinks allowed.</li>
          <li>Respect fellow readers.</li>
        </ul>
      </section>
    </aside>
  </div>

  <!-- Visit Us + Map -->
  <div class="text-center mt-8 space-y-3">
  <h2 class="text-2xl font-bold">Visit Us!</h2>
  <div class="mx-auto max-w-full rounded-md overflow-hidden shadow-md" style="width:100%; max-width:600px; height:450px;">
    <iframe
      src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d2295.983182897871!2d121.03267637330832!3d14.578091391887153!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c84b4bd0a891%3A0x882a0fec03716ed3!2sKaban%20ng%20Hiyas%3A%20Cultural%20Center%2C%20Historical%20Museum%20and%20Convention%20Hall!5e0!3m2!1sen!2sph!4v1753438230250!5m2!1sen!2sph"
      width="100%"
      height="100%"
      style="border:0;"
      allowfullscreen=""
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</div>
