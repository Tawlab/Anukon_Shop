<?php 
include_once '../includes/header.php'; 

$user_id = $_SESSION['user_id'];
$sale_id = $_GET['id'] ?? 0;

if (!$sale_id) {
    echo "<script>window.location.href = 'my_orders.php';</script>";
    exit;
}

$sql_bill = "SELECT b.*, 
                    a.home_no, a.moo, a.soi, a.road, a.remark as addr_remark, 
                    s.sub_dist_name, s.zip_code, d.dist_name, p.prov_name 
             FROM bill_sales b
             LEFT JOIN addresses a ON b.address_id = a.address_id
             LEFT JOIN subdistricts s ON a.sub_dist_id = s.sub_dist_id
             LEFT JOIN districts d ON s.dist_id = d.dist_id
             LEFT JOIN provinces p ON d.prov_id = p.prov_id
             WHERE b.sale_id = ? AND b.user_id = ?";
$stmt_bill = $conn->prepare($sql_bill);
$stmt_bill->bind_param("ii", $sale_id, $user_id);
$stmt_bill->execute();
$bill = $stmt_bill->get_result()->fetch_assoc();

if (!$bill && $_SESSION['role'] !== 'admin') {
    echo "<script>alert('ไม่พบข้อมูลคำสั่งซื้อ'); window.location.href = 'my_orders.php';</script>";
    exit;
}
if (!$bill && $_SESSION['role'] === 'admin') {
    // Admin override if checking other user's order
    $sql_admin = "SELECT b.*, 
                    a.home_no, a.moo, a.soi, a.road, a.remark as addr_remark, 
                    s.sub_dist_name, s.zip_code, d.dist_name, p.prov_name,
                    u.first_name, u.last_name
             FROM bill_sales b
             LEFT JOIN users u ON b.user_id = u.user_id
             LEFT JOIN addresses a ON b.address_id = a.address_id
             LEFT JOIN subdistricts s ON a.sub_dist_id = s.sub_dist_id
             LEFT JOIN districts d ON s.dist_id = d.dist_id
             LEFT JOIN provinces p ON d.prov_id = p.prov_id
             WHERE b.sale_id = ?";
    $stmt_admin = $conn->prepare($sql_admin);
    $stmt_admin->bind_param("i", $sale_id);
    $stmt_admin->execute();
    $bill = $stmt_admin->get_result()->fetch_assoc();
    if (!$bill) {
        echo "<script>alert('ไม่พบข้อมูลคำสั่งซื้อ'); window.location.href = 'orders_manage.php';</script>";
        exit;
    }
}

$status_config = [
    0 => ['label' => 'ยกเลิก', 'bg' => 'var(--danger-soft)', 'color' => 'var(--danger)', 'icon' => 'fa-ban'],
    1 => ['label' => 'รอดำเนินการ', 'bg' => 'var(--warning-soft)', 'color' => '#b45309', 'icon' => 'fa-clock'],
    2 => ['label' => 'กำลังจัดส่ง', 'bg' => 'var(--info-soft)', 'color' => 'var(--info)', 'icon' => 'fa-truck-fast'],
    3 => ['label' => 'สำเร็จ', 'bg' => 'var(--success-soft)', 'color' => 'var(--success)', 'icon' => 'fa-circle-check'],
];
$cfg = $status_config[$bill['sale_status']] ?? $status_config[1];

$sql_items = "SELECT d.*, p.prod_name, p.img 
              FROM details_sales d 
              JOIN products p ON d.product_id = p.prod_id 
              WHERE d.sale_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $sale_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

$shipping_cost = 50; 
$subtotal_items = $bill['total_price'] - $shipping_cost;
?>

<main class="page-content fade-up">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="javascript:history.back()" class="btn btn-light rounded-circle border shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-arrow-left text-primary"></i>
        </a>
        <div>
            <h5 class="fw-bold mb-0">รายละเอียดคำสั่งซื้อ <span class="text-primary">#ORD-<?php echo str_pad($bill['sale_id'], 4, '0', STR_PAD_LEFT); ?></span></h5>
            <small class="text-muted"><?php echo date_format(date_create($bill['sale_date']), "d/m/Y H:i"); ?></small>
        </div>
        <span class="badge ms-auto" style="background: <?php echo $cfg['bg']; ?>; color: <?php echo $cfg['color']; ?>; font-size: 0.9rem;">
            <i class="fa-solid <?php echo $cfg['icon']; ?> me-1"></i><?php echo $cfg['label']; ?>
        </span>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card p-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-box text-primary me-2"></i>รายการสินค้า</h6>
                
                <div class="d-flex flex-column gap-3 mb-4">
                    <?php while($item = $items->fetch_assoc()): 
                        $img_src = !empty($item['img']) ? '../uploads/products/'.$item['img'] : 'https://placehold.co/100x100?text=No+Image';
                    ?>
                    <div class="d-flex align-items-center gap-3 p-2 rounded-3" style="background: var(--surface-alt);">
                        <img src="<?php echo $img_src; ?>" alt="product" class="rounded" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='https://placehold.co/100x100?text=No+Image'">
                        <div class="flex-grow-1">
                            <div class="fw-bold small"><?php echo htmlspecialchars($item['prod_name']); ?></div>
                            <div class="text-muted small"><?php echo $item['quantity']; ?> x ฿<?php echo number_format($item['unit_price'], 2); ?></div>
                        </div>
                        <div class="fw-bold text-primary">฿<?php echo number_format($item['subtotal'], 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>ยอดรวมสินค้า</span>
                        <span>฿<?php echo number_format($subtotal_items, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 small text-muted pb-3 border-bottom">
                        <span>ค่าจัดส่ง</span>
                        <span>฿<?php echo number_format($shipping_cost, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">ยอดสุทธิ</span>
                        <h4 class="fw-bold text-primary mb-0">฿<?php echo number_format($bill['total_price'], 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-location-dot text-danger me-2"></i>จัดส่งไปที่</h6>
                <div class="fw-bold small mb-1"><?php echo isset($bill['first_name']) ? $bill['first_name'] . ' ' . $bill['last_name'] : $_SESSION['full_name']; ?></div>
                <div class="small text-muted mb-2">
                    <?php if($bill['shipping_type'] === 'delivery'): ?>
                        <?php echo $bill['home_no']; ?> 
                        <?php echo !empty($bill['moo']) ? 'ม.'.$bill['moo'] : ''; ?> 
                        <?php echo !empty($bill['soi']) ? 'ซ.'.$bill['soi'] : ''; ?> 
                        <?php echo !empty($bill['road']) ? 'ถ.'.$bill['road'] : ''; ?> <br>
                        ต.<?php echo $bill['sub_dist_name']; ?> อ.<?php echo $bill['dist_name']; ?> 
                        จ.<?php echo $bill['prov_name']; ?> <?php echo $bill['zip_code']; ?>
                    <?php else: ?>
                        <span style="color: var(--success); font-weight: bold;">รับสินค้าที่ร้าน</span>
                    <?php endif; ?>
                </div>
                <?php if(!empty($bill['addr_remark'])): ?>
                    <div class="small text-muted p-2 rounded" style="background: var(--warning-soft); color: #b45309;">
                        <strong><i class="fa-solid fa-circle-info me-1"></i>โน้ตที่อยู่:</strong> <?php echo htmlspecialchars($bill['addr_remark']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-wallet text-success me-2"></i>การชำระเงิน</h6>
                <?php if($bill['payment_type'] == 'COD'): ?>
                    <div class="text-center p-3 rounded-3" style="background: var(--surface-alt);">
                        <i class="fa-solid fa-money-bill-wave fa-2x mb-2 text-success"></i>
                        <h6 class="fw-bold mb-0">เงินสด / เก็บปลายทาง</h6>
                    </div>
                <?php else: ?>
                    <div class="text-center p-3 rounded-3 mb-2" style="background: var(--surface-alt);">
                        <i class="fa-solid fa-qrcode fa-2x mb-2 text-info"></i>
                        <h6 class="fw-bold mb-0">โอนเงิน</h6>
                    </div>
                    <?php if(!empty($bill['slip_img'])): ?>
                        <button class="btn btn-outline-primary w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#slipModal">
                            <i class="fa-solid fa-image me-1"></i>ดูสลิปโอนเงิน
                        </button>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if(!empty($bill['remark'])): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="fw-bold small mb-1">หมายเหตุถึงร้าน:</div>
                        <div class="small p-2 rounded" style="background: var(--surface-alt);"><?php echo htmlspecialchars($bill['remark']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if($_SESSION['role'] === 'admin'): ?>
            <div class="mt-3">
                <a href="print_order.php?id=<?php echo $bill['sale_id']; ?>" target="_blank" class="btn btn-dark w-100 py-2">
                    <i class="fa-solid fa-print me-1"></i>พิมพ์ใบเสร็จ
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php if($bill['payment_type'] == 'Transfer' && !empty($bill['slip_img'])): ?>
<div class="modal fade" id="slipModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close btn-close-white bg-dark rounded-circle p-2" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0 mt-3">
                <img src="../uploads/slips/<?php echo htmlspecialchars($bill['slip_img']); ?>" class="img-fluid rounded-4 shadow-lg" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
$stmt_bill->close();
$stmt_items->close();
include_once '../includes/footer.php'; 
?>