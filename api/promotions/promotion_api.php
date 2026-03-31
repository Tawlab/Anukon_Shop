<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $promo_code = strtoupper(trim($_POST['promo_code'] ?? ''));
            $discount_percent = intval($_POST['discount_percent'] ?? 0);
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';

            if (empty($promo_code) || $discount_percent <= 0 || empty($start_date) || empty($end_date)) {
                echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
                exit;
            }

            // เช็คโค้ดซ้ำ
            $chk = $conn->prepare("SELECT promo_id FROM promotions WHERE promo_code = ?");
            $chk->bind_param("s", $promo_code);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'โค้ดส่วนลดนี้มีในระบบแล้ว']);
                $chk->close();
                exit;
            }
            $chk->close();

            $sql = "INSERT INTO promotions (promo_code, discount_percent, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siss", $promo_code, $discount_percent, $start_date, $end_date);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเพิ่มโปรโมชั่นได้']);
            }
            $stmt->close();
            break;

        case 'toggle':
            $promo_id = intval($_POST['promo_id'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 0);

            $stmt = $conn->prepare("UPDATE promotions SET is_active = ? WHERE promo_id = ?");
            $stmt->bind_param("ii", $is_active, $promo_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'อัปเดตสถานะไม่สำเร็จ']);
            }
            $stmt->close();
            break;

        case 'delete':
            $promo_id = intval($_POST['promo_id'] ?? 0);

            $stmt = $conn->prepare("DELETE FROM promotions WHERE promo_id = ?");
            $stmt->bind_param("i", $promo_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ลบไม่สำเร็จ']);
            }
            $stmt->close();
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
    
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
