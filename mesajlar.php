<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/message_helper.php';
start_session_once();

$currentUser = get_current_user_data();
$darkMode = is_dark_mode();

if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();

// Kullanıcının konuşmalarını al
$conversations = getUserConversations($currentUser['id']);

// Konuşma yapılan kullanıcıların bilgilerini al
$conversationUsers = [];
foreach ($conversations as $conv) {
    $stmt = $pdo->prepare('SELECT id, full_name, username, avatar_url FROM users WHERE id = :id');
    $stmt->execute([':id' => $conv['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $user['last_message'] = $conv['last_message'];
        $user['last_time'] = $conv['last_time'];
        $conversationUsers[] = $user;
    }
}

// Seçili kullanıcı
$selectedUserId = isset($_GET['u']) ? (int)$_GET['u'] : null;
$selectedUser = null;
$messages = [];

if ($selectedUserId) {
    $stmt = $pdo->prepare('SELECT id, full_name, username, avatar_url FROM users WHERE id = :id');
    $stmt->execute([':id' => $selectedUserId]);
    $selectedUser = $stmt->fetch();
    
    if ($selectedUser) {
        $messages = getConversation($currentUser['id'], $selectedUserId);
    }
}

// Tüm kullanıcıları çek (yeni mesaj için)
$stmt = $pdo->prepare('SELECT id, full_name, username, avatar_url FROM users WHERE id != :current_id ORDER BY full_name');
$stmt->execute([':current_id' => $currentUser['id']]);
$allUsers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <title>Musocial - Mesajlar</title>
 <link rel="stylesheet" href="stylee.css"> 
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />  
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">

 <style>
 /* Sohbet Penceresinin Boyutu İçin Basit CSS */
 .chat-box {
     height: calc(100vh - 180px);
     overflow-y: auto;
 }
 </style>
</head>
<body class="<?= $darkMode ? 'dark' : '' ?>">

<!-- MOBİL NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white d-lg-none border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
        <img src="img/logo.jpg" width="40" height="40" class="d-inline-block align-top rounded-circle" style="object-fit:cover;">
        Musocial
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mobileSidebar">
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a href="index.php" class="nav-link"><i class="fa-regular fa-house"></i> Ana Sayfa</a></li>
            <li class="nav-item"><a href="kesfet.php" class="nav-link"><i class="fa-regular fa-compass"></i> Keşfet</a></li>
            <li class="nav-item"><a href="bildirimler.php" class="nav-link"><i class="fa-regular fa-bell"></i> Bildirimler</a></li>
            <li class="nav-item"><a href="mesajlar.php" class="nav-link"><i class="fa-regular fa-message"></i> Mesajlar</a></li>
            <li class="nav-item"><a href="ayarlar.php" class="nav-link"><i class="fa-solid fa-gear"></i> Ayarlar</a></li>
            <?php if($currentUser): ?>
            <li class="nav-item">
                <a href="profil.php" class="nav-link text-success fw-semibold">
                <i class="fa-solid fa-circle-user"></i> @<?=htmlspecialchars($currentUser['username'])?></a>
            </li>
            <?php endif; ?>
            <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a></li>
        </ul>
        </div>
    </div>
    </nav>

<div class="container-fluid">
 <div class="row min-vh-100">

<!-- SOL MENÜ (PC) -->
<nav class="col-md-3 col-lg-2 bg-white border-end p-3 d-none d-md-block">
 <div class="d-flex align-items-center">
 <img src="img/logo.jpg" class="rounded-circle me-2" alt="" width="60" height="60" style="object-fit: cover;">
 <h2 class="text-primary fw-bold mb-0">Musocial</h2>
 </div>

 <ul class="nav flex-column mt-4">
 <li class="nav-item mb-2"><a href="index.php" class="nav-link text-dark"><i class="fa-regular fa-house"></i> Ana Sayfa</a></li>
 <li class="nav-item mb-2"><a href="kesfet.php" class="nav-link text-dark"><i class="fa-regular fa-compass"></i> Keşfet</a></li> 
 <li class="nav-item mb-2"><a href="bildirimler.php" class="nav-link text-dark"><i class="fa-regular fa-bell"></i> Bildirimler</a></li>
 <li class="nav-item mb-2"><a href="mesajlar.php" class="nav-link text-primary fw-bold"><i class="fa-solid fa-message"></i> Mesajlar</a></li>
 <li class="nav-item mb-2"><a href="ayarlar.php" class="nav-link text-dark"><i class="fa-solid fa-gear"></i> Ayarlar</a></li>
 <?php if($currentUser): ?>
 <li class="nav-item"><a href="profil.php" class="nav-link text-success fw-semibold"><i class="fa-solid fa-circle-user"></i> @<?=htmlspecialchars($currentUser['username'])?></a></li>
 <?php endif; ?>
 <li class="nav-item"><a href="logout.php" class="nav-link text-dark"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a></li>
 </ul>
</nav>

<!-- ANA ALAN -->
<main class="col-md-6 col-lg-7 p-0 d-flex bg-white border-end">
    <div class="col-lg-5 col-12 border-end p-0">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <h4 class="mb-0 fw-bold text-primary">Mesajlar</h4>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                <i class="fa-solid fa-plus"></i> Yeni Mesaj
            </button>
        </div>
        <div class="p-3 border-bottom">
            <input type="text" class="form-control rounded-pill" placeholder="Sohbetlerde Ara...">
        </div>
        <div class="list-group list-group-flush" style="max-height: calc(100vh - 120px); overflow-y: auto;">
            <?php if (empty($conversationUsers)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fa-regular fa-message fa-3x mb-3"></i>
                    <p>Henüz mesajınız yok</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversationUsers as $user): ?>
                <a href="mesajlar.php?u=<?= $user['id'] ?>" class="list-group-item list-group-item-action <?= $selectedUserId == $user['id'] ? 'active border-start border-primary border-4' : '' ?> p-3">
                    <div class="d-flex align-items-center">
                        <img src="<?= htmlspecialchars($user['avatar_url'] ?: 'img/user.png') ?>" class="rounded-circle me-3" width="50" height="50" style="object-fit: cover;">
                        <div class="w-100">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars($user['full_name']) ?></h6>
                                <small><?= date('H:i', strtotime($user['last_time'])) ?></small>
                            </div>
                            <p class="mb-1 small text-truncate"><?= htmlspecialchars(mb_substr($user['last_message'], 0, 50)) ?></p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7 d-none d-lg-block p-0">
        <?php if ($selectedUser): ?>
        <div class="d-flex align-items-center p-3 border-bottom bg-light">
            <img src="<?= htmlspecialchars($selectedUser['avatar_url'] ?: 'img/user.png') ?>" class="rounded-circle me-3" width="40" height="40" style="object-fit: cover;">
            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($selectedUser['full_name']) ?> <span class="text-muted small">(@<?= htmlspecialchars($selectedUser['username']) ?>)</span></h6>
        </div>
        <div class="p-3 chat-box d-flex flex-column-reverse" id="chatBox">
            <?php foreach (array_reverse($messages) as $msg): ?>
                <?php if ($msg['from'] == $currentUser['id']): ?>
                    <!-- Gönderilen mesaj -->
                    <div class="d-flex justify-content-end mb-3">
                        <div class="p-2 bg-primary text-white rounded-3 shadow-sm" style="max-width: 70%;">
                            <?= htmlspecialchars($msg['message']) ?>
                            <div class="small text-end mt-1 opacity-75"><?= date('H:i', strtotime($msg['time'])) ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Alınan mesaj -->
                    <div class="d-flex justify-content-start mb-3">
                        <img src="<?= htmlspecialchars($selectedUser['avatar_url'] ?: 'img/user.png') ?>" class="rounded-circle me-2" width="30" height="30" style="object-fit: cover;">
                        <div class="p-2 bg-light rounded-3 shadow-sm" style="max-width: 70%;">
                            <?= htmlspecialchars($msg['message']) ?>
                            <div class="text-muted small text-end mt-1"><?= date('H:i', strtotime($msg['time'])) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="p-3 border-top">
            <form id="messageForm" onsubmit="sendMessage(event)">
                <div class="input-group">
                    <input type="text" id="messageInput" class="form-control rounded-pill-start" placeholder="Mesaj yazın..." required>
                    <button type="submit" class="btn btn-primary rounded-pill-end ms-2"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="d-flex align-items-center justify-content-center h-100">
            <div class="text-center text-muted">
                <i class="fa-regular fa-message fa-4x mb-3"></i>
                <h5>Bir sohbet seçin</h5>
                <p>Mesajlaşmaya başlamak için soldaki listeden bir kişi seçin</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- SAĞ PANEL -->
<aside class="col-lg-3 d-none d-lg-block bg-white border-start p-3">
 <h5 class="mb-3">İletişim Bilgileri</h5>
 <p class="text-muted small">Mesajlaşma alanında sağ panel genellikle boştur veya sohbet ettiğiniz kişinin profili gösterilir.</p>
</aside>

</div>
</div>

<!-- Yeni Mesaj Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-paper-plane"></i> Yeni Mesaj</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="searchUser" class="form-control mb-3" placeholder="Kullanıcı ara..." onkeyup="filterUsers()">
        <div class="list-group" id="userList">
          <?php foreach ($allUsers as $user): ?>
          <a href="javascript:void(0)" onclick="startNewConversation(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')" class="list-group-item list-group-item-action user-item" data-username="<?= strtolower($user['username']) ?>" data-fullname="<?= strtolower($user['full_name']) ?>">
            <div class="d-flex align-items-center">
              <img src="<?= htmlspecialchars($user['avatar_url'] ?: 'img/user.png') ?>" class="rounded-circle me-3" width="40" height="40" style="object-fit: cover;">
              <div>
                <h6 class="mb-0"><?= htmlspecialchars($user['full_name']) ?></h6>
                <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterUsers() {
    const searchInput = document.getElementById('searchUser').value.toLowerCase();
    const userItems = document.querySelectorAll('.user-item');
    
    userItems.forEach(item => {
        const username = item.getAttribute('data-username');
        const fullname = item.getAttribute('data-fullname');
        
        if (username.includes(searchInput) || fullname.includes(searchInput)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function sendMessage(e) {
    e.preventDefault();
    
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    const formData = new FormData();
    formData.append('to', <?= $selectedUserId ?>);
    formData.append('message', message);
    
    fetch('message_send.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'ok') {
            messageInput.value = '';
            location.reload(); // Mesajları yenile
        }
    })
    .catch(error => console.error('Error:', error));
}

// Otomatik scroll
const chatBox = document.getElementById('chatBox');
if (chatBox) {
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Yeni mesaj gönderme fonksiyonu
function startNewConversation(userId, username) {
    const message = prompt('Mesajınızı yazın:');
    if (!message || message.trim() === '') return;
    
    const formData = new FormData();
    formData.append('to', userId);
    formData.append('message', message.trim());
    
    fetch('message_send.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'ok') {
            window.location.href = 'mesajlar.php?u=' + userId;
        } else {
            alert('Mesaj gönderilemedi!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu!');
    });
}

</script>
</body>
</html>
