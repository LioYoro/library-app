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
$data['major'] = $data['major'] ?? '';
$data['strand'] = $data['strand'] ?? '';
$data['school_name'] = $data['school_name'] ?? '';
$data['is_mandaluyong_resident'] = $data['is_mandaluyong_resident'] ?? '';
$data['barangay'] = $data['barangay'] ?? '';
$data['city_outside_mandaluyong'] = $data['city_outside_mandaluyong'] ?? '';
$data['contact_number'] = $data['contact_number'] ?? null;

$hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (
    first_name, last_name, email, password, gender, age, religion,
    education_level, major, strand, school_name,
    is_mandaluyong_resident, barangay, city_outside_mandaluyong,
    contact_number, profile_picture,
    email_verified, role, created_at
) VALUES (
    :first_name, :last_name, :email, :password, :gender, :age, :religion,
    :education_level, :major, :strand, :school_name,
    :is_mandaluyong_resident, :barangay, :city_outside_mandaluyong,
    :contact_number, :profile_picture,
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
    ':major' => $data['education_level'] === 'College' ? $data['major'] : null,
    ':strand' => $data['education_level'] === 'SHS' ? $data['strand'] : null,
    ':school_name' => $data['school_name'],
    ':is_mandaluyong_resident' => $data['is_mandaluyong_resident'],
    ':barangay' => $data['barangay'],
    ':city_outside_mandaluyong' => $data['city_outside_mandaluyong'],
    ':contact_number' => $data['contact_number'],
    ':profile_picture' => null,
    ':role' => 'user'
]);


    session_destroy();
    echo "<script>
      alert('✅ Matagumpay na nakarehistro ang iyong account!');
      window.location.href = '../index.php';
    </script>";
    exit();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
