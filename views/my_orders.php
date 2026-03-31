<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลบิลคำสั่งซื้อของลูกค้าคนนี้ เรียงจากล่าสุดไปเก่าสุด
$sql = "SELECT sale_id, sale_date, total_price, payment_type, sale_status 
        FROM bill_sales 
        WHERE user_id = ? 
        ORDER BY sale_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        <h2 class="fw-bold mb-4"><i class="fa-solid fa-clipboard-list text-primary me-2"></i>ประวัติการสั่งซื้อของฉัน</h2>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>หมายเลขคำสั่งซื้อ</th>
                                    <th>วันที่สั่งซื้อ</th>
                                    <th>ยอดรวม</th>
                                    <th>การชำระเงิน</th>
                                    <th>สถานะ</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): 
                                    // จัดการแปลงรูปแบบวันที่ให้สวยงาม
                                    $date = date_create($row['sale_date']);
                                    $formatted_date = date_format($date, "d M Y H:i");
                                    
                                    // กำหนดสีและข้อความของสถานะ
                                    $status_text = '';
                                    $status_badge = '';
                                    switch($row['sale_status']) {
                                        case 0:
                                            $status_text = 'ยกเลิกแล้ว';
                                            $status_badge = 'bg-danger';
                                            break;
                                        case 1:
                                            $status_text = 'รอดำเนินการ';
                                            $status_badge = 'bg-warning text-dark';
                                            break;
                                        case 2:
                                            $status_text = 'กำลังจัดส่ง';
                                            $status_badge = 'bg-info text-dark';
                                            break;
                                        case 3:
                                            $status_text = 'สำเร็จ';
                                            $status_badge = 'bg-success';
                                            break;
                                        default:
                                            $status_text = 'ไม่ทราบสถานะ';
                                            $status_badge = 'bg-secondary';
                                    }
                                ?>
                                <tr>
                                    <td class="fw-bold text-primary">
                                        #ORD-<?php echo str_pad($row['sale_id'], 4, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td class="text-muted small"><?php echo $formatted_date; ?></td>
                                    <td class="fw-bold">฿<?php echo number_format($row['total_price'], 2); ?></td>
                                    <td>
                                        <?php if($row['payment_type'] == 'COD'): ?>
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-truck me-1"></i> ปลายทาง</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-primary border"><i class="fa-solid fa-building-columns me-1"></i> โอนเงิน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?php echo $status_badge; ?> px-2 py-1"><?php echo $status_text; ?></span></td>
                                    <td class="text-center">
                                        <a href="order_detail.php?id=<?php echo $row['sale_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            ดูรายละเอียด
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-box-open fa-4x text-muted opacity-25 mb-3"></i>
                        <h5 class="text-muted">ยังไม่มีประวัติการสั่งซื้อ</h5>
                        <p class="text-muted small">ลองไปดูสินค้าในร้านเราสิ อาจจะมีของที่คุณถูกใจ!</p>
                        <a href="shop.php" class="btn btn-primary rounded-pill mt-2 px-4">ไปเลือกซื้อสินค้าเลย</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</main>

<?php 
$stmt->close();
include_once '../includes/footer.php'; 
?>