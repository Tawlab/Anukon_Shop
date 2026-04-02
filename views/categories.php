<?php 
include_once '../includes/header.php'; 
if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit; }

$result = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM products p WHERE p.type_id = t.type_id) as prod_count FROM prod_types t ORDER BY t.type_id DESC");
?>

<main class="page-content fade-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-tags text-primary me-2"></i>หมวดหมู่สินค้า</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCatModal">
            <i class="fa-solid fa-plus me-1"></i>เพิ่มหมวดหมู่
        </button>
    </div>

    <div class="row g-3">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card p-4 h-100 text-center <?php echo $row['type_status'] == 0 ? 'opacity-50' : ''; ?>">
                    <div class="mx-auto mb-3" style="width: 52px; height: 52px; border-radius: 14px; background: var(--primary-light); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-tag text-primary" style="font-size: 1.3rem;"></i>
                    </div>
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($row['type_name']); ?></h6>
                    <small class="text-muted"><?php echo $row['prod_count']; ?> สินค้า</small>
                    
                    <div class="mt-3">
                        <?php if($row['type_status'] == 1): ?>
                            <span class="badge" style="background: var(--success-soft); color: var(--success);">เปิดใช้งาน</span>
                        <?php else: ?>
                            <span class="badge" style="background: var(--danger-soft); color: var(--danger);">ปิดใช้งาน</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2 mt-3 justify-content-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="editCat(<?php echo $row['type_id']; ?>, '<?php echo addslashes($row['type_name']); ?>')">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-<?php echo $row['type_status'] == 1 ? 'warning' : 'success'; ?>" 
                                onclick="toggleCat(<?php echo $row['type_id']; ?>, <?php echo $row['type_status']; ?>)">
                            <i class="fa-solid fa-<?php echo $row['type_status'] == 1 ? 'ban' : 'check'; ?>"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCat(<?php echo $row['type_id']; ?>)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card p-5 text-center text-muted">
                    <i class="fa-solid fa-tags fa-3x mb-3 opacity-25"></i>
                    <p>ยังไม่มีหมวดหมู่</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Add Modal -->
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>เพิ่มหมวดหมู่</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCatForm">
                <div class="modal-body p-4">
                    <label class="form-label fw-bold small">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="type_name" required placeholder="เช่น ขนม, เครื่องดื่ม...">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#addCatForm').submit(function(e) {
    e.preventDefault();
    $.post('../api/category/cat_api.php', $(this).serialize() + '&action=add', function(res) {
        res.status === 'success' ? Swal.fire({ icon: 'success', title: 'เพิ่มสำเร็จ!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
    }, 'json');
});

function editCat(id, name) {
    Swal.fire({
        title: 'แก้ไขหมวดหมู่', input: 'text', inputValue: name, showCancelButton: true,
        confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#6366f1',
        preConfirm: (val) => { if(!val) Swal.showValidationMessage('กรุณากรอกชื่อ'); return val; }
    }).then(r => {
        if(r.isConfirmed) {
            $.post('../api/category/cat_api.php', { action: 'update', type_id: id, type_name: r.value }, function(res) {
                res.status === 'success' ? Swal.fire({ icon: 'success', title: 'อัปเดตแล้ว!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
            }, 'json');
        }
    });
}

function toggleCat(id, currentStatus) {
    let newStatus = currentStatus == 1 ? 0 : 1;
    $.post('../api/category/cat_api.php', { action: 'toggle', type_id: id, type_status: newStatus }, function(res) {
        res.status === 'success' ? location.reload() : Swal.fire('ผิดพลาด', res.message, 'error');
    }, 'json');
}

function deleteCat(id) {
    Swal.fire({ title: 'ลบหมวดหมู่นี้?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก' })
    .then(r => {
        if(r.isConfirmed) {
            $.post('../api/category/cat_api.php', { action: 'delete', type_id: id }, function(res) {
                res.status === 'success' ? Swal.fire({ icon: 'success', title: 'ลบแล้ว!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ลบไม่ได้', res.message, 'error');
            }, 'json');
        }
    });
}
</script>

<?php include_once '../includes/footer.php'; ?>
