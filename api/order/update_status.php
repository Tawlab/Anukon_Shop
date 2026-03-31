<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id = $_POST['sale_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? null;

    if ($sale_id && $new_status !== null) {
        $conn->begin_transaction();
        try {
            // เช็คสถานะเดิมก่อน
            $check_sql = "SELECT sale_status FROM bill_sales WHERE sale_id = ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("i", $sale_id);
            $stmt_check->execute();
            $old_status = $stmt_check->get_result()->fetch_assoc()['sale_status'] ?? -1;
            $stmt_check->close();

            if ($old_status == -1) throw new Exception("ไม่พบคำสั่งซื้อ");

            $reason = $_POST['reason'] ?? '';

            // อัปเดตตาราง bill_sales
            if (!empty($reason) && $new_status == 0) {
                // เก็บเหตุผลการยกเลิกลง remark
                $sql = "UPDATE bill_sales SET sale_status = ?, remark = CONCAT('ยกเลิกด่วน: ', ?) WHERE sale_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isi", $new_status, $reason, $sale_id);
            } else {
                $sql = "UPDATE bill_sales SET sale_status = ? WHERE sale_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $new_status, $sale_id);
            }
            $stmt->execute();
            $stmt->close();

            // คืนสต็อกถ้ายกเลิกคำสั่งซื้อ (0) และสถานะเดิมไม่ใช่ยกเลิก
            if ($new_status == 0 && $old_status != 0) {
                $sql_dtl = "SELECT product_id, quantity FROM details_sales WHERE sale_id = ?";
                $stmt_dtl = $conn->prepare($sql_dtl);
                $stmt_dtl->bind_param("i", $sale_id);
                $stmt_dtl->execute();
                $items = $stmt_dtl->get_result();
                
                $sql_restore = "UPDATE stocks SET total_qty = total_qty + ? WHERE prod_id = ?";
                $stmt_restore = $conn->prepare($sql_restore);
                
                while($item = $items->fetch_assoc()) {
                    $stmt_restore->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stmt_restore->execute();
                }
                $stmt_restore->close();
                $stmt_dtl->close();
            }

            // ตัดสต็อกกลับถ้าเปลี่ยนจากยกเลิก (0) เป็นรอดำเนินการ (1+)
            if ($old_status == 0 && $new_status > 0) {
                $sql_dtl = "SELECT product_id, quantity FROM details_sales WHERE sale_id = ?";
                $stmt_dtl = $conn->prepare($sql_dtl);
                $stmt_dtl->bind_param("i", $sale_id);
                $stmt_dtl->execute();
                $items = $stmt_dtl->get_result();
                
                $sql_deduct = "UPDATE stocks SET total_qty = total_qty - ? WHERE prod_id = ?";
                $stmt_deduct = $conn->prepare($sql_deduct);
                
                while($item = $items->fetch_assoc()) {
                    $stmt_deduct->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stmt_deduct->execute();
                }
                $stmt_deduct->close();
                $stmt_dtl->close();
            }

            $conn->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    }
    $conn->close();
}
?>