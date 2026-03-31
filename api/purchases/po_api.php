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

    if ($action === 'create_po') {
        $sp_id = intval($_POST['sp_id'] ?? 0);
        $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
        $remark = $_POST['remark'] ?? '';
        $items = $_POST['items'] ?? [];
        
        if ($sp_id <= 0 || empty($items)) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลผู้จัดจำหน่ายหรือรายการสินค้าว่างเปล่า']);
            exit;
        }

        $conn->begin_transaction();
        try {
            // คำนวณยอดรวมใหม่กันการแก้หน้าบ้าน
            $total_cost = 0;
            foreach ($items as $item) {
                $cost = floatval($item['unit_cost'] ?? 0);
                $qty = intval($item['order_qty'] ?? 0);
                $total_cost += ($cost * $qty);
            }

            // เพิ่มลง bill_purchases 
            // สถานะ 1 = Wait for Receiving (รอรับเข้า)
            $sql_po = "INSERT INTO bill_purchases (sp_id, total_cost, purchase_date, purchase_status, remark) VALUES (?, ?, ?, 1, ?)";
            $stmt_po = $conn->prepare($sql_po);
            $stmt_po->bind_param("idss", $sp_id, $total_cost, $purchase_date, $remark);
            $stmt_po->execute();
            $po_id = $conn->insert_id;

            // เพิ่มรายการลง detail_purchases
            $sql_dtl = "INSERT INTO detail_purchases (purchases_id, product_id, order_qty, unit_cost, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmt_dtl = $conn->prepare($sql_dtl);

            foreach ($items as $item) {
                $p_id = intval($item['prod_id'] ?? 0);
                $qty = intval($item['order_qty'] ?? 0);
                $cost = floatval($item['unit_cost'] ?? 0);
                $sub = $qty * $cost;

                if ($p_id > 0 && $qty > 0) {
                    $stmt_dtl->bind_param("iiidd", $po_id, $p_id, $qty, $cost, $sub);
                    $stmt_dtl->execute();
                }
            }

            $conn->commit();
            echo json_encode(['status' => 'success', 'po_id' => $po_id]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'เขียนฐานข้อมูลล้มเหลว: ' . $e->getMessage()]);
        }
    }
    else if ($action === 'receive_po') {
        $po_id = intval($_POST['po_id'] ?? 0);
        $receive_items = $_POST['recv'] ?? []; // format: recv[detail_purchases_id][received_qty & expiry]
        
        if ($po_id <= 0 || empty($receive_items)) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลผิดพลาด']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $upd_dtl = $conn->prepare("UPDATE detail_purchases SET received_qty = ? WHERE dlt_purchases_id = ?");
            // batch insert
            $ins_batch = $conn->prepare("INSERT INTO product_batches (detail_purchase_id, product_id, lot_qty, expiry_date, received_date) VALUES (?, ?, ?, ?, CURDATE())");
            // stock insert/update
            $upd_stock = $conn->prepare("UPDATE stocks SET total_qty = total_qty + ? WHERE prod_id = ?");
            $ins_move = $conn->prepare("INSERT INTO stock_movements (prod_id, movement_type, quantity, ref_id, remark) VALUES (?, 'IN', ?, ?, 'รับเข้าหน้าร้าน (PO)')");

            $all_received = true; // สมมติส่งยอดมารับของหมดในทีเดียว (แบบ Simple)
            
            foreach ($receive_items as $dtl_id => $data) {
                // query to get original order_qty and product_id
                $chk_dtl = $conn->query("SELECT product_id, order_qty FROM detail_purchases WHERE dlt_purchases_id = " . intval($dtl_id));
                $dinfo = $chk_dtl->fetch_assoc();
                if(!$dinfo) continue;

                $prod_id = $dinfo['product_id'];
                $order_qty = $dinfo['order_qty'];

                $recv_qty = intval($data['received_qty'] ?? 0);
                $expiry = !empty($data['expiry_date']) ? $data['expiry_date'] : null;

                if ($recv_qty > 0) {
                    // 1. อัปเดต detail ว่าได้ของเท่าไหร่
                    $upd_dtl->bind_param("ii", $recv_qty, $dtl_id);
                    $upd_dtl->execute();

                    // 2. แอด into batches
                    if($expiry) {
                        $ins_batch->bind_param("iiis", $dtl_id, $prod_id, $recv_qty, $expiry);
                        $ins_batch->execute();
                    }

                    // 3. ปรับ stock หลัก
                    $upd_stock->bind_param("ii", $recv_qty, $prod_id);
                    $upd_stock->execute();

                    // 4. บันทึกประวัติความเคลื่อนไหว
                    $ref_id = 'PO-' . str_pad($po_id, 4, '0', STR_PAD_LEFT);
                    $ins_move->bind_param("iis", $prod_id, $recv_qty, $ref_id);
                    $ins_move->execute();
                } else {
                    $all_received = false; 
                }
            }

            // ถ้ามีของเข้ามา ถือเป็นการรับครบแล้ว (สถานะ 2) หรือบางส่วน (สถานะ 1 หรือ 2 ก็ว่าไป) 
            // เอาแบบ basic คือถ้าเซฟ แปลว่า Received Completeted เลย (status = 2)
            $status_code = $all_received ? 2 : 2; // Fixed to 2 for simplicity
            $upd_po = $conn->query("UPDATE bill_purchases SET purchase_status = $status_code, received_date = CURDATE() WHERE purchases_id = $po_id");

            $conn->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'ดำเนินการล้มเหลว: ' . $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่มีแอคชันนี้']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Access']);
}
?>
