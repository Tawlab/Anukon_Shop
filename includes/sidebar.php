<?php
function renderMenu($role) {
    $current_page = basename($_SERVER['PHP_SELF']);
    
    $isActive = function($page) use ($current_page) {
        return ($current_page == $page) ? 'active' : '';
    };

    echo '<ul class="sidebar-menu">';
    
    // Dashboard - ทุก role
    echo '<li><a href="dashboard.php" class="menu-item ' . $isActive('dashboard.php') . '">
            <span class="menu-icon"><i class="fa-solid fa-house"></i></span>
            <span>หน้าแรก</span>
          </a></li>';

    if ($role == 'admin') {
        // POS
        echo '<li class="menu-section">ขายสินค้า</li>';
        echo '<li><a href="pos.php" class="menu-item ' . $isActive('pos.php') . '">
                <span class="menu-icon bg-success-soft text-success"><i class="fa-solid fa-cash-register"></i></span>
                <span>POS เครื่องคิดเงิน</span>
              </a></li>';
        
        // จัดการร้าน
        echo '<li class="menu-section">จัดการร้านค้า</li>';
        echo '<li><a href="sales_summary.php" class="menu-item ' . $isActive('sales_summary.php') . '">
                <span class="menu-icon bg-primary-soft text-primary"><i class="fa-solid fa-chart-pie"></i></span>
                <span>สรุปการขาย</span>
              </a></li>';
        echo '<li><a href="orders_manage.php" class="menu-item ' . $isActive('orders_manage.php') . '">
                <span class="menu-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                <span>คำสั่งซื้อ</span>
              </a></li>';
        echo '<li><a href="inventory.php" class="menu-item ' . $isActive('inventory.php') . '">
                <span class="menu-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                <span>คลังสินค้า</span>
              </a></li>';
        echo '<li><a href="categories.php" class="menu-item ' . $isActive('categories.php') . '">
                <span class="menu-icon"><i class="fa-solid fa-tags"></i></span>
                <span>หมวดหมู่</span>
              </a></li>';
        echo '<li><a href="suppliers.php" class="menu-item ' . $isActive('suppliers.php') . '">
                <span class="menu-icon"><i class="fa-solid fa-handshake"></i></span>
                <span>ซัพพลายเออร์</span>
              </a></li>';
        echo '<li><a href="purchases.php" class="menu-item ' . $isActive('purchases.php') . '">
                <span class="menu-icon"><i class="fa-solid fa-truck-ramp-box"></i></span>
                <span>รับเข้า PO</span>
              </a></li>';
        echo '<li><a href="expenses.php" class="menu-item ' . $isActive('expenses.php') . '">
                <span class="menu-icon"><i class="fa-solid fa-wallet"></i></span>
                <span>ค่าใช้จ่าย</span>
              </a></li>';
              
        echo '<li class="menu-section">ผู้ใช้งาน</li>';
        echo '<li><a href="users_manage.php" class="menu-item ' . $isActive('users_manage.php') . '">
                <span class="menu-icon"><i class="fa-solid fa-users-gear"></i></span>
                <span>จัดการสมาชิก</span>
              </a></li>';
    } else {
        // Customer menu
        echo '<li class="menu-section">บริการ</li>';
        echo '<li><a href="shop.php" class="menu-item ' . $isActive('shop.php') . '">
                <span class="menu-icon bg-success-soft text-success"><i class="fa-solid fa-bag-shopping"></i></span>
                <span>เลือกซื้อสินค้า</span>
              </a></li>';
        echo '<li><a href="cart.php" class="menu-item ' . $isActive('cart.php') . '">
                <span class="menu-icon"><i class="fa-solid fa-cart-shopping"></i></span>
                <span>ตะกร้า</span>
              </a></li>';
        echo '<li><a href="my_orders.php" class="menu-item ' . $isActive('my_orders.php') . '">
                <span class="menu-icon"><i class="fa-solid fa-receipt"></i></span>
                <span>ประวัติสั่งซื้อ</span>
              </a></li>';
    }

    // Personal
    echo '<li class="menu-section">บัญชี</li>';
    echo '<li><a href="profile.php" class="menu-item ' . $isActive('profile.php') . '">
            <span class="menu-icon"><i class="fa-solid fa-circle-user"></i></span>
            <span>โปรไฟล์</span>
          </a></li>';
    
    echo '<li class="mt-3"><a href="../actions/logout.php" class="menu-item menu-logout">
            <span class="menu-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
            <span>ออกจากระบบ</span>
          </a></li>';
    echo '</ul>';
}
?>

<!-- Desktop Sidebar -->
<nav id="sidebar" class="d-none d-lg-flex flex-column">
    <div class="sidebar-brand">
        <i class="fa-solid fa-store"></i>
        <div>
            <div class="brand-name">Anukon Shop</div>
            <div class="brand-sub">ร้านค้าออนไลน์</div>
        </div>
    </div>
    <div class="sidebar-body">
        <?php renderMenu($_SESSION['role']); ?>
    </div>
</nav>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" style="width: 280px;">
    <div class="offcanvas-header border-bottom py-3">
        <h6 class="offcanvas-title fw-bold text-primary mb-0">
            <i class="fa-solid fa-store me-2"></i>Anukon Shop
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php renderMenu($_SESSION['role']); ?>
    </div>
</div>

<style>
    /* Sidebar Design */
    #sidebar {
        width: 260px;
        min-height: 100vh;
        background: var(--surface);
        border-right: 1px solid var(--border);
        position: sticky;
        top: 0;
        z-index: 1000;
        overflow-y: auto;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border);
        color: var(--primary);
        font-size: 1.25rem;
    }
    .brand-name {
        font-weight: 700;
        font-size: 1rem;
        line-height: 1.2;
        color: var(--text);
    }
    .brand-sub {
        font-size: 0.72rem;
        color: var(--text-muted);
        font-weight: 400;
    }

    .sidebar-body {
        padding: 8px 12px;
        flex: 1;
        overflow-y: auto;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .menu-section {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        padding: 16px 12px 6px;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: var(--radius-sm);
        text-decoration: none;
        color: var(--text-secondary);
        font-weight: 500;
        font-size: 0.88rem;
        transition: var(--transition);
        margin-bottom: 2px;
    }
    .menu-item:hover {
        background: var(--primary-light);
        color: var(--primary);
    }
    .menu-item.active {
        background: var(--primary);
        color: #fff;
        box-shadow: 0 2px 8px rgba(99,102,241,0.25);
    }
    .menu-item.active .menu-icon {
        background: rgba(255,255,255,0.2) !important;
        color: #fff !important;
    }

    .menu-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        background: var(--surface-alt);
        color: var(--text-secondary);
        flex-shrink: 0;
        transition: var(--transition);
    }
    .bg-success-soft { background: var(--success-soft) !important; }
    .text-success { color: var(--success) !important; }

    .menu-logout {
        color: var(--danger) !important;
    }
    .menu-logout:hover {
        background: var(--danger-soft) !important;
        color: var(--danger) !important;
    }
    .menu-logout .menu-icon {
        background: var(--danger-soft);
        color: var(--danger);
    }

    /* Offcanvas override */
    .offcanvas-body .sidebar-menu { padding: 8px 12px; }
</style>