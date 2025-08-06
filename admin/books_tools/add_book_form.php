<?php include('../db.php'); ?>
<form action="process_add_book.php" method="post" class="p-4 space-y-4">
    <input type="text" name="title" placeholder="Title" required>
    <input type="text" name="author" placeholder="Author" required>
    <input type="text" name="accession_no" placeholder="Accession No." required>
    <input type="text" name="call_number" placeholder="Call Number" required>
    <input type="text" name="date_acquired" placeholder="Date Acquired" required>
    <textarea name="summary" placeholder="Summary"></textarea>
    <textarea name="keywords" placeholder="Keywords"></textarea>
    <input type="text" name="general_category" placeholder="General Category">
    <input type="text" name="sub_category" placeholder="Sub Category">
    <button type="submit">Add Book</button>
</form>
