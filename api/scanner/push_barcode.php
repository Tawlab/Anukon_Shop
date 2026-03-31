<?php
require_once '../../config/db_config.php';
header('Content-Type: application/json');

// อนุญาตให้ทุกต้นทางเรียกใช้ได้เพื่อความสะดวกตอนเทสมือถือ (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = $_POST['barcode'] ?? '';

    if (empty($barcode)) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่มีบาร์โค้ดส่งมา']);
        exit;
    }

    // บันทึกลงตาราง scan_queue (สถานะ 0 คือ รอดำเนินการฝั่งคอมพิวเตอร์)
    // ก่อนบันทึก เช็คก่อนว่าเพิ่งสแกนบาร์โค้ดนี้ซ้ำไปภายใน 5 วินาทีหรือไม่ (กันกดย้ำ)
    $chk_sql = "SELECT id FROM scan_queue WHERE barcode = ? AND status = 0 AND created_at >= NOW() - INTERVAL 5 SECOND";
    $stmt_chk = $conn->prepare($chk_sql);
    $stmt_chk->bind_param("s", $barcode);
    $stmt_chk->execute();
    
    if ($stmt_chk->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'duplicate', 'message' => 'ข้อมูลถูกสแกนแล้ว']);
        $stmt_chk->close();
        exit;
    }
    $stmt_chk->close();

    $sql = "INSERT INTO scan_queue (barcode, status) VALUES (?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $barcode);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'ส่งข้อมูลสำเร็จ']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ: ' . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Method']);
}
?>
