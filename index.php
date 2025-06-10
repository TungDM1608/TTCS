<?php
include 'includes/session.php';
include 'includes/db_connect.php';

$genres = ['Action', 'Adventure', 'Comedy', 'Drama', 'School Life',
    'Fantasy', 'Psychological', 'Horror', 'Tragedy', 'Supernatural'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 24;
$offset = ($page - 1) * $limit;

$sql_base = "FROM comics WHERE 1";
if (!empty($search)) {
    $sql_base .= " AND title LIKE '%" . $conn->real_escape_string($search) . "%'";
}
if (!empty($filter_genre)) {
    $sql_base .= " AND genre = '" . $conn->real_escape_string($filter_genre) . "'";
}

$total_result = $conn->query("SELECT COUNT(*) as total $sql_base")->fetch_assoc()['total'];
$total_pages = ceil($total_result / $limit);
$result = $conn->query("SELECT * $sql_base ORDER BY created_at DESC LIMIT $limit OFFSET $offset");

// Truy vấn top 10
$ranking_sql = "
SELECT c.id, c.title, c.genre, c.cover_image
FROM comics c
LEFT JOIN comic_views v ON c.id = v.comic_id
WHERE v.viewed_at >= NOW() - INTERVAL 7 DAY
GROUP BY c.id
ORDER BY COUNT(v.id) DESC
LIMIT 10";
$ranking_result = $conn->query($ranking_sql);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Trang chủ truyện tranh</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  
  
  <style>
    .ranking-carousel-wrapper {
        position: relative;
        width: 100%;
        overflow: hidden;
        padding: 10px 0;
        margin: 30px 0;
    }

    .ranking-carousel {
        overflow-x: auto;
        scroll-behavior: smooth;
    }

    .carousel-track {
        display: flex;
        gap: 20px;
        width: max-content;
        padding: 0 40px;
    }

    .carousel-item-wide {
        display: flex;
        width: 240px; /* Hoặc một giá trị cụ thể, ví dụ: 300px */
        height: auto; /* Đảm bảo chiều cao tự điều chỉnh */
        aspect-ratio: 3 / 4;
        background-color: #1e1e1e;
        color: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        margin: 0 10px;
    }

    .carousel-cover {
        width: 220px;
        height: auto;
        object-fit: cover;
    }

    .carousel-info {
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        flex: 1;
    }

    .carousel-title {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .carousel-genres {
        margin-bottom: 10px;
    }

    .genre-badge {
        display: inline-block;
        background-color: #4caf50;
        color: white;
        padding: 3px 8px;
        margin-right: 5px;
        border-radius: 3px;
        font-size: 12px;
        text-transform: uppercase;
    }

    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background-color: rgba(0, 0, 0, 0.3);
        color: white;
        border: none;
        font-size: 24px;
        padding: 10px;
        z-index: 10;
        cursor: pointer;
        border-radius: 50%;
    }

    .carousel-btn.left { left: 10px; }
    .carousel-btn.right { right: 10px; }
    .carousel-btn:hover { background-color: rgba(0, 0, 0, 0.6); }
    /* --- Header chính (Thanh điều hướng) --- */
        .main-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 2px 10px var(--shadow-light);
            padding: 15px 30px; /* Tăng padding */
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Cho phép các phần tử xuống dòng trên màn hình nhỏ */
            gap: 20px; /* Khoảng cách giữa các khối */
            top: 0;
            z-index: 1000;
            background-color: #eee;
        }

        /* --- Logo hoặc tiêu đề trang (nếu có) --- */
        .site-branding {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-gray);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .site-branding i {
            margin-right: 10px;
            color: var(--primary-blue);
        }

        /* --- Form Tìm kiếm --- */
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-grow: 1; /* Cho phép form tìm kiếm mở rộng */
            max-width: 600px; /* Giới hạn chiều rộng */
        }

        .search-form .form-control {
            border-radius: 25px; /* Bo tròn hoàn toàn input */
            border: 1px solid var(--border-color);
            padding: 10px 18px; /* Tăng padding */
            box-shadow: none;
            transition: all 0.3s ease;
            height: auto; /* Điều chỉnh chiều cao tự động */
        }
        .search-form .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.15); /* Shadow lớn hơn, mờ hơn */
            outline: none;
        }

        .search-form select.form-control {
            appearance: none; /* Bỏ mũi tên mặc định */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E"); /* Icon mũi tên tùy chỉnh */
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 10px;
            padding-right: 30px; /* Để icon không bị che */
        }

        .search-form .btn-primary {
            background-color: var(--primary-blue);
            border: none; /* Bỏ viền */
            color: var(--white);
            padding: 10px 22px;
            border-radius: 25px; /* Bo tròn hoàn toàn nút */
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px; /* Khoảng cách giữa icon và chữ */
        }
        .search-form .btn-primary:hover {
            background-color: #0056B3;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            transform: translateY(-2px);
        }
        .search-form .btn-primary:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* --- Phần Người dùng (Dropdown hoặc nút Login/Register) --- */
        .user-auth-section {
            display: flex;
            align-items: center;
            gap: 15px; /* Khoảng cách lớn hơn giữa các nút/dropdown */
        }

        .user-dropdown .dropdown-toggle {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            border: none;
            padding: 10px 20px;
            border-radius: 25px; /* Bo tròn hoàn toàn */
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .user-dropdown .dropdown-toggle:hover,
        .user-dropdown .dropdown-toggle:focus {
            background-color: #DDE0E3;
            color: var(--dark-gray);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            outline: none; /* Bỏ outline focus mặc định */
        }
        .user-dropdown .dropdown-toggle .caret {
            margin-left: 8px;
            border-top-color: var(--dark-gray); /* Đổi màu mũi tên */
        }

        .user-dropdown .dropdown-menu {
            border-radius: 8px; /* Bo tròn menu */
            box-shadow: 0 8px 25px var(--shadow-hover); /* Đổ bóng mạnh hơn */
            border: 1px solid var(--border-color);
            padding: 10px 0;
            min-width: 200px;
            overflow: hidden; /* Đảm bảo bo tròn góc */
        }

        .user-dropdown .dropdown-menu > li > a {
            padding: 12px 25px; /* Tăng padding */
            color: var(--dark-gray);
            display: flex;
            align-items: center;
            gap: 12px; /* Khoảng cách giữa icon và chữ */
            transition: all 0.2s ease;
            font-weight: 400;
        }
        .user-dropdown .dropdown-menu > li > a:hover,
        .user-dropdown .dropdown-menu > li > a:focus {
            background-color: #E6F3FF; /* Nền xanh nhạt hơn khi hover */
            color: var(--primary-blue);
            text-decoration: none;
        }
        .user-dropdown .dropdown-menu .divider {
            background-color: var(--border-color);
            margin: 8px 0;
        }

        /* Nút Đăng nhập/Đăng ký khi chưa login */
        .user-auth-section .btn {
            padding: 10px 22px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-auth-section .btn-primary {
            background-color: var(--primary-blue);
            border: none;
            color: var(--white);
        }
        .user-auth-section .btn-primary:hover {
            background-color: #0056B3;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            transform: translateY(-2px);
        }
        .user-auth-section .btn-default {
            background-color: var(--light-gray);
            border: none;
            color: var(--medium-gray);
        }
        .user-auth-section .btn-default:hover {
            background-color: #DDE0E3;
            color: var(--dark-gray);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .user-auth-section .btn:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* Responsive */
        @media (max-width: 992px) { /* Cho tablet và mobile */
            .main-header {
                flex-direction: column; /* Xếp chồng các phần tử */
                align-items: flex-start; /* Căn trái */
                padding: 15px 20px;
            }
            .search-form {
                width: 100%;
                max-width: 100%; /* Cho phép chiếm toàn bộ chiều rộng */
                order: 2; /* Đặt form tìm kiếm xuống dưới */
            }
            .site-branding {
                margin-bottom: 15px;
                order: 1; /* Đặt logo/tiêu đề lên trên */
            }
            .user-auth-section {
                width: 100%;
                justify-content: center; /* Căn giữa các nút/dropdown */
                order: 3; /* Đặt phần người dùng xuống cuối */
                margin-top: 15px; /* Khoảng cách */
            }
            .user-dropdown .dropdown-toggle {
                width: 100%; /* Nút dropdown full width */
                justify-content: center; /* Căn giữa nội dung trong nút */
            }
            .user-auth-section .btn {
                width: 100%; /* Nút login/register full width */
                justify-content: center;
            }
        }
        @media (max-width: 576px) { /* Cho mobile nhỏ hơn */
            .search-form input,
            .search-form select,
            .search-form button {
                width: 100%; /* Mỗi phần tử form chiếm 1 hàng */
            }
            .search-form {
                flex-direction: column;
                align-items: stretch; /* Kéo dãn các phần tử để chiếm hết chiều rộng */
            }
        }

        /* --- Footer --- */
        .main-footer {
            background-color: #343A40; /* Nền xám đậm */
            color:  #E9ECEF; /* Chữ xám nhạt */
            padding: 30px 20px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1); /* Viền trên mờ */
            margin-top: auto; /* Đẩy footer xuống cuối trang */
        }
        .main-footer p {
            margin: 0;
            font-size: 14px;
        }
        .main-footer a {
            color: #ADB5BD; /* Màu link */
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .main-footer a:hover {
            color: var(--white); /* Màu link khi hover */
            text-decoration: underline;
        }
        .main-footer .social-icons {
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .main-footer .social-icons a {
            color: var(--footer-link);
            font-size: 20px;
            margin: 0 10px;
            transition: color 0.2s ease, transform 0.2s ease;
        }
        .main-footer .social-icons a:hover {
            color: var(--white);
            transform: translateY(-3px);
            text-decoration: none;
        }

        .pagination {
            list-style: none; /* Bỏ dấu chấm đầu dòng */
            padding: 0;
            margin: 20px 0; /* Khoảng cách trên dưới */

            display: flex; /* Biến ul thành flex container */
            justify-content: center; /* Căn giữa các item con theo chiều ngang */
            align-items: center; /* Căn giữa các item con theo chiều dọc (nếu có chiều cao khác nhau) */
        }

        .pagination li {
            /* Khi dùng flexbox, li không cần display: inline-block; nữa */
            margin: 0 5px; /* Khoảng cách giữa các nút */
        }

        .pagination li a {
            display: block; /* Để padding và margin hoạt động tốt hơn */
            padding: 8px 12px;
            text-decoration: none;
            color:rgb(120, 120, 120);
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-weight: 600;
        }

        .pagination li a:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }

        .pagination li.active a {
            background-color:rgb(62, 62, 62);
            border-color:rgb(62, 62, 62);
            color: white;
        }
  </style>
</head>
<body class="container">

<header class="main-header">
        <a href="index.php" class="site-branding">
            <i class="fas fa-book-reader"></i> TT
        </a>

        <div class="search-container">
            <form method="get" class="search-form">
                <input type="text" name="search" class="form-control" placeholder="Nhập tên truyện..." value="<?= htmlspecialchars($search ?? '') ?>">
                <select name="genre" class="form-control">
                    <option value="">-- Tất cả thể loại --</option>
                    <?php 
                    // Giả sử $genres là một mảng PHP chứa các thể loại
                    // Ví dụ: $genres = ['Action', 'Fantasy', 'Romance', 'Comedy'];
                    $genres = $genres ?? ['Action', 'Fantasy', 'Romance', 'Comedy']; // Dữ liệu mẫu nếu biến chưa được định nghĩa
                    foreach ($genres as $g): ?>
                        <option value="<?= htmlspecialchars($g) ?>" <?= (($filter_genre ?? '') == $g ? "selected" : "") ?>><?= htmlspecialchars($g) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
            </form>
        </div>

        <div class="user-auth-section">
            <?php 
            if (isset($_SESSION['user'])): ?>
                <div class="dropdown user-dropdown">
                    <span class="dropdown-toggle" data-toggle="dropdown">
                        👋 Xin chào, <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong> <span class="caret"></span>
                    </span>
                    <ul class="dropdown-menu">
                        <?php if (function_exists('is_uploader') && is_uploader()): ?>
                            <li><a href="thong_bao.php"><i class="fas fa-bell"></i> Thông báo</a></li>
                            <li><a href="upload_comic.php"><i class="fas fa-plus-circle"></i> Đăng truyện</a></li>
                            <li><a href="my_comic.php"><i class="fas fa-book"></i> Truyện của tôi</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                        <?php elseif (function_exists('is_admin') && is_admin()): ?>
                            <li><a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Bảng điều khiển</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                        <?php else: /* Người dùng bình thường */ ?>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                <a href="register.php" class="btn btn-default"><i class="fas fa-user-plus"></i> Đăng ký</a>
            <?php endif; ?>
        </div>
    </header>



<!-- Carousel -->
 <h2 style="margin-top: 60px">
    <i class="fas fa-fire"></i> Truyện hay nhất tuần
 </h2>
<div class="ranking-carousel-wrapper">
  <button class="carousel-btn left">&#10094;</button>
  <div class="ranking-carousel">
    <div class="carousel-track">
      <?php if ($ranking_result->num_rows > 0): ?>
        <?php while ($row = $ranking_result->fetch_assoc()): ?>
          <div class="carousel-item-wide">
            <img src="<?= htmlspecialchars($row['cover_image']) ?>"> <class="carousel-cover" alt="<?= htmlspecialchars($row['title']) ?>">
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="carousel-item-wide">Không có dữ liệu xếp hạng.</div>
      <?php endif; ?>
    </div>
  </div>
  <button class="carousel-btn right">&#10095;</button>
</div>

<!-- Danh sách truyện -->
<h2 style="margin-top: 60px">📚 Danh sách Truyện Tranh</h2>
<div class="comic-grid">
<?php if ($result->num_rows > 0): ?>
  <?php while ($comic = $result->fetch_assoc()): ?>
    <div class="comic-item">
      <div class="comic-card" onclick="location.href='view_chapters.php?comic_id=<?= $comic['id'] ?>';">
        <?php if ($comic['cover_image']): ?>
          <img src="<?= htmlspecialchars($comic['cover_image']) ?>" class="truyen-bia">
        <?php else: ?>
          <p>Không có ảnh</p>
        <?php endif; ?>
        <h4 class="truyen-title"><?= htmlspecialchars($comic['title']) ?></h4>
        <p class="truyen-genre"><strong>Thể loại:</strong> <?= htmlspecialchars($comic['genre']) ?></p>
      </div>
    </div>
  <?php endwhile; ?>
<?php else: ?>
  <p>Không tìm thấy truyện phù hợp.</p>
<?php endif; ?>
</div>

<!-- Phân trang -->
<nav>
  <ul class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <li class="<?= ($i == $page) ? 'active' : '' ?>">
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&genre=<?= urlencode($filter_genre) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>

<footer class="main-footer">
        <div class="footer-content">
            <p>&copy; TT</p>
            <div class="social-icons">
                <a href=""><i class="fab fa-facebook-f"></i></a>
                <a href=""><i class="fab fa-twitter"></i></a>
                <a href=""><i class="fab fa-instagram"></i></a>
                <a href=""><i class="fab fa-github"></i></a>
            </div>
            <p>
                <a href="">Về chúng tôi</a> | 
                <a href="">Chính sách bảo mật</a> | 
                <a href="">Điều khoản sử dụng</a> | 
                <a href="">Liên hệ</a>
            </p>
        </div>
    </footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const track = document.querySelector(".carousel-track");
  const leftBtn = document.querySelector(".carousel-btn.left");
  const rightBtn = document.querySelector(".carousel-btn.right");
  const scrollAmount = 700;

  leftBtn.addEventListener("click", () => {
    track.scrollBy({ left: -scrollAmount, behavior: "smooth" });
  });
  rightBtn.addEventListener("click", () => {
    track.scrollBy({ left: scrollAmount, behavior: "smooth" });
  });
});
</script>
</body>
</html>
