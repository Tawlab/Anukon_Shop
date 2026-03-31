<?php
include_once '../includes/header.php';
include_once '../includes/sidebar.php';

if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('ไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href = 'dashboard.php';</script>";
    exit;
}
?>

<main id="content" class="flex-grow-1 bg-light">
    <div class="container-fluid py-4 h-100 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-cash-register me-2"></i>POS สแกนขายสินค้า</h3>
            <a href="pos_scanner_mobile.php" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-mobile-screen-button me-1"></i> เปิดกล้องมือถือสแกน
            </a>
        </div>

        <div class="row g-4 flex-grow-1">
            <!-- ซ้าย: รายการสินค้า -->
            <div class="col-lg-8 d-flex flex-column">
                <div class="card border-0 shadow-sm rounded-4 flex-grow-1 d-flex flex-column h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4">
                        <div class="row text-secondary fw-bold small">
                            <div class="col-5">สินค้า</div>
                            <div class="col-2 text-center">ราคา</div>
                            <div class="col-3 text-center">จำนวน</div>
                            <div class="col-2 text-end">รวม</div>
                        </div>
                        <hr class="mb-0">
                    </div>
                    
                    <!-- พื้นที่ใส่รายการ -->
                    <div class="card-body p-4 overflow-auto" id="posItemsContainer" style="max-height: 60vh;">
                        <div id="empty-state" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-barcode fa-4x mb-3 text-light"></i>
                            <h5 class="fw-bold text-secondary">รอการสแกนบาร์โค้ด...</h5>
                            <p class="small">นำมือถือสแกนสินค้า ข้อมูลจะเด้งขึ้นที่นี่อัตโนมัติ</p>
                        </div>
                        <div id="posItemList" class="d-flex flex-column gap-3"></div>
                    </div>
                </div>
            </div>

            <!-- ขวา: คิดเงิน -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                    <div class="card-body p-4">
                        <div class="mb-4 text-center p-3 bg-light rounded-3 border">
                            <h6 class="text-secondary fw-bold mb-1">ยอดชำระสุทธิ</h6>
                            <h1 class="text-primary fw-bold mb-0" style="font-size: 3rem;" id="totalAmountDisplay">฿0.00</h1>
                        </div>

                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-wallet text-success me-2"></i>วิธีชำระเงิน</h6>
                        
                        <div class="d-flex gap-2 mb-4">
                            <input type="radio" class="btn-check" name="payment_type" id="pay_cash" value="Cash" checked>
                            <label class="btn btn-outline-success flex-fill py-3 rounded-3" for="pay_cash">
                                <i class="fa-solid fa-money-bill-wave d-block mb-1 fs-4"></i>เงินสด
                            </label>

                            <input type="radio" class="btn-check" name="payment_type" id="pay_transfer" value="Transfer">
                            <label class="btn btn-outline-info flex-fill py-3 rounded-3" for="pay_transfer">
                                <i class="fa-solid fa-building-columns d-block mb-1 fs-4"></i>โอนเงิน
                            </label>
                        </div>
                        
                        <div id="cashInputSection" class="mb-4">
                            <label class="form-label small fw-bold">รับเงินสดมา (บาท)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0">฿</span>
                                <input type="number" class="form-control border-start-0 fs-4 text-success fw-bold" id="cashReceived" min="0" placeholder="0">
                            </div>
                            <div class="d-flex justify-content-between mt-2 text-danger fw-bold d-none" id="changeSection">
                                <span>เงินทอน:</span>
                                <span class="fs-5" id="changeAmount">฿0.00</span>
                            </div>
                        </div>

                        <hr>
                        <button class="btn btn-primary btn-lg w-100 py-3 rounded-3 fw-bold shadow mt-2" id="btnCheckout" disabled>
                            <i class="fa-solid fa-check-circle me-2"></i>จบการขาย (Checkout)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
let posCart = []; 
let pollingInterval;

function formatMoney(amount) {
    return '฿' + amount.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function renderCart() {
    let container = $('#posItemList');
    container.empty();
    let total = 0;

    if (posCart.length === 0) {
        $('#empty-state').show();
        $('#totalAmountDisplay').text('฿0.00');
        $('#btnCheckout').prop('disabled', true);
        calculateChange();
        return;
    }

    $('#empty-state').hide();
    $('#btnCheckout').prop('disabled', false);

    posCart.forEach((item, index) => {
        let subtotal = item.price * item.qty;
        total += subtotal;

        let rowHTML = `
            <div class="row align-items-center bg-white p-3 border rounded shadow-sm">
                <div class="col-5">
                    <h6 class="fw-bold mb-0 text-dark">${item.prod_name}</h6>
                    <small class="text-muted"><i class="fa-solid fa-barcode"></i> ${item.barcode}</small>
                    ${item.qty > item.stock_qty ? `<div class="text-danger small"><i class="fa-solid fa-circle-exclamation"></i> สต็อกมีแค่ ${item.stock_qty} ชิ้น</div>` : ''}
                </div>
                <div class="col-2 text-center fw-bold text-secondary">
                    ${formatMoney(item.price)}
                </div>
                <div class="col-3 text-center">
                    <div class="input-group input-group-sm w-100 mx-auto" style="max-width: 120px;">
                        <button class="btn btn-outline-secondary px-2" onclick="updateQty(${index}, -1)"><i class="fa-solid fa-minus"></i></button>
                        <input type="number" class="form-control text-center fw-bold px-1" value="${item.qty}" min="1" max="${item.stock_qty}" onchange="setQty(${index}, this.value)">
                        <button class="btn btn-outline-secondary px-2" onclick="updateQty(${index}, 1)"><i class="fa-solid fa-plus"></i></button>
                    </div>
                </div>
                <div class="col-2 text-end fw-bold text-primary pe-0 position-relative">
                    ${formatMoney(subtotal)}
                    <button class="btn text-danger position-absolute top-50 translate-middle-y" style="right: -40px;" onclick="removeItem(${index})" title="ลบรายการ">
                        <i class="fa-solid fa-circle-xmark fs-5"></i>
                    </button>
                </div>
            </div>
        `;
        container.append(rowHTML);
    });

    $('#totalAmountDisplay').text(formatMoney(total));
    $('#totalAmountDisplay').data('total', total); // เซฟค่าดิบไว้คำนวณเงินทอน
    calculateChange();
}

function addToCart(product) {
    let existIndex = posCart.findIndex(item => item.prod_id === product.prod_id);
    if (existIndex !== -1) {
        if(posCart[existIndex].qty < product.stock_qty) {
            posCart[existIndex].qty += 1;
        } else {
            // เล่นเสียง error แจ้งว่าสต็อกหมด
            Swal.fire({
                icon: 'warning', text: 'สินค้า "' + product.prod_name + '" สต็อกหมด (เหลือ ' + product.stock_qty + ' ชิ้น)', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
            });
            return;
        }
    } else {
        if(product.stock_qty < 1) {
             Swal.fire({
                icon: 'error', text: 'สินค้าหมดสต็อก ไม่สามารถขายได้!', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
            });
            return;
        }
        product.qty = 1;
        posCart.push(product);
    }
    
    // เล่นเสียงสำเร็จ
    let audio = new Audio('https://www.soundjay.com/buttons/beep-07a.mp3');
    audio.play().catch(e => {});

    renderCart();
}

function updateQty(index, change) {
    let item = posCart[index];
    let newQty = item.qty + change;
    
    if (newQty < 1) {
        removeItem(index);
        return;
    }
    
    if(newQty > item.stock_qty) {
         Swal.fire('แจ้งเตือน', 'เกินจำนวนสต็อกที่มี!', 'warning');
         return;
    }
    
    item.qty = newQty;
    renderCart();
}

function setQty(index, value) {
    let item = posCart[index];
    let newQty = parseInt(value);
    
    if (isNaN(newQty) || newQty < 1) newQty = 1;
    if (newQty > item.stock_qty) {
        newQty = item.stock_qty;
        Swal.fire('แจ้งเตือน', 'ยอดเกินสต็อก ปรับเป็นยอดคงเหลือสูงสุด', 'warning');
    }
    item.qty = newQty;
    renderCart();
}

function removeItem(index) {
    posCart.splice(index, 1);
    renderCart();
}

// ---------------- Polling Logic ---------------- 
function pollPOSQueue() {
    $.getJSON('../api/scanner/pull_pos_barcode.php', function(res) {
        if (res.status === 'success') {
            addToCart(res.product);
        } else if (res.status === 'error') {
            Swal.fire({icon: 'error', text: res.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
        }
    });
}
// -----------------------------------------------

// คำนวณเงินทอน
function calculateChange() {
    let total = parseFloat($('#totalAmountDisplay').data('total')) || 0;
    let cash = parseFloat($('#cashReceived').val()) || 0;
    
    if(total > 0 && $('input[name="payment_type"]:checked').val() === 'Cash') {
        if (cash >= total) {
            let change = cash - total;
            $('#changeSection').removeClass('d-none');
            $('#changeAmount').text(formatMoney(change));
        } else {
            $('#changeSection').addClass('d-none');
        }
    } else {
        $('#changeSection').addClass('d-none');
    }
}

$(document).ready(function() {
    pollingInterval = setInterval(pollPOSQueue, 1500); // 1.5 วินาที

    $('#cashReceived').on('input', calculateChange);
    
    $('input[name="payment_type"]').change(function() {
        if($(this).val() === 'Transfer') {
            $('#cashInputSection').hide();
        } else {
            $('#cashInputSection').show();
            calculateChange();
        }
    });

    $('#btnCheckout').click(function() {
        if (posCart.length === 0) return;
        
        let paymentType = $('input[name="payment_type"]:checked').val();
        let total = parseFloat($('#totalAmountDisplay').data('total')) || 0;
        let cash = parseFloat($('#cashReceived').val()) || 0;

        if (paymentType === 'Cash' && cash < total) {
            Swal.fire('ยอดเงินไม่พอ', 'กรุณารับเงินสดให้ครอบคลุมยอดสุทธิ', 'error');
            return;
        }

        Swal.fire({
            title: 'ยืนยันจบการขาย?', text: "ระบบจะทำการตัดสต็อกทันที", icon: 'question', showCancelButton: true, confirmButtonText: 'ยืนยันเลย!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'กำลังบันทึกรายการ...', didOpen: () => { Swal.showLoading(); }});

                $.ajax({
                    url: '../api/sales/pos_checkout.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        cart: posCart,
                        payment_type: paymentType,
                        total_price: total
                    }),
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('ขายสำเร็จ!', res.message, 'success').then(() => {
                                // Reset POS
                                posCart = [];
                                $('#cashReceived').val('');
                                renderCart();
                            });
                        } else {
                            Swal.fire('ข้อผิดพลาด', res.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>
<?php include_once '../includes/footer.php'; ?>
