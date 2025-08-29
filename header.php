<?php
if (session_status() === PHP_SESSION_NONE) session_start();
@include 'config/config.php';
?>
<link rel="stylesheet" href="css\style.css">


<?php
$row_count = 0; // tránh lỗi undefined nếu chưa gán giá trị
if (isset($conn) && isset($_SESSION['user']['id'])) {
   $user_id = $_SESSION['user']['id'];
   $select_rows = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = '$user_id'");
   $row_count = mysqli_num_rows($select_rows);
}
?>

<header class="header">
   <div class="flex">
      <a href="#" class="logo">Phone</a>

      <nav class="navbar">
   <?php if (isset($_SESSION['admin'])): ?>
      <a href="admin.php">Thêm Sản Phẩm</a>
   <?php endif; ?>
   <a href="products.php">Xem Sản Phẩm</a>
   <a href="cart.php" class="cart">Giỏ Hàng <span><?= $row_count ?></span></a>

   <?php
   if (isset($_SESSION['admin'])) {
      echo "<a href='auth/logout.php'>Đăng Xuất (Admin)</a>";
   } elseif (isset($_SESSION['user'])) {
      echo "<a href='auth/logout.php'>Đăng Xuất ({$_SESSION['user']['name']})</a>";
   } else {
      echo "<a href='auth/login.php'>Đăng Nhập</a>";
   }
   ?>
    </nav>
      <div id="menu-btn" class="fas fa-bars"></div>
   </div>
</header>
