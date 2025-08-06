<?php
include '../db.php';  // Adjust path as needed

function redirect($message = '', $error = '') {
    $params = [];
    if ($message) $params['message'] = $message;
    if ($error) $params['error'] = $error;
    $query = http_build_query($params);
    header("Location: import_delete_books.php?" . $query);
    exit();
}

if (!isset($_POST['import'])) {
    redirect('', 'Invalid access.');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    redirect('', 'Error uploading file.');
}

$fileTmp = $_FILES['file']['tmp_name'];

// Updated expected headers to match your CSV (10 fields)
$expectedHeaders = ['No.', 'TITLE', 'AUTHOR', 'ACCESSION NO.', 'CALL NUMBER', 'DATE ACQUIRED', 'Summary', 'Keywords', 'General_Category', 'Sub_Category'];

if (($handle = fopen($fileTmp, "r")) !== FALSE) {
    $headers = fgetcsv($handle);
    if ($headers === false || array_map('trim', $headers) !== $expectedHeaders) {
        fclose($handle);
        redirect('', 'CSV headers do not match expected format.');
    }

    $stmt = $conn->prepare("INSERT INTO books (title, author, accession_no, call_number, date_acquired, summary, keywords, general_category, sub_category, likes, dislikes, date_added) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)");
    if (!$stmt) {
        fclose($handle);
        redirect('', 'Failed to prepare database query.');
    }

    $inserted = 0;
    $errors = 0;

    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        if (count($data) < 10) {
            $errors++;
            continue;
        }

        list(, $title, $author, $accession_no, $call_number, $date_acquired, $summary, $keywords, $general, $sub) = $data;
        $date_added = date('Y-m-d'); // Add current date

        $stmt->bind_param(
            "ssssssssss",
            $title, $author, $accession_no, $call_number, $date_acquired,
            $summary, $keywords, $general, $sub, $date_added
        );

        if ($stmt->execute()) {
            $inserted++;
        } else {
            $errors++;
        }
    }

    fclose($handle);
    $stmt->close();

    $msg = "$inserted book(s) imported.";
    if ($errors) $msg .= " $errors row(s) skipped due to errors.";
    redirect($msg);
} else {
    redirect('', 'Failed to open uploaded file.');
}
