<?php
session_start();

// DB connection
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once __DIR__ . '/../../includes/event_mailer.php';

// =================== SINGLE ACCEPT/REJECT ===================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    $sendEmail = isset($_GET['send_email']) ? (bool)$_GET['send_email'] : false;

    if (in_array($action, ['ACCEPTED','REJECTED'])) {
        // 1️⃣ Update propose_event
        $stmt = $pdo->prepare("UPDATE propose_event SET status=? WHERE id=?");
        $stmt->execute([$action, $id]);

        // 2️⃣ Insert or update in event_report including file_path and file_type
        $stmt = $pdo->prepare("
            INSERT INTO event_report
            (proposal_id, name, event_title, description, contact, user_email, event_date, event_time, file_path, file_type, status, decision_date)
            SELECT id, name, event_title, description, contact, user_email, event_date, event_time, file_path, file_type, ?, NOW()
            FROM propose_event
            WHERE id=?
            ON DUPLICATE KEY UPDATE 
                status=VALUES(status), 
                decision_date=VALUES(decision_date),
                file_path=VALUES(file_path),
                file_type=VALUES(file_type)
        ");
        $stmt->execute([$action, $id]);

        // 3️⃣ Send email if requested
        if ($sendEmail) {
            // Fetch proposal info
            $stmt = $pdo->prepare("SELECT name, user_email, event_title, description, event_date, event_time FROM propose_event WHERE id=?");
            $stmt->execute([$id]);
            $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($proposal) {
                if ($action === 'ACCEPTED') {
                    sendProposalAcceptedEmail(
                        $proposal['user_email'],
                        $proposal['name'],
                        $proposal['event_title'],
                        $proposal['description'],
                        $proposal['event_date'],
                        $proposal['event_time']
                    );
                } else { // REJECTED
                    sendProposalRejectedEmail(
                        $proposal['user_email'],
                        $proposal['name'],
                        $proposal['event_title'],
                        $proposal['description'],
                        $proposal['event_date'],
                        $proposal['event_time']
                    );
                }
            }
        }
    }

    header("Location: event_proposals.php");
    exit;
}

// =================== BULK ACTIONS ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['ids'])) {
    $action = $_POST['bulk_action'];
    $ids = $_POST['ids'];

    if (in_array($action, ['ACCEPTED','REJECTED'])) {
        foreach ($ids as $id) {
            $id = (int)$id;

            // Update propose_event
            $stmt = $pdo->prepare("UPDATE propose_event SET status=? WHERE id=?");
            $stmt->execute([$action, $id]);

            // Insert or update event_report including file_path and file_type
            $stmt = $pdo->prepare("INSERT INTO event_report (proposal_id, name, event_title, description, contact, user_email, event_date, event_time, file_path, file_type, status, decision_date) SELECT id, name, event_title, description, contact, user_email, event_date, event_time, file_path, file_type, ?, NOW() FROM propose_event WHERE id=? ON DUPLICATE KEY UPDATE status=VALUES(status), decision_date=VALUES(decision_date), file_path=VALUES(file_path), file_type=VALUES(file_type)");
            $stmt->execute([$action, $id]);
        }
    }

    header("Location: event_proposals.php");
    exit;
}

// =================== FETCH DATA ===================
$pending = $pdo->query("SELECT * FROM propose_event WHERE status='PENDING' ORDER BY date_submitted DESC")->fetchAll();
$accepted = $pdo->query("SELECT * FROM propose_event WHERE status='ACCEPTED' ORDER BY date_submitted DESC")->fetchAll();
$rejected = $pdo->query("SELECT * FROM propose_event WHERE status='REJECTED' ORDER BY date_submitted DESC")->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Event Proposals</title>
<!-- Added link to separate popup modal CSS file -->
<link rel="stylesheet" href="event_proposals.css">
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
.email-modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); }
.email-modal { background:#fff; padding:20px; border-radius:8px; max-width:600px; margin:5% auto; position:relative; }
.email-modal-header { text-align:center; margin-bottom:15px; }
.email-modal-body { margin-bottom:20px; }
.email-modal-actions { display:flex; justify-content:center; gap:10px; }
.email-modal-btn { padding:10px 20px; border-radius:4px; font-size:14px; color:#fff; text-decoration:none; cursor:pointer; }
.email-modal-btn-primary { background:green; }
.email-modal-btn-danger { background:red; }
.email-modal-btn-secondary { background:#ccc; }
</style>
<script>
function confirmActionWithEmail(action, id, email, name, title, desc, date, time) {
    // Show custom modal instead of browser confirm
    showEmailConfirmModal(action, id, email, name, title, desc, date, time);
}

function showEmailConfirmModal(action, id, email, name, title, desc, date, time) {
    const modal = document.getElementById('emailConfirmModal');
    const actionText = action === 'ACCEPTED' ? 'accept' : 'reject';
    const actionColor = action === 'ACCEPTED' ? 'accept' : 'reject';
    
    // Update modal content
    document.getElementById('modalActionText').textContent = actionText;
    document.getElementById('modalProposalTitle').textContent = title;
    
    // Update button colors based on action
    const proceedBtn = document.getElementById('proceedWithEmail');
    const proceedNoEmailBtn = document.getElementById('proceedWithoutEmail');
    
    if (action === 'ACCEPTED') {
        proceedBtn.className = 'email-modal-btn email-modal-btn-primary';
        proceedNoEmailBtn.className = 'email-modal-btn email-modal-btn-primary';
    } else {
        proceedBtn.className = 'email-modal-btn email-modal-btn-danger';
        proceedNoEmailBtn.className = 'email-modal-btn email-modal-btn-danger';
    }
    
    // Set up button click handlers
    proceedBtn.onclick = function() {
        hideEmailConfirmModal();
        window.location.href = `?action=${action}&id=${id}&send_email=1`;
    };
    
    proceedNoEmailBtn.onclick = function() {
        hideEmailConfirmModal();
        window.location.href = `?action=${action}&id=${id}&send_email=0`;
    };
    
    // Show modal
    modal.style.display = 'block';
}

function hideEmailConfirmModal() {
    document.getElementById('emailConfirmModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('emailConfirmModal');
    if (event.target === modal) {
        hideEmailConfirmModal();
    }
}

function showPopup(title, desc, contact, email, date, time, filePath) {
    document.getElementById('popup').style.display = 'block';
    document.getElementById('pTitle').innerText = title;
    document.getElementById('pDesc').innerText = desc;
    document.getElementById('pContact').innerText = contact;
    document.getElementById('pEmail').innerText = email;
    document.getElementById('pDate').innerText = date;
    document.getElementById('pTime').innerText = time;
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
                    <th>Name</th><th>Event Title</th><th>Date Submitted</th><th>Actions</th>
                </tr>
                <?php foreach ($pending as $p): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>"></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['event_title']) ?></td>
                    <td><?= $p['date_submitted'] ?></td>
                    <td>
                        <a href="javascript:void(0)" class="btn btn-view"
                        onclick="showPopup(
                            '<?= htmlspecialchars($p['event_title']) ?>',
                            '<?= htmlspecialchars($p['description']) ?>',
                            '<?= htmlspecialchars($p['contact']) ?>',
                            '<?= htmlspecialchars($p['user_email']) ?>',
                            '<?= htmlspecialchars($p['event_date']) ?>',
                            '<?= htmlspecialchars(date("g:i A", strtotime($p['event_time']))) ?>',
                            '../../events_user/uploads/<?= htmlspecialchars($p['file_path']) ?>'
                        )">View</a>
                        <a href="javascript:void(0)" class="btn btn-accept"
                           onclick="confirmActionWithEmail('ACCEPTED', <?= $p['id'] ?>, '<?= htmlspecialchars($p['user_email']) ?>', '<?= htmlspecialchars($p['name']) ?>', '<?= htmlspecialchars($p['event_title']) ?>', '<?= htmlspecialchars($p['description']) ?>', '<?= htmlspecialchars($p['event_date']) ?>', '<?= htmlspecialchars($p['event_time']) ?>')">
                           Accept
                        </a>
                        <a href="javascript:void(0)" class="btn btn-reject"
                           onclick="confirmActionWithEmail('REJECTED', <?= $p['id'] ?>, '<?= htmlspecialchars($p['user_email']) ?>', '<?= htmlspecialchars($p['name']) ?>', '<?= htmlspecialchars($p['event_title']) ?>', '<?= htmlspecialchars($p['description']) ?>', '<?= htmlspecialchars($p['event_date']) ?>', '<?= htmlspecialchars($p['event_time']) ?>')">
                           Reject
                        </a>
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
                <th>Name</th><th>Event Title</th><th>Date Submitted</th><th>Actions</th>
            </tr>
            <?php foreach ($accepted as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['name']) ?></td>
                <td><?= htmlspecialchars($a['event_title']) ?></td>
                <td><?= $a['date_submitted'] ?></td>
                <td>
                    <a href="javascript:void(0)" class="btn btn-view"
                        onclick="showPopup(
                            '<?= htmlspecialchars($a['event_title']) ?>',
                            '<?= htmlspecialchars($a['description']) ?>',
                            '<?= htmlspecialchars($a['contact']) ?>',
                            '<?= htmlspecialchars($a['user_email']) ?>',
                            '<?= htmlspecialchars($a['event_date']) ?>',
                            '<?= htmlspecialchars(date("g:i A", strtotime($a['event_time']))) ?>',
                            '../../events_user/uploads/<?= htmlspecialchars($a['file_path']) ?>'
                        )">View</a>
                    <a href="javascript:void(0)" class="btn btn-reject"
                       onclick="confirmActionWithEmail('REJECTED', <?= $a['id'] ?>, '<?= htmlspecialchars($a['user_email']) ?>', '<?= htmlspecialchars($a['name']) ?>', '<?= htmlspecialchars($a['event_title']) ?>', '<?= htmlspecialchars($a['description']) ?>', '<?= htmlspecialchars($a['event_date']) ?>', '<?= htmlspecialchars($a['event_time']) ?>')">Reject</a>
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
                <th>Name</th><th>Event Title</th><th>Date Submitted</th><th>Actions</th>
            </tr>
            <?php foreach ($rejected as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['event_title']) ?></td>
                <td><?= $r['date_submitted'] ?></td>
                <td>
                    <a href="javascript:void(0)" class="btn btn-view"
                        onclick="showPopup(
                            '<?= htmlspecialchars($r['event_title']) ?>',
                            '<?= htmlspecialchars($r['description']) ?>',
                            '<?= htmlspecialchars($r['contact']) ?>',
                            '<?= htmlspecialchars($r['user_email']) ?>',
                            '<?= htmlspecialchars($r['event_date']) ?>',
                            '<?= htmlspecialchars(date("g:i A", strtotime($r['event_time']))) ?>',
                            '../../events_user/uploads/<?= htmlspecialchars($r['file_path']) ?>'
                        )">View</a>
                    <a href="javascript:void(0)" class="btn btn-accept"
                       onclick="confirmActionWithEmail('ACCEPTED', <?= $r['id'] ?>, '<?= htmlspecialchars($r['user_email']) ?>', '<?= htmlspecialchars($r['name']) ?>', '<?= htmlspecialchars($r['event_title']) ?>', '<?= htmlspecialchars($r['description']) ?>', '<?= htmlspecialchars($r['event_date']) ?>', '<?= htmlspecialchars($r['event_time']) ?>')">Accept</a>
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
        <p><b>Email:</b> <span id="pEmail"></span></p>
        <p><b>Event Date:</b> <span id="pDate"></span></p>
        <p><b>Event Time:</b> <span id="pTime"></span></p>
        <p><a id="pFile" href="#" target="_blank">View Attached File</a></p>
    </div>
</div>

<!-- Added custom email confirmation modal -->
<div id="emailConfirmModal" class="email-modal-overlay">
    <div class="email-modal">
        <div class="email-modal-header">
            <h3>Confirm Action</h3>
        </div>
        <div class="email-modal-body">
            <p>You are about to <strong id="modalActionText">accept</strong> the proposal:</p>
            <p><strong id="modalProposalTitle"></strong></p>
            <p>Would you like to notify the proposer via email?</p>
            
            <div class="email-modal-actions">
                <button id="proceedWithEmail" class="email-modal-btn email-modal-btn-primary">
                    Yes, Send Email
                </button>
                <button id="proceedWithoutEmail" class="email-modal-btn email-modal-btn-secondary">
                    No, Just Update Status
                </button>
                <button onclick="hideEmailConfirmModal()" class="email-modal-btn email-modal-btn-secondary">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
