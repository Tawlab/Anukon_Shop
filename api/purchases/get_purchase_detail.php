<?php
session_start();
require_once '../../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "ไม่มีสิทธิ์เข้าถึง";
    exit;
}

$purchase_id = intval($_GET['purchase_id'] ?? 0);

if ($purchase_id <= 0) {
    echo "ไม่พบข้อมูลบิลสั่งซื้อ";
    exit;
}

$sql = "SELECT dp.*, p.prod_name 
        FROM details_purchases dp 
        JOIN products p ON dp.product_id = p.prod_id 
        WHERE dp.purchase_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $purchase_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<table class="table table-bordered">';
    echo '<thead class="table-light"><tr><th>ลำดับ</th><th>รายการสินค้า</th><th class="text-center">จำนวน (ชิ้น)</th><th class="text-end">ต้นทุน/ชิ้น</th><th class="text-end">รวมแยก</th></tr></thead>';
    echo '<tbody>';
    
    $i = 1;
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['quantity'] * $row['unit_cost'];
        $total += $subtotal;
        echo '<tr>';
        echo '<td>' . $i++ . '</td>';
        echo '<td class="fw-bold">' . htmlspecialchars($row['prod_name']) . '</td>';
        echo '<td class="text-center">' . $row['quantity'] . '</td>';
        echo '<td class="text-end">฿' . number_format($row['unit_cost'], 2) . '</td>';
        echo '<td class="text-end">฿' . number_format($subtotal, 2) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '<tfoot><tr class="table-light">';
    echo '<td colspan="4" class="text-end fw-bold">ยอดสุทธิของบิลนี้</td>';
    echo '<td class="text-end fw-bold text-danger">฿' . number_format($total, 2) . '</td>';
    echo '</tr></tfoot>';
    echo '</table>';
} else {
    echo '<div class="alert alert-warning text-center">ไม่พบรายการสินค้าย่อยในบิลนี้</div>';
}

$stmt->close();
$conn->close();
?>
