<?php
require_once __DIR__ . '/db.php';
start_session_once();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$successMessage = '';
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $successMessage = 'Hesabınız başarıyla silindi. Yeni bir hesap oluşturabilirsiniz.';
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $avatar = trim($_POST['avatar_url'] ?? '');

    if ($fullName === '' || $username === '' || $email === '' || $password === '') {
        $errors[] = 'Tüm alanları doldurmalısın.';
    }

    if (!$errors) {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username');
        $stmt->execute([':email' => $email, ':username' => $username]);
        if ($stmt->fetch()) {
            $errors[] = 'Email veya kullanıcı adı zaten kullanılıyor.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = get_pdo()->prepare('INSERT INTO users (full_name, username, email, password_hash, avatar_url) VALUES (:full, :user, :email, :hash, :avatar)');
        $stmt->execute([
            ':full'   => $fullName,
            ':user'   => $username,
            ':email'  => $email,
            ':hash'   => $hash,
            ':avatar' => $avatar ?: null,
        ]);
        $_SESSION['user_id'] = get_pdo()->lastInsertId();
        header('Location: index.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Kayıt Ol</title>
<style>
  body{font-family:Arial;background:#e9f1f7;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
  .box{background:#fff;padding:30px;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,0.1);width:min(360px,90%);text-align:center;}
  input{width:100%;padding:10px;margin:8px 0;border:1px solid #ccc;border-radius:6px;}
  button{width:100%;padding:12px;background:#28a745;color:#fff;border:none;border-radius:6px;font-weight:bold;cursor:pointer;}
  button:hover{background:#218838;}
  a{text-decoration:none;display:block;text-align:center;margin-top:12px;color:#1d9bf0;}
</style>
</head>
<body>
<div class="box">
  <h2>Kayıt Ol</h2>
  <?php if ($successMessage): ?>
    <div style="background:#d4edda;color:#155724;padding:10px;border-radius:6px;margin-bottom:12px;border:1px solid #c3e6cb;">
      <?= htmlspecialchars($successMessage) ?>
    </div>
  <?php endif; ?>
  <?php foreach ($errors as $err): ?>
    <div style="background:#ffe0e0;color:#b3261e;padding:8px;border-radius:6px;margin-bottom:8px;"><?= htmlspecialchars($err) ?></div>
  <?php endforeach; ?>
  <form method="post">
    <input type="text" name="full_name" placeholder="Ad Soyad" required>
    <input type="text" name="username" placeholder="Kullanıcı adı" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Şifre" required>
    <input type="text" name="avatar_url" placeholder="Profil fotoğrafı URL (opsiyonel)">
    <button type="submit">Kayıt Ol</button>
  </form>
  <a href="login.php">Zaten hesabın var mı? Giriş Yap</a>
</div>
</body>
</html>
