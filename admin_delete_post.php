<?php
/**
 * Admin İşlemleri - Gönderi Silme
 */

require_once 'db.php';
start_session_once();

header('Content-Type: application/json');

// Admin kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Giriş yapmalısınız']);
    exit;
}

$pdo = get_pdo();
$currentUser = get_current_user_data();

if (!$currentUser || (int)$currentUser['is_admin'] !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Yetkiniz yok']);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);

if (!$postId) {
    echo json_encode(['status' => 'error', 'message' => 'Gönderi ID gerekli']);
    exit;
}

try {
    // Gönderiyi sil (Foreign key cascade ile ilgili veriler de silinecek)
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
    $stmt->execute([':id' => $postId]);
    
    echo json_encode(['status' => 'success', 'message' => 'Gönderi silindi']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Silme işlemi başarısız: ' . $e->getMessage()]);
}
