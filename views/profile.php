<?php 
include_once '../includes/header.php'; 

$uid = $_SESSION['user_id'];
$sql = "SELECT u.*, a.home_no, a.moo, a.soi, a.road, a.remark, 
               s.sub_dist_name, s.zip_code, d.dist_name, p.prov_name 
        FROM users u 
        LEFT JOIN addresses a ON u.address_id = a.address_id 
        LEFT JOIN subdistricts s ON a.sub_dist_id = s.sub_dist_id 
        LEFT JOIN districts d ON s.dist_id = d.dist_id 
        LEFT JOIN provinces p ON d.prov_id = p.prov_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<main class="page-content fade-up">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="mx-auto mb-3" style="width: 72px; height: 72px; border-radius: 50%; background: var(--primary-light); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-circle-user text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h5 class="fw-bold mb-0"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h5>
                    <small class="text-muted">@<?php echo $user['username']; ?></small>
                    <div class="mt-2">
                        <span class="badge bg-primary"><?php echo $user['role'] == 'admin' ? 'ผู้ดูแลระบบ' : 'ลูกค้า'; ?></span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i>เบอร์โทร</small>
                            <div class="fw-bold"><?php echo $user['phone_no'] ?: '-'; ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted"><i class="fa-solid fa-envelope me-1"></i>อีเมล</small>
                            <div class="fw-bold"><?php echo $user['email'] ?: '-'; ?></div>
                        </div>
                    </div>
                </div>

                <?php if(!empty($user['home_no'])): ?>
                <div class="p-3 bg-light rounded-3 mb-4">
                    <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>ที่อยู่จัดส่ง</small>
                    <div class="fw-bold mt-1">
                        <?php echo $user['home_no']; ?> 
                        <?php echo !empty($user['moo']) ? 'ม.'.$user['moo'] : ''; ?> 
                        <?php echo !empty($user['soi']) ? 'ซ.'.$user['soi'] : ''; ?> 
                        <?php echo !empty($user['road']) ? 'ถ.'.$user['road'] : ''; ?>
                    </div>
                    <div class="text-muted small">
                        ต.<?php echo $user['sub_dist_name']; ?> อ.<?php echo $user['dist_name']; ?> 
                        จ.<?php echo $user['prov_name']; ?> <?php echo $user['zip_code']; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <a href="edit_profile.php" class="btn btn-primary flex-fill"><i class="fa-solid fa-pen me-1"></i>แก้ไขข้อมูล</a>
                </div>

                <div class="mt-4 p-3 rounded-3 text-center" style="background: var(--warning-soft);">
                    <small class="text-muted"><i class="fa-solid fa-lock me-1"></i>ต้องการเปลี่ยนรหัสผ่าน?</small>
                    <div class="small fw-bold" style="color: #b45309;">ติดต่อ Admin ผ่านทาง LINE: @anukonshop</div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>