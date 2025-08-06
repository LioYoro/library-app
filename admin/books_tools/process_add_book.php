<?php
include('../db.php');

$title = $_POST['title'];
$author = $_POST['author'];
$accession_no = $_POST['accession_no'];
$call_number = $_POST['call_number'];
$date_acquired = $_POST['date_acquired'];
$summary = $_POST['summary'];
$keywords = $_POST['keywords'];
$general_category = $_POST['general_category'];
$sub_category = $_POST['sub_category'];
$date_added = date('Y-m-d');


$sql = "INSERT INTO books 
(title, author, accession_no, call_number, date_acquired, summary, keywords, general_category, sub_category, likes, dislikes, date_added) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssss", $title, $author, $accession_no, $call_number, $date_acquired, $summary, $keywords, $general_category, $sub_category, $date_added);

if ($stmt->execute()) {
    echo "Book added successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
