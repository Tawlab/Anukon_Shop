<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') die("Unauthorized");

$po_id = $_GET['id'] ?? 0;

$sql_po = "SELECT b.*, s.sp_name, s.ct_phone, s.sp_email 
           FROM bill_purchases b JOIN supplers s ON b.sp_id = s.sp_id 
           WHERE b.purchases_id = ?";
$stmt = $conn->prepare($sql_po);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po = $stmt->get_result()->fetch_assoc();
if (!$po) die("PO Not Found");

$sql_dtl = "SELECT d.*, p.prod_name, p.barcode FROM detail_purchases d JOIN products p ON d.product_id = p.prod_id WHERE d.purchases_id = ?";
$stmt_dtl = $conn->prepare($sql_dtl);
$stmt_dtl->bind_param("i", $po_id);
$stmt_dtl->execute();
$items = $stmt_dtl->get_result();

$po_no = 'PO-' . str_pad($po_id, 5, '0', STR_PAD_LEFT);
$po_status = [0 => 'ยกเลิก', 1 => 'รอรับสินค้า', 2 => 'รับแล้ว'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?php echo $po_no; ?> | Anukon Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Noto Sans Thai', sans-serif; font-size: 13px; color: #333; background: #e5e7eb; padding: 20px; }
        .receipt { background: #fff; max-width: 800px; margin: 0 auto; padding: 48px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        .receipt-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 2px solid #3b82f6; }
        .store-logo { display: flex; align-items: center; gap: 12px; }
        .store-icon { width: 48px; height: 48px; background: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; font-weight: 700; }
        .store-name { font-size: 1.4rem; font-weight: 700; color: #1e293b; }
        .store-sub { font-size: 0.75rem; color: #94a3b8; }
        .doc-info { text-align: right; }
        .doc-type { font-size: 1.1rem; font-weight: 700; color: #3b82f6; margin-bottom: 4px; }
        .doc-no { font-size: 0.85rem; color: #64748b; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
        .info-card { padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
        .info-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 8px; }
        .info-value { font-size: 0.85rem; line-height: 1.6; }
        .info-value strong { color: #1e293b; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .items-table thead th { background: #eff6ff; padding: 10px 12px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #3b82f6; border-bottom: 2px solid #bfdbfe; }
        .items-table tbody td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        
        .summary-section { display: flex; justify-content: flex-end; margin-bottom: 40px; }
        .summary-table { width: 280px; }
        .summary-table tr td { padding: 6px 0; font-size: 0.85rem; color: #64748b; }
        .summary-table .total-row td { padding-top: 12px; border-top: 2px solid #1e293b; font-size: 1.1rem; font-weight: 700; color: #3b82f6; }
        
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; text-align: center; font-size: 0.8rem; }
        .sign-box { width: 30%; }
        .sign-line { border-bottom: 1px solid #94a3b8; height: 40px; margin-bottom: 8px; }
        .sign-label { font-weight: 600; color: #64748b; margin-bottom: 4px; }
        .sign-date { color: #94a3b8; font-size: 0.75rem; }
        
        .print-btn { position: fixed; bottom: 24px; right: 24px; background: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 50px; font-family: 'Noto Sans Thai', sans-serif; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(59,130,246,0.4); font-size: 0.9rem; }
        .print-btn:hover { background: #2563eb; }
        
        @media print {
            body { background: white; padding: 0; }
            .receipt { max-width: 100%; margin: 0; padding: 24px; box-shadow: none; border-radius: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨️ พิมพ์เอกสาร</button>

<div class="receipt">
    <div class="receipt-header">
        <div class="store-logo">
            <div class="store-icon">A</div>
            <div>
                <div class="store-name">Anukon Shop</div>
                <div class="store-sub">ใบสั่งซื้อ (Purchase Order)</div>
            </div>
        </div>
        <div class="doc-info">
            <div class="doc-type"><?php echo $po_no; ?></div>
            <div class="doc-no"><?php echo date_format(date_create($po['purchase_date']), "d/m/Y"); ?></div>
            <div class="doc-no"><?php echo $po_status[$po['purchase_status']] ?? '-'; ?></div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <div class="info-label">ผู้จำหน่าย (Supplier)</div>
            <div class="info-value">
                <strong><?php echo htmlspecialchars($po['sp_name']); ?></strong><br>
                โทร: <?php echo $po['ct_phone'] ?: '-'; ?><br>
                อีเมล: <?php echo $po['sp_email'] ?: '-'; ?>
            </div>
        </div>
        <div class="info-card">
            <div class="info-label">ผู้สั่งซื้อ</div>
            <div class="info-value">
                <strong>Anukon Shop</strong><br>
                ฝ่ายจัดซื้อ (Admin)<br>
                วันที่สั่ง: <?php echo date_format(date_create($po['purchase_date']), "d/m/Y"); ?>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="6%">#</th>
                <th width="34%" style="text-align:left;">สินค้า</th>
                <th width="18%">บาร์โค้ด</th>
                <th width="12%">จำนวน</th>
                <th width="15%">ราคา/หน่วย</th>
                <th width="15%">รวม (฿)</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; while($item = $items->fetch_assoc()): ?>
            <tr>
                <td class="text-center" style="color:#94a3b8;"><?php echo $i++; ?></td>
                <td style="font-weight:500;"><?php echo htmlspecialchars($item['prod_name']); ?></td>
                <td class="text-center" style="color:#94a3b8;"><?php echo $item['barcode'] ?: '-'; ?></td>
                <td class="text-center" style="font-weight:600; color:#3b82f6;"><?php echo $item['order_qty']; ?></td>
                <td class="text-end"><?php echo number_format($item['unit_cost'], 2); ?></td>
                <td class="text-end" style="font-weight:600;"><?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="summary-section">
        <table class="summary-table">
            <tr class="total-row"><td>ยอดรวมทั้งสิ้น</td><td class="text-end"><?php echo number_format($po['total_cost'], 2); ?> ฿</td></tr>
        </table>
    </div>

    <div class="signatures">
        <div class="sign-box">
            <div class="sign-line"></div>
            <div class="sign-label">ผู้จัดทำ (Prepared By)</div>
            <div class="sign-date">วันที่ ____/____/____</div>
        </div>
        <div class="sign-box">
            <div class="sign-line"></div>
            <div class="sign-label">ผู้อนุมัติ (Authorized By)</div>
            <div class="sign-date">วันที่ ____/____/____</div>
        </div>
        <div class="sign-box">
            <div class="sign-line"></div>
            <div class="sign-label">ผู้รับคำสั่งซื้อ (Supplier)</div>
            <div class="sign-date">วันที่ ____/____/____</div>
        </div>
    </div>
</div>

</body>
</html>
<?php $stmt->close(); $stmt_dtl->close(); $conn->close(); ?>
