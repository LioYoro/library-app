
<?php
// Show feedback messages from GET parameters (after redirect)
include '../db.php';


$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Import / Delete Books</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 2rem; max-width: 600px; margin: auto; }
    .message { color: green; margin-bottom: 1rem; }
    .error { color: red; margin-bottom: 1rem; }
    fieldset { margin-bottom: 2rem; }
  </style>
</head>


<body>

  <h1>Manage Books via CSV Upload</h1>

  <?php if ($message): ?>
    <div class="message"><?=htmlspecialchars($message)?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="error"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>

  <fieldset>
    <legend><strong>Import / Upload New Books</strong></legend>
    <form action="process_import.php" method="POST" enctype="multipart/form-data">
      <input type="file" name="file" accept=".csv" required>
      <button type="submit" name="import">Upload & Import</button>
    </form>
  </fieldset>

  <fieldset>
    <legend><strong>Import / Upload List of Books to Delete</strong></legend>
    <form action="process_delete.php" method="POST" enctype="multipart/form-data">
      <input type="file" name="file" accept=".csv" required>
      <button type="submit" name="delete">Upload & Delete</button>
    </form>
  </fieldset>

</body>
</html>
