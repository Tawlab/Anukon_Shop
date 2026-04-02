<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | Anukon Shop</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg: #f1f5f9;
            --surface: #ffffff;
            --text: #1e293b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --radius: 16px;
        }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            background: var(--surface);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.06);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
        }

        .auth-header {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            padding: 36px 24px;
            text-align: center;
            color: white;
        }
        .auth-header .logo-icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        .auth-header h3 { font-weight: 700; margin-bottom: 4px; font-size: 1.3rem; }
        .auth-header p { opacity: 0.8; font-size: 0.85rem; margin: 0; }

        .auth-body { padding: 32px; }

        .form-label { font-weight: 600; font-size: 0.82rem; color: var(--text); margin-bottom: 6px; }
        
        .input-icon-group {
            position: relative;
        }
        .input-icon-group i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .input-icon-group .form-control {
            padding-left: 40px;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding-top: 12px;
            padding-bottom: 12px;
        }
        .input-icon-group .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            background: none;
            border: none;
            padding: 0;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 13px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: 0.2s;
            font-size: 0.95rem;
        }
        .btn-submit:hover { background: var(--primary-hover); color: white; transform: translateY(-1px); }

        .form-scrollable {
            max-height: 50vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 8px;
        }
        .form-scrollable::-webkit-scrollbar { width: 4px; }
        .form-scrollable::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

        .text-link { color: var(--primary); text-decoration: none; font-weight: 600; }
        .text-link:hover { text-decoration: underline; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-up { animation: slideUp 0.4s ease-out; }
    </style>
</head>
<body>

<div class="auth-card animate-up">
    <div class="auth-header">
        <div class="logo-icon"><i class="fa-solid fa-store"></i></div>
        <h3>Anukon Shop</h3>
        <p>ร้านสะดวกซื้อออนไลน์</p>
    </div>

    <div class="auth-body">
        
        <!-- Login Form -->
        <div id="login-section">
            <h5 class="text-center mb-4 fw-bold" style="font-size: 1.1rem;">ยินดีต้อนรับ</h5>
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ใช้งาน</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-circle-user"></i>
                        <input type="text" class="form-control" name="username" placeholder="กรอกชื่อผู้ใช้งาน" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">รหัสผ่าน</label>
                    <div class="input-icon-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" class="form-control pass-input" name="password" placeholder="กรอกรหัสผ่าน" required>
                        <button type="button" class="toggle-password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label small" for="remember">จดจำฉัน</label>
                    </div>
                    <a href="forgot_password.php" class="text-link small">ลืมรหัสผ่าน?</a>
                </div>

                <button type="submit" class="btn btn-submit mb-3">เข้าสู่ระบบ</button>
                <div class="text-center small text-muted">
                    ยังไม่มีบัญชี? <a href="javascript:void(0)" onclick="toggleForm('reg')" class="text-link">สมัครสมาชิก</a>
                </div>
            </form>
        </div>

        <!-- Register Form -->
        <div id="register-section" style="display: none;">
            <h5 class="text-center mb-4 fw-bold" style="font-size: 1.1rem;">สร้างบัญชีใหม่</h5>
            <form id="registerForm">
                <div class="form-scrollable mb-3">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">ชื่อจริง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" placeholder="ชื่อ" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" placeholder="นามสกุล" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">ชื่อเล่น</label>
                            <input type="text" class="form-control" name="nick_name" placeholder="ชื่อเล่น">
                        </div>
                        <div class="col-6">
                            <label class="form-label">เบอร์โทร <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone_no" placeholder="08x-xxx-xxxx" required>
                        </div>
                    </div>

                    <div class="p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <h6 class="fw-bold mb-3 text-center" style="font-size: 0.85rem; color: #64748b;">
                            <i class="fa-solid fa-location-dot me-1"></i>ที่อยู่จัดส่ง
                        </h6>
                        
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">บ้านเลขที่ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="home_no" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">หมู่</label>
                                <input type="text" class="form-control" name="moo">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">ซอย</label>
                                <input type="text" class="form-control" name="soi">
                            </div>
                            <div class="col-6">
                                <label class="form-label">ถนน</label>
                                <input type="text" class="form-control" name="road">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                                <select class="form-select" id="province" name="prov_id" required>
                                    <option value="">เลือก</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">อำเภอ <span class="text-danger">*</span></label>
                                <select class="form-select" id="district" name="dist_id" disabled required>
                                    <option value="">เลือก</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">ตำบล <span class="text-danger">*</span></label>
                                <select class="form-select" id="subdistrict" name="sub_dist_id" disabled required>
                                    <option value="">เลือก</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">รหัสไปรษณีย์</label>
                                <input type="text" class="form-control bg-light" id="zipcode" name="zip_code" readonly>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">หมายเหตุที่อยู่</label>
                            <textarea class="form-control" name="remark" rows="2" placeholder="จุดสังเกต..."></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" placeholder="example@email.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" placeholder="ภาษาอังกฤษหรือตัวเลข" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" class="form-control pass-input" name="password" id="reg_password" placeholder="6 ตัวขึ้นไป" required>
                                <button type="button" class="toggle-password"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">ยืนยันรหัส <span class="text-danger">*</span></label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" class="form-control pass-input" id="reg_confirm_password" placeholder="พิมพ์อีกครั้ง" required>
                                <button type="button" class="toggle-password"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                </div>
                <button type="submit" class="btn btn-submit mb-3">ลงทะเบียนสมาชิก</button>
                <div class="text-center small text-muted">
                    มีบัญชีแล้ว? <a href="javascript:void(0)" onclick="toggleForm('login')" class="text-link">เข้าสู่ระบบ</a>
                </div>
            </form>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Toggle Password
        $('.toggle-password').click(function() {
            const input = $(this).siblings('.pass-input');
            const icon = $(this).find('i');
            const isPass = input.attr('type') === 'password';
            input.attr('type', isPass ? 'text' : 'password');
            icon.toggleClass('fa-eye fa-eye-slash');
        });

        window.toggleForm = function(type) {
            if (type === 'reg') {
                $('#login-section').fadeOut(250, function() {
                    $('#register-section').fadeIn(250);
                    loadProvinces();
                });
            } else {
                $('#register-section').fadeOut(250, function() {
                    $('#login-section').fadeIn(250);
                });
            }
        };

        function loadProvinces() {
            if($('#province option').length <= 1) { 
                $.get('../api/location/get_provinces.php', function(data) {
                    $('#province').append(data);
                });
            }
        }

        $('#province').change(function() {
            let prov_id = $(this).val();
            $('#district').html('<option value="">เลือก</option>').prop('disabled', true);
            $('#subdistrict').html('<option value="">เลือก</option>').prop('disabled', true);
            $('#zipcode').val('');
            if (prov_id) {
                $.get('../api/location/get_districts.php', { prov_id: prov_id }, function(data) {
                    $('#district').append(data).prop('disabled', false);
                });
            }
        });

        $('#district').change(function() {
            let dist_id = $(this).val();
            $('#subdistrict').html('<option value="">เลือก</option>').prop('disabled', true);
            $('#zipcode').val('');
            if (dist_id) {
                $.get('../api/location/get_subdistricts.php', { dist_id: dist_id }, function(data) {
                    $('#subdistrict').append(data).prop('disabled', false);
                });
            }
        });

        $('#subdistrict').change(function() {
            let zip = $(this).find(':selected').data('zip');
            $('#zipcode').val(zip ? zip : '');
        });

        // Register
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            if ($('#reg_password').val() !== $('#reg_confirm_password').val()) {
                Swal.fire({ icon: 'warning', title: 'รหัสผ่านไม่ตรงกัน', text: 'กรุณาตรวจสอบอีกครั้ง' });
                return; 
            }
            Swal.fire({ title: 'กำลังตรวจสอบ...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            $.ajax({
                url: '../api/auth/register.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'สมัครสำเร็จ!', text: 'เข้าสู่ระบบได้เลย', confirmButtonColor: '#6366f1' })
                        .then(() => { toggleForm('login'); $('#registerForm')[0].reset(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: res.message });
                    }
                },
                error: () => { Swal.fire('ผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์', 'error'); }
            });
        });

        // Login
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({ title: 'กำลังเข้าสู่ระบบ...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            let loginData = $(this).serializeArray();
            loginData.push({name: "remember", value: $('#remember').is(':checked')});
            $.ajax({
                url: '../api/auth/login.php', type: 'POST', data: loginData, dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'เข้าสู่ระบบสำเร็จ', timer: 1200, showConfirmButton: false })
                        .then(() => { window.location.href = 'dashboard.php'; });
                    } else {
                        Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: res.message });
                    }
                }
            });
        });
    });

    function forgotPassword() {
        Swal.fire({
            title: 'ลืมรหัสผ่าน?',
            html: `
                <input type="text" id="fw_username" class="swal2-input" placeholder="ชื่อผู้ใช้งาน">
                <input type="email" id="fw_email" class="swal2-input" placeholder="อีเมลที่ลงทะเบียน">
            `,
            confirmButtonText: 'ตั้งรหัสใหม่',
            confirmButtonColor: '#6366f1',
            showCancelButton: true,
            cancelButtonText: 'ยกเลิก',
            preConfirm: () => {
                const user = Swal.getPopup().querySelector('#fw_username').value;
                const email = Swal.getPopup().querySelector('#fw_email').value;
                if (!user || !email) { Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบ'); }
                return { username: user, email: email };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'กำลังตรวจสอบ...', didOpen: () => { Swal.showLoading(); } });
                $.post('../api/auth/forgot_password.php', result.value, function(res) {
                    res.status === 'success' ? Swal.fire('สำเร็จ', res.message, 'success') : Swal.fire('ข้อผิดพลาด', res.message, 'error');
                }, 'json');
            }
        });
    }
</script>
</body>
</html>