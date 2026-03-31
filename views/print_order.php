<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

$sale_id = $_GET['id'] ?? 0;

$sql = "SELECT b.*, u.first_name, u.last_name, u.phone_no, 
               a.home_no, a.moo, a.soi, a.road, s.sub_dist_name, d.dist_name, p.prov_name, s.zip_code
        FROM bill_sales b 
        LEFT JOIN users u ON b.user_id = u.user_id 
        LEFT JOIN addresses a ON b.address_id = a.address_id
        LEFT JOIN subdistricts s ON a.sub_dist_id = s.sub_dist_id
        LEFT JOIN districts d ON s.dist_id = d.dist_id
        LEFT JOIN provinces p ON d.prov_id = p.prov_id
        WHERE b.sale_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found");
}

// สินค้าในบิล
$sql_dtl = "SELECT d.*, p.prod_name FROM details_sales d JOIN products p ON d.product_id = p.prod_id WHERE d.sale_id = ?";
$stmt_dtl = $conn->prepare($sql_dtl);
$stmt_dtl->bind_param("i", $sale_id);
$stmt_dtl->execute();
$items = $stmt_dtl->get_result();

$is_shipping = ($order['shipping_type'] === 'delivery');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จรับเงิน/ใบส่งของ #ORD-<?php echo str_pad($sale_id, 4, '0', STR_PAD_LEFT); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: "Sarabun", "Tahoma", sans-serif; font-size: 14px; margin: 0; padding: 20px; background: #525659; }
        .page { background: white; margin: 0 auto; padding: 40px; width: 210mm; min-height: 297mm; box-shadow: 0 0 10px rgba(0,0,0,0.5); position: relative; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; }
        .store-info { text-align: right; font-size: 12px; color: #555; }
        
        .customer-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }
        .info-box { width: 48%; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        th { background: #f0f0f0; border: 1px solid #ccc; padding: 10px; text-align: center; }
        td { border: 1px solid #ccc; padding: 10px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        
        .summary { width: 300px; float: right; }
        .summary table { border: none; }
        .summary td { border: none; padding: 5px 10px; }
        .summary .total-row { font-size: 18px; font-weight: bold; border-top: 2px solid #000 !important; }
        
        .footer { clear: both; margin-top: 80px; text-align: center; font-size: 12px; color: #777; }
        
        @media print {
            body { background: white; padding: 0; }
            .page { width: 100%; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="page">
    <button class="no-print" style="float: right; padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px;" onclick="window.print()"><i class="fa-solid fa-print"></i> สั่งพิมพ์ / บันทึก PDF</button>

    <div class="header">
        <div>
            <div class="title"><i class="fa-solid fa-bag-shopping"></i> V-SHOP</div>
            <div style="font-size: 16px; margin-top: 5px;">ใบเสร็จรับเงิน / ใบส่งของ</div>
            <div style="color: #666;">(Receipt / Delivery Order)</div>
        </div>
        <div class="store-info">
            <strong>บริษัท วี-ช็อป มินิมาร์ท จำกัด</strong><br>
            99/9 ถ.สุขสวัสดิ์ เขตจอมทอง กทม. 10150<br>
            โทร: 02-123-4567 | เลขประจำตัวผู้เสียภาษี: 01055xxxxxxxx
        </div>
    </div>

    <div class="customer-info">
        <div class="info-box">
            <strong>ข้อมูลลูกค้า / จัดส่งถึง:</strong><br>
            ชื่อ: <?php echo $order['first_name'] . ' ' . $order['last_name']; ?><br>
            โทร: <?php echo $order['phone_no'] ?: '-'; ?><br>
            ที่อยู่: 
            <?php 
            if ($is_shipping) {
                echo "บ้านเลขที่ " . $order['home_no'] . " ";
                if($order['moo']) echo "ม." . $order['moo'] . " ";
                if($order['soi']) echo "ซ." . $order['soi'] . " ";
                if($order['road']) echo "ถ." . $order['road'] . " ";
                echo "<br>ต." . $order['sub_dist_name'] . " อ." . $order['dist_name'] . " จ." . $order['prov_name'] . " " . $order['zip_code'];
            } else {
                echo "<span style='color: green; font-weight: bold;'>ซื้อที่หน้าร้าน (รับสินค้าทันที)</span>";
            }
            ?>
        </div>
        <div class="info-box">
            <strong>ข้อมูลเอกสาร:</strong><br>
            เลขที่เอกสาร: ORD-<?php echo str_pad($sale_id, 4, '0', STR_PAD_LEFT); ?><br>
            วันที่: <?php echo date_format(date_create($order['sale_date']), "d / m / Y"); ?><br>
            เวลา: <?php echo date_format(date_create($order['sale_date']), "H:i"); ?> น.<br>
            วิธีชำระเงิน: <?php echo $order['payment_type'] == 'COD' ? 'เก็บเงินปลายทาง' : ($order['payment_type'] == 'Transfer' ? 'โอนเงิน' : 'เงินสด'); ?><br>
            สถานะ: 
            <?php 
                if($order['sale_status'] == 0) echo 'ยกเลิก (Void)';
                elseif($order['sale_status'] == 1) echo 'รอดำเนินการ';
                elseif($order['sale_status'] == 2) echo 'กำลังจัดส่ง';
                elseif($order['sale_status'] == 3) echo 'สำเร็จ / ชำระแล้ว';
            ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="45%">รายการสินค้า</th>
                <th width="15%">ราคา/หน่วย</th>
                <th width="15%">จำนวน</th>
                <th width="20%">รวบเป็นเงิน</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1; 
            $sub_total = 0;
            while($item = $items->fetch_assoc()): 
                $sub_total += $item['subtotal'];
            ?>
            <tr>
                <td class="text-center"><?php echo $i++; ?></td>
                <td><?php echo $item['prod_name']; ?></td>
                <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-center"><?php echo $item['quantity']; ?></td>
                <td class="text-end"><?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td>รวมราคาสินค้า:</td>
                <td class="text-end"><?php echo number_format($sub_total, 2); ?> ฿</td>
            </tr>
            <tr>
                <td>ค่าจัดส่ง (ถ้ามี):</td>
                <td class="text-end"><?php echo number_format($order['total_price'] - $sub_total, 2); ?> ฿</td>
            </tr>
            <tr>
                <td class="total-row">ยอดสุทธิ:</td>
                <td class="text-end total-row"><?php echo number_format($order['total_price'], 2); ?> ฿</td>
            </tr>
        </table>
    </div>
    <div style="clear: both;"></div>

    <?php if($order['remark']): ?>
    <div style="margin-top: 20px; font-size: 12px; color: #d9534f;">
        <strong>หมายเหตุระบบ:</strong> <?php echo $order['remark']; ?>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>ขอบคุณที่ใช้บริการ V-SHOP</p>
        <p>เอกสารฉบับนี้พิมพ์จากระบบคอมพิวเตอร์อัตโนมัติ</p>
    </div>
</div>

</body>
</html>
<?php 
$stmt->close();
$stmt_dtl->close();
$conn->close();
?>
