<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

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

<main id="content" class="flex-grow-1">
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-bold"><i class="fa-solid fa-user-pen text-primary me-2"></i>แก้ไขข้อมูลส่วนตัว</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">หน้าแรก</a></li>
                <li class="breadcrumb-item"><a href="profile.php" class="text-decoration-none">โปรไฟล์</a></li>
                <li class="breadcrumb-item active">แก้ไขข้อมูล</li>
            </ol>
        </nav>
    </div>

    <form id="editProfileForm">
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fa-solid fa-id-card text-primary me-2"></i>ข้อมูลส่วนตัว</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ชื่อจริง <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ชื่อเล่น</label>
                                <input type="text" class="form-control" name="nick_name" value="<?php echo htmlspecialchars($user['nick_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">เบอร์โทรศัพท์</label>
                                <input type="text" class="form-control" name="phone_no" value="<?php echo htmlspecialchars($user['phone_no'] ?? ''); ?>" placeholder="08x-xxx-xxxx">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">อีเมล <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mt-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fa-solid fa-truck-fast text-success me-2"></i>ที่อยู่จัดส่ง</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">บ้านเลขที่ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="home_no" value="<?php echo htmlspecialchars($user['home_no'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">หมู่</label>
                                <input type="text" class="form-control" name="moo" value="<?php echo htmlspecialchars($user['moo'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">ซอย</label>
                                <input type="text" class="form-control" name="soi" value="<?php echo htmlspecialchars($user['soi'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ถนน</label>
                                <input type="text" class="form-control" name="road" value="<?php echo htmlspecialchars($user['road'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">หมู่บ้าน</label>
                                <input type="text" class="form-control" name="village" value="<?php echo htmlspecialchars($user['village'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">จังหวัด <span class="text-danger">*</span></label>
                                <select class="form-select" id="province" name="prov_id" required>
                                    <option value="">เลือกจังหวัด</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">อำเภอ/เขต <span class="text-danger">*</span></label>
                                <select class="form-select" id="district" name="dist_id" required>
                                    <option value="">เลือกอำเภอ</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ตำบล/แขวง <span class="text-danger">*</span></label>
                                <select class="form-select" id="subdistrict" name="sub_dist_id" required>
                                    <option value="">เลือกตำบล</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">รหัสไปรษณีย์</label>
                                <input type="text" class="form-control bg-light" id="zipcode" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">หมายเหตุที่อยู่</label>
                                <textarea class="form-control" name="remark" rows="2" placeholder="เช่น จุดสังเกต, ฝากไว้ที่..."><?php echo htmlspecialchars($user['remark'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                    <div class="card-body p-4 text-center">
                        <img src="../assets/img/<?php echo htmlspecialchars($user['profile'] ?: 'default-profile.png'); ?>" 
                             class="rounded-circle border p-1 shadow-sm mb-3" 
                             style="width: 120px; height: 120px; object-fit: cover;">
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted mb-4">@<?php echo htmlspecialchars($user['username']); ?></p>
                        
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm mb-3">
                            <i class="fa-solid fa-floppy-disk me-2"></i>บันทึกข้อมูล
                        </button>
                        <a href="profile.php" class="btn btn-light border w-100 py-2 rounded-3">
                            <i class="fa-solid fa-arrow-left me-2"></i>กลับหน้าโปรไฟล์
                        </a>

                        <hr class="my-4 opacity-25">
                        <button type="button" class="btn btn-outline-info w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#contactModal">
                            <i class="fa-solid fa-headset me-2"></i>ติดต่อแอดมิน
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</main>

<!-- Modal ติดต่อแอดมิน -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-headset me-2"></i>ติดต่อแอดมิน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <i class="fa-solid fa-store fa-3x text-primary mb-3"></i>
                    <h5 class="fw-bold">V-SHOP ร้านจำหน่ายสินค้าไอที</h5>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item border-0 px-0 py-3">
                        <i class="fa-solid fa-phone text-success me-3 fa-fw"></i>
                        <strong>โทรศัพท์:</strong> 02-xxx-xxxx
                    </div>
                    <div class="list-group-item border-0 px-0 py-3">
                        <i class="fa-brands fa-line text-success me-3 fa-fw"></i>
                        <strong>LINE ID:</strong> @v-shop
                    </div>
                    <div class="list-group-item border-0 px-0 py-3">
                        <i class="fa-solid fa-envelope text-primary me-3 fa-fw"></i>
                        <strong>อีเมล:</strong> admin@v-shop.com
                    </div>
                    <div class="list-group-item border-0 px-0 py-3">
                        <i class="fa-solid fa-clock text-warning me-3 fa-fw"></i>
                        <strong>เวลาทำการ:</strong> จ-ส 09:00 - 18:00
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ค่าปัจจุบันจาก DB
    const currentProv = '<?php echo $user['current_prov'] ?? ''; ?>';
    const currentDist = '<?php echo $user['current_dist'] ?? ''; ?>';
    const currentSubDist = '<?php echo $user['current_sub_dist'] ?? ''; ?>';

    // โหลดจังหวัด
    $.get('../api/location/get_provinces.php', function(data) {
        $('#province').append(data);
        if (currentProv) {
            $('#province').val(currentProv).trigger('change');
        }
    });

    // เมื่อเลือกจังหวัด
    $('#province').change(function() {
        let prov_id = $(this).val();
        $('#district').html('<option value="">เลือกอำเภอ</option>');
        $('#subdistrict').html('<option value="">เลือกตำบล</option>');
        $('#zipcode').val('');

        if (prov_id) {
            $.get('../api/location/get_districts.php', { prov_id: prov_id }, function(data) {
                $('#district').append(data);
                if (currentDist) {
                    $('#district').val(currentDist).trigger('change');
                }
            });
        }
    });

    // เมื่อเลือกอำเภอ
    $('#district').change(function() {
        let dist_id = $(this).val();
        $('#subdistrict').html('<option value="">เลือกตำบล</option>');
        $('#zipcode').val('');

        if (dist_id) {
            $.get('../api/location/get_subdistricts.php', { dist_id: dist_id }, function(data) {
                $('#subdistrict').append(data);
                if (currentSubDist) {
                    $('#subdistrict').val(currentSubDist).trigger('change');
                    // Reset เพื่อไม่ให้ใส่ค่าเดิมซ้ำในครั้งถัดไป
                    window.setTimeout(function() {}, 100);
                }
            });
        }
    });

    // เมื่อเลือกตำบล → ดึงรหัสไปรษณีย์
    $('#subdistrict').change(function() {
        let zip = $(this).find(':selected').data('zip');
        $('#zipcode').val(zip ? zip : '');
    });

    // ส่งฟอร์ม
    $('#editProfileForm').on('submit', function(e) {
        e.preventDefault();

        Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        $.ajax({
            url: '../api/auth/update_profile.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ!',
                        text: 'ข้อมูลของคุณได้รับการอัปเดตแล้ว',
                        confirmButtonColor: '#0056b3'
                    }).then(() => {
                        window.location.href = 'profile.php';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: res.message });
                }
            },
            error: function() {
                Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
