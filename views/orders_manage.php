<?php 
include_once '../includes/header.php'; 
if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit; }

$sql = "SELECT b.sale_id, b.sale_date, b.total_price, b.payment_type, b.sale_status, b.slip_img, 
               u.first_name, u.last_name 
        FROM bill_sales b JOIN users u ON b.user_id = u.user_id 
        ORDER BY b.sale_date DESC";
$result = $conn->query($sql);

$status_config = [
    0 => ['label' => 'ยกเลิก', 'bg' => 'var(--danger-soft)', 'color' => 'var(--danger)', 'icon' => 'fa-ban'],
    1 => ['label' => 'รอดำเนินการ', 'bg' => 'var(--warning-soft)', 'color' => '#b45309', 'icon' => 'fa-clock'],
    2 => ['label' => 'กำลังจัดส่ง', 'bg' => 'var(--info-soft)', 'color' => 'var(--info)', 'icon' => 'fa-truck-fast'],
    3 => ['label' => 'สำเร็จ', 'bg' => 'var(--success-soft)', 'color' => 'var(--success)', 'icon' => 'fa-circle-check'],
];
?>

<main class="page-content fade-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-clipboard-list text-primary me-2"></i>คำสั่งซื้อ</h5>
        <button class="btn btn-outline-success btn-sm" onclick="window.location.href='../api/order/export_excel.php'">
            <i class="fa-solid fa-file-excel me-1"></i>Excel
        </button>
    </div>

    <div class="row g-3">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $cfg = $status_config[$row['sale_status']] ?? $status_config[1];
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="fw-bold text-primary">#ORD-<?php echo str_pad($row['sale_id'], 4, '0', STR_PAD_LEFT); ?></div>
                            <small class="text-muted"><?php echo date_format(date_create($row['sale_date']), "d/m/Y H:i"); ?></small>
                        </div>
                        <span class="badge" style="background: <?php echo $cfg['bg']; ?>; color: <?php echo $cfg['color']; ?>;">
                            <i class="fa-solid <?php echo $cfg['icon']; ?> me-1"></i><?php echo $cfg['label']; ?>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="small">
                            <i class="fa-solid fa-user text-muted me-1"></i><?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                        </div>
                        <div class="fw-bold fs-5">฿<?php echo number_format($row['total_price'], 2); ?></div>
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <?php if($row['payment_type'] == 'COD'): ?>
                            <span class="badge bg-light text-dark"><i class="fa-solid fa-money-bill me-1"></i>เงินสด</span>
                        <?php else: ?>
                            <a href="../uploads/slips/<?php echo $row['slip_img']; ?>" target="_blank" class="badge bg-primary text-decoration-none text-white">
                                <i class="fa-solid fa-image me-1"></i>ดูสลิป
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2 mt-auto">
                        <select class="form-select form-select-sm flex-grow-1 status-dropdown" data-id="<?php echo $row['sale_id']; ?>">
                            <option value="1" <?php echo $row['sale_status'] == 1 ? 'selected' : ''; ?>>รอดำเนินการ</option>
                            <option value="2" <?php echo $row['sale_status'] == 2 ? 'selected' : ''; ?>>กำลังจัดส่ง</option>
                            <option value="3" <?php echo $row['sale_status'] == 3 ? 'selected' : ''; ?>>สำเร็จ</option>
                            <option value="0" <?php echo $row['sale_status'] == 0 ? 'selected' : ''; ?>>ยกเลิก</option>
                        </select>
                        <a href="print_order.php?id=<?php echo $row['sale_id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="พิมพ์">
                            <i class="fa-solid fa-print"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card p-5 text-center text-muted">
                    <i class="fa-solid fa-clipboard fa-3x mb-3 opacity-25"></i>
                    <p>ยังไม่มีคำสั่งซื้อ</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
$(document).ready(function() {
    $('.status-dropdown').change(function() {
        let dropdown = $(this);
        let sale_id = dropdown.data('id');
        let new_status = dropdown.val();

        if (new_status == 0) {
            Swal.fire({
                title: 'เหตุผลยกเลิก', input: 'textarea', inputPlaceholder: 'ระบุเหตุผล...',
                showCancelButton: true, confirmButtonText: 'ยืนยัน', cancelButtonText: 'กลับ', confirmButtonColor: '#ef4444',
                preConfirm: (reason) => { if (!reason) Swal.showValidationMessage('กรุณาระบุเหตุผล'); return reason; }
            }).then(r => { r.isConfirmed ? updateStatus(sale_id, new_status, r.value) : location.reload(); });
        } else {
            updateStatus(sale_id, new_status, '');
        }
    });

    function updateStatus(sale_id, new_status, reason) {
        Swal.fire({ title: 'กำลังอัปเดต...', didOpen: () => Swal.showLoading() });
        $.post('../api/order/update_status.php', { sale_id, new_status, reason }, function(res) {
            res.status === 'success' ? Swal.fire({ icon: 'success', title: 'อัปเดตแล้ว!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
        }, 'json');
    }
});
</script>

<?php include_once '../includes/footer.php'; ?>