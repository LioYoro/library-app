<?php
session_start();
require '../includes/db.php';

// Check if email was verified via OTP
if (
    !isset($_SESSION['registration']) || 
    !isset($_SESSION['registration']['email_verified']) || 
    $_SESSION['registration']['email_verified'] !== true
) {
    die("Email verification required.");
}

$data = $_SESSION['registration'];

$data['education_level'] = $data['education_level'] ?? '';
$data['is_shs_student'] = $data['is_shs_student'] ?? '';
$data['course'] = $data['course'] ?? '';
$data['school_name'] = $data['school_name'] ?? '';
$data['is_mandaluyong_resident'] = $data['is_mandaluyong_resident'] ?? '';
$data['barangay'] = $data['barangay'] ?? '';
$data['city_outside_mandaluyong'] = $data['city_outside_mandaluyong'] ?? '';


// Hash the password
$hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

try {
    $stmt = $conn->prepare("INSERT INTO users (
        first_name, last_name, email, password, gender, age, religion,
        education_level, is_shs_student, course, school_name,
        is_mandaluyong_resident, barangay, city_outside_mandaluyong,
        email_verified, role, created_at
    ) VALUES (
        :first_name, :last_name, :email, :password, :gender, :age, :religion,
        :education_level, :is_shs_student, :course, :school_name,
        :is_mandaluyong_resident, :barangay, :city_outside_mandaluyong,
        1, :role, NOW()
    )");

    $stmt->execute([
        ':first_name' => $data['first_name'],
        ':last_name' => $data['last_name'],
        ':email' => $data['email'],
        ':password' => $hashedPassword,
        ':gender' => $data['gender'],
        ':age' => $data['age'],
        ':religion' => $data['religion'],
        ':education_level' => $data['education_level'],
        ':is_shs_student' => $data['is_shs_student'],
        ':course' => $data['course'],
        ':school_name' => $data['school_name'],
        ':is_mandaluyong_resident' => $data['is_mandaluyong_resident'],
        ':barangay' => $data['barangay'],
        ':city_outside_mandaluyong' => $data['city_outside_mandaluyong'],
        ':role' => 'user'
    ]);

    // ✅ Registration successful
    session_destroy();
    echo "User registered successfully!";
    session_destroy();
    header("Location:index.php");
    exit();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
