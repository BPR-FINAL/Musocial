<?php
require_once __DIR__ . '/db.php';
start_session_once();

header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || empty($_POST['post_id'])) {
    echo json_encode(['status'=>'error','message'=>'Yetkisiz işlem']);
    exit;
}

$pdo = get_pdo();
$postId = (int)$_POST['post_id'];

// Sadece kendi gönderilerini silebilsin
$stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id AND user_id = :uid');
$result = $stmt->execute([':id'=>$postId, ':uid'=>$_SESSION['user_id']]);

if ($result) {
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>'Gönderi silinemedi']);
}
