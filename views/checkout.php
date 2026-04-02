<?php 
include_once '../includes/header.php'; 

if ($_SESSION['role'] !== 'customer') { header("Location: dashboard.php"); exit; }

$user_id = $_SESSION['user_id'];

$sql_cart = "SELECT ci.cart_id, ci.quantity, p.prod_id, p.prod_name, p.price, p.img,
             (ci.quantity * p.price) as subtotal 
             FROM cart_items ci JOIN products p ON ci.prod_id = p.prod_id 
             WHERE ci.user_id = ?";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$res_cart = $stmt_cart->get_result();

if ($res_cart->num_rows == 0) {
    echo "<script>window.location.href = 'shop.php';</script>";
    exit;
}

$total_price = 0;
$items = [];
while($row = $res_cart->fetch_assoc()) {
    $total_price += $row['subtotal'];
    $items[] = $row;
}

$sql_address = "SELECT a.home_no, a.moo, a.soi, a.road, a.remark, 
                       s.sub_dist_name, s.zip_code, d.dist_name, p.prov_name 
                FROM users u 
                LEFT JOIN addresses a ON u.address_id = a.address_id 
                LEFT JOIN subdistricts s ON a.sub_dist_id = s.sub_dist_id 
                LEFT JOIN districts d ON s.dist_id = d.dist_id 
                LEFT JOIN provinces p ON d.prov_id = p.prov_id 
                WHERE u.user_id = ?";
$stmt_addr = $conn->prepare($sql_address);
$stmt_addr->bind_param("i", $user_id);
$stmt_addr->execute();
$address = $stmt_addr->get_result()->fetch_assoc();

$can_deliver = $total_price >= 100;
?>

<main class="page-content fade-up">
    <h5 class="fw-bold mb-4"><i class="fa-solid fa-clipboard-check text-primary me-2"></i>ยืนยันคำสั่งซื้อ</h5>

    <form id="checkoutForm" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-lg-7">
                <!-- Shipping -->
                <div class="card p-4 mb-3">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-truck text-info me-2"></i>การจัดส่ง</h6>
                    <div class="form-check mb-2 p-3 rounded-3 border bg-light">
                        <input class="form-check-input ms-1" type="radio" name="shipping_type" id="ship_store" value="store_pickup" checked>
                        <label class="form-check-label fw-bold ms-2" for="ship_store">รับที่ร้าน <span class="badge bg-success-soft text-success ms-1" style="background: var(--success-soft);">ฟรี</span></label>
                    </div>
                    <div class="form-check p-3 rounded-3 border bg-light <?php echo !$can_deliver ? 'opacity-50' : ''; ?>">
                        <input class="form-check-input ms-1" type="radio" name="shipping_type" id="ship_delivery" value="delivery" <?php echo !$can_deliver ? 'disabled' : ''; ?>>
                        <label class="form-check-label fw-bold ms-2" for="ship_delivery">จัดส่งตามที่อยู่ (฿50)</label>
                        <?php if(!$can_deliver): ?>
                            <div class="text-danger small ms-2 mt-1"><i class="fa-solid fa-info-circle me-1"></i>สั่งขั้นต่ำ ฿100</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Address -->
                <div id="address_section" class="card p-4 mb-3 d-none">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-location-dot text-danger me-2"></i>ที่อยู่จัดส่ง</h6>
                    <?php if (!empty($address['home_no'])): ?>
                        <div class="p-3 bg-light rounded-3">
                            <div class="fw-bold"><?php echo $_SESSION['full_name']; ?></div>
                            <div class="text-muted small mt-1">
                                <?php echo $address['home_no']; ?> 
                                <?php echo !empty($address['moo']) ? 'ม.'.$address['moo'] : ''; ?> 
                                <?php echo !empty($address['soi']) ? 'ซ.'.$address['soi'] : ''; ?> 
                                <?php echo !empty($address['road']) ? 'ถ.'.$address['road'] : ''; ?><br>
                                ต.<?php echo $address['sub_dist_name']; ?> อ.<?php echo $address['dist_name']; ?> 
                                จ.<?php echo $address['prov_name']; ?> <?php echo $address['zip_code']; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning small mb-0 address-warning"><i class="fa-solid fa-exclamation-triangle me-1"></i>กรุณาอัปเดตที่อยู่ในโปรไฟล์ก่อน</div>
                    <?php endif; ?>
                </div>

                <!-- Payment -->
                <div class="card p-4">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-wallet text-success me-2"></i>ชำระเงิน</h6>
                    <div class="form-check mb-2 p-3 rounded-3 border bg-light">
                        <input class="form-check-input ms-1" type="radio" name="payment_type" id="pay_cod" value="COD" checked>
                        <label class="form-check-label fw-bold ms-2" for="pay_cod"><i class="fa-solid fa-money-bill me-1"></i>เงินสด / ปลายทาง</label>
                    </div>
                    <div class="form-check p-3 rounded-3 border bg-light">
                        <input class="form-check-input ms-1" type="radio" name="payment_type" id="pay_transfer" value="Transfer">
                        <label class="form-check-label fw-bold ms-2" for="pay_transfer"><i class="fa-solid fa-qrcode me-1"></i>โอนเงิน / PromptPay</label>
                        
                        <div id="slip_upload_div" class="mt-3 d-none">
                            <div class="text-center p-3 bg-white rounded-3 border mb-3">
                                <div class="small fw-bold text-muted mb-2">สแกน QR Code เพื่อชำระเงิน</div>
                                <img id="promptpay_qr" src="" alt="QR" class="rounded-3" style="max-width: 180px;">
                                <div class="fw-bold text-danger fs-5 mt-2" id="qr_amount_display">฿0.00</div>
                            </div>
                            <label class="form-label fw-bold small">แนบสลิป <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" type="file" name="slip_img" id="slip_img" accept="image/*">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-bold small">หมายเหตุ (ถ้ามี)</label>
                        <textarea class="form-control" name="order_remark" rows="2" placeholder="เช่น ฝากไว้หน้าบ้าน..."></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card p-4 position-sticky" style="top: 80px;">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-receipt text-primary me-2"></i>สรุปรายการ</h6>
                    
                    <?php foreach($items as $item): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="../uploads/products/<?php echo $item['img']; ?>" alt="" 
                             style="width: 48px; height: 48px; border-radius: 10px; object-fit: cover; background: #f8fafc;"
                             onerror="this.src='https://placehold.co/100x100?text=No+Image'">
                        <div class="flex-grow-1">
                            <div class="fw-bold small"><?php echo $item['prod_name']; ?></div>
                            <div class="text-muted small"><?php echo $item['quantity']; ?> x ฿<?php echo number_format($item['price'], 2); ?></div>
                        </div>
                        <span class="fw-bold small">฿<?php echo number_format($item['subtotal'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>

                    <hr>
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>สินค้า</span><span>฿<?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>ค่าจัดส่ง</span><span id="display_shipping">฿0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">ยอดสุทธิ</span>
                        <span class="fw-bold text-primary fs-5" id="display_net_total">฿<?php echo number_format($total_price, 2); ?></span>
                    </div>

                    <input type="hidden" id="input_shipping_cost" name="shipping_cost" value="0">
                    <input type="hidden" id="input_net_total" name="total_price" value="<?php echo $total_price; ?>">

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">
                        <i class="fa-solid fa-check-circle me-1"></i>ยืนยันสั่งซื้อ
                    </button>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
$(document).ready(function() {
    const baseTotal = <?php echo $total_price; ?>;

    function updateQRCode(amount) {
        if ($('input[name="payment_type"]:checked').val() === 'Transfer') {
            $.getJSON('../api/payment/get_qr.php', { amount: amount }, function(res) {
                if(res.status === 'success') {
                    $('#promptpay_qr').attr('src', res.qr_url);
                    $('#qr_amount_display').text('฿' + parseFloat(res.amount).toFixed(2));
                }
            });
        }
    }

    $('input[name="shipping_type"]').change(function() {
        let ship = $(this).val();
        let cost = ship === 'delivery' ? 50 : 0;
        ship === 'delivery' ? $('#address_section').removeClass('d-none') : $('#address_section').addClass('d-none');
        let net = baseTotal + cost;
        $('#display_shipping').text('฿' + cost.toFixed(2));
        $('#display_net_total').text('฿' + net.toFixed(2));
        $('#input_shipping_cost').val(cost);
        $('#input_net_total').val(net);
        updateQRCode(net);
    });

    $('input[name="payment_type"]').change(function() {
        if ($(this).val() === 'Transfer') {
            $('#slip_upload_div').removeClass('d-none');
            $('#slip_img').prop('required', true);
            updateQRCode(parseFloat($('#input_net_total').val()));
        } else {
            $('#slip_upload_div').addClass('d-none');
            $('#slip_img').prop('required', false);
        }
    });

    $('#checkoutForm').on('submit', function(e) {
        e.preventDefault();
        if($('input[name="shipping_type"]:checked').val() === 'delivery' && $('.address-warning').length > 0) {
            Swal.fire('แจ้งเตือน', 'กรุณาอัปเดตที่อยู่จัดส่งก่อน', 'warning');
            return;
        }
        Swal.fire({ title: 'กำลังดำเนินการ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: '../api/order/place_order.php', type: 'POST',
            data: new FormData(this), contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'สั่งซื้อสำเร็จ!', text: 'รหัส ' + res.order_id, confirmButtonText: 'ดูประวัติ', confirmButtonColor: '#6366f1' })
                    .then(() => { window.location.href = 'my_orders.php'; });
                } else {
                    Swal.fire('ข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    });
});
</script>

<?php 
$stmt_cart->close();
$stmt_addr->close();
include_once '../includes/footer.php'; 
?>