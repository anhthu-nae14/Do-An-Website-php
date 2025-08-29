
class PhoneStore {
    constructor() {
        this.init();
        this.bindEvents();
        this.setupIntersectionObserver();
    }

    init() {
        // Khởi tạo thành phần
        this.searchTimeout = null;
        this.loadingIndicator = document.getElementById('loadingIndicator');
        this.productsGrid = document.getElementById('productsGrid');
        this.searchInput = document.getElementById('searchInput');
        this.sortSelect = document.getElementById('sortSelect');
        this.productCount = document.getElementById('productCount');
        
        // Đặt số lượng sản phẩm ban đầu
        this.updateProductCount();
        
        // Khởi tạo thông báo
        this.setupNotifications();
        
        // Thiết lập cuộn mượt mà
        this.setupSmoothScrolling();
    }

    bindEvents() {
        // Search functionality
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.debounceSearch();
            });
        }

        // Sort functionality
        if (this.sortSelect) {
            this.sortSelect.addEventListener('change', () => {
                this.performSearch();
            });
        }

        // Add to cart with animation
        document.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart-btn')) {
                this.handleAddToCart(e);
            }
        });

        // Quick view functionality
        document.addEventListener('click', (e) => {
            if (e.target.closest('.quick-view-btn')) {
                this.handleQuickView(e);
            }
        });

        // Modal functionality
        this.setupModalEvents();

        // Header scroll effect
        window.addEventListener('scroll', () => {
            this.handleHeaderScroll();
        });

        // Mobile menu
        this.setupMobileMenu();

        // Phím tắt
        this.setupKeyboardShortcuts();
    }

    // Chức năng tìm kiếm với debouncing
    debounceSearch() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.performSearch();
        }, 300);
    }

    async performSearch() {
        if (!this.searchInput || !this.productsGrid) return;

        const searchTerm = this.searchInput.value.trim();
        const sortBy = this.sortSelect ? this.sortSelect.value : 'newest';
        
        this.showLoading(true);
        
        try {
            const response = await fetch(`?ajax_search=1&search=${encodeURIComponent(searchTerm)}&sort=${sortBy}`);
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const data = await response.text();
            
            // Làm mới nội dung cũ
            this.productsGrid.style.opacity = '0';
            this.productsGrid.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                this.productsGrid.innerHTML = data;
                this.updateProductCount();
                
                // Hoạt hình trong nội dung mới
                this.productsGrid.style.opacity = '1';
                this.productsGrid.style.transform = 'translateY(0)';
                
                // Kích hoạt lại hoạt ảnh nhập cảnh cho sản phẩm mới
                this.animateProducts();
                
                this.showLoading(false);
            }, 200);
            
        } catch (error) {
            console.error('Search error:', error);
            this.showNotification('Có lỗi xảy ra khi tìm kiếm. Vui lòng thử lại.', 'error');
            this.showLoading(false);
        }
    }

    showLoading(show) {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = show ? 'block' : 'none';
        }
    }

    updateProductCount() {
        if (!this.productsGrid || !this.productCount) return;
        
        const products = this.productsGrid.querySelectorAll('.product-card');
        const count = products.length;
        
        this.productCount.textContent = `${count} sản phẩm`;
        
        this.productCount.style.transform = 'scale(1.1)';
        setTimeout(() => {
            this.productCount.style.transform = 'scale(1)';
        }, 200);
    }

   // Thêm vào giỏ hàng với UX nâng cao
    async handleAddToCart(e) {
        e.preventDefault();
        
        const button = e.target.closest('.add-to-cart-btn');
        const form = button.closest('form');
        
        // Visual feedback
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thêm...';
        button.disabled = true;
        
        try {
            // Submit form data
            const formData = new FormData(form);
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                button.innerHTML = '<i class="fas fa-check"></i> Đã thêm!';
                button.style.backgroundColor = 'var(--success-color)';

                this.updateCartCount();

                this.showNotification('Đã thêm sản phẩm vào giỏ hàng!', 'success');

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                    button.disabled = false;
                }, 2000);
                
            } else {
                throw new Error('Failed to add to cart');
            }
            
        } catch (error) {
            console.error('Add to cart error:', error);
            button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi!';
            button.style.backgroundColor = 'var(--error-color)';
            
            this.showNotification('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.backgroundColor = '';
                button.disabled = false;
            }, 2000);
        }
    }

    // Quick view modal
    handleQuickView(e) {
        const productCard = e.target.closest('.product-card');
        const img = productCard.querySelector('img');
        const name = productCard.querySelector('.product-name').textContent;
        const price = productCard.querySelector('.product-price').textContent;
        
        const modal = document.getElementById('quickViewModal');
        
        if (modal) {
            document.getElementById('modalImage').src = img.src;
            document.getElementById('modalImage').alt = name;
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalPrice').textContent = price;
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Bẫy tập trung cho khả năng truy cập
            this.trapFocus(modal);
        }
    }

    setupModalEvents() {
        const modal = document.getElementById('quickViewModal');
        const closeBtn = document.querySelector('.close');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }
        
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            });
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && modal.style.display === 'block') {
                this.closeModal();
            }
        });
    }

    closeModal() {
        const modal = document.getElementById('quickViewModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // Header scroll effect
    handleHeaderScroll() {
        const header = document.getElementById('header');
        if (header) {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
    }

    // Chức năng menu di động
    setupMobileMenu() {
        const menuBtn = document.getElementById('menu-btn');
        const navbar = document.getElementById('navbar');
        
        if (menuBtn && navbar) {
            menuBtn.addEventListener('click', () => {
                navbar.classList.toggle('active');
                menuBtn.classList.toggle('fa-bars');
                menuBtn.classList.toggle('fa-times');
                
                // Animate menu items
                if (navbar.classList.contains('active')) {
                    this.animateMenuItems();
                }
            });
            
            // Đóng menu khi nhấp ra bên ngoài
            document.addEventListener('click', (e) => {
                if (!navbar.contains(e.target) && !menuBtn.contains(e.target)) {
                    navbar.classList.remove('active');
                    menuBtn.classList.add('fa-bars');
                    menuBtn.classList.remove('fa-times');
                }
            });
        }
    }

    animateMenuItems() {
        const menuItems = document.querySelectorAll('.navbar a');
        menuItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                item.style.transition = 'all 0.3s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, index * 100);
        });
    }

    // Làm sản phẩm chuyển động khi tải
    animateProducts() {
        const products = document.querySelectorAll('.product-card');
        products.forEach((product, index) => {
            product.style.opacity = '0';
            product.style.transform = 'translateY(20px)';
            product.style.animationDelay = `${index * 0.1}s`;
            
            setTimeout(() => {
                product.style.transition = 'all 0.6s ease';
                product.style.opacity = '1';
                product.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Thiết lập trình quan sát giao lộ để thực hiện
    setupIntersectionObserver() {
        const options = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, options);
        
        // Quan sát thẻ sản phẩm
        document.querySelectorAll('.product-card').forEach(card => {
            observer.observe(card);
        });
    }

    // Hệ thống thông báo
    setupNotifications() {
        if (!document.getElementById('notificationContainer')) {
            const container = document.createElement('div');
            container.id = 'notificationContainer';
            container.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
            document.body.appendChild(container);
        }
    }

    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notificationContainer');
        if (!container) return;
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--${type === 'success' ? 'success' : type === 'error' ? 'error' : 'primary'}-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-lg);
            max-width: 350px;
            animation: slideInRight 0.3s ease;
            cursor: pointer;
        `;
        
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <i class="fas fa-times" style="margin-left: auto; opacity: 0.5;"></i>
            </div>
        `;
        
        container.appendChild(notification);
        
        // Auto remove
        setTimeout(() => {
            this.removeNotification(notification);
        }, duration);
        
        // Manual close
        notification.addEventListener('click', () => {
            this.removeNotification(notification);
        });
    }

    removeNotification(notification) {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    // Update cart count
    async updateCartCount() {
        try {
            const response = await fetch('api/cart_count.php');
            if (response.ok) {
                const data = await response.json();
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.count;
                    cartCount.classList.add('bounce');
                    setTimeout(() => {
                        cartCount.classList.remove('bounce');
                    }, 600);
                }
            }
        } catch (error) {
            console.error('Failed to update cart count:', error);
        }
    }

    // Keyboard shortcuts
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K để tìm kiếm tiêu điểm
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (this.searchInput) {
                    this.searchInput.focus();
                }
            }
            
            // Thoát để xóa tìm kiếm
            if (e.key === 'Escape' && this.searchInput === document.activeElement) {
                this.searchInput.value = '';
                this.performSearch();
                this.searchInput.blur();
            }
        });
    }

    // Smooth scrolling
    setupSmoothScrolling() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Bẫy tập trung cho khả năng truy cập
    trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];
        
        element.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            }
        });
        
        firstFocusable.focus();
    }
}

// Hoạt ảnh CSS cho thông báo
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
    
    .notification:hover {
        transform: translateX(-5px);
        transition: transform 0.2s ease;
    }
`;
document.head.appendChild(style);

// Khởi tạo khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
    new PhoneStore();
});

// Xuất để sử dụng cho mô-đun tiềm năng
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PhoneStore;
}