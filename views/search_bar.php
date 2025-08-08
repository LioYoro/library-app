<div class="search-container">
  <form id="searchForm" method="get">
    <!-- Search Input Group -->
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
    
    <!-- Action Buttons -->
    <button type="button" class="search-btn">
      ASK <span style="font-size: 10px;">✦</span>
    </button>
    
    <button id="advancedSearchBtn" type="button" class="search-btn">
      ADVANCED SEARCH
    </button>
    
    <a href="views/favorites.php" class="search-btn">
      BOOKMARKS
    </a>
  </form>
</div>

<script>
  const form = document.getElementById('searchForm');
  const input = form.querySelector("input[name='search']");
  const standardBtn = document.getElementById('standardSearchBtn');
  const advancedBtn = document.getElementById('advancedSearchBtn');

  // Standard search: submit to book_results.php
  standardBtn.addEventListener('click', function (e) {
    e.preventDefault();
    const value = input.value.trim();
    if (value) {
      form.action = "views/book_results.php";
      form.method = "get";
      form.submit();
    }
  });

  // Advanced search: submit to ask.php
  advancedBtn.addEventListener('click', function () {
    const value = input.value.trim();
    if (value) {
      // Create a temporary form for POST request
      const tempForm = document.createElement('form');
      tempForm.method = 'post';
      tempForm.action = 'ask.php';
      
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'question';
      hiddenInput.value = value;
      
      tempForm.appendChild(hiddenInput);
      document.body.appendChild(tempForm);
      tempForm.submit();
    }
  });

  // Handle Enter key for standard search
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const value = input.value.trim();
    if (value) {
      form.action = "views/book_results.php";
      form.method = "get";
      form.submit();
    }
  });
</script>