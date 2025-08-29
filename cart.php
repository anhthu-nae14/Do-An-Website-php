<?php

@include 'config/config.php';
session_start();
include 'middleware.php';
require_user();

$user_id = $_SESSION['user']['id'];

function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

if(isset($_POST['update_update_btn'])){
   $update_value = $_POST['update_quantity'];
   $update_id = $_POST['update_quantity_id'];
   $update_quantity_query = mysqli_query($conn, "UPDATE cart SET quantity = '$update_value' WHERE id = '$update_id' AND user_id = '$user_id'");
   if($update_quantity_query){
      header('location:cart.php');
   }
};

if(isset($_GET['remove'])){
   $remove_id = $_GET['remove'];
   mysqli_query($conn, "DELETE FROM cart WHERE id = '$remove_id' AND user_id = '$user_id'");
   header('location:cart.php');
};

if(isset($_GET['delete_all'])){
   mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");
   header('location:cart.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Giỏ hàng</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <link rel="stylesheet" href="css\style.css">

</head>
<body>

<?php include 'header.php'; ?>

<div class="container">

<section class="shopping-cart">

   <h1 class="heading">Giỏ hàng</h1>

   <table>

      <thead>
         <th>Ảnh</th>
         <th>Tên</th>
         <th>Giá</th>
         <th>Số lượng</th>
         <th>Tổng giá</th>
         <th>Tình trạng</th>
      </thead>

      <tbody>

         <?php 
         
         $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'");
         $grand_total = 0;
         if(mysqli_num_rows($select_cart) > 0){
            while($fetch_cart = mysqli_fetch_assoc($select_cart)){
               $sub_total = $fetch_cart['price'] * $fetch_cart['quantity'];
         ?>

         <tr>
            <td><img src="uploaded_img/<?php echo $fetch_cart['image']; ?>" height="100" alt=""></td>
            <td><?php echo $fetch_cart['name']; ?></td>
            <td><?php echo formatVND($fetch_cart['price']); ?></td>
            <td>
               <form action="" method="post">
                  <input type="hidden" name="update_quantity_id"  value="<?php echo $fetch_cart['id']; ?>" >
                  <input type="number" name="update_quantity" min="1"  value="<?php echo $fetch_cart['quantity']; ?>" >
                  <input type="submit" value="Cập nhật" name="update_update_btn">
               </form>   
            </td>
            <td><?php echo formatVND($sub_total); ?></td>
            <td><a href="cart.php?remove=<?php echo $fetch_cart['id']; ?>" onclick="return confirm('Xóa sản phẩm khỏi giỏ hàng?')" class="delete-btn"> <i class="fas fa-trash"></i> Loại bỏ</a></td>
         </tr>
         <?php
           $grand_total += $sub_total;  
            };
         };
         ?>
         <tr class="table-bottom">
            <td><a href="products.php" class="option-btn" style="margin-top: 0;">Tiếp tục mua sắm</a></td>
            <td colspan="3">Tổng cộng</td>
            <td><?php echo formatVND($grand_total); ?></td>
            <td><a href="cart.php?delete_all" onclick="return confirm('Bạn có chắc chắn muốn xóa tất cả không?');" class="delete-btn"> <i class="fas fa-trash"></i> Xóa tất cả </a></td>
         </tr>

      </tbody>

   </table>

   <div class="checkout-btn">
      <a href="checkout.php" class="btn <?= ($grand_total > 1)?'':'disabled'; ?>">Tiến hành thanh toán</a>
   </div>

</section>

</div>

<script src="js/script.js"></script>

</body>
</html>