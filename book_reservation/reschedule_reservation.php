<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

require_once __DIR__ . '/../includes/reservation_mailer.php';

$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = $_SESSION['user_id'];
$message = "";
$error = "";

// Check if reservation ID is passed
$reservationId = $_POST['reservation_id'] ?? null;
if (!$reservationId) {
    $error = "No reservation selected to reschedule.";
}

// Fetch reservation details
if (!$error) {
    $stmt = $pdo->prepare("
        SELECT r.*, b.TITLE, b.AUTHOR, b.`CALL NUMBER`, b.`ACCESSION NO.`, u.email, u.first_name
        FROM reservations r
        JOIN books b ON r.book_id = b.id
        JOIN users u ON r.user_id = u.id
        WHERE r.reservation_id = ? 
          AND r.user_id = ? 
          AND r.status = 'pending'
    ");
    $stmt->execute([$reservationId, $userId]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        $error = "Reservation cannot be rescheduled.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reservation']) && $reservation) {
    $pickupDate = $_POST['pickup_date'] ?? '';
    $pickupTime = $_POST['pickup_time'] ?? '';

    date_default_timezone_set('Asia/Manila');
    $currentDateTime = new DateTime();
    $selectedDateTime = DateTime::createFromFormat('Y-m-d H:i', $pickupDate . ' ' . $pickupTime);

    if (!$selectedDateTime) {
        $error = "Invalid date or time format.";
    } elseif ($selectedDateTime <= $currentDateTime) {
        $error = "You cannot select a pickup time that has already passed. Please choose a future time.";
    } elseif ($selectedDateTime->format('H:i') < '07:30' || $selectedDateTime->format('H:i') > '19:00') {
        $error = "Pickup time must be between 7:30 AM and 7:00 PM.";
    }

    if (!$error) {
        // Calculate expiry time (+30 minutes)
        $expiryDateTime = clone $selectedDateTime;
        $expiryDateTime = $expiryDateTime->add(new DateInterval('PT30M'))->format('Y-m-d H:i:s');

        // Update reservation
        $updateStmt = $pdo->prepare("
            UPDATE reservations
            SET pickup_time = ?, expiry_time = ?, rescheduled = 1
            WHERE reservation_id = ? AND user_id = ?
        ");
        $updateStmt->execute([$selectedDateTime->format('Y-m-d H:i:s'), $expiryDateTime, $reservationId, $userId]);

        // Update local reservation data for display
        $reservation['pickup_time'] = $selectedDateTime->format('Y-m-d H:i:s');
        $reservation['expiry_time'] = $expiryDateTime;
        $reservation['rescheduled'] = 1;
        $message = "Reservation rescheduled successfully!";

        // Send reschedule email
        sendReservationRescheduledEmail(
            $reservation['email'],
            $reservation['first_name'],
            $reservationId,
            $reservation['TITLE'],
            $reservation['AUTHOR'],
            $reservation['CALL NUMBER'],
            $reservation['ACCESSION NO.'],
            $reservation['pickup_time']
        );
    }
}

require __DIR__ . '/../views/header.php';
?>

<main class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">🕐 Reschedule Reservation</h1>

    <?php if ($error): ?>
        <p class="text-red-600 font-bold mb-4"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($message): ?>
        <p class="text-green-600 font-bold mb-4"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($reservation): ?>
    <div class="bg-white p-6 rounded shadow space-y-4">
        <h2 class="font-semibold text-lg"><?= htmlspecialchars($reservation['TITLE']) ?></h2>
        <p class="text-sm text-gray-600">
            👤 <strong>Author:</strong> <?= htmlspecialchars($reservation['AUTHOR']) ?><br>
            🔖 <strong>Call Number:</strong> <?= htmlspecialchars($reservation['CALL NUMBER']) ?><br>
            📚 <strong>Accession No.:</strong> <?= htmlspecialchars($reservation['ACCESSION NO.']) ?>
        </p>

        <?php if (!$message): ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($reservation['reservation_id']) ?>">

            <div>
                <label for="pickup_date" class="block text-sm font-medium text-gray-700 mb-1">📅 Pickup Date</label>
                <input type="date" id="pickup_date" name="pickup_date" required
                    min="<?= date('Y-m-d') ?>"
                    max="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                    class="w-full px-3 py-2 border rounded-md"
                    value="<?= date('Y-m-d', strtotime($reservation['pickup_time'])) ?>">
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
                        $selected = (date('H:i', strtotime($reservation['pickup_time'])) === $timeValue) ? 'selected' : '';
                        echo "<option value=\"$timeValue\" $selected>$timeDisplay</option>";
                        $start->add($interval);
                    }
                    ?>
                </select>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" name="submit_reservation" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                    📌 Submit Reschedule
                </button>
                <a href="my_reservations.php" class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 text-center">Cancel</a>
            </div>
        </form>
        <?php else: ?>
            <div class="space-y-2">
                <p><strong>📅 Pickup Date:</strong> <?= date('Y-m-d', strtotime($reservation['pickup_time'])) ?></p>
                <p><strong>🕐 Pickup Time:</strong> <?= date('g:i A', strtotime($reservation['pickup_time'])) ?></p>
                <p><strong>⏰ Expiry Time:</strong> <?= date('g:i A', strtotime($reservation['expiry_time'])) ?></p>
                <div class="flex gap-3 pt-4">
                    <a href="my_reservations.php" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 text-center">
                        ✅ Back to My Reservations
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<script>
// Disable past times for same-day pickup
document.getElementById('pickup_date').addEventListener('input', function() {
    const selectedDate = new Date(this.value);
    const today = new Date();
    const timeSelect = document.getElementById('pickup_time');
    const options = timeSelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') return;
        const [hours, minutes] = option.value.split(':').map(Number);
        const optionTime = new Date();
        optionTime.setHours(hours, minutes, 0, 0);

        if (selectedDate.toDateString() === today.toDateString()) {
            if (optionTime <= today) {
                option.style.display = 'none';
                option.disabled = true;
            } else {
                option.style.display = 'block';
                option.disabled = false;
            }
        } else {
            option.style.display = 'block';
            option.disabled = false;
        }
    });

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
