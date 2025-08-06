<?php
if (isset($_POST['update'])) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file']['tmp_name'];
        if (($handle = fopen($file, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($header, $data);

                $title = $conn->real_escape_string($row['title']);
                $author = $conn->real_escape_string($row['author']);
                // other fields...

                // Find by title OR author
                $checkSql = "SELECT id FROM books WHERE title='$title' OR author='$author' LIMIT 1";
                $checkResult = $conn->query($checkSql);
                if ($checkResult && $checkResult->num_rows > 0) {
                    $bookId = $checkResult->fetch_assoc()['id'];
                    // Update record
                    $updateSql = "UPDATE books SET
                      accession_no='" . $conn->real_escape_string($row['accession_no']) . "',
                      call_number='" . $conn->real_escape_string($row['call_number']) . "',
                      date_acquired='" . $conn->real_escape_string($row['date_acquired']) . "',
                      summary='" . $conn->real_escape_string($row['summary']) . "',
                      keywords='" . $conn->real_escape_string($row['keywords']) . "',
                      general_category='" . $conn->real_escape_string($row['general_category']) . "',
                      sub_category='" . $conn->real_escape_string($row['sub_category']) . "',
                      `like`=" . intval($row['like'] ?? 0) . ",
                      `dislike`=" . intval($row['dislike'] ?? 0) . "
                      WHERE id=$bookId";
                    $conn->query($updateSql);
                }
            }
            fclose($handle);
            echo "<script>alert('Update completed.'); window.location.href='manage_books.php';</script>";
            exit;
        }
    }
}
