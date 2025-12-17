<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
    $pdo = get_pdo();
}

/**
 * Bildirim ekle
 * @param int $userId   Bildirimi alacak kişi
 * @param int $senderId İşlemi yapan kişi
 * @param string $type  follow | like | comment | message
 * @param int $postId   Gönderi ID (opsiyonel)
 */
function addNotification($userId, $senderId, $type, $postId = null)
{
    global $pdo;

    if ($userId == $senderId) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, sender_id, type, post_id, created_at)
        VALUES (:user, :sender, :type, :post_id, NOW())
    ");
    $stmt->execute([
        ':user'    => $userId,
        ':sender'  => $senderId,
        ':type'    => $type,
        ':post_id' => $postId
    ]);
}
