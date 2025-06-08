<?php
include 'includes/session.php';
include 'includes/db_connect.php';

$message = '';

if (!is_uploader()) {
    echo "Bạn không có quyền truy cập chức năng này.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $genre = $_POST['genre'];
    $uploader_id = $_SESSION['user']['id'];

    $cover_image = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $filename = time() . '_' . basename($_FILES['cover_image']['name']);
        $target_path = 'uploads/' . $filename;
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
            $cover_image = $target_path;
        }
    }

    $stmt = $conn->prepare("INSERT INTO comics (title, genre, uploader_id, cover_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $title, $genre, $uploader_id, $cover_image);

    if ($stmt->execute()) {
        $message = "Đăng truyện thành công!";
    } else {
        $message = "Lỗi khi đăng truyện.";
    }
}

$genres = [
    'Action', 'Adventure', 'Comedy', 'Drama', 'School Life',
    'Fantasy', 'Psychological', 'Horror', 'Tragedy', 'Supernatural'
];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload Truyện</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body class="container">
    <a href="dashboard_uploader.php" class="btn btn-default" style="margin-top:20px;">← Quay về trang cá nhân</a>
    <h2>Đăng truyện mới</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Tên truyện:</label>
            <input type="text" name="title" required class="form-control">
        </div>
        <div class="form-group">
            <label>Thể loại:</label>
            <select name="genre" class="form-control" required>
                <?php foreach ($genres as $g): ?>
                    <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Ảnh bìa:</label>
            <input type="file" name="cover_image" accept="image/*" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Đăng truyện</button>
    </form>
</body>
</html>
