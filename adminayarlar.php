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
$successMessage = '';

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tema ayarını güncelleme
    if (isset($_POST['theme'])) {
        $newTheme = ($_POST['theme'] === 'dark') ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET dark_mode = :theme WHERE id = :id");
        $stmt->execute([':theme' => $newTheme, ':id' => $currentUser['id']]);
        $successMessage = 'Tema ayarı güncellendi!';
        header("Location: adminayarlar.php?success=theme");
        exit();
    }
    
    // Site ayarlarını güncelleme
    if (isset($_POST['site_title'])) {
        $newTitle = htmlspecialchars(trim($_POST['site_title']));
        $newDesc = htmlspecialchars(trim($_POST['site_desc'] ?? ''));
        
        $stmt = $pdo->prepare("
            INSERT INTO site_settings (setting_key, setting_value) 
            VALUES ('site_title', :title)
            ON DUPLICATE KEY UPDATE setting_value = :title
        ");
        $stmt->execute([':title' => $newTitle]);
        
        $stmt = $pdo->prepare("
            INSERT INTO site_settings (setting_key, setting_value) 
            VALUES ('site_description', :desc)
            ON DUPLICATE KEY UPDATE setting_value = :desc
        ");
        $stmt->execute([':desc' => $newDesc]);
        
        header("Location: adminayarlar.php?success=settings");
        exit();
    }
}

// Mevcut ayarları çek
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$currentSiteTitle = $settings['site_title'] ?? 'Musocial Blog';
$currentSiteDesc = $settings['site_description'] ?? '';

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'theme') {
        $successMessage = 'Tema ayarı başarıyla güncellendi!';
    } elseif ($_GET['success'] === 'settings') {
        $successMessage = 'Site ayarları başarıyla güncellendi!';
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ayarlar | Yönetim Paneli</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="admin_style.css"> 
</head>
<body class="<?= $darkMode ? 'dark' : '' ?>">
<div class="sidebar">
  <div>
    <div class="logo">Musocial Admin</div>
    <ul>
      <li><a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a></li>
      <li><a href="admin.php"><i class="fa-solid fa-users"></i> <span>Kullanıcılar</span></a></li>
      <li><a href="gonderiler.php"><i class="fa-solid fa-file-lines"></i> <span>Gönderiler</span></a></li>
      <li><a href="adminayarlar.php" class="active"><i class="fa-solid fa-gear"></i> <span>Ayarlar</span></a></li>
      <li><a href="index.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>Siteye Git</span></a></li>
    </ul>
  </div>
  <footer>© 2025 Musocial</footer>
</div>
<main>
  <header>
    <input type="text" placeholder="Ayarlarda ara...">
    <div class="user">
      <span><?= htmlspecialchars($currentUser['username']) ?></span>
      <img src="<?= htmlspecialchars($currentUser['avatar_url'] ?: 'https://i.pravatar.cc/100?img=12') ?>" alt="Admin">
    </div>
  </header>

  <?php if ($successMessage): ?>
  <div style="background: #4caf50; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <i class="fa-solid fa-check-circle"></i> <?= $successMessage ?>
  </div>
  <?php endif; ?>

  <div class="form-box">
    <h3><i class="fa-solid fa-desktop"></i> Genel Site Ayarları</h3>
    <form method="POST" action="adminayarlar.php">
      <label for="site_title" style="display: block; margin-bottom: 5px; font-weight: 500;">Site Başlığı</label>
      <input type="text" id="site_title" name="site_title" value="<?= htmlspecialchars($currentSiteTitle) ?>" placeholder="Sitenizin ana başlığı" required>
      
      <label for="site_desc" style="display: block; margin-bottom: 5px; font-weight: 500;">Site Açıklaması (Meta)</label>
      <textarea id="site_desc" name="site_desc" rows="3" placeholder="SEO için kısa bir açıklama..."><?= htmlspecialchars($currentSiteDesc) ?></textarea>
      
      <button class="primary-btn"><i class="fa-solid fa-save"></i> Ayarları Kaydet</button>
    </form>
  </div>

  <div class="form-box">
    <h3><i class="fa-solid fa-palette"></i> Görünüm Ayarları</h3>
    <form method="POST" action="adminayarlar.php" style="display: flex; flex-direction: column; gap: 15px;">
      
      <div style="display: flex; align-items: center; gap: 20px;">
          <label style="font-weight: 500;">Tema Seçimi:</label>
          
          <label for="light_theme" style="cursor: pointer;">
              <input type="radio" id="light_theme" name="theme" value="light" <?= !$darkMode ? 'checked' : '' ?> style="margin-right: 5px;"> Açık Mod
          </label>

          <label for="dark_theme" style="cursor: pointer;">
              <input type="radio" id="dark_theme" name="theme" value="dark" <?= $darkMode ? 'checked' : '' ?> style="margin-right: 5px;"> Koyu Mod
          </label>
      </div>

      <button class="primary-btn"><i class="fa-solid fa-magic"></i> Temayı Uygula</button>
    </form>
  </div>
</main>
</body>
</html>