<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

$user_id = $_SESSION['user_id'];

// 1. ดึงข้อมูลสินค้าจากตะกร้า
$sql_cart = "SELECT c.cart_id, c.quantity, p.prod_id, p.prod_name, p.price, (c.quantity * p.price) as subtotal 
             FROM cart_items c 
             JOIN products p ON c.prod_id = p.prod_id 
             WHERE c.user_id = ?";
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

// 2. ดึงข้อมูลที่อยู่ของลูกค้า
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

$shipping_cost = 50; // สมมติค่าส่ง 50 บาท
// ลบ net_total จากเซิร์ฟเวอร์เพื่อให้ JS คำนวณเอง
//$net_total = $total_price + $shipping_cost;
$can_deliver = $total_price >= 100;
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-4"><i class="fa-solid fa-clipboard-check text-primary me-2"></i>ยืนยันคำสั่งซื้อ</h2>

        <form id="checkoutForm" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-truck text-info me-2"></i>การจัดส่ง</h5>
                            
                            <div class="form-check mb-3 p-3 border rounded bg-light">
                                <input class="form-check-input ms-1" type="radio" name="shipping_type" id="ship_store" value="store_pickup" checked>
                                <label class="form-check-label fw-bold ms-2" for="ship_store">รับที่ร้าน (ฟรีค่าจัดส่ง)</label>
                            </div>

                            <div class="form-check p-3 border rounded bg-light <?php echo !$can_deliver ? 'opacity-50' : ''; ?>">
                                <input class="form-check-input ms-1" type="radio" name="shipping_type" id="ship_delivery" value="delivery" <?php echo !$can_deliver ? 'disabled' : ''; ?>>
                                <label class="form-check-label fw-bold ms-2" for="ship_delivery">จัดส่งตามที่อยู่ (ค่าส่ง ฿50)</label>
                                <?php if (!$can_deliver): ?>
                                    <div class="text-danger small mt-1 ms-2"><i class="fa-solid fa-circle-exclamation me-1"></i>ต้องมียอดสั่งซื้อขั้นต่ำ 100 บาท เพื่อใช้บริการจัดส่ง</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="address_section" class="card border-0 shadow-sm rounded-4 mb-4 d-none">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-location-dot text-danger me-2"></i>ที่อยู่จัดส่ง</h5>
                            <?php if (!empty($address['home_no'])): ?>
                                <div class="p-3 bg-light rounded border">
                                    <p class="mb-1 fw-bold"><?php echo $_SESSION['full_name']; ?></p>
                                    <p class="mb-0 text-muted small">
                                        บ้านเลขที่ <?php echo $address['home_no']; ?> 
                                        <?php echo !empty($address['moo']) ? 'ม.'.$address['moo'] : ''; ?> 
                                        <?php echo !empty($address['soi']) ? 'ซ.'.$address['soi'] : ''; ?> 
                                        <?php echo !empty($address['road']) ? 'ถ.'.$address['road'] : ''; ?> <br>
                                        ต.<?php echo $address['sub_dist_name']; ?> อ.<?php echo $address['dist_name']; ?> 
                                        จ.<?php echo $address['prov_name']; ?> <?php echo $address['zip_code']; ?>
                                    </p>
                                    <?php if(!empty($address['remark'])): ?>
                                        <p class="mb-0 text-muted small mt-2"><strong>หมายเหตุ:</strong> <?php echo $address['remark']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="address_id" value="<?php echo $_SESSION['user_id']; /* สมมติอ้างอิงจากระบบเดิม */ ?>">
                            <?php else: ?>
                                <div class="alert alert-warning address-warning">กรุณาอัปเดตที่อยู่จัดส่งในหน้าโปรไฟล์ก่อนสั่งซื้อ (ไปที่หน้าตั้งค่าข้อมูลตัวเอง)</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-wallet text-success me-2"></i>ช่องทางการชำระเงิน</h5>
                            
                            <div class="form-check mb-3 p-3 border rounded bg-light">
                                <input class="form-check-input ms-1" type="radio" name="payment_type" id="pay_cod" value="COD" checked>
                                <label class="form-check-label fw-bold ms-2" for="pay_cod">เก็บเงินปลายทาง (COD)</label>
                            </div>

                            <div class="form-check p-3 border rounded bg-light">
                                <input class="form-check-input ms-1" type="radio" name="payment_type" id="pay_transfer" value="Transfer">
                                <label class="form-check-label fw-bold ms-2" for="pay_transfer">โอนเงินผ่านธนาคาร</label>
                                
                                <div id="slip_upload_div" class="mt-3 d-none">
                                    <div class="alert alert-info small py-2 mb-3 text-center">
                                        <strong>สแกน QR Code ด้วยแอปธนาคารใดก็ได้ (PromptPay)</strong><br>
                                        <div class="my-2 bg-white d-inline-block p-2 rounded shadow-sm">
                                            <img id="promptpay_qr" src="" alt="PromptPay QR Code" class="img-fluid" style="max-width: 180px;">
                                        </div><br>
                                        <span>ยอดที่ต้องโอน: <strong id="qr_amount_display" class="text-danger fs-5">฿0.00</strong></span>
                                    </div>
                                    <label class="form-label small fw-bold">แนบสลิปโอนเงิน <span class="text-danger">*</span></label>
                                    <input class="form-control form-control-sm" type="file" name="slip_img" id="slip_img" accept="image/*">
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="form-label fw-bold">หมายเหตุถึงร้านค้า (ถ้ามี)</label>
                                <textarea class="form-control" name="order_remark" rows="2" placeholder="เช่น ฝากไว้หน้าบ้าน..."></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">รายการสินค้า (<?php echo count($items); ?> ชิ้น)</h5>
                            
                            <ul class="list-group list-group-flush mb-4">
                                <?php foreach($items as $item): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo $item['prod_name']; ?></h6>
                                        <small class="text-muted"><?php echo $item['quantity']; ?> x ฿<?php echo number_format($item['price'], 2); ?></small>
                                    </div>
                                    <span class="fw-bold">฿<?php echo number_format($item['subtotal'], 2); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>

                            <div class="d-flex justify-content-between mb-2 text-muted">
                                <span>ยอดรวมสินค้า</span>
                                <span>฿<?php echo number_format($total_price, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom text-muted">
                                <span>ค่าจัดส่ง</span>
                                <span id="display_shipping">฿0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <h5 class="fw-bold mb-0">ยอดสุทธิที่ต้องชำระ</h5>
                                <h4 class="fw-bold text-primary mb-0" id="display_net_total">฿<?php echo number_format($total_price, 2); ?></h4>
                            </div>
                            
                            <input type="hidden" id="input_shipping_cost" name="shipping_cost" value="0">
                            <input type="hidden" id="input_net_total" name="total_price" value="<?php echo $total_price; ?>">
                            
                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm">
                                ยืนยันการสั่งซื้อ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
$(document).ready(function() {
    const baseTotal = <?php echo $total_price; ?>;

    function updateQRCode(amount) {
        if ($('input[name="payment_type"]:checked').val() === 'Transfer') {
            $.getJSON('../api/payment/get_qr.php', { amount: amount }, function(res) {
                if(res.status === 'success') {
                    $('#promptpay_qr').attr('src', res.qr_url);
                    $('#qr_amount_display').text('฿' + res.amount.toFixed(2));
                }
            });
        }
    }

    // คำนวณค่าส่งเมื่อเปลี่ยนตัวเลือกการจัดส่ง
    $('input[name="shipping_type"]').change(function() {
        let shippingVal = $(this).val();
        let shippingCost = 0;

        if (shippingVal === 'delivery') {
            $('#address_section').removeClass('d-none');
            shippingCost = 50;
        } else {
            $('#address_section').addClass('d-none');
            shippingCost = 0;
        }

        let newNetTotal = baseTotal + shippingCost;
        
        $('#display_shipping').text('฿' + shippingCost.toFixed(2));
        $('#display_net_total').text('฿' + newNetTotal.toFixed(2));
        
        $('#input_shipping_cost').val(shippingCost);
        $('#input_net_total').val(newNetTotal);
        
        // อัปเดตยอดโอนด้วย
        updateQRCode(newNetTotal);
    });

    // ซ่อน/แสดง ช่องแนบสลิป
    $('input[name="payment_type"]').change(function() {
        if ($(this).val() === 'Transfer') {
            $('#slip_upload_div').removeClass('d-none');
            $('#slip_img').prop('required', true);
            let currentNetTotal = parseFloat($('#input_net_total').val());
            updateQRCode(currentNetTotal);
        } else {
            $('#slip_upload_div').addClass('d-none');
            $('#slip_img').prop('required', false);
        }
    });

    // ส่งข้อมูลสั่งซื้อ
    $('#checkoutForm').on('submit', function(e) {
        e.preventDefault();
        
        // เช็คว่าเลือกส่งตามที่อยู่ แต่ยังไม่มีที่อยู่
        if($('input[name="shipping_type"]:checked').val() === 'delivery' && $('.address-warning').length > 0) {
            Swal.fire('แจ้งเตือน', 'คุณเลือกจัดส่งตามที่อยู่ แต่ยังไม่ได้อัปเดตที่อยู่จัดส่ง', 'warning');
            return;
        }

        let formData = new FormData(this);

        Swal.fire({
            title: 'กำลังดำเนินการ...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '../api/order/place_order.php', // ไฟล์ถัดไปที่เราจะสร้าง
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สั่งซื้อสำเร็จ!',
                        text: 'รหัสคำสั่งซื้อของคุณคือ ' + res.order_id,
                        confirmButtonText: 'ดูประวัติการสั่งซื้อ'
                    }).then(() => {
                        window.location.href = 'my_orders.php';
                    });
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