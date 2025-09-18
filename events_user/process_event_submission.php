<?php
header("Content-Type: text/html; charset=UTF-8");
session_start();
include '../includes/db.php';  // adjust path if needed

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    echo "<div style='text-align:center; color:red;'>
            <p>You must be logged in to submit an event.</p>
            <button id='closeModal' style='padding:8px 20px; border:none; border-radius:6px; cursor:pointer; background:#dc2626; color:white; margin-top:10px;'>Close</button>
          </div>";
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $event_title = trim($_POST['event_title']);
    $description = trim($_POST['description']);
    $contact = trim($_POST['contact']);
    $event_date = $_POST['event_date'] ?? null;
    $event_time = $_POST['event_time'] ?? null;

    // ========== VALIDATIONS ==========
    // Title
    if (strlen($event_title) > 60) {
        echo "<div style='text-align:center; color:red;'><p>Event Title must be 60 characters or less.</p>
              <button id='closeModal'>Close</button></div>";
        exit();
    }
    if (!preg_match("/^[A-Za-z0-9\s\.\,\-\&\'\:\!\@\#]+$/", $event_title)) {
        echo "<div style='text-align:center; color:red;'><p>Event Title contains invalid characters.</p>
              <button id='closeModal'>Close</button></div>";
        exit();
    }

    // Description
    if (strlen($description) > 200) {
        echo "<div style='text-align:center; color:red;'><p>Short Description must be 200 characters or less.</p>
              <button id='closeModal'>Close</button></div>";
        exit();
    }

    // Date validation
    if (!$event_date) {
        echo "<div style='text-align:center; color:red;'><p>Please select a date.</p>
            <button id='closeModal'>Close</button></div>";
        exit();
    }

    $today = new DateTime();
    $minDate = (clone $today)->modify('+5 days')->setTime(0,0,0);  // earliest selectable date at 00:00
    $maxDate = (clone $today)->modify('+1 month +5 days')->setTime(23,59,59);  // latest selectable date at end of day

    $chosenDate = DateTime::createFromFormat('Y-m-d', $event_date);
    $chosenDate->setTime(12,0,0); // avoid timezone/time issues

    if (!$chosenDate || $chosenDate < $minDate || $chosenDate > $maxDate) {
        echo "<div style='text-align:center; color:red;'>
                <p>Date must be between " . $minDate->format('F d, Y') . " and " . $maxDate->format('F d, Y') . ".</p>
                <button id='closeModal'>Close</button>
            </div>";
        exit();
    }

    // Time validation
    if (!$event_time) {
        echo "<div style='text-align:center; color:red;'><p>Please select a time.</p>
              <button id='closeModal'>Close</button></div>";
        exit();
    }

    $chosenTime = DateTime::createFromFormat('H:i', $event_time);
    $minTime = DateTime::createFromFormat('H:i', '09:00');
    $maxTime = DateTime::createFromFormat('H:i', '19:30');

    if (!$chosenTime || $chosenTime < $minTime || $chosenTime > $maxTime) {
        echo "<div style='text-align:center; color:red;'><p>Time must be between 09:00 and 7:30.</p>
              <button id='closeModal'>Close</button></div>";
        exit();
    }

    // File check
    if (!isset($_FILES['event_file']) || $_FILES['event_file']['error'] !== UPLOAD_ERR_OK) {
        echo "<div style='text-align:center; color:red;'><p>File upload error.</p>
              <button id='closeModal'>Close</button></div>";
        exit();
    }

    $maxFileSize = 5 * 1024 * 1024; // 5 MB
    if ($_FILES['event_file']['size'] > $maxFileSize) {
        echo "<div style='text-align:center; color:red;'><p>File size exceeds 5MB limit.</p>
            <button id='closeModal' style='padding:8px 20px; border:none; border-radius:6px; cursor:pointer; background:#dc2626; color:white; margin-top:10px;'>Close</button></div>";
        exit();
    }

    $allowed = ['pdf', 'png'];
    $ext = strtolower(pathinfo($_FILES['event_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo "<div style='text-align:center; color:red;'><p>Only PDF or PNG files are allowed.</p>
              <button id='closeModal'>Close</button></div>";
        exit();
    }

    // ========== UPLOAD FILE ==========
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filename = uniqid('event_') . "." . $ext;
    $filePath = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['event_file']['tmp_name'], $filePath)) {
        echo "<div style='text-align:center; color:red;'><p>Failed to move uploaded file.</p>
              <button id='closeModal'>Close</button></div>";
        exit();
    }

    // ========== SAVE TO DB ==========
    $stmt = $conn->prepare("INSERT INTO propose_event 
        (name, event_title, description, contact, file_path, file_type, date_submitted, status, user_email, event_date, event_time) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'PENDING', ?, ?, ?)");
    $stmt->execute([
        $name, $event_title, $description, $contact,
        $filename, strtoupper($ext), $user_email,
        $event_date, $event_time
    ]);

    // ========== SUCCESS MODAL ==========
    echo "
        <div class='modal-card'>
        <h3 class='modal-success'>✅ Event Proposal Submitted Successfully!</h3>
        <div class='modal-body'>
            <div class='modal-row'><span class='label'>Name:</span><span>" . htmlspecialchars($name) . "</span></div>
            <div class='modal-row'><span class='label'>Title:</span><span>" . htmlspecialchars($event_title) . "</span></div>
            <div class='modal-row'><span class='label'>Short Description:</span><span>" . nl2br(htmlspecialchars($description)) . "</span></div>
            <div class='modal-row'><span class='label'>Date:</span><span>" . htmlspecialchars($event_date) . "</span></div>
            <div class='modal-row'><span class='label'>Time:</span><span>" . date("g:i A", strtotime($event_time)) . "</span></div>
            <div class='modal-row'><span class='label'>Contact:</span><span>" . htmlspecialchars($contact) . "</span></div>
            <div class='modal-row'><span class='label'>Email:</span><span>" . htmlspecialchars($user_email) . "</span></div>
            <div class='modal-row'><span class='label'>File:</span>
            <a href='uploads/$filename' target='_blank' class='modal-link'>📄 View Uploaded File</a>
            </div>
        </div>
        <button id='closeModal' class='modal-btn'>Close</button>
        </div>";

    // ========== SEND EMAIL ==========
    require_once __DIR__ . '/../includes/event_mailer.php';
    $attachmentFullPath = __DIR__ . "/uploads/" . $filename;

    sendEventProposalEmail(
        $user_email,
        $name,
        $event_title,
        nl2br(htmlspecialchars($description)),
        $contact,
        $attachmentFullPath,
        $event_date,
        $event_time
    );

} else {
    echo "<div style='text-align:center; color:red;'><p>Invalid request.</p>
          <button id='closeModal'>Close</button></div>";
}
?>
