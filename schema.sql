-- Create database and tables for Musocial
CREATE DATABASE IF NOT EXISTS musocial CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE musocial;

DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS followers;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS site_settings;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  avatar_url VARCHAR(255) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  is_admin TINYINT(1) DEFAULT 0,
  is_banned TINYINT(1) DEFAULT 0,
  is_private TINYINT(1) DEFAULT 0,
  dark_mode TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  content TEXT NOT NULL,
  image_url VARCHAR(255) DEFAULT NULL,
  tags VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE likes (
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id, user_id),
  CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE site_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE followers (
  follower_id INT UNSIGNED NOT NULL,
  following_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, following_id),
  CONSTRAINT fk_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_following FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NOT NULL,
  type VARCHAR(50) NOT NULL,
  post_id INT UNSIGNED DEFAULT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notification_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notification_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Seed users
-- NOT: Admin hesabı için create_admin.php dosyasını kullanın!
-- Diğer kullanıcıların şifresi: "password"
INSERT INTO users (full_name, username, email, password_hash, avatar_url, is_admin, is_banned) VALUES
('Arda Demirci', 'ardademirci', 'arda@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'img/arda.jpg', 0, 0),
('Turkiye Resmi', 'Turkiye', 'turkiye@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'img/turkiye_pp.jpg', 0, 0),
('Muge Anli', 'mugeanli', 'muge@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'img/mugeanl_pp.jpg', 0, 1),
('Hadise', 'hadisemu', 'hadise@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'img/hadse_pp.jpg', 0, 0);

-- Seed posts
INSERT INTO posts (user_id, content, image_url, tags) VALUES
(1, 'Bu Musocial''daki ilk gonderim! #musocial', 'img/said_post.png', 'musocial'),
(2, 'Abim ve ben mezuniyet hatirasi :) #sosyalağ', 'img/arda_post.png', 'sosyalağ'),
(3, 'Guzel bir gun Turkiye''de #Turkiye', 'img/turkiye_post.png', 'Turkiye'),
(4, 'Mutlu sabahlar! Takipte kalin #Turkiye', 'img/mugeanl_post.png', 'Turkiye'),
(5, 'Yeni sarkim yayinda! #musocial', 'img/hadse_post.png', 'musocial'),
(1, 'Stil denemesi #musocial', 'img/musa_post.png', 'musocial'),
(1, 'Hafta sonu enerjisi #weekend', 'img/musa3_post.png', 'weekend'),
(1, 'Gun batimi #travel', 'img/musa4_post.png', 'travel'),
(1, 'Favori kare #photography', 'img/musa5_.png', 'photography');

-- Sample comments
INSERT INTO comments (post_id, user_id, body) VALUES
(1, 2, 'Hos geldin!'),
(1, 3, 'Basarilar!'),
(2, 1, 'Harika gorunuyor.');

-- Sample likes
INSERT INTO likes (post_id, user_id) VALUES
(1, 2),
(1, 3),
(2, 1),
(2, 3),
(3, 1);

-- Site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_title', 'Musocial Blog'),
('site_description', 'Sosyal medya platformu'),
('default_theme', 'light');
