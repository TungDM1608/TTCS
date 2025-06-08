<?php
include 'includes/session.php';
include 'includes/db_connect.php';

if (!is_uploader()) {
    echo "Chỉ uploader mới được xem thông báo.";
    exit;
}

$user_id = $_SESSION['user']['id'];
$result = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Thông báo</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body class="container">
    <h2>📢 Thông báo từ Admin</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="panel panel-default">
                <div class="panel-body"><?= htmlspecialchars($row['message']) ?></div>
                <div class="panel-footer"><small>Gửi lúc: <?= $row['created_at'] ?></small></div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Không có thông báo nào.</p>
    <?php endif; ?>

    <a href="dashboard_uploader.php" class="btn btn-default">← Quay về trang cá nhân</a>
</body>
</html>
