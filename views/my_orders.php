<?php 
include_once '../includes/header.php'; 
if ($_SESSION['role'] !== 'customer') { header("Location: dashboard.php"); exit; }

$uid = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM bill_sales WHERE user_id = $uid ORDER BY sale_date DESC");

$status_config = [
    0 => ['label' => 'ยกเลิก', 'bg' => 'var(--danger-soft)', 'color' => 'var(--danger)', 'icon' => 'fa-ban'],
    1 => ['label' => 'รอดำเนินการ', 'bg' => 'var(--warning-soft)', 'color' => '#b45309', 'icon' => 'fa-clock'],
    2 => ['label' => 'กำลังจัดส่ง', 'bg' => 'var(--info-soft)', 'color' => 'var(--info)', 'icon' => 'fa-truck-fast'],
    3 => ['label' => 'สำเร็จ', 'bg' => 'var(--success-soft)', 'color' => 'var(--success)', 'icon' => 'fa-circle-check'],
];
?>

<main class="page-content fade-up">
    <h5 class="fw-bold mb-4"><i class="fa-solid fa-receipt text-primary me-2"></i>ประวัติการสั่งซื้อ</h5>

    <?php if ($result->num_rows > 0): ?>
    <div class="row g-3">
        <?php while($row = $result->fetch_assoc()): 
            $cfg = $status_config[$row['sale_status']] ?? $status_config[1];
        ?>
        <div class="col-12 col-md-6">
            <a href="order_detail.php?id=<?php echo $row['sale_id']; ?>" class="card p-4 h-100 text-decoration-none">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-bold text-primary">#ORD-<?php echo str_pad($row['sale_id'], 4, '0', STR_PAD_LEFT); ?></div>
                        <small class="text-muted"><?php echo date_format(date_create($row['sale_date']), "d/m/Y H:i"); ?></small>
                    </div>
                    <span class="badge" style="background: <?php echo $cfg['bg']; ?>; color: <?php echo $cfg['color']; ?>;">
                        <i class="fa-solid <?php echo $cfg['icon']; ?> me-1"></i><?php echo $cfg['label']; ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-auto">
                    <span class="badge bg-light text-dark">
                        <i class="fa-solid fa-<?php echo $row['payment_type'] == 'COD' ? 'money-bill' : 'qrcode'; ?> me-1"></i>
                        <?php echo $row['payment_type'] == 'COD' ? 'เงินสด' : 'โอนเงิน'; ?>
                    </span>
                    <span class="fw-bold fs-5 text-dark">฿<?php echo number_format($row['total_price'], 2); ?></span>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="card p-5 text-center text-muted">
        <i class="fa-solid fa-receipt fa-3x mb-3 opacity-25"></i>
        <p class="mb-3">ยังไม่มีประวัติการสั่งซื้อ</p>
        <a href="shop.php" class="btn btn-primary">เลือกซื้อสินค้า</a>
    </div>
    <?php endif; ?>
</main>

<?php include_once '../includes/footer.php'; ?>