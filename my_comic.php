<?php
include 'includes/session.php';
include 'includes/db_connect.php';

if (!is_uploader()) {
    echo "Chỉ uploader mới có quyền truy cập!";
    exit;
}

$uploader_id = $_SESSION['user']['id'];
$deleted = false;

// Xoá truyện
if (isset($_GET['delete_comic'])) {
    $comic_id = intval($_GET['delete_comic']);

    $check = $conn->prepare("SELECT id FROM comics WHERE id = ? AND uploader_id = ?");
    $check->bind_param("ii", $comic_id, $uploader_id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows === 1) {
        // Xoá chương
        $conn->query("DELETE FROM pages WHERE chapter_id IN (SELECT id FROM chapters WHERE comic_id = $comic_id)");
        $conn->query("DELETE FROM chapters WHERE comic_id = $comic_id");
        $conn->query("DELETE FROM comics WHERE id = $comic_id");

        $deleted = true;
    }
}

$sql = "SELECT * FROM comics WHERE uploader_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uploader_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Truyện của tôi</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body {
            background-color: #F8F8F8; /* Nền trắng hơi ngả xám */
            color: #333333; /* Chữ xám đậm */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Font hiện đại hơn */
            padding-top: 30px; /* Thêm khoảng trống trên cùng */
        }

        .container {
            width: 90%; /* Tăng chiều rộng container */
            max-width: 1200px; /* Giới hạn chiều rộng tối đa */
        }

        h2 {
            color: #222222; /* Tiêu đề màu đen hơn */
            margin-bottom: 30px;
            font-weight: 600; /* Làm đậm hơn một chút */
            display: flex;
            align-items: center;
            font-size: 28px; /* Kích thước lớn hơn */
        }

        h2 svg {
            margin-right: 12px;
            color: #444; /* Màu icon hơi xám */
        }

        .alert-success {
            background-color: #E0E0E0; /* Nền xám nhạt cho thông báo thành công */
            color: #333333; /* Chữ xám đậm */
            border: 1px solid #CCCCCC;
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 6px; /* Bo tròn hơn */
            font-size: 16px;
        }
        .alert-success::before {
            content: "✅ "; /* Icon vẫn giữ nguyên */
        }

        .btn {
            background-color: #666666; /* Nền nút xám đậm */
            color: white; /* Chữ trắng */
            border: none; /* Bỏ viền */
            padding: 10px 20px;
            border-radius: 5px; /* Bo tròn hơn */
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease, transform 0.2s ease; /* Thêm hiệu ứng transform */
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 10px; /* Khoảng cách giữa các nút */
        }

        .btn:hover {
            background-color: #444444; /* Nền nút đậm hơn khi di chuột qua */
            color: white;
            text-decoration: none;
            transform: translateY(-2px); /* Nâng nhẹ nút lên */
        }

        .btn-info {
            background-color: #AAAAAA; /* Nút quay về danh sách */
        }

        .btn-info:hover {
            background-color: #999999;
        }

        /* --- Giao diện Card --- */
        .comic-list {
            display: grid; /* Sử dụng Grid để tạo layout cột */
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); /* Tự động điều chỉnh số cột */
            gap: 25px; /* Khoảng cách giữa các card */
            margin-top: 20px;
        }

        .comic-card {
            background-color: #FFFFFF;
            border: 1px solid #E0E0E0; /* Viền thẻ */
            border-radius: 8px; /* Bo tròn các góc */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); /* Đổ bóng nhẹ */
            overflow: hidden; /* Đảm bảo ảnh không tràn ra ngoài */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column; /* Xếp chồng nội dung theo cột */
        }

        .comic-card:hover {
            transform: translateY(-5px); /* Nâng nhẹ card khi di chuột */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); /* Bóng đậm hơn */
        }

        .comic-card-image {
            width: 100%;
            height: 250px; /* Chiều cao cố định cho ảnh bìa */
            object-fit: cover; /* Đảm bảo ảnh vừa khung mà không bị méo */
            border-bottom: 1px solid #EAEAEA;
        }

        .comic-card-image.no-image {
            background-color: #F0F0F0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777777;
            font-style: italic;
            height: 250px;
        }

        .comic-card-content {
            padding: 15px;
            flex-grow: 1; /* Cho phép nội dung card mở rộng */
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Đẩy action xuống cuối */
        }

        .comic-card-title {
            font-size: 19px;
            font-weight: 700;
            color: #222222;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .comic-card-meta {
            font-size: 13px;
            color: #777777;
            margin-bottom: 5px;
        }

        .comic-card-actions {
            margin-top: 15px;
            border-top: 1px solid #F0F0F0; /* Đường kẻ phân cách */
            padding-top: 15px;
            display: flex;
            flex-wrap: wrap; /* Cho phép các nút xuống dòng nếu cần */
            gap: 8px; /* Khoảng cách giữa các nút */
        }

        .comic-card-actions .btn {
            font-size: 13px;
            padding: 7px 12px;
            flex-grow: 1; /* Các nút có thể mở rộng */
            text-align: center;
        }

        /* Nút "Thêm chương" và "Xoá" trong card */
        .comic-card-actions .btn-success {
            background-color: #555555;
            color: white;
        }
        .comic-card-actions .btn-success:hover {
            background-color: #333333;
        }

        .comic-card-actions .btn-danger {
            background-color: #888888;
            color: white;
        }
        .comic-card-actions .btn-danger:hover {
            background-color: #666666;
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #777777;
            background-color: #F0F0F0;
            border-radius: 8px;
            margin-top: 30px;
            border: 1px dashed #CCCCCC;
        }
    </style>
</head>
<body class="container">
    <a href="dashboard_uploader.php" class="btn btn-default">← Quay về trang cá nhân</a>
    <h2><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M21 5H3c-1.103 0-2 .897-2 2v10c0 1.103.897 2 2 2h18c1.103 0 2-.897 2-2V7c0-1.103-.897-2-2-2zm-18 9v-4h18l.001 4H3zm18-7h-4V7h4v2z"/></svg> Danh sách truyện của tôi</h2>

    <?php if ($deleted): ?>
        <div class="alert alert-success">✅ Đã xoá truyện thành công!</div>
        <a href="my_comics.php" class="btn btn-info">Quay về danh sách truyện</a>
        <hr style="border-top: 1px solid #EEE; margin: 30px 0;">
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <div class="comic-list">
            <?php while($comic = $result->fetch_assoc()): ?>
            <div class="comic-card">
                <?php if ($comic['cover_image']): ?>
                    <img src="<?php echo htmlspecialchars($comic['cover_image']); ?>" alt="Ảnh bìa <?php echo htmlspecialchars($comic['title']); ?>" class="comic-card-image">
                <?php else: ?>
                    <div class="comic-card-image no-image">Không có ảnh bìa</div>
                <?php endif; ?>
                <div class="comic-card-content">
                    <div>
                        <div class="comic-card-title"><?php echo htmlspecialchars($comic['title']); ?></div>
                        <div class="comic-card-meta">Thể loại: **<?php echo htmlspecialchars($comic['genre']); ?>**</div>
                        <div class="comic-card-meta">Đăng vào: <?php echo htmlspecialchars(date('d/m/Y', strtotime($comic['created_at']))); ?></div>
                    </div>
                    <div class="comic-card-actions">
                        <a href="upload_chapter.php?comic_id=<?php echo htmlspecialchars($comic['id']); ?>" class="btn btn-success">Thêm chương</a>
                        <a href="?delete_comic=<?php echo htmlspecialchars($comic['id']); ?>" onclick="return confirm('Bạn có chắc chắn muốn xoá truyện này không? Hành động này không thể hoàn tác!')" class="btn btn-danger">Xoá truyện</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>Hiện tại bạn chưa đăng tải truyện nào.</p>
            <p>Hãy bắt đầu tạo truyện đầu tiên của bạn!</p>
            </div>
    <?php endif; ?>
</body>
</html>
