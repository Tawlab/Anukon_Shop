<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน | Anukon Shop</title>
    <!-- CSS & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-color: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background: var(--bg-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
        }
        .auth-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            padding: 40px;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: #eff6ff;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 24px;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 0.95rem;
        }
        .form-control:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        .back-link {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-6">
            <div class="auth-card mx-auto">
                <div class="text-center mb-4">
                    <div class="icon-circle">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <h3 class="fw-bold mb-2">ลืมรหัสผ่าน?</h3>
                    <p class="text-muted">กรุณากรอกชื่อผู้ใช้ (Username) และอีเมลของท่าน <br>ระบบจะทำการสร้างรหัสผ่านใหม่ชั่วคราวให้</p>
                </div>

                <form id="forgotForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">ชื่อผู้ใช้ (Username)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted px-3"><i class="fa-solid fa-user"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" name="username" placeholder="Username ของคุณ" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">อีเมล (Email)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted px-3"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" class="form-control border-start-0 ps-0" name="email" placeholder="example@email.com" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-4" id="btnSubmit">
                        <i class="fa-solid fa-paper-plane me-2"></i>ยืนยันขอกู้รหัสผ่าน
                    </button>

                    <div class="text-center">
                        <a href="login.php" class="back-link"><i class="fa-solid fa-arrow-left me-2"></i>กลับไปหน้าเข้าสู่ระบบ</a>
                    </div>
                </form>
            </div>
            
            <div class="text-center mt-4 text-muted small">
                &copy; <?php echo date('Y'); ?> Anukon Shop. All rights reserved.
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#forgotForm').on('submit', function(e) {
        e.preventDefault();
        
        let btn = $('#btnSubmit');
        let ogText = btn.html();
        
        btn.html('<i class="fa-solid fa-spinner fa-spin me-2"></i>กำลังตรวจสอบ...').prop('disabled', true);
        
        $.ajax({
            url: '../api/auth/forgot_password.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                btn.html(ogText).prop('disabled', false);
                
                if (res.status === 'success') {
                    // Success! Show temp password
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        html: res.message, // The API returns the HTML formatted message with the new password
                        confirmButtonText: 'ไปหน้าเข้าสู่ระบบ',
                        confirmButtonColor: '#6366f1',
                        allowOutsideClick: false
                    }).then((result) => {
                        window.location.href = 'login.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: res.message,
                        confirmButtonColor: '#ef4444'
                    });
                }
            },
            error: function() {
                btn.html(ogText).prop('disabled', false);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    });
});
</script>

</body>
</html>
