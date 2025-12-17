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

// Tüm gönderileri çek
$stmt = $pdo->query("
    SELECT p.id, p.content, p.image_url, p.created_at, u.username, u.full_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");
$posts = $stmt->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gönderiler | Yönetim Paneli</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
 <link rel="stylesheet" href="admin_style.css">

<body class="<?= $darkMode ? 'dark' : '' ?>">
<div class="sidebar">
  <div>
    <div class="logo">Musocial Admin</div>
    <ul>
      <li><a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a></li>
      <li><a href="admin.php"><i class="fa-solid fa-users"></i> <span>Kullanıcılar</span></a></li>
      <li><a href="gonderiler.php" class="active"><i class="fa-solid fa-file-lines"></i> <span>Gönderiler</span></a></li>
      <li><a href="adminayarlar.php"><i class="fa-solid fa-gear"></i> <span>Ayarlar</span></a></li>
      <li><a href="index.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>Siteye Git</span></a></li>
    </ul>
  </div>
  <footer>© 2025 Musocial</footer>
</div>
<main>
  <header>
    <input type="text" placeholder="Gönderi ara...">
    <div class="user">
      <span><?= htmlspecialchars($currentUser['username']) ?></span>
      <img src="<?= htmlspecialchars($currentUser['avatar_url'] ?: 'https://i.pravatar.cc/100?img=12') ?>" alt="Admin">
    </div>
  </header>

  <div class="form-box">
    <h3><i class="fa-solid fa-plus-circle"></i> Yeni Gönderi Oluştur</h3>
    <form>
      <input type="text" placeholder="Başlık" required>
      <textarea rows="5" placeholder="Gönderi içeriği..." required></textarea>
      <input type="text" placeholder="Yazar Adı" required>
      <button class="primary-btn"><i class="fa-solid fa-share-nodes"></i> Paylaş</button>
    </form>
  </div>

  <div class="table-box">
    <h3><i class="fa-solid fa-list-ul"></i> Gönderi Listesi</h3>
    <table>
<thead>
  <tr><th>İçerik</th><th>Yazar</th><th>Tarih</th><th>İşlemler</th></tr>
</thead>
<tbody>
  <?php foreach ($posts as $post): ?>
  <tr>
    <td><?= htmlspecialchars(mb_substr($post['content'], 0, 60)) . (mb_strlen($post['content']) > 60 ? '...' : '') ?></td>
    <td><?= htmlspecialchars($post['full_name']) ?> (@<?= htmlspecialchars($post['username']) ?>)</td>
    <td><?= date('d.m.Y', strtotime($post['created_at'])) ?></td>
    <td class="actions">
      <button class="delete" onclick="deletePost(<?= $post['id'] ?>)"><i class="fa-solid fa-trash-can"></i> Sil</button>
    </td>
  </tr>
  <?php endforeach; ?>
</tbody>
    </table>
  </div>
</main>

<script>
function deletePost(postId) {
  if (!confirm('Bu gönderiyi kalıcı olarak silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) return;
  
  const formData = new FormData();
  formData.append('post_id', postId);
  
  fetch('admin_delete_post.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      alert(data.message);
      location.reload();
    } else {
      alert('Hata: ' + data.message);
    }
  })
  .catch(error => {
    alert('İşlem başarısız: ' + error);
  });
}
</script>

</body>
</html>