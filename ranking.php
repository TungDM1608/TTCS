<?php
include 'includes/session.php';
include 'includes/db_connect.php';

// Truyện có lượt đọc cao nhất
$sql = "
SELECT c.id, c.title, c.genre, COUNT(v.id) AS view_count
FROM comics c
JOIN comic_views v ON c.id = v.comic_id
WHERE v.viewed_at >= NOW() - INTERVAL 7 DAY
GROUP BY c.id
ORDER BY view_count DESC
LIMIT 10
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bảng xếp hạng truyện</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body class="container">
    <h2>🏆 Top Truyện Hot Trong Tuần</h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Hạng</th>
                    <th>Tên Truyện</th>
                    <th>Thể loại</th>
                    <th>Lượt Đọc</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                while ($row = $result->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['genre']); ?></td>
                    <td><?php echo $row['view_count']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>place_holder</p>
    <?php endif; ?>
</body>
</html>
