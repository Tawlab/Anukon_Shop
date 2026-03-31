<?php
session_start();
// ยอมให้ทุกคนที่มีลิ้งก์เข้าสแกน หรือจะล็อคให้เฉพาะ admin ก็ได้
// ถ้าจะให้ลูกน้องล็อคอินด้วยโทรศัพท์ ก็ต้องเช็ค $_SESSION['role']
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner มือถือ | V-SHOP</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- html5-qrcode library -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f8f9fa; }
        #reader { width: 100%; max-width: 500px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .success-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(25, 135, 84, 0.9); z-index: 1000; display: none; align-items: center; justify-content: center; flex-direction: column; color: white; }
    </style>
</head>
<body>
    <div class="container py-4 text-center">
        <h3 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-barcode me-2"></i>V-Scanner</h3>
        <p class="text-muted">หันกล้องมือถือไปที่บาร์โค้ดของสินค้า ข้อมูลจะซิงค์ไปที่คอมพิวเตอร์ของคุณอัตโนมัติ</p>

        <div id="reader"></div>

        <div class="mt-4">
            <button class="btn btn-outline-secondary" onclick="window.location.reload()"><i class="fa-solid fa-rotate-right me-1"></i>รีเฟรชกล้อง</button>
            <a href="inventory.php" class="btn btn-outline-primary ms-2"><i class="fa-solid fa-home me-1"></i>กลับหน้าคลัง</a>
        </div>
    </div>

    <!-- แอนิเมชันสำเร็จ -->
    <div class="success-overlay" id="successOverlay">
        <i class="fa-solid fa-check-circle fa-5x mb-3 animate__animated animate__zoomIn"></i>
        <h2 class="fw-bold">ส่งข้อมูลสำเร็จ!</h2>
        <p>กำลังเตรียมสแกนชิ้นต่อไป...</p>
    </div>

    <script>
        let html5QrcodeScanner = null;
        let lastScanTime = 0;

        function onScanSuccess(decodedText, decodedResult) {
            let currentTime = new Date().getTime();
            // ป้องกันการยิงซ้ำรัวๆ ภายใน 3 วินาที
            if (currentTime - lastScanTime < 3000) return;
            lastScanTime = currentTime;

            // หยุดกล้องชั่วคราว
            if(html5QrcodeScanner) {
                html5QrcodeScanner.pause(true);
            }

            // ส่งค่าไปที่ API
            $.ajax({
                url: '../api/scanner/push_barcode.php',
                type: 'POST',
                data: { barcode: decodedText.trim() },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        $('#successOverlay').css('display', 'flex');
                        setTimeout(() => {
                            $('#successOverlay').fadeOut(300, function() {
                                if(html5QrcodeScanner) html5QrcodeScanner.resume();
                            });
                        }, 1500);
                    } else if (res.status === 'duplicate') {
                        if(html5QrcodeScanner) html5QrcodeScanner.resume();
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', res.message, 'error').then(() => {
                            if(html5QrcodeScanner) html5QrcodeScanner.resume();
                        });
                    }
                },
                error: function() {
                    Swal.fire('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', '', 'error').then(() => {
                        if(html5QrcodeScanner) html5QrcodeScanner.resume();
                    });
                }
            });
        }

        function onScanFailure(error) {
            // ไม่ต้องทำอะไร ปล่อยให้มันหาต่อไป
        }

        $(document).ready(function() {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { fps: 10, qrbox: {width: 250, height: 150}, aspectRatio: 1.0 },
                /* verbose= */ false);
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        });
    </script>
</body>
</html>
