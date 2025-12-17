<?php
/**
 * Admin İşlemleri - Kullanıcı Yönetimi
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

$action = $_POST['action'] ?? '';
$userId = (int)($_POST['user_id'] ?? 0);

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Kullanıcı ID gerekli']);
    exit;
}

// Kendi hesabını silemesin
if ($userId == $currentUser['id']) {
    echo json_encode(['status' => 'error', 'message' => 'Kendi hesabınızı yönetemezsiniz']);
    exit;
}

switch ($action) {
    case 'ban':
        // Kullanıcıyı banla
        $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        echo json_encode(['status' => 'success', 'message' => 'Kullanıcı banlandı']);
        break;
        
    case 'unban':
        // Banı kaldır
        $stmt = $pdo->prepare("UPDATE users SET is_banned = 0 WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        echo json_encode(['status' => 'success', 'message' => 'Ban kaldırıldı']);
        break;
        
    case 'delete':
        // Kullanıcıyı sil
        try {
            // İlgili tüm verileri sil (Foreign key cascade ile otomatik silinecek)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            echo json_encode(['status' => 'success', 'message' => 'Kullanıcı silindi']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Silme işlemi başarısız: ' . $e->getMessage()]);
        }
        break;
        
    case 'make_admin':
        // Admin yap
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        echo json_encode(['status' => 'success', 'message' => 'Kullanıcı admin yapıldı']);
        break;
        
    case 'remove_admin':
        // Admin yetkisini kaldır
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        echo json_encode(['status' => 'success', 'message' => 'Admin yetkisi kaldırıldı']);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);
        break;
}
