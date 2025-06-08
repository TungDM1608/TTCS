<?php
include 'includes/session.php';
include 'includes/db_connect.php';

$chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : 0;

if (!$chapter_id) {
    die("Không có ID chương hợp lệ.");
}

// Lấy thông tin chương và uploader truyện
$stmt = $conn->prepare("
    SELECT c.id AS chapter_id, c.comic_id, cm.uploader_id
    FROM chapters c
    JOIN comics cm ON c.comic_id = cm.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $chapter_id);
$stmt->execute();
$result = $stmt->get_result();
$chapter = $result->fetch_assoc();

if (!$chapter) {
    die("Chương không tồn tại.");
}

// Lấy thông tin người dùng đăng nhập
$user_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'] ?? 'reader';

// Kiểm tra quyền xóa: admin hoặc uploader của truyện
if ($role === 'admin' || ($role === 'uploader' && $user_id == $chapter['uploader_id'])) {
    // Xóa tất cả các trang thuộc chương trước
    $conn->query("DELETE FROM pages WHERE chapter_id = $chapter_id");

    // Sau đó mới xóa chương
    $conn->query("DELETE FROM chapters WHERE id = $chapter_id");

    // Chuyển hướng về danh sách chương
    header("Location: view_chapters.php?comic_id=" . $chapter['comic_id']);
    exit();
} else {
    die("Bạn không có quyền xóa chương này.");
}
?>
