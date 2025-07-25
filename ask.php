<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/views/header.php';


$answer = '';
$mainBook = [];
$relatedBooks = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question'])) {
    $question = trim($_POST['question']);
    if (!empty($question)) {
        $data = json_encode(['question' => $question]);

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
            $mainBook = $decoded['main'] ?? [];
            $relatedBooks = $decoded['related'] ?? [];
        } else {
            $answer = 'Error communicating with the AI.';
        }
    }
}
?>

<div class="max-w-4xl mx-auto py-6 px-4">
  <h1 class="text-xl font-bold mb-4">🤖 Ask the Library Bot</h1>

  <form method="POST" onsubmit="showLoading()" class="flex flex-col sm:flex-row items-center gap-2 mb-4">
    <input
      type="text"
      name="question"
      required
      placeholder="Ask about books, topics, summaries..."
      value="<?= htmlspecialchars($_POST['question'] ?? '') ?>"
      class="flex-grow px-4 py-2 border rounded shadow-sm w-full sm:w-auto"
    />
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Ask</button>
    <button type="button" onclick="clearAnswer()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">🗑 Clear Last Answer</button>
  </form>

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
        <a href="views/book_detail.php?title=<?= urlencode($mainBook['title']) ?>" class="block p-3 bg-white rounded border shadow hover:shadow-md transition">
          <div class="text-lg font-semibold text-blue-800"><?= htmlspecialchars($mainBook['title']) ?></div>
          <div class="text-sm text-gray-600 mt-1">
            👤 <?= htmlspecialchars($mainBook['author']) ?><br>
            🔖 <?= htmlspecialchars($mainBook['call_no']) ?><br>
            📄 <?= htmlspecialchars($mainBook['short_summary']) ?>
          </div>
        </a>
      </div>
    <?php endif; ?>

    <?php if (!empty($relatedBooks)): ?>
      <div class="mb-6 p-4 border border-green-300 bg-green-50 rounded">
        <h3 class="font-semibold text-green-700 mb-3">📚 Related Books:</h3>
        <div class="grid gap-4">
          <?php foreach ($relatedBooks as $related): ?>
            <a href="views/book_detail.php?title=<?= urlencode($related['title']) ?>" class="block p-3 bg-white rounded border shadow hover:shadow-md transition">
              <div class="text-base font-semibold text-green-800"><?= htmlspecialchars($related['title']) ?></div>
              <div class="text-sm text-gray-600 mt-1">
                👤 <?= htmlspecialchars($related['author']) ?><br>
                🔖 <?= htmlspecialchars($related['call_no']) ?><br>
                📄 <?= htmlspecialchars($related['short']) ?>
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

  function clearAnswer() {
    window.location.href = 'ask.php';
  }
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
