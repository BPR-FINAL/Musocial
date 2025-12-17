<?php
require_once 'db.php';
start_session_once();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$pdo = get_pdo();

// Tümünü okundu işaretle
if (isset($_POST['mark_all'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    echo json_encode(['status' => 'success']);
    exit;
}

// Tek bildirimi okundu işaretle
if (isset($_POST['id'])) {
    $notifId = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $notifId, ':uid' => $_SESSION['user_id']]);
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error']);
