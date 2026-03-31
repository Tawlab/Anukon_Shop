<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// ดึงข้อมูลซัพพลายเออร์ทั้งหมด
$sql = "SELECT * FROM supplers ORDER BY sp_id DESC";
$result = $conn->query($sql);
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-handshake text-primary me-2"></i>จัดการซัพพลายเออร์</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-success shadow-sm rounded-pill px-3" onclick="window.location.href='../api/export/export_data.php?type=suppliers'">
                    <i class="fa-solid fa-file-excel me-1"></i>ส่งออก Excel
                </button>
                <button class="btn btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSupModal">
                    <i class="fa-solid fa-plus me-1"></i> เพิ่มผู้จัดจำหน่าย
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>บริษัท/ร้านค้า</th>
                                <th>เลขผู้เสียภาษี</th>
                                <th>ผู้ติดต่อ</th>
                                <th>เบอร์โทร</th>
                                <th>สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php 
                                $counter = 1;
                                while($row = $result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td class="text-muted"><?php echo $counter++; ?></td>
                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($row['sp_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sp_tax']) ?: '-'; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($row['ct_first_name']) ?: '-'; ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['sp_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['ct_phone']) ?: '-'; ?></td>
                                    <td>
                                        <?php if($row['sp_status'] == 1): ?>
                                            <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-check-circle me-1"></i>เปิดดิว</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-2 py-1"><i class="fa-solid fa-ban me-1"></i>ระงับ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info rounded-circle me-1" title="แก้ไข"
                                                onclick='editSup(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-circle ms-1" title="ลบ"
                                                onclick="deleteSup(<?php echo $row['sp_id']; ?>)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-building fa-3x mb-3 opacity-25 d-block"></i>ยังไม่มีข้อมูลผู้จัดจำหน่าย
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal เพิ่ม -->
<div class="modal fade" id="addSupModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4 pb-3">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>เพิ่มซัพพลายเออร์</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addSupForm">
                <div class="modal-body p-4 row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">ชื่อบริษัท / ร้านค้า <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="sp_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">เลขผู้เสียภาษี</label>
                        <input type="text" class="form-control" name="sp_tax">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">ชื่อผู้ติดต่อ</label>
                        <input type="text" class="form-control" name="ct_first_name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" name="ct_phone">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">อีเมล</label>
                        <input type="email" class="form-control" name="sp_email">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไข -->
<div class="modal fade" id="editSupModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-info text-white border-0 rounded-top-4 pb-3">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>แก้ไขข้อมูล</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSupForm">
                <input type="hidden" name="sp_id" id="e_sp_id">
                <div class="modal-body p-4 row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">ชื่อบริษัท / ร้านค้า <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="sp_name" id="e_sp_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">เลขผู้เสียภาษี</label>
                        <input type="text" class="form-control" name="sp_tax" id="e_sp_tax">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">ชื่อผู้ติดต่อ</label>
                        <input type="text" class="form-control" name="ct_first_name" id="e_ct_first_name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" name="ct_phone" id="e_ct_phone">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">อีเมล</label>
                        <input type="email" class="form-control" name="sp_email" id="e_sp_email">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-info text-white px-4">อัปเดตข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#addSupForm').on('submit', function(e) {
    e.preventDefault();
    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    let formData = $(this).serialize() + '&action=add';
    
    $.post('../api/suppliers/sup_api.php', formData, function(res) {
        if(res.status === 'success') {
            Swal.fire({ icon: 'success', title: 'สำเร็จ!', showConfirmButton: false, timer: 1200 })
            .then(() => location.reload());
        } else {
            Swal.fire('ข้อผิดพลาด', res.message, 'error');
        }
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
    Swal.fire({ title: 'กำลังอัปเดต...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    let formData = $(this).serialize() + '&action=update';
    
    $.post('../api/suppliers/sup_api.php', formData, function(res) {
        if(res.status === 'success') {
            Swal.fire({ icon: 'success', title: 'อัปเดตสำเร็จ!', showConfirmButton: false, timer: 1200 })
            .then(() => location.reload());
        } else {
            Swal.fire('ข้อผิดพลาด', res.message, 'error');
        }
    }, 'json');
});

function deleteSup(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'การลบจะไม่สามารถนำกลับมาได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ลบทันที',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../api/suppliers/sup_api.php', { action: 'delete', sp_id: id }, function(res) {
                if(res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'ลบสำเร็จ!', showConfirmButton: false, timer: 1000 })
                    .then(() => location.reload());
                } else {
                    Swal.fire('ลบไม่ได้', res.message, 'error');
                }
            }, 'json');
        }
    });
}
</script>

<?php include_once '../includes/footer.php'; ?>
