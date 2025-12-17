<?php
require_once __DIR__ . '/db.php';
start_session_once();

$currentUser = get_current_user_data();
$darkMode = is_dark_mode();


$pdo = get_pdo();

// Mevcut kullanıcıyı al
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare('SELECT id, full_name, username, avatar_url FROM users WHERE id = :id');
    $stmtUser->execute([':id' => $_SESSION['user_id']]);
    $currentUser = $stmtUser->fetch() ?: null;
}

// Trendler
$trends = $pdo->query("
SELECT tags, COUNT(*) as cnt
FROM posts
WHERE tags IS NOT NULL AND tags <> ''
GROUP BY tags
ORDER BY cnt DESC
LIMIT 5
")->fetchAll();

// Takip önerileri
$suggestions = $pdo->query("
SELECT id, full_name, username, avatar_url
FROM users
ORDER BY created_at DESC
LIMIT 5
")->fetchAll();

// Kullanıcının takip ettiği kişiler (takip durumu için)
$followingIds = [];
if ($currentUser) {
    $f = $pdo->prepare("SELECT following_id FROM follows WHERE follower_id = :id");
    $f->execute([':id'=>$currentUser['id']]);
    $followingIds = $f->fetchAll(PDO::FETCH_COLUMN);
}

// Öne çıkan gönderiler
$tagFilter = trim($_GET['tag'] ?? '');
if ($tagFilter !== '') {
    $stmtPosts = $pdo->prepare("
        SELECT p.id, p.content, p.image_url, p.created_at, p.tags,
               u.username, u.avatar_url
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.tags LIKE :tag
        ORDER BY p.created_at DESC
        LIMIT 4
    ");
    $stmtPosts->execute([':tag' => "%$tagFilter%"]);
} else {
    $stmtPosts = $pdo->query("
        SELECT p.id, p.content, p.image_url, p.created_at, p.tags,
               u.username, u.avatar_url
        FROM posts p
        JOIN users u ON u.id = p.user_id
        ORDER BY p.created_at DESC
        LIMIT 4
    ");
}
$featured = $stmtPosts->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <title>Musocial - Keşfet</title>
 <link rel="stylesheet" href="stylee.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
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
        <img src="img/logo.jpg" class="rounded-circle me-2" alt="" width="60" height="60" style="object-fit: cover;">
        <h2 class="text-primary fw-bold mb-0">Musocial</h2>
      </div>

      <ul class="nav flex-column mt-4">
  <li class="nav-item mb-2"><a href="index.php" class="nav-link text-dark"><i class="fa-regular fa-house"></i> Ana Sayfa</a></li>
  <li class="nav-item mb-2"><a href="kesfet.php" class="nav-link text-dark"><i class="fa-regular fa-compass"></i> Keşfet</a></li>
  <li class="nav-item mb-2"><a href="bildirimler.php" class="nav-link text-dark"><i class="fa-regular fa-bell"></i> Bildirimler</a></li>
  <li class="nav-item mb-2"><a href="mesajlar.php" class="nav-link text-dark"><i class="fa-regular fa-message"></i> Mesajlar</a></li>
  <li class="nav-item mb-2"><a href="ayarlar.php" class="nav-link text-dark"><i class="fa-solid fa-gear"></i> Ayarlar</a></li>

  <?php if ($currentUser): ?>
  <a href="profil.php" style="text-decoration:none"><li class="nav-item mb-2 mt-3">
  <span class="nav-link text-success fw-semibold">
  <i class="fa-solid fa-circle-user"></i> @<?= htmlspecialchars($currentUser['username']) ?>
  </span>
  </li></a>
  <?php endif; ?>

  <li class="nav-item mb-2"><a href="logout.php" class="nav-link text-dark"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a></li>
  </ul>
  </nav>

    <main class="col-md-6 col-lg-7 p-4">
      <h3 class="mb-4 text-primary"><i class="fa-solid fa-compass me-2"></i> Keşfet</h3>

      <div class="input-group mb-4">
        <input type="text" class="form-control rounded-pill pe-5" placeholder="Musocial'da Ara...">
        <span class="input-group-text bg-transparent border-0 position-absolute end-0" style="z-index: 1;">
          <i class="fa-solid fa-magnifying-glass text-muted"></i>
        </span>
      </div>

      <div class="card mb-4">
  <div class="card-header bg-white fw-bold">🔥 Popüler Trendler</div>
  <ul class="list-group list-group-flush">
    <?php if (!$trends): ?>
      <li class="list-group-item text-muted">Trend bulunamadı.</li>
    <?php else: ?>
      <?php foreach ($trends as $trend): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <a href="index.php?tag=<?= urlencode($trend['tags']) ?>" class="text-decoration-none text-dark">
            #<?= htmlspecialchars($trend['tags']) ?> 
            <div class="text-muted small"><?= (int)$trend['cnt'] ?> Gönderi</div>
          </a>
          <i class="fa-solid fa-chevron-right text-muted"></i>
        </li>
      <?php endforeach; ?>
    <?php endif; ?>
  </ul>
</div>


      <div class="card mb-4">
        <div class="card-header bg-white fw-bold">👥 Takip Edebileceğin Kişiler</div>
        <ul class="list-group list-group-flush">
          <?php foreach ($suggestions as $user): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <img src="<?= htmlspecialchars($user['avatar_url'] ?? 'img/logo.jpg') ?>" class="rounded-circle me-3" alt="Profil" width="50" height="50" style="object-fit: cover;">
                <div>
                  <h6 class="mb-0"><?= htmlspecialchars($user['full_name']) ?></h6>
                  <p class="text-muted small mb-0">@<?= htmlspecialchars($user['username']) ?></p>
                </div>
              </div>
              <?php if ($currentUser && $currentUser['id'] != $user['id']): ?>
              <button class="btn btn-sm btn-primary follow-btn" data-user-id="<?= $user['id'] ?>">
                <?= in_array($user['id'], $followingIds) ? 'Takip Ediliyor' : 'Takip Et' ?>
              </button>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <h4 class="mt-4 mb-3"><?= $tagFilter ? "#".htmlspecialchars($tagFilter)." Gönderileri" : "Öne Çıkan Gönderiler" ?></h4>
      <?php foreach ($featured as $post): ?>
        <div class="card mb-3 post shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <img src="<?= htmlspecialchars($post['avatar_url'] ?? 'img/logo.jpg') ?>" class="rounded-circle me-2" alt="Profil Fotoğrafı" width="60" height="60" style="object-fit: cover;">
              <div>
                <h6 class="card-title mb-0">@<?= htmlspecialchars($post['username']) ?></h6>
                <p class="text-muted small mb-0"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($post['created_at']))) ?></p>
              </div>
            </div>
            <p class="card-text mt-2"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
            <?php if (!empty($post["image_url"])): ?>
              <img src="<?= htmlspecialchars($post["image_url"]) ?>" alt="" class="img-fluid rounded" style="max-height: 500px; object-fit: cover;">
            <?php endif; ?>
            <div class="d-flex justify-content-start gap-3 mt-2">
              <button class="btn btn-sm btn-outline-danger action-btn like-btn"><i class="fa-solid fa-heart"></i> Beğen</button>
              <button class="btn btn-sm btn-outline-secondary action-btn comment-btn"><i class="fa-regular fa-comment"></i> Yorum</button>
              <button class="btn btn-sm btn-outline-primary action-btn share-btn"><i class="fa-solid fa-share"></i> Paylaş</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </main>

    <aside class="col-lg-3 d-none d-lg-block bg-white border-start p-3">
      <h5 class="mb-3">İpucu</h5>
      <p class="text-muted small">Etiketlere tıklayıp akışa dönerek aradığın başlıklardaki gönderileri filtreleyebilirsin.</p>
    </aside>

  </div>
 </div>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
 <script>
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
</script>
</body>
</html>
