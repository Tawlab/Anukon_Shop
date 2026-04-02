<?php 
include_once '../includes/header.php'; 
if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit; }

$result = $conn->query("SELECT b.*, s.sp_name FROM bill_purchases b LEFT JOIN supplers s ON b.sp_id = s.sp_id ORDER BY b.purchases_id DESC");

$po_status = [
    0 => ['label' => 'ยกเลิก', 'bg' => 'var(--danger-soft)', 'color' => 'var(--danger)', 'icon' => 'fa-ban'],
    1 => ['label' => 'รอรับสินค้า', 'bg' => 'var(--warning-soft)', 'color' => '#b45309', 'icon' => 'fa-clock'],
    2 => ['label' => 'รับแล้ว', 'bg' => 'var(--success-soft)', 'color' => 'var(--success)', 'icon' => 'fa-circle-check'],
];
?>

<main class="page-content fade-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-truck-ramp-box text-primary me-2"></i>ใบสั่งซื้อ (PO)</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm" onclick="window.location.href='../api/export/export_data.php?type=po'">
                <i class="fa-solid fa-file-excel me-1"></i>Excel
            </button>
            <a href="purchase_create.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i>สร้าง PO
            </a>
        </div>
    </div>

    <div class="row g-3">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $cfg = $po_status[$row['purchase_status']] ?? $po_status[1];
                $po_no = 'PO-' . str_pad($row['purchases_id'], 4, '0', STR_PAD_LEFT);
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="fw-bold text-primary"><?php echo $po_no; ?></div>
                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($row['purchase_date'])); ?></small>
                        </div>
                        <span class="badge" style="background: <?php echo $cfg['bg']; ?>; color: <?php echo $cfg['color']; ?>;">
                            <i class="fa-solid <?php echo $cfg['icon']; ?> me-1"></i><?php echo $cfg['label']; ?>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="small"><i class="fa-solid fa-building text-muted me-1"></i><?php echo htmlspecialchars($row['sp_name'] ?? '-'); ?></div>
                        <div class="fw-bold fs-5">฿<?php echo number_format($row['total_cost'], 2); ?></div>
                    </div>

                    <div class="d-flex gap-2 mt-auto">
                        <?php if($row['purchase_status'] == 1): ?>
                            <a href="purchase_receive.php?id=<?php echo $row['purchases_id']; ?>" class="btn btn-sm btn-success flex-grow-1">
                                <i class="fa-solid fa-box-open me-1"></i>รับของ
                            </a>
                        <?php else: ?>
                            <a href="purchase_receive.php?id=<?php echo $row['purchases_id']; ?>" class="btn btn-sm btn-outline-secondary flex-grow-1">
                                <i class="fa-solid fa-eye me-1"></i>ดูรายละเอียด
                            </a>
                        <?php endif; ?>
                        <a href="print_po.php?id=<?php echo $row['purchases_id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-print"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card p-5 text-center text-muted">
                    <i class="fa-solid fa-file-invoice fa-3x mb-3 opacity-25"></i>
                    <p>ยังไม่มีใบสั่งซื้อ</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>
