<?php
// Database connection settings
$host = "localhost";
$username = "root";
$password = "";  // put your MySQL password here if any
$database = "library_test_db";  // change to your actual database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
