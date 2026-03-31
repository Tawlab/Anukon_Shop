<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

$user_id = $_SESSION['user_id'];
$sql = "SELECT u.*, a.*, s.sub_dist_name, d.dist_name, p.prov_name, s.zip_code
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
?>

<main id="content" class="flex-grow-1">
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="fw-bold"><i class="fa-solid fa-address-card text-primary me-2"></i>ข้อมูลส่วนตัว</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">หน้าแรก</a></li>
                <li class="breadcrumb-item active">โปรไฟล์</li>
            </ol>
        </nav>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100">
                <div class="position-relative d-inline-block mx-auto mb-3">
                    <img src="../assets/img/<?php echo $user['profile'] ?: 'default-profile.png'; ?>" 
                         class="rounded-circle border p-1 shadow-sm" 
                         style="width: 150px; height: 150px; object-fit: cover;">
                </div>
                <h4 class="fw-bold mb-1"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h4>
                <p class="text-muted mb-3">@<?php echo $user['username']; ?></p>
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="badge bg-primary px-3 py-2 rounded-pill"><?php echo strtoupper($user['role']); ?></span>
                    <span class="badge <?php echo $user['user_status'] == 1 ? 'bg-success' : 'bg-danger'; ?> px-3 py-2 rounded-pill">
                        <?php echo $user['user_status'] == 1 ? 'ปกติ' : 'ระงับใช้งาน'; ?>
                    </span>
                </div>
                <hr class="opacity-25">
                <div class="text-start">
                    <p class="small mb-1 text-muted">อีเมลผู้ใช้งาน</p>
                    <p class="fw-bold mb-3"><?php echo $user['email']; ?></p>
                    <p class="small mb-1 text-muted">เบอร์โทรศัพท์</p>
                    <p class="fw-bold mb-0"><?php echo $user['phone_no'] ?: 'ไม่ได้ระบุ'; ?></p>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">รายละเอียดข้อมูลส่วนตัว</h5>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="location.href='edit_profile.php'">
                            <i class="fa-solid fa-pen-to-square me-1"></i> แก้ไขข้อมูล
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">ชื่อผู้ใช้งาน</label>
                            <div class="p-2 border-bottom fw-bold"><?php echo $user['username']; ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">ชื่อเล่น</label>
                            <div class="p-2 border-bottom fw-bold"><?php echo $user['nick_name'] ?: '-'; ?></div>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-secondary"><i class="fa-solid fa-truck-fast me-2"></i>ที่อยู่สำหรับจัดส่ง</h6>
                        </div>

                        <div class="col-md-4">
                            <label class="text-muted small d-block">บ้านเลขที่ / หมู่</label>
                            <div class="p-2 border-bottom fw-bold"><?php echo $user['home_no'] . ' ม.' . ($user['moo'] ?: '-'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block">ซอย</label>
                            <div class="p-2 border-bottom fw-bold"><?php echo $user['soi'] ?: '-'; ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block">ถนน</label>
                            <div class="p-2 border-bottom fw-bold"><?php echo $user['road'] ?: '-'; ?></div>
                        </div>

                        <div class="col-md-4">
                            <label class="text-muted small d-block">ตำบล / อำเภอ</label>
                            <div class="p-2 border-bottom fw-bold"><?php echo $user['sub_dist_name'] . ' / ' . $user['dist_name']; ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block">จังหวัด</label>
                            <div class="p-2 border-bottom fw-bold"><?php echo $user['prov_name']; ?></div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block">รหัสไปรษณีย์</label>
                            <div class="p-2 border-bottom fw-bold"><?php echo $user['zip_code']; ?></div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small d-block">หมายเหตุที่อยู่ (Remark)</label>
                            <div class="p-2 border-bottom text-muted"><?php echo $user['remark'] ?: 'ไม่มีข้อมูลเพิ่มเติม'; ?></div>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-top d-flex flex-wrap gap-3">
                        <div class="alert alert-warning mb-0 small py-2 d-flex align-items-center rounded-pill">
                            <i class="fa-solid fa-circle-exclamation me-2"></i> หากต้องการเปลี่ยนรหัสผ่าน กรุณาติดต่อผู้ดูแลระบบ
                        </div>
                        <button class="btn btn-light border px-4 rounded-pill" onclick="history.back()">
                            กลับหน้าเดิม
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<?php 
$stmt->close();
include_once '../includes/footer.php'; 
?>