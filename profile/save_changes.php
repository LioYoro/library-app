<?php
session_start();
require_once '../includes/db.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(403);
    exit("Not logged in.");
}

// Fetch user data first
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) exit("User not found.");

$education_level = $_POST['education_level'] ?? '';

$fields = [
    'first_name', 'last_name', 'contact_number', 'email', 'gender', 'age', 'religion',
    'education_level', 'school_name',
    'is_mandaluyong_resident', 'barangay', 'city_outside_mandaluyong',
];

// Include the correct field based on education level
if ($education_level === 'SHS') {
    $fields[] = 'strand';
} elseif ($education_level === 'College') {
    $fields[] = 'major';
}

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

if (isset($_POST['remove_picture']) && $_POST['remove_picture'] === '1') {
    // Remove the profile picture by setting it to NULL
    $updates[] = "profile_picture = ?";
    $params[] = null;
} 
// Profile picture (if uploaded)
elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $fileType = $_FILES['profile_picture']['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        exit("Only JPEG and PNG files are allowed.");
    }
    
    $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    
    if (!in_array($ext, $allowedExtensions)) {
        exit("Only JPEG and PNG files are allowed.");
    }
    
    $filename = 'pp_' . $userId . '_' . time() . '.' . $ext;
    $uploadDir = realpath(__DIR__ . '/../uploads');

    if (!$uploadDir) {
        exit("Upload directory not found.");
    }

    $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $fullPath)) {
        exit("Failed to save uploaded profile picture.");
    }

    $updates[] = "profile_picture = ?";
    $params[] = 'uploads/' . $filename;
}

if (empty($updates)) {
    exit("No changes detected.");
}

$params[] = $userId;
$sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt->execute($params)) {
    echo "Profile updated successfully.";
} else {
    echo "Failed to update profile.";
}
?>
