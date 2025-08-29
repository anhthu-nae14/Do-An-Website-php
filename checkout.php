<?php

@include 'config/config.php';
session_start();
include 'middleware.php';
require_user();

$user_id = $_SESSION['user']['id'];

function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

if(isset($_POST['order_btn'])){

   $name = $_POST['name'];
   $number = $_POST['number'];
   $email = $_POST['email'];
   $method = $_POST['method'];
   $flat = $_POST['flat'];
   $street = $_POST['street'];
   $city = $_POST['city'];
   $state = $_POST['state'];
   $country = $_POST['country'];
   $pin_code = $_POST['pin_code'];

   $cart_query = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = '$user_id'");
   $price_total = 0;
   $product_name = [];

   if(mysqli_num_rows($cart_query) > 0){
      while($product_item = mysqli_fetch_assoc($cart_query)){
         $product_name[] = $product_item['name'] .' ('. $product_item['quantity'] .') ';
         $product_price = $product_item['price'] * $product_item['quantity'];
         $price_total += $product_price;
      };
   };

   $total_product = implode(', ',$product_name);

   $detail_query = mysqli_query($conn, "INSERT INTO `order`(name, number, email, method, flat, street, city, state, country, pin_code, total_products, total_price, user_id) VALUES('$name','$number','$email','$method','$flat','$street','$city','$state','$country','$pin_code','$total_product','$price_total', '$user_id')") or die('query failed');

   if($cart_query && $detail_query){
      echo "<div class='order-message-container'><div class='message-container'>
      <h3>Cảm ơn bạn đã mua sắm!</h3>
      <div class='order-detail'><span>".$total_product."</span>
      <span class='total'> Tổng cộng : ".formatVND($price_total)."</span></div>
      <div class='customer-details'>
      <p> Tên của bạn : <span>".$name."</span> </p>
      <p> Số điện thoại : <span>".$number."</span> </p>
      <p> Email của bạn : <span>".$email."</span> </p>
      <p> Địa chỉ của bạn : <span>".$flat.", ".$street.", ".$city.", ".$state.", ".$country." - ".$pin_code."</span> </p>
      <p> Phương thức thanh toán của bạn : <span>".$method."</span> </p>
      <p>(*thanh toán khi sản phẩm đến*)</p>
      </div><a href='products.php' class='btn'>Tiếp tục mua sắm</a></div></div>";
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Thanh toán</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php include 'header.php'; ?>

<div class="container">

<section class="checkout-form">

   <h1 class="heading">hoàn thành đơn đặt hàng của bạn</h1>

   <form action="" method="post">

   <div class="display-order">
      <?php
         $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'");
         $total = 0;
         if(mysqli_num_rows($select_cart) > 0){
            while($fetch_cart = mysqli_fetch_assoc($select_cart)){
               $item_total = $fetch_cart['price'] * $fetch_cart['quantity'];
               $total += $item_total;
      ?>
      <div class="product-display">
         <span><?= $fetch_cart['name']; ?> (<?= $fetch_cart['quantity']; ?>) - <?= formatVND($item_total); ?></span>
      </div>
      <?php
            }
            $grand_total = $total;
         }else{
            echo "<div class='display-order'><span>Giỏ hàng của bạn trống!</span></div>";
            $grand_total = 0;
         }
      ?>
      <span class="grand-total"> Tổng cộng : <?= formatVND($grand_total); ?> </span>
   </div>

      <div class="flex">
         <div class="inputBox">
            <span>Tên của bạn</span>
            <input type="text" placeholder="Nhập tên của bạn" name="name" required>
         </div>
         <div class="inputBox">
            <span>Số điện thoại</span>
            <input type="number" placeholder="Nhập số điện thoại" name="number" required>
         </div>
         <div class="inputBox">
            <span>Email của bạn</span>
            <input type="email" placeholder="Nhập Email của bạn" name="email" required>
         </div>
         <div class="inputBox">
            <span>Phương thức thanh toán</span>
            <select name="method">
               <option value="cash on delivery" selected>Tiền mặt khi giao hàng</option>
               <option value="credit card">Thẻ tín dụng</option>
               <option value="bank transfer">Chuyển khoản ngân hàng</option>
               <option value="momo">Ví MoMo</option>
               <option value="zalopay">ZaloPay</option>
            </select>
         </div>
         <div class="inputBox">
            <span>Địa chỉ cụ thể</span>
            <input type="text" placeholder="Số nhà, tên đường" name="flat" required>
         </div>
         <div class="inputBox">
            <span>Phường/Xã</span>
            <input type="text" placeholder="Phường hoặc Xã" name="street" required>
         </div>
         <div class="inputBox">
            <span>Thành phố/Thị xã</span>
            <input type="text" placeholder="Ví dụ: Long Khánh" name="city" required>
         </div>
         <div class="inputBox">
            <span>Tỉnh/Thành phố</span>
            <input type="text" placeholder="Ví dụ: Đồng Nai" name="state" required>
         </div>
         <div class="inputBox">
            <span>Quốc gia</span>
            <input type="text" placeholder="Việt Nam" name="country" value="Việt Nam" required>
         </div>
         <div class="inputBox">
            <span>Mã bưu điện</span>
            <input type="text" placeholder="Ví dụ: 810000" name="pin_code" required>
         </div>
      </div>
      <input type="submit" value="Đặt hàng ngay" name="order_btn" class="btn">
   </form>

</section>

</div>

<script src="js/script.js"></script>
   
</body>
</html>