<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = $_POST['cart_id'] ?? 0;
    $user_id = $_SESSION['user_id'];

    // ลบสินค้าโดยเช็ค user_id ด้วยเพื่อป้องกันการลบตะกร้าคนอื่น
    $sql = "DELETE FROM cart_items WHERE cart_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'ลบสินค้าเรียบร้อย']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ลบสินค้าไม่สำเร็จ']);
    }
}
?>