<?php
include 'includes/db_connect.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    if (!in_array($role, ['reader', 'uploader'])) {
        $role = 'reader';
    }

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password, $role);

    if ($stmt->execute()) {
        $message = "Đăng ký thành công! <a href='login.php'>Đăng nhập</a>";
    } else {
        $message = "Lỗi: Email đã tồn tại hoặc dữ liệu không hợp lệ.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f2f2f2;
        }
        .register-box {
            max-width: 450px;
            margin: 80px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <h2>Đăng ký tài khoản</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Tên người dùng:</label>
                <input type="text" name="username" required class="form-control">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required class="form-control">
            </div>
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" required class="form-control">
            </div>
            <div class="form-group">
                <label>Vai trò:</label>
                <select name="role" class="form-control" required>
                    <option value="reader">Độc giả</option>
                    <option value="uploader">Uploader</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
        </form>
        <p style="margin-top:15px; text-align:center;">
            Đã có tài khoản? 👉 <a href="login.php">Đăng nhập</a>
        </p>
    </div>
</body>
</html>
