<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

// Admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// ระบบแบ่งหน้า (Pagination)
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// หาจำนวนข้อมูลทั้งหมด
$sql_count = "SELECT COUNT(*) as total FROM users";
$total_result = $conn->query($sql_count);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// ดึงข้อมูลสมาชิกตามหน้าปัจจุบัน
$current_user = $_SESSION['user_id'];
$sql = "SELECT u.user_id, u.username, u.first_name, u.last_name, u.nick_name, 
               u.phone_no, u.email, u.user_status, u.role, u.created_at,
               a.home_no, s.sub_dist_name, d.dist_name, p.prov_name
        FROM users u
        LEFT JOIN addresses a ON u.address_id = a.address_id
        LEFT JOIN subdistricts s ON a.sub_dist_id = s.sub_dist_id
        LEFT JOIN districts d ON s.dist_id = d.dist_id
        LEFT JOIN provinces p ON d.prov_id = p.prov_id
        ORDER BY u.created_at DESC
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-users-gear text-primary me-2"></i>จัดการข้อมูลสมาชิก</h2>
            <div class="d-flex gap-2 w-100 justify-content-md-end" style="max-width: 500px;">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="searchUser" class="form-control border-start-0" placeholder="ค้นหาชื่อ, username...">
                </div>
                <button class="btn btn-primary shadow-sm text-nowrap rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fa-solid fa-user-plus me-1"></i>เพิ่มผู้ใช้
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="w-100">
                    <table class="table table-hover align-middle mb-0 text-nowrap" id="usersTable">
                        <thead class="table-light">
                            <tr>
                                <th width="60">#</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>Username</th>
                                <th>อีเมล / เบอร์</th>
                                <th>ที่อยู่</th>
                                <th>บทบาท</th>
                                <th>สมัครเมื่อ</th>
                                <th>สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php 
                                $counter = $offset + 1;
                                while($row = $result->fetch_assoc()): 
                                    $role_badge = '';
                                    switch($row['role']) {
                                        case 'admin': $role_badge = 'bg-danger'; break;
                                        case 'staff': $role_badge = 'bg-warning text-dark'; break;
                                        default: $role_badge = 'bg-info text-dark';
                                    }
                                    $addr = '';
                                    if (!empty($row['prov_name'])) {
                                        $addr = (!empty($row['sub_dist_name']) ? $row['sub_dist_name'] . ', ' : '') . $row['prov_name'];
                                    }
                                ?>
                                <tr class="user-row" 
                                    data-search="<?php echo strtolower(htmlspecialchars($row['first_name'] . ' ' . $row['last_name'] . ' ' . $row['username'] . ' ' . $row['email'])); ?>">
                                    <td class="fw-bold text-muted"><?php echo $counter++; ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                        <?php if($row['nick_name']): ?>
                                            <small class="text-muted">(<?php echo htmlspecialchars($row['nick_name']); ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-primary fw-bold">@<?php echo htmlspecialchars($row['username']); ?></td>
                                    <td>
                                        <div class="small"><?php echo htmlspecialchars($row['email'] ?: '-'); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($row['phone_no'] ?: '-'); ?></div>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($addr ?: 'ไม่ระบุ'); ?></td>
                                    <td><span class="badge <?php echo $role_badge; ?> px-2 py-1"><?php echo strtoupper($row['role']); ?></span></td>
                                    <td class="small text-muted"><?php echo date_format(date_create($row['created_at']), "d M Y"); ?></td>
                                    <td>
                                        <?php if($row['user_status'] == 1): ?>
                                            <span class="badge bg-success px-2 py-1">ปกติ</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-2 py-1">ระงับ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($row['user_id'] != $current_user): ?>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border rounded-circle shadow-sm" type="button" data-bs-toggle="dropdown">
                                                    <i class="fa-solid fa-ellipsis-vertical text-muted"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 text-sm">
                                                    <li>
                                                        <a class="dropdown-item py-2" href="#" 
                                                           onclick="openEditUser(<?php echo htmlspecialchars(json_encode([
                                                               'user_id' => $row['user_id'],
                                                               'first_name' => $row['first_name'],
                                                               'last_name' => $row['last_name'],
                                                               'nick_name' => $row['nick_name'],
                                                               'phone_no' => $row['phone_no'],
                                                               'email' => $row['email'],
                                                               'role' => $row['role']
                                                           ])); ?>)">
                                                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i>แก้ไขข้อมูล
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="#" onclick="openResetPassword(<?php echo $row['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['username'])); ?>')">
                                                            <i class="fa-solid fa-key me-2 text-warning"></i>เปลี่ยนรหัสผ่าน
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="#" onclick="toggleUserStatus(<?php echo $row['user_id']; ?>, <?php echo $row['user_status']; ?>, '<?php echo htmlspecialchars(addslashes($row['first_name'])); ?>')">
                                                            <?php if($row['user_status'] == 1): ?>
                                                                <i class="fa-solid fa-ban me-2 text-danger"></i>ระงับบัญชี
                                                            <?php else: ?>
                                                                <i class="fa-solid fa-check me-2 text-success"></i>ปลดระงับบัญชี
                                                            <?php endif; ?>
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item py-2 text-danger" href="#" onclick="deleteUser(<?php echo $row['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($row['username'])); ?>')">
                                                            <i class="fa-solid fa-trash me-2"></i>ลบบัญชีผู้ใช้
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">คุณตัวเอง</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-users-slash fa-3x mb-3 opacity-25 d-block"></i>ยังไม่มีข้อมูลสมาชิก
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if($total_pages > 1): ?>
            <div class="card-footer bg-white border-0 py-3 rounded-bottom-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link shadow-sm" href="?page=<?php echo $page - 1; ?>" tabindex="-1">ก่อนหน้า</a>
                        </li>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link shadow-sm" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link shadow-sm" href="?page=<?php echo $page + 1; ?>">ถัดไป</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- Modal เพิ่มข้อมูลผู้ใช้ -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i>เพิ่มสมาชิกใหม่</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ชื่อผู้ใช้ (Username) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" required minlength="4">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">รหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ชื่อจริง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ชื่อเล่น</label>
                            <input type="text" class="form-control" name="nick_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">เบอร์โทรศัพท์</label>
                            <input type="text" class="form-control" name="phone_no">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold text-dark"><i class="fa-solid fa-shield-halved text-info me-2"></i>ระดับสิทธิ์ผู้ใช้ (Role)</label>
                            <select class="form-select border-info" name="role">
                                <option value="customer">Customer (ลูกค้า)</option>
                                <option value="staff">Staff (พนักงาน)</option>
                                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success px-4 shadow-sm fw-bold">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไขข้อมูลผู้ใช้ -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>แก้ไขข้อมูลสมาชิก</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="eu_user_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ชื่อจริง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" id="eu_first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" id="eu_last_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ชื่อเล่น</label>
                            <input type="text" class="form-control" name="nick_name" id="eu_nick_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">เบอร์โทรศัพท์</label>
                            <input type="text" class="form-control" name="phone_no" id="eu_phone_no">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="eu_email" required>
                        </div>
                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold text-dark"><i class="fa-solid fa-shield-halved text-info me-2"></i>ระดับสิทธิ์ผู้ใช้ (Role)</label>
                            <select class="form-select border-info" name="role" id="eu_role">
                                <option value="customer">Customer (ลูกค้า)</option>
                                <option value="staff">Staff (พนักงาน)</option>
                                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-bold">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal เปลี่ยนรหัสผ่าน -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-warning border-0 rounded-top-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-key me-2"></i>เปลี่ยนรหัสผ่าน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resetPasswordForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="rp_user_id">
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-lock fa-3x text-warning mb-3 opacity-50"></i>
                        <h6 class="fw-bold">รีเซ็ตรหัสผ่านสำหรับ: <span id="rp_username" class="text-primary"></span></h6>
                        <p class="text-muted small">ระบบจะทำการเปลี่ยนรหัสผ่านของผู้ใช้งานท่านนี้ทันทีเมื่อกดยืนยัน</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" required minlength="6" placeholder="รหัสผ่านอย่างน้อย 6 ตัวอักษร">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning px-4 shadow-sm fw-bold text-dark">บันทึกรหัสผ่านใหม่</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ค้นหาผู้ใช้แบบ Real-time (client-side filter)
$('#searchUser').on('keyup', function() {
    let query = $(this).val().toLowerCase();
    $('.user-row').each(function() {
        let data = $(this).data('search');
        $(this).toggle(data.indexOf(query) > -1);
    });
});

// Toggle สถานะผู้ใช้
function toggleUserStatus(userId, currentStatus, name) {
    let newStatus = currentStatus == 1 ? 0 : 1;
    let actionText = newStatus == 1 ? 'เปิดใช้งาน' : 'ระงับการใช้งาน';
    
    Swal.fire({
        title: actionText + ' "' + name + '"?',
        text: newStatus == 0 ? 'ผู้ใช้จะไม่สามารถเข้าสู่ระบบได้' : 'ผู้ใช้จะสามารถเข้าสู่ระบบได้ตามปกติ',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: newStatus == 0 ? '#dc3545' : '#198754',
        confirmButtonText: actionText,
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/auth/users_api.php',
                type: 'POST',
                data: { action: 'toggle_status', user_id: userId, user_status: newStatus },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        Swal.fire({ icon: 'success', title: actionText + 'สำเร็จ!', showConfirmButton: false, timer: 1200 })
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

<script>
$(document).ready(function() {
    // Submit เพิ่มผู้ใช้
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), 'เพิ่มสมาชิกสำเร็จ!');
    });

    // Submit แก้ไขผู้ใช้
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), 'บันทึกสำเร็จ!');
    });

    // Submit เปลี่ยนรหัสผ่าน
    $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), 'เปลี่ยนรหัสผ่านสำเร็จ!');
    });
});

function submitForm(formElement, successMsg) {
    Swal.fire({ title: 'กำลังประมวลผล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    
    $.ajax({
        url: '../api/auth/users_api.php',
        type: 'POST',
        data: formElement.serialize(),
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                Swal.fire({ icon: 'success', title: successMsg, showConfirmButton: false, timer: 1500 })
                .then(() => location.reload());
            } else {
                Swal.fire('ข้อผิดพลาด', res.message, 'error');
            }
        }
    });
}

function openEditUser(user) {
    $('#eu_user_id').val(user.user_id);
    $('#eu_first_name').val(user.first_name);
    $('#eu_last_name').val(user.last_name);
    $('#eu_nick_name').val(user.nick_name);
    $('#eu_phone_no').val(user.phone_no);
    $('#eu_email').val(user.email);
    $('#eu_role').val(user.role);
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function openResetPassword(userId, username) {
    $('#rp_user_id').val(userId);
    $('#rp_username').text('@' + username);
    $('#resetPasswordForm')[0].reset();
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function deleteUser(userId, username) {
    Swal.fire({
        title: 'ยืนยันลบบัญชี @' + username + '?',
        text: "โปรดทราบ: หากผู้ใช้นี้มีประวัติการสั่งซื้อ หรือประวัติทำรายการในระบบ บัญชีจะถูกเปลี่ยนเป็น 'ระงับการใช้งาน' (Soft Delete) แทนการลบทิ้ง เพื่อรักษาข้อมูลทางบัญชี",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'กำลังตรวจสอบและลบ...', didOpen: () => { Swal.showLoading(); } });
            $.ajax({
                url: '../api/auth/users_api.php',
                type: 'POST',
                data: { action: 'delete', user_id: userId },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        // แจ้งเตือนข้อความยาวๆได้ถ้าเป็น Soft Delete
                        Swal.fire({ 
                            icon: 'success', 
                            title: 'ดำเนินการเสร็จสิ้น!', 
                            text: res.message, 
                            confirmButtonColor: '#0056b3'
                        }).then(() => location.reload());
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
