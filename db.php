<?php
// 🔹 Basic PDO bootstrap
$dbConfig = [
    'host'     => 'localhost',
    'port'     => '3306',
    'dbname'   => 'musocial',
    'user'     => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];

function get_pdo(): PDO {
    static $pdo = null;
    global $dbConfig;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['dbname'],
        $dbConfig['charset']
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], $options);
    return $pdo;
}

function start_session_once(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/* =====================================================
   🔹 GLOBAL USER + DARK MODE HELPER
   ===================================================== */

/**
 * Aktif kullanıcıyı getirir (tek sorgu, her yerde kullanılır)
 */
function get_current_user_data(): ?array {
    static $user = null;

    if ($user !== null) {
        return $user;
    }

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare("
        SELECT id, username, dark_mode, is_admin, avatar_url, full_name
        FROM users
        WHERE id = :id
    ");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    return $user;
}

/**
 * Dark mode açık mı?
 */
function is_dark_mode(): bool {
    $user = get_current_user_data();
    return $user && (int)$user['dark_mode'] === 1;
}
