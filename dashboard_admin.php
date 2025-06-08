<?php
include 'includes/session.php';
include 'includes/db_connect.php';

if (!is_admin()) {
    echo "Bạn không có quyền truy cập trang này.";
    exit;
}

$users = $conn->query("SELECT * FROM users ORDER BY id ASC");
$comics = $conn->query("SELECT * FROM comics ORDER BY id ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #F8F9FA; /* Nền sáng, mềm mại */
            color: #343A40; /* Màu chữ tổng thể */
            padding: 40px 20px; /* Tăng khoảng cách lề */
        }

        .container-fluid {
            padding: 0 40px; /* Khoảng cách lề cho nội dung chính */
        }

        /* Nút quay về trang chủ */
        .home-btn {
            position: absolute;
            top: 25px;
            right: 25px;
            background-color: #6C757D; /* Màu xám trung tính */
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        .home-btn:hover {
            background-color: #5A6268;
            transform: translateY(-2px); /* Hiệu ứng nâng nhẹ */
            color: white; /* Đảm bảo chữ vẫn trắng khi hover */
        }
        .home-btn i {
            margin-right: 8px;
        }

        /* Tiêu đề chính */
        h2 {
            color: #212529; /* Màu tối hơn cho tiêu đề */
            text-align: center;
            margin-bottom: 40px;
            font-weight: 700;
            font-size: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: -0.5px; /* Giảm khoảng cách chữ */
        }
        h2 i {
            margin-right: 15px;
            color:rgb(0, 0, 0); /* Màu xanh dương cho biểu tượng */
            font-size: 40px;
        }

        /* Tiêu đề phụ (cho từng phần) */
        h3 {
            color:rgb(0, 0, 0); /* Màu xanh dương đậm cho tiêu đề phụ */
            margin-top: 50px;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        h3 i {
            margin-right: 12px;
            font-size: 28px;
            color:rgb(0, 0, 0); /* Màu xanh dương cho biểu tượng */
        }

        /* Bảng */
        .table {
            background-color: #FFFFFF; /* Nền trắng cho bảng */
            border-radius: 8px; /* Bo tròn góc bảng */
            overflow: hidden; /* Đảm bảo nội dung không tràn khi bo góc */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); /* Đổ bóng nhẹ */
            border-collapse: separate; /* Để bo tròn góc border */
            border-spacing: 0;
        }

        .table thead th {
            background-color: #E9ECEF; /* Nền tiêu đề bảng màu xám nhạt */
            color: #495057; /* Màu chữ đậm hơn */
            font-weight: 600;
            padding: 15px 12px;
            border-bottom: 1px solid #DEE2E6; /* Viền dưới tiêu đề */
            text-align: left;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: #F0F8FF; /* Nền xanh nhạt khi hover */
        }

        .table td {
            padding: 12px;
            vertical-align: middle;
            border-top: 1px solid #DEE2E6; /* Viền trên các ô */
        }

        /* Ảnh bìa trong bảng */
        .table img {
            max-width: 60px;
            height: auto;
            border-radius: 4px;
            border: 1px solid #E0E0E0;
        }

        /* Nút hành động trong bảng */
        .btn-xs {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 4px;
            font-weight: 500;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .btn-danger {
            background-color: #DC3545; /* Màu đỏ nổi bật cho nút xóa */
            border-color: #DC3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #C82333;
            border-color: #BD2130;
            transform: translateY(-1px);
        }

        /* Tùy chỉnh Bootstrap để khớp với tông màu */
        .table-bordered > thead > tr > th,
        .table-bordered > tbody > tr > td {
            border: none; /* Bỏ viền mặc định của bootstrap table-bordered */
        }
        .table-bordered {
            border: none; /* Bỏ viền ngoài cùng của bảng */
        }

        /* Căn chỉnh cột hành động */
        td:last-child, th:last-child {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <a href="index.php" class="btn home-btn"><i class="fas fa-arrow-left"></i> Trang chủ</a>
        <h2><i class="fas fa-crown"></i> Quản trị tài khoản & Nội dung</h2>

        <h3><i class="fas fa-user-circle"></i> Người dùng</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên người dùng</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u['id']); ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['role']); ?></td>
                        <td>
                            <?php if ($u['role'] != 'admin'): ?>
                                <a href="delete_user.php?id=<?php echo htmlspecialchars($u['id']); ?>" class="btn btn-danger btn-xs" onclick="return confirm('Bạn có chắc chắn muốn xoá người dùng này không? Hành động này không thể hoàn tác!')">Xoá</a>
                            <?php else: ?>
                                <span class="text-muted">Không khả dụng</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3><i class="fas fa-book-open"></i> Truyện tranh</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên truyện</th>
                    <th>Thể loại</th>
                    <th>Uploader ID</th>
                    <th>Ảnh bìa</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($c = $comics->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['id']); ?></td>
                        <td><?php echo htmlspecialchars($c['title']); ?></td>
                        <td><?php echo htmlspecialchars($c['genre']); ?></td>
                        <td><?php echo htmlspecialchars($c['uploader_id']); ?></td>
                        <td>
                            <?php if (!empty($c['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($c['cover_image']); ?>" alt="Ảnh bìa">
                            <?php else: ?>
                                Không ảnh
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="delete_comic.php?id=<?php echo htmlspecialchars($c['id']); ?>" class="btn btn-danger btn-xs" onclick="return confirm('Bạn có chắc chắn muốn xoá truyện này không? Hành động này không thể hoàn tác!')">Xoá</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
