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
            $type_name = trim($_POST['type_name'] ?? '');
            if (empty($type_name)) {
                echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อหมวดหมู่']);
                exit;
            }
            // เช็คซ้ำ
            $chk = $conn->prepare("SELECT type_id FROM prod_types WHERE type_name = ?");
            $chk->bind_param("s", $type_name);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'ชื่อหมวดหมู่นี้มีในระบบแล้ว']);
                $chk->close();
                exit;
            }
            $chk->close();

            $stmt = $conn->prepare("INSERT INTO prod_types (type_name, type_status) VALUES (?, 1)");
            $stmt->bind_param("s", $type_name);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเพิ่มหมวดหมู่ได้']);
            }
            $stmt->close();
            break;

        case 'update':
            $type_id = intval($_POST['type_id'] ?? 0);
            $type_name = trim($_POST['type_name'] ?? '');
            if (empty($type_name) || $type_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }
            // เช็คซ้ำ (ยกเว้นตัวเอง)
            $chk = $conn->prepare("SELECT type_id FROM prod_types WHERE type_name = ? AND type_id != ?");
            $chk->bind_param("si", $type_name, $type_id);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'ชื่อหมวดหมู่นี้มีในระบบแล้ว']);
                $chk->close();
                exit;
            }
            $chk->close();

            $stmt = $conn->prepare("UPDATE prod_types SET type_name = ? WHERE type_id = ?");
            $stmt->bind_param("si", $type_name, $type_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอัปเดตได้']);
            }
            $stmt->close();
            break;

        case 'toggle_status':
            $type_id = intval($_POST['type_id'] ?? 0);
            $type_status = intval($_POST['type_status'] ?? 0);
            if ($type_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE prod_types SET type_status = ? WHERE type_id = ?");
            $stmt->bind_param("ii", $type_status, $type_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเปลี่ยนสถานะได้']);
            }
            $stmt->close();
            break;

        case 'delete':
            $type_id = intval($_POST['type_id'] ?? 0);
            if ($type_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            // เช็คว่ามีสินค้าใช้หมวดหมู่นี้อยู่หรือไม่
            $chk_prod = $conn->prepare("SELECT COUNT(*) as cnt FROM products WHERE type_id = ?");
            $chk_prod->bind_param("i", $type_id);
            $chk_prod->execute();
            $result = $chk_prod->get_result()->fetch_assoc();
            $chk_prod->close();

            if ($result['cnt'] > 0) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากหมวดหมู่นี้ถูกใช้งานกับสินค้า ' . $result['cnt'] . ' รายการ']);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM prod_types WHERE type_id = ?");
            $stmt->bind_param("i", $type_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบข้อมูลได้: ' . $conn->error]);
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
