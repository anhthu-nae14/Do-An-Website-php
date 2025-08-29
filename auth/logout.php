<?php
session_start();

if (isset($_POST['confirm_logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../products.php");
    exit;
}

if (!isset($_SESSION['user']) && !isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng xuất</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #333; margin-bottom: 1rem; }
        p { color: #666; margin-bottom: 2rem; }
        .buttons { display: flex; gap: 1rem; }
        button, .btn { flex: 1; padding: 12px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; text-decoration: none; display: inline-block; transition: transform 0.2s; }
        .logout-btn { background: #e74c3c; color: white; }
        .cancel-btn { background: #95a5a6; color: white; }
        button:hover, .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <h2>🚪 Đăng xuất</h2>
        <p>Bạn có chắc chắn muốn đăng xuất không?</p>
        <div class="buttons">
            <form method="post" style="flex: 1;">
                <button type="submit" name="confirm_logout" class="logout-btn">Đăng xuất</button>
            </form>
            <a href="javascript:history.back()" class="btn cancel-btn">Hủy</a>
        </div>
    </div>
</body>
</html>