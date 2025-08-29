<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

@include 'config/config.php';       
@include 'auth/check_auth.php';

function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

class ImageProcessor {
    
    private $targetWidth = 600;
    private $targetHeight = 600;
    private $quality = 85; // JPEG chất lượng (1-100)
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    public function processUpload($file, $uploadDir, $filename = null) {
        try {
            $validation = $this->validateUpload($file);
            if (!$validation['success']) {
                return $validation;
            }
            
            if (!$filename) {
                $filename = uniqid('product_', true);
            }

            $imageInfo = getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                return ['success' => false, 'error' => 'Invalid image file'];
            }

            $sourceImage = $this->createImageResource($file['tmp_name'], $imageInfo[2]);
            if (!$sourceImage) {
                return ['success' => false, 'error' => 'Failed to process image'];
            }

            $processedImage = $this->resizeAndCrop($sourceImage, $imageInfo[0], $imageInfo[1]);

            $outputPath = $uploadDir . '/' . $filename . '.jpg';
            $saved = imagejpeg($processedImage, $outputPath, $this->quality);

            imagedestroy($sourceImage);
            imagedestroy($processedImage);
            
            if ($saved) {
                return [
                    'success' => true, 
                    'filename' => $filename . '.jpg',
                    'path' => $outputPath,
                    'size' => filesize($outputPath)
                ];
            } else {
                return ['success' => false, 'error' => 'Không lưu được hình ảnh đã xử lý'];
            }
            
        } catch (Exception $e) {
            error_log("Lỗi xử lý hình ảnh: " . $e->getMessage());
            return ['success' => false, 'error' => 'Xử lý hình ảnh không thành công'];
        }
    }

    private function validateUpload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Lỗi tải tệp lên: ' . $file['error']];
        }

        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'error' => 'Tệp quá lớn. Kích thước tối đa: 5MB'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'error' => 'Định dạng tệp không hợp lệ. Được phép: JPG, PNG, GIF, WebP'];
        }
        
        return ['success' => true];
    }
    
    private function createImageResource($filePath, $imageType) {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }

    private function resizeAndCrop($sourceImage, $sourceWidth, $sourceHeight) {

        $newImage = imagecreatetruecolor($this->targetWidth, $this->targetHeight);

        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);

        $size = min($sourceWidth, $sourceHeight);
        $x = ($sourceWidth - $size) / 2;
        $y = ($sourceHeight - $size) / 2;

        imagecopyresampled(
            $newImage, $sourceImage,
            0, 0, $x, $y,
            $this->targetWidth, $this->targetHeight,
            $size, $size
        );
        
        return $newImage;
    }
}

// Khởi tạo bộ xử lý hình ảnh
$imageProcessor = new ImageProcessor();

// Tạo thư mục tải lên nếu nó không tồn tại
$uploadDir = 'uploaded_img';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if(isset($_POST['add_product'])){
   $p_name = mysqli_real_escape_string($conn, $_POST['p_name']);
   $p_price = mysqli_real_escape_string($conn, $_POST['p_price']);
   
   // Xử lý tải lên hình ảnh với việc thay đổi kích thước
   if(isset($_FILES['p_image']) && $_FILES['p_image']['error'] === UPLOAD_ERR_OK) {
       $result = $imageProcessor->processUpload($_FILES['p_image'], $uploadDir);
       
       if($result['success']) {
           $p_image = $result['filename'];
           
           // Sử dụng câu lệnh đã chuẩn bị để bảo mật
           $stmt = mysqli_prepare($conn, "INSERT INTO `products`(name, price, image) VALUES(?, ?, ?)");
           mysqli_stmt_bind_param($stmt, "sss", $p_name, $p_price, $p_image);
           $insert_query = mysqli_stmt_execute($stmt);
           
           if($insert_query){
              $message[] = 'Sản phẩm thêm thành công (ảnh đã được resize thành 600x600px)';
           }else{
              $message[] = 'Không thể thêm sản phẩm';
              // Dọn dẹp tệp đã tải lên nếu việc chèn cơ sở dữ liệu không thành công
              unlink($result['path']);
           }
           mysqli_stmt_close($stmt);
       } else {
           $message[] = 'Lỗi upload ảnh: ' . $result['error'];
       }
   } else {
       $message[] = 'Vui lòng chọn ảnh sản phẩm';
   }
};

if(isset($_GET['delete'])){
   $delete_id = intval($_GET['delete']); 
   
   // Lấy tên tệp hình ảnh trước khi xóa
   $stmt = mysqli_prepare($conn, "SELECT image FROM `products` WHERE id = ?");
   mysqli_stmt_bind_param($stmt, "i", $delete_id);
   mysqli_stmt_execute($stmt);
   $result = mysqli_stmt_get_result($stmt);
   $product = mysqli_fetch_assoc($result);
   mysqli_stmt_close($stmt);
   
   // Xóa khỏi database
   $stmt = mysqli_prepare($conn, "DELETE FROM `products` WHERE id = ?");
   mysqli_stmt_bind_param($stmt, "i", $delete_id);
   $delete_query = mysqli_stmt_execute($stmt);
   mysqli_stmt_close($stmt);
   
   if($delete_query){
      // Xóa file ảnh
      if($product && file_exists($uploadDir . '/' . $product['image'])) {
          unlink($uploadDir . '/' . $product['image']);
      }
      header('location:admin.php');
      $message[] = 'Sản phẩm đã bị xóa';
   }else{
      header('location:admin.php');
      $message[] = 'Sản phẩm không thể bị xóa';
   };
};

if(isset($_POST['update_product'])){
   $update_p_id = intval($_POST['update_p_id']);
   $update_p_name = mysqli_real_escape_string($conn, $_POST['update_p_name']);
   $update_p_price = mysqli_real_escape_string($conn, $_POST['update_p_price']);
   
   // Nhận thông tin sản phẩm hiện tại
   $stmt = mysqli_prepare($conn, "SELECT image FROM `products` WHERE id = ?");
   mysqli_stmt_bind_param($stmt, "i", $update_p_id);
   mysqli_stmt_execute($stmt);
   $result = mysqli_stmt_get_result($stmt);
   $current_product = mysqli_fetch_assoc($result);
   mysqli_stmt_close($stmt);
   
   $update_p_image = $current_product['image']; // Giữ nguyên hình ảnh hiện tại theo mặc định
   
   // Xử lý hình ảnh mới nếu đã tải lên
   if(isset($_FILES['update_p_image']) && $_FILES['update_p_image']['error'] === UPLOAD_ERR_OK) {
       $result = $imageProcessor->processUpload($_FILES['update_p_image'], $uploadDir);
       
       if($result['success']) {
           $update_p_image = $result['filename'];
           
           // Xóa ảnh cũ
           if($current_product && file_exists($uploadDir . '/' . $current_product['image'])) {
               unlink($uploadDir . '/' . $current_product['image']);
           }
       } else {
           $message[] = 'Lỗi upload ảnh: ' . $result['error'];
           header('location:admin.php');
           exit;
       }
   }
   
   // Update database
   $stmt = mysqli_prepare($conn, "UPDATE `products` SET name = ?, price = ?, image = ? WHERE id = ?");
   mysqli_stmt_bind_param($stmt, "sssi", $update_p_name, $update_p_price, $update_p_image, $update_p_id);
   $update_query = mysqli_stmt_execute($stmt);
   mysqli_stmt_close($stmt);

   if($update_query){
      $message[] = 'Sản phẩm đã được cập nhật thành công (ảnh đã được resize thành 600x600px)';
      header('location:admin.php');
   }else{
      $message[] = 'Sản phẩm không thể được cập nhật';
      header('location:admin.php');
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shoping - Admin Quan Ly</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <link rel="stylesheet" href="css\style.css">
   
   <style>
   .upload-info {
       background: #f1f1f1;
       padding: 15px;
       margin: 10px 0;
       border-radius: 5px;
       border-left: 4px solid #282f37ff;
   }
   .upload-info h4 {
       margin: 0 0 10px 0;
       color: #007bff;
   }
   .upload-info ul {
       margin: 0;
       padding-left: 20px;
   }
   .upload-info li {
       margin: 5px 0;
   }
   .price-note {
       background: #e8f4fd;
       padding: 10px;
       margin: 10px 0;
       border-radius: 5px;
       border-left: 4px solid #007bff;
       color: #004085;
   }
   </style>

</head>
<body>
   
<?php

if(isset($message)){
   foreach($message as $message){
      echo '<div class="message"><span>'.$message.'</span> <i class="fas fa-times" onclick="this.parentElement.style.display = `none`;"></i> </div>';
   };
};

?>

<?php include 'header.php'; ?>

<div class="container">

<section>

<div class="upload-info">
   <h4><i class="fas fa-info-circle"></i> Thông tin Upload Ảnh</h4>
   <ul>
       <li><strong>Tự động resize:</strong> Tất cả ảnh sẽ được tự động resize thành 600x600 pixels</li>
       <li><strong>Định dạng hỗ trợ:</strong> JPG, PNG, GIF, WebP</li>
       <li><strong>Kích thước tối đa:</strong> 5MB</li>
       <li><strong>Chất lượng:</strong> Tối ưu hóa cho web (85% quality)</li>
   </ul>
</div>

<div class="price-note">
   <h4><i class="fas fa-money-bill"></i> Lưu ý về giá sản phẩm</h4>
   <p><strong>Nhập giá bằng VND:</strong> Giá sẽ được lưu và hiển thị trực tiếp bằng Việt Nam Đồng</p>
   <p><em>Ví dụ: Nhập 2500000 → Khách hàng thấy 2,500,000 ₫</em></p>
</div>

<form action="" method="post" class="add-product-form" enctype="multipart/form-data">
   <h3>Thêm sản phẩm mới</h3>
   <input type="text" name="p_name" placeholder="nhập tên sản phẩm" class="box" required>
   <input type="number" name="p_price" min="0" step="1000" placeholder="nhập giá sản phẩm (VND)" class="box" required>
   <input type="file" name="p_image" accept="image/png, image/jpg, image/jpeg, image/gif, image/webp" class="box" required>
   <small style="color: #666; display: block; margin-top: 5px;">
       <i class="fas fa-camera"></i> Ảnh sẽ được tự động resize thành 600x600px
   </small>
   <input type="submit" value="thêm sản phẩm" name="add_product" class="btn">
</form>

</section>

<section class="display-product-table">

   <table>

      <thead>
         <th>Ảnh sản phẩm</th>
         <th>Tên sản phẩm</th>
         <th>Giá sản phẩm</th>
         <th>Tình trạng</th>
      </thead>

      <tbody>
         <?php
         
            $select_products = mysqli_query($conn, "SELECT * FROM `products`");
            if(mysqli_num_rows($select_products) > 0){
               while($row = mysqli_fetch_assoc($select_products)){
         ?>

         <tr>
            <td>
                <img src="uploaded_img/<?php echo htmlspecialchars($row['image']); ?>" 
                     height="100" 
                     alt="<?php echo htmlspecialchars($row['name']); ?>"
                     style="object-fit: cover; border-radius: 5px;"
                     onerror="this.onerror=null;this.src='https://via.placeholder.com/100x100?text=No+Image';">
            </td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td>
                <?php echo formatVND($row['price']); ?>
            </td>
            <td>
               <a href="admin.php?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa mục này không?');"> <i class="fas fa-trash"></i> xóa </a>
               <a href="admin.php?edit=<?php echo $row['id']; ?>" class="option-btn"> <i class="fas fa-edit"></i> cập nhật </a>
            </td>
         </tr>

         <?php
            };    
            }else{
               echo "<div class='empty'>không có sản phẩm nào được thêm vào</div>";
            };
         ?>
      </tbody>
   </table>

</section>

<section class="edit-form-container">

   <?php
   
   if(isset($_GET['edit'])){
      $edit_id = intval($_GET['edit']);
      $stmt = mysqli_prepare($conn, "SELECT * FROM `products` WHERE id = ?");
      mysqli_stmt_bind_param($stmt, "i", $edit_id);
      mysqli_stmt_execute($stmt);
      $edit_query = mysqli_stmt_get_result($stmt);
      
      if(mysqli_num_rows($edit_query) > 0){
         while($fetch_edit = mysqli_fetch_assoc($edit_query)){
   ?>

   <form action="" method="post" enctype="multipart/form-data">
      <img src="uploaded_img/<?php echo htmlspecialchars($fetch_edit['image']); ?>" 
           height="200" 
           alt="<?php echo htmlspecialchars($fetch_edit['name']); ?>"
           style="object-fit: cover; border-radius: 10px; border: 2px solid #ddd;">
      <input type="hidden" name="update_p_id" value="<?php echo $fetch_edit['id']; ?>">
      <input type="text" class="box" required name="update_p_name" value="<?php echo htmlspecialchars($fetch_edit['name']); ?>">
      <input type="number" min="0" step="1000" class="box" required name="update_p_price" value="<?php echo $fetch_edit['price']; ?>" placeholder="Giá (VND)">
      <input type="file" class="box" name="update_p_image" accept="image/png, image/jpg, image/jpeg, image/gif, image/webp">
      <small style="color: #666; display: block; margin: 5px 0;">
          <i class="fas fa-info-circle"></i> Để trống nếu không muốn thay đổi ảnh. Ảnh mới sẽ được resize thành 600x600px
      </small>
      <input type="submit" value="cập nhật sản phẩm" name="update_product" class="btn">
      <input type="reset" value="Hủy" id="close-edit" class="option-btn">
   </form>

   <?php
            };
         };
         mysqli_stmt_close($stmt);
         echo "<script>document.querySelector('.edit-form-container').style.display = 'flex';</script>";
      };
   ?>

</section>

</div>

<script src="js/script.js"></script>

</body>
</html>