<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$po_id = intval($_GET['id'] ?? 0);
if ($po_id <= 0) {
    echo "<script>alert('ไม่พบหมายเลข PO'); window.location.href='purchases.php';</script>";
    exit;
}

// 1. ดึงหัวบิล PO
$sql_po = "SELECT b.*, s.sp_name, s.ct_first_name, s.ct_phone, s.sp_tax
           FROM bill_purchases b 
           LEFT JOIN supplers s ON b.sp_id = s.sp_id
           WHERE b.purchases_id = $po_id LIMIT 1";
$res_po = $conn->query($sql_po);
if($res_po->num_rows == 0) {
    echo "<script>alert('ไม่พบข้อมูล PO'); window.location.href='purchases.php';</script>";
    exit;
}
$po = $res_po->fetch_assoc();
$is_received = ($po['purchase_status'] == 2);

// 2. ดึงรายละเอียดสินค้า (detail_purchases)
$sql_dtl = "SELECT d.*, p.prod_name, p.barcode 
            FROM detail_purchases d 
            JOIN products p ON d.product_id = p.prod_id 
            WHERE d.purchases_id = $po_id";
$res_dtl = $conn->query($sql_dtl);
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">
                <i class="fa-solid fa-box-open text-primary me-2"></i>รับเข้าสินค้าเข้าคลัง (Receive)
            </h2>
            <a href="purchases.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> กลับไปหน้ารวม
            </a>
        </div>

        <div class="row g-4">
            <!-- ซีกซ้าย ใบสั่งซื้อ info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold mb-0 text-primary">PO-<?php echo str_pad($po['purchases_id'], 4, '0', STR_PAD_LEFT); ?></h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-2"><span class="text-muted"><i class="fa-solid fa-calendar me-2"></i>สั่งวันที่:</span> <strong><?php echo date('d M Y', strtotime($po['purchase_date'])); ?></strong></p>
                        <p class="mb-2"><span class="text-muted"><i class="fa-solid fa-building me-2"></i>ซัพพลายเออร์:</span> <strong><?php echo htmlspecialchars($po['sp_name']); ?></strong></p>
                        <p class="mb-2"><span class="text-muted"><i class="fa-solid fa-phone me-2"></i>ติดต่อ:</span> <strong><?php echo htmlspecialchars($po['ct_first_name'] . ' ' . $po['ct_phone']); ?></strong></p>
                        <hr>
                        <h4 class="fw-bold text-dark text-center my-3">ยอดรวมบิล: ฿<?php echo number_format($po['total_cost'], 2); ?></h4>
                        
                        <?php if($is_received): ?>
                            <div class="alert alert-success text-center mt-3"><i class="fa-solid fa-check-circle me-1"></i> รับเข้าสินค้าและตัดสต็อกเรียบร้อยแล้วเมื่อ: <br><strong><?php echo date('d M Y', strtotime($po['received_date'])); ?></strong></div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center mt-3"><i class="fa-solid fa-clock me-1"></i> สถานะ: รอรับสินค้า</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ซีกขวา แบบฟอร์มกรอกจำนวนรับและวันหมดอายุ -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <form id="receiveForm">
                        <div class="card-body p-4">
                            <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-list-check me-2"></i>รายการที่สั่ง</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>สินค้า</th>
                                            <th width="15%" class="text-center">จำนวนสั่ง</th>
                                            <th width="20%">จำนวนรับจริง <span class="text-danger">*</span></th>
                                            <th width="30%">วันหมดอายุ (Expiry)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($item = $res_dtl->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-primary"><?php echo htmlspecialchars($item['prod_name']); ?></div>
                                                    <small class="text-muted">บาร์โค้ด: <?php echo htmlspecialchars($item['barcode']); ?></small>
                                                </td>
                                                <td class="text-center fw-bold fs-5 text-secondary"><?php echo $item['order_qty']; ?></td>
                                                <td>
                                                    <input type="number" min="0" class="form-control fw-bold fs-5 text-center text-success border-success" 
                                                           name="recv[<?php echo $item['dlt_purchases_id']; ?>][received_qty]" 
                                                           value="<?php echo $is_received ? $item['received_qty'] : $item['order_qty']; ?>" 
                                                           <?php echo $is_received ? 'readonly' : 'required'; ?>>
                                                </td>
                                                <td>
                                                    <input type="date" class="form-control" 
                                                           name="recv[<?php echo $item['dlt_purchases_id']; ?>][expiry_date]"
                                                           <?php echo $is_received ? 'disabled' : ''; ?>>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if(!$is_received): ?>
                        <div class="card-footer bg-white border-top-0 p-4 text-end">
                            <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm rounded-pill">
                                <i class="fa-solid fa-check-double me-1"></i> ยืนยันรับสินค้าเข้าสต็อก
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

        </div>

    </div>
</main>

<script>
$('#receiveForm').on('submit', function(e) {
    e.preventDefault();

    Swal.fire({
        title: 'ยืนยันจำนวนที่รับจริง?',
        text: 'เมื่อรับสินค้าแล้ว สต็อกจะถูกบวกเพิ่มอัตโนมัติ และแจ้งวันหมดอายุเข้าระบบ',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยันการรับของ',
        cancelButtonText: 'แก้ไขก่อน',
        confirmButtonColor: '#198754'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'กำลังปรับสต็อก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            let formData = $(this).serialize() + '&action=receive_po&po_id=<?php echo $po_id; ?>';
            
            $.ajax({
                url: '../api/purchases/po_api.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'รับสินค้าเข้าคลังตีสต็อกสำเร็จ!',
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
