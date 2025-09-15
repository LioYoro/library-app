<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include DB connection
require_once __DIR__ . '/includes/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-red-600 font-semibold p-4'>You must be logged in to use Advanced Search.</p>";
    exit;
}

$userId = $_SESSION['user_id'];
$limit = 5;
$today = date('Y-m-d');

// Check daily limit
$stmt = $conn->prepare("SELECT count FROM advanced_search_log WHERE user_id = ? AND search_date = ?");
$stmt->execute([$userId, $today]);
$row = $stmt->fetch();

if ($row) {
    if ($row['count'] >= $limit) {
        echo "<p class='text-red-600 font-semibold p-4'>You have reached the daily limit ($limit) for Advanced Search.</p>";
        exit;
    } else {
        $stmt = $conn->prepare("UPDATE advanced_search_log SET count = count + 1 WHERE user_id = ? AND search_date = ?");
        $stmt->execute([$userId, $today]);
    }
} else {
    $stmt = $conn->prepare("INSERT INTO advanced_search_log (user_id, search_date, count) VALUES (?, ?, 1)");
    $stmt->execute([$userId, $today]);
}

// ---------------------
// Ask logic
// ---------------------
require_once __DIR__ . '/views/header.php';

$answer = '';
$mainBook = [];
$relatedBooks = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question'])) {
    $question = trim($_POST['question']);
    if (!empty($question)) {
        $data = json_encode(['question' => $question]);

        // Call AI
        $ch = curl_init('http://127.0.0.1:5001/api/chat');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $decoded = json_decode($response, true);
            $answer = $decoded['answer'] ?? 'No answer.';
            $mainBook = $decoded['main'] ?? null;
            $relatedBooks = $decoded['related'] ?? [];

            // -----------------
            // MAIN BOOK FALLBACK
            // -----------------
            function normalizeTitle($str) {
                // replace curly quotes with nothing or standard quote
                $search = ["’", "‘", "“", "”"];
                $replace = ["", "", '"', '"'];
                $str = str_replace($search, $replace, $str);
                // lowercase for case-insensitive match
                return mb_strtolower($str);
            }

            if (!$mainBook) {
                // normalize the question
                $normalizedQuestion = normalizeTitle($question);

                $stmt = $conn->prepare("
                    SELECT * FROM books 
                    WHERE LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TITLE, '’',''), '‘',''), '“',''), '”','')) LIKE ? 
                    LIMIT 1
                ");
                $stmt->execute(['%' . $normalizedQuestion . '%']);
                $dbBook = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($dbBook) {
                    $mainBook = [
                        'title' => $dbBook['TITLE'],
                        'author' => $dbBook['AUTHOR'],
                        'call_no' => $dbBook['CALL NUMBER'],
                        'short_summary' => $dbBook['SUMMARY'],
                        'cover_image_url' => !empty($dbBook['cover_image_url']) ? $dbBook['cover_image_url'] : 'assets/Noimage.jpg'
                    ];
                }
            } else {
                // normalize the AI-returned main book title
                $normalizedMain = normalizeTitle($mainBook['title']);

                $stmt = $conn->prepare("
                    SELECT * FROM books 
                    WHERE LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TITLE, '’',''), '‘',''), '“',''), '”','')) LIKE ? 
                    LIMIT 1
                ");
                $stmt->execute(['%' . $normalizedMain . '%']);
                $dbBook = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($dbBook) {
                    $mainBook['title'] = $dbBook['TITLE'];
                    $mainBook['author'] = $dbBook['AUTHOR'];
                    $mainBook['call_no'] = $dbBook['CALL NUMBER'];
                    $mainBook['short_summary'] = $dbBook['SUMMARY'];
                    $mainBook['cover_image_url'] = !empty($dbBook['cover_image_url']) ? $dbBook['cover_image_url'] : 'assets/Noimage.jpg';
                }
            }


            // -----------------
            // RELATED BOOKS FALLBACK
            // -----------------
            foreach ($relatedBooks as &$related) {
                $stmt = $conn->prepare("SELECT * FROM books WHERE TITLE = ? OR TITLE LIKE ? LIMIT 1");
                $stmt->execute([$related['title'], '%' . $related['title'] . '%']);
                $dbBook = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($dbBook) {
                    $related['title'] = $dbBook['TITLE'];
                    $related['author'] = $dbBook['AUTHOR'];
                    $related['call_no'] = $dbBook['CALL NUMBER'];
                    $related['short'] = $dbBook['SUMMARY'];
                    $related['cover_image_url'] = !empty($dbBook['cover_image_url']) ? $dbBook['cover_image_url'] : 'assets/Noimage.jpg';
                } else {
                    $related = null;
                }
            }
            unset($related);
            $relatedBooks = array_filter($relatedBooks);

            // If still empty, call /api/recommend
            if (empty($relatedBooks) && !empty($mainBook)) {
                $ch2 = curl_init('http://127.0.0.1:5001/api/recommend');
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_POST, true);
                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(['title' => $mainBook['title']]));
                curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                $resp2 = curl_exec($ch2);
                curl_close($ch2);

                if ($resp2) {
                    $rel = json_decode($resp2, true);
                    $relatedBooks = $rel['recommended'] ?? [];

                    // Sync covers from DB
                    foreach ($relatedBooks as &$r) {
                        $stmt = $conn->prepare("SELECT * FROM books WHERE TITLE = ? OR TITLE LIKE ? LIMIT 1");
                        $stmt->execute([$r['title'], '%' . $r['title'] . '%']);
                        $dbBook = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($dbBook) {
                            $r['title'] = $dbBook['TITLE'];
                            $r['author'] = $dbBook['AUTHOR'];
                            $r['call_no'] = $dbBook['CALL NUMBER'];
                            $r['short'] = $dbBook['SUMMARY'];
                            $r['cover_image_url'] = !empty($dbBook['cover_image_url']) ? $dbBook['cover_image_url'] : 'assets/Noimage.jpg';
                        } else {
                            $r = null;
                        }
                    }
                    unset($r);
                    $relatedBooks = array_filter($relatedBooks);
                }
            }
        } else {
            $answer = 'Error communicating with the AI.';
        }
    }
}
?>

<div class="max-w-4xl mx-auto py-6 px-4">
  <h1 class="text-xl font-bold mb-4">🤖 Ask the Library Bot</h1>

  <div id="loading" class="text-blue-600 mb-4 hidden">
    <i class="fas fa-spinner fa-spin mr-2"></i> Getting answer from the Library Bot...
  </div>

  <?php if (!empty($answer)): ?>
    <div id="bot-answer" class="bg-gray-100 border border-gray-300 p-4 rounded mb-4">
      <h2 class="text-lg font-semibold mb-2">🤖 Bot Answer:</h2>
      <p class="text-gray-800 whitespace-pre-line"><?= nl2br(htmlspecialchars($answer)) ?></p>
    </div>

    <?php if (!empty($mainBook)): ?>
      <div class="mb-6 p-4 border border-blue-300 bg-blue-50 rounded">
        <h3 class="font-semibold text-blue-700 mb-2">📘 Main Recommendation:</h3>
        <!-- Fixed book detail link path -->
        <a href="views/book_detail.php?title=<?= urlencode($mainBook['title']) ?>" class="flex gap-4 p-3 bg-white rounded border shadow hover:shadow-md transition">
          <!-- Fixed image src path and alt text -->
          <img src="<?= htmlspecialchars($mainBook['cover_image_url']) ?>" 
               class="w-24 h-32 object-cover rounded shadow" 
               alt="<?= htmlspecialchars($mainBook['title']) ?> cover">
          <div>
            <div class="text-lg font-semibold text-blue-800"><?= htmlspecialchars($mainBook['title']) ?></div>
            <div class="text-sm text-gray-600 mt-1">
              👤 <?= htmlspecialchars($mainBook['author']) ?><br>
              🔖 <?= htmlspecialchars($mainBook['call_no']) ?><br>
              📄 <?= htmlspecialchars($mainBook['short_summary']) ?>
            </div>
          </div>
        </a>
      </div>
    <?php endif; ?>

    <?php if (!empty($relatedBooks)): ?>
      <div class="mb-6 p-4 border border-green-300 bg-green-50 rounded">
        <h3 class="font-semibold text-green-700 mb-3">📚 Related Books:</h3>
        <div class="grid gap-4">
          <?php foreach ($relatedBooks as $related): ?>
            <!-- Fixed book detail link path -->
            <a href="views/book_detail.php?title=<?= urlencode($related['title']) ?>" class="flex gap-3 items-center border rounded hover:shadow-md p-3 bg-white">
              <!-- Fixed image src and alt text -->
              <img src="<?= htmlspecialchars($related['cover_image_url'] ?? 'assets/Noimage.jpg') ?>" 
                   class="w-12 h-16 object-cover rounded shadow" 
                   alt="<?= htmlspecialchars($related['title']) ?> cover">
              <div>
                <strong class="text-green-800"><?= htmlspecialchars($related['title']) ?></strong><br>
                👤 <?= htmlspecialchars($related['author']) ?><br>
                🔖 <?= htmlspecialchars($related['call_no']) ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<script>
  function showLoading() {
    document.getElementById('loading').classList.remove('hidden');
  }
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
