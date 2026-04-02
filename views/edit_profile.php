<?php 
include_once '../includes/header.php'; 

$user_id = $_SESSION['user_id'];
$sql = "SELECT u.*, a.*, s.sub_dist_name, s.zip_code, s.sub_dist_id as current_sub_dist,
               d.dist_id as current_dist, d.dist_name, 
               p.prov_id as current_prov, p.prov_name
        FROM users u 
        LEFT JOIN addresses a ON u.address_id = a.address_id 
        LEFT JOIN subdistricts s ON a.sub_dist_id = s.sub_dist_id 
        LEFT JOIN districts d ON s.dist_id = d.dist_id 
        LEFT JOIN provinces p ON d.prov_id = p.prov_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<main class="page-content fade-up">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-user-pen text-primary me-2"></i>แก้ไขข้อมูล</h5>
                <a href="profile.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>กลับ</a>
            </div>

            <form id="editProfileForm">
                <!-- Personal Info -->
                <div class="card p-4 mb-3">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-id-card text-primary me-2"></i>ข้อมูลส่วนตัว</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">ชื่อจริง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">ชื่อเล่น</label>
                            <input type="text" class="form-control" name="nick_name" value="<?php echo htmlspecialchars($user['nick_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">เบอร์โทร</label>
                            <input type="text" class="form-control" name="phone_no" value="<?php echo htmlspecialchars($user['phone_no'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="card p-4 mb-3">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-location-dot text-danger me-2"></i>ที่อยู่จัดส่ง</h6>
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label fw-bold small">บ้านเลขที่ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="home_no" value="<?php echo htmlspecialchars($user['home_no'] ?? ''); ?>" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold small">หมู่</label>
                            <input type="text" class="form-control" name="moo" value="<?php echo htmlspecialchars($user['moo'] ?? ''); ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold small">ซอย</label>
                            <input type="text" class="form-control" name="soi" value="<?php echo htmlspecialchars($user['soi'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">ถนน</label>
                            <input type="text" class="form-control" name="road" value="<?php echo htmlspecialchars($user['road'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">หมู่บ้าน</label>
                            <input type="text" class="form-control" name="village" value="<?php echo htmlspecialchars($user['village'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">จังหวัด <span class="text-danger">*</span></label>
                            <select class="form-select" id="province" name="prov_id" required><option value="">เลือก</option></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">อำเภอ <span class="text-danger">*</span></label>
                            <select class="form-select" id="district" name="dist_id" required><option value="">เลือก</option></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">ตำบล <span class="text-danger">*</span></label>
                            <select class="form-select" id="subdistrict" name="sub_dist_id" required><option value="">เลือก</option></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">รหัสไปรษณีย์</label>
                            <input type="text" class="form-control bg-light" id="zipcode" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">หมายเหตุ</label>
                            <textarea class="form-control" name="remark" rows="2" placeholder="จุดสังเกต..."><?php echo htmlspecialchars($user['remark'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i>บันทึกข้อมูล
                </button>
            </form>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    const currentProv = '<?php echo $user['current_prov'] ?? ''; ?>';
    const currentDist = '<?php echo $user['current_dist'] ?? ''; ?>';
    const currentSubDist = '<?php echo $user['current_sub_dist'] ?? ''; ?>';

    $.get('../api/location/get_provinces.php', function(data) {
        $('#province').append(data);
        if (currentProv) $('#province').val(currentProv).trigger('change');
    });

    $('#province').change(function() {
        let prov_id = $(this).val();
        $('#district').html('<option value="">เลือก</option>');
        $('#subdistrict').html('<option value="">เลือก</option>');
        $('#zipcode').val('');
        if (prov_id) {
            $.get('../api/location/get_districts.php', { prov_id }, function(data) {
                $('#district').append(data);
                if (currentDist) $('#district').val(currentDist).trigger('change');
            });
        }
    });

    $('#district').change(function() {
        let dist_id = $(this).val();
        $('#subdistrict').html('<option value="">เลือก</option>');
        $('#zipcode').val('');
        if (dist_id) {
            $.get('../api/location/get_subdistricts.php', { dist_id }, function(data) {
                $('#subdistrict').append(data);
                if (currentSubDist) $('#subdistrict').val(currentSubDist).trigger('change');
            });
        }
    });

    $('#subdistrict').change(function() {
        let zip = $(this).find(':selected').data('zip');
        $('#zipcode').val(zip || '');
    });

    $('#editProfileForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({ title: 'กำลังบันทึก...', didOpen: () => Swal.showLoading() });
        $.post('../api/auth/update_profile.php', $(this).serialize(), function(res) {
            res.status === 'success' 
                ? Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ!', confirmButtonColor: '#6366f1' }).then(() => window.location.href = 'profile.php')
                : Swal.fire('ผิดพลาด', res.message, 'error');
        }, 'json');
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
