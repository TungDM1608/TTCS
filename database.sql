-- Tạo database nếu chưa có
CREATE DATABASE IF NOT EXISTS comic_db;
USE comic_db;

-- Bảng người dùng
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','uploader','reader') NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Bảng truyện
CREATE TABLE comics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  cover_image VARCHAR(255),
  uploader_id INT NOT NULL,
  genre VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (uploader_id) REFERENCES users(id)
);

-- Bảng chương
CREATE TABLE chapters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  comic_id INT NOT NULL,
  chapter_number INT NOT NULL,
  title VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (comic_id) REFERENCES comics(id)
);

-- Bảng trang trong chương
CREATE TABLE pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chapter_id INT NOT NULL,
  image_path VARCHAR(255),
  page_number INT NOT NULL,
  FOREIGN KEY (chapter_id) REFERENCES chapters(id)
);

-- Bảng lượt xem truyện
CREATE TABLE comic_views (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  comic_id INT NOT NULL,
  viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (comic_id) REFERENCES comics(id)
);

-- Bảng thông báo
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng bình luận
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comic_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comic_id) REFERENCES comics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
