<?php
require_once __DIR__ . '/db.php';
start_session_once();

header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$pdo = get_pdo(); // 🔥 EKSİK OLAN BUYDU

$followerId  = (int)$_SESSION['user_id'];
$followingId = (int)$_POST['user_id'];

if ($followerId === $followingId) {
    echo json_encode(['status' => 'error']);
    exit;
}

// zaten takip ediyor mu
$check = $pdo->prepare("
    SELECT follower_id FROM followers
    WHERE follower_id = ? AND following_id = ?
");
$check->execute([$followerId, $followingId]);

if ($check->fetch()) {

    $pdo->prepare("
        DELETE FROM followers
        WHERE follower_id = ? AND following_id = ?
    ")->execute([$followerId, $followingId]);

    echo json_encode(['status' => 'unfollowed']);
    exit;
}

// FOLLOW
$pdo->prepare("
    INSERT INTO followers (follower_id, following_id, created_at)
    VALUES (?, ?, NOW())
")->execute([$followerId, $followingId]);

// 🔔 BİLDİRİM
$pdo->prepare("
    INSERT INTO notifications (user_id, sender_id, type)
    VALUES (?, ?, 'follow')
")->execute([$followingId, $followerId]);

echo json_encode(['status' => 'followed']);
