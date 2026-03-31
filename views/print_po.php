<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

$po_id = $_GET['id'] ?? 0;

$sql_po = "SELECT b.*, s.sp_name, s.sp_phone, s.sp_address 
           FROM bill_purchases b 
           JOIN supplers s ON b.sp_id = s.sp_id 
           WHERE b.purchases_id = ?";
$stmt = $conn->prepare($sql_po);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();

if (!$po) die("PO Not Found");

$sql_dtl = "SELECT d.*, p.prod_name, p.barcode 
            FROM detail_purchases d 
            JOIN products p ON d.product_id = p.prod_id 
            WHERE d.purchases_id = ?";
$stmt_dtl = $conn->prepare($sql_dtl);
$stmt_dtl->bind_param("i", $po_id);
$stmt_dtl->execute();
$items = $stmt_dtl->get_result();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบสั่งซื้อ (Purchase Order) #PO-<?php echo str_pad($po_id, 4, '0', STR_PAD_LEFT); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: "Sarabun", "Tahoma", sans-serif; font-size: 14px; margin: 0; padding: 20px; background: #525659; }
        .page { background: white; margin: 0 auto; padding: 40px; width: 210mm; min-height: 297mm; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; }
        .store-info { text-align: right; font-size: 12px; color: #555; }
        
        .customer-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }
        .info-box { width: 48%; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        th { background: #e0f7fa; border: 1px solid #ccc; padding: 10px; text-align: center; }
        td { border: 1px solid #ccc; padding: 8px 10px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        
        .summary { width: 300px; float: right; }
        .summary table { border: none; }
        .summary td { border: none; padding: 5px 10px; }
        .summary .total-row { font-size: 18px; font-weight: bold; border-top: 2px solid #000 !important; color: #0277bd; }
        
        .signatures { display: flex; justify-content: space-between; margin-top: 80px; text-align: center; }
        .sign-box { width: 30%; }
        .sign-line { border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px; }

        @media print {
            body { background: white; padding: 0; }
            .page { width: 100%; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="page">
    <button class="no-print" style="float: right; padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px;" onclick="window.print()"><i class="fa-solid fa-print"></i> พิมพ์เอกสาร</button>

    <div class="header">
        <div>
            <div class="title" style="color: #0277bd;"><i class="fa-solid fa-file-invoice-dollar"></i> ใบสั่งซื้อ (Purchase Order)</div>
            <div style="font-size: 16px; margin-top: 5px;">บริษัท วี-ช็อป มินิมาร์ท จำกัด</div>
        </div>
        <div class="store-info">
            <strong>ที่อยู่สำหรับจัดส่ง:</strong><br>
            99/9 ถ.สุขสวัสดิ์ เขตจอมทอง กทม. 10150<br>
            โทร: 02-123-4567 | ผู้ติดต่อ: ฝ่ายจัดซื้อ (Admin)
        </div>
    </div>

    <div class="customer-info">
        <div class="info-box border-info">
            <strong>ผู้จำหน่าย (Supplier):</strong><br>
            บริษัท: <?php echo $po['sp_name']; ?><br>
            โทรศัพท์: <?php echo $po['sp_phone'] ?: '-'; ?><br>
            ที่อยู่: <?php echo $po['sp_address'] ?: '-'; ?>
        </div>
        <div class="info-box">
            <strong>รายละเอียดเอกสาร:</strong><br>
            เลขที่เอกสาร: <span style="font-weight:bold; color:red;">PO-<?php echo str_pad($po_id, 5, '0', STR_PAD_LEFT); ?></span><br>
            วันที่สั่งซื้อ: <?php echo date_format(date_create($po['purchase_date']), "d / m / Y"); ?><br>
            สถานะ: 
            <?php 
                if($po['purchase_status'] == 0) echo 'ยกเลิก (Void)';
                elseif($po['purchase_status'] == 1) echo 'รอรับสินค้า (Pending)';
                elseif($po['purchase_status'] == 2) echo '<span style="color:green; font-weight:bold;">รับสินค้าแล้ว (Received)</span>';
            ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="35%">รายการสินค้า</th>
                <th width="20%">บาร์โค้ด</th>
                <th width="10%">จำนวน</th>
                <th width="15%">ราคา/หน่วย</th>
                <th width="15%">ราคารวม</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1; 
            while($item = $items->fetch_assoc()): 
            ?>
            <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td><?php echo $item['prod_name']; ?></td>
                <td class="text-center text-muted"><?php echo $item['barcode'] ?: '-'; ?></td>
                <td class="text-center fw-bold text-primary"><?php echo $item['order_qty']; ?></td>
                <td class="text-end"><?php echo number_format($item['unit_cost'], 2); ?></td>
                <td class="text-end fw-bold"><?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td class="total-row">ยอดสุทธิ (Net Total):</td>
                <td class="text-end total-row"><?php echo number_format($po['total_cost'], 2); ?> ฿</td>
            </tr>
        </table>
    </div>
    <div style="clear: both;"></div>

    <div class="signatures">
        <div class="sign-box">
            <div class="sign-line"></div>
            (......................................................)<br>
            <strong>ผู้จัดทำ (Prepared By)</strong><br>
            วันที่: _______/_______/_______
        </div>
        <div class="sign-box">
            <div class="sign-line"></div>
            (......................................................)<br>
            <strong>ผู้อนุมัติ (Authorized By)</strong><br>
            วันที่: _______/_______/_______
        </div>
        <div class="sign-box">
            <div class="sign-line"></div>
            (......................................................)<br>
            <strong>ผู้รับคำสั่งซื้อ (Supplier)</strong><br>
            วันที่: _______/_______/_______
        </div>
    </div>
</div>

</body>
</html>
<?php 
$stmt->close();
$stmt_dtl->close();
$conn->close();
?>
