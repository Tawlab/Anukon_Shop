<?php 
include_once '../includes/header.php'; 
if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit; }

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$total_rows = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$current_user = $_SESSION['user_id'];
$sql = "SELECT u.user_id, u.username, u.first_name, u.last_name, u.nick_name, 
               u.phone_no, u.email, u.user_status, u.role, u.created_at
        FROM users u ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<main class="page-content fade-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-users-gear text-primary me-2"></i>จัดการสมาชิก</h5>
        <div class="d-flex gap-2">
            <div class="position-relative">
                <input type="text" id="searchUser" class="form-control form-control-sm" placeholder="ค้นหา..." style="padding-left: 36px; min-width: 200px;">
                <i class="fa-solid fa-magnifying-glass position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            </div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa-solid fa-user-plus me-1"></i>เพิ่ม
            </button>
        </div>
    </div>

    <div class="card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="usersTable">
                <thead>
                    <tr>
                        <th>ชื่อ-นามสกุล</th>
                        <th>Username</th>
                        <th>อีเมล / เบอร์</th>
                        <th>บทบาท</th>
                        <th>สถานะ</th>
                        <th class="text-center" width="80">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $role_styles = ['admin' => ['bg' => 'var(--danger-soft)', 'color' => 'var(--danger)'], 'staff' => ['bg' => 'var(--warning-soft)', 'color' => '#b45309'], 'customer' => ['bg' => 'var(--info-soft)', 'color' => 'var(--info)']];
                            $rs = $role_styles[$row['role']] ?? $role_styles['customer'];
                        ?>
                        <tr class="user-row" data-search="<?php echo strtolower($row['first_name'] . ' ' . $row['last_name'] . ' ' . $row['username'] . ' ' . $row['email']); ?>">
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                <?php if($row['nick_name']): ?><small class="text-muted">(<?php echo htmlspecialchars($row['nick_name']); ?>)</small><?php endif; ?>
                            </td>
                            <td class="text-primary fw-bold">@<?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <div class="small"><?php echo htmlspecialchars($row['email'] ?: '-'); ?></div>
                                <div class="small text-muted"><?php echo $row['phone_no'] ?: '-'; ?></div>
                            </td>
                            <td><span class="badge" style="background: <?php echo $rs['bg']; ?>; color: <?php echo $rs['color']; ?>;"><?php echo strtoupper($row['role']); ?></span></td>
                            <td>
                                <?php if($row['user_status'] == 1): ?>
                                    <span class="badge" style="background: var(--success-soft); color: var(--success);">ปกติ</span>
                                <?php else: ?>
                                    <span class="badge" style="background: var(--danger-soft); color: var(--danger);">ระงับ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($row['user_id'] != $current_user): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" style="border-radius: var(--radius); min-width: 180px;">
                                        <li><a class="dropdown-item py-2 rounded-3" href="#" 
                                               onclick="openEditUser(<?php echo htmlspecialchars(json_encode(['user_id'=>$row['user_id'],'first_name'=>$row['first_name'],'last_name'=>$row['last_name'],'nick_name'=>$row['nick_name'],'phone_no'=>$row['phone_no'],'email'=>$row['email'],'role'=>$row['role']])); ?>)">
                                            <i class="fa-solid fa-pen me-2 text-primary"></i>แก้ไข</a></li>
                                        <li><a class="dropdown-item py-2 rounded-3" href="#" onclick="openResetPassword(<?php echo $row['user_id']; ?>, '<?php echo addslashes($row['username']); ?>')">
                                            <i class="fa-solid fa-key me-2 text-warning"></i>เปลี่ยนรหัส</a></li>
                                        <li><a class="dropdown-item py-2 rounded-3" href="#" onclick="toggleUserStatus(<?php echo $row['user_id']; ?>, <?php echo $row['user_status']; ?>, '<?php echo addslashes($row['first_name']); ?>')">
                                            <i class="fa-solid fa-<?php echo $row['user_status']==1?'ban':'check'; ?> me-2 <?php echo $row['user_status']==1?'text-danger':'text-success'; ?>"></i><?php echo $row['user_status']==1?'ระงับ':'ปลดระงับ'; ?></a></li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li><a class="dropdown-item py-2 rounded-3 text-danger" href="#" onclick="deleteUser(<?php echo $row['user_id']; ?>, '<?php echo addslashes($row['username']); ?>')">
                                            <i class="fa-solid fa-trash me-2"></i>ลบ</a></li>
                                    </ul>
                                </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted">คุณ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fa-solid fa-users fa-2x mb-2 opacity-25 d-block"></i>ไม่มีสมาชิก</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="p-3 border-top">
            <nav><ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo $page-1; ?>">ก่อนหน้า</a></li>
                <?php for($i=1;$i<=$total_pages;$i++): ?>
                    <li class="page-item <?php echo $page==$i?'active':''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page>=$total_pages?'disabled':''; ?>"><a class="page-link" href="?page=<?php echo $page+1; ?>">ถัดไป</a></li>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white"><h6 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i>เพิ่มสมาชิก</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form id="addUserForm"><input type="hidden" name="action" value="add">
                <div class="modal-body p-4 row g-3">
                    <div class="col-md-6"><label class="form-label fw-bold small">Username <span class="text-danger">*</span></label><input type="text" class="form-control" name="username" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold small">รหัสผ่าน <span class="text-danger">*</span></label><input type="password" class="form-control" name="password" required minlength="6"></div>
                    <div class="col-md-6"><label class="form-label fw-bold small">ชื่อจริง <span class="text-danger">*</span></label><input type="text" class="form-control" name="first_name" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold small">นามสกุล <span class="text-danger">*</span></label><input type="text" class="form-control" name="last_name" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold small">ชื่อเล่น</label><input type="text" class="form-control" name="nick_name"></div>
                    <div class="col-md-6"><label class="form-label fw-bold small">เบอร์โทร</label><input type="text" class="form-control" name="phone_no"></div>
                    <div class="col-12"><label class="form-label fw-bold small">อีเมล <span class="text-danger">*</span></label><input type="email" class="form-control" name="email" required></div>
                    <div class="col-12"><label class="form-label fw-bold small">บทบาท</label>
                        <select class="form-select" name="role"><option value="customer">Customer</option><option value="staff">Staff</option><option value="admin">Admin</option></select>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-success">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h6 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>แก้ไขข้อมูล</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form id="editUserForm"><input type="hidden" name="action" value="edit"><input type="hidden" name="user_id" id="eu_user_id">
                <div class="modal-body p-4 row g-3">
                    <div class="col-md-6"><label class="form-label fw-bold small">ชื่อจริง <span class="text-danger">*</span></label><input type="text" class="form-control" name="first_name" id="eu_first_name" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold small">นามสกุล <span class="text-danger">*</span></label><input type="text" class="form-control" name="last_name" id="eu_last_name" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold small">ชื่อเล่น</label><input type="text" class="form-control" name="nick_name" id="eu_nick_name"></div>
                    <div class="col-md-6"><label class="form-label fw-bold small">เบอร์โทร</label><input type="text" class="form-control" name="phone_no" id="eu_phone_no"></div>
                    <div class="col-12"><label class="form-label fw-bold small">อีเมล <span class="text-danger">*</span></label><input type="email" class="form-control" name="email" id="eu_email" required></div>
                    <div class="col-12"><label class="form-label fw-bold small">บทบาท</label>
                        <select class="form-select" name="role" id="eu_role"><option value="customer">Customer</option><option value="staff">Staff</option><option value="admin">Admin</option></select>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-primary">อัปเดต</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning"><h6 class="modal-title fw-bold text-dark"><i class="fa-solid fa-key me-2"></i>เปลี่ยนรหัสผ่าน</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="resetPasswordForm"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="user_id" id="rp_user_id">
                <div class="modal-body p-4 text-center">
                    <i class="fa-solid fa-lock fa-2x text-warning mb-3 opacity-50"></i>
                    <h6 class="fw-bold">รีเซ็ตสำหรับ: <span id="rp_username" class="text-primary"></span></h6>
                    <div class="mt-3 text-start">
                        <label class="form-label fw-bold small">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" required minlength="6" placeholder="6 ตัวขึ้นไป">
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-warning text-dark">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<script>
$('#searchUser').on('keyup', function() {
    let q = $(this).val().toLowerCase();
    $('.user-row').each(function() { $(this).toggle($(this).data('search').indexOf(q) > -1); });
});

function toggleUserStatus(userId, currentStatus, name) {
    let newStatus = currentStatus == 1 ? 0 : 1;
    let text = newStatus == 1 ? 'เปิดใช้งาน' : 'ระงับ';
    Swal.fire({ title: text + ' "' + name + '"?', icon: 'warning', showCancelButton: true, confirmButtonText: text, cancelButtonText: 'ยกเลิก', confirmButtonColor: newStatus == 0 ? '#ef4444' : '#22c55e' })
    .then(r => { if(r.isConfirmed) { $.post('../api/auth/users_api.php', { action: 'toggle_status', user_id: userId, user_status: newStatus }, function(res) { res.status === 'success' ? Swal.fire({ icon: 'success', title: 'สำเร็จ!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error'); }, 'json'); } });
}

function submitForm(form, msg) {
    Swal.fire({ title: 'กำลังประมวลผล...', didOpen: () => Swal.showLoading() });
    $.post('../api/auth/users_api.php', form.serialize(), function(res) {
        res.status === 'success' ? Swal.fire({ icon: 'success', title: msg, timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
    }, 'json');
}

$('#addUserForm').submit(function(e) { e.preventDefault(); submitForm($(this), 'เพิ่มสำเร็จ!'); });
$('#editUserForm').submit(function(e) { e.preventDefault(); submitForm($(this), 'อัปเดตแล้ว!'); });
$('#resetPasswordForm').submit(function(e) { e.preventDefault(); submitForm($(this), 'เปลี่ยนรหัสผ่านแล้ว!'); });

function openEditUser(u) { $('#eu_user_id').val(u.user_id); $('#eu_first_name').val(u.first_name); $('#eu_last_name').val(u.last_name); $('#eu_nick_name').val(u.nick_name); $('#eu_phone_no').val(u.phone_no); $('#eu_email').val(u.email); $('#eu_role').val(u.role); new bootstrap.Modal(document.getElementById('editUserModal')).show(); }
function openResetPassword(id, username) { $('#rp_user_id').val(id); $('#rp_username').text('@' + username); new bootstrap.Modal(document.getElementById('resetPasswordModal')).show(); }
function deleteUser(id, username) {
    Swal.fire({ title: 'ลบ @'+username+'?', text: 'หากมีประวัติจะถูก Soft Delete แทน', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก' })
    .then(r => { if(r.isConfirmed) { $.post('../api/auth/users_api.php', { action: 'delete', user_id: id }, function(res) { res.status === 'success' ? Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: res.message, confirmButtonColor: '#6366f1' }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error'); }, 'json'); } });
}
</script>

<?php include_once '../includes/footer.php'; ?>
