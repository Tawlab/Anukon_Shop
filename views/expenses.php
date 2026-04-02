<?php 
include_once '../includes/header.php'; 
if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit; }

$result = $conn->query("SELECT * FROM expenses ORDER BY exp_date DESC, exp_id DESC");
?>

<main class="page-content fade-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-wallet text-primary me-2"></i>ค่าใช้จ่าย</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addExpModal">
            <i class="fa-solid fa-plus me-1"></i>บันทึกรายจ่าย
        </button>
    </div>

    <div class="card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>รายการ</th>
                        <th class="text-end">จำนวนเงิน</th>
                        <th class="text-center" width="100">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-muted small"><?php echo date('d/m/Y', strtotime($row['exp_date'])); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($row['remark']); ?></td>
                            <td class="text-end text-danger fw-bold">-฿<?php echo number_format($row['amount'], 2); ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteExp(<?php echo $row['exp_id']; ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted"><i class="fa-solid fa-wallet fa-2x mb-2 opacity-25 d-block"></i>ยังไม่มีรายการค่าใช้จ่าย</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice me-2"></i>บันทึกค่าใช้จ่าย</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addExpForm">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">วันที่</label>
                        <input type="date" class="form-control" name="exp_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">รายการ</label>
                        <input type="text" class="form-control" name="title" placeholder="เช่น ค่าไฟ, ค่าน้ำ" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">จำนวนเงิน (฿)</label>
                        <input type="number" step="0.01" class="form-control text-danger fw-bold fs-5" name="amount" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#addExpForm').submit(function(e) {
    e.preventDefault();
    $.post('../api/reports/save_expense.php', $(this).serialize(), function(res) {
        res.status === 'success' ? Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
    }, 'json');
});

function deleteExp(id) {
    Swal.fire({ title: 'ลบรายการนี้?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก' })
    .then(r => {
        if(r.isConfirmed) {
            $.post('../api/reports/delete_expense.php', { exp_id: id }, function(res) {
                res.status === 'success' ? location.reload() : Swal.fire('ผิดพลาด', res.message, 'error');
            }, 'json');
        }
    });
}
</script>

<?php include_once '../includes/footer.php'; ?>
