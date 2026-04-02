<?php
include_once '../includes/header.php';
if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit; }
?>

<main class="page-content fade-up" style="padding-bottom: 0;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-cash-register text-primary me-2"></i>POS ขายสินค้า</h5>
        <a href="pos_scanner_mobile.php" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-mobile-screen-button me-1"></i>เปิดกล้องสแกน
        </a>
    </div>

    <div class="row g-3" style="height: calc(100vh - 150px);">
        <!-- Left: Items -->
        <div class="col-lg-8 d-flex flex-column">
            <div class="card flex-grow-1 d-flex flex-column overflow-hidden p-0">
                <div class="px-4 pt-3 pb-2 border-bottom">
                    <div class="row small fw-bold text-muted text-uppercase">
                        <div class="col-5">สินค้า</div>
                        <div class="col-2 text-center">ราคา</div>
                        <div class="col-3 text-center">จำนวน</div>
                        <div class="col-2 text-end">รวม</div>
                    </div>
                </div>
                <div class="flex-grow-1 overflow-auto p-3" id="posItemsContainer">
                    <div id="empty-state" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-barcode fa-3x mb-3 opacity-15"></i>
                        <h6 class="fw-bold text-secondary">รอสแกนบาร์โค้ด...</h6>
                        <p class="small mb-0">สแกนจากมือถือ ข้อมูลจะขึ้นที่นี่อัตโนมัติ</p>
                    </div>
                    <div id="posItemList" class="d-flex flex-column gap-2"></div>
                </div>
            </div>
        </div>

        <!-- Right: Checkout -->
        <div class="col-lg-4 d-flex flex-column">
            <div class="card flex-grow-1 d-flex flex-column p-4">
                <div class="text-center p-3 rounded-3 mb-3" style="background: var(--primary-light);">
                    <div class="small text-muted fw-bold mb-1">ยอดชำระ</div>
                    <h2 class="text-primary fw-bold mb-0" id="totalAmountDisplay">฿0.00</h2>
                </div>

                <h6 class="fw-bold mb-2"><i class="fa-solid fa-wallet text-success me-1"></i>ชำระเงิน</h6>
                <div class="d-flex gap-2 mb-3">
                    <input type="radio" class="btn-check" name="payment_type" id="pay_cash" value="Cash" checked>
                    <label class="btn btn-outline-success flex-fill py-2 rounded-3" for="pay_cash">
                        <i class="fa-solid fa-money-bill-wave me-1"></i>เงินสด
                    </label>
                    <input type="radio" class="btn-check" name="payment_type" id="pay_transfer" value="Transfer">
                    <label class="btn btn-outline-info flex-fill py-2 rounded-3" for="pay_transfer">
                        <i class="fa-solid fa-qrcode me-1"></i>โอนเงิน
                    </label>
                </div>

                <div id="cashInputSection" class="mb-3">
                    <label class="form-label small fw-bold">รับเงินมา (฿)</label>
                    <input type="number" class="form-control fs-5 fw-bold text-success" id="cashReceived" min="0" placeholder="0.00">
                    <div class="d-flex justify-content-between mt-2 text-danger fw-bold d-none" id="changeSection">
                        <span>เงินทอน:</span>
                        <span class="fs-5" id="changeAmount">฿0.00</span>
                    </div>
                </div>

                <div class="mt-auto">
                    <button class="btn btn-primary w-100 py-3 fw-bold" id="btnCheckout" disabled>
                        <i class="fa-solid fa-check-circle me-1"></i>จบการขาย
                    </button>
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
            <div class="row align-items-center p-2 rounded-3 border" style="background: var(--surface-alt);">
                <div class="col-5">
                    <div class="fw-bold" style="font-size: 0.88rem;">${item.prod_name}</div>
                    <small class="text-muted">${item.barcode || '-'}</small>
                    ${item.qty > item.stock_qty ? `<div class="text-danger small"><i class="fa-solid fa-exclamation-triangle"></i> สต็อก ${item.stock_qty}</div>` : ''}
                </div>
                <div class="col-2 text-center text-muted fw-bold small">${formatMoney(item.price)}</div>
                <div class="col-3 text-center">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary px-2" onclick="updateQty(${index}, -1)"><i class="fa-solid fa-minus" style="font-size:0.65rem;"></i></button>
                        <input type="number" class="form-control text-center fw-bold px-1 border-secondary" value="${item.qty}" min="1" max="${item.stock_qty}" onchange="setQty(${index}, this.value)" style="width: 44px; font-size: 0.85rem;">
                        <button class="btn btn-outline-secondary px-2" onclick="updateQty(${index}, 1)"><i class="fa-solid fa-plus" style="font-size:0.65rem;"></i></button>
                    </div>
                </div>
                <div class="col-2 text-end">
                    <div class="fw-bold text-primary small">${formatMoney(subtotal)}</div>
                    <button class="btn btn-sm text-danger p-0" onclick="removeItem(${index})"><i class="fa-solid fa-trash-can" style="font-size:0.75rem;"></i></button>
                </div>
            </div>`;
        container.append(rowHTML);
    });

    $('#totalAmountDisplay').text(formatMoney(total)).data('total', total);
    calculateChange();
}

function addToCart(product) {
    let existIndex = posCart.findIndex(item => item.prod_id === product.prod_id);
    if (existIndex !== -1) {
        if(posCart[existIndex].qty < product.stock_qty) {
            posCart[existIndex].qty += 1;
        } else {
            Swal.fire({ icon: 'warning', text: `สต็อกเหลือ ${product.stock_qty} ชิ้น`, toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            return;
        }
    } else {
        if(product.stock_qty < 1) {
            Swal.fire({ icon: 'error', text: 'สินค้าหมดสต็อก!', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            return;
        }
        product.qty = 1;
        posCart.push(product);
    }
    let audio = new Audio('https://www.soundjay.com/buttons/beep-07a.mp3');
    audio.play().catch(e => {});
    renderCart();
}

function updateQty(index, change) {
    let item = posCart[index];
    let newQty = item.qty + change;
    if (newQty < 1) { removeItem(index); return; }
    if (newQty > item.stock_qty) { Swal.fire('แจ้งเตือน', 'เกินสต็อก!', 'warning'); return; }
    item.qty = newQty;
    renderCart();
}

function setQty(index, value) {
    let item = posCart[index];
    let newQty = parseInt(value);
    if (isNaN(newQty) || newQty < 1) newQty = 1;
    if (newQty > item.stock_qty) { newQty = item.stock_qty; }
    item.qty = newQty;
    renderCart();
}

function removeItem(index) { posCart.splice(index, 1); renderCart(); }

function pollPOSQueue() {
    $.getJSON('../api/scanner/pull_pos_barcode.php', function(res) {
        if (res.status === 'success') addToCart(res.product);
        else if (res.status === 'error' && res.message) Swal.fire({ icon: 'error', text: res.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    });
}

function calculateChange() {
    let total = parseFloat($('#totalAmountDisplay').data('total')) || 0;
    let cash = parseFloat($('#cashReceived').val()) || 0;
    if (total > 0 && $('input[name="payment_type"]:checked').val() === 'Cash' && cash >= total) {
        $('#changeSection').removeClass('d-none');
        $('#changeAmount').text(formatMoney(cash - total));
    } else {
        $('#changeSection').addClass('d-none');
    }
}

$(document).ready(function() {
    pollingInterval = setInterval(pollPOSQueue, 1500);
    $('#cashReceived').on('input', calculateChange);
    $('input[name="payment_type"]').change(function() {
        $(this).val() === 'Transfer' ? $('#cashInputSection').hide() : $('#cashInputSection').show();
        calculateChange();
    });

    $('#btnCheckout').click(function() {
        if (posCart.length === 0) return;
        let paymentType = $('input[name="payment_type"]:checked').val();
        let total = parseFloat($('#totalAmountDisplay').data('total')) || 0;
        let cash = parseFloat($('#cashReceived').val()) || 0;
        if (paymentType === 'Cash' && cash < total) { Swal.fire('ยอดไม่พอ', 'กรุณารับเงินให้ครบ', 'error'); return; }

        Swal.fire({ title: 'ยืนยันจบการขาย?', icon: 'question', showCancelButton: true, confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#6366f1' })
        .then(r => {
            if (r.isConfirmed) {
                Swal.fire({ title: 'กำลังบันทึก...', didOpen: () => Swal.showLoading() });
                $.ajax({
                    url: '../api/sales/pos_checkout.php', type: 'POST', contentType: 'application/json',
                    data: JSON.stringify({ cart: posCart, payment_type: paymentType, total_price: total }),
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'ขายสำเร็จ!', text: res.message, confirmButtonColor: '#6366f1' }).then(() => { posCart = []; $('#cashReceived').val(''); renderCart(); });
                        } else { Swal.fire('ผิดพลาด', res.message, 'error'); }
                    }
                });
            }
        });
    });
});
</script>
<?php include_once '../includes/footer.php'; ?>
