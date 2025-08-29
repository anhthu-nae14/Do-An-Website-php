# 📱 Do An Shopping – Website bán điện thoại (PHP + MySQL)

Dự án đồ án môn học: xây dựng website bán điện thoại với các chức năng quản trị sản phẩm, giỏ hàng – đặt hàng, đăng nhập/đăng ký và phân quyền **admin / user**. Dự án triển khai local bằng **XAMPP** (Apache + PHP + MySQL) và quản trị DB qua **phpMyAdmin**.

---

## 🔧 Công nghệ & Kiến trúc
- **Backend:** PHP (PDO), kiến trúc MVC đơn giản
- **Frontend:** HTML5, CSS3, JavaScript (jQuery/Fetch)
- **Database:** MySQL (quản trị bằng phpMyAdmin)
- **Server local:** XAMPP (Apache + MySQL)
- **Công cụ:** Git/GitHub, VSCode

---

## ✨ Tính năng chính
- **Auth & Phân quyền:** Đăng ký/đăng nhập, phân quyền **admin / user**, bảo mật mật khẩu (bcrypt).
- **Quản lý sản phẩm:** CRUD sản phẩm, danh mục, hình ảnh; tìm kiếm, phân trang.
- **Giỏ hàng & Đặt hàng:** Thêm/sửa/xóa giỏ hàng, đặt hàng → tạo order.
- **Quản trị (admin):** Dashboard, quản lý users (trực tiếp tại phpmyadmin), đơn hàng, tồn kho cơ bản.
- **Bảo mật:** Prepared Statements (PDO), CSRF token (form quan trọng), lọc file upload.
- **Khác:** Upload ảnh, log hoạt động cơ bản (middleware.php).


