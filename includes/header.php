<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db_config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือยัง ถ้ายังให้เด้งไปหน้า Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. คำนวณจำนวนสินค้าในตะกร้า (เฉพาะ Customer)
$cart_count = 0;
if ($_SESSION['role'] === 'customer') {
    $uid = $_SESSION['user_id'];
    // หาผลรวมของจำนวนชิ้นสินค้า (quantity) ในตะกร้าของ user นี้
    $cart_sql = "SELECT SUM(quantity) as total_qty FROM cart_items WHERE user_id = ?";
    $stmt_cart = $conn->prepare($cart_sql);
    $stmt_cart->bind_param("i", $uid);
    $stmt_cart->execute();
    $cart_res = $stmt_cart->get_result()->fetch_assoc();
    $cart_count = $cart_res['total_qty'] ?? 0;
    $stmt_cart->close();
}

// 2. ระบบกำจัดสลิปเก่าอัตโนมัติ (ลบสลิปที่อายุเกิน 3 วัน)
if (rand(1, 10) === 1) { // 10% chance per page load to avoid slowing down every request
    $slip_dir = __DIR__ . '/../uploads/slips/';
    if (is_dir($slip_dir)) {
        $files = glob($slip_dir . '*.*');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 3 * 24 * 60 * 60) {
                    @unlink($file);
                }
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
    <title>V-SHOP | ระบบจัดการร้านค้า</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --primary-blue: #4f46e5; /* Backwards compatibility */
            --soft-gray: #f8f9fa;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        /* Custom SweetAlert2 Theme */
        div:where(.swal2-container) button:where(.swal2-styled).swal2-confirm {
            background-color: var(--primary-color) !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3) !important;
        }
        div:where(.swal2-container) button:where(.swal2-styled).swal2-cancel {
            border-radius: 8px !important;
        }
        div:where(.swal2-container) div:where(.swal2-popup) {
            border-radius: 16px !important;
            padding: 2em !important;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Override Bootstrap Primary */
        .text-primary { color: var(--primary-color) !important; }
        .bg-primary { background-color: var(--primary-color) !important; }
        .btn-primary { 
            background-color: var(--primary-color) !important; 
            border-color: var(--primary-color) !important; 
        }
        .btn-primary:hover { 
            background-color: var(--primary-hover) !important; 
            border-color: var(--primary-hover) !important; 
        }

        #wrapper {
            display: flex;
            /* กำหนดให้ Sidebar และ Content วางเรียงข้างกัน */
            width: 100%;
            min-height: 100vh;
        }

        #content-wrapper {
            flex: 1;
            /* ให้เนื้อหาขยายเต็มพื้นที่ที่เหลือจาก Sidebar */
            display: flex;
            flex-direction: column;
            min-width: 0;
            /* สำคัญมาก: ป้องกัน Flex item ดันเนื้อหาล้นจอ */
            background-color: #f0f2f5;
        }

        .top-navbar {
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: 70px;
            min-height: 70px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            width: 100%;
        }

        .btn-circle-nav {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .btn-circle-nav:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <div id="wrapper">
        <?php include_once __DIR__ . '/sidebar.php'; ?>
        <div id="content-wrapper">

            <header class="top-navbar d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">
                    <button class="btn btn-light d-lg-none me-3 btn-circle-nav border shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                        <i class="fa-solid fa-bars text-dark"></i>
                    </button>
                    <h5 class="fw-bold text-primary mb-0 d-none d-sm-block">V-SHOP</h5>
                </div>

                <div class="d-flex align-items-center">

                    <?php if ($_SESSION['role'] === 'customer'): ?>
                        <a href="cart.php" class="btn btn-light bg-white border shadow-sm btn-circle-nav position-relative me-3 text-decoration-none">
                            <i class="fa-solid fa-cart-shopping text-primary fs-5"></i>

                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light shadow-sm" style="font-size: 0.7rem; padding: 4px 6px;">
                                    <?php echo $cart_count > 99 ? '99+' : $cart_count; ?>
                                    <span class="visually-hidden">สินค้าในตะกร้า</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn btn-light bg-white border shadow-sm rounded-pill px-3 py-2 dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <img src="../assets/img/default-profile.png" alt="Profile" class="rounded-circle me-2 border" width="28" height="28">
                            <span class="fw-bold text-dark small me-1 d-none d-sm-inline"><?php echo $_SESSION['full_name'] ?? 'ผู้ใช้งาน'; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                            <li><a class="dropdown-item py-2" href="profile.php"><i class="fa-solid fa-user-gear me-2 text-secondary"></i>โปรไฟล์ของฉัน</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item py-2 text-danger" href="../actions/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>ออกจากระบบ</a></li>
                        </ul>
                    </div>

                </div>
            </header>