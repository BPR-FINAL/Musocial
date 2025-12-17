<?php
require_once __DIR__ . '/db.php';
start_session_once();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errors[] = 'Email/kullanıcı adı ve şifre gerekli.';
    } else {
        $stmt = get_pdo()->prepare('SELECT id, password_hash FROM users WHERE email = :email OR username = :username');
        $stmt->execute([':email' => $identifier, ':username' => $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Bilgiler eşleşmedi.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>Giriş</title>
<style>
  body{font-family:Arial,Helvetica,sans-serif;background:#e9f1f7;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
  .box{background:#fff;padding:30px;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,0.1);width:min(360px,90%);text-align:center;}
  input{width:100%;padding:10px;margin:8px 0;border:1px solid #ccc;border-radius:6px;}
  button{width:100%;padding:12px;background:#1d9bf0;color:#fff;border:none;border-radius:6px;font-weight:bold;cursor:pointer;}
  button:hover{background:#0d8ae8;}
  a{text-decoration:none;display:block;text-align:center;margin-top:12px;color:#1d9bf0;}
</style>
</head>
<body>
<div class="box">
  <h2>Giriş Yap</h2>
  <?php foreach ($errors as $err): ?>
    <div style="background:#ffe0e0;color:#b3261e;padding:8px;border-radius:6px;margin-bottom:8px;"><?= htmlspecialchars($err) ?></div>
  <?php endforeach; ?>
  <form method="post">
    <input type="text" name="identifier" placeholder="Email veya kullanıcı adı" required>
    <input type="password" name="password" placeholder="Şifre" required>
    <button type="submit">Giriş Yap</button>
  </form>
  <a href="register.php">Hesabın yok mu? Kayıt Ol</a>
</div>
</body>
</html>
