<?php
session_start();
require_once '../../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Orders_Report_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
// ใส่ BOM ให้ Excel อ่านภาษาไทยออก
fwrite($output, "\xEF\xBB\xBF");

// Header
fputcsv($output, ['รหัสบิล', 'วันที่ทำรายการ', 'ชื่อลูกค้า', 'วิธีชำระเงิน', 'รูปแบบการส่ง', 'ยอดรวม (บาท)', 'สถานะ', 'หมายเหตุ']);

$sql = "SELECT b.sale_id, b.sale_date, b.total_price, b.payment_type, b.shipping_type, b.sale_status, b.remark,
               u.first_name, u.last_name 
        FROM bill_sales b 
        JOIN users u ON b.user_id = u.user_id 
        ORDER BY b.sale_id DESC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $status_str = '';
    switch($row['sale_status']) {
        case 0: $status_str = 'ยกเลิก'; break;
        case 1: $status_str = 'รอดำเนินการ'; break;
        case 2: $status_str = 'กำลังจัดส่ง'; break;
        case 3: $status_str = 'สำเร็จ'; break;
    }

    fputcsv($output, [
        'ORD-' . str_pad($row['sale_id'], 4, '0', STR_PAD_LEFT),
        $row['sale_date'],
        $row['first_name'] . ' ' . $row['last_name'],
        $row['payment_type'],
        $row['shipping_type'] == 'store_pickup' ? 'รับที่ร้าน (POS)' : 'จัดส่ง',
        number_format($row['total_price'], 2, '.', ''),
        $status_str,
        $row['remark'] ?? ''
    ]);
}

fclose($output);
$conn->close();
?>
