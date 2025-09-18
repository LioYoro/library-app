<?php
session_start();

// DB connection
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle single Accept/Reject actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if (in_array($action, ['ACCEPTED','REJECTED'])) {
        // Update in propose_event
        $stmt = $pdo->prepare("UPDATE propose_event SET status=? WHERE id=?");
        $stmt->execute([$action, $id]);

        // Update in event_report
        $stmt = $pdo->prepare("UPDATE event_report SET status=?, decision_date=NOW() WHERE proposal_id=?");
        $stmt->execute([$action, $id]);
    }
    header("Location: event_proposals.php");
    exit;
}

// Handle bulk actions (only for Pending)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['ids'])) {
    $action = $_POST['bulk_action'];
    $ids = $_POST['ids'];

    if (in_array($action, ['ACCEPTED','REJECTED'])) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Update propose_event
        $stmt = $pdo->prepare("UPDATE propose_event SET status=? WHERE id IN ($placeholders)");
        $stmt->execute(array_merge([$action], $ids));

        // Update event_report
        $stmt = $pdo->prepare("UPDATE event_report SET status=?, decision_date=NOW() WHERE proposal_id IN ($placeholders)");
        $stmt->execute(array_merge([$action], $ids));
    }
    header("Location: event_proposals.php");
    exit;
}

// Fetch data grouped by status
$pending = $pdo->query("SELECT * FROM propose_event WHERE status='PENDING' ORDER BY date_submitted DESC")->fetchAll();
$accepted = $pdo->query("SELECT * FROM propose_event WHERE status='ACCEPTED' ORDER BY date_submitted DESC")->fetchAll();
$rejected = $pdo->query("SELECT * FROM propose_event WHERE status='REJECTED' ORDER BY date_submitted DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Event Proposals</title>
<style>
    body { font-family: Arial, sans-serif; background: #f8fafc; margin: 20px; }
    h1 { text-align: center; color: #1d4ed8; margin-bottom: 30px; }
    .container { display: flex; gap: 20px; }
    .box { flex: 1; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); max-height: 80vh; overflow-y: auto; }
    h2 { text-align: center; font-size: 18px; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    th { background: #f1f5f9; }
    .btn { padding: 5px 10px; border-radius: 4px; font-size: 12px; color: #fff; text-decoration: none; }
    .btn-view { background: #1d4ed8; }
    .btn-accept { background: green; }
    .btn-reject { background: red; }
    .popup { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); }
    .popup-content { background:#fff; padding:20px; border-radius:8px; max-width:600px; margin:5% auto; position:relative; }
    .close { position:absolute; top:10px; right:15px; cursor:pointer; font-weight:bold; }
    .bulk-actions { margin-bottom: 10px; text-align: right; }
</style>
<script>
function confirmAction(url, action) {
    if (confirm("Are you sure you want to " + action + " this proposal?")) {
        window.location.href = url;
    }
}

function showPopup(title, desc, contact, filePath) {
    document.getElementById('popup').style.display = 'block';
    document.getElementById('pTitle').innerText = title;
    document.getElementById('pDesc').innerText = desc;
    document.getElementById('pContact').innerText = contact;
    document.getElementById('pFile').href = filePath;
}
function closePopup() {
    document.getElementById('popup').style.display = 'none';
}
function toggleSelectAll(source, formId) {
    checkboxes = document.querySelectorAll('#' + formId + ' input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>
</head>
<body>

<h1>📑 Event Proposals</h1>

<div class="container">
    <!-- Pending -->
    <div class="box">
        <h2>Pending</h2>
        <?php if (empty($pending)): ?>
            <p style="text-align:center; color:gray;">No pending proposals</p>
        <?php else: ?>
        <form method="POST" id="pendingForm">
            <div class="bulk-actions">
                <select name="bulk_action" required>
                    <option value="">--Bulk Action--</option>
                    <option value="ACCEPTED">Accept</option>
                    <option value="REJECTED">Reject</option>
                </select>
                <button type="submit">Apply</button>
            </div>
            <table>
                <tr>
                    <th><input type="checkbox" onclick="toggleSelectAll(this,'pendingForm')"></th>
                    <th>Name</th><th>Event Title</th><th>Date</th><th>Actions</th>
                </tr>
                <?php foreach ($pending as $p): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>"></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['event_title']) ?></td>
                    <td><?= $p['date_submitted'] ?></td>
                    <td>
                        <a href="javascript:void(0)" class="btn btn-view"
                           onclick="showPopup('<?= htmlspecialchars($p['event_title']) ?>','<?= htmlspecialchars($p['description']) ?>','<?= htmlspecialchars($p['contact']) ?>','<?= $p['file_path'] ?>')">View</a>
                        <a href="javascript:void(0)" class="btn btn-accept" onclick="confirmAction('?action=ACCEPTED&id=<?= $p['id'] ?>','Accept')">Accept</a>
                        <a href="javascript:void(0)" class="btn btn-reject" onclick="confirmAction('?action=REJECTED&id=<?= $p['id'] ?>','Reject')">Reject</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </form>
        <?php endif; ?>
    </div>

    <!-- Accepted -->
    <div class="box">
        <h2>Accepted</h2>
        <?php if (empty($accepted)): ?>
            <p style="text-align:center; color:gray;">No accepted proposals</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Name</th><th>Event Title</th><th>Date</th><th>Actions</th>
            </tr>
            <?php foreach ($accepted as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['name']) ?></td>
                <td><?= htmlspecialchars($a['event_title']) ?></td>
                <td><?= $a['date_submitted'] ?></td>
                <td>
                    <a href="javascript:void(0)" class="btn btn-view"
                       onclick="showPopup('<?= htmlspecialchars($a['event_title']) ?>','<?= htmlspecialchars($a['description']) ?>','<?= htmlspecialchars($a['contact']) ?>','<?= $a['file_path'] ?>')">View</a>
                    <a href="javascript:void(0)" class="btn btn-reject" onclick="confirmAction('?action=REJECTED&id=<?= $a['id'] ?>','Reject')">Reject</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

    <!-- Rejected -->
    <div class="box">
        <h2>Rejected</h2>
        <?php if (empty($rejected)): ?>
            <p style="text-align:center; color:gray;">No rejected proposals</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Name</th><th>Event Title</th><th>Date</th><th>Actions</th>
            </tr>
            <?php foreach ($rejected as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['event_title']) ?></td>
                <td><?= $r['date_submitted'] ?></td>
                <td>
                    <a href="javascript:void(0)" class="btn btn-view"
                       onclick="showPopup('<?= htmlspecialchars($r['event_title']) ?>','<?= htmlspecialchars($r['description']) ?>','<?= htmlspecialchars($r['contact']) ?>','<?= $r['file_path'] ?>')">View</a>
                    <a href="javascript:void(0)" class="btn btn-accept" onclick="confirmAction('?action=ACCEPTED&id=<?= $r['id'] ?>','Accept')">Accept</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Popup -->
<div id="popup" class="popup">
    <div class="popup-content">
        <span class="close" onclick="closePopup()">X</span>
        <h3 id="pTitle"></h3>
        <p><b>Description:</b></p>
        <p id="pDesc"></p>
        <p><b>Contact:</b> <span id="pContact"></span></p>
        <p><a id="pFile" href="#" target="_blank">Download Submitted File</a></p>
    </div>
</div>

</body>
</html>
