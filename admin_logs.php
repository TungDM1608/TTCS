<?php
include 'includes/session.php';
include 'includes/db_connect.php';

if (!is_admin()) {
    echo "Chỉ admin mới có quyền xem nhật ký.";
    exit;
}

// Tạo bảng nếu chưa có
$conn->query("CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$result = $conn->query("
    SELECT a.username, l.action, l.created_at
    FROM admin_logs l
    JOIN users a ON l.admin_id = a.id
    ORDER BY l.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nhật ký quản trị</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body class="container">
    <h2>📘 Nhật ký hành động của Admin</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Admin</th>
                <th>Hành động</th>
                <th>Thời gian</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['action']) ?></td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">Chưa có hành động nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="dashboard_admin.php" class="btn btn-default">← Quay về trang quản trị</a>
</body>
</html>
