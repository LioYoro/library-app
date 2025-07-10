<!-- Top Controls with Search -->
<div class="flex flex-wrap gap-2 items-center mb-4">
  <a href="ask.php" class="border border-black rounded-md px-3 py-1 text-xs font-normal select-none block">
  ADVANCE SEARCH
</a>


  <div class="flex items-center border border-black rounded-md flex-grow max-w-full">
    <form method="get" class="flex items-center w-full">
      <button type="button" class="px-2 text-lg text-black">
        <i class="fas fa-filter"></i>
      </button>
      <input 
        type="text" 
        name="search" 
        placeholder="Search by title or summary..." 
        value="<?= htmlspecialchars($search) ?>" 
        class="flex-grow px-2 py-1 text-sm outline-none" 
      />
      <select 
        name="category" 
        class="text-sm border-l px-1 py-1 border-black" 
        onchange="this.form.submit()"
      >
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= $cat === $category ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="px-3 text-lg text-black">
        <i class="fas fa-search"></i>
      </button>
    </form>
  </div>

  <button aria-label="Ask" class="border border-black rounded-md px-3 py-1 text-xs font-normal select-none flex items-center gap-1" type="button">
    ASK <span class="text-xs -mb-1">✦</span>
  </button>
  <button class="border border-black rounded-md px-3 py-1 text-xs font-normal select-none" type="button">
    RECENT/FAVORITE
  </button>
</div>
