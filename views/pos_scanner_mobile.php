<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Scanner มือถือ | V-SHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f8f9fa; }
        #reader { width: 100%; max-width: 500px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .success-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(13, 110, 253, 0.95); z-index: 1000; display: none; align-items: center; justify-content: center; flex-direction: column; color: white; }
    </style>
</head>
<body>
    <div class="container py-4 text-center">
        <h3 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-barcode me-2"></i>POS-Scanner</h3>
        <p class="text-muted">ระบบยิงบาร์โค้ดขายหน้าร้าน (ดึงข้อมูลเข้าจอคอมพิวเตอร์อัตโนมัติ)</p>

        <div id="reader"></div>

        <div class="mt-4">
            <button class="btn btn-outline-secondary" onclick="window.location.reload()"><i class="fa-solid fa-rotate-right me-1"></i>รีเฟรชกล้อง</button>
            <a href="pos.php" class="btn btn-outline-primary ms-2"><i class="fa-solid fa-desktop me-1"></i>กลับหน้าจอ POS</a>
        </div>
    </div>

    <!-- แอนิเมชันสำเร็จ -->
    <div class="success-overlay" id="successOverlay">
        <i class="fa-solid fa-cart-arrow-down fa-5x mb-3 animate__animated animate__bounceIn"></i>
        <h2 class="fw-bold">เพิ่มลงจอขายแล้ว!</h2>
        <p>สแกนชิ้นต่อไปได้เลย...</p>
    </div>

    <script>
        let html5QrcodeScanner = null;
        let lastScanTime = 0;

        function onScanSuccess(decodedText, decodedResult) {
            let currentTime = new Date().getTime();
            // ป้องกันการแสกนรัวๆ เกินไป ภายในอึดใจ (1.5 วิ) พอ
            if (currentTime - lastScanTime < 1500) return;
            lastScanTime = currentTime;

            if(html5QrcodeScanner) {
                html5QrcodeScanner.pause(true);
            }

            $.ajax({
                url: '../api/scanner/push_pos_barcode.php',
                type: 'POST',
                data: { barcode: decodedText.trim() },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        // เล่นเสียง ปิ๊บ
                        let audio = new Audio('https://www.soundjay.com/buttons/beep-07a.mp3');
                        audio.play().catch(e => {});

                        $('#successOverlay').css('display', 'flex');
                        setTimeout(() => {
                            $('#successOverlay').fadeOut(200, function() {
                                if(html5QrcodeScanner) html5QrcodeScanner.resume();
                            });
                        }, 800);
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message, 'error').then(() => {
                            if(html5QrcodeScanner) html5QrcodeScanner.resume();
                        });
                    }
                },
                error: function() {
                    Swal.fire('การเชื่อมต่อขัดข้อง', '', 'error').then(() => {
                        if(html5QrcodeScanner) html5QrcodeScanner.resume();
                    });
                }
            });
        }

        function onScanFailure(error) { }

        $(document).ready(function() {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader",
                { fps: 15, qrbox: {width: 280, height: 120}, aspectRatio: 1.0 },
                false);
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        });
    </script>
</body>
</html>
