<?php
require_once 'db.php';
require_once 'notification_helper.php';
start_session_once();


if (empty($_SESSION['user_id']) || empty($_POST['post_id']) || empty($_POST['comment'])) exit;

$pdo = get_pdo();

$postId = (int)$_POST['post_id'];
$userId = $_SESSION['user_id'];
$comment = trim($_POST['comment']);

$pdo->prepare("
    INSERT INTO comments (post_id, user_id, body, created_at)
    VALUES (:post, :user, :comment, NOW())
")->execute([
    ':post' => $postId,
    ':user' => $userId,
    ':comment' => $comment
]);

$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = :id");
$stmt->execute([':id'=>$postId]);
$postOwner = $stmt->fetchColumn();

if ($postOwner && $postOwner != $userId) {
    addNotification($postOwner, $userId, 'comment', $postId);
}

echo json_encode(['status'=>'ok']);
