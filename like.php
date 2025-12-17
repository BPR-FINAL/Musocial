<?php
require_once 'db.php';
require_once 'notification_helper.php';
start_session_once();

if (empty($_SESSION['user_id']) || empty($_POST['post_id'])) exit;

$pdo = get_pdo();
$postId = (int)$_POST['post_id'];
$userId = $_SESSION['user_id'];

/* BEĞENİ KAYDI */
$pdo->prepare("
    INSERT IGNORE INTO likes (post_id, user_id, created_at)
    VALUES (:post, :user, NOW())
")->execute([
    ':post' => $postId,
    ':user' => $userId
]);

/* POST SAHİBİ */
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = :id");
$stmt->execute([':id' => $postId]);
$postOwner = $stmt->fetchColumn();

/* 🔔 BİLDİRİM */
if ($postOwner && $postOwner != $userId) {
    addNotification($postOwner, $userId, 'like', $postId);
}

echo json_encode(['status'=>'ok']);
