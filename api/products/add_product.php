<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $prod_name = $_POST['prod_name'] ?? '';
    $type_id = !empty($_POST['type_id']) ? $_POST['type_id'] : null;
    $barcode = $_POST['barcode'] ?? null;
    $price = $_POST['price'] ?? 0;
    $detail = $_POST['detail'] ?? '';
    $initial_stock = 0; // บังคับเป็น 0 เพื่อรอการรับเข้า (Phase 3)
    
    // เช็คว่าสวิตช์ "เปิดขายทันที" ถูกเปิดหรือไม่ (ถ้าไม่ได้ติ๊ก ค่าจะไม่ถูกส่งมา)
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($prod_name) || empty($price)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อสินค้าและราคา']);
        exit;
    }

    // 2. จัดการอัปโหลดรูปภาพสินค้า (ถ้ามีการเลือกไฟล์)
    $img_name = null;
    if (isset($_FILES['prod_img']) && $_FILES['prod_img']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        // ตรวจสอบประเภทไฟล์
        if (!in_array($_FILES['prod_img']['type'], $allowed_types)) {
            echo json_encode(['status' => 'error', 'message' => 'รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF, WEBP)']);
            exit;
        }

        $upload_dir = '../../assets/img/products/';
        // ถ้าโฟลเดอร์ยังไม่มี ให้สร้างขึ้นมาใหม่
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['prod_img']['name'], PATHINFO_EXTENSION);
        $img_name = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        
        if (!move_uploaded_file($_FILES['prod_img']['tmp_name'], $upload_dir . $img_name)) {
            echo json_encode(['status' => 'error', 'message' => 'อัปโหลดรูปภาพไม่สำเร็จ']);
            exit;
        }
    }

    // 3. เริ่มกระบวนการบันทึกข้อมูล (Transaction)
    $conn->begin_transaction();

    try {
        // Step 1: เพิ่มข้อมูลลงตาราง products
        $sql_prod = "INSERT INTO products (prod_name, type_id, barcode, price, detail, img, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_prod = $conn->prepare($sql_prod);
        $stmt_prod->bind_param("sisdssi", $prod_name, $type_id, $barcode, $price, $detail, $img_name, $status);
        $stmt_prod->execute();
        
        // ดึงไอดีสินค้าที่เพิ่งเพิ่มเข้าไปเพื่อเอาไปเชื่อมกับตารางสต็อก
        $new_prod_id = $conn->insert_id; 

        // Step 2: เพิ่มจำนวนสต็อกเริ่มต้นลงตาราง stocks
        $sql_stock = "INSERT INTO stocks (prod_id, total_qty) VALUES (?, ?)";
        $stmt_stock = $conn->prepare($sql_stock);
        $stmt_stock->bind_param("ii", $new_prod_id, $initial_stock);
        $stmt_stock->execute();

        // ยืนยันการทำงานทั้งหมด
        $conn->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มสินค้าสำเร็จ']);

    } catch (Exception $e) {
        // หากมีข้อผิดพลาด (เช่น บาร์โค้ดซ้ำ) ให้ยกเลิกการบันทึกข้อมูลทั้งหมด
        $conn->rollback();
        
        // ลบไฟล์รูปภาพที่เพิ่งอัปโหลดไป (ถ้ามี)
        if ($img_name && file_exists('../../assets/img/products/' . $img_name)) {
            unlink('../../assets/img/products/' . $img_name);
        }
        
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>