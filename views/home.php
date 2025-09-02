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
$debugInfo = []; // Add debug information

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT major, strand, education_level FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Add debug information
        $debugInfo['user_data'] = $user;
        $debugInfo['user_id'] = $_SESSION['user_id'];
        
        // Prepare the data to send to Flask API
        $postData = [
            'user_id' => $_SESSION['user_id'],
            'education_level' => $user['education_level'],
            'major' => $user['major'],
            'strand' => $user['strand']
        ];
        
        $debugInfo['post_data'] = $postData;
        
        $flask_api_url = 'http://127.0.0.1:5001/recommend_by_field';
        $ch = curl_init($flask_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($postData))
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Add timeout
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $debugInfo['http_code'] = $httpCode;
        $debugInfo['raw_response'] = $response;
        
        if (curl_errno($ch)) {
            $debugInfo['curl_error'] = curl_error($ch);
            echo '<div style="color: red; padding: 10px; margin: 10px; border: 1px solid red; background: #ffe6e6;">cURL Error: ' . curl_error($ch) . '</div>';
            error_log("cURL Error for Flask API: " . curl_error($ch));
            $recommendedBooks = [];
        } else {
            $data = json_decode($response, true);
            $debugInfo['decoded_data'] = $data;
            $debugInfo['json_error'] = json_last_error_msg();
            
            // Check if 'recommendations' key exists and is an array
            if (json_last_error() === JSON_ERROR_NONE && isset($data['recommendations']) && is_array($data['recommendations'])) {
                $recommendedBooks = $data['recommendations'];
                $debugInfo['recommendation_count'] = count($recommendedBooks);
            } else {
                echo '<div style="color: red; padding: 10px; margin: 10px; border: 1px solid red; background: #ffe6e6;">
                    <strong>Flask API Issue:</strong><br>
                    HTTP Code: ' . $httpCode . '<br>
                    Response: ' . htmlspecialchars($response) . '<br>
                    JSON Error: ' . json_last_error_msg() . '
                </div>';
                error_log("Flask response issue for user " . $_SESSION['user_id'] . ". Response: " . $response . ". JSON Error: " . json_last_error_msg());
                $recommendedBooks = [];
            }
        }
        curl_close($ch);
    } else {
        $debugInfo['error'] = "User not found in database";
        error_log("User with ID " . $_SESSION['user_id'] . " not found in database for recommendations.");
    }
} else {
    $debugInfo['error'] = "User not logged in";
    error_log("user_id not set. Cannot fetch field-based recommendations.");
}

// Initialize variables for liked books section
$likedBooks = [];
$totalLikes = 0;
$totalLikePages = 1;
$likePage = 1;

if (isset($_SESSION['user_id'])) {
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
}

// Initialize variables for top commented books section
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
<!-- WRAP EVERYTHING IN A CONTAINER TO PREVENT LAYOUT ISSUES -->
<div style="width: 100%; display: block; position: relative;">
    <div class="text-center py-6">
        <h1 class="text-2xl font-bold">Welcome to Kaban ng Hiyas Congressional Library</h1>
        <p class="text-gray-600">Explore academic knowledge, discover resources, and ask questions.</p>
    </div>
    
    <!-- DEBUG INFORMATION (Remove this after fixing) -->
    <!-- 
    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
        <div style="background: #f0f0f0; padding: 15px; margin: 10px; border: 1px solid #ccc; font-family: monospace; font-size: 12px;">
            <h3>Debug Information:</h3>
            <pre><?= htmlspecialchars(print_r($debugInfo, true)) ?></pre>
        </div>
    <?php endif; ?> --> 
    
    
    <div style="margin-top: 10px;">
        <form action="admin/index.php" method="get">
            <button type="submit" style="padding: 10px 20px; background-color: #1d4ed8; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Go to Admin Panel
            </button>
        </form>
        <!-- Add debug link -->
        <!--
        <a href="?debug=1" style="margin-left: 10px; padding: 10px 20px; background-color: #dc2626; color: white; text-decoration: none; border-radius: 5px;">
            Show Debug Info
        </a>
        -->
    </div>

    <section class="custom-slider-section">
        <div class="megaphone-icon">
            <ion-icon name="megaphone"></ion-icon>
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

    <!-- MAIN CONTENT SECTION -->
    <div style="display: block; width: 100%; margin-bottom: 40px;">
        <div class="flex flex-col md:flex-row gap-6" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">

         <!-- LEFT Sidebar -->
<div class="w-full md:w-1/5 border border-black rounded-lg px-4 py-4 space-y-6 text-sm bg-white">
    <h3 class="aside-title text-center font-bold">E-Resources</h3>

    <div class="space-y-6 text-center">
        <!-- First Image + Name -->
        <a href="https://web.nlp.gov.ph/" target="_blank">
        <div class="group cursor-pointer border rounded-lg p-2 transition-all duration-300 transform hover:scale-105 hover:bg-sky-100">
            <img src="EResources/NLPLogo.png"
                alt="National Library of the Philippines"
                class="mx-auto rounded-lg shadow-md transition-transform duration-300 transform group-hover:scale-110">
            <p class="mt-2 font-semibold text-gray-900 text-center group-hover:text-black">National Library of the Philippines</p>
        </div>
        </a>

        <!-- Second Image + Name -->
        <a href="https://eportal.nlp.gov.ph/" target="_blank">
  <div class="group cursor-pointer border rounded-lg p-2 transition-all duration-300 transform hover:scale-105 hover:bg-sky-100">
      <img src="EResources/NLPLogo.png"
           alt="NLP E-Portal"
           class="mx-auto rounded-lg shadow-md transition-transform duration-300 transform group-hover:scale-110">
      <p class="mt-2 font-semibold text-gray-900 text-center group-hover:text-black">NLP E-Portal</p>
  </div>
</a>

    </div>
</div>

            <!-- Main Content Panel -->
            <aside class="w-full md:w-5/6 border border-black rounded-lg px-4 py-4 space-y-6 text-sm bg-white">
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
                        <h2 class="section-title">Because you viewed: <?= htmlspecialchars($viewedBook['TITLE']) ?></h2>
                        <div class="book-grid">
                            <?php foreach ($recommendations as $b): ?>
                               <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>"class="book-card blue flex items-center gap-4 p-2">
                                <img src="EResources/Noimage.jpg" 
                                alt="Book cover" 
                                class="w-14 h-20 object-cover rounded-lg shadow-md transform transition duration-200 hover:scale-110 flex-shrink-0">
                                    <div class="flex flex-col">
                                    <div class="book-title"><?= htmlspecialchars($b['TITLE']) ?></div>
                                    <div class="book-meta"><?php if (!empty($b['AUTHOR'])): ?><div><strong><em>Author: </em></strong><?= htmlspecialchars($b['AUTHOR']) ?></div><?php endif; ?></div>
                                    <div class="book-meta"><?php if (!empty($b['CALL NUMBER'])): ?><div><strong><em>Call Number: </em></strong><?= htmlspecialchars($b['CALL NUMBER']) ?></div><?php endif; ?></div>
                                    <div class="book-meta"><ion-icon name="thumbs-up"></ion-icon> <?= $b['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $b['TITLE']) ?> Comments</div>
                                </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Trending in Category -->
                    <div class="section-block">
                        <h2 class="section-title">Trending in <?= htmlspecialchars($viewedBook['General_Category']) ?></h2>
                        <div class="book-grid">
                            <?php foreach ($trending as $t): ?>
                                <a href="views/book_detail.php?title=<?= urlencode($t['TITLE']) ?>" class="book-card yellow flex items-center gap-4 p-2">
                                    <img src="EResources/Noimage.jpg" 
                                    alt="Book cover" 
                                    class="w-14 h-20 object-cover rounded-lg shadow-md transform transition duration-200 hover:scale-110 flex-shrink-0">
                                    <div class="flex flex-col">
                                    <div class="book-title"><?= htmlspecialchars($t['TITLE']) ?></div>
                                    <?php if (!empty($t['CALL NUMBER'])): ?><div><?= htmlspecialchars($t['CALL NUMBER']) ?></div><?php endif; ?>
                                    <div class="book-meta"><ion-icon name="thumbs-up"></ion-icon> <?= $t['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $t['TITLE']) ?> Comments</div>
                                </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Other Works -->
                    <?php if (!empty($otherWorks)): ?>
                        <div class="section-block">
                            <h2 class="section-title">Other Works by <?= htmlspecialchars($viewedBook['AUTHOR']) ?></h2>
                            <div class="book-grid">
                                <?php foreach ($otherWorks as $w): ?>
                                    <a href="views/book_detail.php?title=<?= urlencode($w['TITLE']) ?>" class="book-card purple flex items-center gap-4 p-2">
                                    <img src="EResources/Noimage.jpg" 
                                    alt="Book cover" 
                                    class="w-14 h-20 object-cover rounded-lg shadow-md transform transition duration-200 hover:scale-110 flex-shrink-0">
                                        <div class="flex flex-col">
                                        <div class="book-title"><?= htmlspecialchars($w['TITLE']) ?></div>
                                        <?php if (!empty($w['CALL NUMBER'])): ?><div><?= htmlspecialchars($w['CALL NUMBER']) ?></div><?php endif; ?>
                                        <div class="book-meta"><ion-icon name="thumbs-up"></ion-icon> <?= $w['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $w['TITLE']) ?> Comments</div>
                                    </div>
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
                            <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="book-card orange flex items-center gap-4 p-2">
                                    <img src="EResources/Noimage.jpg" 
                                    alt="Book cover" 
                                    class="w-14 h-20 object-cover rounded-lg shadow-md transform transition duration-200 hover:scale-110 flex-shrink-0">
                                <div class="flex flex-col">
                                <div class="book-title small"><?= htmlspecialchars($b['TITLE']) ?></div>
                                <?php if (!empty($b['AUTHOR'])): ?><div class="book-meta"><strong><em>Author: </em></strong><?= htmlspecialchars($b['AUTHOR']) ?></div><?php endif; ?>
                                <div class="book-meta"><ion-icon name="thumbs-up"></ion-icon> <?= $b['Like'] ?? 0 ?> Likes • 💬 <?= getCommentCount($pdo, $b['TITLE']) ?> Comments</div>
                            </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
            
            <!-- RIGHT Sidebar (Aside) -->
            <div class="w-full md:w-2/5 border border-black rounded-lg px-4 py-4 space-y-6 text-sm bg-white">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <section class="aside-box" id="liked-section">
                        <h3 class="aside-title">Your Likes</h3>
                        <?php if (empty($likedBooks)): ?>
                            <p class="aside-empty">You haven't liked any books yet.</p>
                        <?php else: ?>
                            <?php foreach ($likedBooks as $b): ?>
                                <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="aside-link">
                                    <div class="aside-entry">
                                        <img src="<?= htmlspecialchars($b['COVER_IMAGE'] ?? 'https://storage.googleapis.com/a1aa/image/9512dff8-dde3-4812-5c14-1588768a98ca.jpg') ?>" class="aside-image" alt="Book cover">
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
                    <h3 class="aside-title"> Recommended for Your Field</h3>
                    <?php if (empty($recommendedBooks)): ?>
                        <p class="aside-empty">No recommendations available for your field.</p>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <p class="aside-empty" style="font-size: 11px; color: #666;">
                                Debug: User ID <?= $_SESSION['user_id'] ?> | 
                                <a href="?debug=1" style="color: blue;">Show Debug Info</a>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($recommendedBooks as $b): ?>
                            <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="aside-link">
                                <div class="aside-entry">
                                    <img src="<?= htmlspecialchars($b['COVER_IMAGE'] ?? 'https://storage.googleapis.com/a1aa/image/9512dff8-dde3-4812-5c14-1588768a98ca.jpg') ?>" class="aside-image" alt="Book cover">
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
                    <h3 class="aside-title">Top Commented Books</h3>
                    <?php if (empty($topCommented)): ?>
                        <p class="aside-empty">No books have comments yet.</p>
                    <?php else: ?>
                        <?php foreach ($topCommented as $b): ?>
                            <a href="views/book_detail.php?title=<?= urlencode($b['TITLE']) ?>" class="aside-link">
                                <div class="aside-entry">
                                    <img src="https://storage.googleapis.com/a1aa/image/9512dff8-dde3-4812-5c14-1588768a98ca.jpg" class="aside-image" alt="Book cover">
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
            </div>
        </div>
    </div>

    <!-- Google Map Section -->
    <section class="google-map" style="width: 100%; display: block; margin-top: 40px;">
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
        </div>
    </section>
</div>
<!-- END MAIN CONTAINER -->

<script src="js/comment.js"></script>
<script src="js/login.js"></script>
<script src="js/pagination.js"></script>
