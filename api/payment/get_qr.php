<?php
require_once '../../includes/promptpay.php';

$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
// สมมติบัญชีพร้อมเพย์ของร้านเป็นเบอร์โทร: 0812345678 (เปลี่ยนเป็นของจริงได้)
$pp_number = '0812345678'; 

$payload = PromptPay::generatePayload($pp_number, $amount);

// เราจะส่งคืน URL ของรูปภาพ QR Code ที่มาจาก API สาธารณะ โดยใช้ payload ทึ่เข้ารหัสถูกต้องตามมาตรฐาน PromptPay แล้ว (Lib ของ PHP)
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($payload);

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'qr_url' => $qr_url, 'amount' => $amount]);
?>
