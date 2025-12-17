<?php
require_once __DIR__ . '/db.php';
start_session_once();

// --- Yapılandırma ---
$uploadDir = __DIR__ . '/uploads/'; // Yükleme klasörü yolu. Bu klasörü oluşturun!
$defaultAvatar = 'img/user.png'; // Varsayılan profil resmi yolu.
$maxFileSize = 2 * 1024 * 1024; // 2MB maksimum dosya boyutu

$currentUser = get_current_user_data();
$darkMode = is_dark_mode();

$pdo = get_pdo();

$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$errors = [];
$flash = '';


// Mevcut kullanıcı verisini al (Varsayılan avatar için düzenlendi)
if (!empty($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare('SELECT id, full_name, username, avatar_url FROM users WHERE id = :id');
    $stmtUser->execute([':id' => $_SESSION['user_id']]);
    $currentUser = $stmtUser->fetch() ?: null;
    // Varsayılan avatar ayarı: Eğer avatar_url boşsa, varsayılanı kullan
    if ($currentUser && empty($currentUser['avatar_url'])) {
        $currentUser['avatar_url'] = $defaultAvatar;
    }
} else {
    $currentUser = null;
}


// POST ACTIONS (LIKE & COMMENT) AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_ajax'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    if (!$currentUser) {
        $response['message'] = 'Giriş yapmalısınız.';
        echo json_encode($response);
        exit;
    }

    if ($_POST['action_ajax'] === 'like_toggle') {
        $postId = (int)($_POST['post_id'] ?? 0);
        $existsStmt = $pdo->prepare('SELECT 1 FROM likes WHERE post_id = :pid AND user_id = :uid');
        $existsStmt->execute([':pid'=>$postId, ':uid'=>$currentUser['id']]);
        if ($existsStmt->fetch()) {
            $pdo->prepare('DELETE FROM likes WHERE post_id = :pid AND user_id = :uid')
                ->execute([':pid'=>$postId, ':uid'=>$currentUser['id']]);
            $liked = false;
        } else {
            $pdo->prepare('INSERT INTO likes (post_id, user_id) VALUES (:pid, :uid)')
                ->execute([':pid'=>$postId, ':uid'=>$currentUser['id']]);
            $liked = true;
        }
        $count = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
        $count->execute([$postId]);
        $likesCount = (int)$count->fetchColumn();

        $response['success'] = true;
        $response['liked'] = $liked;
        $response['likesCount'] = $likesCount;
        echo json_encode($response);
        exit;
    }

    if ($_POST['action_ajax'] === 'new_comment') {
        $postId = (int)($_POST['post_id'] ?? 0);
        $body = trim($_POST['comment_body'] ?? '');
        if ($body === '') {
            $response['message'] = 'Yorum boş olamaz.';
            echo json_encode($response);
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, body) VALUES (:pid, :uid, :body)');
        $stmt->execute([':pid'=>$postId, ':uid'=>$currentUser['id'], ':body'=>$body]);

        $stmtC = $pdo->prepare('SELECT c.body, u.username, u.avatar_url, c.created_at FROM comments c JOIN users u ON u.id=c.user_id WHERE c.id = ?');
        $stmtC->execute([$pdo->lastInsertId()]);
        $newComment = $stmtC->fetch();
        // Yorumcu avatar URL'sini varsayılana ayarla
        if (empty($newComment['avatar_url'])) {
            $newComment['avatar_url'] = $defaultAvatar;
        }


        $response['success'] = true;
        $response['comment'] = $newComment;
        echo json_encode($response);
        exit;
    }
}

// NEW POST (normal) - Resim yükleme mantığı buraya eklendi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    if (!$currentUser) $errors[] = 'Gönderi paylaşmak için giriş yapmalısın.';
    else {
        $content = trim($_POST['content'] ?? '');
        $tagsInput = trim($_POST['tags'] ?? '');
        $imageUrl = null; // Varsayılan olarak boş

        // Dosya Yükleme İşlemi Başlangıcı
        if (isset($_FILES['dosya']) && $_FILES['dosya']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['dosya'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileType = $file['type'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

            if ($fileSize > $maxFileSize) {
                $errors[] = 'Dosya boyutu 2MB\'ı aşamaz.';
            } elseif (!in_array($fileExt, $allowedExtensions) || !in_array($fileType, $allowedTypes)) {
                $errors[] = 'Sadece JPG, JPEG, PNG ve GIF formatları desteklenmektedir.';
            } else {
                // Güvenli bir dosya adı oluşturma
                $newFileName = uniqid('post_img_') . '.' . $fileExt;
                $fileDestination = $uploadDir . $newFileName;

                // Dosyayı geçici konumdan kalıcı konuma taşı
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    $imageUrl = 'uploads/' . $newFileName; // Veritabanına kaydedilecek URL
                } else {
                    $errors[] = 'Dosya yüklenirken bir hata oluştu. Yükleme klasörünün yazma izni olduğundan emin olun.';
                }
            }
        }
        // Dosya Yükleme İşlemi Sonu

        if ($content === '' && $imageUrl === null) $errors[] = 'İçerik veya resim eklemelisin.';
        
        if (!$errors) {
            $stmtInsert = $pdo->prepare('INSERT INTO posts (user_id, content, image_url, tags) VALUES (:uid, :content, :image, :tags)');
            $stmtInsert->execute([
                ':uid'=>$currentUser['id'],
                ':content'=>$content,
                ':image'=>$imageUrl, // Yüklenen resmin URL'si veya null
                ':tags'=>$tagsInput ?: null,
            ]);
            $flash = 'Gönderin paylaşıldı!';
        }
    }
}

// DELETE POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (!$currentUser) $errors[] = 'Gönderi silmek için giriş yapmalısın.';
    else {
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId <= 0) $errors[] = 'Geçersiz gönderi.';
        else {
            $stmtOwner = $pdo->prepare('SELECT user_id, image_url FROM posts WHERE id = :pid');
            $stmtOwner->execute([':pid'=>$postId]);
            $row = $stmtOwner->fetch();
            if (!$row) {}
            elseif ((int)$row['user_id'] !== (int)$currentUser['id']) $errors[]='Bu gönderiyi silme yetkin yok.';
            else {
                // Resim dosyasını sil (eğer varsa)
                if (!empty($row['image_url'])) {
                    $filePath = __DIR__ . '/' . $row['image_url'];
                    if (file_exists($filePath) && is_file($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $stmtDel = $pdo->prepare('DELETE FROM posts WHERE id = :pid AND user_id = :uid');
                $stmtDel->execute([':pid'=>$postId, ':uid'=>$currentUser['id']]);
                $flash = 'Gönderi silindi.';
            }
        }
    }
}

// Filter by tag
$where = ''; $params = [];
if ($tag!=='') { $where = 'WHERE p.tags LIKE :tag'; $params[':tag']='%'.$tag.'%'; }
$likeSelect = '';
if ($currentUser) { $likeSelect=', EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id = p.id AND l2.user_id = :me) AS liked_by_me'; $params[':me']=$currentUser['id']; }
else $likeSelect=',0 AS liked_by_me';

// POSTS
$stmtPosts = $pdo->prepare("
    SELECT p.id, p.user_id, p.content, p.image_url, p.created_at, p.tags,
    u.username, u.avatar_url,
    (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes_count,
    (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count
    $likeSelect
    FROM posts p
    JOIN users u ON u.id=p.user_id
    $where
    ORDER BY p.created_at DESC
");
if ($currentUser) { $params[':me']=$currentUser['id']; }
$stmtPosts->execute($params);
$posts = $stmtPosts->fetchAll();

// Yorumcu ve Post sahibi avatar URL'lerini ayarla
foreach ($posts as &$post) {
    if (empty($post['avatar_url'])) {
        $post['avatar_url'] = $defaultAvatar;
    }
}
unset($post);

// Comments grouped by post
$commentsByPost = [];
if ($posts) {
    $ids = array_column($posts,'id');
    $in = implode(',', array_fill(0,count($ids),'?'));
    $stmtComments = $pdo->prepare("SELECT c.post_id, c.body, c.created_at, u.username, u.avatar_url FROM comments c JOIN users u ON u.id=c.user_id WHERE c.post_id IN ($in) ORDER BY c.created_at ASC");
    $stmtComments->execute($ids);
    while ($row = $stmtComments->fetch()) {
        // Yorumcu avatar URL'sini varsayılana ayarla
        if (empty($row['avatar_url'])) {
            $row['avatar_url'] = $defaultAvatar;
        }
        $commentsByPost[$row['post_id']][]=$row;
    }
}

// Trends
$trends = $pdo->query("SELECT tags, COUNT(*) as cnt FROM posts WHERE tags IS NOT NULL AND tags<>'' GROUP BY tags ORDER BY cnt DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Musocial</title>
<link rel="stylesheet" href="stylee.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
/* CSS Stilleri buraya kopyalanmıştır. */
body{background:#f7f9fc;}
.action-btn{position:relative;overflow:hidden;}
.floating-icon{position:absolute;font-size:20px;animation:float-up 1s ease-out forwards;pointer-events:none;}
@keyframes float-up{0%{transform:translateY(0) scale(1);opacity:1;}50%{transform:translateY(-30px) scale(1.5);opacity:0.8;}100%{transform:translateY(-60px) scale(0.5);opacity:0;}}
</style>
</head>
<body class="<?= is_dark_mode() ? 'dark' : '' ?>">
    
    <nav class="navbar navbar-expand-lg navbar-light bg-black d-lg-none border-bottom">
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

<nav class="col-md-3 col-lg-2 bg-black border-end p-3 d-none d-md-block">
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
<li class="nav-item mt-3">

</li>

<?php if($currentUser): ?>
<a href="profil.php" style="text-decoration:none"><li class="nav-item mb-2 mt-3">
<span class="nav-link text-success fw-semibold"><i class="fa-solid fa-circle-user"></i> @<?=htmlspecialchars($currentUser['username'])?></span>
</li></a>
<?php endif; ?>
<li class="nav-item mb-2"><a href="logout.php" class="nav-link text-dark"><i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap</a></li>
</ul>
</nav>

<main class="col-md-12 col-lg-7 p-3 p-md-4 mx-auto">

<div class="card mb-4 shadow-sm">
<div class="card-body">
<?php if($flash): ?><div class="alert alert-success py-2"><?=htmlspecialchars($flash)?></div><?php endif; ?>
<?php foreach($errors as $err): ?><div class="alert alert-danger py-2 mb-2"><?=htmlspecialchars($err)?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="new_post" value="1">
<textarea name="content" class="form-control mb-2" rows="3" placeholder="Ne düşünüyorsun?"><?=htmlspecialchars($_POST['content'] ?? '')?></textarea>
<div class="row g-2">
<div class="col-md-6"><input type="text" name="image_url" class="form-control" placeholder="Harici Resim URL (opsiyonel)" value="<?=htmlspecialchars($_POST['image_url'] ?? '')?>"></div>
<div class="col-md-6"><input type="file" name="dosya" class="form-control" ></div>

<div class="col-md-6"><input type="text" name="tags" class="form-control" placeholder="etiket (ör: musocial)" value="<?=htmlspecialchars($_POST['tags'] ?? '')?>"></div>
</div>
<div class="mt-3 d-flex justify-content-between align-items-center">
<?php if($currentUser): ?>
<div class="d-flex align-items-center">
<img src="<?=htmlspecialchars($currentUser['avatar_url'])?>" class="rounded-circle me-2" alt="" width="40" height="40" style="object-fit: cover;">
<small class="text-muted" style="font-size:18px;">@<?=htmlspecialchars($currentUser['username'])?></small>
</div>
<?php else: ?>
<small class="text-muted">Gönderi paylaşmak için <a href="login.php">giriş yap</a>.</small>
<?php endif; ?>
<button class="btn btn-primary" type="submit">Gönder</button>
</div>
</form>
</div>
</div>

<?php if($tag!==''): ?><h5 class="text-primary mb-3">#<?=htmlspecialchars($tag)?> etiketiyle ilgili gönderiler</h5><?php endif; ?>
<?php if(!$posts): ?><p class="text-muted">Bu etiketle ilgili gönderi bulunamadı.</p>
<?php else: ?>
<?php foreach($posts as $post): ?>
<div class="card mb-3 post shadow-sm" data-id="<?=$post['id']?>">
<div class="card-body">
<div class="d-flex align-items-start justify-content-between mb-2">
<div class="d-flex align-items-center">
<img src="<?=htmlspecialchars($post['avatar_url'])?>" class="rounded-circle me-2" alt="Profil Fotoğrafı" width="60" height="60" style="object-fit: cover;">
<div>
<h5 class="card-title mb-0">@<?=htmlspecialchars($post["username"])?></h5>
<small class="text-muted"><?=htmlspecialchars(date('d.m.Y H:i', strtotime($post['created_at'])))?></small>
</div>
</div>
<?php if($currentUser && (int)$post['user_id'] === (int)$currentUser['id']): ?>
<form method="post" class="ms-2" onsubmit="return confirm('Bu gönderiyi silmek istediğine emin misin?');">
<input type="hidden" name="delete_post" value="1">
<input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
<button type="submit" class="btn btn-sm btn-outline-secondary" title="Gönderiyi Sil"><i class="fa-regular fa-trash-can"></i></button>
</form>
<?php endif; ?>
</div>

<p class="card-text mt-2"><?=nl2br(htmlspecialchars($post["content"]))?></p>
<?php if(!empty($post["image_url"])): ?><img src="<?=htmlspecialchars($post["image_url"])?>" alt="" class="img-fluid rounded mt-2" style="max-height:500px; object-fit:contain;"><?php endif; ?>

<div class="d-flex justify-content-start gap-3 mt-2 align-items-center">
<button class="btn btn-sm <?= $post['liked_by_me']?'btn-danger':'btn-outline-danger' ?> action-btn like-btn" data-id="<?=$post['id']?>">
<i class="fa-solid fa-heart"></i> <span class="like-text"><?=$post['liked_by_me']?'Beğenildi':'Beğen'?></span>
</button>
<span class="ms-1 text-muted small like-count"><?=$post['likes_count']?></span>
<div><i class="fa-regular fa-comment text-muted"></i> <span class="text-muted small comment-count"><?=$post['comments_count']?></span></div>
<?php if(!empty($post['tags'])): ?><span class="badge bg-light text-primary border">#<?=htmlspecialchars($post['tags'])?></span><?php endif; ?>
</div>

<div class="comments-list mt-3">
<?php if(!empty($commentsByPost[$post['id']])): ?>
<?php foreach($commentsByPost[$post['id']] as $comment): ?>
<div class="d-flex mb-2">
<img src="<?=htmlspecialchars($comment['avatar_url'])?>" class="rounded-circle me-2" alt="" width="34" height="34" style="object-fit: cover;">
<div class="bg-light rounded p-2 w-100">
<strong>@<?=htmlspecialchars($comment['username'])?>:</strong> <span><?=nl2br(htmlspecialchars($comment['body']))?></span>
<div class="text-muted small"><?=htmlspecialchars(date('d.m.Y H:i', strtotime($comment['created_at'])))?></div>
</div>
</div>
<?php endforeach; ?>
<?php else: ?><p class="text-muted small no-comments">Henüz yorum yok.</p><?php endif; ?>
</div>

<div class="comment-input mt-3">
<?php if($currentUser): ?>
<form class="d-flex gap-2 comment-form" data-id="<?=$post['id']?>">
<input type="text" name="comment_body" class="form-control" placeholder="Yorum yaz...">
<button class="btn btn-primary btn-sm" type="submit">Gönder</button>
</form>
<?php else: ?><small class="text-muted">Yorum yapmak için <a href="login.php">giriş yap</a>.</small><?php endif; ?>
</div>

</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

</main>


<aside class="col-lg-3 d-none d-lg-block bg-white border-start p-3">
<h5 class="mb-3">Gündemdekiler</h5>
<ul class="list-group list-group-flush">
<?php if(!$trends): ?><li class="list-group-item text-muted">Trend bulunamadı.</li>
<?php else: foreach($trends as $trend): ?>
<li class="list-group-item d-flex justify-content-between align-items-center">
<a href="index.php?tag=<?=urlencode($trend['tags'])?>" class="text-decoration-none text-dark">#<?=htmlspecialchars($trend['tags'])?></a>
<span class="badge bg-light text-secondary"><?= (int)$trend['cnt'] ?></span>
</li>
<?php endforeach; endif; ?>
</ul>
</aside>

</div>
</div>

<script>
// LIKE
document.querySelectorAll('.like-btn').forEach(btn=>{
    btn.addEventListener('click', e=>{
        e.preventDefault();
        const postId = btn.dataset.id;
        const formData = new FormData();
        formData.append('action_ajax','like_toggle');
        formData.append('post_id',postId);

        fetch('',{method:'POST',body:formData})
        .then(res=>res.json())
        .then(res=>{
            if(res.success){
                // Kalp ikonunun animasyonunu ekle (isteğe bağlı)
                const icon = btn.querySelector('.fa-heart');
                const floatingIcon = document.createElement('i');
                floatingIcon.className = 'fa-solid fa-heart floating-icon';
                floatingIcon.style.color = res.liked ? '#dc3545' : 'transparent';
                btn.appendChild(floatingIcon);
                setTimeout(() => floatingIcon.remove(), 1000);

                btn.classList.toggle('btn-danger',res.liked);
                btn.classList.toggle('btn-outline-danger',!res.liked);
                btn.querySelector('.like-text').textContent = res.liked?'Beğenildi':'Beğen';
                btn.parentElement.querySelector('.like-count').textContent = res.likesCount;
            } else alert(res.message);
        });
    });
});

// COMMENT
document.querySelectorAll('.comment-form').forEach(form=>{
    form.addEventListener('submit', e=>{
        e.preventDefault();
        const postId = form.dataset.id;
        const inputField = form.querySelector('input[name="comment_body"]');
        const body = inputField.value;
        const formData = new FormData();
        formData.append('action_ajax','new_comment');
        formData.append('post_id',postId);
        formData.append('comment_body',body);

        fetch('',{method:'POST',body:formData})
        .then(res=>res.json())
        .then(res=>{
            if(res.success){
                const commentList = form.closest('.post').querySelector('.comments-list');
                
                // "Henüz yorum yok" yazısını kaldır
                const noComments = commentList.querySelector('.no-comments');
                if (noComments) noComments.remove();

                const div = document.createElement('div');
                div.classList.add('d-flex','mb-2');
                // new Date(res.comment.created_at).toLocaleString('tr-TR') yerine
                // PHP'den dönen timestamp'i kullanmak daha güvenli:
                const formattedDate = new Date().toLocaleString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });

                div.innerHTML=`<img src="${res.comment.avatar_url}" class="rounded-circle me-2" width="34" height="34" style="object-fit:cover;">
                <div class="bg-light rounded p-2 w-100"><strong>@${res.comment.username}:</strong> <span>${res.comment.body.replace(/\n/g, '<br>')}</span>
                <div class="text-muted small">${formattedDate}</div></div>`;
                commentList.appendChild(div);
                inputField.value='';
                
                // yorum sayısını güncelle
                const countElem = form.closest('.post').querySelector('.comment-count');
                countElem.textContent = parseInt(countElem.textContent)+1;
            } else alert(res.message);
        });
    });
});
</script>
<script>
// Bu kısım, is_dark_mode() fonksiyonunun PHP tarafında halledilmesi nedeniyle gerekli olmayabilir.
// Ancak, tarayıcıda Dark Mode ayarını tutmaya devam etmek istiyorsanız kalsın.
const body = document.querySelector('body');

if (localStorage.getItem('darkMode') === 'on') {
    body.classList.add('dark');
}

// Bu kodda bir 'darkModeToggle' butonu bulunmadığı için, bu scriptin doğru çalışması için
// ayarlar.php'deki dark mode ayarının güncellenmesi gerekir.
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
