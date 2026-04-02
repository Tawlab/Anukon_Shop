<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id'])) { die("Unauthorized"); }

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
if (!$order) die("Order not found");

$sql_dtl = "SELECT d.*, p.prod_name FROM details_sales d JOIN products p ON d.product_id = p.prod_id WHERE d.sale_id = ?";
$stmt_dtl = $conn->prepare($sql_dtl);
$stmt_dtl->bind_param("i", $sale_id);
$stmt_dtl->execute();
$items = $stmt_dtl->get_result();

$is_shipping = ($order['shipping_type'] === 'delivery');
$order_no = 'ORD-' . str_pad($sale_id, 4, '0', STR_PAD_LEFT);

$status_map = [0 => 'ยกเลิก', 1 => 'รอดำเนินการ', 2 => 'กำลังจัดส่ง', 3 => 'ชำระแล้ว'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จ <?php echo $order_no; ?> | Anukon Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Noto Sans Thai', sans-serif; 
            font-size: 13px; 
            color: #333; 
            background: #e5e7eb; 
            padding: 20px; 
        }
        .receipt { 
            background: #fff; 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 48px; 
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        /* Header */
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #6366f1;
        }
        .store-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .store-icon {
            width: 48px;
            height: 48px;
            background: #6366f1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.2rem;
            font-weight: 700;
        }
        .store-name { font-size: 1.4rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
        .store-sub { font-size: 0.75rem; color: #94a3b8; }
        
        .doc-info { text-align: right; }
        .doc-type { 
            font-size: 1.1rem; 
            font-weight: 700; 
            color: #6366f1;
            margin-bottom: 4px;
        }
        .doc-no { font-size: 0.85rem; color: #64748b; }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }
        .info-card {
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .info-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 8px; }
        .info-value { font-size: 0.85rem; line-height: 1.6; }
        .info-value strong { color: #1e293b; }
        
        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .items-table thead th {
            background: #f1f5f9;
            padding: 10px 12px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
        }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        
        /* Summary */
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 32px;
        }
        .summary-table { width: 280px; }
        .summary-table tr td { padding: 6px 0; font-size: 0.85rem; color: #64748b; }
        .summary-table .total-row td { 
            padding-top: 12px; 
            border-top: 2px solid #1e293b; 
            font-size: 1.1rem; 
            font-weight: 700; 
            color: #1e293b; 
        }
        
        /* Notes */
        .notes { 
            padding: 12px 16px; 
            background: #fef3c7; 
            border-radius: 6px; 
            font-size: 0.8rem; 
            color: #92400e;
            margin-bottom: 24px;
        }
        
        /* Footer */
        .receipt-footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 0.75rem;
        }
        .receipt-footer .thank { 
            font-size: 0.95rem; 
            font-weight: 600; 
            color: #6366f1; 
            margin-bottom: 4px; 
        }
        
        /* Print Button */
        .print-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #6366f1;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-family: 'Noto Sans Thai', sans-serif;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(99,102,241,0.4);
            font-size: 0.9rem;
        }
        .print-btn:hover { background: #4f46e5; }
        
        @media print {
            body { background: white; padding: 0; }
            .receipt { max-width: 100%; margin: 0; padding: 24px; box-shadow: none; border-radius: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨️ พิมพ์ใบเสร็จ</button>

<div class="receipt">
    <!-- Header -->
    <div class="receipt-header">
        <div class="store-logo">
            <div class="store-icon">A</div>
            <div>
                <div class="store-name">Anukon Shop</div>
                <div class="store-sub">ร้านค้าสะดวกซื้อออนไลน์</div>
            </div>
        </div>
        <div class="doc-info">
            <div class="doc-type">ใบเสร็จรับเงิน</div>
            <div class="doc-no"><?php echo $order_no; ?></div>
            <div class="doc-no"><?php echo date_format(date_create($order['sale_date']), "d/m/Y H:i"); ?></div>
        </div>
    </div>

    <!-- Customer & Order Info -->
    <div class="info-grid">
        <div class="info-card">
            <div class="info-label">ข้อมูลลูกค้า</div>
            <div class="info-value">
                <strong><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></strong><br>
                โทร: <?php echo $order['phone_no'] ?: '-'; ?><br>
                <?php if ($is_shipping): ?>
                    <?php echo $order['home_no']; ?> 
                    <?php echo $order['moo'] ? 'ม.'.$order['moo'] : ''; ?> 
                    <?php echo $order['soi'] ? 'ซ.'.$order['soi'] : ''; ?> 
                    <?php echo $order['road'] ? 'ถ.'.$order['road'] : ''; ?><br>
                    ต.<?php echo $order['sub_dist_name']; ?> อ.<?php echo $order['dist_name']; ?> 
                    จ.<?php echo $order['prov_name']; ?> <?php echo $order['zip_code']; ?>
                <?php else: ?>
                    <span style="color: #22c55e; font-weight: 600;">รับสินค้าที่ร้าน</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="info-card">
            <div class="info-label">ข้อมูลเอกสาร</div>
            <div class="info-value">
                <strong>เลขที่:</strong> <?php echo $order_no; ?><br>
                <strong>วันที่:</strong> <?php echo date_format(date_create($order['sale_date']), "d/m/Y"); ?><br>
                <strong>ชำระ:</strong> <?php echo $order['payment_type'] == 'COD' ? 'เงินสด/ปลายทาง' : 'โอนเงิน'; ?><br>
                <strong>สถานะ:</strong> <?php echo $status_map[$order['sale_status']] ?? '-'; ?>
            </div>
        </div>
    </div>

    <!-- Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="6%">#</th>
                <th width="44%" style="text-align: left;">รายการ</th>
                <th width="16%">ราคา/หน่วย</th>
                <th width="14%">จำนวน</th>
                <th width="20%">รวม (฿)</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; $sub_total = 0; while($item = $items->fetch_assoc()): $sub_total += $item['subtotal']; ?>
            <tr>
                <td class="text-center" style="color: #94a3b8;"><?php echo $i++; ?></td>
                <td style="font-weight: 500;"><?php echo htmlspecialchars($item['prod_name']); ?></td>
                <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-center"><?php echo $item['quantity']; ?></td>
                <td class="text-end" style="font-weight: 600;"><?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-section">
        <table class="summary-table">
            <tr><td>รวมสินค้า</td><td class="text-end"><?php echo number_format($sub_total, 2); ?></td></tr>
            <tr><td>ค่าจัดส่ง</td><td class="text-end"><?php echo number_format($order['total_price'] - $sub_total, 2); ?></td></tr>
            <tr class="total-row"><td>ยอดสุทธิ (฿)</td><td class="text-end"><?php echo number_format($order['total_price'], 2); ?></td></tr>
        </table>
    </div>

    <?php if(!empty($order['remark'])): ?>
    <div class="notes">
        <strong>หมายเหตุ:</strong> <?php echo htmlspecialchars($order['remark']); ?>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="receipt-footer">
        <div class="thank">ขอบคุณที่ใช้บริการ Anukon Shop ♡</div>
        <div>เอกสารฉบับนี้ออกโดยระบบอัตโนมัติ</div>
    </div>
</div>

</body>
</html>
<?php 
$stmt->close(); $stmt_dtl->close(); $conn->close();
?>
