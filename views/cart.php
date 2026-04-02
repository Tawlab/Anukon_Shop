<?php
include_once '../includes/header.php';

if ($_SESSION['role'] !== 'customer') {
    header("Location: dashboard.php");
    exit;
}

$uid = $_SESSION['user_id'];
$sql = "SELECT ci.*, p.prod_name, p.price, p.img, 
        (SELECT COALESCE(SUM(s.total_qty),0) FROM stocks s WHERE s.prod_id = p.prod_id) as total_qty
        FROM cart_items ci 
        JOIN products p ON ci.prod_id = p.prod_id 
        WHERE ci.user_id = ? ORDER BY ci.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

$total = 0;
$cart_data = [];
while ($row = $items->fetch_assoc()) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $cart_data[] = $row;
}
?>

<style>
    /* Cart Specific Styles */
    .cart-item-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.03);
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        display: flex;
        gap: 20px;
        align-items: center;
        transition: all 0.3s;
    }

    .cart-item-card:hover {
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.08);
        border-color: rgba(99, 102, 241, 0.1);
    }

    .cart-img-wrap {
        width: 100px;
        height: 100px;
        border-radius: 12px;
        overflow: hidden;
        background: #f8fafc;
        flex-shrink: 0;
    }

    .cart-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cart-item-details {
        flex-grow: 1;
    }

    .cart-item-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .cart-item-price {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--primary);
    }

    .qty-control {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        border-radius: 50px;
        padding: 4px;
    }

    .btn-qty {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none;
        background: #fff;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.2s;
    }

    .btn-qty:hover {
        background: var(--primary);
        color: #fff;
    }

    .qty-val {
        width: 36px;
        text-align: center;
        font-weight: 600;
        font-size: 1rem;
        color: #1e293b;
    }

    .btn-remove {
        background: #fee2e2;
        color: #ef4444;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        cursor: pointer;
    }

    .btn-remove:hover {
        background: #ef4444;
        color: #fff;
    }

    /* Summary Card */
    .summary-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 28px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    }

    .empty-cart-state {
        text-align: center;
        padding: 80px 20px;
        background: #fff;
        border-radius: 24px;
        border: 1px dashed #cbd5e1;
    }

    .empty-cart-icon {
        width: 120px;
        height: 120px;
        background: #f1f5f9;
        color: #94a3b8;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        margin-bottom: 24px;
    }

    @media (max-width: 768px) {
        .cart-item-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .cart-img-wrap {
            width: 100%;
            height: 160px;
        }

        .cart-actions {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    }
</style>

<main class="page-content fade-up" style="max-width: 1200px; margin: 0 auto;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1e293b;">ตะกร้าสินค้า</h4>
            <p class="text-muted small mb-0">ตรวจสอบและจัดการรายการสินค้าของคุณ</p>
        </div>
        <a href="shop.php" class="btn btn-outline-primary rounded-pill px-4">
            <i class="fa-solid fa-plus me-2"></i>เลือกสินค้าเพิ่ม
        </a>
    </div>

    <?php if (count($cart_data) > 0): ?>
        <div class="row g-4">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <?php foreach ($cart_data as $item):
                    $img_src = !empty($item['img']) ? '../uploads/products/' . $item['img'] : 'https://placehold.co/100x100?text=No+Image';
                    ?>
                    <div class="cart-item-card" id="cart-item-<?php echo $item['cart_id']; ?>">
                        <div class="cart-img-wrap">
                            <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($item['prod_name']); ?>"
                                onerror="this.src='https://placehold.co/100x100?text=No+Image'">
                        </div>

                        <div class="cart-item-details">
                            <div class="cart-item-title"><?php echo htmlspecialchars($item['prod_name']); ?></div>
                            <div class="cart-item-price mb-2">฿<?php echo number_format($item['price'], 2); ?></div>
                            <div class="text-muted small">
                                <?php if ($item['total_qty'] > 0): ?>
                                    <span style="color: #22c55e;"><i class="fa-solid fa-check-circle me-1"></i>มีสินค้า
                                        (<?php echo $item['total_qty']; ?>ชิ้น)</span>
                                <?php else: ?>
                                    <span class="text-danger"><i
                                            class="fa-solid fa-triangle-exclamation me-1"></i>สินค้าหมดชั่วคราว</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="cart-actions d-flex align-items-center gap-4">
                            <div class="qty-control shadow-sm">
                                <button class="btn-qty" onclick="updateQty(<?php echo $item['cart_id']; ?>, -1)"><i
                                        class="fa-solid fa-minus"></i></button>
                                <div class="qty-val" id="qty-<?php echo $item['cart_id']; ?>">
                                    <?php echo $item['quantity']; ?>
                                </div>
                                <button class="btn-qty" onclick="updateQty(<?php echo $item['cart_id']; ?>, 1)"><i
                                        class="fa-solid fa-plus"></i></button>
                            </div>
                            <button class="btn-remove" title="ลบสินค้า"
                                onclick="removeItem(<?php echo $item['cart_id']; ?>)">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary Sidebar -->
            <div class="col-lg-4">
                <div class="summary-card position-sticky" style="top: 100px;">
                    <h5 class="fw-bold mb-4" style="color: #1e293b;"><i
                            class="fa-solid fa-receipt me-2 text-primary"></i>สรุปคำสั่งซื้อ</h5>

                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>จำนวนสินค้าทั้งหมด</span>
                        <span class="fw-medium"><?php echo count($cart_data); ?> รายการ</span>
                    </div>

                    <hr style="border-color: #e2e8f0; margin: 24px 0;">

                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <span class="fw-bold text-dark fs-5">ยอดรวมสุทธิ</span>
                        <span class="fw-bold text-primary" style="font-size: 1.8rem;"
                            id="totalPrice">฿<?php echo number_format($total, 2); ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-primary w-100 py-3 rounded-pill fw-bold"
                        style="font-size: 1.05rem;">
                        ดำเนินการชำระเงิน <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>

                    <div class="text-center mt-3">
                        <div class="small text-muted"><i class="fa-solid fa-shield-halved me-1 text-success"></i>
                            ชำระเงินได้อย่างปลอดภัย 100%</div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-cart-state">
            <div class="empty-cart-icon shadow-sm">
                <i class="fa-solid fa-cart-arrow-down"></i>
            </div>
            <h3 class="fw-bold mb-3" style="color: #1e293b;">ตะกร้าของคุณยังว่างเปล่า</h3>
            <p class="text-muted mb-4" style="font-size: 1.1rem;">ดูเหมือนว่าคุณยังไม่ได้เพิ่มสินค้าใดๆ ลงในตะกร้า
                <br>ลองไปเลือกดูสินค้าที่น่าสนใจของเราสิ!
            </p>
            <a href="shop.php" class="btn btn-primary rounded-pill px-5 py-3 fw-bold" style="font-size: 1.1rem;">
                <i class="fa-solid fa-bag-shopping me-2"></i>ไปเลือกซื้อสินค้า
            </a>
        </div>
    <?php endif; ?>

</main>

<script>
    function updateQty(cartId, delta) {
        let qtyEl = $(`#qty-${cartId}`);
        let newQty = parseInt(qtyEl.text()) + delta;

        if (newQty < 1) {
            removeItem(cartId);
            return;
        }

        // Disable buttons temporarily
        $('.btn-qty').prop('disabled', true);
        
        let action = delta > 0 ? 'plus' : 'minus';

        $.ajax({
            url: '../api/cart/update_cart.php',
            type: 'POST',
            data: { cart_id: cartId, action: action },
            dataType: 'json',
            success: function (res) {
                $('.btn-qty').prop('disabled', false);
                if (res.status === 'success') {
                    location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'ไม่สามารถเพิ่มจำนวนได้', text: res.message });
                }
            },
            error: function () {
                $('.btn-qty').prop('disabled', false);
                Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    }

    function removeItem(cartId) {
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: 'คุณต้องการลบสินค้านี้ออกจากตะกร้าใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ลบรายการ',
            cancelButtonText: 'ยกเลิก'
        }).then(r => {
            if (r.isConfirmed) {
                $.post('../api/cart/remove_item.php', { cart_id: cartId }, function (res) {
                    if (res.status === 'success') {
                        location.reload();
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }, 'json');
            }
        });
    }
</script>

<?php include_once '../includes/footer.php'; ?>