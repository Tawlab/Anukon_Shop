<?php
include_once '../includes/header.php';

if ($_SESSION['role'] !== 'customer') {
    header("Location: dashboard.php");
    exit;
}

$cats = $conn->query("SELECT * FROM prod_types WHERE type_status = 1 ORDER BY type_name ASC");
?>

<style>
    /* Modern Minimalist Product Cards */
    .product-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(0, 0, 0, 0.04);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .product-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(99, 102, 241, 0.1);
        border-color: rgba(99, 102, 241, 0.15);
    }

    .product-img-wrapper {
        position: relative;
        padding-top: 100%;
        /* 1:1 Aspect Ratio */
        overflow: hidden;
        background: #f8fafc;
        border-bottom: 1px solid rgba(0, 0, 0, 0.02);
    }

    .product-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .product-card:hover .product-img {
        transform: scale(1.08);
    }

    .product-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 2;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        backdrop-filter: blur(4px);
    }

    .product-info {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .product-title {
        font-size: 1.05rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-price-row {
        margin-top: auto;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .product-price {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
    }

    .product-stock {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
    }

    .btn-add-cart {
        background: #f1f5f9;
        color: var(--primary);
        border: none;
        border-radius: 12px;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: all 0.2s;
        cursor: pointer;
    }

    .btn-add-cart:hover {
        background: var(--primary);
        color: #fff;
        transform: scale(1.05);
    }

    .btn-add-cart:active {
        transform: scale(0.95);
    }

    .btn-add-cart:disabled {
        background: #e2e8f0;
        color: #94a3b8;
        cursor: not-allowed;
        transform: none;
    }

    /* Category Filter Chips */
    .cat-chips {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }

    .cat-chip {
        padding: 8px 20px;
        border-radius: 50px;
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-weight: 500;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .cat-chip:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .cat-chip.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    /* Search Bar */
    .search-wrapper {
        position: relative;
        max-width: 320px;
        width: 100%;
    }

    .search-wrapper input {
        width: 100%;
        padding: 12px 20px 12px 48px;
        border-radius: 50px;
        border: 1px solid #e2e8f0;
        background: #fff;
        font-size: 0.95rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
    }

    .search-wrapper input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.15);
    }

    .search-wrapper i {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
</style>

<main class="page-content fade-up" style="max-width: 1200px; margin: 0 auto;">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1e293b;">เลือกซื้อสินค้า</h4>
            <p class="text-muted small mb-0">ค้นหาและเลือกซื้อสินค้าคุณภาพจากเรา</p>
        </div>
        <div class="search-wrapper">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchProduct" placeholder="ค้นหาสินค้า...">
        </div>
    </div>

    <!-- Category Chips -->
    <div class="cat-chips" id="categoryChips">
        <div class="cat-chip active" data-id="">ทั้งหมด</div>
        <?php while ($cat = $cats->fetch_assoc()): ?>
            <div class="cat-chip" data-id="<?php echo $cat['type_id']; ?>">
                <?php echo htmlspecialchars($cat['type_name']); ?>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5 d-none">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem; opacity: 0.5;" role="status"></div>
        <p class="text-muted mt-3 fw-medium">กำลังโหลดสินค้า...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-5 d-none">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
            style="width: 100px; height: 100px; background: #f1f5f9; color: #94a3b8; font-size: 3rem; margin-bottom: 20px;">
            <i class="fa-solid fa-box-open"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2">ไม่พบสินค้าที่คุณค้นหา</h5>
        <p class="text-muted">ลองค้นหาด้วยคำอื่น หรือเลือกหมวดหมู่ที่ต่างออกไป</p>
        <button class="btn btn-primary rounded-pill px-4 mt-2" onclick="resetFilters()">รีเซ็ตการค้นหา</button>
    </div>

    <!-- Products Grid -->
    <div class="row g-4" id="productsGrid">
        <!-- Products injected via JS -->
    </div>

</main>

<script>
    let currentCategory = '';
    let searchTimeout;

    $(document).ready(function () {
        loadProducts();

        // Search input with debounce
        $('#searchProduct').on('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadProducts, 400);
        });

        // Category chips click
        $('.cat-chip').click(function () {
            $('.cat-chip').removeClass('active');
            $(this).addClass('active');
            currentCategory = $(this).data('id');
            loadProducts();
        });
    });

    function resetFilters() {
        $('#searchProduct').val('');
        $('.cat-chip').removeClass('active');
        $('.cat-chip[data-id=""]').addClass('active');
        currentCategory = '';
        loadProducts();
    }

    function loadProducts() {
        let search = $('#searchProduct').val();

        $('#productsGrid').empty();
        $('#emptyState').addClass('d-none');
        $('#loadingState').removeClass('d-none');

        $.ajax({
            url: '../api/products/get_products.php',
            type: 'GET',
            data: { search: search, category: currentCategory },
            dataType: 'json',
            success: function (res) {
                $('#loadingState').addClass('d-none');

                if (res.status === 'success' && res.products.length > 0) {
                    let html = '';
                    res.products.forEach(p => {
                        let imgSrc = p.img ? '../uploads/products/' + p.img : 'https://placehold.co/100x100?text=No+Image';

                        let badgeColor = p.stock_qty > 10 ? 'rgba(34, 197, 94, 0.9)' :
                            (p.stock_qty > 0 ? 'rgba(245, 158, 11, 0.9)' : 'rgba(239, 68, 68, 0.9)');
                        let badgeText = p.stock_qty > 0 ? 'พร้อมส่ง' : 'สินค้าหมด';
                        let badgeStyle = `background: ${badgeColor}; color: white;`;

                        let stockText = p.stock_qty > 0 ? `คงเหลือ: ${p.stock_qty}` : `<span class="text-danger">Out of stock</span>`;

                        html += `
                    <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                        <div class="product-card">
                            <div class="product-badge shadow-sm" style="${badgeStyle}">
                                ${badgeText}
                            </div>
                            <div class="product-img-wrapper">
                                <img src="${imgSrc}" class="product-img" alt="${p.prod_name}" onerror="this.src='https://placehold.co/100x100?text=No+Image'">
                            </div>
                            <div class="product-info">
                                <div class="product-title">${p.prod_name}</div>
                                <div class="product-price-row mt-auto">
                                    <div>
                                        <div class="product-price">฿${parseFloat(p.price).toLocaleString()}</div>
                                        <div class="product-stock mt-1">${stockText}</div>
                                    </div>
                                    <button class="btn-add-cart shadow-sm" 
                                            onclick="addToCart(${p.prod_id})" 
                                            ${p.stock_qty <= 0 ? 'disabled' : ''} 
                                            title="เพิ่มลงตะกร้า">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                    });
                    $('#productsGrid').html(html);
                } else {
                    $('#emptyState').removeClass('d-none');
                }
            },
            error: function () {
                $('#loadingState').addClass('d-none');
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    }

    function addToCart(prodId) {
        $.post('../api/cart/add_to_cart.php', { prod_id: prodId, qty: 1 }, function (res) {
            if (res.status === 'success') {
                // SweetAlert notification in bottom right
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    background: '#334155',
                    color: '#fff',
                    iconColor: '#22c55e'
                });
                Toast.fire({
                    icon: 'success',
                    title: 'เพิ่มลงตะกร้าแล้ว'
                });

                // Update cart badge visually
                let badge = document.querySelector('.cart-badge.bg-primary');
                if (badge) {
                    let current = parseInt(badge.textContent) || 0;
                    badge.textContent = current + 1;
                    badge.style.transform = 'scale(1.5)';
                    setTimeout(() => badge.style.transform = 'translate(50%, -50%)', 200);
                } else {
                    let cartNav = document.querySelector('a[href="cart.php"].btn-nav');
                    if (cartNav) {
                        cartNav.insertAdjacentHTML('beforeend', '<span class="cart-badge bg-primary" style="right:0; top:0;">1</span>');
                    }
                }
            } else {
                Swal.fire({ icon: 'error', title: 'สั่งซื้อไม่ได้', text: res.message, confirmButtonColor: '#6366f1' });
            }
        }, 'json');
    }
</script>

<?php include_once '../includes/footer.php'; ?>