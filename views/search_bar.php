<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$limitReached = false;

if ($isLoggedIn) {
    require_once __DIR__ . '/../includes/db.php';
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT count FROM advanced_search_log WHERE user_id = ? AND search_date = ?");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $row = $stmt->fetch();
    $limit = 5; // must match ask.php

    if ($row && $row['count'] >= $limit) {
        $limitReached = true;
    }
}
?>

<div class="search-container">
  <form id="searchForm" method="get" action="/library-app/views/book_results.php">
    <div class="search-input-group">
      <button type="button">
        <i class="fas fa-filter"></i>
      </button>
      <input 
        type="text"
        name="search"
        placeholder="Search by title or summary..."
        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
      />
      <button id="standardSearchBtn" type="submit">
        <i class="fas fa-search"></i>
      </button>
    </div>
    
    <!-- ASK button (unchanged) -->
    <button type="button" class="search-btn">
      ASK <span style="font-size: 10px;">✦</span>
    </button> 
    
    <!-- Advanced Search button logic -->
    <?php if ($isLoggedIn && !$limitReached): ?>
      <button id="advancedSearchBtn" type="button" class="search-btn">
        ADVANCED SEARCH <span style="font-size: 10px;">✦</span>
      </button>
    <?php elseif ($isLoggedIn && $limitReached): ?>
      <button type="button" class="search-btn opacity-50 cursor-not-allowed" disabled>
        ADVANCED SEARCH (Limit Reached)
      </button>
    <?php else: ?>
      <button id="loginPromptBtn" type="button" class="search-btn">
        ADVANCED SEARCH <span style="font-size: 10px;">✦</span>
      </button>
    <?php endif; ?>
    
    <a href="/library-app/views/favorites.php" class="search-btn">
      BOOKMARKS
    </a>

    <a href="/library-app/book_reservation/my_reservations.php" class="search-btn">
      MY BORROWING LIST
    </a>
  </form>
</div>

<!-- Login Prompt Modal -->
<div id="loginModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden flex items-center justify-center">
  <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm text-center">
    <h2 class="text-xl font-semibold mb-4">Login Required</h2>
    <p class="mb-4">You need to log in to use Advanced Search.</p>
    <button id="closeModal" class="bg-gray-400 text-white px-4 py-2 rounded">Close</button>
  </div>
</div>

<script>
  const form = document.getElementById('searchForm');
  const input = form.querySelector("input[name='search']");
  const standardBtn = document.getElementById('standardSearchBtn');
  const advancedBtn = document.getElementById('advancedSearchBtn');
  const loginPromptBtn = document.getElementById('loginPromptBtn');

  // Normal search validation
  form.addEventListener("submit", function (e) {
    const value = input.value.trim();
    if (!value) {
      e.preventDefault();
      return;
    }
  });

  // Advanced Search logic (only if logged in & limit not reached)
  if (advancedBtn) {
    advancedBtn.addEventListener('click', function () {
      const value = input.value.trim();
      if (!value) return;

      const originalText = advancedBtn.innerHTML;
      advancedBtn.innerHTML = 'Loading...';
      advancedBtn.disabled = true;

      const tempForm = document.createElement('form');
      tempForm.method = 'POST';
      tempForm.action = '/library-app/ask.php';
      
      const questionInput = document.createElement('input');
      questionInput.type = 'hidden';
      questionInput.name = 'question';
      questionInput.value = value;
      
      tempForm.appendChild(questionInput);
      document.body.appendChild(tempForm);
      tempForm.submit();
    });
  }

  // Login prompt logic (if not logged in)
  if (loginPromptBtn) {
    const modal = document.getElementById("loginModal");
    const closeBtn = document.getElementById("closeModal");

    loginPromptBtn.addEventListener("click", () => {
      modal.classList.remove("hidden");
    });

    closeBtn.addEventListener("click", () => {
      modal.classList.add("hidden");
    });
  }
</script>
