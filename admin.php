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

// Kullanıcı istatistikleri
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as active FROM users WHERE is_banned = 0");
$activeUsers = $stmt->fetch()['active'];

$stmt = $pdo->query("SELECT COUNT(*) as banned FROM users WHERE is_banned = 1");
$bannedUsers = $stmt->fetch()['banned'];

$stmt = $pdo->query("SELECT COUNT(*) as new_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$newUsers = $stmt->fetch()['new_users'];

// Tüm kullanıcıları çek
$stmt = $pdo->query("SELECT id, full_name, username, email, is_banned, is_admin, avatar_url, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kullanıcılar | Yönetim Paneli</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
 <link rel="stylesheet" href="admin_style.css">
<body class="<?= $darkMode ? 'dark' : '' ?>">

  <div class="sidebar">
    <div>
      <div class="logo">Musocial Admin</div>
      <ul>
        <li><a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a></li>
        <li><a href="admin.php" class="active"><i class="fa-solid fa-users"></i> <span>Kullanıcılar</span></a></li>
        <li><a href="gonderiler.php"><i class="fa-solid fa-file-lines"></i> <span>Gönderiler</span></a></li>
        <li><a href="adminayarlar.php"><i class="fa-solid fa-gear"></i> <span>Ayarlar</span></a></li>
        <li><a href="index.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>Siteye Git</span></a></li>
      </ul>
    </div>
    <footer>© 2025 Musocial</footer>
  </div>

  <main>
    <header>
      <input type="text" placeholder="Kullanıcı ara...">
      <div class="user">
        <span><?= htmlspecialchars($currentUser['username']) ?></span>
        <img src="<?= htmlspecialchars($currentUser['avatar_url'] ?: 'https://i.pravatar.cc/100?img=12') ?>" alt="Admin">
      </div>
    </header>

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
        <h3><i class="fa-solid fa-user-plus"></i> Yeni Kayıtlar (24s)</h3>
        <p><?= $newUsers ?></p>
      </div>
    </div>

    <div class="table-box">
      <h2>Kullanıcı Yönetimi</h2>
      <div style="margin:15px 0;">
        <button class="add primary-btn"><i class="fa-solid fa-plus"></i> Yeni Kullanıcı Ekle</button>
      </div>
      <table>
        <thead>
          <tr>
            <th>Ad Soyad</th>
            <th>Kullanıcı Adı</th>
            <th>Email</th>
            <th>Durum</th>
            <th>Rol</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['full_name']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
              <?php if ($user['is_banned']): ?>
                <span class="status-banned">Banlı</span>
              <?php else: ?>
                <span class="status-active">Aktif</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($user['is_admin']): ?>
                <span class="status-active">Admin</span>
              <?php else: ?>
                <span>Kullanıcı</span>
              <?php endif; ?>
            </td>
            <td class="actions">
              <button class="edit" onclick="toggleBan(<?= $user['id'] ?>, <?= $user['is_banned'] ?>)">
                <i class="fa-solid fa-<?= $user['is_banned'] ? 'user-check' : 'ban' ?>"></i> 
                <?= $user['is_banned'] ? 'Banı Kaldır' : 'Banla' ?>
              </button>
              <?php if (!$user['is_admin']): ?>
              <button class="edit" onclick="makeAdmin(<?= $user['id'] ?>)">
                <i class="fa-solid fa-user-shield"></i> Admin Yap
              </button>
              <?php else: ?>
              <button class="edit" onclick="removeAdmin(<?= $user['id'] ?>)">
                <i class="fa-solid fa-user-minus"></i> Admin Kaldır
              </button>
              <?php endif; ?>
              <button class="delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                <i class="fa-solid fa-trash-can"></i> Sil
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <script>
  function toggleBan(userId, isBanned) {
    const action = isBanned ? 'unban' : 'ban';
    const message = isBanned ? 'Banı kaldırmak istediğinizden emin misiniz?' : 'Bu kullanıcıyı banlamak istediğinizden emin misiniz?';
    
    if (!confirm(message)) return;
    
    performAction(action, userId);
  }
  
  function makeAdmin(userId) {
    if (!confirm('Bu kullanıcıya admin yetkisi vermek istediğinizden emin misiniz?')) return;
    performAction('make_admin', userId);
  }
  
  function removeAdmin(userId) {
    if (!confirm('Bu kullanıcının admin yetkisini kaldırmak istediğinizden emin misiniz?')) return;
    performAction('remove_admin', userId);
  }
  
  function deleteUser(userId, username) {
    if (!confirm(`"${username}" kullanıcısını kalıcı olarak silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!`)) return;
    performAction('delete', userId);
  }
  
  function performAction(action, userId) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('user_id', userId);
    
    fetch('admin_actions.php', {
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