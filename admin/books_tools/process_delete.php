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

if (!isset($_POST['delete'])) {
    redirect('', 'Invalid access.');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    redirect('', 'Error uploading file.');
}

$fileTmp = $_FILES['file']['tmp_name'];

if (($handle = fopen($fileTmp, "r")) !== FALSE) {
    fgetcsv($handle); // skip header

    $stmt = $conn->prepare("DELETE FROM books WHERE accession_no = ?");
    if (!$stmt) {
        fclose($handle);
        redirect('', 'Failed to prepare database query.');
    }

    $deleted = 0;
    $errors = 0;

    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        if (count($data) < 4) {  // accession_no expected at index 3
            $errors++;
            continue;
        }

        $accession_no = $data[3];

        $stmt->bind_param("s", $accession_no);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $deleted++;
        } else {
            $errors++;
        }
    }

    fclose($handle);
    $stmt->close();

    $msg = "$deleted book(s) deleted.";
    if ($errors) $msg .= " $errors row(s) skipped due to errors.";
    redirect($msg);

} else {
    redirect('', 'Failed to open uploaded file.');
}
