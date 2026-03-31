<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

// ป้องกันไม่ให้ Customer แอบเข้าหน้านี้
if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('ไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href = 'dashboard.php';</script>";
    exit;
}

// ดึงข้อมูลบิลทั้งหมด เรียงจากใหม่ไปเก่า
$sql = "SELECT b.sale_id, b.sale_date, b.total_price, b.payment_type, b.sale_status, b.slip_img, 
               u.first_name, u.last_name 
        FROM bill_sales b 
        JOIN users u ON b.user_id = u.user_id 
        ORDER BY b.sale_date DESC";
$result = $conn->query($sql);
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>จัดการคำสั่งซื้อ (Admin)</h2>
            <button class="btn btn-success shadow-sm rounded-pill px-4" onclick="window.location.href='../api/order/export_excel.php'">
                <i class="fa-solid fa-file-excel me-2"></i>ส่งออก Excel
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>รหัสบิล</th>
                                <th>วันที่</th>
                                <th>ชื่อลูกค้า</th>
                                <th>ยอดรวม</th>
                                <th>การชำระเงิน</th>
                                <th>สถานะปัจจุบัน</th>
                                <th>อัปเดตสถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    // กำหนดสี Badge ตามสถานะ
                                    $badge_class = '';
                                    switch($row['sale_status']) {
                                        case 0: $badge_class = 'bg-danger'; break;
                                        case 1: $badge_class = 'bg-warning text-dark'; break;
                                        case 2: $badge_class = 'bg-info text-dark'; break;
                                        case 3: $badge_class = 'bg-success'; break;
                                    }
                                ?>
                                <tr>
                                    <td class="fw-bold text-primary">#ORD-<?php echo str_pad($row['sale_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="small text-muted"><?php echo date_format(date_create($row['sale_date']), "d M Y H:i"); ?></td>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td class="fw-bold text-dark">฿<?php echo number_format($row['total_price'], 2); ?></td>
                                    
                                    <td>
                                        <?php if($row['payment_type'] == 'COD'): ?>
                                            <span class="badge bg-light text-dark border">ปลายทาง</span>
                                        <?php else: ?>
                                            <a href="../uploads/slips/<?php echo $row['slip_img']; ?>" target="_blank" class="badge bg-primary text-white text-decoration-none shadow-sm">
                                                <i class="fa-solid fa-image me-1"></i> ดูสลิป
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?> px-2 py-1" id="status-badge-<?php echo $row['sale_id']; ?>">
                                            <?php 
                                                if($row['sale_status'] == 0) echo 'ยกเลิก';
                                                elseif($row['sale_status'] == 1) echo 'รอดำเนินการ';
                                                elseif($row['sale_status'] == 2) echo 'กำลังจัดส่ง';
                                                elseif($row['sale_status'] == 3) echo 'สำเร็จ';
                                            ?>
                                        </span>
                                    </td>
                                    
                                    <td style="min-width: 200px;">
                                        <div class="d-flex gap-2">
                                            <select class="form-select form-select-sm status-dropdown" data-id="<?php echo $row['sale_id']; ?>">
                                                <option value="1" <?php echo $row['sale_status'] == 1 ? 'selected' : ''; ?>>รอดำเนินการ</option>
                                                <option value="2" <?php echo $row['sale_status'] == 2 ? 'selected' : ''; ?>>กำลังจัดส่ง</option>
                                                <option value="3" <?php echo $row['sale_status'] == 3 ? 'selected' : ''; ?>>สำเร็จ</option>
                                                <option value="0" <?php echo $row['sale_status'] == 0 ? 'selected' : ''; ?>>ยกเลิก (Cancel)</option>
                                            </select>
                                            <a href="print_order.php?id=<?php echo $row['sale_id']; ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="พิมพ์ใบสั่งซื้อ">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">ยังไม่มีคำสั่งซื้อในระบบ</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</main>

<script>
$(document).ready(function() {
    // เมื่อ Admin เปลี่ยนค่าใน Dropdown
    $('.status-dropdown').change(function() {
        let dropdown = $(this);
        let sale_id = dropdown.data('id');
        let new_status = dropdown.val();

        if (new_status == 0) {
            Swal.fire({
                title: 'เหตุผลการยกเลิก',
                input: 'textarea',
                inputPlaceholder: 'ระบุเหตุผลที่ยกเลิกออเดอร์นี้...',
                showCancelButton: true,
                confirmButtonText: 'ยืนยันยกเลิก',
                cancelButtonText: 'กลับ',
                preConfirm: (reason) => {
                    if (!reason) {
                        Swal.showValidationMessage('กรุณาระบุเหตุผล');
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateStatus(sale_id, new_status, result.value);
                } else {
                    location.reload(); // Revert selection
                }
            });
        } else {
            updateStatus(sale_id, new_status, '');
        }
    });

    function updateStatus(sale_id, new_status, reason) {
        Swal.fire({
            title: 'กำลังอัปเดต...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '../api/order/update_status.php',
            type: 'POST',
            data: { sale_id: sale_id, new_status: new_status, reason: reason },
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'อัปเดตสำเร็จ!',
                        showConfirmButton: false,
                        timer: 1000
                    }).then(() => {
                        location.reload(); 
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    }
});
</script>

<?php include_once '../includes/footer.php'; ?>