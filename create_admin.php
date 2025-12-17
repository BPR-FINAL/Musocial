<?php
/**
 * Admin Hesabı Oluşturma Scripti
 * 
 * Bu dosyayı tarayıcıda çalıştırarak admin hesabı oluşturabilirsiniz.
 * Örnek: http://localhost/Hyedek/create_admin.php
 * 
 * Kullanım:
 * 1. Tarayıcıda bu dosyayı açın
 * 2. Formu doldurun
 * 3. Admin hesabı oluşturulacak
 * 
 * GÜVENLİK UYARISI: Admin oluşturduktan sonra bu dosyayı SİLİN!
 */

require_once 'db.php';

$message = '';
$error = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Doğrulama
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'Tüm alanları doldurun!';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalı!';
    } else {
        try {
            $pdo = get_pdo();
            
            // Kullanıcı adı veya email kontrolü
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $email]);
            
            if ($stmt->fetch()) {
                $error = 'Bu kullanıcı adı veya email zaten kullanılıyor!';
            } else {
                // Şifreyi hashle
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Admin kullanıcısı oluştur
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, username, email, password_hash, is_admin, is_banned, dark_mode)
                    VALUES (:full_name, :username, :email, :password_hash, 1, 0, 0)
                ");
                
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':username' => $username,
                    ':email' => $email,
                    ':password_hash' => $password_hash
                ]);
                
                $message = "✅ Admin hesabı başarıyla oluşturuldu!<br><br>
                            <strong>Kullanıcı Adı:</strong> $username<br>
                            <strong>Email:</strong> $email<br>
                            <strong>Şifre:</strong> (girdiğiniz şifre)<br><br>
                            <a href='login.php'>Giriş Yap</a> | <a href='admin.php'>Admin Panel</a><br><br>
                            <span style='color: red;'><strong>UYARI:</strong> Bu dosyayı şimdi silin!</span>";
            }
        } catch (PDOException $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Hesabı Oluştur - Musocial</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            line-height: 1.6;
        }
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        button:active {
            transform: translateY(0);
        }
        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
        .subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Admin Hesabı Oluştur</h1>
        <p class="subtitle">Musocial Yönetim Paneli</p>
        
        <?php if ($message): ?>
            <div class="success"><?= $message ?></div>
        <?php elseif ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php else: ?>
            <div class="warning">
                ⚠️ <strong>Güvenlik Uyarısı:</strong> Admin hesabı oluşturduktan sonra bu dosyayı mutlaka silin!
            </div>
            
            <form method="POST">
                <label for="full_name">Ad Soyad</label>
                <input type="text" id="full_name" name="full_name" placeholder="Örn: Ahmet Yılmaz" required>
                
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" placeholder="Örn: admin" required>
                
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Örn: admin@example.com" required>
                
                <label for="password">Şifre (en az 6 karakter)</label>
                <input type="password" id="password" name="password" placeholder="Güçlü bir şifre girin" required minlength="6">
                
                <button type="submit">✨ Admin Hesabı Oluştur</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
