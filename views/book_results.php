<h2 class="text-sm font-medium mb-2"><?= count($books) ?> Book(s) Found</h2>

<?php if ($search || $category): ?>
  <div class="mb-3">
    <a href="index.php" class="text-blue-600 hover:underline">← Back to Home</a>
  </div>
<?php endif; ?>

<?php if (count($books) === 0): ?>
  <p class="text-red-600 font-medium">No results found.</p>
<?php else: ?>
  <div class="results grid gap-4">
    <?php foreach ($books as $book): 
      $data = $book['data'];
    ?>
      <div class="card flex items-start gap-3 p-3 border rounded-md bg-white shadow-sm">
        <div class="thumbnail text-2xl">📘</div>
        <div class="info flex-1">
          <a href="book.php?id=<?= $book['index'] ?>" class="font-semibold text-blue-700 hover:underline">
            <?= htmlspecialchars($data[1]) ?>
          </a>
          <div class="meta text-sm text-gray-600 mt-1">
            <?= htmlspecialchars($data[8]) ?> | <?= htmlspecialchars($data[9]) ?>
          </div>
          <div class="summary text-sm mt-1">
            <?= htmlspecialchars($data[6]) ?>
          </div>
          <div class="likes text-sm text-gray-700 mt-2">
            👍 <?= $data[10] ?> &nbsp; | &nbsp; 👎 <?= $data[11] ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
