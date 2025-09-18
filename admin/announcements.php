<?php 
$pageTitle = 'Announcements';
include('includes/header.php'); 
include('includes/sidebar.php');
include('db.php'); // database connection

// Handle AJAX reorder request (priority update)
if (isset($_POST['order']) && is_array($_POST['order'])) {
    $order = $_POST['order'];
    foreach ($order as $position => $id) {
        $id = intval($id);
        $position = intval($position);
        $stmt = $conn->prepare("UPDATE announcements SET priority=? WHERE id=?");
        $stmt->bind_param("ii", $position, $id);
        $stmt->execute();
        $stmt->close();
    }
    echo "Priorities updated successfully";
    exit; // stop rendering HTML if AJAX
}

// Handle new upload
if (isset($_POST['upload'])) {
    $title = $_POST['title'];
    $priority = intval($_POST['priority'] ?? 0);

    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/announcements/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $stmt = $conn->prepare("INSERT INTO announcements (title, image_path, priority) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $title, $targetFilePath, $priority);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT image_path FROM announcements WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($imagePath);
    if ($stmt->fetch()) {
        if (file_exists($imagePath)) unlink($imagePath);
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Fetch announcements ordered by priority
$result = $conn->query("SELECT * FROM announcements ORDER BY priority ASC, created_at DESC");
?>

<div id="main-content" class="flex-1 flex flex-col min-w-0 ml-[15rem] h-screen transition-all duration-300">
  <header class="h-16 bg-blue-500 text-white flex items-center justify-between px-6 shadow">
    <h1 class="text-xl font-bold"><?= $pageTitle ?></h1>
    <div class="flex items-center space-x-3">
      <span class="text-sm">ADMIN</span>
      <i class="fas fa-user-circle text-2xl"></i>
    </div>
  </header>

  <main class="flex-1 p-6 bg-white overflow-y-auto">
    <h2 class="text-lg font-semibold mb-4">Upload New Announcement</h2>

    <form method="POST" enctype="multipart/form-data" class="mb-6 space-y-3">
      <input type="text" name="title" placeholder="Announcement Title" required class="block border p-2 w-full">
      <input type="file" name="image" required class="block border p-2">
      <input type="number" name="priority" placeholder="Priority (default = 0)" class="block border p-2">
      <button type="submit" name="upload" class="bg-blue-500 text-white px-4 py-2 rounded">Upload</button>
    </form>

    <h2 class="text-lg font-semibold mb-4">Manage Announcements</h2>
    <table class="w-full border" id="announcementTable">
      <thead>
        <tr class="bg-gray-100">
          <th class="border px-3 py-2">Image</th>
          <th class="border px-3 py-2">Title</th>
          <th class="border px-3 py-2">Reorder</th>
          <th class="border px-3 py-2">Action</th>
        </tr>
      </thead>
      <tbody id="sortable">
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr data-id="<?= $row['id'] ?>" class="border">
          <td class="border px-3 py-2">
            <img src="<?= $row['image_path'] ?>" class="h-20 rounded">
          </td>
          <td class="border px-3 py-2"><?= htmlspecialchars($row['title']) ?></td>
          <td class="border px-3 py-2 text-center cursor-move">⬍ Drag</td>
          <td class="border px-3 py-2">
            <a href="?delete=<?= $row['id'] ?>" class="text-red-500">Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </main>
</div>

<!-- jQuery + jQuery UI for drag-and-drop -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<script>
$(function(){
  $("#sortable").sortable({
    update: function(event, ui) {
      var order = $(this).sortable('toArray', { attribute: 'data-id' });
      $.post("announcements.php", { order: order }, function(response){
        console.log(response);
      });
    }
  });
  $("#sortable").disableSelection();
});
</script>

<?php include('includes/footer.php'); ?>