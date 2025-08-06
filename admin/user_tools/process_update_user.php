<?php
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        header('Location: users_report.php?status=error');
        exit;
    }

    // Sanitize inputs
    $first_name = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
    $last_name = $conn->real_escape_string(trim($_POST['last_name'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $gender = $conn->real_escape_string(trim($_POST['gender'] ?? ''));
    $age = intval($_POST['age'] ?? 0);
    $religion = $conn->real_escape_string(trim($_POST['religion'] ?? ''));
    $education_level = $conn->real_escape_string(trim($_POST['education_level'] ?? ''));
    $school_name = $conn->real_escape_string(trim($_POST['school_name'] ?? ''));
    $is_mandaluyong_resident = $conn->real_escape_string(trim($_POST['is_mandaluyong_resident'] ?? ''));
    $barangay = $conn->real_escape_string(trim($_POST['barangay'] ?? ''));
    $city_outside_mandaluyong = $conn->real_escape_string(trim($_POST['city_outside_mandaluyong'] ?? ''));
    $major = $conn->real_escape_string(trim($_POST['major'] ?? ''));
    $strand = $conn->real_escape_string(trim($_POST['strand'] ?? ''));
    $contact_number = $conn->real_escape_string(trim($_POST['contact_number'] ?? ''));
    $profile_verified = intval($_POST['profile_verified'] ?? 0);

    $sql = "UPDATE users SET
        first_name='$first_name',
        last_name='$last_name',
        email='$email',
        gender='$gender',
        age=$age,
        religion='$religion',
        education_level='$education_level',
        school_name='$school_name',
        is_mandaluyong_resident='$is_mandaluyong_resident',
        barangay='$barangay',
        city_outside_mandaluyong='$city_outside_mandaluyong',
        major='$major',
        strand='$strand',
        contact_number='$contact_number',
        profile_verified=$profile_verified
        WHERE id=$id
    ";

    if ($conn->query($sql)) {
        header('Location: users_report.php?status=success');
        exit;
    } else {
        header('Location: users_report.php?status=error');
        exit;
    }
} else {
    header('Location: users_report.php');
    exit;
}
