<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

// Admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// ยอดรวมรายจ่ายเดือนนี้
$current_month = date('m');
$current_year = date('Y');
$sql_monthly_total = "SELECT SUM(exp_amount) as total FROM expenses WHERE MONTH(exp_date) = ? AND YEAR(exp_date) = ?";
$stmt_monthly = $conn->prepare($sql_monthly_total);
$stmt_monthly->bind_param("ss", $current_month, $current_year);
$stmt_monthly->execute();
$monthly_total = $stmt_monthly->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_monthly->close();

// ดึงรายการรายจ่ายทั้งหมด
$sql_expenses = "SELECT * FROM expenses ORDER BY exp_date DESC";
$res_expenses = $conn->query($sql_expenses);
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="row mb-4 align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <h2 class="fw-bold mb-0"><i class="fa-solid fa-money-bill-transfer text-danger me-2"></i>บันทึกรายจ่ายร้านค้า</h2>
            </div>
            <div class="col-md-6 text-md-end">
                <button class="btn btn-danger rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="fa-solid fa-plus me-1"></i> บันทึกรายจ่ายใหม่
                </button>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-xl-3 col-sm-6 mb-4 mb-xl-0">
                <div class="card h-100 border-0 shadow-sm rounded-4 bg-danger bg-opacity-10">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-danger fw-bold mb-2">รายจ่ายเดือนนี้</h6>
                            <h3 class="fw-bold mb-0 text-dark">฿<?php echo number_format($monthly_total, 2); ?></h3>
                        </div>
                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-chart-line fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="150">วันที่ทำรายการ</th>
                                <th>ประเภท/รายละเอียดรายจ่าย</th>
                                <th class="text-end">จำนวนเงิน (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res_expenses->num_rows > 0): ?>
                                <?php while($row = $res_expenses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['exp_date'])); ?></td>
                                    <td>
                                        <span class="fw-bold"><?php echo htmlspecialchars($row['exp_type']); ?></span>
                                        <?php if(strpos($row['exp_type'], 'สั่งซื้อสินค้าเข้าสต็อก') !== false): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><i class="fa-solid fa-robot"></i> ระบบบันทึกอัตโนมัติ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold text-danger">฿<?php echo number_format($row['exp_amount'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-wallet fa-3x mb-3 opacity-25 d-block"></i>ยังไม่มีข้อมูลรายจ่าย
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal บันทึกรายจ่าย -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2"></i>บันทึกรายจ่ายใหม่</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="addExpenseForm">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ประเภท/รายละเอียด <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="exp_type" placeholder="เช่น ค่าไฟ, ค่าน้ำ, ค่าเช่าที่..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">จำนวนเงิน (บาท) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" class="form-control fw-bold text-danger fs-5" name="exp_amount" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger px-4 shadow-sm">บันทึก</button>
                </div>
            </form>
            
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#addExpenseForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'กำลังบันทึก...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '../api/expenses/add_expense.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload(); 
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
