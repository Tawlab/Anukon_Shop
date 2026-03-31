<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

// ดึงข้อมูลสินค้าในตะกร้าของลูกค้าคนนี้
$user_id = $_SESSION['user_id'];
$sql = "SELECT c.cart_id, c.quantity, p.prod_id, p.prod_name, p.price, p.img, (c.quantity * p.price) as subtotal 
        FROM cart_items c 
        JOIN products p ON c.prod_id = p.prod_id 
        WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_price = 0;
$has_items = $result->num_rows > 0;
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-4"><i class="fa-solid fa-cart-shopping text-primary me-2"></i>ตะกร้าสินค้าของฉัน</h2>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <?php if ($has_items): ?>
                            <ul class="list-group list-group-flush rounded-4">
                                <?php while($row = $result->fetch_assoc()): 
                                    $total_price += $row['subtotal'];
                                    $img_src = !empty($row['img']) ? '../assets/img/products/'.$row['img'] : 'https://placehold.co/100x100?text=No+Image';
                                ?>
                                <li class="list-group-item p-4 border-bottom">
                                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                                        <!-- รูปภาพ -->
                                        <div class="text-center" style="width: 80px; flex-shrink: 0;">
                                            <img src="<?php echo $img_src; ?>" class="img-fluid rounded shadow-sm" alt="product">
                                        </div>
                                        <!-- ข้อมูลสินค้า -->
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-1 text-dark"><?php echo $row['prod_name']; ?></h6>
                                            <span class="text-primary fw-semibold">฿<?php echo number_format($row['price'], 2); ?></span>
                                        </div>
                                        <!-- จัดการจำนวนและลบ -->
                                        <div class="d-flex justify-content-between align-items-center gap-4 mt-2 mt-sm-0">
                                            <div class="input-group input-group-sm" style="width: 110px;">
                                                <button class="btn btn-outline-secondary px-2" onclick="updateQty(<?php echo $row['cart_id']; ?>, 'minus')"><i class="fa-solid fa-minus"></i></button>
                                                <input type="text" class="form-control text-center fw-bold bg-light" value="<?php echo $row['quantity']; ?>" readonly>
                                                <button class="btn btn-outline-secondary px-2" onclick="updateQty(<?php echo $row['cart_id']; ?>, 'plus')"><i class="fa-solid fa-plus"></i></button>
                                            </div>
                                            <div class="text-end" style="min-width: 80px;">
                                                <p class="fw-bold text-dark mb-1 d-none d-sm-block">฿<?php echo number_format($row['subtotal'], 2); ?></p>
                                                <button class="btn btn-sm btn-link text-danger p-0 text-decoration-none" onclick="removeItem(<?php echo $row['cart_id']; ?>)">
                                                    <i class="fa-solid fa-trash"></i> เอาออก
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fa-solid fa-basket-shopping fa-4x text-muted opacity-25 mb-3"></i>
                                <h5 class="text-muted">ไม่มีสินค้าในตะกร้า</h5>
                                <a href="shop.php" class="btn btn-primary rounded-pill mt-3">ไปช้อปเลย!</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">สรุปคำสั่งซื้อ</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">ยอดรวมสินค้า</span>
                            <span class="fw-bold">฿<?php echo number_format($total_price, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                            <span class="text-muted">ค่าจัดส่ง</span>
                            <span>คำนวณในขั้นตอนถัดไป</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <h5 class="fw-bold mb-0">ยอดสุทธิ</h5>
                            <h4 class="fw-bold text-primary mb-0">฿<?php echo number_format($total_price, 2); ?></h4>
                        </div>
                        
                        <button class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm" <?php echo !$has_items ? 'disabled' : ''; ?> onclick="window.location.href='checkout.php'">
                            ดำเนินการสั่งซื้อ <i class="fa-solid fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// เตรียมฟังก์ชันสำหรับเรียก API อัปเดตจำนวนหรือลบสินค้า
function updateQty(cart_id, action) {
    $.ajax({
        url: '../api/cart/update_cart.php',
        type: 'POST',
        data: { cart_id: cart_id, action: action },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                location.reload(); // รีเฟรชหน้าเพื่อคำนวณยอดใหม่
            } else {
                Swal.fire('ข้อผิดพลาด', res.message, 'error');
            }
        }
    });
}

function removeItem(cart_id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบสินค้านี้ออกจากตะกร้าใช่หรือไม่",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบสินค้า',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/cart/remove_item.php',
                type: 'POST',
                data: { cart_id: cart_id },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        location.reload(); // รีเฟรชหน้าเพื่อลบรายการออก
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

<?php 
$stmt->close();
include_once '../includes/footer.php'; 
?>