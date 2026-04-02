<?php 
include_once '../includes/header.php'; 

if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit; }

$sql = "SELECT * FROM supplers ORDER BY sp_id DESC";
$result = $conn->query($sql);
?>

<main class="page-content fade-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-handshake text-primary me-2"></i>ซัพพลายเออร์</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm" onclick="window.location.href='../api/export/export_data.php?type=suppliers'">
                <i class="fa-solid fa-file-excel me-1"></i>Excel
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSupModal">
                <i class="fa-solid fa-plus me-1"></i>เพิ่ม
            </button>
        </div>
    </div>

    <!-- Card Grid -->
    <div class="row g-3">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card p-4 h-100 <?php echo $row['sp_status'] == 0 ? 'opacity-50' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--primary-light); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-building text-primary"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($row['sp_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($row['sp_tax']) ?: 'ไม่ระบุเลขภาษี'; ?></small>
                            </div>
                        </div>
                        <?php if($row['sp_status'] == 1): ?>
                            <span class="badge bg-success-soft text-success" style="background: var(--success-soft);">เปิดใช้งาน</span>
                        <?php else: ?>
                            <span class="badge" style="background: var(--danger-soft); color: var(--danger);">ระงับ</span>
                        <?php endif; ?>
                    </div>

                    <div class="small text-muted mb-3">
                        <div class="mb-1"><i class="fa-solid fa-user me-2 text-secondary"></i><?php echo htmlspecialchars($row['ct_first_name'] ?? '') . ' ' . htmlspecialchars($row['ct_last_name'] ?? ''); ?></div>
                        <div class="mb-1"><i class="fa-solid fa-phone me-2 text-secondary"></i><?php echo htmlspecialchars($row['ct_phone']) ?: '-'; ?></div>
                        <div><i class="fa-solid fa-envelope me-2 text-secondary"></i><?php echo htmlspecialchars($row['sp_email']) ?: '-'; ?></div>
                    </div>

                    <div class="d-flex gap-2 mt-auto">
                        <button class="btn btn-sm btn-outline-primary flex-fill" 
                                onclick='editSup(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                            <i class="fa-solid fa-pen me-1"></i>แก้ไข
                        </button>
                        <button class="btn btn-sm btn-outline-<?php echo $row['sp_status'] == 1 ? 'warning' : 'success'; ?>" 
                                onclick="toggleSupStatus(<?php echo $row['sp_id']; ?>, <?php echo $row['sp_status']; ?>)" title="<?php echo $row['sp_status'] == 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน'; ?>">
                            <i class="fa-solid fa-<?php echo $row['sp_status'] == 1 ? 'ban' : 'check'; ?>"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSup(<?php echo $row['sp_id']; ?>)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card p-5 text-center text-muted">
                    <i class="fa-solid fa-building fa-3x mb-3 opacity-25"></i>
                    <p>ยังไม่มีข้อมูลซัพพลายเออร์</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal เพิ่ม -->
<div class="modal fade" id="addSupModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>เพิ่มซัพพลายเออร์</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addSupForm">
                <div class="modal-body p-4 row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">ชื่อบริษัท/ร้าน <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="sp_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">เลขผู้เสียภาษี</label>
                        <input type="text" class="form-control" name="sp_tax">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">ชื่อผู้ติดต่อ</label>
                        <input type="text" class="form-control" name="ct_first_name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">เบอร์โทร</label>
                        <input type="text" class="form-control" name="ct_phone">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">อีเมล</label>
                        <input type="email" class="form-control" name="sp_email">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไข -->
<div class="modal fade" id="editSupModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-pen me-2"></i>แก้ไขข้อมูล</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSupForm">
                <input type="hidden" name="sp_id" id="e_sp_id">
                <div class="modal-body p-4 row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">ชื่อบริษัท/ร้าน <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="sp_name" id="e_sp_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">เลขผู้เสียภาษี</label>
                        <input type="text" class="form-control" name="sp_tax" id="e_sp_tax">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">ชื่อผู้ติดต่อ</label>
                        <input type="text" class="form-control" name="ct_first_name" id="e_ct_first_name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">เบอร์โทร</label>
                        <input type="text" class="form-control" name="ct_phone" id="e_ct_phone">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">อีเมล</label>
                        <input type="email" class="form-control" name="sp_email" id="e_sp_email">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-info text-white">อัปเดต</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#addSupForm').on('submit', function(e) {
    e.preventDefault();
    Swal.fire({ title: 'กำลังบันทึก...', didOpen: () => Swal.showLoading() });
    $.post('../api/suppliers/sup_api.php', $(this).serialize() + '&action=add', function(res) {
        res.status === 'success' ? Swal.fire({ icon: 'success', title: 'สำเร็จ!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
    }, 'json');
});

function editSup(data) {
    $('#e_sp_id').val(data.sp_id);
    $('#e_sp_name').val(data.sp_name);
    $('#e_sp_tax').val(data.sp_tax);
    $('#e_ct_first_name').val(data.ct_first_name);
    $('#e_ct_phone').val(data.ct_phone);
    $('#e_sp_email').val(data.sp_email);
    new bootstrap.Modal(document.getElementById('editSupModal')).show();
}

$('#editSupForm').on('submit', function(e) {
    e.preventDefault();
    Swal.fire({ title: 'กำลังอัปเดต...', didOpen: () => Swal.showLoading() });
    $.post('../api/suppliers/sup_api.php', $(this).serialize() + '&action=update', function(res) {
        res.status === 'success' ? Swal.fire({ icon: 'success', title: 'อัปเดตแล้ว!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
    }, 'json');
});

// NEW: Toggle Status
function toggleSupStatus(id, currentStatus) {
    let newStatus = currentStatus == 1 ? 0 : 1;
    let actionText = newStatus == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    
    Swal.fire({
        title: actionText + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: actionText,
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: newStatus == 1 ? '#22c55e' : '#f59e0b'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../api/suppliers/sup_api.php', { action: 'toggle_status', sp_id: id, sp_status: newStatus }, function(res) {
                res.status === 'success' ? Swal.fire({ icon: 'success', title: actionText + 'แล้ว!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
            }, 'json');
        }
    });
}

function deleteSup(id) {
    Swal.fire({
        title: 'ยืนยันลบ?', icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../api/suppliers/sup_api.php', { action: 'delete', sp_id: id }, function(res) {
                res.status === 'success' ? Swal.fire({ icon: 'success', title: 'ลบแล้ว!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ลบไม่ได้', res.message, 'error');
            }, 'json');
        }
    });
}
</script>

<?php include_once '../includes/footer.php'; ?>
