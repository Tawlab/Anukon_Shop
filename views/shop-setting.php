<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

// เช็คสิทธิ์เฉพาะ Admin เท่านั้น
if ($_SESSION['role'] !== 'admin') {
    echo "<script>window.location.href='dashboard.php';</script>";
    exit;
}

// ดึงการตั้งค่าทั้งหมดจาก store_settings
$settings = [];
$result = $conn->query("SELECT * FROM store_settings");
while($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<main id="content" class="flex-grow-1 p-3 p-md-4">
    <div class="container-fluid">
        <div class="d-flex align-items-center mb-4">
            <i class="fa-solid fa-gears fs-3 text-primary me-3"></i>
            <div>
                <h4 class="fw-bold mb-0">ตั้งค่าระบบร้านค้า</h4>
                <p class="text-muted small mb-0">จัดการข้อมูลพื้นฐานและเงินทุนของ Anukon Shop</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-4"><i class="fa-solid fa-shop me-2 text-primary"></i>ข้อมูลทั่วไปของร้าน</h6>
                        <form id="generalSettingForm">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">ชื่อร้านค้า</label>
                                <input type="text" class="form-control" name="shop_name" value="<?php echo $settings['shop_name'] ?? 'Anukon Shop'; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-primary"><i class="fa-solid fa-mobile-screen-button me-1"></i>เลขพร้อมเพย์ (PromptPay ID)</label>
                                <input type="text" class="form-control fw-bold" name="promptpay_id" placeholder="เบอร์โทรศัพท์ หรือ เลขบัตรประชาชน" value="<?php echo $settings['promptpay_id'] ?? ''; ?>">
                                <div class="form-text text-muted">ใช้สำหรับแสดงให้ลูกค้าโอนเงินในหน้าชำระเงิน</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">ชื่อบัญชีรับเงิน</label>
                                <input type="text" class="form-control" name="account_name" value="<?php echo $settings['account_name'] ?? ''; ?>">
                            </div>
                            <hr>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-4 rounded-pill">บันทึกการตั้งค่า</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-primary text-white mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-1">ทุนเริ่มต้นปัจจุบัน</h6>
                        <h2 class="fw-bold mb-0">฿ <?php echo number_format($settings['initial_capital'] ?? 0, 2); ?></h2>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-money-bill-trend-up me-2 text-success"></i>เติมทุน / ปรับปรุงทุน</h6>
                        <p class="small text-muted">การแก้ไขตรงนี้จะถูกบันทึกในประวัติเงินทุน (Capital History)</p>
                        
                        <form id="capitalUpdateForm">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">จำนวนเงิน (฿)</label>
                                <input type="number" step="0.01" class="form-control fs-5 fw-bold text-success" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">เหตุผล/หมายเหตุ</label>
                                <input type="text" class="form-control" name="remark" placeholder="เช่น เติมทุนหมุนเวียน, ปรับยอดต้นปี" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold">บันทึกรายการเงินทุน</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    // บันทึกการตั้งค่าทั่วไป
    $('#generalSettingForm').on('submit', function(e) {
        e.preventDefault();
        $.post('../api/settings/update_general.php', $(this).serialize(), function(res) {
            if(res.status === 'success') {
                Swal.fire('สำเร็จ', 'อัปเดตข้อมูลร้านเรียบร้อยแล้ว', 'success');
            }
        }, 'json');
    });

    // บันทึกการปรับปรุงทุน
    $('#capitalUpdateForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'ยืนยันการปรับปรุงทุน?',
            text: "ยอดเงินจะถูกนำไปบวกเพิ่มจากทุนเริ่มต้นเดิม",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../api/settings/update_capital.php', $(this).serialize(), function(res) {
                    if(res.status === 'success') {
                        Swal.fire('สำเร็จ', res.message, 'success').then(() => location.reload());
                    }
                }, 'json');
            }
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>