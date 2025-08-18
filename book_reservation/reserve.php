<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] ?? 'User';

// Check if a book ID was passed
$bookId = $_POST['book_id'] ?? $_GET['book_id'] ?? null;
if (!$bookId) {
    die("Book not specified.");
}

// Fetch the book
$stmt = $pdo->prepare("SELECT * FROM books WHERE `id` = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    die("Book not found.");
}

$bookTitle = $book['TITLE'];

// Count current user's active reservations
$resCountStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status IN ('pending','confirmed')");
$resCountStmt->execute([$userId]);
$userActiveReservations = $resCountStmt->fetchColumn();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reservation'])) {
    if ($userActiveReservations >= 3) {
        $error = "You’ve reached the maximum of 3 active reservations. Cancel an existing reservation to reserve a new book.";
    } else {
        $pickupDate = $_POST['pickup_date'] ?? '';
        $pickupTime = $_POST['pickup_time'] ?? '';

        date_default_timezone_set('Asia/Manila');
        $today = new DateTime('today');
        $tomorrow = new DateTime('tomorrow');

        // Check that selected date is today or tomorrow
        $selectedDateOnly = DateTime::createFromFormat('Y-m-d', $pickupDate);
        if (!$selectedDateOnly) {
            $error = "Invalid date format.";
        } elseif ($selectedDateOnly->format('Y-m-d') !== $today->format('Y-m-d') && $selectedDateOnly->format('Y-m-d') !== $tomorrow->format('Y-m-d')) {
            $error = "You can only reserve for today or tomorrow.";
        } else {
            // Check if selected time is still valid
            $currentDateTime = new DateTime();
            $selectedDateTime = DateTime::createFromFormat('Y-m-d H:i', $pickupDate . ' ' . $pickupTime);
            if (!$selectedDateTime) {
                $error = "Invalid date or time format.";
            } elseif ($selectedDateTime <= $currentDateTime) {
                $error = "You cannot reserve a pickup time that has already passed. Please choose a future time.";
            } elseif ($selectedDateTime->format('H:i') < '07:30' || $selectedDateTime->format('H:i') > '19:00') {
                $error = "Pickup time must be between 7:30 AM and 7:00 PM.";
            } else {
                // Check if book is already reserved
                $checkStmt = $pdo->prepare("SELECT * FROM reservations WHERE book_id = ? AND status IN ('pending','confirmed') LIMIT 1");
                $checkStmt->execute([$book['id']]);
                if ($checkStmt->fetch()) {
                    $error = "This book has already been reserved by another user.";
                } else {
                    // Create reservation
                    $pickupDateTime = $selectedDateTime->format('Y-m-d H:i:s');
                    $expiryDateTime = $selectedDateTime->add(new DateInterval('PT30M'))->format('Y-m-d H:i:s'); // +30 minutes

                    $insertStmt = $pdo->prepare("
                        INSERT INTO reservations (user_id, book_id, book_title, pickup_time, expiry_time, status, created_at)
                        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                    ");
                    $insertStmt->execute([$userId, $book['id'], $bookTitle, $pickupDateTime, $expiryDateTime]);

                    $success = "Reservation submitted successfully! Please wait for admin confirmation.";
                }
            }
        }
    }
}
require __DIR__ . '/../views/header.php';
?>

<main class="max-w-2xl mx-auto px-4 py-8">
<div class="bg-white rounded-lg shadow-md p-6">
  <h1 class="text-2xl font-bold mb-6 text-center">📖 Reserve Book</h1>

  <!-- Book Information -->
  <div class="bg-gray-50 rounded-lg p-4 mb-6">
    <h2 class="font-semibold text-lg mb-2"><?= htmlspecialchars($book['TITLE']) ?></h2>
    <p class="text-gray-600 text-sm">
      <strong>Author:</strong> <?= htmlspecialchars($book['AUTHOR']) ?><br>
      <strong>Call Number:</strong> <?= htmlspecialchars($book['CALL NUMBER']) ?><br>
      <strong>Category:</strong> <?= htmlspecialchars($book['General_Category']) ?>
    </p>
  </div>

  <!-- User Reservation Note -->
  <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
      <p class="text-sm">ℹ️ You can only reserve one copy of a book at a time, and up to <strong>3 active reservations</strong> at once.</p>
  </div>

  <?php if ($book['status'] === 'Borrowed'): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <strong>❌ Book Currently Borrowed</strong>
      </div>
      <a href="../views/book_detail.php?title=<?= urlencode($bookTitle) ?>" class="inline-block bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"> ← Back to Book Details </a>

  <?php elseif ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          <strong>Error:</strong> <?= htmlspecialchars($error) ?>
      </div>
      <a href="../views/book_detail.php?title=<?= urlencode($bookTitle) ?>" class="inline-block bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"> ← Back to Book Details </a>

  <?php elseif ($success): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          <strong>Success!</strong> <?= htmlspecialchars($success) ?>
      </div>
      <div class="text-center">
          <a href="../views/book_detail.php?title=<?= urlencode($bookTitle) ?>" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"> ← Back to Book Details </a>
      </div>
  <?php else: ?>
      <form method="POST" class="space-y-4">
          <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
          <input type="hidden" name="book_title" value="<?= htmlspecialchars($bookTitle) ?>">
          <div>
              <label for="pickup_date" class="block text-sm font-medium text-gray-700 mb-1">📅 Pickup Date</label>
              <input type="date" id="pickup_date" name="pickup_date" required
                     min="<?= date('Y-m-d') ?>"
                     max="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                     class="w-full px-3 py-2 border rounded-md"
                     value="<?= $_POST['pickup_date'] ?? '' ?>">
          </div>
          <div>
              <label for="pickup_time" class="block text-sm font-medium text-gray-700 mb-1">🕐 Pickup Time</label>
              <select id="pickup_time" name="pickup_time" required class="w-full px-3 py-2 border rounded-md">
                    <option value="">Select pickup time</option>
                    <?php
                    $start = new DateTime('07:30');
                    $end = new DateTime('19:00');
                    $interval = new DateInterval('PT30M');

                    while ($start <= $end) {
                        $timeValue = $start->format('H:i');
                        $timeDisplay = $start->format('g:i A');
                        $selected = ($_POST['pickup_time'] ?? '') === $timeValue ? 'selected' : '';
                        echo "<option value=\"$timeValue\" $selected>$timeDisplay</option>";
                        $start->add($interval);
                    }
                    ?>
                </select>
          </div>
          <div class="flex gap-3 pt-4">
              <button type="submit" name="submit_reservation" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700"> 📌 Submit Reservation </button>
              <a href="../views/book_detail.php?title=<?= urlencode($bookTitle) ?>" class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 text-center"> Cancel </a>
          </div>
      </form>
  <?php endif; ?>
</div>
</main>

<script>
document.getElementById('pickup_date').addEventListener('input', function() {
    const selectedDate = new Date(this.value);
    const today = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(today.getDate() + 1);

    if (selectedDate.toDateString() !== today.toDateString() && selectedDate.toDateString() !== tomorrow.toDateString()) {
        alert('You can only reserve for today or tomorrow.');
        this.value = '';
        return;
    }

    const currentHour = today.getHours();
    const currentMinute = today.getMinutes();
    if (selectedDate.toDateString() === today.toDateString() && currentHour >= 19) {
        alert('Library is closed for today. Come back tomorrow at 7:30 AM.');
        this.value = '';
        return;
    }

    // Filter time options based on selected date
    const timeSelect = document.getElementById('pickup_time');
    const options = timeSelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') return; // Skip the default "Select pickup time" option
        
        const [hours, minutes] = option.value.split(':').map(Number);
        const optionTime = new Date();
        optionTime.setHours(hours, minutes, 0, 0);
        
        // If today is selected, hide past time slots
        if (selectedDate.toDateString() === today.toDateString()) {
            if (optionTime <= today) {
                option.style.display = 'none';
                option.disabled = true;
            } else {
                option.style.display = 'block';
                option.disabled = false;
            }
        } else {
            // If tomorrow is selected, show all time slots
            option.style.display = 'block';
            option.disabled = false;
        }
    });
    
    // Reset selection if current selection is now hidden
    if (timeSelect.selectedOptions[0] && timeSelect.selectedOptions[0].style.display === 'none') {
        timeSelect.value = '';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('pickup_date');
    if (dateInput.value) {
        dateInput.dispatchEvent(new Event('input'));
    }
});
</script>

<?php require __DIR__ . '/../views/footer.php'; ?>
