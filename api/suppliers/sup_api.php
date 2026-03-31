<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $sp_name = trim($_POST['sp_name'] ?? '');
            $sp_tax = trim($_POST['sp_tax'] ?? '');
            $sp_email = trim($_POST['sp_email'] ?? '');
            $ct_first_name = trim($_POST['ct_first_name'] ?? '');
            $ct_phone = trim($_POST['ct_phone'] ?? '');

            if (empty($sp_name)) {
                echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อบริษัท/ซัพพลายเออร์']);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO supplers (sp_name, sp_tax, sp_email, ct_first_name, ct_phone, sp_status) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssss", $sp_name, $sp_tax, $sp_email, $ct_first_name, $ct_phone);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'บันทึกข้อมูลไม่สำเร็จ: ' . $conn->error]);
            }
            $stmt->close();
            break;

        case 'update':
            $sp_id = intval($_POST['sp_id'] ?? 0);
            $sp_name = trim($_POST['sp_name'] ?? '');
            $sp_tax = trim($_POST['sp_tax'] ?? '');
            $sp_email = trim($_POST['sp_email'] ?? '');
            $ct_first_name = trim($_POST['ct_first_name'] ?? '');
            $ct_phone = trim($_POST['ct_phone'] ?? '');

            if (empty($sp_name) || $sp_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE supplers SET sp_name=?, sp_tax=?, sp_email=?, ct_first_name=?, ct_phone=? WHERE sp_id=?");
            $stmt->bind_param("sssssi", $sp_name, $sp_tax, $sp_email, $ct_first_name, $ct_phone, $sp_id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'อัปเดตข้อมูลไม่สำเร็จ']);
            }
            $stmt->close();
            break;

        case 'delete':
            $sp_id = intval($_POST['sp_id'] ?? 0);
            if ($sp_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            // เช็คว่าเคยมีบิลสั่งซื้อหรือไม่
            $chk = $conn->prepare("SELECT COUNT(*) as c FROM bill_purchases WHERE sp_id = ?");
            $chk->bind_param("i", $sp_id);
            $chk->execute();
            $cnt = $chk->get_result()->fetch_assoc()['c'];
            $chk->close();

            if($cnt > 0) {
                echo json_encode(['status' => 'error', 'message' => "ซัพพลายเออร์นี้มีประวัติการสั่งซื้อ $cnt รายการ ไม่สามารถลบได้"]);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM supplers WHERE sp_id = ?");
            $stmt->bind_param("i", $sp_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบข้อมูลได้']);
            }
            $stmt->close();
            break;

        case 'toggle_status':
            $sp_id = intval($_POST['sp_id'] ?? 0);
            $sp_status = intval($_POST['sp_status'] ?? 0);

            $stmt = $conn->prepare("UPDATE supplers SET sp_status = ? WHERE sp_id = ?");
            $stmt->bind_param("ii", $sp_status, $sp_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เปลี่ยนสถานะไม่สำเร็จ']);
            }
            $stmt->close();
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
