<?php
require_once '../../config/db_config.php';

// รับค่าคำค้นหา (ถ้ามีการพิมพ์ค้นหามา)
$search = $_GET['search'] ?? '';
$search_param = "%{$search}%";

$type_id = $_GET['type_id'] ?? '';

// ดึงข้อมูลสินค้า เชื่อมกับตารางประเภทสินค้า (prod_types) และสต็อก (stocks)
$sql = "SELECT p.prod_id, p.prod_name, p.price, p.img, p.detail, 
               t.type_name, 
               COALESCE(s.total_qty, 0) as stock_qty 
        FROM products p
        LEFT JOIN prod_types t ON p.type_id = t.type_id
        LEFT JOIN stocks s ON p.prod_id = s.prod_id
        WHERE p.status = 1 AND p.prod_name LIKE ?";

$params = [$search_param];
$types = "s";

if (!empty($type_id)) {
    $sql .= " AND p.type_id = ?";
    $params[] = $type_id;
    $types .= "i";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$html = '';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $img_src = !empty($row['img']) ? '../assets/img/products/'.$row['img'] : 'https://placehold.co/300x300?text=No+Image';
        $stock_status = $row['stock_qty'] > 0 
            ? '<span class="badge bg-success bg-opacity-10 text-success mb-2">พร้อมส่ง ('.$row['stock_qty'].')</span>' 
            : '<span class="badge bg-danger bg-opacity-10 text-danger mb-2">สินค้าหมด</span>';
            
        $btn_disabled = $row['stock_qty'] > 0 ? '' : 'disabled';
        
        $html .= '
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm product-card transition-all">
                <img src="'.$img_src.'" class="card-img-top p-3" alt="'.$row['prod_name'].'" style="aspect-ratio: 1/1; object-fit: contain;">
                <div class="card-body d-flex flex-column">
                    <small class="text-muted mb-1">'.$row['type_name'].'</small>
                    <h6 class="card-title fw-bold mb-2 text-truncate">'.$row['prod_name'].'</h6>
                    '.$stock_status.'
                    <h5 class="fw-bold text-primary mt-auto mb-3">฿ '.number_format($row['price'], 2).'</h5>
                    <button class="btn btn-primary w-100 btn-sm rounded-pill" onclick="addToCart('.$row['prod_id'].')" '.$btn_disabled.'>
                        <i class="fa-solid fa-cart-plus me-1"></i> เพิ่มลงตะกร้า
                    </button>
                </div>
            </div>
        </div>';
    }
} else {
    $html = '
    <div class="col-12 text-center py-5">
        <i class="fa-solid fa-box-open fa-4x text-muted mb-3 opacity-50"></i>
        <h5 class="text-muted">ไม่พบสินค้าที่คุณค้นหา</h5>
    </div>';
}

echo $html;

$stmt->close();
$conn->close();
?>