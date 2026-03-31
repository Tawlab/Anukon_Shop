<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

// ป้องกันไม่ให้ Customer แอบเข้าหน้านี้
if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('ไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href = 'dashboard.php';</script>";
    exit;
}

// 1. ดึงข้อมูลสินค้าทั้งหมดพร้อมหมวดหมู่และสต็อก
$sql_products = "SELECT p.prod_id, p.prod_name, p.barcode, p.price, p.img, p.status, 
                        t.type_name, 
                        COALESCE(s.total_qty, 0) as stock_qty 
                 FROM products p
                 LEFT JOIN prod_types t ON p.type_id = t.type_id
                 LEFT JOIN stocks s ON p.prod_id = s.prod_id
                 ORDER BY p.prod_id DESC";
$result_products = $conn->query($sql_products);

// 2. ดึงหมวดหมู่สินค้ามาเตรียมไว้สำหรับ Dropdown ใน Modal เพิ่มสินค้า
$sql_types = "SELECT * FROM prod_types WHERE type_status = 1";
$result_types = $conn->query($sql_types);
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>คลังสินค้า (สต็อก)</h2>
            <div class="d-flex gap-2">
                <a href="scanner_mobile.php" target="_blank" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-mobile-screen-button me-1"></i> เปิดกล้องมือถือสแกน
                </a>
                <button class="btn btn-success shadow-sm rounded-pill px-3" onclick="window.location.href='../api/export/export_data.php?type=inventory'">
                    <i class="fa-solid fa-file-excel me-1"></i>ส่งออก Excel
                </button>
                <button class="btn btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fa-solid fa-plus me-1"></i> เพิ่มสินค้าใหม่
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="80">รูปภาพ</th>
                                <th>รหัสสินค้า / บาร์โค้ด</th>
                                <th>ชื่อสินค้า</th>
                                <th>หมวดหมู่</th>
                                <th>ราคาขาย</th>
                                <th>คงเหลือ (ชิ้น)</th>
                                <th>สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_products->num_rows > 0): ?>
                                <?php while($row = $result_products->fetch_assoc()): 
                                    $img_src = !empty($row['img']) ? '../assets/img/products/'.$row['img'] : 'https://placehold.co/100x100?text=No+Image';
                                ?>
                                <tr>
                                    <td><img src="<?php echo $img_src; ?>" class="rounded border" width="50" height="50" style="object-fit: cover;" alt="product"></td>
                                    <td class="text-muted small"><?php echo $row['barcode'] ?: 'ไม่มีบาร์โค้ด'; ?></td>
                                    <td class="fw-bold"><?php echo $row['prod_name']; ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo $row['type_name'] ?: 'ไม่ระบุ'; ?></span></td>
                                    <td class="text-primary fw-bold">฿<?php echo number_format($row['price'], 2); ?></td>
                                    <td>
                                        <?php if($row['stock_qty'] <= 5): ?>
                                            <span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $row['stock_qty']; ?></span>
                                        <?php else: ?>
                                            <span class="text-success fw-bold"><?php echo $row['stock_qty']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 1): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-eye"></i> เปิดขาย</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fa-solid fa-eye-slash"></i> ปิดซ่อน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-success rounded-circle me-1" title="รับเข้าสต็อก" 
                                                onclick='openAddStockModal(<?php echo htmlspecialchars(json_encode([
                                                    "prod_id" => $row["prod_id"],
                                                    "prod_name" => $row["prod_name"],
                                                    "barcode" => $row["barcode"]
                                                ]), ENT_QUOTES, "UTF-8"); ?>)'>
                                            <i class="fa-solid fa-boxes-packing"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info rounded-circle me-1" title="แก้ไข" 
                                                onclick='editProduct(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-circle" title="ลบ" 
                                                onclick="deleteProduct(<?php echo $row['prod_id']; ?>)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fa-solid fa-box-open fa-3x mb-3 opacity-25 d-block"></i>ยังไม่มีข้อมูลสินค้าในคลัง</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-box-open me-2"></i>เพิ่มสินค้าใหม่เข้าระบบ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="addProductForm" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">ชื่อสินค้า <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="prod_name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select class="form-select" name="type_id" required>
                                <option value="">เลือกหมวดหมู่</option>
                                <?php while($type = $result_types->fetch_assoc()): ?>
                                    <option value="<?php echo $type['type_id']; ?>"><?php echo $type['type_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">รหัสบาร์โค้ด</label>
                            <input type="text" class="form-control" name="barcode" placeholder="ถ้ามี">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ราคาขาย (บาท) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="price" required>
                        </div>
                        <div class="col-md-4">
                            <!-- Removed initial stock field per Phase 3 -->
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">รายละเอียดสินค้า</label>
                            <textarea class="form-control" name="detail" rows="3" placeholder="อธิบายคุณสมบัติสินค้า..."></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">รูปภาพสินค้า</label>
                            <input class="form-control" type="file" name="prod_img" accept="image/*">
                            <small class="text-muted">รองรับไฟล์ .jpg, .png ขนาดไม่เกิน 2MB</small>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="status" id="statusSwitch" value="1" checked>
                                <label class="form-check-label fw-bold" for="statusSwitch">เปิดขายทันที</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 p-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">บันทึกข้อมูล</button>
                </div>
            </form>
            
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>แก้ไขข้อมูลสินค้า</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="editProductForm">
                <input type="hidden" name="prod_id" id="edit_prod_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">ชื่อสินค้า <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="prod_name" id="edit_prod_name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select class="form-select" name="type_id" id="edit_type_id" required>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">รหัสบาร์โค้ด</label>
                            <input type="text" class="form-control" name="barcode" id="edit_barcode">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ราคาขาย (บาท) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="price" id="edit_price" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">รายละเอียดสินค้า</label>
                            <textarea class="form-control" name="detail" id="edit_detail" rows="3"></textarea>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="status" id="edit_status" value="1">
                                <label class="form-check-label fw-bold" for="edit_status">เปิดขาย</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 p-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-info text-white px-4 shadow-sm">บันทึกการแก้ไข</button>
                </div>
            </form>
            
        </div>
    </div>
</div>

<!-- Modal เพิ่มสินค้า -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-success text-white border-0 rounded-top-4 pb-3">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-boxes-packing me-2"></i>รับสินค้าเข้าจากการสแกน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="addStockForm">
                <input type="hidden" name="prod_id" id="stock_prod_id">
                <div class="modal-body p-4">
                    <div class="alert alert-secondary border-0 mb-4">
                        <small class="text-muted d-block">สินค้าที่สแกนพบ:</small>
                        <h5 class="fw-bold mb-0 text-dark" id="stock_prod_name">-</h5>
                        <small class="text-muted" id="stock_barcode">-</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">จำนวนที่รับเข้า <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control" name="qty" id="stock_qty" required autofocus>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">วันหมดอายุ (ถ้ามี)</label>
                            <input type="date" class="form-control" name="expiry_date" id="stock_expiry">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 p-4">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success px-4 shadow-sm">บันทึกสต็อก</button>
                </div>
            </form>
            
        </div>
    </div>
</div>

<script>
let pollingInterval;

function startPolling() {
    pollingInterval = setInterval(pollBarcode, 1500);
}

function stopPolling() {
    clearInterval(pollingInterval);
}

function pollBarcode() {
    $.post('../api/scanner/pull_barcode.php', function(res) {
        if(res.status === 'success') {
            stopPolling(); // หยุดพักแพพเพื่อไม่ให้ป็อปอัปเด้งซ้อน
            
            // เล่นเสียงแจ้งเตือนสั้นๆ (ถ้าเบราว์เซอร์อนุญาต)
            let audio = new Audio('https://www.soundjay.com/buttons/beep-07a.mp3');
            audio.play().catch(e => {});

            if(res.is_new) {
                // บาร์โค้ดใหม่ ให้กรอกเข้าเพิ่มสินค้าโหมด
                Swal.fire({
                    icon: 'info',
                    title: 'พบบาร์โค้ดใหม่!',
                    text: 'รหัส: ' + res.barcode + ' (ยังไม่มีในระบบ)',
                    confirmButtonText: 'สร้างสินค้าใหม่'
                }).then(() => {
                    $('#addProductModal').modal('show');
                    $('input[name="barcode"]').val(res.barcode);
                });
            } else {
                // บาร์โค้ดเก่า เด้งฟอร์มแอดสต็อก
                $('#stock_prod_id').val(res.product.prod_id);
                $('#stock_prod_name').text(res.product.prod_name);
                $('#stock_barcode').text('บาร์โค้ด: ' + res.barcode);
                $('#stock_qty').val('');
                $('#stock_expiry').val('');
                
                $('#addStockModal').modal('show');
                setTimeout(() => $('#stock_qty').focus(), 500);
            }
        }
    }, 'json');
}

// เมื่อปิด modal หรือ sweetalert ให้เปิดกลไกสแกนอีกรอบ
$('#addStockModal, #addProductModal').on('hidden.bs.modal', function () {
    startPolling();
});

$(document).ready(function() {
    startPolling(); // เริ่มต้นตอนโหลดหน้า
    
    // จัดการตอนกดยืนยันรับสต็อก
    $('#addStockForm').submit(function(e) {
        e.preventDefault();
        Swal.fire({ title: 'กำลังปรับยอด...', didOpen: () => { Swal.showLoading(); } });
        
        $.ajax({
            url: '../api/products/add_stock.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'รับเข้าสำเร็จ!', showConfirmButton: false, timer: 1000 })
                    .then(() => location.reload()); // โหลดหน้าใหม่เพื่อให้ UI ตารางสีเขียวแดงอัปเดต
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    });
    // จัดการตอนกดปุ่ม Submit ฟอร์มเพิ่มสินค้า
    $('#addProductForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);

        Swal.fire({
            title: 'กำลังบันทึกข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '../api/products/add_product.php', // รอสร้างไฟล์นี้
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'เพิ่มสินค้าสำเร็จ!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload(); // รีเฟรชหน้าเพื่อแสดงสินค้าใหม่
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    });

    // จัดการตอนกดปุ่ม Submit ฟอร์มแก้ไขสินค้า
    $('#editProductForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'กำลังอัปเดตข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '../api/products/update_product.php', 
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'อัปเดตสำเร็จ!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload(); 
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    });
});

// ฟังก์ชันเปิด Modal แก้ไขสินค้า
function editProduct(product) {
    $('#edit_prod_id').val(product.prod_id);
    $('#edit_prod_name').val(product.prod_name);
    $('#edit_barcode').val(product.barcode);
    $('#edit_price').val(product.price);
    $('#edit_detail').val(product.detail);
    
    if (product.status == 1) {
        $('#edit_status').prop('checked', true);
    } else {
        $('#edit_status').prop('checked', false);
    }

    // copy options จาก select แอด
    let typeOptions = $('select[name="type_id"]').first().html();
    $('#edit_type_id').html(typeOptions);
    $('#edit_type_id').val(product.type_id);

    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

// ฟังก์ชันลบสินค้า
function deleteProduct(id) {
    Swal.fire({
        title: 'ยืนยันลบสินค้า?',
        text: "การลบจะไม่สามารถกู้คืนได้ (ตะกร้าที่ค้างอยู่จะถูกลบด้วย)",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบทันที!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'กำลังลบ...', didOpen: () => { Swal.showLoading(); } });
            $.ajax({
                url: '../api/products/delete_product.php',
                type: 'POST',
                data: { prod_id: id },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'ลบเรียบร้อย!', showConfirmButton: false, timer: 1500 })
                        .then(() => location.reload());
                    } else {
                        Swal.fire('ข้อผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

// ฟังก์ชันเปิด Modal รับเข้าด่วน (Direct Inbound)
function openAddStockModal(prod) {
    stopPolling(); // หยุดพักแสกนเมื่อเปิดด้วยตัวเอง
    $('#stock_prod_id').val(prod.prod_id);
    $('#stock_prod_name').text(prod.prod_name);
    $('#stock_barcode').text(prod.barcode ? 'บาร์โค้ด: ' + prod.barcode : '-');
    $('#stock_qty').val('');
    $('#stock_expiry').val('');
    
    $('#addStockModal').modal('show');
    setTimeout(() => $('#stock_qty').focus(), 500);
}
</script>

<?php include_once '../includes/footer.php'; ?>