<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$res_shop = $conn->query("SELECT setting_value FROM store_settings WHERE setting_key = 'shop_name'");
$shop_name = ($res_shop && $res_shop->num_rows > 0) ? $res_shop->fetch_assoc()['setting_value'] : 'Anukon Shop';

// Notifications
$notifications = [];
$noti_count = 0;

if ($_SESSION['role'] === 'admin') {
    // Admin: Pending Orders
    $res = $conn->query("SELECT COUNT(*) as c FROM bill_sales WHERE sale_status = 1");
    if ($res && $row = $res->fetch_assoc()) {
        if ($row['c'] > 0) {
            $notifications[] = ['icon' => 'fa-box-open', 'color' => 'warning', 'text' => "มีออเดอร์ใหม่รอตรวจสอบ {$row['c']} รายการ", 'link' => 'orders_manage.php'];
            $noti_count += $row['c'];
        }
    }
    // Admin: Low/Out of Stock
    $res = $conn->query("SELECT p.prod_name, s.total_qty FROM stocks s JOIN products p ON s.prod_id = p.prod_id WHERE s.total_qty <= 5 LIMIT 5");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if ($row['total_qty'] <= 0) {
                $notifications[] = ['icon' => 'fa-triangle-exclamation', 'color' => 'danger', 'text' => "สินค้าหมด: {$row['prod_name']}", 'link' => 'inventory.php'];
            } else {
                $notifications[] = ['icon' => 'fa-arrow-trend-down', 'color' => 'warning', 'text' => "สินค้าใกล้หมด ({$row['total_qty']} ชิ้น): {$row['prod_name']}", 'link' => 'inventory.php'];
            }
            $noti_count++;
        }
    }
} else if ($_SESSION['role'] === 'customer') {
    $uid = $_SESSION['user_id'];
    // Cart count for customer
    $cart_sql = "SELECT SUM(quantity) as total_qty FROM cart_items WHERE user_id = ?";
    $stmt_cart = $conn->prepare($cart_sql);
    $stmt_cart->bind_param("i", $uid);
    $stmt_cart->execute();
    $cart_res = $stmt_cart->get_result()->fetch_assoc();
    $cart_count = $cart_res['total_qty'] ?? 0;
    $stmt_cart->close();

    // Customer: Order Status
    $res = $conn->query("SELECT sale_id, sale_status FROM bill_sales WHERE user_id = $uid ORDER BY sale_id DESC LIMIT 5");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $status_text = '';
            $icon = '';
            $color = '';
            switch ($row['sale_status']) {
                case 1:
                    $status_text = "รอตรวจสอบ/ชำระเงิน";
                    $icon = "fa-clock";
                    $color = "warning";
                    break;
                case 2:
                    $status_text = "กำลังจัดเตรียมสินค้า";
                    $icon = "fa-box";
                    $color = "info";
                    break;
                case 3:
                    $status_text = "จัดส่งเรียบร้อยแล้ว";
                    $icon = "fa-check-circle";
                    $color = "success";
                    break;
                case 4:
                    $status_text = "ชำระเงินเรียบร้อย (รับเอง)";
                    $icon = "fa-store";
                    $color = "primary";
                    break;
                case 0:
                    $status_text = "ยกเลิกคำสั่งซื้อแล้ว";
                    $icon = "fa-xmark";
                    $color = "danger";
                    break;
            }
            $notifications[] = ['icon' => $icon, 'color' => $color, 'text' => "ออเดอร์ #{$row['sale_id']} {$status_text}", 'link' => "order_detail.php?id={$row['sale_id']}"];
            $noti_count++;
        }
    }
}

// Auto cleanup slips older than 3 days (10% chance)
if (rand(1, 10) === 1) {
    $slip_dir = __DIR__ . '/../uploads/slips/';
    if (is_dir($slip_dir)) {
        $files = glob($slip_dir . '*.*');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) >= 3 * 24 * 60 * 60)) {
                @unlink($file);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anukon Shop | ระบบจัดการร้านค้า</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="icon" href="../assets/img/shop.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #eef2ff;
            --primary-soft: #c7d2fe;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --bg: #f1f5f9;
            --text: #1e293b;
            --text-muted: #94a3b8;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --success: #22c55e;
            --success-soft: #dcfce7;
            --danger: #ef4444;
            --danger-soft: #fee2e2;
            --warning: #f59e0b;
            --warning-soft: #fef3c7;
            --info: #3b82f6;
            --info-soft: #dbeafe;
            --radius: 16px;
            --radius-sm: 10px;
            --radius-xs: 6px;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.03);
            --transition: all 0.2s ease;
        }

        /* SweetAlert2 Theme */
        div:where(.swal2-container) button:where(.swal2-styled).swal2-confirm {
            background-color: var(--primary) !important;
            border-radius: var(--radius-sm) !important;
            font-family: 'Noto Sans Thai', sans-serif !important;
        }

        div:where(.swal2-container) button:where(.swal2-styled).swal2-cancel {
            border-radius: var(--radius-sm) !important;
            font-family: 'Noto Sans Thai', sans-serif !important;
        }

        div:where(.swal2-container) div:where(.swal2-popup) {
            border-radius: var(--radius) !important;
            font-family: 'Noto Sans Thai', sans-serif !important;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: var(--bg);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            color: var(--text);
            font-size: 0.925rem;
            line-height: 1.6;
        }

        /* Bootstrap Overrides */
        .text-primary {
            color: var(--primary) !important;
        }

        .bg-primary {
            background-color: var(--primary) !important;
        }

        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            border-radius: var(--radius-sm) !important;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover) !important;
            border-color: var(--primary-hover) !important;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline-primary {
            color: var(--primary) !important;
            border-color: var(--primary) !important;
            border-radius: var(--radius-sm) !important;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary) !important;
            color: #fff !important;
        }

        .btn {
            border-radius: var(--radius-sm) !important;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .card {
            border: 1px solid var(--border);
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow);
            background: var(--surface);
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .form-control,
        .form-select {
            border-radius: var(--radius-sm) !important;
            border: 1px solid var(--border);
            padding: 10px 14px;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-soft);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .badge {
            font-weight: 500;
            padding: 5px 10px;
            border-radius: var(--radius-xs);
            font-size: 0.78rem;
        }

        .table {
            font-size: 0.9rem;
        }

        .table th {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--border);
        }

        .table td {
            vertical-align: middle;
            color: var(--text);
            border-color: var(--border);
        }

        .table-hover tbody tr:hover {
            background-color: var(--primary-light);
        }

        .modal-content {
            border: none;
            border-radius: var(--radius) !important;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            border-radius: var(--radius) var(--radius) 0 0 !important;
            border-bottom: none;
        }

        /* Layout */
        #wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        #content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background-color: var(--bg);
        }

        /* Top Navbar */
        .top-navbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            height: 64px;
            min-height: 64px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            width: 100%;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .nav-brand {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        .btn-nav {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-secondary);
            transition: var(--transition);
            position: relative;
        }

        .btn-nav:hover {
            background-color: var(--primary-light);
            color: var(--primary);
            border-color: var(--primary-soft);
        }

        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--danger);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            border: 2px solid var(--surface);
        }

        .user-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px 6px 6px;
            border-radius: 24px;
            border: 1px solid var(--border);
            background: var(--surface);
            cursor: pointer;
            transition: var(--transition);
        }

        .user-dropdown-btn:hover {
            background: var(--primary-light);
            border-color: var(--primary-soft);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-soft);
        }

        .user-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text);
        }

        /* Page container */
        .page-content {
            flex: 1;
            padding: 24px;
        }

        @media (max-width: 768px) {
            .page-content {
                padding: 16px;
            }
        }

        /* Page Header */
        .page-header {
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title .icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        /* Animations */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-up {
            animation: fadeUp 0.35s ease-out;
        }
    </style>
</head>

<body>

    <div id="wrapper">
        <?php include_once __DIR__ . '/sidebar.php'; ?>
        <div id="content-wrapper">

            <header class="top-navbar d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-light d-lg-none btn-nav" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#sidebarOffcanvas">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <!-- <span class="nav-brand d-none d-sm-block">
                        <i class="fa-solid fa-store me-1"></i>
                        <?php echo htmlspecialchars($shop_name); ?>
                    </span> -->
                </div>

                <div class="d-flex align-items-center gap-3">

                    <!-- Notifications Dropdown -->
                    <div class="dropdown">
                        <button class="btn-nav dropdown-toggle border-0" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false" style="background:none;">
                            <i class="fa-solid fa-bell"></i>
                            <?php if ($noti_count > 0): ?>
                                <span class="cart-badge bg-danger"
                                    style="right:0; top:0;"><?php echo $noti_count > 9 ? '9+' : $noti_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-0"
                            style="border-radius: var(--radius); min-width: 320px; max-height: 400px; overflow-y: auto;">
                            <li class="p-3 border-bottom bg-light"
                                style="border-radius: var(--radius) var(--radius) 0 0;">
                                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-bell me-2"></i>การแจ้งเตือน
                                </h6>
                            </li>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach ($notifications as $noti): ?>
                                    <li>
                                        <a class="dropdown-item p-3 border-bottom text-wrap" href="<?php echo $noti['link']; ?>"
                                            style="font-size:0.85rem; line-height:1.4;">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="bg-<?php echo $noti['color']; ?>-soft text-<?php echo $noti['color']; ?> rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width: 36px; height: 36px; flex-shrink: 0;">
                                                    <i class="fa-solid <?php echo $noti['icon']; ?>"></i>
                                                </div>
                                                <div><?php echo $noti['text']; ?></div>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="p-4 text-center text-muted small">ไม่มีการแจ้งเตือนใหม่</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php if ($_SESSION['role'] === 'customer'): ?>
                        <a href="cart.php" class="btn-nav text-decoration-none border-0" style="background:none;">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <?php if (isset($cart_count) && $cart_count > 0): ?>
                                <span class="cart-badge bg-primary"
                                    style="right:0; top:0;"><?php echo $cart_count > 99 ? '99+' : $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="user-dropdown-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <img src="../assets/img/default-profile.png" alt="" class="user-avatar">
                            <span
                                class="user-name d-none d-sm-inline"><?php echo $_SESSION['full_name'] ?? 'ผู้ใช้'; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2"
                            style="border-radius: var(--radius); min-width: 200px;">
                            <li>
                                <a class="dropdown-item py-2 rounded-3" href="profile.php">
                                    <i class="fa-solid fa-circle-user me-2 text-primary"></i>โปรไฟล์
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider my-1">
                            </li>
                            <li>
                                <a class="dropdown-item py-2 rounded-3 text-danger" href="../actions/logout.php">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>ออกจากระบบ
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>
            </header>