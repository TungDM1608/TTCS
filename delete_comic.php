<?php
include 'includes/session.php';
include 'includes/db_connect.php';

if (!is_admin()) {
    echo "Bạn không có quyền thực hiện thao tác này.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comic_id = intval($_POST['comic_id']);
    $reason = trim($_POST['reason']);

    // Lấy thông tin truyện
    $stmt = $conn->prepare("SELECT uploader_id, title FROM comics WHERE id = ?");
    $stmt->bind_param("i", $comic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "Không tìm thấy truyện.";
        exit;
    }
    $row = $result->fetch_assoc();
    $uploader_id = $row['uploader_id'];
    $title = $row['title'];

    // Xoá trang
    $conn->query("DELETE p FROM pages p JOIN chapters c ON p.chapter_id = c.id WHERE c.comic_id = $comic_id");

    // Xoá chương 
    $conn->query("DELETE FROM chapters WHERE comic_id = $comic_id");

    // Xoá lượt xem 
    $conn->query("DELETE FROM comic_views WHERE comic_id = $comic_id");

    // Xoá truyện
    $conn->query("DELETE FROM comics WHERE id = $comic_id");

    // Gửi thông báo
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $msg = "Truyện \"$title\" của bạn đã bị admin xoá. Lý do: $reason";
    $stmt2 = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt2->bind_param("is", $uploader_id, $msg);
    $stmt2->execute();

    // log
    $conn->query("CREATE TABLE IF NOT EXISTS admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT,
        action TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $admin_id = $_SESSION['user']['id'];
    $log = "Xoá truyện \"$title\" và gửi lý do: $reason";
    $conn->query("INSERT INTO admin_logs (admin_id, action) VALUES ($admin_id, '" . $conn->real_escape_string($log) . "')");

    header("Location: dashboard_admin.php");
    exit;
} else {
    $comic_id = intval($_GET['id'] ?? 0);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Xác nhận xoá truyện</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body class="container">
    <h2>Xác nhận xoá truyện</h2>
    <form method="post">
        <input type="hidden" name="comic_id" value="<?= $comic_id ?>">
        <div class="form-group">
            <label>Lý do xoá truyện:</label>
            <textarea name="reason" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-danger">Xác nhận xoá và gửi thông báo</button>
        <a href="dashboard_admin.php" class="btn btn-default">Huỷ</a>
    </form>
</body>
</html>
