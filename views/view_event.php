<div class="event-section" style="margin: 20px auto; max-width: 1000px;">
    <div style="border: 1px solid #ccc; border-radius: 8px; padding: 25px; background: #fff;">
        <h2 style="font-size: 20px; font-weight: bold; margin-bottom: 20px; text-align: center;">
            📅 Library Events
        </h2>

        <?php
        // DB connection
        $pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch all events (newest first)
        $sql = "SELECT * FROM post_event WHERE status='POSTED' ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $events = $stmt->fetchAll();
        ?>

        <?php if (empty($events)): ?>
            <!-- Placeholder if no events -->
            <div style="border: 1px dashed #aaa; padding: 60px; border-radius: 6px; background: #f9fafb; text-align: center;">
                <img src="assets/no-event.png" alt="No Event" style="max-width: 150px; margin-bottom: 15px; opacity: 0.6;">
                <p style="color: #666; font-size: 16px;">No events have been posted yet.</p>
            </div>
        <?php else: ?>
            <div class="event-slider" style="position: relative; text-align:center;">
                <!-- Left Button -->
                <button id="eventPrevBtn" class="event-nav prev">&#10094;</button>
                
                <div class="event-wrapper">
                    <?php foreach ($events as $index => $event): ?>
                        <div class="event-card event-slide <?= $index === 0 ? 'active' : '' ?>" 
                             style="display: <?= $index === 0 ? 'flex' : 'none' ?>; 
                                    align-items: center; 
                                    gap: 25px; 
                                    margin: 0 auto; 
                                    padding: 25px; 
                                    border: 1px solid #ddd; 
                                    border-radius: 10px; 
                                    background: #fafafa; 
                                    width: 90%; 
                                    min-height: 280px;">
                            
                            <!-- Left: Image (bigger now) -->
                            <?php if (!empty($event['image'])): ?>
                                <img src="admin/events_tools/uploads/<?= htmlspecialchars($event['image']) ?>" 
                                     alt="Event Poster" 
                                     style="width: 350px; height: 220px; border-radius: 8px; object-fit: cover; flex-shrink: 0;">
                            <?php else: ?>
                                <img src="assets/no-event.png" 
                                     alt="No Image" 
                                     style="width: 350px; height: 220px; border-radius: 8px; opacity: 0.6; object-fit: cover; flex-shrink: 0;">
                            <?php endif; ?>

                            <!-- Right: Text -->
                            <div style="flex: 1; text-align:left;">
                                <h3 style="margin: 0; font-size: 22px; color: #1d4ed8;">
                                    <?= htmlspecialchars($event['title']) ?>
                                </h3>
                                <p style="margin-top: 12px; font-size: 16px; color: #333; line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($event['description'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Right Button -->
                <button id="eventNextBtn" class="event-nav next">&#10095;</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.event-nav {
    background: #1d4ed8;
    color: #fff;
    border: none;
    font-size: 28px;
    cursor: pointer;
    padding: 12px 16px;
    border-radius: 50%;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    transition: background 0.3s;
}
.event-nav:hover { background: #0f2d96; }
.event-nav.prev { left: -55px; }
.event-nav.next { right: -55px; }
</style>

<script>
let currentEvent = 0;
const eventSlides = document.querySelectorAll('.event-slide');
const eventPrevBtn = document.getElementById('eventPrevBtn');
const eventNextBtn = document.getElementById('eventNextBtn');

function showEvent(index) {
    eventSlides.forEach((slide, i) => {
        slide.style.display = (i === index) ? 'flex' : 'none';
    });
}

eventNextBtn.addEventListener('click', () => {
    currentEvent = (currentEvent + 1) % eventSlides.length;
    showEvent(currentEvent);
});

eventPrevBtn.addEventListener('click', () => {
    currentEvent = (currentEvent - 1 + eventSlides.length) % eventSlides.length;
    showEvent(currentEvent);
});
</script>
