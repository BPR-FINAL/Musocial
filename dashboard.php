<?php 
require_once 'db.php';
start_session_once();

// Admin kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();
$currentUser = get_current_user_data();

if (!$currentUser || (int)$currentUser['is_admin'] !== 1) {
    die('Bu sayfaya erişim yetkiniz yok!');
}

$darkMode = is_dark_mode();

// İstatistikler
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as active FROM users WHERE is_banned = 0");
$activeUsers = $stmt->fetch()['active'];

$stmt = $pdo->query("SELECT COUNT(*) as banned FROM users WHERE is_banned = 1");
$bannedUsers = $stmt->fetch()['banned'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts");
$totalPosts = $stmt->fetch()['total'];

// Son 5 gönderi
$stmt = $pdo->query("
    SELECT p.id, p.content, p.created_at, u.username, u.full_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentPosts = $stmt->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard | Yönetim Paneli</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
 <link rel="stylesheet" href="admin_style.css">
<body class="<?= $darkMode ? 'dark' : '' ?>">
<div class="sidebar">
  <div>
    <div class="logo">Musocial Admin</div>
    <ul>
      <li><a href="dashboard.php" class="active"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a></li>
      <li><a href="admin.php"><i class="fa-solid fa-users"></i> <span>Kullanıcılar</span></a></li>
      <li><a href="gonderiler.php"><i class="fa-solid fa-file-lines"></i> <span>Gönderiler</span></a></li>
      <li><a href="adminayarlar.php"> <i class="fa-solid fa-gear"></i> <span>Ayarlar</span></a></li>
      <li><a href="index.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>Siteye Git</span></a></li>
    </ul>
  </div>
  <footer>© 2025 Musocial</footer>
</div>
<main>
  <header>
    <input type="text" placeholder="Arama yap...">
    <div class="user">
      <span><?= htmlspecialchars($currentUser['username']) ?></span>
      <img src="<?= htmlspecialchars($currentUser['avatar_url'] ?: 'https://i.pravatar.cc/100?img=12') ?>" alt="Admin">
    </div>
  </header>

  <h2>Genel Durum</h2>

  <div class="cards">
    <div class="card">
      <h3><i class="fa-solid fa-user-group"></i> Toplam Kullanıcı</h3>
      <p><?= $totalUsers ?></p>
    </div>
    <div class="card">
      <h3><i class="fa-solid fa-user-check"></i> Aktif Kullanıcı</h3>
      <p><?= $activeUsers ?></p>
    </div>
    <div class="card">
      <h3><i class="fa-solid fa-user-slash"></i> Banlı Kullanıcı</h3>
      <p><?= $bannedUsers ?></p>
    </div>
    <div class="card">
      <h3><i class="fa-solid fa-file-lines"></i> Toplam Gönderi</h3>
      <p><?= $totalPosts ?></p>
    </div>
  </div>

  <div class="table-box">
    <h3>Son 5 Gönderi</h3>
    <table>
<thead>
  <tr><th>İçerik</th><th>Yazar</th><th>Tarih</th></tr>
</thead>
<tbody>
  <?php foreach ($recentPosts as $post): ?>
  <tr>
    <td><?= htmlspecialchars(mb_substr($post['content'], 0, 50)) . (mb_strlen($post['content']) > 50 ? '...' : '') ?></td>
    <td><?= htmlspecialchars($post['full_name']) ?></td>
    <td><?= date('d.m.Y', strtotime($post['created_at'])) ?></td>
  </tr>
  <?php endforeach; ?>
</tbody>
    </table>
  </div>
</main>
</body>
</html>