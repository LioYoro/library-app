<?php
session_start();
require_once '../includes/db.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(403);
    exit("Not logged in.");
}

$submittedOtp = $_POST['otp'] ?? '';
if (empty($submittedOtp)) {
    exit("OTP required.");
}

// Validate OTP
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) exit("User not found.");

if ($user['otp_code'] !== $submittedOtp || strtotime($user['otp_expires_at']) < time()) {
    exit("Invalid or expired OTP.");
}

// Prepare updated fields
$fields = [
    'first_name', 'last_name', 'contact_number', 'email', 'gender', 'age', 'religion',
    'education_level', 'course', 'school_name',
    'is_mandaluyong_resident', 'barangay', 'city_outside_mandaluyong'
];

$updates = [];
$params = [];

foreach ($fields as $field) {
    if (isset($_POST[$field]) && $_POST[$field] !== $user[$field]) {
        $updates[] = "$field = ?";
        $params[] = $_POST[$field];
    }
}

// Password change
if (!empty($_POST['password'])) {
    $hashed = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $updates[] = "password = ?";
    $params[] = $hashed;
}

// Profile picture (if uploaded)
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $filename = 'pp_' . $userId . '_' . time() . '.' . $ext;
    $uploadDir = realpath(__DIR__ . '/../uploads');

    if (!$uploadDir) {
        exit("Upload directory not found.");
    }

    $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $fullPath)) {
        exit("Failed to save uploaded profile picture.");
    }

    // Save relative path to DB
    $updates[] = "profile_picture = ?";
    $params[] = 'uploads/' . $filename;
}

if (empty($updates)) {
    exit("No changes detected.");
}

$params[] = $userId;
$sql = "UPDATE users SET " . implode(', ', $updates) . ", otp_code = NULL, otp_expires_at = NULL WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute($params);

echo "Profile updated successfully.";
?>
