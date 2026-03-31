<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน | V-SHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .forgot-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        .card-header-shop {
            background: #e67e22;
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-up { animation: slideUp 0.4s ease-out; }
    </style>
</head>
<body>

<div class="forgot-card animate-up">
    <div class="card-header-shop">
        <i class="fa-solid fa-lock fa-3x mb-3"></i>
        <h3 class="fw-bold mb-0">ลืมรหัสผ่าน?</h3>
        <p class="small mb-0 opacity-75">ไม่ต้องกังวล เราช่วยคุณได้</p>
    </div>

    <div class="p-4 p-md-5">
        <div class="text-center mb-4">
            <i class="fa-solid fa-store fa-4x text-primary opacity-50 mb-3"></i>
            <h5 class="fw-bold text-dark">กรุณาติดต่อที่ร้านค้า</h5>
            <p class="text-muted">เพื่อความปลอดภัย การรีเซ็ตรหัสผ่านจะต้องดำเนินการโดยเจ้าหน้าที่ของเราโดยตรง</p>
        </div>

        <div class="card bg-light border-0 rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="fa-solid fa-phone text-success fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">โทรศัพท์</small>
                        <strong>02-xxx-xxxx</strong>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="fa-brands fa-line text-success fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">LINE ID</small>
                        <strong>@v-shop</strong>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                        <i class="fa-solid fa-envelope text-primary fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">อีเมล</small>
                        <strong>admin@v-shop.com</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info text-center small mb-4 rounded-3">
            <i class="fa-solid fa-clock me-1"></i> เวลาทำการ: จันทร์ - เสาร์ 09:00 - 18:00
        </div>

        <a href="login.php" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i>กลับหน้าเข้าสู่ระบบ
        </a>
    </div>
</div>

</body>
</html>
