<?php
session_start();
require_once '../../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized Access");
}

$type = $_GET['type'] ?? '';
$output = fopen('php://output', 'w');

// ตั้งค่าให้โหลดไฟล์และอ่านภาษาไทยใน Excel ด้วย UTF-8 BOM
header('Content-Type: text/csv; charset=utf-8');
fwrite($output, "\xEF\xBB\xBF");

if ($type === 'inventory') {
    header('Content-Disposition: attachment; filename=Inventory_Report_' . date('Ymd_His') . '.csv');
    fputcsv($output, ['รหัสสินค้า', 'บาร์โค้ด', 'ชื่อสินค้า', 'หมวดหมู่', 'ราคาขาย (฿)', 'ต้นทุน (฿)', 'คงเหลือ']);

    $sql = "SELECT p.prod_id, p.barcode, p.prod_name, c.cate_name, p.price, p.cost, 
                   COALESCE(s.total_qty, 0) as total_qty 
            FROM products p 
            LEFT JOIN categories c ON p.cate_id = c.cate_id 
            LEFT JOIN stocks s ON p.prod_id = s.prod_id";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['prod_id'], $row['barcode'], $row['prod_name'], $row['cate_name'], 
            number_format($row['price'], 2, '.', ''), number_format($row['cost'], 2, '.', ''), $row['total_qty']
        ]);
    }
} 
elseif ($type === 'suppliers') {
    header('Content-Disposition: attachment; filename=Suppliers_Report_' . date('Ymd_His') . '.csv');
    fputcsv($output, ['รหัสซัพพลายเออร์', 'ชื่อบริษัท', 'โทรศัพท์', 'ที่อยู่']);

    $sql = "SELECT sp_id, sp_name, sp_phone, sp_address FROM supplers";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['sp_id'], $row['sp_name'], $row['sp_phone'], $row['sp_address']
        ]);
    }
}
elseif ($type === 'po') {
    header('Content-Disposition: attachment; filename=PO_Report_' . date('Ymd_His') . '.csv');
    fputcsv($output, ['เลขที่บิล(PO)', 'วันที่', 'ชื่อซัพพลายเออร์', 'ยอดรวม (฿)', 'สถานะ']);

    $sql = "SELECT b.purchases_id, b.purchase_date, b.total_cost, b.purchase_status, s.sp_name 
            FROM bill_purchases b 
            LEFT JOIN supplers s ON b.sp_id = s.sp_id";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['purchase_status'] == 0 ? 'ยกเลิก' : ($row['purchase_status'] == 1 ? 'รอรับสินค้า' : 'รับแล้ว');
        fputcsv($output, [
            'PO-' . str_pad($row['purchases_id'], 4, '0', STR_PAD_LEFT), 
            $row['purchase_date'], $row['sp_name'], 
            number_format($row['total_cost'], 2, '.', ''), $status
        ]);
    }
}
else {
    fputcsv($output, ['Invalid Type']);
}

fclose($output);
$conn->close();
?>
