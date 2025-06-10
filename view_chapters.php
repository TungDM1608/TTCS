<?php
include 'includes/session.php';
include 'includes/db_connect.php';

$comic_id = isset($_GET['comic_id']) ? intval($_GET['comic_id']) : 0;

if (!$comic_id) {
    echo "Không có thông tin truyện.";
    exit;
}

// Lấy thông tin truyện + uploader_id
$comic_stmt = $conn->prepare("SELECT title, genre, cover_image, uploader_id FROM comics WHERE id = ?");
$comic_stmt->bind_param("i", $comic_id);
$comic_stmt->execute();
$comic_result = $comic_stmt->get_result();

if ($comic_result->num_rows === 0) {
    echo "Truyện không tồn tại.";
    exit;
}

$comic = $comic_result->fetch_assoc();
$uploader_id = $comic['uploader_id']; // Dùng để kiểm tra quyền xóa chương

// Danh sách chương
$chapter_stmt = $conn->prepare("SELECT * FROM chapters WHERE comic_id = ? ORDER BY chapter_number ASC");
$chapter_stmt->bind_param("i", $comic_id);
$chapter_stmt->execute();
$chapters = $chapter_stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($comic['title']); ?> - Danh sách chương</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/view_chapters.css">
</head>
<body>
    <a href="index.php" class="back-button"> Quay về trang chủ</a>
    
    <div class="comic-info-container">
        <img src="<?php echo htmlspecialchars($comic['cover_image']); ?>" alt="<?php echo htmlspecialchars($comic['title']); ?>" class="comic-cover">
        <div class="comic-details">
            <h2 class="comic-title"><?php echo htmlspecialchars($comic['title']); ?></h2>
            <div class="detail-item"><span class="detail-label">Thể loại:</span> <?php echo htmlspecialchars($comic['genre']); ?></div>
        </div>
    </div>

    <div class="chapter-list-container">
        <h2 class="chapter-list-header">Danh sách chương</h2>
        <?php if ($chapters->num_rows > 0): ?>
            <?php while($chapter = $chapters->fetch_assoc()): ?>
                <div class="chapter-item">
                    <div class="chapter-title">
                        Chương <?php echo $chapter['chapter_number']; ?>
                        <?php if (!empty($chapter['title'])): ?>
                            - <?php echo htmlspecialchars($chapter['title']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="chapter-date">
                        <?php
                            $date = new DateTime($chapter['created_at']);
                            echo $date->format('d/m/Y');
                        ?>
                    </div>

                    <!-- Nút Đọc -->
                    <a href="read_comic.php?comic_id=<?php echo $comic_id; ?>&chapter_id=<?php echo $chapter['id']; ?>" class="read-button btn btn-primary btn-sm">Đọc</a>

                    <!-- Nút Xóa chỉ hiện nếu là uploader hoặc admin -->
                    <?php if (is_admin() || (is_uploader() && $_SESSION['user']['id'] == $uploader_id)): ?>
                        <a href="delete_chapter.php?chapter_id=<?= $chapter['id'] ?>" class="delete-button btn btn-danger btn-sm" onclick="return confirm('Xác nhận xóa chương này?');">Xóa</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Chưa có chương nào cho truyện này.</p>
        <?php endif; ?>
    </div>
</body>
</html>
