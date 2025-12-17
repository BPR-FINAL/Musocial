<?php
require_once __DIR__ . '/db.php';
start_session_once();

$pdo = get_pdo();

/* 🔹 FORM GÖNDERİM İŞLEMLERİ (Avatar ve Metin Alanları) */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile']) &&
    !empty($_SESSION['user_id'])
) {
    $userId = $_SESSION['user_id'];
    $updateFields = [];
    $updateParams = [':id' => $userId];

    // 1. Metin Alanlarını Toplama
    $newFullName = trim($_POST['full_name'] ?? '');
    $newUsername = trim($_POST['username'] ?? '');
    $newBio = trim($_POST['bio'] ?? '');

    if (!empty($newFullName)) {
        $updateFields[] = "full_name = :full_name";
        $updateParams[':full_name'] = $newFullName;
    }
    if (!empty($newUsername)) {
        // Kullanıcı adı benzersizlik kontrolü (Ek güvenlik için eklenmeli, burada sadece güncelliyoruz)
        $updateFields[] = "username = :username";
        $updateParams[':username'] = $newUsername;
    }
    // Biyografi boş bırakılabilir, bu yüzden sadece güncelliyoruz.
    $updateFields[] = "bio = :bio";
    $updateParams[':bio'] = $newBio;


    // 2. Avatar Yükleme Kontrolü
    if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === 0) {
        $uploadDir = 'uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (in_array($ext, $allowed)) {
            $fileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $target = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                $updateFields[] = "avatar_url = :avatar";
                $updateParams[':avatar'] = $target;
            }
        }
    }

    // 3. Güncellemeyi Yapma
    if (!empty($updateFields)) {
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateParams);
    }
    
    // Yönlendirme
    header("Location: profil.php");
    exit;
}

/* 🔹 AKTİF KULLANICI */
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
}

/* 🔹 PROFİL VERİLERİ */
$usernameParam = trim($_GET['u'] ?? '');
if ($usernameParam === '' && $currentUser) $usernameParam = $currentUser['username'];

$userStmt = $pdo->prepare('SELECT * FROM users WHERE username = :u');
$userStmt->execute([':u' => $usernameParam]);
$profile = $userStmt->fetch();

$posts = [];
if ($profile) {
    // Profilin dark mode ayarını çek (Kullanım kolaylığı için eklendi)
    $isDarkMode = $profile['dark_mode'] ?? false; 
    
    $p = $pdo->prepare('SELECT * FROM posts WHERE user_id = :id ORDER BY created_at DESC');
    $p->execute([':id' => $profile['id']]);
    $posts = $p->fetchAll();
}

$isOwnProfile = $currentUser && $profile && $currentUser['id'] == $profile['id'];

// Takipçi sayıları
$followersCount = 0;
if($profile) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = :id");
    $stmt->execute([':id' => $profile['id']]);
    $followersCount = $stmt->fetchColumn();
}

$followingCount = 0;
if($profile) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = :id");
    $stmt->execute([':id' => $profile['id']]);
    $followingCount = $stmt->fetchColumn();
}

// Takip durumu
$isFollowing = false;
if ($currentUser && $profile && !$isOwnProfile) {
    $stmt = $pdo->prepare("SELECT follower_id FROM followers WHERE follower_id = :me AND following_id = :profile");
    $stmt->execute([':me'=>$currentUser['id'], ':profile'=>$profile['id']]);
    $isFollowing = (bool)$stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Musocial - Profil</title>
<link rel="stylesheet" href="stylee.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<style>
/* Varsayılan Dark Mode stili */
body.dark {
    background:#0f1419 !important;
    color:#e7e9ea;
}
.dark .bg-white {
    background:#16181c !important;
}
.dark .border-end,
.dark .border-start,
.dark .border-bottom {
    border-color:#2f3336 !important;
}
.dark .text-dark,
.dark .nav-link.text-dark {
    color:#e7e9ea !important;
}
.dark .card {
    background:#16181c;
    border-color:#2f3336 !important;
}
</style>
</head>

<body class="<?= ($profile['dark_mode'] ?? false) ? 'dark' : 'bg-light' ?>">
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
<img src="img/logo.jpg" class="rounded-circle me-2" width="60" height="60" style="object-fit:cover;">
<h2 class="text-primary fw-bold mb-0">Musocial</h2>
</div>

<ul class="nav flex-column mt-4">
<li class="nav-item mb-2"><a href="index.php" class="nav-link text-dark"><i class="fa-regular fa-house"></i> Ana Sayfa</a></li>
<li class="nav-item mb-2"><a href="kesfet.php" class="nav-link text-dark"><i class="fa-regular fa-compass"></i> Keşfet</a></li>
<li class="nav-item mb-2"><a href="bildirimler.php" class="nav-link text-dark"><i class="fa-regular fa-bell"></i> Bildirimler</a></li>
<li class="nav-item mb-2"><a href="mesajlar.php" class="nav-link text-dark"><i class="fa-regular fa-message"></i> Mesajlar</a></li>
<li class="nav-item mb-2"><a href="ayarlar.php" class="nav-link text-dark"><i class="fa-solid fa-gear"></i> Ayarlar</a></li>

<?php if ($currentUser): ?>
<li class="nav-item mb-2 mt-3">
<a href="profil.php" class="nav-link text-success fw-semibold" style="text-decoration:none">
<i class="fa-solid fa-circle-user"></i> @<?= htmlspecialchars($currentUser['username']) ?>
</a>
</li>
<?php endif; ?>

<li class="nav-item mb-2"><a href="logout.php" class="nav-link text-dark"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a></li>
</ul>
</nav>

<main class="col-md-6 col-lg-7 p-0 bg-white border-end">

<?php if (!$profile): ?>
<div class="p-4"><div class="alert alert-danger">Profil bulunamadı.</div></div>
<?php else: ?>

<div class="p-3 border-bottom sticky-top bg-white">
<h5 class="mb-0 fw-bold"><?= htmlspecialchars($profile['full_name']) ?>
<span class="text-muted small ms-2">(<?= count($posts) ?> Gönderi)</span></h5>

</div>

<div class="card border-0 rounded-0">
<div style="height:200px;background:linear-gradient(135deg,#1d9bf0,#9c27b0);"></div>

<div class="card-body position-relative" style="margin-top:-50px;">
<div class="d-flex align-items-center">
<img src="<?= htmlspecialchars($profile['avatar_url'] ?? 'img/user.png') ?>"
class="rounded-circle border border-3 border-white me-3"
width="100" height="100" style="object-fit:cover;">
<div>
<h4 class="mb-0"><?= htmlspecialchars($profile['full_name']) ?></h4>
<p class="text-muted mb-1">@<?= htmlspecialchars($profile['username']) ?></p>
<small class="text-muted">Katılım: <?= date('d.m.Y', strtotime($profile['created_at'])) ?></small>
</div>
</div>
<div class="mt-1 text-muted small">
<span><strong><?= $followersCount ?></strong> Takipçi</span> •
<span><strong><?= $followingCount ?></strong> Takip</span>
</div>

<?php if ($profile['bio']): ?>
<p class="mt-3"><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
<?php endif; ?>

<?php if ($isOwnProfile): ?>
<div class="mt-3">
<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
Profili Düzenle
</button>
<a class="btn btn-outline-secondary btn-sm" href="index.php">Akışa Dön</a>
</div>
<?php elseif ($currentUser): ?>
<button class="btn btn-sm btn-primary follow-btn" data-user-id="<?= $profile['id'] ?>">
<?= $isFollowing ? 'Takip Ediliyor' : 'Takip Et' ?>
</button>
<?php endif; ?>
</div>
</div>

<div class="p-3 border-bottom bg-white">
<ul class="nav nav-tabs border-0">
<li class="nav-item"><span class="nav-link active text-primary fw-semibold">Gönderiler</span></li>
</ul>
</div>

<?php if (!$posts): ?>
<p class="text-muted p-4">Henüz gönderi yok.</p>
<?php else: foreach ($posts as $post): ?>
<div class="card border-0 border-bottom rounded-0">
<div class="card-body position-relative">
<p><?= nl2br(htmlspecialchars($post['content'])) ?></p>

<?php if ($post['image_url']): ?>
<img src="<?= htmlspecialchars($post['image_url']) ?>" class="img-fluid rounded mb-2">
<?php endif; ?>

<div class="d-flex justify-content-between text-muted small">
<span><?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></span>
<?php if ($post['tags']): ?><span>#<?= htmlspecialchars($post['tags']) ?></span><?php endif; ?>
</div>

<?php if ($isOwnProfile): ?>
<button class="btn btn-sm btn-outline-danger mt-2 delete-post-btn" data-post-id="<?= $post['id'] ?>">Sil</button>
<?php endif; ?>

</div>
</div>
<?php endforeach; endif; ?>
<?php endif; ?>


</main>

<aside class="col-lg-3 d-none d-lg-block bg-white border-start p-3">
<h5>İpucu</h5>
<p class="text-muted small">URL'de <code>?u=kullanici</code> yazarak başka profillere bakabilirsin.</p>
</aside>

</div>
</div>

<?php if ($isOwnProfile): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1">
<div class="modal-dialog">
<form method="post" enctype="multipart/form-data" class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Profili Düzenle</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<input type="hidden" name="update_profile" value="1">
<input type="hidden" name="current_avatar" value="<?= htmlspecialchars($profile['avatar_url'] ?? '') ?>">

<div class="mb-2">
<label class="form-label">Profil Fotoğrafı</label>
<input type="file" name="avatar" class="form-control">
</div>

<div class="mb-2">
<label class="form-label">Ad Soyad</label>
<input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($profile['full_name']) ?>">
</div>

<div class="mb-2">
<label class="form-label">Kullanıcı Adı</label>
<input type="text" name="username" class="form-control" value="<?= htmlspecialchars($profile['username']) ?>">
</div>

<div class="mb-2">
<label class="form-label">Biyografi</label>
<textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
</div>
</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
<button type="submit" class="btn btn-primary">Kaydet</button>
</div>
</form>
</div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// JavaScript kodu aynı kalmıştır.
document.querySelectorAll('.follow-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const userId = btn.getAttribute('data-user-id');
        fetch('follow.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'user_id=' + userId
        }).then(res => res.json()).then(data => {
            if(data.status === 'followed') btn.textContent = 'Takip Ediliyor';
            else if(data.status === 'unfollowed') btn.textContent = 'Takip Et';
        });
    });
});
document.querySelectorAll('.delete-post-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        if(!confirm('Bu gönderiyi silmek istediğine emin misin?')) return;
        const postId = btn.getAttribute('data-post-id');
        fetch('delete_post.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'post_id=' + postId
        }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                btn.closest('.card').remove();
            } else {
                alert(data.message);
            }
        });
    });
});

</script>
</body>
</html>