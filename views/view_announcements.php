<section class="border rounded-lg p-4 bg-transparent shadow-sm max-w-5xl mx-auto mb-6">
  <h2 class="text-2xl font-extrabold text-center mb-4 tracking-wide">
    📢 Library Announcements
  </h2>

  <div class="relative w-full overflow-hidden">
    <div id="announcementCarousel" class="flex transition-transform duration-500">
      <?php
      $stmt = $pdo->query("SELECT * FROM announcements ORDER BY priority DESC, created_at DESC LIMIT 10");
      if ($stmt->rowCount() > 0):
        foreach ($stmt as $a): ?>
          <div class="flex-shrink-0 w-full flex justify-center">
            <img src="admin/<?= htmlspecialchars($a['image_path']) ?>" 
                 alt="Announcement" 
                 class="max-h-[500px] w-auto object-contain rounded shadow">
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-sm text-gray-600 text-center">No announcements yet.</p>
      <?php endif; ?>
    </div>

    <!-- Left Button -->
    <button id="prevBtn" 
            class="absolute top-1/2 left-2 transform -translate-y-1/2 bg-gray-700 text-white p-2 rounded-full hover:bg-gray-900">
      &#10094;
    </button>

    <!-- Right Button -->
    <button id="nextBtn" 
            class="absolute top-1/2 right-2 transform -translate-y-1/2 bg-gray-700 text-white p-2 rounded-full hover:bg-gray-900">
      &#10095;
    </button>
  </div>
</section>

<!-- Carousel Script -->
<script>
  const carousel = document.getElementById('announcementCarousel');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');

  let index = 0;
  const slides = carousel.children;
  const totalSlides = slides.length;

  function updateCarousel() {
    carousel.style.transform = `translateX(-${index * 100}%)`;
  }

  nextBtn.addEventListener('click', () => {
    index = (index + 1) % totalSlides;
    updateCarousel();
  });

  prevBtn.addEventListener('click', () => {
    index = (index - 1 + totalSlides) % totalSlides;
    updateCarousel();
  });
</script>
