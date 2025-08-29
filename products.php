<?php
@include 'config/config.php';      
session_start();
include 'middleware.php';

function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

// Xử lý các yêu cầu tìm kiếm AJAX
if (isset($_GET['ajax_search'])) {
   $search_term = mysqli_real_escape_string($conn, $_GET['search']);
   $category = $_GET['category'] ?? '';
   $category = mysqli_real_escape_string($conn, $category);

   if (!empty($category)) {
      $query .= " AND category = '$category'";
   }

    $sort_by = mysqli_real_escape_string($conn, $_GET['sort']);
    
    $query = "SELECT * FROM `products` WHERE 1=1";
    
   if (!empty($search_term)) {
        $query .= " AND name LIKE '%$search_term%'";
   }
    
    // Thêm sắp xếp
    switch($sort_by) {
        case 'price_asc':
            $query .= " ORDER BY price ASC";
            break;
        case 'price_desc':
            $query .= " ORDER BY price DESC";
            break;
        case 'name_asc':
            $query .= " ORDER BY name ASC";
            break;
        case 'name_desc':
            $query .= " ORDER BY name DESC";
            break;
        default:
            $query .= " ORDER BY id DESC";
    }
    
    $select_products = mysqli_query($conn, "SELECT * FROM `products` ORDER BY id DESC");
    
    if(mysqli_num_rows($select_products) > 0) {
        while($fetch_product = mysqli_fetch_assoc($select_products)) {
         if (empty($fetch_product['image'])) continue;
            echo '<form action="" method="post" class="product-form">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="uploaded_img/' . htmlspecialchars($fetch_product['image']) . '" 
                                 alt="' . htmlspecialchars($fetch_product['name']) . '"
                                 loading="lazy">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name">' . htmlspecialchars($fetch_product['name']) . '</h3>
                            <div class="product-price">' . formatVND($fetch_product['price']) . '</div>
                            <input type="hidden" name="product_name" value="' . htmlspecialchars($fetch_product['name']) . '">
                            <input type="hidden" name="product_price" value="' . $fetch_product['price'] . '">
                            <input type="hidden" name="product_image" value="' . htmlspecialchars($fetch_product['image']) . '">
                            <button type="submit" class="add-to-cart-btn" name="add_to_cart">
                                <i class="fas fa-shopping-cart"></i>
                                Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                  </form>';
        }
    } else {
        echo '<div class="no-products">
                <i class="fas fa-search"></i>
                <h3>Không tìm thấy sản phẩm</h3>
                <p>Thử tìm kiếm với từ khóa khác</p>
              </div>';
    }
    exit;
}

if (isset($_POST['add_to_cart'])) {
    require_user();
    $user_id = $_SESSION['user']['id'];
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_price = mysqli_real_escape_string($conn, $_POST['product_price']);
    $product_image = mysqli_real_escape_string($conn, $_POST['product_image']);
    $product_quantity = 1;

    // Sử dụng câu lệnh đã chuẩn bị để bảo mật
    $stmt = mysqli_prepare($conn, "SELECT * FROM cart WHERE name = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $product_name, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0) {
        $message[] = 'Sản phẩm đã có trong giỏ hàng';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO cart(name, price, image, quantity, user_id) VALUES(?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssisi", $product_name, $product_price, $product_image, $product_quantity, $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $message[] = 'Đã thêm sản phẩm vào giỏ hàng';
        } else {
            $message[] = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm - Phone Store</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css\style.css">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="css\style.css" as="style">
</head>
<body>

<?php
if(isset($message)) {
    foreach($message as $msg) {
        echo '<div class="message">
                <span>' . htmlspecialchars($msg) . '</span> 
                <i class="fas fa-times" onclick="this.parentElement.style.display = `none`;"></i> 
              </div>';
    }
}
?>

<?php include 'header.php'; ?>

<main class="main-content">
    <div class="container">
        
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1>Khám phá sản phẩm mới nhất</h1>
                <p>Những chiếc điện thoại tốt nhất với công nghệ tiên tiến</p>
            </div>
        </section>

        <!-- Search and Filter Section -->
        <section class="search-section">
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Tìm kiếm sản phẩm...">
                </div>
                
                <div class="filter-controls">
                    <select id="sortSelect" class="sort-select">
                        <option value="newest">Mới nhất</option>
                        <option value="price_asc">Giá: Thấp đến cao</option>
                        <option value="price_desc">Giá: Cao đến thấp</option>
                        <option value="name_asc">Tên: A-Z</option>
                        <option value="name_desc">Tên: Z-A</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section class="products-section">
            <div class="products-header">
                <h2>Sản phẩm nổi bật</h2>
                <div class="products-count">
                    <span id="productCount">Đang tải...</span>
                </div>
            </div>

            <!-- Loading indicator -->
            <div id="loadingIndicator" class="loading-indicator" style="display: none;">
                <div class="spinner"></div>
                <p>Đang tải sản phẩm...</p>
            </div>

            <!-- Products Grid -->
            <div class="products-grid" id="productsGrid">
                <?php
                $select_products = mysqli_query($conn, "SELECT * FROM `products` ORDER BY id DESC");
                $product_count = mysqli_num_rows($select_products);
                
                if($product_count > 0) {
                    while($fetch_product = mysqli_fetch_assoc($select_products)) {
                ?>
                <form action="" method="post" class="product-form">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="uploaded_img/<?php echo htmlspecialchars($fetch_product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($fetch_product['name']); ?>"
                                 loading="lazy">
                            <div class="product-overlay">
                                <button type="button" class="quick-view-btn">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($fetch_product['name']); ?></h3>
                            <div class="product-price"><?php echo formatVND($fetch_product['price']); ?></div>
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($fetch_product['name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $fetch_product['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($fetch_product['image']); ?>">
                            <button type="submit" class="add-to-cart-btn" name="add_to_cart">
                                <i class="fas fa-shopping-cart"></i>
                                Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                </form>
                <?php
                    }
                } else {
                    echo '<div class="no-products">
                            <i class="fas fa-box-open"></i>
                            <h3>Chưa có sản phẩm nào</h3>
                            <p>Vui lòng quay lại sau</p>
                          </div>';
                }
                ?>
            </div>
        </section>
    </div>
</main>

<!-- Quick View Modal -->
<div id="quickViewModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-body">
            <div class="product-details">
                <img id="modalImage" src="" alt="">
                <div class="product-info">
                    <h3 id="modalName"></h3>
                    <p id="modalPrice"></p>
                    <button class="add-to-cart-btn">Thêm vào giỏ hàng</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chức năng tìm kiếm
let searchTimeout;
const searchInput = document.getElementById('searchInput');
const sortSelect = document.getElementById('sortSelect');
const productsGrid = document.getElementById('productsGrid');
const loadingIndicator = document.getElementById('loadingIndicator');
const productCount = document.getElementById('productCount');

// Cập nhật số lượng sản phẩm
function updateProductCount() {
    const products = productsGrid.querySelectorAll('.product-card');
    productCount.textContent = `${products.length} sản phẩm`;
}

// Số đếm ban đầu
updateProductCount();

// Hàm tìm kiếm
function performSearch() {
    const searchTerm = searchInput.value.trim();
    const sortBy = sortSelect.value;
    
    loadingIndicator.style.display = 'block';
    productsGrid.style.opacity = '0.5';
    
    fetch(`?ajax_search=1&search=${encodeURIComponent(searchTerm)}&sort=${sortBy}`)
        .then(response => response.text())
        .then(data => {
            productsGrid.innerHTML = data;
            updateProductCount();
            loadingIndicator.style.display = 'none';
            productsGrid.style.opacity = '1';
        })
        .catch(error => {
            console.error('Search error:', error);
            loadingIndicator.style.display = 'none';
            productsGrid.style.opacity = '1';
        });
}

// Tìm kiếm trả về
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(performSearch, 300);
});

// Sắp xếp thay đổi
sortSelect.addEventListener('change', performSearch);

// Chức năng chế độ xem nhanh
const modal = document.getElementById('quickViewModal');
const closeModal = document.querySelector('.close');

document.addEventListener('click', function(e) {
    if (e.target.closest('.quick-view-btn')) {
        const productCard = e.target.closest('.product-card');
        const img = productCard.querySelector('img');
        const name = productCard.querySelector('.product-name').textContent;
        const price = productCard.querySelector('.product-price').textContent;
        
        document.getElementById('modalImage').src = img.src;
        document.getElementById('modalName').textContent = name;
        document.getElementById('modalPrice').textContent = price;
        
        modal.style.display = 'block';
    }
});

closeModal.addEventListener('click', function() {
    modal.style.display = 'none';
});

window.addEventListener('click', function(e) {
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});

// Hoạt ảnh mượt mà cho thẻ sản phẩm
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.product-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>

</body>
</html>