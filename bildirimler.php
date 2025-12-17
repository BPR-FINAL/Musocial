<?php
require_once __DIR__ . '/db.php';
start_session_once();

$currentUser = get_current_user_data();
$darkMode = is_dark_mode();

if (!$currentUser) {
    header("Location: login.php");
    exit;
}

$pdo = get_pdo();

// Bildirimleri çek
$stmt = $pdo->prepare("
    SELECT 
        n.id,
        n.type,
        n.post_id,
        n.is_read,
        n.created_at,
        u.id as sender_id,
        u.username,
        u.full_name,
        u.avatar_url
    FROM notifications n
    JOIN users u ON u.id = n.sender_id
    WHERE n.user_id = :uid
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([':uid' => $currentUser['id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Okunmamış bildirim sayısı
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
$stmt->execute([':uid' => $currentUser['id']]);
$unreadCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Musocial - Bildirimler</title>
<link rel="stylesheet" href="stylee.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<style>
body.dark {
    background:#0f1419;
    color:#e7e9ea;
}
body.dark .bg-white {
    background:#16181c !important;
}
body.dark .border-end,
body.dark .border-start,
body.dark .border-bottom {
    border-color:#2f3336 !important;
}
body.dark .text-dark, 
body.dark .nav-link.text-dark {
    color:#e7e9ea !important;
}
body.dark .list-group-item {
    background:#16181c !important;
    color:#e7e9ea !important;
    border-color:#2f3336 !important;
}
.notification-unread {
    background-color: #f0f8ff !important;
    border-left: 4px solid #1da1f2 !important;
}
body.dark .notification-unread {
    background-color: #1a2634 !important;
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
 <li class="nav-item mb-2"><a href="bildirimler.php" class="nav-link text-primary fw-bold"><i class="fa-solid fa-bell"></i> Bildirimler</a></li>
 <li class="nav-item mb-2"><a href="mesajlar.php" class="nav-link text-dark"><i class="fa-regular fa-message"></i> Mesajlar</a></li>
 <li class="nav-item mb-2"><a href="ayarlar.php" class="nav-link text-dark"><i class="fa-solid fa-gear"></i> Ayarlar</a></li>
 <?php if($currentUser): ?>
 <li class="nav-item"><a href="profil.php" class="nav-link text-success fw-semibold"><i class="fa-solid fa-circle-user"></i> @<?=htmlspecialchars($currentUser['username'])?></a></li>
 <?php endif; ?>
 <li class="nav-item"><a href="logout.php" class="nav-link text-dark"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a></li>
 </ul>
</nav>

<!-- ANA ALAN -->
<main class="col-md-6 col-lg-7 bg-white border-end p-0">
<div class="p-3 border-bottom sticky-top bg-white">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="mb-0 text-primary">
            <i class="fa-solid fa-bell me-2"></i> Bildirimler
            <?php if ($unreadCount > 0): ?>
                <span class="badge bg-danger rounded-pill"><?= $unreadCount ?></span>
            <?php endif; ?>
        </h3>
        <?php if (!empty($notifications)): ?>
        <button class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()">
            <i class="fa-solid fa-check-double"></i> Tümünü Okundu İşaretle
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="list-group list-group-flush">
<?php if (empty($notifications)): ?>
    <div class="text-center text-muted p-5">
        <i class="fa-regular fa-bell fa-4x mb-3"></i>
        <h5>Henüz bildiriminiz yok</h5>
        <p>Birisi sizi takip ettiğinde veya gönderilerinizi beğendiğinde burada göreceksiniz.</p>
    </div>
<?php else: ?>
    <?php foreach ($notifications as $n): ?>
    <div class="list-group-item list-group-item-action p-3 <?= $n['is_read'] == 0 ? 'notification-unread' : '' ?>" onclick="markAsRead(<?= $n['id'] ?>)">
        <div class="d-flex w-100 align-items-start">
            <div class="me-3">
                <?php if ($n['type'] === 'follow'): ?>
                    <i class="fa-solid fa-user-plus text-success fs-4"></i>
                <?php elseif ($n['type'] === 'like'): ?>
                    <i class="fa-solid fa-heart text-danger fs-4"></i>
                <?php elseif ($n['type'] === 'comment'): ?>
                    <i class="fa-solid fa-comment text-primary fs-4"></i>
                <?php else: ?>
                    <i class="fa-solid fa-bell text-info fs-4"></i>
                <?php endif; ?>
            </div>
            
            <a href="profil.php?u=<?= htmlspecialchars($n['username']) ?>" class="text-decoration-none">
                <img src="<?= htmlspecialchars($n['avatar_url'] ?: 'img/user.png') ?>"
                    class="rounded-circle me-3" width="45" height="45" style="object-fit:cover;">
            </a>
            
            <div class="flex-grow-1">
                <p class="mb-1">
                    <a href="profil.php?u=<?= htmlspecialchars($n['username']) ?>" class="fw-bold text-decoration-none">
                        <?= htmlspecialchars($n['full_name']) ?>
                    </a>
                    <span class="text-muted">@<?= htmlspecialchars($n['username']) ?></span>
                    
                    <?php if ($n['type'] === 'follow'): ?>
                        seni takip etmeye başladı
                    <?php elseif ($n['type'] === 'like'): ?>
                        gönderini beğendi
                    <?php elseif ($n['type'] === 'comment'): ?>
                        gönderine yorum yaptı
                    <?php else: ?>
                        sana bildirim gönderdi
                    <?php endif; ?>
                </p>
                <small class="text-muted">
                    <i class="fa-regular fa-clock"></i>
                    <?php
                        $time = strtotime($n['created_at']);
                        $diff = time() - $time;
                        if ($diff < 60) echo 'Az önce';
                        elseif ($diff < 3600) echo floor($diff/60) . ' dakika önce';
                        elseif ($diff < 86400) echo floor($diff/3600) . ' saat önce';
                        else echo date('d.m.Y H:i', $time);
                    ?>
                </small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</main>

<!-- SAĞ PANEL -->
<aside class="col-lg-3 d-none d-lg-block bg-white p-3">
 <h5 class="mb-3">Bildirim Özeti</h5>
 <div class="card border-0 bg-light p-3 mb-3">
     <div class="d-flex justify-content-between align-items-center">
         <span><i class="fa-solid fa-bell text-primary"></i> Toplam Bildirim</span>
         <strong><?= count($notifications) ?></strong>
     </div>
 </div>
 <div class="card border-0 bg-light p-3">
     <div class="d-flex justify-content-between align-items-center">
         <span><i class="fa-solid fa-envelope-open text-danger"></i> Okunmamış</span>
         <strong><?= $unreadCount ?></strong>
     </div>
 </div>
</aside>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function markAsRead(notificationId) {
    fetch('notification_mark_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + notificationId
    });
}

function markAllAsRead() {
    if (!confirm('Tüm bildirimleri okundu olarak işaretlemek istiyor musunuz?')) return;
    
    fetch('notification_mark_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'mark_all=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
        }
    });
}
</script>
</body>
</html>
