<form id="searchForm" method="get" class="flex flex-wrap gap-2 items-center mb-4 w-full">
  <!-- Search Bar -->
  <div class="flex items-center border border-black rounded-md flex-grow max-w-full">
    <button type="button" class="px-2 text-lg text-black">
      <i class="fas fa-filter"></i>
    </button>
    <input 
      type="text" 
      name="search" 
      placeholder="Search by title or summary..." 
      value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
      class="flex-grow px-2 py-1 text-sm outline-none" 
    />
    <button id="standardSearchBtn" type="submit" class="px-3 text-lg text-black">
      <i class="fas fa-search"></i>
    </button>
  </div>

  <!-- ASK (still static) -->
  <button type="button" class="border border-black rounded-md px-3 py-1 text-xs font-normal select-none flex items-center gap-1">
    ASK <span class="text-xs -mb-1">✦</span>
  </button>

  <!-- ADVANCED SEARCH (handled via JS) -->
  <button id="advancedSearchBtn" type="button" class="border border-black rounded-md px-3 py-1 text-xs font-normal select-none">
    ADVANCED SEARCH
  </button>

  <!-- RECENT/FAVORITE -->
  <a href="views/favorites.php" class="border border-black rounded-md px-3 py-1 text-xs font-normal select-none flex items-center gap-1">
  ❤️ BOOKMARKS
</a>
</form>

<script>
  const form = document.getElementById('searchForm');
  const input = form.querySelector("input[name='search']");
  const standardBtn = document.getElementById('standardSearchBtn');
  const advancedBtn = document.getElementById('advancedSearchBtn');

  // Standard search: submit to book_results.php
  standardBtn.addEventListener('click', function () {
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
      form.action = "ask.php";
      form.method = "post";

      // Convert the input into a POST field
      const hidden = document.createElement("input");
      hidden.type = "hidden";
      hidden.name = "question";
      hidden.value = value;
      form.appendChild(hidden);

      form.submit();
    }
  });

  // Handle Enter key manually to use standard search
  form.addEventListener("submit", function (e) {
    const value = input.value.trim();
    if (!value) {
      e.preventDefault(); // prevent if empty
    } else {
      form.action = "views/book_results.php";
      form.method = "get";
    }
  });
</script>
