<?php
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. รับค่าข้อมูลส่วนตัว
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $nick_name = $_POST['nick_name'] ?? '';
    $phone_no = $_POST['phone_no'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 2. รับค่าข้อมูลที่อยู่
    $home_no = $_POST['home_no'] ?? '';
    $moo = $_POST['moo'] ?? '';
    $soi = $_POST['soi'] ?? '';
    $road = $_POST['road'] ?? '';
    $sub_dist_id = $_POST['sub_dist_id'] ?? 0;
    $remark = $_POST['remark'] ?? '';

    // ตรวจสอบข้อมูลเบื้องต้น
    if (empty($username) || empty($password) || empty($first_name) || empty($sub_dist_id)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน']);
        exit;
    }

    // เข้ารหัสรหัสผ่านเพื่อความปลอดภัย
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // เริ่มการทำงานแบบ Transaction (บันทึก 2 ตารางพร้อมกัน ถ้าพังให้ยกเลิกทั้งหมด)
    $conn->begin_transaction();

    try {
        // เช็คก่อนว่า Username หรือ Email ซ้ำไหมในตาราง users
        $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            throw new Exception("ชื่อผู้ใช้งาน (Username) หรือ อีเมลนี้ มีในระบบแล้ว");
        }
        $stmt_check->close();

        // ขั้นตอนที่ 1: บันทึกข้อมูลลงตาราง addresses
        $sql_addr = "INSERT INTO addresses (home_no, moo, soi, road, remark, sub_dist_id) 
                     VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_addr = $conn->prepare($sql_addr);
        $stmt_addr->bind_param("sssssi", $home_no, $moo, $soi, $road, $remark, $sub_dist_id);
        $stmt_addr->execute();
        
        // ดึงไอดีที่อยู่ที่เพิ่งบันทึกสำเร็จ
        $address_id = $conn->insert_id; 
        $stmt_addr->close();

        // ขั้นตอนที่ 2: บันทึกข้อมูลลงตาราง users พร้อมแนบ address_id
        $role = 'customer'; // กำหนดให้คนที่สมัครเองผ่านหน้าเว็บเป็น customer เสมอ
        $user_status = 1; // 1 = เปิดใช้งาน

        $sql_user = "INSERT INTO users (username, password, first_name, last_name, nick_name, phone_no, email, address_id, user_status, role) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("sssssssiss", $username, $hashed_password, $first_name, $last_name, $nick_name, $phone_no, $email, $address_id, $user_status, $role);
        $stmt_user->execute();
        $stmt_user->close();

        // ยืนยันการบันทึกข้อมูล
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'สมัครสมาชิกสำเร็จ']);

    } catch (Exception $e) {
        // หากมี Error ให้ Rollback ยกเลิกการกระทำทั้งหมด
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
}
?>