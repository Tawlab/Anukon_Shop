<nav id="sidebar" class="d-none d-lg-block border-end bg-white shadow-sm" style="width: 280px; min-height: 100vh; position: sticky; top: 0; z-index: 1000;">
    <div class="sidebar-header p-4 text-center border-bottom">
        <h4 class="fw-bold text-primary mb-0"><i class="fa-solid fa-bag-shopping me-2"></i>V-SHOP</h4>
        <small class="text-muted">ระบบจัดการร้านค้า</small>
    </div>
    <div class="p-3 sidebar-scroll">
        <?php renderMenu($_SESSION['role']); ?>
    </div>
</nav>

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold text-primary" id="sidebarOffcanvasLabel">
            <i class="fa-solid fa-bag-shopping me-2"></i>V-SHOP MENU
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3 sidebar-scroll">
        <?php renderMenu($_SESSION['role']); ?>
    </div>
</div>

<?php
// ฟังก์ชันสำหรับสร้างเมนูตามสิทธิ์ผู้ใช้
function renderMenu($role) {
    // ดึงชื่อไฟล์ของหน้าที่กำลังเปิดอยู่
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // ฟังก์ชันย่อยสำหรับเช็คสถานะ Active
    $isActive = function($page) use ($current_page) {
        return ($current_page == $page) ? 'active bg-primary text-white shadow-sm' : 'text-dark hover-bg-light';
    };

    echo '<ul class="nav nav-pills flex-column mb-auto gap-1">';
    
    // ================= เมนูหลัก (มีทุกสิทธิ์) =================
    echo '<li class="nav-item">
            <a href="dashboard.php" class="nav-link py-3 ' . $isActive('dashboard.php') . '">
                <i class="fa-solid fa-house fa-fw me-3"></i>หน้าแรก
            </a>
          </li>';

    if ($role == 'admin') {
        // ================= เมนูสำหรับ Admin =================
        echo '<hr class="my-3 text-muted opacity-25">';
        echo '<small class="text-muted fw-bold px-3 mb-2 text-uppercase" style="font-size: 0.75rem;">ระบบขายหน้าร้าน (POS)</small>';
        
        echo '<li class="nav-item">
                <a href="pos.php" class="nav-link py-3 ' . $isActive('pos.php') . '">
                    <i class="fa-solid fa-cash-register fa-fw me-3"></i>เครื่องคิดเงิน (POS)
                </a>
              </li>';
              
        echo '<hr class="my-3 text-muted opacity-25">';
        echo '<small class="text-muted fw-bold px-3 mb-2 text-uppercase" style="font-size: 0.75rem;">ระบบจัดการร้านค้า</small>';
        
        echo '<li class="nav-item">
                <a href="orders_manage.php" class="nav-link py-3 ' . $isActive('orders_manage.php') . '">
                    <i class="fa-solid fa-list-check fa-fw me-3"></i>จัดการคำสั่งซื้อ
                </a>
              </li>';
        echo '<li class="nav-item">
                <a href="inventory.php" class="nav-link py-3 ' . $isActive('inventory.php') . '">
                    <i class="fa-solid fa-boxes-stacked fa-fw me-3"></i>คลังสินค้า (สต็อก)
                </a>
              </li>';
        echo '<li class="nav-item">
                <a href="categories.php" class="nav-link py-3 ' . $isActive('categories.php') . '">
                    <i class="fa-solid fa-tags fa-fw me-3"></i>จัดการหมวดหมู่
                </a>
              </li>';
        echo '<li class="nav-item">
                <a href="suppliers.php" class="nav-link py-3 ' . $isActive('suppliers.php') . '">
                    <i class="fa-solid fa-handshake fa-fw me-3"></i>จัดการซัพพลายเออร์
                </a>
              </li>';
        echo '<li class="nav-item">
                <a href="purchases.php" class="nav-link py-3 ' . $isActive('purchases.php') . '">
                    <i class="fa-solid fa-truck-ramp-box fa-fw me-3"></i>รับเข้าสินค้า (PO)
                </a>
              </li>';
              
        echo '<hr class="my-3 text-muted opacity-25">';
        echo '<small class="text-muted fw-bold px-3 mb-2 text-uppercase" style="font-size: 0.75rem;">จัดการผู้ใช้งาน</small>';
        
        echo '<li class="nav-item">
                <a href="users_manage.php" class="nav-link py-3 ' . $isActive('users_manage.php') . '">
                    <i class="fa-solid fa-users-gear fa-fw me-3"></i>ข้อมูลสมาชิกลูกค้า
                </a>
              </li>';
    } else {
        // ================= เมนูสำหรับลูกค้า =================
        echo '<hr class="my-3 text-muted opacity-25">';
        echo '<small class="text-muted fw-bold px-3 mb-2 text-uppercase" style="font-size: 0.75rem;">บริการของเรา</small>';

        echo '<li class="nav-item">
                <a href="shop.php" class="nav-link py-3 ' . $isActive('shop.php') . '">
                    <i class="fa-solid fa-store fa-fw me-3"></i>เลือกซื้อสินค้า
                </a>
              </li>';
        echo '<li class="nav-item">
                <a href="cart.php" class="nav-link py-3 ' . $isActive('cart.php') . '">
                    <i class="fa-solid fa-cart-shopping fa-fw me-3"></i>ตะกร้าสินค้า
                </a>
              </li>';
        echo '<li class="nav-item">
                <a href="my_orders.php" class="nav-link py-3 ' . $isActive('my_orders.php') . '">
                    <i class="fa-solid fa-receipt fa-fw me-3"></i>ประวัติการสั่งซื้อ
                </a>
              </li>';
    }

    // ================= เมนูส่วนตัว (มีทุกสิทธิ์) =================
    echo '<hr class="my-3 text-muted opacity-25">';
    echo '<small class="text-muted fw-bold px-3 mb-2 text-uppercase" style="font-size: 0.75rem;">บัญชีของฉัน</small>';

    echo '<li class="nav-item">
            <a href="profile.php" class="nav-link py-3 ' . $isActive('profile.php') . '">
                <i class="fa-solid fa-user-circle fa-fw me-3"></i>ข้อมูลส่วนตัว
            </a>
          </li>';
    
    echo '<li class="nav-item mt-4">
            <a href="../actions/logout.php" class="nav-link text-danger py-3 border border-danger border-opacity-25 bg-danger bg-opacity-10">
                <i class="fa-solid fa-right-from-bracket fa-fw me-3"></i>ออกจากระบบ
            </a>
          </li>';
    echo '</ul>';
}
?>

<style>
    /* สไตล์การตกแต่ง Sidebar */
    .nav-link {
        border-radius: 12px;
        transition: all 0.2s ease-in-out;
        font-weight: 500;
        font-size: 0.95rem;
    }
    .hover-bg-light:hover {
        background-color: #f8f9fa;
        color: var(--primary-blue) !important;
        transform: translateX(5px); /* เลื่อนขวาเล็กน้อยเมื่อ Hover */
    }
    .nav-link.active {
        box-shadow: 0 4px 10px rgba(0, 86, 179, 0.2);
    }
    .nav-link.active i {
        color: white !important; /* ให้ไอคอนเป็นสีขาวเมื่อปุ่ม Active */
    }
    .nav-link.text-danger:hover {
        background-color: #dc3545 !important;
        color: white !important;
    }
    .nav-link.text-danger:hover i {
        color: white !important;
    }
    
    /* Scrollbar สำหรับ Sidebar เผื่อเมนูยาวเกินจอ */
    .sidebar-scroll {
        max-height: calc(100vh - 80px); /* หักความสูงของ Header ออก */
        overflow-y: auto;
    }
    .sidebar-scroll::-webkit-scrollbar {
        width: 4px;
    }
    .sidebar-scroll::-webkit-scrollbar-thumb {
        background-color: #e9ecef;
        border-radius: 10px;
    }
    .sidebar-scroll:hover::-webkit-scrollbar-thumb {
        background-color: #ced4da;
    }
</style>