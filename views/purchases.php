<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// 1. ดึงข้อมูลใบสั่งซื้อ (PO) ทั้งหมด
$sql = "SELECT b.*, s.sp_name 
        FROM bill_purchases b 
        LEFT JOIN supplers s ON b.sp_id = s.sp_id
        ORDER BY b.purchases_id DESC";
$result = $conn->query($sql);
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-truck-ramp-box text-primary me-2"></i>จัดการสั่งซื้อ / รับเข้า (PO)</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-success shadow-sm rounded-pill px-3" onclick="window.location.href='../api/export/export_data.php?type=po'">
                    <i class="fa-solid fa-file-excel me-1"></i>ส่งออก Excel
                </button>
                <a href="purchase_create.php" class="btn btn-primary rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-plus me-1"></i> สร้างใบสั่งซื้อใหม่ (PO)
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-primary fw-bold">#PO Number</th>
                                <th>วันที่สั่ง</th>
                                <th>ซัพพลายเออร์</th>
                                <th>ยอดรวมบิล (฿)</th>
                                <th>สถานะ</th>
                                <th class="text-center">ตรวจสอบ / รับของ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold">PO-<?php echo str_pad($row['purchases_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="small text-muted"><?php echo date('d M Y', strtotime($row['purchase_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['sp_name'] ?? 'ไม่ระบุ'); ?></td>
                                    <td class="text-dark fw-bold">฿<?php echo number_format($row['total_cost'], 2); ?></td>
                                    <td>
                                        <?php if($row['purchase_status'] == 0): ?>
                                            <span class="badge bg-danger px-2"><i class="fa-solid fa-xmark me-1"></i>ยกเลิกแล้ว</span>
                                        <?php elseif($row['purchase_status'] == 1): ?>
                                            <span class="badge bg-warning text-dark px-2"><i class="fa-solid fa-clock me-1"></i>รอรับสินค้า</span>
                                        <?php elseif($row['purchase_status'] == 2): ?>
                                            <span class="badge bg-success px-2"><i class="fa-solid fa-check-circle me-1"></i>ได้รับของครบแล้ว</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($row['purchase_status'] == 1): ?>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="purchase_receive.php?id=<?php echo $row['purchases_id']; ?>" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" title="รับสินค้าเข้าคลัง">
                                                    <i class="fa-solid fa-box-open me-1"></i> รับของ
                                                </a>
                                                <a href="print_po.php?id=<?php echo $row['purchases_id']; ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill" title="พิมพ์ใบสั่งซื้อ">
                                                    <i class="fa-solid fa-print"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="purchase_receive.php?id=<?php echo $row['purchases_id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="ดูรายละเอียด">
                                                    <i class="fa-solid fa-eye me-1"></i> ดูรายละเอียด
                                                </a>
                                                <a href="print_po.php?id=<?php echo $row['purchases_id']; ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill" title="พิมพ์ใบสั่งซื้อ">
                                                    <i class="fa-solid fa-print"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-file-invoice fa-3x mb-3 opacity-25 d-block"></i>ยังไม่มีประวัติใบสั่งซื้อ PO
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include_once '../includes/footer.php'; ?>
