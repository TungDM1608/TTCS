<?php
include 'includes/session.php';
include 'includes/db_connect.php';

if (!is_uploader()) {
    echo "Bạn không có quyền truy cập trang này.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Uploader Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body {
            background-color: #F0F2F5; /* Nền tổng thể nhẹ nhàng */
            color: #333; /* Màu chữ chính */
            font-family: 'Segoe UI', Arial, sans-serif; /* Font hiện đại */
            padding-top: 50px; /* Khoảng cách từ trên xuống */
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Căn chỉnh lên trên */
            min-height: 100vh; /* Chiều cao tối thiểu của body */
        }

        .container {
            width: 100%;
            max-width: 700px; /* Giới hạn chiều rộng của dashboard */
            background-color: #FFFFFF; /* Nền trắng cho phần nội dung */
            border-radius: 10px; /* Bo tròn góc */
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08); /* Đổ bóng nhẹ nhàng */
            padding: 30px;
        }

        h2 {
            color: #222; /* Màu chữ tiêu đề đậm hơn */
            text-align: center;
            margin-bottom: 40px;
            font-weight: 600;
            font-size: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        h2 svg {
            margin-right: 15px;
            color: #555; /* Màu icon tiêu đề */
            font-size: 36px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* 2 cột trên màn hình lớn, 1 cột trên màn hình nhỏ */
            gap: 25px; /* Khoảng cách giữa các thẻ */
        }

        .dashboard-card {
            background-color: #FFFFFF;
            border: 1px solid #E0E0E0; /* Viền thẻ */
            border-radius: 8px; /* Bo tròn góc thẻ */
            padding: 25px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none !important; /* Đảm bảo loại bỏ gạch chân của link */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 140px; /* Chiều cao tối thiểu của thẻ */
        }

        .dashboard-card:hover {
            transform: translateY(-5px); /* Nâng nhẹ thẻ khi di chuột */
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12); /* Đổ bóng đậm hơn */
            text-decoration: none !important;
        }

        .card-icon {
            font-size: 45px; /* Kích thước icon */
            margin-bottom: 15px;
            color: #666; /* Màu icon mặc định */
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333; /* Màu chữ tiêu đề trong thẻ */
        }

        /* Tùy chỉnh màu sắc icon và hover cho từng loại thẻ */
        /* Thông báo */
        .dashboard-card.card-notification .card-icon { color: #5B5B5B; }
        .dashboard-card.card-notification:hover .card-icon { color: #333333; }
        .dashboard-card.card-notification:hover .card-title { color: #333333; }

        /* Đăng truyện mới */
        .dashboard-card.card-new-comic .card-icon { color: #6A6A6A; }
        .dashboard-card.card-new-comic:hover .card-icon { color: #444444; }
        .dashboard-card.card-new-comic:hover .card-title { color: #444444; }

        /* Danh sách truyện */
        .dashboard-card.card-my-comics .card-icon { color: #7B7B7B; }
        .dashboard-card.card-my-comics:hover .card-icon { color: #555555; }
        .dashboard-card.card-my-comics:hover .card-title { color: #555555; }

        /* Trang chủ */
        .dashboard-card.card-home .card-icon { color: #8C8C8C; }
        .dashboard-card.card-home:hover .card-icon { color: #666666; }
        .dashboard-card.card-home:hover .card-title { color: #666666; }

        /* Đăng xuất */
        .dashboard-card.card-logout .card-icon { color: #9D9D9D; }
        .dashboard-card.card-logout:hover .card-icon { color: #777777; }
        .dashboard-card.card-logout:hover .card-title { color: #777777; }

        /* Để các icon Bootstrap vẫn hoạt động */
        .glyphicon {
            font-size: inherit; /* Giữ kích thước mặc định của Bootstrap */
            margin-right: 0; /* Loại bỏ margin nếu có */
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2>
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
            </svg>
            Xin chào, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!
        </h2>

        <div class="dashboard-grid">
            <a href="thong_bao.php" class="dashboard-card card-notification">
                <span class="card-icon"><i class="fas fa-bell"></i></span> <div class="card-title">Thông báo</div>
            </a>
            <a href="upload_comic.php" class="dashboard-card card-new-comic">
                <span class="card-icon"><i class="fas fa-plus-circle"></i></span> <div class="card-title">Đăng truyện mới</div>
            </a>
            <a href="my_comic.php" class="dashboard-card card-my-comics">
                <span class="card-icon"><i class="fas fa-book"></i></span> <div class="card-title">Danh sách truyện của tôi</div>
            </a>
            <a href="index.php" class="dashboard-card card-home">
                <span class="card-icon"><i class="fas fa-home"></i></span> <div class="card-title">Quay về trang chủ</div>
            </a>
            <a href="logout.php" class="dashboard-card card-logout">
                <span class="card-icon"><i class="fas fa-sign-out-alt"></i></span> <div class="card-title">Đăng xuất</div>
            </a>
        </div>
    </div>
</body>
</html>