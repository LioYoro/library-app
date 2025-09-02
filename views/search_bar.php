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
    
    <!-- Action Buttons 
    <button type="button" class="search-btn">
      ASK <span style="font-size: 10px;">✦</span>
    </button> -->
    
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

  advancedBtn.addEventListener('click', async function () {
  const value = input.value.trim();
  if (!value) return;

  // Show a temporary loading state
  const tempBtn = advancedBtn;
  const originalText = tempBtn.innerHTML;
  tempBtn.innerHTML = 'Loading...';
  tempBtn.disabled = true;

  try {
    const formData = new FormData();
    formData.append('question', value);

    const res = await fetch('/library-app/ask.php', {
      method: 'POST',
      body: formData
    });

    const text = await res.text();
    // Replace the page content or inject result
    document.body.innerHTML = text;
  } catch (err) {
    alert('Error fetching answer.');
    console.error(err);
  } finally {
    tempBtn.innerHTML = originalText;
    tempBtn.disabled = false;
  }
});

</script>