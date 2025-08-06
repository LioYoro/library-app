<?php
include '../db.php';  // adjust if needed

function redirectWithMessage($message) {
    echo "<h3>$message</h3><a href='../event_submit.php'>Back</a>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $event_title = $_POST['event_title'];
    $description = $_POST['description'];
    $contact = $_POST['contact'];

    if (!isset($_FILES['event_file']) || $_FILES['event_file']['error'] !== UPLOAD_ERR_OK) {
        redirectWithMessage("File upload error.");
    }

    $allowed = ['pdf', 'png'];
    $ext = strtolower(pathinfo($_FILES['event_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        redirectWithMessage("Only PDF or PNG files are allowed.");
    }

    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = uniqid("event_") . "." . $ext;
    $filePath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['event_file']['tmp_name'], $filePath)) {
        $stmt = $conn->prepare("INSERT INTO events (name, event_title, description, contact, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $event_title, $description, $contact, $filename);
        $stmt->execute();

        echo "<h3>Event Submitted Successfully!</h3>";
        echo "<p><strong>Name:</strong> $name</p>";
        echo "<p><strong>Title:</strong> $event_title</p>";
        echo "<p><strong>Description:</strong> $description</p>";
        echo "<p><strong>Contact:</strong> $contact</p>";
        echo "<p><a href='../uploads/$filename' target='_blank'>View Uploaded File</a></p>";
        echo "<a href='../event_submit.php'>Submit Another</a>";
    } else {
        redirectWithMessage("Failed to move uploaded file.");
    }
} else {
    redirectWithMessage("Invalid request.");
}
?>
