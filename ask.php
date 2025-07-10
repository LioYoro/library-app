<?php
session_start();

// 📡 POST to Flask API
function callApiPost(string $url, array $data): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR    => false,
        CURLOPT_PROXY          => '',
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($data)
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        echo "<pre>cURL error [" . curl_errno($ch) . "]: " . curl_error($ch) . "</pre>";
        curl_close($ch);
        return null;
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        echo "<pre>API error $code:\n$body</pre>";
        return null;
    }
    return json_decode($body, true);
}

// 🧠 Input Handling
$question = $_POST['question'] ?? '';
$result = null;
$error = '';

if (isset($_POST['clear_cache'])) {
    unset($_SESSION['last_bot']);
    header("Location: ask.php");
    exit;
}

if ($question !== '') {
    $result = callApiPost("http://127.0.0.1:5001/api/chat", [
        "question" => $question
    ]);
    if ($result) {
        $_SESSION['last_bot'] = [
            'question' => $question,
            'answer'   => $result
        ];
    } else {
        $error = "No results found or API error.";
    }
} elseif (isset($_SESSION['last_bot'])) {
    $question = $_SESSION['last_bot']['question'];
    $result   = $_SESSION['last_bot']['answer'];
}

require __DIR__ . '/views/header.php';
?>

<main class="max-w-4xl mx-auto py-10 px-4">
  <h1 class="text-xl font-bold text-center mb-6">🤖 Ask the Library Bot</h1>

  <!-- Ask form -->
  <form method="post" class="mb-4 flex flex-col gap-2">
      <input name="question"
             type="text"
             placeholder="Ask something like 'Books about World War II'"
             value="<?= htmlspecialchars($question) ?>"
             class="border border-gray-300 rounded px-3 py-2 w-full text-sm" />
      <button type="submit"
              class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
          Ask
      </button>
  </form>

  <!-- Clear button -->
  <form method="post" class="mb-6">
      <input type="hidden" name="clear_cache" value="1">
      <button class="text-red-600 underline text-sm">🗑 Clear Last Answer</button>
  </form>

  <?php if ($error): ?>
      <p class="text-red-600 font-semibold"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if ($result): ?>
      <div class="space-y-6">

  <!--Answer -->
  <section>
    <h2 class="text-lg font-semibold">📣 Answer</h2>
    <p class="text-gray-800 whitespace-pre-wrap"><?= htmlspecialchars($result['answer']) ?></p>
  </section>

  <!-- Main Recommendation -->
<section>
  <h2 class="text-lg font-semibold">📌 Main Recommendation</h2>
  <a href="views/book_detail.php?title=<?= urlencode($result['main']['title']) ?>"
     class="block bg-blue-100 border border-blue-300 hover:border-blue-500 hover:shadow-md transition shadow-md rounded p-4">
    <p class="text-xl font-bold"><?= htmlspecialchars($result['main']['title']) ?></p>
    <p class="text-sm">👤 Author: <?= htmlspecialchars($result['main']['author']) ?></p>
    <p class="text-sm">🔖 Call No: <?= htmlspecialchars($result['main']['call_no']) ?></p>
    <p class="mt-2 text-gray-700 text-sm"><?= htmlspecialchars($result['main']['short_summary']) ?></p>
  </a>
</section>


  <!-- Related Books -->
  <?php if (!empty($result['related'])): ?>
    <section>
      <h2 class="text-lg font-semibold">📚 Related Books</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($result['related'] as $r): ?>
          <a href="views/book_detail.php?title=<?= urlencode($r['title']) ?>"
             class="block bg-gray-100 border hover:border-blue-400 hover:shadow-md transition rounded p-4 text-sm text-gray-800">
            <p class="font-semibold"><?= htmlspecialchars($r['title']) ?></p>
            <p>👤 <?= htmlspecialchars($r['author']) ?></p>
            <p>🔖 <?= htmlspecialchars($r['call_no']) ?></p>
            <p class="mt-2 text-gray-700"><?= htmlspecialchars($r['short']) ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

      </div>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/views/footer.php'; ?>
