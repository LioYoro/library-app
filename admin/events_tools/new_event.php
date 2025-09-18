<?php
session_start();

// DB connection
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// =================== AUTO PURGE ===================
// 30 days for DELETED events
$pdo->exec("DELETE FROM post_event WHERE status='DELETED' AND updated_at < NOW() - INTERVAL 30 DAY");
// 1 year for reports
$pdo->exec("DELETE FROM post_event_report WHERE created_at < NOW() - INTERVAL 1 YEAR");

// =================== VARIABLES ===================
$editMode = false;
$editEvent = null;

// =================== ADD EVENT ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'] ?? 'NOT POSTED';

    if (str_word_count($description) > 200) {
        echo "<p style='color:red'>⚠️ Description must be max 200 words.</p>";
    } else {
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . "/uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $imageName = time() . "_" . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $imageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                echo "<p style='color:red'>⚠️ File upload failed.</p>";
                $imageName = null;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO post_event (title, description, image, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $imageName, $status]);
        header("Location: new_event.php");
        exit;
    }
}

// =================== EDIT MODE ===================
if (isset($_GET['edit_id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM post_event WHERE id=?");
    $stmt->execute([$_GET['edit_id']]);
    $editEvent = $stmt->fetch(PDO::FETCH_ASSOC);
}

// =================== UPDATE EVENT ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'] ?? 'NOT POSTED';

    if (str_word_count($description) > 200) {
        echo "<p style='color:red'>⚠️ Description must be max 200 words.</p>";
    } else {
        $stmt = $pdo->prepare("SELECT image FROM post_event WHERE id=?");
        $stmt->execute([$id]);
        $oldImage = $stmt->fetchColumn();

        $imageName = $oldImage;
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . "/uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $imageName = time() . "_" . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $imageName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                if ($oldImage && file_exists($uploadDir . $oldImage)) {
                    unlink($uploadDir . $oldImage);
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE post_event SET title=?, description=?, image=?, status=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $description, $imageName, $status, $id]);
        header("Location: new_event.php");
        exit;
    }
}

// =================== CHANGE STATUS ===================
if (isset($_GET['set_status']) && isset($_GET['status'])) {
    $id = (int)$_GET['set_status'];
    $newStatus = $_GET['status'];
    $stmt = $pdo->prepare("UPDATE post_event SET status=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$newStatus, $id]);
    header("Location: new_event.php");
    exit;
}

// =================== DELETE (MOVE TO BIN) ===================
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("UPDATE post_event SET status='DELETED', updated_at=NOW() WHERE id=?");
    $stmt->execute([$id]);
    header("Location: new_event.php");
    exit;
}

// =================== RESTORE ===================
if (isset($_GET['restore_id'])) {
    $id = (int)$_GET['restore_id'];
    $stmt = $pdo->prepare("UPDATE post_event SET status='NOT POSTED', updated_at=NOW() WHERE id=?");
    $stmt->execute([$id]);
    header("Location: new_event.php?recycle_bin=1");
    exit;
}

// =================== FETCH ===================
$events = $pdo->query("SELECT * FROM post_event WHERE status!='DELETED' ORDER BY created_at DESC")->fetchAll();
$deletedEvents = [];
if (isset($_GET['recycle_bin'])) {
    $stmt = $pdo->query("SELECT * FROM post_event WHERE status='DELETED' ORDER BY updated_at DESC");
    $deletedEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Events</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f8fafc; }
    h1 { color: #1d4ed8; }
    form { display: flex; justify-content: space-between; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .form-left { flex: 2; margin-right: 20px; }
    .form-right { flex: 1; text-align: center; }
    input, textarea, select { width: 100%; padding: 8px; margin-top: 6px; margin-bottom: 12px; border: 1px solid #ccc; border-radius: 4px; }
    button { padding: 10px 16px; background: #1d4ed8; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
    button:hover { background: #2563eb; }
    .preview { margin-top: 10px; max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 6px; }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
    th { background: #f1f5f9; }
    a.btn { padding: 6px 12px; border-radius: 4px; color: white; text-decoration: none; font-size: 14px; }
    .btn-green { background: green; }
    .btn-red { background: red; }
    .btn-edit { background: #2563eb; }
    .btn-del { background: #dc2626; }
</style>
<script>
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('preview').src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>
</head>
<body>
    <h1>📢 Manage Events</h1>

    <!-- Form -->
    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-left">
            <h3><?= $editMode ? "Edit Event" : "Add New Event" ?></h3>
            <input type="hidden" name="id" value="<?= $editEvent['id'] ?? '' ?>">
            <label>Title:</label>
            <input type="text" name="title" required value="<?= htmlspecialchars($editEvent['title'] ?? '') ?>">

            <label>Description (max 200 words):</label>
            <textarea name="description" rows="6" required><?= htmlspecialchars($editEvent['description'] ?? '') ?></textarea>

            <label>Status:</label>
            <select name="status">
                <option value="POSTED" <?= isset($editEvent['status']) && $editEvent['status']=="POSTED" ? "selected" : "" ?>>POSTED</option>
                <option value="NOT POSTED" <?= isset($editEvent['status']) && $editEvent['status']=="NOT POSTED" ? "selected" : "" ?>>NOT POSTED</option>
            </select>

            <button type="submit" name="<?= $editMode ? "update_event" : "add_event" ?>">
                <?= $editMode ? "Update Event" : "Add Event" ?>
            </button>
            <?php if ($editMode): ?>
                <a href="new_event.php" style="margin-left:10px;">Cancel Edit</a>
            <?php endif; ?>
        </div>

        <div class="form-right">
            <label>Poster (Image):</label>
            <input type="file" name="image" accept="image/*" onchange="previewImage(event)">
            <img id="preview" src="<?= $editMode && $editEvent['image'] ? 'uploads/' . htmlspecialchars($editEvent['image']) : '' ?>" class="preview" alt="Preview">
        </div>
    </form>

    <!-- Toggle buttons -->
    <a href="new_event.php" class="btn btn-edit">📋 Active Events</a>
    <a href="new_event.php?recycle_bin=1" class="btn btn-del">🗑️ Recycle Bin</a>
    <hr><br>

    <!-- Events Table -->
    <?php if (!isset($_GET['recycle_bin'])): ?>
        <h3>Existing Events</h3>
        <?php if (empty($events)): ?>
            <p>No events posted yet.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= htmlspecialchars($event['title']) ?></td>
                    <td><?= htmlspecialchars(substr($event['description'],0,100)) ?>...</td>
                    <td>
                        <?php if ($event['image']): ?>
                            <a href="uploads/<?= htmlspecialchars($event['image']) ?>" target="_blank">View Image</a>
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($event['status'] === 'POSTED'): ?>
                            <a href="?set_status=<?= $event['id'] ?>&status=NOT POSTED" class="btn btn-green">POSTED</a>
                        <?php else: ?>
                            <a href="?set_status=<?= $event['id'] ?>&status=POSTED" class="btn btn-red">NOT POSTED</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit_id=<?= $event['id'] ?>" class="btn btn-edit">✏️ Edit</a>
                        <a href="?delete_id=<?= $event['id'] ?>" onclick="return confirm('Move this event to recycle bin?')" class="btn btn-del">🗑️ Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <h3>♻️ Recycle Bin (Deleted Events - auto purge after 30 days)</h3>
        <?php if (empty($deletedEvents)): ?>
            <p>No deleted events found.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Deleted At</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($deletedEvents as $event): ?>
                <tr>
                    <td><?= htmlspecialchars($event['title']) ?></td>
                    <td><?= htmlspecialchars(substr($event['description'],0,100)) ?>...</td>
                    <td>
                        <?php if ($event['image']): ?>
                            <a href="uploads/<?= htmlspecialchars($event['image']) ?>" target="_blank">View Image</a>
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($event['updated_at']) ?></td>
                    <td>
                        <a href="?restore_id=<?= $event['id'] ?>" class="btn btn-green">♻️ Restore</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
