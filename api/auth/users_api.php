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
        case 'toggle_status':
            $user_id = intval($_POST['user_id'] ?? 0);
            $user_status = intval($_POST['user_status'] ?? 0);

            if ($user_id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
                exit;
            }

            // ห้ามเปลี่ยนสถานะตัวเอง
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเปลี่ยนสถานะของตัวเองได้']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE users SET user_status = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $user_status, $user_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอัปเดตสถานะได้']);
            }
            $stmt->close();
            break;

        case 'add':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $nick_name = trim($_POST['nick_name'] ?? '');
            $phone_no = trim($_POST['phone_no'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'customer';

            if (empty($username) || empty($password) || empty($first_name) || empty($last_name) || empty($email)) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            // เช็ค Username ซ้ำ
            $chk1 = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $chk1->bind_param("s", $username);
            $chk1->execute();
            if($chk1->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว']);
                $chk1->close();
                exit;
            }
            $chk1->close();

            // เช็คอีเมลซ้ำ
            $chk2 = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $chk2->bind_param("s", $email);
            $chk2->execute();
            if($chk2->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'อีเมลนี้ถูกใช้แล้ว']);
                $chk2->close();
                exit;
            }
            $chk2->close();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, first_name, last_name, nick_name, phone_no, email, role, user_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $username, $hashed_password, $first_name, $last_name, $nick_name, $phone_no, $email, $role);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เพิ่มข้อมูลไม่สำเร็จ: ' . $conn->error]);
            }
            $stmt->close();
            break;

        case 'edit':
            $user_id = intval($_POST['user_id'] ?? 0);
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $nick_name = trim($_POST['nick_name'] ?? '');
            $phone_no = trim($_POST['phone_no'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'customer';

            if ($user_id <= 0 || empty($first_name) || empty($last_name) || empty($email)) {
                echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
                exit;
            }

            // เช็คอีเมลซ้ำกับคนอื่นไหม
            $chk = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $chk->bind_param("si", $email, $user_id);
            $chk->execute();
            if($chk->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'อีเมลนี้ถูกใช้แล้ว']);
                $chk->close();
                exit;
            }
            $chk->close();

            $sql = "UPDATE users SET first_name=?, last_name=?, nick_name=?, phone_no=?, email=?, role=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $first_name, $last_name, $nick_name, $phone_no, $email, $role, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'แก้ไขข้อมูลไม่สำเร็จ']);
            }
            $stmt->close();
            break;

        case 'delete':
            $user_id = intval($_POST['user_id'] ?? 0);
            
            if ($user_id <= 0 || $user_id == $_SESSION['user_id']) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบตัวเองหรือข้อมูลไม่ถูกต้องได้']);
                exit;
            }
            
            $conn->begin_transaction();
            try {
                $sql_del = "DELETE FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($sql_del);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $conn->commit();
                    echo json_encode(['status' => 'success', 'message' => 'ลบผู้ใช้งานออกจากระบบเรียบร้อยแล้ว']);
                } else {
                    throw new Exception("ไม่พบผู้ใช้งานที่ต้องการลบ");
                }
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                // 1451 Cannot delete or update a parent row: a foreign key constraint fails
                if ($e->getCode() == 1451) {
                    $soft = $conn->prepare("UPDATE users SET user_status = 0 WHERE user_id = ?");
                    $soft->bind_param("i", $user_id);
                    $soft->execute();
                    echo json_encode(['status' => 'success', 'message' => 'ผู้ใช้มีประวัติการทำรายการ ระบบจึงทำการระงับบัญชี (Soft Delete) เพื่อรักษาข้อมูลทางบัญชีแทน']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
                }
            }
            break;

        case 'reset_password':
            $user_id = intval($_POST['user_id'] ?? 0);
            $new_password = $_POST['new_password'] ?? '';

            if ($user_id <= 0 || empty($new_password) || strlen($new_password) < 6) {
                echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร']);
                exit;
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้']);
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
