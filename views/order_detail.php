<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

$user_id = $_SESSION['user_id'];
$sale_id = $_GET['id'] ?? 0;

if (!$sale_id) {
    echo "<script>window.location.href = 'my_orders.php';</script>";
    exit;
}

// 1. ดึงข้อมูลบิลหลัก และ ที่อยู่จัดส่ง
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

if (!$bill) {
    // ถ้าไม่พบบิล หรือไม่ใช่บิลของตัวเอง ให้เด้งกลับ
    echo "<script>alert('ไม่พบข้อมูลคำสั่งซื้อ'); window.location.href = 'my_orders.php';</script>";
    exit;
}

// จัดการสถานะคำสั่งซื้อ
$status_text = '';
$status_badge = '';
switch($bill['sale_status']) {
    case 0: $status_text = 'ยกเลิกแล้ว'; $status_badge = 'bg-danger'; break;
    case 1: $status_text = 'รอดำเนินการ'; $status_badge = 'bg-warning text-dark'; break;
    case 2: $status_text = 'กำลังจัดส่ง'; $status_badge = 'bg-info text-dark'; break;
    case 3: $status_text = 'สำเร็จ'; $status_badge = 'bg-success'; break;
    default: $status_text = 'ไม่ทราบสถานะ'; $status_badge = 'bg-secondary';
}

// 2. ดึงรายการสินค้าในบิลนี้
$sql_items = "SELECT d.*, p.prod_name, p.img 
              FROM details_sales d 
              JOIN products p ON d.product_id = p.prod_id 
              WHERE d.sale_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $sale_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

// สมมติค่าส่ง (หรือจะเก็บใน DB อนาคตก็ได้)
$shipping_cost = 50; 
$subtotal_items = $bill['total_price'] - $shipping_cost;
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex align-items-center mb-4">
            <a href="my_orders.php" class="btn btn-light text-primary me-3 border shadow-sm rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h3 class="fw-bold mb-0">รายละเอียดคำสั่งซื้อ <span class="text-primary">#ORD-<?php echo str_pad($bill['sale_id'], 4, '0', STR_PAD_LEFT); ?></span></h3>
                <small class="text-muted">สั่งซื้อเมื่อ: <?php echo date_format(date_create($bill['sale_date']), "d M Y H:i"); ?></small>
            </div>
            <div class="ms-auto">
                <span class="badge <?php echo $status_badge; ?> fs-6 px-3 py-2 shadow-sm"><?php echo $status_text; ?></span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fa-solid fa-box-open text-primary me-2"></i>รายการสินค้า</h5>
                        
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>สินค้า</th>
                                        <th class="text-center">ราคา/ชิ้น</th>
                                        <th class="text-center">จำนวน</th>
                                        <th class="text-end">รวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($item = $items->fetch_assoc()): 
                                        $img_src = !empty($item['img']) ? '../assets/img/products/'.$item['img'] : 'https://placehold.co/100x100?text=No+Image';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $img_src; ?>" alt="product" class="rounded border me-3" width="60" height="60" style="object-fit: contain;">
                                                <span class="fw-bold"><?php echo $item['prod_name']; ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center text-muted">฿<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td class="text-center fw-bold"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end fw-bold text-primary">฿<?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6 offset-md-6">
                                <div class="d-flex justify-content-between mb-2 small text-muted">
                                    <span>ยอดรวมสินค้า</span>
                                    <span>฿<?php echo number_format($subtotal_items, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 border-bottom pb-3 small text-muted">
                                    <span>ค่าจัดส่ง</span>
                                    <span>฿<?php echo number_format($shipping_cost, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold fs-5">ยอดสุทธิ</span>
                                    <span class="fw-bold fs-5 text-primary">฿<?php echo number_format($bill['total_price'], 2); ?></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-location-dot text-danger me-2"></i>ข้อมูลจัดส่ง</h6>
                        <p class="mb-1 fw-bold"><?php echo $_SESSION['full_name']; ?></p>
                        <p class="small text-muted mb-0">
                            บ้านเลขที่ <?php echo $bill['home_no']; ?> 
                            <?php echo !empty($bill['moo']) ? 'ม.'.$bill['moo'] : ''; ?> 
                            <?php echo !empty($bill['soi']) ? 'ซ.'.$bill['soi'] : ''; ?> 
                            <?php echo !empty($bill['road']) ? 'ถ.'.$bill['road'] : ''; ?> <br>
                            ต.<?php echo $bill['sub_dist_name']; ?> อ.<?php echo $bill['dist_name']; ?> 
                            จ.<?php echo $bill['prov_name']; ?> <?php echo $bill['zip_code']; ?>
                        </p>
                        <?php if(!empty($bill['addr_remark'])): ?>
                            <p class="small text-muted mt-2 mb-0"><strong>จุดสังเกต:</strong> <?php echo $bill['addr_remark']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-wallet text-success me-2"></i>การชำระเงิน</h6>
                        
                        <?php if($bill['payment_type'] == 'COD'): ?>
                            <div class="alert alert-light border border-secondary text-center mb-0">
                                <i class="fa-solid fa-truck fa-2x mb-2 text-dark"></i>
                                <h6 class="fw-bold mb-0">เก็บเงินปลายทาง (COD)</h6>
                                <small class="text-muted">กรุณาเตรียมเงินสดให้พอดีกับยอดสุทธิ</small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border border-primary text-center mb-0">
                                <i class="fa-solid fa-building-columns fa-2x mb-2 text-primary"></i>
                                <h6 class="fw-bold mb-2">โอนเงินผ่านธนาคาร</h6>
                                <?php if(!empty($bill['slip_img'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill w-100" data-bs-toggle="modal" data-bs-target="#slipModal">
                                        <i class="fa-solid fa-image me-1"></i> ดูสลิปที่แนบมา
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-danger">ไม่พบสลิปโอนเงิน</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($bill['remark'])): ?>
                            <div class="mt-3 pt-3 border-top">
                                <span class="fw-bold small d-block mb-1">หมายเหตุถึงร้าน:</span>
                                <p class="small text-muted mb-0 bg-light p-2 rounded"><?php echo $bill['remark']; ?></p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<?php if($bill['payment_type'] == 'Transfer' && !empty($bill['slip_img'])): ?>
<div class="modal fade" id="slipModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">สลิปโอนเงิน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img src="../assets/img/slips/<?php echo $bill['slip_img']; ?>" class="img-fluid rounded shadow-sm" alt="Payment Slip">
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