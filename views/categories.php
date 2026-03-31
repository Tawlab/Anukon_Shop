<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

// Admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// ดึงหมวดหมู่ทั้งหมด
$sql = "SELECT * FROM prod_types ORDER BY type_id DESC";
$result = $conn->query($sql);
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-tags text-primary me-2"></i>จัดการหมวดหมู่สินค้า</h2>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fa-solid fa-plus me-1"></i> เพิ่มหมวดหมู่ใหม่
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="80">#</th>
                                <th>ชื่อหมวดหมู่</th>
                                <th>จำนวนสินค้า</th>
                                <th>สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php 
                                $counter = 1;
                                while($row = $result->fetch_assoc()): 
                                    // นับสินค้าในหมวดหมู่นี้
                                    $count_sql = $conn->prepare("SELECT COUNT(*) as cnt FROM products WHERE type_id = ?");
                                    $count_sql->bind_param("i", $row['type_id']);
                                    $count_sql->execute();
                                    $prod_count = $count_sql->get_result()->fetch_assoc()['cnt'];
                                    $count_sql->close();
                                ?>
                                <tr>
                                    <td class="fw-bold text-muted"><?php echo $counter++; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['type_name']); ?></td>
                                    <td>
                                        <span class="badge bg-dark bg-opacity-75 text-white px-3 py-2">
                                            <?php echo $prod_count; ?> รายการ
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['type_status'] == 1): ?>
                                            <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-check-circle me-1"></i>เปิดใช้งาน</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-2 py-1"><i class="fa-solid fa-ban me-1"></i>ปิดใช้งาน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info rounded-circle me-1" title="แก้ไข"
                                                onclick="editCategory(<?php echo $row['type_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['type_name'])); ?>')">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-<?php echo $row['type_status'] == 1 ? 'warning' : 'success'; ?> rounded-circle" 
                                                title="<?php echo $row['type_status'] == 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน'; ?>"
                                                onclick="toggleCategoryStatus(<?php echo $row['type_id']; ?>, <?php echo $row['type_status']; ?>)">
                                            <i class="fa-solid fa-<?php echo $row['type_status'] == 1 ? 'eye-slash' : 'eye'; ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-circle ms-1" title="ลบ"
                                                onclick="deleteCategory(<?php echo $row['type_id']; ?>, <?php echo $prod_count; ?>)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-25 d-block"></i>ยังไม่มีข้อมูลหมวดหมู่
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal เพิ่มหมวดหมู่ -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>เพิ่มหมวดหมู่ใหม่</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCategoryForm">
                <div class="modal-body p-4">
                    <label class="form-label fw-bold">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="type_name" placeholder="เช่น อุปกรณ์โทรศัพท์" required>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไขหมวดหมู่ -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-info text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen me-2"></i>แก้ไขหมวดหมู่</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCategoryForm">
                <input type="hidden" name="type_id" id="edit_type_id">
                <div class="modal-body p-4">
                    <label class="form-label fw-bold">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="type_name" id="edit_type_name" required>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-info text-white px-4">อัปเดต</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// เพิ่มหมวดหมู่
$('#addCategoryForm').on('submit', function(e) {
    e.preventDefault();
    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    $.ajax({
        url: '../api/products/categories_api.php',
        type: 'POST',
        data: { action: 'add', type_name: $('[name="type_name"]', this).val() },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'เพิ่มสำเร็จ!', showConfirmButton: false, timer: 1200 })
                .then(() => location.reload());
            } else {
                Swal.fire('ข้อผิดพลาด', res.message, 'error');
            }
        }
    });
});

// เปิด modal แก้ไข
function editCategory(id, name) {
    $('#edit_type_id').val(id);
    $('#edit_type_name').val(name);
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

// แก้ไขหมวดหมู่
$('#editCategoryForm').on('submit', function(e) {
    e.preventDefault();
    Swal.fire({ title: 'กำลังอัปเดต...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    $.ajax({
        url: '../api/products/categories_api.php',
        type: 'POST',
        data: { action: 'update', type_id: $('#edit_type_id').val(), type_name: $('#edit_type_name').val() },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'อัปเดตสำเร็จ!', showConfirmButton: false, timer: 1200 })
                .then(() => location.reload());
            } else {
                Swal.fire('ข้อผิดพลาด', res.message, 'error');
            }
        }
    });
});

// Toggle สถานะ
function toggleCategoryStatus(id, currentStatus) {
    let newStatus = currentStatus == 1 ? 0 : 1;
    let actionText = newStatus == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    
    Swal.fire({
        title: 'ยืนยัน' + actionText + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: actionText,
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: newStatus == 1 ? '#198754' : '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/products/categories_api.php',
                type: 'POST',
                data: { action: 'toggle_status', type_id: id, type_status: newStatus },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        Swal.fire({ icon: 'success', title: actionText + 'สำเร็จ!', showConfirmButton: false, timer: 1000 })
                        .then(() => location.reload());
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

// ลบหมวดหมู่
function deleteCategory(id, count) {
    if (count > 0) {
        Swal.fire('ไม่สามารถลบได้!', 'หมวดหมู่นี้ถูกใช้งานกับสินค้าจำนวน ' + count + ' รายการ กรุณาย้ายหรือลบสินค้าก่อน', 'error');
        return;
    }
    
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'หากลบหมวดหมู่จะไม่สามารถนำกลับมาได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบทันที',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'กำลังลบ...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            $.ajax({
                url: '../api/products/categories_api.php',
                type: 'POST',
                data: { action: 'delete', type_id: id },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'ลบหมวดหมู่เรียบร้อยแล้ว!', showConfirmButton: false, timer: 1000 })
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
