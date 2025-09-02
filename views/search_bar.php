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
    
    <button type="button" class="search-btn">
      ASK <span style="font-size: 10px;">✦</span>
    </button> 
    
    <button id="advancedSearchBtn" type="button" class="search-btn">
      ADVANCED SEARCH <span style="font-size: 10px;">✦</span>
    </button>
    
    <a href="/library-app/views/favorites.php" class="search-btn">
      BOOKMARKS
    </a>

    <a href="/library-app/book_reservation/my_reservations.php" class="search-btn">
    MY RESERVATIONS
    </a>
  </form>
</div>

<script>
  const form = document.getElementById('searchForm');
  const input = form.querySelector("input[name='search']");
  const standardBtn = document.getElementById('standardSearchBtn');
  const advancedBtn = document.getElementById('advancedSearchBtn');

  // The form action will handle the redirect to book_results.php

  advancedBtn.addEventListener('click', function () {
    const value = input.value.trim();
    if (!value) return;

    // Show loading state on button
    const originalText = advancedBtn.innerHTML;
    advancedBtn.innerHTML = 'Loading...';
    advancedBtn.disabled = true;

    // Create a temporary form to submit to ask.php
    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = '/library-app/ask.php';
    
    const questionInput = document.createElement('input');
    questionInput.type = 'hidden';
    questionInput.name = 'question';
    questionInput.value = value;
    
    tempForm.appendChild(questionInput);
    document.body.appendChild(tempForm);
    
    // Submit the form (this will navigate to ask.php)
    tempForm.submit();
  });

  form.addEventListener("submit", function (e) {
    const value = input.value.trim();
    if (!value) {
      e.preventDefault();
      return;
    }
    // Form will submit normally to book_results.php due to action attribute
  });
</script>
