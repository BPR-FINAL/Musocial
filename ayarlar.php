<?php
require_once __DIR__ . '/db.php';
start_session_once();

// Mevcut kullanıcıyı çek
$currentUser = get_current_user_data();
$darkMode = is_dark_mode();


if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = get_pdo();

/* 🔹 Aktif kullanıcı detaylarını tekrar çek */
$stmt = $pdo->prepare("
    SELECT id, username, email, is_private, dark_mode, avatar_url
    FROM users WHERE id = :id
");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// Varsayılan avatar yolu
$defaultAvatar = 'img/user.png';
if ($user && empty($user['avatar_url'])) {
    $user['avatar_url'] = $defaultAvatar;
}

/* 🔹 MENU UYUMU (güncel kullanıcı verilerini kullan) */
$currentUser = $user;

/* 🔹 Ayar işlemleri */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Mesaj değişkenini sıfırla
    $message = null;
    $message_type = null;

    // Gizli Hesap değiştirme
    if (isset($_POST['toggle_private'])) {
        $pdo->prepare("
            UPDATE users SET is_private = IF(is_private=1,0,1)
            WHERE id = :id
        ")->execute([':id' => $user['id']]);
    }

    // Karanlık Mod değiştirme
    if (isset($_POST['toggle_dark'])) {
        $pdo->prepare("
            UPDATE users SET dark_mode = IF(dark_mode=1,0,1)
            WHERE id = :id
        ")->execute([':id' => $user['id']]);
    }

    // Şifre Güncelleme
    if (!empty($_POST['new_password'])) {
        $newPassword = trim($_POST['new_password']);
        
        if (strlen($newPassword) >= 6) { // Minimum 6 karakter şartı
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("
                UPDATE users SET password_hash = :p WHERE id = :id
            ")->execute([
                ':p' => $hash,
                ':id' => $user['id']
            ]);
            
            // Başarılı mesajı ayarla
            $message = 'Şifreniz başarıyla güncellendi.';
            $message_type = 'success';
            
        } else {
            // Başarısız mesajı ayarla (min karakter sağlanamadı)
            $message = 'Şifreniz güncellenemedi. Yeni şifre en az 6 karakter olmalıdır.';
            $message_type = 'danger';
        }
    }

    // Hesap Silme
    if (isset($_POST['delete_account'])) {
        try {
            $userId = $user['id'];
            
            // İlgili tüm verileri sil (Foreign key cascade ile çoğu otomatik silinecek)
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = :id1 OR sender_id = :id2");
            $stmt->execute([':id1' => $userId, ':id2' => $userId]);
            
            $pdo->prepare("DELETE FROM likes WHERE user_id = :id")->execute([':id' => $userId]);
            $pdo->prepare("DELETE FROM comments WHERE user_id = :id")->execute([':id' => $userId]);
            $pdo->prepare("DELETE FROM posts WHERE user_id = :id")->execute([':id' => $userId]);
            
            $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = :id1 OR following_id = :id2");
            $stmt->execute([':id1' => $userId, ':id2' => $userId]);
            
            // Mesaj dosyalarını sil
            $messagesDir = __DIR__ . '/data/messages';
            if (is_dir($messagesDir)) {
                $files = glob($messagesDir . '/conversation_*_*.txt');
                foreach ($files as $file) {
                    if (preg_match('/conversation_(\d+)_(\d+)\.txt/', basename($file), $matches)) {
                        if ($matches[1] == $userId || $matches[2] == $userId) {
                            @unlink($file);
                        }
                    }
                }
            }
            
            // Son olarak kullanıcıyı sil
            $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $userId]);
            
            // Oturumu sonlandır
            session_destroy();
            header("Location: register.php?deleted=1");
            exit;
        } catch (Exception $e) {
            $message = 'Hesap silinirken bir hata oluştu: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    
    // Yönlendirmeden önce mesajları Session'a kaydet
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
    }

    // İşlem bitince ayarlar sayfasına geri dön
    header("Location: ayarlar.php");
    exit;
}

// Yönlendirmeden sonra Session'daki mesajları kontrol et
$alert_message = null;
$alert_type = null;

if (isset($_SESSION['message'])) {
    $alert_message = $_SESSION['message'];
    $alert_type = $_SESSION['message_type'] ?? 'info';
    
    // Mesajı gösterdikten sonra session'dan temizle
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Musocial - Ayarlar</title>
<link rel="stylesheet" href="stylee.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

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
</style>
</head>

<body class="<?= $user['dark_mode'] ? 'dark' : '' ?>">

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
        <li class="nav-item"><a href="profil.php" class="nav-link text-success fw-semibold"><i class="fa-solid fa-circle-user"></i> @<?=htmlspecialchars($currentUser['username'])?></a></li>
        <?php endif; ?>
        <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid">
<div class="row min-vh-100">

<nav class="col-md-3 col-lg-2 bg-white border-end p-3 d-none d-md-block">
<div class="d-flex align-items-center">
<img src="img/logo.jpg" class="rounded-circle me-2" width="60" height="60" style="object-fit: cover;">
<h2 class="text-primary fw-bold mb-0">Musocial</h2>
</div>

<ul class="nav flex-column mt-4">
<li class="nav-item mb-2"><a href="index.php" class="nav-link text-dark"><i class="fa-regular fa-house"></i> Ana Sayfa</a></li>
<li class="nav-item mb-2"><a href="kesfet.php" class="nav-link text-dark"><i class="fa-regular fa-compass"></i> Keşfet</a></li>
<li class="nav-item mb-2"><a href="bildirimler.php" class="nav-link text-dark"><i class="fa-regular fa-bell"></i> Bildirimler</a></li>
<li class="nav-item mb-2"><a href="mesajlar.php" class="nav-link text-dark"><i class="fa-regular fa-message"></i> Mesajlar</a></li>
<li class="nav-item mb-2"><a href="ayarlar.php" class="nav-link text-primary fw-bold"><i class="fa-solid fa-gear"></i> Ayarlar</a></li>

<?php if ($currentUser): ?>
<a href="profil.php"style="text-decoration:none"><li class="nav-item mb-2 mt-3">
<span class="nav-link text-success fw-semibold">
<i class="fa-solid fa-circle-user"></i> @<?= htmlspecialchars($currentUser['username']) ?>
</span>
</li></a>
<?php endif; ?>

<li class="nav-item mb-2"><a href="logout.php" class="nav-link text-dark"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a></li>
</ul>
</nav>

<main class="col-md-6 col-lg-7 p-0 bg-white border-end">

<div class="p-3 border-bottom sticky-top bg-white">
<h5 class="fw-bold mb-0">Ayarlar</h5>
</div>

<div class="p-4">

<?php if ($alert_message): ?>
    <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($alert_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center border-bottom py-3">
<div>
<strong>Gizli Hesap</strong>
<p class="text-muted small mb-0">Takip isteği gerekir</p>
</div>
<form method="post">
<button name="toggle_private" class="btn btn-outline-primary btn-sm">
<?= $user['is_private'] ? 'Kapalı' : 'Açık' ?>
</button>
</form>
</div>

<div class="d-flex justify-content-between align-items-center border-bottom py-3">
<div>
<strong>Karanlık Mod</strong>
<p class="text-muted small mb-0">Gece görünümü</p>
</div>
<form method="post">
<button name="toggle_dark" class="btn btn-outline-secondary btn-sm">
<?= $user['dark_mode'] ? 'Kapalı' : 'Açık' ?>
</button>
</form>
</div>

<div class="border-bottom py-3">
<strong>Şifre Değiştir</strong>
<form method="post" class="mt-2">
<input type="password" name="new_password" class="form-control mb-2" placeholder="Yeni şifre (min. 6 karakter)">
<button class="btn btn-outline-warning btn-sm">Güncelle</button>
</form>
</div>

<div class="py-3">
<strong class="text-danger">Hesap Sil</strong>
<p class="small text-muted mb-2">Bu işlem geri alınamaz</p>
<form method="post" onsubmit="return confirm('Hesabını silmek istediğine emin misin?');">
<button name="delete_account" class="btn btn-danger btn-sm">
Hesabımı Sil
</button>
</form>
</div>

</div>
</main>

<aside class="col-lg-3 d-none d-lg-block bg-white border-start p-3">
<h6 class="fw-bold">İpucu</h6>
<p class="text-muted small">
Ayarlarını düzenli kontrol etmek hesabını güvende tutar.
</p>
</aside>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>