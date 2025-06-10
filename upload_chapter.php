<?php
include 'includes/session.php';
include 'includes/db_connect.php';

$message = '';
$preselected_comic_id = 0; // Biến để lưu ID truyện sẽ được chọn sẵn

// Kiểm tra nếu comic_id được truyền qua URL
if (isset($_GET['comic_id']) && is_numeric($_GET['comic_id'])) {
    $preselected_comic_id = intval($_GET['comic_id']);
}

if (!is_uploader()) {
    echo "Chỉ uploader mới có quyền upload chương!";
    exit;
}

$uploader_id = $_SESSION['user']['id'];
$comics = $conn->query("SELECT id, title FROM comics WHERE uploader_id = $uploader_id");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comic_id = intval($_POST['comic_id']);
    $chapter_number = intval($_POST['chapter_number']);
    $chapter_title = trim($_POST['chapter_title']);

    $stmt = $conn->prepare("INSERT INTO chapters (comic_id, chapter_number, title) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $comic_id, $chapter_number, $chapter_title);

    if ($stmt->execute()) {
        $chapter_id = $stmt->insert_id;

        // Chuẩn hóa danh sách ảnh và sắp xếp theo tên
        $pages = $_FILES['pages'];
        $image_files = [];
        for ($i = 0; $i < count($pages['name']); $i++) {
            if ($pages['error'][$i] === 0) {
                $image_files[] = [
                    'name' => $pages['name'][$i],
                    'tmp_name' => $pages['tmp_name'][$i],
                ];
            }
        }

        // Sắp xếp theo tên ảnh (theo thứ tự tự nhiên)
        usort($image_files, function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });

        // Upload từng ảnh
        foreach ($image_files as $index => $file) {
            $page_number = $index + 1;
            $filename = time() . '_' . basename($file['name']);
            $target = 'uploads/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $insert = $conn->prepare("INSERT INTO pages (chapter_id, image_path, page_number) VALUES (?, ?, ?)");
                $insert->bind_param("isi", $chapter_id, $target, $page_number);
                $insert->execute();
            }
        }

        $message = "Upload chương thành công!";
    } else {
        $message = "Có lỗi khi upload chương.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload Chương</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body class="container">
    <a href="my_comic.php" class="btn btn-default" style="margin-top: 20px;">← Danh sách truyện của tôi</a>
    <h2>Upload Chương mới</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Chọn Truyện:</label>
            <select name="comic_id" class="form-control" required>
                <?php while ($comic = $comics->fetch_assoc()): ?>
                    <option value="<?= $comic['id'] ?>"
                        <?php if ($comic['id'] == $preselected_comic_id) echo 'selected'; ?>>
                        <?= htmlspecialchars($comic['title']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Số chương:</label>
            <input type="number" name="chapter_number" required class="form-control">
        </div>

        <div class="form-group">
            <label>Tiêu đề chương:</label>
            <input type="text" name="chapter_title" required class="form-control">
        </div>

        <div class="form-group">
            <label>Upload các trang (chọn nhiều ảnh):</label>
            <input type="file" name="pages[]" multiple required class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Upload Chương</button>
    </form>
</body>
</html>
