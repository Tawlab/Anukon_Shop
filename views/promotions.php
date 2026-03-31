<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

// Admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// ดึงโปรโมชั่นทั้งหมด
$sql = "SELECT * FROM promotions ORDER BY is_active DESC, end_date DESC";
$res_promo = $conn->query($sql);
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-ticket text-warning me-2"></i>จัดการคูปอง/โปรโมชั่น</h2>
            <button class="btn btn-warning rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addPromoModal">
                <i class="fa-solid fa-plus me-1"></i> เพิ่มโค้ดส่วนลดใหม่
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>รหัสโค้ด (Code)</th>
                                <th>ส่วนลด (%)</th>
                                <th>วันเริ่ม</th>
                                <th>วันสิ้นสุด</th>
                                <th>วันคงเหลือ</th>
                                <th>สถานะ</th>
                                <th class="text-center">ตรวจสอบ/ลบ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res_promo->num_rows > 0): ?>
                                <?php 
                                $now = new DateTime();
                                while($row = $res_promo->fetch_assoc()): 
                                    $end_date = new DateTime($row['end_date']);
                                    $diff = $now->diff($end_date);
                                    
                                    $is_expired = ($now > $end_date);
                                    
                                    $status_badge = '';
                                    if ($row['is_active'] == 0) {
                                        $status_badge = '<span class="badge bg-secondary">ปิดใช้งาน</span>';
                                    } elseif ($is_expired) {
                                        $status_badge = '<span class="badge bg-danger">หมดอายุ</span>';
                                    } else {
                                        $status_badge = '<span class="badge bg-success">ใช้งานได้</span>';
                                    }

                                    $days_left = $is_expired ? '0 วัน' : $diff->days . ' วัน';
                                ?>
                                <tr>
                                    <td><span class="badge bg-dark bg-opacity-10 text-dark fs-6 px-3 py-2 fw-bold" style="letter-spacing: 2px; border: 1px dashed #666;"><?php echo htmlspecialchars($row['promo_code']); ?></span></td>
                                    <td class="fw-bold text-danger"><?php echo $row['discount_percent']; ?>%</td>
                                    <td><?php echo date('d/m/Y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['end_date'])); ?></td>
                                    <td><span class="text-<?php echo ($is_expired || $row['is_active']==0) ? 'muted' : 'primary fw-bold'; ?>"><?php echo $days_left; ?></span></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-<?php echo $row['is_active'] == 1 ? 'warning' : 'success'; ?> rounded-circle me-1" 
                                                title="<?php echo $row['is_active'] == 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน'; ?>"
                                                onclick="togglePromo(<?php echo $row['promo_id']; ?>, <?php echo $row['is_active']; ?>)">
                                            <i class="fa-solid fa-<?php echo $row['is_active'] == 1 ? 'eye-slash' : 'eye'; ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-circle" title="ลบโค้ดส่วนลด"
                                                onclick="deletePromo(<?php echo $row['promo_id']; ?>)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-tags fa-3x mb-3 opacity-25 d-block"></i>ยังไม่มีโค้ดโปรโมชั่น
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal เพิ่ม Promo -->
<div class="modal fade" id="addPromoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-warning border-0 rounded-top-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-ticket me-2"></i>สร้างโค้ดส่วนลด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="addPromoForm">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">โค้ดส่วนลด (CODE) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase fw-bold" name="promo_code" placeholder="เช่น SUMMER2023, NEWUSER" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ส่วนลด (%) <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="1" max="100" class="form-control" name="discount_percent" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">เริ่มวันที่ <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">ถึงวันที่ <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4 shadow-sm">บันทึกโค้ด</button>
                </div>
            </form>
            
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#addPromoForm').on('submit', function(e) {
        e.preventDefault();
        
        let sDate = new Date($('input[name="start_date"]').val());
        let eDate = new Date($('input[name="end_date"]').val());
        
        if (eDate < sDate) {
            Swal.fire('ข้อผิดพลาด', 'วันสิ้นสุดต้องไม่อยู่ก่อนวันเริ่มต้น', 'error');
            return;
        }

        Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: '../api/promotions/promotion_api.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'เพิ่มโค้ดสำเร็จ!', showConfirmButton: false, timer: 1500 })
                    .then(() => location.reload());
                } else {
                    Swal.fire('ข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    });
});

function togglePromo(id, currentStatus) {
    let newStatus = currentStatus == 1 ? 0 : 1;
    let actionText = newStatus == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    
    $.ajax({
        url: '../api/promotions/promotion_api.php',
        type: 'POST',
        data: { action: 'toggle', promo_id: id, is_active: newStatus },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                location.reload();
            } else {
                Swal.fire('ข้อผิดพลาด', res.message, 'error');
            }
        }
    });
}

function deletePromo(id) {
    Swal.fire({
        title: 'ยืนยันลบโค้ด?',
        text: "คุณแน่ใจหรือไม่ที่จะลบออคูปองนี้ออกจากระบบ",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบทันที',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/promotions/promotion_api.php',
                type: 'POST',
                data: { action: 'delete', promo_id: id },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'ลบสำเร็จ!', showConfirmButton: false, timer: 1200 })
                        .then(() => location.reload());
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

<?php include_once '../includes/footer.php'; ?>
