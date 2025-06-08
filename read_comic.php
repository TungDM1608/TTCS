<?php
include 'includes/session.php';
include 'includes/db_connect.php';

// Lấy và làm sạch đầu vào
$comic_id = isset($_GET['comic_id']) ? intval($_GET['comic_id']) : 0;
$chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : 0;

if (!$comic_id || !$chapter_id) {
    echo "Thiếu thông tin truyện hoặc chương.";
    exit;
}

// Ghi nhận lượt xem nếu là reader (sử dụng prepared statement)
if (is_reader() && isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
    $stmt = $conn->prepare("INSERT INTO comic_views (user_id, comic_id) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $comic_id);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Failed to prepare comic_views insert statement: " . $conn->error);
    }
}

// Lấy thông tin truyện (sử dụng prepared statement)
$comic_title = 'Đọc truyện'; // Giá trị mặc định
$comic_cover = '';
$comic_stmt = $conn->prepare("SELECT title, cover_image FROM comics WHERE id = ?");
if ($comic_stmt) {
    $comic_stmt->bind_param("i", $comic_id);
    $comic_stmt->execute();
    $comic_result = $comic_stmt->get_result();
    if ($comic_result->num_rows > 0) {
        $comic_data = $comic_result->fetch_assoc();
        $comic_title = $comic_data['title'];
        $comic_cover = $comic_data['cover_image'];
    }
    $comic_stmt->close();
} else {
    error_log("Failed to prepare comic info statement: " . $conn->error);
}


// Lấy thông tin chương hiện tại (sử dụng prepared statement)
$current_chapter = null;
$chapter_stmt = $conn->prepare("SELECT id, chapter_number, title FROM chapters WHERE id = ? AND comic_id = ?");
if ($chapter_stmt) {
    $chapter_stmt->bind_param("ii", $chapter_id, $comic_id);
    $chapter_stmt->execute();
    $chapter_result = $chapter_stmt->get_result();
    if ($chapter_result->num_rows == 0) {
        echo "Chương không tồn tại hoặc không thuộc về truyện này.";
        exit;
    }
    $current_chapter = $chapter_result->fetch_assoc();
    $chapter_stmt->close();
} else {
    error_log("Failed to prepare current chapter statement: " . $conn->error);
}


// Lấy chương trước và sau (sử dụng prepared statement)
$prev = null;
$next = null;

if ($current_chapter) { // Đảm bảo $current_chapter đã được lấy thành công
    $prev_stmt = $conn->prepare("SELECT id FROM chapters WHERE comic_id = ? AND chapter_number < ? ORDER BY chapter_number DESC LIMIT 1");
    if ($prev_stmt) {
        $prev_stmt->bind_param("ii", $comic_id, $current_chapter['chapter_number']);
        $prev_stmt->execute();
        $prev_result = $prev_stmt->get_result();
        $prev = $prev_result->fetch_assoc();
        $prev_stmt->close();
    } else {
        error_log("Failed to prepare previous chapter statement: " . $conn->error);
    }

    $next_stmt = $conn->prepare("SELECT id FROM chapters WHERE comic_id = ? AND chapter_number > ? ORDER BY chapter_number ASC LIMIT 1");
    if ($next_stmt) {
        $next_stmt->bind_param("ii", $comic_id, $current_chapter['chapter_number']);
        $next_stmt->execute();
        $next_result = $next_stmt->get_result();
        $next = $next_result->fetch_assoc();
        $next_stmt->close();
    } else {
        error_log("Failed to prepare next chapter statement: " . $conn->error);
    }
}


// Lấy danh sách tất cả chương (sử dụng prepared statement)
$all_chapters = [];
$all_chapters_stmt = $conn->prepare("SELECT id, chapter_number, title FROM chapters WHERE comic_id = ? ORDER BY chapter_number DESC");
if ($all_chapters_stmt) {
    $all_chapters_stmt->bind_param("i", $comic_id);
    $all_chapters_stmt->execute();
    $all_chapters_result = $all_chapters_stmt->get_result();
    while ($row = $all_chapters_result->fetch_assoc()) {
        $all_chapters[] = $row;
    }
    $all_chapters_stmt->close();
} else {
    error_log("Failed to prepare all chapters statement: " . $conn->error);
}

// Lấy danh sách trang của chương hiện tại (sử dụng prepared statement)
$pages_data = [];
$pages_stmt = $conn->prepare("SELECT image_path FROM pages WHERE chapter_id = ? ORDER BY page_number ASC");
if ($pages_stmt) {
    $pages_stmt->bind_param("i", $chapter_id);
    $pages_stmt->execute();
    $pages_result = $pages_stmt->get_result();
    while ($row = $pages_result->fetch_assoc()) {
        $pages_data[] = $row['image_path'];
    }
    $pages_stmt->close();
} else {
    error_log("Failed to prepare pages statement: " . $conn->error);
}


// Xử lý gửi bình luận (đã có prepared statement)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment']) && isset($_SESSION['user'])) {
    $content = trim($_POST['comment']);
    $user_id = $_SESSION['user']['id'];
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO comments (comic_id, user_id, content) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iis", $comic_id, $user_id, $content);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Failed to prepare comment insert statement: " . $conn->error);
        }
        header("Location: read_comic.php?comic_id=$comic_id&chapter_id=$chapter_id");
        exit();
    }
}

// Xử lý xóa bình luận (chỉ admin - sử dụng prepared statement)
if (isset($_GET['delete_comment']) && is_admin()) {
    $comment_id = intval($_GET['delete_comment']);

    // Lấy user_id của bình luận để thông báo
    $comment_user_stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $user_id_to_notify = null;
    if ($comment_user_stmt) {
        $comment_user_stmt->bind_param("i", $comment_id);
        $comment_user_stmt->execute();
        $comment_user_result = $comment_user_stmt->get_result();
        if ($comment_user_result->num_rows > 0) {
            $comment_data = $comment_user_result->fetch_assoc();
            $user_id_to_notify = $comment_data['user_id'];
        }
        $comment_user_stmt->close();
    } else {
        error_log("Failed to prepare comment user select statement: " . $conn->error);
    }

    // Xóa bình luận
    $delete_comment_stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    if ($delete_comment_stmt) {
        $delete_comment_stmt->bind_param("i", $comment_id);
        $delete_comment_stmt->execute();
        $delete_comment_stmt->close();

        // Gửi thông báo nếu có user_id để thông báo
        if ($user_id_to_notify !== null) {
            $msg = "Bình luận của bạn đã bị xóa bởi quản trị viên.";
            $notify_stmt = $conn->prepare("INSERT INTO notifications (message, user_id) VALUES (?, ?)");
            if ($notify_stmt) {
                $notify_stmt->bind_param("si", $msg, $user_id_to_notify);
                $notify_stmt->execute();
                $notify_stmt->close();
            } else {
                error_log("Failed to prepare notification insert statement: " . $conn->error);
            }
        }
    } else {
        error_log("Failed to prepare delete comment statement: " . $conn->error);
    }
    header("Location: read_comic.php?comic_id=$comic_id&chapter_id=$chapter_id");
    exit();
}

// Danh sách bình luận (sử dụng prepared statement cho comic_id)
$comment_list = [];
$comment_sql = "
    SELECT c.id, c.content, c.created_at, u.username, u.id as uid
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.comic_id = ?
    ORDER BY c.created_at DESC
";
$comment_stmt = $conn->prepare($comment_sql);
if ($comment_stmt) {
    $comment_stmt->bind_param("i", $comic_id);
    $comment_stmt->execute();
    $comment_result = $comment_stmt->get_result();
    while ($cmt = $comment_result->fetch_assoc()) {
        $comment_list[] = $cmt;
    }
    $comment_stmt->close();
} else {
    error_log("Failed to prepare comment list statement: " . $conn->error);
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($comic_title) ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body {
            font-family: sans-serif;
            background-color: #f0f2f5; /* Màu nền xám nhạt */
            padding-top: 60px; /* Khoảng cách cho nút trang chủ cố định */
            padding-bottom: 60px; /* Khoảng cách cho thanh điều hướng dưới cố định */
            position: relative; /* Để định vị tuyệt đối thanh điều hướng dưới */
        }

        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 40px; /* Đủ khoảng trống cho thanh điều hướng dưới */
        }

        .container1 {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px; /* Đủ khoảng trống cho thanh điều hướng dưới */
            max-width: 800px; /* Giới hạn chiều rộng của phần này */
            margin: 0 auto; /* Căn giữa phần này */
        }

        .top-buttons {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 10;
            display: flex;
            flex-direction: column; /* Xếp các nút theo chiều dọc */
            gap: 0px; /* Khoảng cách giữa các nút */
        }

        .home-btn:hover {
            background-color: #e0a800;
        }

        .chapters-btn {
            background-color: #007bff; /* Màu xanh dương */
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }

        .chapters-btn:hover {
            background-color: #0056b3;
        }

        .comic-title-header {
            color: #343a40; /* Màu đen */
            text-align: center;
            margin-bottom: 20px;
            margin-top: 20px; /* Tạo khoảng cách với các nút cố định */
        }

        .nav-btns-top {
            text-align: center;
            margin-bottom: 20px;

        }

        .nav-btns-top .btn {
            margin: 0 5px;
            background-color:rgb(49, 170, 47); /* Màu nền sáng */
            color: #fff
        }

        .nav-btns-top .btn:hover{
            margin: 0 5px;
            background-color:rgb(7, 133, 5); /* Màu nền sáng */
            color: #fff
        }

        .chapter-page {
            margin-bottom: 20px;
            border: 1px solid #dee2e6; /* Viền khung cho ảnh */
            border-radius: 5px;
            overflow: hidden; /* Ẩn các phần tràn ra ngoài border-radius */
            background-color: #fff; /* Nền trắng cho từng trang */
        }

        .chapter-page img {
            display: block; /* Loại bỏ khoảng trắng thừa dưới ảnh */
            width: 100%; /* Ảnh chiếm toàn bộ chiều rộng khung */
            height: auto;
        }

        .no-pages {
            color: #6c757d; /* Màu xám */
            text-align: center;
        }

        .comic-info-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center; /* Căn giữa ảnh và tiêu đề theo chiều dọc */
        }

        .comic-cover {
            width: 80px; /* Điều chỉnh kích thước ảnh bìa */
            height: auto;
            border-radius: 5px;
            margin-right: 15px;
        }

        .comic-info-container h2 {
            margin: 0; /* Loại bỏ margin mặc định của h2 */
            font-size: 1.5em;
        }

        /* Thanh điều hướng cố định ở dưới */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #343a40; /* Màu nền tối */
            color: #fff;
            padding: 10px;
            display: flex;
            justify-content: center; /* Căn giữa các phần tử */
            align-items: center;
            z-index: 20; /* Đảm bảo nó ở trên nội dung */
            height: 50px; /* Chiều cao cố định cho thanh điều hướng */
        }

        .bottom-nav a {
            color: #fff;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            margin: 0 10px; /* Thêm khoảng cách giữa các nút */
        }

        .bottom-nav a:hover {
            background-color: #495057;
        }

        .bottom-nav select {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            background-color: #fff;
            color: #333;
            margin: 0 10px; /* Thêm khoảng cách với các nút */
        }

        .bottom-nav .nav-btn-prev,
        .bottom-nav .nav-btn-next {
            background: #fff;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
            outline: none;
            color:#666
        }

        .bottom-nav .nav-btn-prev:hover,
        .bottom-nav .nav-btn-next:hover {
            color: #ddd;
            background-color:rgb(134, 134, 134); /* Màu nền khi hover */
        }

        /* CSS cho phần bình luận */
        .comment-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .comment-item {
            border-bottom: 1px solid #eee; /* Viền nhẹ phân cách bình luận */
            padding: 15px 0;
            margin-bottom: 10px;
        }
        .comment-item:last-child {
            border-bottom: none; /* Không có viền ở bình luận cuối cùng */
        }
        .comment-meta {
            font-size: 0.85em;
            color: #888;
            margin-left: 10px;
        }
        .comment-content {
            margin-top: 5px;
            color: #333;
            line-height: 1.5;
        }
        .comment-author {
            font-weight: bold;
            color:rgb(0, 0, 0); /* Màu xanh dương cho tên người dùng */
        }
        .delete-btn {
            font-size: 0.8em;
            color: #dc3545; /* Màu đỏ cho nút xóa */
            margin-left: 15px;
            text-decoration: none;
        }
        .delete-btn:hover {
            text-decoration: underline;
        }
        .comment-form textarea {
            resize: vertical; /* Cho phép thay đổi kích thước theo chiều dọc */
            min-height: 80px;
            margin-bottom: 10px;
        }
        .comment-form .btn {
            background-color: rgb(49, 170, 47);
            border: none;
            color: #fff;
        }
        .comment-form .btn:hover {
            background-color: rgb(7, 133, 5);
        }
    </style>
</head>
<body>
    <div class="top-buttons">
        <a href="index.php" class="btn btn-warning home-btn"> Trang chủ</a>
        <a href="view_chapters.php?comic_id=<?= $comic_id ?>" class="btn btn-primary chapters-btn"> Danh sách chương</a>
    </div>

    <div class="container">
        <div class="comic-info-container">
            <?php if (!empty($comic_cover)): ?>
                <img src="<?= htmlspecialchars($comic_cover) ?>" alt="<?= htmlspecialchars($comic_title) ?>" class="comic-cover">
            <?php endif; ?>
            <h2 class="comic-title-header">📖 <?= htmlspecialchars($comic_title) ?> - Chương <?= htmlspecialchars($current_chapter['chapter_number']) ?></h2>
        </div>

        <div class="nav-btns-top">
            <?php if ($prev): ?>
                <a href="read_comic.php?comic_id=<?= $comic_id ?>&chapter_id=<?= $prev['id'] ?>" class="btn btn-outline-primary">← Chương trước</a>
            <?php endif; ?>
            <?php if ($next): ?>
                <a href="read_comic.php?comic_id=<?= $comic_id ?>&chapter_id=<?= $next['id'] ?>" class="btn btn-outline-primary">Chương tiếp →</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container1">
        <?php if (empty($pages_data)): ?>
            <p class="no-pages">Chương này chưa có trang nào!</p>
        <?php else: ?>
            <?php foreach ($pages_data as $img_path): ?>
                <div class="chapter-page">
                    <img src="<?= htmlspecialchars($img_path) ?>" alt="Trang truyện" class="img-responsive center-block">
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="bottom-nav">
        <?php if ($prev): ?>
            <a href="read_comic.php?comic_id=<?= $comic_id ?>&chapter_id=<?= $prev['id'] ?>" class="nav-btn-prev">←</a>
        <?php endif; ?>

        <?php if (!empty($all_chapters)): ?>
            <select onchange="window.location.href = this.value;">
                <?php foreach ($all_chapters as $chapter): ?>
                    <option value="read_comic.php?comic_id=<?= $comic_id ?>&chapter_id=<?= $chapter['id'] ?>"
                        <?php if ($chapter['id'] == $chapter_id): ?>selected<?php endif; ?>>
                        Chương <?= htmlspecialchars($chapter['chapter_number']) ?>: <?= htmlspecialchars($chapter['title'] ?? 'Không tiêu đề') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <?php if ($next): ?>
            <a href="read_comic.php?comic_id=<?= $comic_id ?>&chapter_id=<?= $next['id'] ?>" class="nav-btn-next">→</a>
        <?php endif; ?>
    </div>

    <div class="container comment-section">
        <h3>Bình luận</h3>

        <?php if (isset($_SESSION['user'])): ?>
        <form method="post" class="comment-form">
            <textarea name="comment" class="form-control" placeholder="Nhập bình luận của bạn..." required></textarea>
            <button type="submit" class="btn">Gửi bình luận</button>
        </form>
        <?php else: ?>
        <p>Vui lòng <a href="login.php">đăng nhập</a> để gửi bình luận.</p>
        <?php endif; ?>

        <div class="comment-list" style="margin-top: 20px;">
            <?php if (!empty($comment_list)): ?>
                <?php foreach ($comment_list as $cmt): ?>
                    <div class="comment-item">
                        <div>
                            <span class="comment-author"><?= htmlspecialchars($cmt['username']) ?></span>
                            <span class="comment-meta"><?= $cmt['created_at'] ?></span>
                            <?php if (is_admin()): ?>
                                <a href="?comic_id=<?= $comic_id ?>&chapter_id=<?= $chapter_id ?>&delete_comment=<?= $cmt['id'] ?>" class="delete-btn" onclick="return confirm('Xác nhận xóa bình luận này?')">[Xóa]</a>
                            <?php endif; ?>
                        </div>
                        <p class="comment-content"><?= nl2br(htmlspecialchars($cmt['content'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Chưa có bình luận nào cho truyện này.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>