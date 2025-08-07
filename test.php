<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$recommendations = []; // Used for "Because you viewed"
$trending = [];
$otherWorks = [];

// Fetch recommendations based on last viewed book (local DB logic)
if ($lastViewedTitle) { // Only attempt if a book was viewed
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

        // Trending for viewed book's category
        $trendStmt = $pdo->prepare("SELECT * FROM books WHERE General_Category = :cat ORDER BY `Like` DESC LIMIT 3");
        $trendStmt->execute(['cat' => $viewedBook['General_Category']]);
        $trending = $trendStmt->fetchAll();

        // Other Works by Author for viewed book's author
        $authorStmt = $pdo->prepare("SELECT * FROM books WHERE AUTHOR = ? AND TITLE != ? LIMIT 2");
        $authorStmt->execute([$viewedBook['AUTHOR'], $viewedBook['TITLE']]);
        $otherWorks = $authorStmt->fetchAll();
    }
}

$recommendedBooks = [];

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT major, strand, education_level FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $postData = ['user_id' => $_SESSION['user_id']];
        
        $flask_api_url = 'http://127.0.0.1:5001/recommend_by_field';

        $ch = curl_init($flask_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($postData))
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            echo '<div style="color: red;">cURL Error: ' . curl_error($ch) . '</div>';
            error_log("cURL Error for Flask API: " . curl_error($ch));
            $recommendedBooks = [];
        } else {
            $data = json_decode($response, true);

            // CRITICAL FIX: Check if 'recommendations' key exists and is an array
            if (json_last_error() === JSON_ERROR_NONE && isset($data['recommendations']) && is_array($data['recommendations'])) {
                $recommendedBooks = $data['recommendations']; // Correctly assign the array of books
            } else {
                echo '<div style="color: red;">Failed to decode Flask JSON or "recommendations" key missing.</div>';
                error_log("Flask response issue for user " . $_SESSION['user_id'] . ". Response: " . $response . ". JSON Error: " . json_last_error_msg());
                $recommendedBooks = [];
            }
        }
        curl_close($ch);
    } else {
        error_log("User with ID " . $_SESSION['user_id'] . " not found in database for recommendations.");
    }
} else {
    error_log("user_id not set. Cannot fetch field-based recommendations.");
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaban ng Hiyas Congressional Library</title>
    <!-- <link rel="stylesheet" href="css/style.css">  -->
    <link rel="stylesheet" href="css/map.css">
    <link rel="stylesheet" href="css/announcement.css">
    <link rel="stylesheet" href="css/booksection.css">
    <link rel="stylesheet" href="css/info.css">
    <link rel="stylesheet" href="css/comment.css">
    <link rel="stylesheet" href="css/logintest.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>

   <?php include 'includes/headertest.php'; ?> 
<body style="margin: 0; padding-top: 80px; font-family: sans-serif;">


<?php include 'login/logintest.php'; ?> <!-- This will include the login popup -->

<div class="max-w-[1200px] mx-auto px-4 md:px-6 py-4 space-y-6">

    <div class="text-center py-6">
        <h1 class="text-2xl font-bold">Welcome to Kaban ng Hiyas Congressional Library</h1>
        <p class="text-gray-600">Explore academic knowledge, discover resources, and ask questions.</p>
    </div>

    <div style="margin-top: 20px;">
    <form action="admin/index.php" method="get">
        <button type="submit" style="padding: 10px 20px; background-color: #1d4ed8; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Go to Admin Panel
        </button>
    </form>
</div>

<div style="margin-top: 20px;">
    <form action="index.php" method="get">
        <button type="submit" style="padding: 10px 20px; background-color: #1d4ed8; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Go to Old Home
        </button>
    </form>
</div>



    <section class="custom-slider-section">
  <div class="volume-icon">
    <i class="fas fa-volume-up"></i>
  </div>
  <div class="slider-content">
    <button class="slider-nav">
      <i class="fas fa-chevron-left"></i>
    </button>

    <div class="slider-main">
      <img src="https://storage.googleapis.com/a1aa/image/8535a2ea-c68e-47a1-475e-c583ecea6076.jpg" class="slider-image" />
      <div class="slider-text">
        <p>Something Something Something</p>
        <p>Something Something Something</p>
        <p>Something Something Something</p>
        <p>Something Something Something</p>
      </div>
    </div>

    <button class="slider-nav">
      <i class="fas fa-chevron-right"></i>
    </button>
  </div>

  <div class="slider-dots">
    <span class="dot active"></span>
    <span class="dot"></span>
  </div>
</section>

<div class="flex flex-col md:flex-row gap-6">
  <!-- Main Content Panel -->
  <aside class="w-full md:w-2/3 border border-black rounded-lg px-4 py-4 space-y-6 text-sm bg-white">
    <?php
    function getCommentCount($pdo, $title) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE book_title = ?");
        $stmt->execute([$title]);
        return (int)$stmt->fetchColumn();
    }
    ?>

    <?php if ($viewedBook): ?>
      <!-- Because You Viewed -->
      <div class="section-block">
        <h2 class="section-title">Because you viewed <?= htmlspecialchars($viewedBook['TITLE']) ?></h2>
        <div class="book-grid">
          <?php foreach ($recommendations as $b): ?>
            <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="book-card blue">
              <div class="book-title"><?= htmlspecialchars($b['TITLE']) ?></div>
              <?php if (!empty($b['AUTHOR'])): ?><div>👤 <?= htmlspecialchars($b['AUTHOR']) ?></div><?php endif; ?>
              <?php if (!empty($b['CALL NUMBER'])): ?><div>🔖 <?= htmlspecialchars($b['CALL NUMBER']) ?></div><?php endif; ?>
              <div class="book-meta">👍 <?= $b['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $b['TITLE']) ?> Comments</div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Trending in Category -->
      <div class="section-block">
        <h2 class="section-title">Trending in <?= htmlspecialchars($viewedBook['General_Category']) ?></h2>
        <div class="book-grid">
          <?php foreach ($trending as $t): ?>
            <a href="views/book_detail.php?title=<?= urlencode($t['TITLE']) ?>" class="book-card yellow">
              <div class="book-title"><?= htmlspecialchars($t['TITLE']) ?></div>
              <?php if (!empty($t['CALL NUMBER'])): ?><div><?= htmlspecialchars($t['CALL NUMBER']) ?></div><?php endif; ?>
              <div class="book-meta">👍 <?= $t['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $t['TITLE']) ?> Comments</div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Other Works -->
      <?php if (!empty($otherWorks)): ?>
        <div class="section-block">
          <h2 class="section-title">✍️ Other Works by <?= htmlspecialchars($viewedBook['AUTHOR']) ?></h2>
          <div class="book-grid">
            <?php foreach ($otherWorks as $w): ?>
              <a href="views/book_detail.php?title=<?= urlencode($w['TITLE']) ?>" class="book-card purple">
                <div class="book-title"><?= htmlspecialchars($w['TITLE']) ?></div>
                <?php if (!empty($w['CALL NUMBER'])): ?><div><?= htmlspecialchars($w['CALL NUMBER']) ?></div><?php endif; ?>
                <div class="book-meta">👍 <?= $w['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $w['TITLE']) ?> Comments</div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Top Trending Books -->
    <div class="section-block">
      <h2 class="section-title">Top Trending Books</h2>
      <div class="book-grid top-trending">
        <?php
        $stmt = $pdo->query("SELECT * FROM books ORDER BY `Like` DESC LIMIT 6");
        foreach ($stmt as $b): ?>
          <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="book-card orange">
            <div class="book-title small"><?= htmlspecialchars($b['TITLE']) ?></div>
            <?php if (!empty($b['AUTHOR'])): ?><div class="book-meta">👤 <?= htmlspecialchars($b['AUTHOR']) ?></div><?php endif; ?>
            <div class="book-meta">👍 <?= $b['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $b['TITLE']) ?> Comments</div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>

  <!-- RIGHT Sidebar (Aside) -->
  <div class="w-full md:w-1/3 border border-black rounded-lg px-4 py-4 space-y-6 text-sm bg-white">
    <?php if (isset($_SESSION['user_id'])): ?>
      <section class="aside-box" id="liked-section">
        <h3 class="aside-title">👍 Your Likes</h3>
        <?php if (empty($likedBooks)): ?>
          <p class="aside-empty">You haven't liked any books yet.</p>
        <?php else: ?>
          <?php foreach ($likedBooks as $b): ?>
            <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="aside-link">
              <div class="aside-entry">
                <img src="<?= htmlspecialchars($b['COVER_IMAGE'] ?? 'default.jpg') ?>" class="aside-image" alt="Book cover">
                <div>
                  <div class="aside-entry-title"><?= htmlspecialchars($b['TITLE']) ?></div>
                  <div class="aside-author">Author: <?= htmlspecialchars($b['AUTHOR']) ?></div>
                  <div class="aside-meta">Likes: <?= $b['Like'] ?></div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
          <div class="aside-pagination" id="like-pagination">
            <?php for ($i = 1; $i <= $totalLikePages; $i++): ?>
              <a href="?like_page=<?= $i ?>" class="page-btn <?= $i === $likePage ? 'active' : '' ?>"> <?= $i ?> </a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <!-- Recommended -->
    <section class="aside-box" id="recommended-section">
      <h3 class="aside-title">🎓 Recommended for Your Field</h3>
      <?php if (empty($recommendedBooks)): ?>
        <p class="aside-empty">No recommendations available for your field.</p>
      <?php else: ?>
        <?php foreach ($recommendedBooks as $b): ?>
          <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="aside-link">
            <div class="aside-entry">
              <img src="<?= htmlspecialchars($b['COVER_IMAGE'] ?? 'default.jpg') ?>" class="aside-image" alt="Book cover">
              <div>
                <div class="aside-entry-title"><?= htmlspecialchars($b['TITLE']) ?></div>
                <div class="aside-author">Author: <?= htmlspecialchars($b['AUTHOR'] ?? '') ?></div>
                <div class="aside-meta">Category: <?= htmlspecialchars($b['General_Category']) ?></div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <!-- Commented -->
    <section class="aside-box" id="commented-section">
      <h3 class="aside-title">💬 Top Commented Books</h3>
      <?php if (empty($topCommented)): ?>
        <p class="aside-empty">No books have comments yet.</p>
      <?php else: ?>
        <?php foreach ($topCommented as $b): ?>
          <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="aside-link">
            <div class="aside-entry">
              <img src="default.jpg" class="aside-image" alt="Book cover">
              <div>
                <div class="aside-entry-title"><?= htmlspecialchars($b['TITLE']) ?></div>
                <div class="aside-author">Author: <?= htmlspecialchars($b['AUTHOR']) ?></div>
                <div class="aside-meta">💬 <?= $b['comment_count'] ?> comment<?= $b['comment_count'] == 1 ? '' : 's' ?></div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
        <div class="aside-pagination" id="comment-pagination">
          <?php for ($i = 1; $i <= $totalCommentedPages; $i++): ?>
            <a href="?commented_page=<?= $i ?>" class="page-btn <?= $i === $commentedPage ? 'active' : '' ?>"> <?= $i ?> </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Info Boxes -->
    <section class="info-box">
      <h3 class="info-title">External Resources</h3>
      <ul class="info-list">
        <li><a href="#" class="info-link">Online Journals</a></li>
        <li><a href="#" class="info-link">Educational Databases</a></li>
        <li><a href="#" class="info-link">E-book Platforms</a></li>
      </ul>
    </section>

    <section class="info-box">
      <h3 class="info-title">Library Guidelines</h3>
      <ul class="info-list">
        <li>Maintain silence.</li>
        <li>Handle books with care.</li>
        <li>Return books on time.</li>
        <li>No food or drinks allowed.</li>
        <li>Respect fellow readers.</li>
      </ul>
    </section>
  </div>
</div>


       


      <section class="google-map">
      <div class="google-map-container">
    <h2 class="section-title">Visit Us!</h2>
    <div class="map-wrapper">
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d2295.983182897871!2d121.03267637330832!3d14.578091391887153!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c84b4bd0a891%3A0x882a0fec03716ed3!2sKaban%20ng%20Hiyas%3A%20Cultural%20Center%2C%20Historical%20Museum%20and%20Convention%20Hall!5e0!3m2!1sen!2sph!4v1753438230250!5m2!1sen!2sph"
        class="map-frame"
        allowfullscreen=""
        loading="lazy">
      </iframe>
  </div>
  </section>
</div>
  
<script src="js/comment.js"></script>
<script src="js/login.js"></script>
<!-- cute ang pokdakodasok -->
</body>
</html>