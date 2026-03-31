<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | V-SHOP</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-blue: #0056b3;
            --soft-gray: #f8f9fa;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 550px; 
            overflow: hidden;
            border: none;
        }

        .card-header-shop {
            background: var(--primary-blue);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }

        .form-label { font-weight: 500; font-size: 0.9rem; margin-bottom: 5px; }
        .input-group-text { background: white; border-right: none; color: #adb5bd; }
        .form-control, .form-select { border-left: none; padding: 11px; font-size: 0.95rem; }
        .form-control:focus, .form-select:focus { border-color: #dee2e6; box-shadow: none; }

        .btn-shop {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 13px;
            border-radius: 10px;
            font-weight: 500;
            width: 100%;
            transition: 0.3s;
        }
        .btn-shop:hover { background: #004494; transform: translateY(-1px); }

        .toggle-password { cursor: pointer; background: white; border-left: none; color: #adb5bd; }
        .text-danger { font-size: 0.85rem; }

        /* พื้นที่ฟอร์มสามารถ Scroll ได้เพื่อรองรับช่องกรอกที่เยอะขึ้น */
        .form-scrollable {
            max-height: 60vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 10px;
        }
        .form-scrollable::-webkit-scrollbar { width: 5px; }
        .form-scrollable::-webkit-scrollbar-thumb { background: #ccc; border-radius: 5px; }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-up { animation: slideUp 0.4s ease-out; }
    </style>
</head>
<body>

<div class="auth-card animate-up">
    <div class="card-header-shop">
        <i class="fa-solid fa-bag-shopping fa-3x mb-3"></i> 
        <h3 class="fw-bold mb-0">V-SHOP</h3>
        <p class="small mb-0 opacity-75">ร้านสะดวกซื้อออนไลน์</p>
    </div>

    <div class="p-4 p-md-5 pt-md-4">
        
        <div id="login-section">
            <h5 class="text-center mb-4 fw-bold">ยินดีต้อนรับ</h5>
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ใช้งาน <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-circle-user"></i></span>
                        <input type="text" class="form-control" name="username" placeholder="กรอกชื่อผู้ใช้งาน" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                        <input type="password" class="form-control pass-input" name="password" placeholder="กรอกรหัสผ่าน" required>
                        <span class="input-group-text toggle-password"><i class="fa-solid fa-eye"></i></span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label small" for="remember">จดจำฉันไว้</label>
                    </div>
                    <a href="javascript:void(0)" onclick="forgotPassword()" class="small text-decoration-none text-primary">ลืมรหัสผ่าน?</a>
                </div>

                <button type="submit" class="btn btn-shop mb-3 shadow-sm">เข้าสู่ระบบ</button>
                <div class="text-center small">
                    ยังไม่มีบัญชี? <a href="javascript:void(0)" onclick="toggleForm('reg')" class="text-primary fw-bold text-decoration-none">เริ่มสมัครสมาชิก</a>
                </div>
            </form>
        </div>

        <div id="register-section" style="display: none;">
            <h5 class="text-center mb-4 fw-bold">สร้างบัญชีใหม่</h5>
            <form id="registerForm">
                <div class="form-scrollable mb-3">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">ชื่อจริง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-start" name="first_name" placeholder="ชื่อ" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-start" name="last_name" placeholder="นามสกุล" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">ชื่อเล่น <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-start" name="nick_name" placeholder="ชื่อเล่น" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-start" name="phone_no" placeholder="08x-xxx-xxxx" required>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded mb-3 border">
                        <h6 class="fw-bold mb-3 text-secondary text-center">ข้อมูลที่อยู่จัดส่ง</h6>
                        
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">บ้านเลขที่ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-start" name="home_no" placeholder="บ้านเลขที่" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">หมู่</label>
                                <input type="text" class="form-control border-start" name="moo" placeholder="หมู่ที่">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">ซอย</label>
                                <input type="text" class="form-control border-start" name="soi" placeholder="ซอย">
                            </div>
                            <div class="col-6">
                                <label class="form-label">ถนน</label>
                                <input type="text" class="form-control border-start" name="road" placeholder="ถนน">
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                                <select class="form-select border-start" id="province" name="prov_id" required>
                                    <option value="">เลือกจังหวัด</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">อำเภอ/เขต <span class="text-danger">*</span></label>
                                <select class="form-select border-start" id="district" name="dist_id" disabled required>
                                    <option value="">เลือกอำเภอ</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">ตำบล/แขวง <span class="text-danger">*</span></label>
                                <select class="form-select border-start" id="subdistrict" name="sub_dist_id" disabled required>
                                    <option value="">เลือกตำบล</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">รหัสไปรษณีย์</label>
                                <input type="text" class="form-control border-start bg-light" id="zipcode" name="zip_code" readonly>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">รายละเอียดเพิ่มเติมที่อยู่ (Remark)</label>
                            <textarea class="form-control border-start" name="remark" rows="2" placeholder="เช่น จุดสังเกต, ฝากไว้ที่นิติบุคคล..."></textarea>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อีเมล</label>
                        <input type="email" class="form-control border-start" name="email" placeholder="example@email.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control border-start" name="username" placeholder="ภาษาอังกฤษหรือตัวเลข" required>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label">ตั้งรหัสผ่าน <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control border-start pass-input" name="password" id="reg_password" placeholder="6 ตัวอักษรขึ้นไป" required>
                                <span class="input-group-text toggle-password"><i class="fa-solid fa-eye"></i></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control border-start pass-input" id="reg_confirm_password" placeholder="พิมพ์อีกครั้ง" required>
                                <span class="input-group-text toggle-password"><i class="fa-solid fa-eye"></i></span>
                            </div>
                        </div>
                    </div>

                </div> <button type="submit" class="btn btn-shop mb-3 shadow-sm">ลงทะเบียนสมาชิก</button>
                <div class="text-center small">
                    มีบัญชีอยู่แล้ว? <a href="javascript:void(0)" onclick="toggleForm('login')" class="text-primary fw-bold text-decoration-none">กลับไปหน้าล็อกอิน</a>
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

        // สลับฟอร์ม
        window.toggleForm = function(type) {
            if (type === 'reg') {
                $('#login-section').fadeOut(300, function() {
                    $('#register-section').fadeIn(300);
                    loadProvinces();
                });
            } else {
                $('#register-section').fadeOut(300, function() {
                    $('#login-section').fadeIn(300);
                });
            }
        };

        // ================= ดึงข้อมูลที่อยู่ (AJAX) =================
        function loadProvinces() {
            if($('#province option').length <= 1) { 
                $.get('../api/location/get_provinces.php', function(data) {
                    $('#province').append(data);
                });
            }
        }

        $('#province').change(function() {
            let prov_id = $(this).val();
            $('#district').html('<option value="">เลือกอำเภอ</option>').prop('disabled', true);
            $('#subdistrict').html('<option value="">เลือกตำบล</option>').prop('disabled', true);
            $('#zipcode').val('');

            if (prov_id) {
                $.get('../api/location/get_districts.php', { prov_id: prov_id }, function(data) {
                    $('#district').append(data).prop('disabled', false);
                });
            }
        });

        $('#district').change(function() {
            let dist_id = $(this).val();
            $('#subdistrict').html('<option value="">เลือกตำบล</option>').prop('disabled', true);
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
        // =======================================================

        // ส่งฟอร์มสมัครสมาชิก
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            
            if ($('#reg_password').val() !== $('#reg_confirm_password').val()) {
                Swal.fire({ icon: 'warning', title: 'ข้อผิดพลาด', text: 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน' });
                return; 
            }

            Swal.fire({ title: 'กำลังตรวจสอบข้อมูล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            $.ajax({
                url: '../api/auth/register.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success', title: 'สมัครสมาชิกสำเร็จ!', text: 'คุณสามารถเข้าสู่ระบบได้ทันที',
                            confirmButtonColor: '#0056b3'
                        }).then(() => {
                            toggleForm('login');
                            $('#registerForm')[0].reset();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'สมัครสมาชิกไม่สำเร็จ', text: res.message });
                    }
                },
                error: function() {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error');
                }
            });
        });

        // ส่งฟอร์ม Login
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({ title: 'กำลังเข้าสู่ระบบ...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            let loginData = $(this).serializeArray();
            loginData.push({name: "remember", value: $('#remember').is(':checked')});

            $.ajax({
                url: '../api/auth/login.php',
                type: 'POST',
                data: loginData,
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success', title: 'เข้าสู่ระบบสำเร็จ', timer: 1500, showConfirmButton: false
                        }).then(() => { window.location.href = 'dashboard.php'; });
                    } else {
                        Swal.fire({ icon: 'error', title: 'เข้าสู่ระบบไม่สำเร็จ', text: res.message });
                    }
                }
            });
        });
    });

    // ฟังก์ชันลืมรหัสผ่าน
    function forgotPassword() {
        Swal.fire({
            title: 'ลืมรหัสผ่าน?',
            html: `
                <input type="text" id="fw_username" class="swal2-input" placeholder="ชื่อผู้ใช้งาน (Username)">
                <input type="email" id="fw_email" class="swal2-input" placeholder="อีเมลที่ลงทะเบียนไว้">
            `,
            confirmButtonText: 'ตั้งรหัสผ่านใหม่',
            showCancelButton: true,
            cancelButtonText: 'ยกเลิก',
            preConfirm: () => {
                const user = Swal.getPopup().querySelector('#fw_username').value;
                const email = Swal.getPopup().querySelector('#fw_email').value;
                if (!user || !email) {
                    Swal.showValidationMessage(`กรุณากรอกข้อมูลให้ครบถ้วน`);
                }
                return { username: user, email: email };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'กำลังตรวจสอบ...', didOpen: () => { Swal.showLoading(); } });
                $.post('../api/auth/forgot_password.php', result.value, function(res) {
                    if (res.status === 'success') {
                        Swal.fire('สำเร็จ', res.message, 'success');
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message, 'error');
                    }
                }, 'json');
            }
        });
    }
</script>
</body>
</html>