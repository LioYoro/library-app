<?php
$pdo = new PDO("mysql:host=localhost;dbname=library_test_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Copy all proposals to event_report as PENDING (if not already there)
$sql = "INSERT INTO event_report (proposal_id, name, event_title, description, contact, file_path, file_type, date_submitted, status)
        SELECT p.id, p.name, p.event_title, p.description, p.contact, p.file_path, p.file_type, p.date_submitted, 'PENDING'
        FROM propose_event p
        WHERE NOT EXISTS (
            SELECT 1 FROM event_report er WHERE er.proposal_id = p.id
        )";

$pdo->exec($sql);

echo "✅ Migration complete. All existing proposals copied to event_report.";
