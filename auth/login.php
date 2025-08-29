<?php
@include '../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user']) || isset($_SESSION['admin'])) {
    header("Location: ../products.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                $_SESSION['admin'] = $user;
                header("Location: ../admin.php");
                exit;
            } else {
                $_SESSION['user'] = $user;
                header("Location: ../products.php");
                exit;
            }
        } else {
            $error = "Sai mật khẩu.";
        }
    } else {
        $error = "Không tìm thấy người dùng.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; position: relative; }
        input { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; transition: border-color 0.3s; }
        input:focus { outline: none; border-color: #667eea; }
        .password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: transform 0.2s; }
        button:hover { transform: translateY(-2px); }
        .error { color: #e74c3c; text-align: center; margin-bottom: 1rem; padding: 10px; background: #ffeaea; border-radius: 5px; }
        .link { text-align: center; margin-top: 1rem; }
        .link a { color: #667eea; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Đăng nhập</h2>
        <?php if(isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <input type="email" name="email" placeholder="📧 Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="🔒 Mật khẩu" required>
                <span class="password-toggle" onclick="togglePassword()">👁️</span>
            </div>
            <button type="submit">Đăng nhập</button>
        </form>
        <div class="link">
            <a href="register.php">Chưa có tài khoản? Đăng ký ngay</a>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const toggle = document.querySelector('.password-toggle');
            if (password.type === 'password') {
                password.type = 'text';
                toggle.textContent = '🙈';
            } else {
                password.type = 'password';
                toggle.textContent = '👁️';
            }
        }
    </script>
</body>
</html>