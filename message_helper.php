<?php
/**
 * Mesaj Helper Fonksiyonları
 * Mesajlar txt dosyasında saklanır
 */

/**
 * İki kullanıcı arasındaki konuşmayı getirir
 */
function getConversation($userId1, $userId2) {
    $messagesDir = __DIR__ . '/data/messages';
    $conversationFile = $messagesDir . '/conversation_' . min($userId1, $userId2) . '_' . max($userId1, $userId2) . '.txt';
    
    if (!file_exists($conversationFile)) {
        return [];
    }
    
    $lines = file($conversationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $messages = [];
    
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data) {
            $messages[] = $data;
        }
    }
    
    return $messages;
}

/**
 * Kullanıcının tüm konuşmalarını listeler
 */
function getUserConversations($userId) {
    $messagesDir = __DIR__ . '/data/messages';
    
    if (!is_dir($messagesDir)) {
        return [];
    }
    
    $conversations = [];
    $files = glob($messagesDir . '/conversation_*.txt');
    
    foreach ($files as $file) {
        $filename = basename($file);
        preg_match('/conversation_(\d+)_(\d+)\.txt/', $filename, $matches);
        
        if (count($matches) === 3) {
            $user1 = (int)$matches[1];
            $user2 = (int)$matches[2];
            
            if ($user1 == $userId || $user2 == $userId) {
                $otherUserId = ($user1 == $userId) ? $user2 : $user1;
                
                // Son mesajı al
                $messages = getConversation($user1, $user2);
                $lastMessage = end($messages);
                
                $conversations[] = [
                    'user_id' => $otherUserId,
                    'last_message' => $lastMessage['message'] ?? '',
                    'last_time' => $lastMessage['time'] ?? '',
                    'unread_count' => 0 // Şimdilik 0
                ];
            }
        }
    }
    
    // Son mesaja göre sırala
    usort($conversations, function($a, $b) {
        return strcmp($b['last_time'], $a['last_time']);
    });
    
    return $conversations;
}

/**
 * Mesaj sayısını getirir
 */
function getMessageCount($userId1, $userId2) {
    return count(getConversation($userId1, $userId2));
}
