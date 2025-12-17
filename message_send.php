<?php
require_once 'db.php';
start_session_once();

header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || empty($_POST['to']) || empty($_POST['message'])) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek']);
    exit;
}

$from = (int)$_SESSION['user_id'];
$to = (int)$_POST['to'];
$msg = trim($_POST['message']);

if (empty($msg)) {
    echo json_encode(['status' => 'error', 'message' => 'Mesaj boş olamaz']);
    exit;
}

// Mesajlar dizinini oluştur
$messagesDir = __DIR__ . '/data/messages';
if (!is_dir($messagesDir)) {
    mkdir($messagesDir, 0777, true);
}

// Mesajları kaydet
$conversationFile = $messagesDir . '/conversation_' . min($from, $to) . '_' . max($from, $to) . '.txt';

$messageData = json_encode([
    'from' => $from,
    'to' => $to,
    'message' => $msg,
    'time' => date('Y-m-d H:i:s')
]) . PHP_EOL;

file_put_contents($conversationFile, $messageData, FILE_APPEND | LOCK_EX);

echo json_encode(['status' => 'ok']);

