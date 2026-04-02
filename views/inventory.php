<?php 
include_once '../includes/header.php'; 

if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit; }

// Products with stock + sold qty
$sql_products = "SELECT p.prod_id, p.prod_name, p.barcode, p.price, p.img, p.status, p.type_id, p.detail,
                        t.type_name, 
                        COALESCE(s.total_qty, 0) as stock_qty,
                        COALESCE(sold.total_sold, 0) as total_sold
                 FROM products p
                 LEFT JOIN prod_types t ON p.type_id = t.type_id
                 LEFT JOIN stocks s ON p.prod_id = s.prod_id
                 LEFT JOIN (SELECT product_id, SUM(quantity) as total_sold FROM details_sales ds 
                            JOIN bill_sales bs ON ds.sale_id = bs.sale_id 
                            WHERE bs.sale_status NOT IN (0) GROUP BY product_id) sold ON p.prod_id = sold.product_id
                 ORDER BY p.prod_id DESC";
$result_products = $conn->query($sql_products);

$sql_types = "SELECT * FROM prod_types WHERE type_status = 1";
$result_types = $conn->query($sql_types);
$types_array = [];
while ($t = $result_types->fetch_assoc()) { $types_array[] = $t; }
?>

<main class="page-content fade-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>คลังสินค้า</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="scanner_mobile.php" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="fa-solid fa-qrcode me-1"></i>สแกน
            </a>
            <button class="btn btn-outline-success btn-sm" onclick="window.location.href='../api/export/export_data.php?type=inventory'">
                <i class="fa-solid fa-file-excel me-1"></i>Excel
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fa-solid fa-plus me-1"></i>เพิ่มสินค้า
            </button>
        </div>
    </div>

    <!-- Quick Search & Filter -->
    <div class="card p-3 mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="position-relative">
                    <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="ค้นหาสินค้า / บาร์โค้ด..." style="padding-left: 36px;">
                    <i class="fa-solid fa-magnifying-glass position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="filterStatus">
                    <option value="">ทุกสถานะ</option>
                    <option value="active">เปิดขาย</option>
                    <option value="inactive">ปิดซ่อน</option>
                    <option value="low">สต็อกต่ำ (≤5)</option>
                    <option value="out">หมด (0)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="filterSort">
                    <option value="">จัดเรียง</option>
                    <option value="sold_desc">ยอดขายมาก→น้อย</option>
                    <option value="sold_asc">ยอดขายน้อย→มาก</option>
                    <option value="stock_desc">สต็อกมาก→น้อย</option>
                    <option value="stock_asc">สต็อกน้อย→มาก</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()">
                    <i class="fa-solid fa-filter-circle-xmark me-1"></i>ล้าง
                </button>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="inventoryTable">
                <thead>
                    <tr>
                        <th width="60"></th>
                        <th>สินค้า</th>
                        <th>หมวดหมู่</th>
                        <th>ราคาขาย</th>
                        <th>คงเหลือ</th>
                        <th>ยอดขาย</th>
                        <th>สถานะ</th>
                        <th class="text-center" width="160">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_products->num_rows > 0): ?>
                        <?php while($row = $result_products->fetch_assoc()): 
                            $img_src = !empty($row['img']) ? '../uploads/products/'.$row['img'] : 'https://placehold.co/100x100?text=No+Image';
                        ?>
                        <tr class="product-row" 
                            data-name="<?php echo strtolower($row['prod_name'] . ' ' . $row['barcode']); ?>"
                            data-status="<?php echo $row['status']; ?>"
                            data-stock="<?php echo $row['stock_qty']; ?>"
                            data-sold="<?php echo $row['total_sold']; ?>">
                            <td><img src="<?php echo $img_src; ?>" class="rounded" width="44" height="44" style="object-fit: cover;" onerror="this.src='https://placehold.co/100x100?text=No+Image'"></td>
                            <td>
                                <div class="fw-bold" style="font-size: 0.88rem;"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                                <small class="text-muted"><?php echo $row['barcode'] ?: '-'; ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark"><?php echo $row['type_name'] ?: '-'; ?></span></td>
                            <td class="text-primary fw-bold">฿<?php echo number_format($row['price'], 2); ?></td>
                            <td>
                                <?php if($row['stock_qty'] <= 0): ?>
                                    <span class="badge" style="background: var(--danger-soft); color: var(--danger);">หมด</span>
                                <?php elseif($row['stock_qty'] <= 5): ?>
                                    <span class="text-danger fw-bold"><i class="fa-solid fa-exclamation-triangle me-1"></i><?php echo $row['stock_qty']; ?></span>
                                <?php else: ?>
                                    <span class="text-success fw-bold"><?php echo $row['stock_qty']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold <?php echo $row['total_sold'] > 0 ? 'text-info' : 'text-muted'; ?>">
                                    <?php echo number_format($row['total_sold']); ?> ชิ้น
                                </span>
                            </td>
                            <td>
                                <?php if($row['status'] == 1): ?>
                                    <span class="badge" style="background: var(--success-soft); color: var(--success);">เปิดขาย</span>
                                <?php else: ?>
                                    <span class="badge" style="background: var(--danger-soft); color: var(--danger);">ซ่อน</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success" title="รับเข้าสต็อก" 
                                            onclick='openAddStockModal(<?php echo htmlspecialchars(json_encode([
                                                "prod_id" => $row["prod_id"],
                                                "prod_name" => $row["prod_name"],
                                                "barcode" => $row["barcode"]
                                            ]), ENT_QUOTES, "UTF-8"); ?>)'>
                                        <i class="fa-solid fa-boxes-packing"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" title="แก้ไข" 
                                            onclick='editProduct(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" title="ลบ" 
                                            onclick="deleteProduct(<?php echo $row['prod_id']; ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fa-solid fa-box-open fa-3x mb-3 opacity-25 d-block"></i>ยังไม่มีสินค้า</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-box-open me-2"></i>เพิ่มสินค้าใหม่</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addProductForm" enctype="multipart/form-data">
                <div class="modal-body p-4 row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">ชื่อสินค้า <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="prod_name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">หมวดหมู่ <span class="text-danger">*</span></label>
                        <select class="form-select" name="type_id" required>
                            <option value="">เลือก</option>
                            <?php foreach($types_array as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>"><?php echo $type['type_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">บาร์โค้ด</label>
                        <input type="text" class="form-control" name="barcode" placeholder="ถ้ามี">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">ราคาขาย (฿) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="price" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">ราคาทุน (฿)</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="cost_price" placeholder="ไม่บังคับ">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">รายละเอียด</label>
                        <textarea class="form-control" name="detail" rows="2" placeholder="คุณสมบัติสินค้า..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">รูปภาพ</label>
                        <input class="form-control" type="file" name="prod_img" accept="image/*">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="statusSwitch" value="1" checked>
                            <label class="form-check-label fw-bold small" for="statusSwitch">เปิดขายทันที</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-pen me-2"></i>แก้ไขสินค้า</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm">
                <input type="hidden" name="prod_id" id="edit_prod_id">
                <div class="modal-body p-4 row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">ชื่อสินค้า <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="prod_name" id="edit_prod_name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">หมวดหมู่ <span class="text-danger">*</span></label>
                        <select class="form-select" name="type_id" id="edit_type_id" required></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">บาร์โค้ด</label>
                        <input type="text" class="form-control" name="barcode" id="edit_barcode">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">ราคาขาย (฿) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="price" id="edit_price" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">รายละเอียด</label>
                        <textarea class="form-control" name="detail" id="edit_detail" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="edit_status" value="1">
                            <label class="form-check-label fw-bold small" for="edit_status">เปิดขาย</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-info text-white">อัปเดต</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Stock Modal - now with cost_price field -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-boxes-packing me-2"></i>รับเข้าสต็อก</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addStockForm">
                <input type="hidden" name="prod_id" id="stock_prod_id">
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <small class="text-muted">สินค้า:</small>
                        <h6 class="fw-bold mb-0" id="stock_prod_name">-</h6>
                        <small class="text-muted" id="stock_barcode">-</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">จำนวน <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control" name="qty" id="stock_qty" required autofocus>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">ราคาทุน/ชิ้น (฿)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="cost_price" id="stock_cost_price" placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">หมดอายุ</label>
                            <input type="date" class="form-control" name="expiry_date" id="stock_expiry">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let pollingInterval;

function startPolling() { pollingInterval = setInterval(pollBarcode, 1500); }
function stopPolling() { clearInterval(pollingInterval); }

function pollBarcode() {
    $.post('../api/scanner/pull_barcode.php', function(res) {
        if(res.status === 'success') {
            stopPolling();
            let audio = new Audio('https://www.soundjay.com/buttons/beep-07a.mp3');
            audio.play().catch(e => {});

            if(res.is_new) {
                Swal.fire({ icon: 'info', title: 'พบบาร์โค้ดใหม่!', text: 'รหัส: ' + res.barcode, confirmButtonText: 'สร้างสินค้าใหม่', confirmButtonColor: '#6366f1' })
                .then(() => { 
                    new bootstrap.Modal(document.getElementById('addProductModal')).show();
                    $('input[name="barcode"]').val(res.barcode);
                });
            } else {
                openAddStockModal({ prod_id: res.product.prod_id, prod_name: res.product.prod_name, barcode: res.barcode });
            }
        }
    }, 'json');
}

$('#addStockModal, #addProductModal').on('hidden.bs.modal', function () { startPolling(); });

$(document).ready(function() {
    startPolling();
    
    // Search & Filter
    function applyFilters() {
        let search = $('#searchInput').val().toLowerCase();
        let status = $('#filterStatus').val();
        let sort = $('#filterSort').val();
        let rows = [];
        
        $('#inventoryTable tbody .product-row').each(function() {
            let $row = $(this);
            let name = $row.data('name');
            let st = parseInt($row.data('status'));
            let stock = parseInt($row.data('stock'));
            let sold = parseInt($row.data('sold'));
            
            let show = true;
            if (search && name.indexOf(search) === -1) show = false;
            if (status === 'active' && st !== 1) show = false;
            if (status === 'inactive' && st !== 0) show = false;
            if (status === 'low' && stock > 5) show = false;
            if (status === 'out' && stock > 0) show = false;
            
            $row.toggle(show);
            if (show) rows.push({ el: $row, stock: stock, sold: sold });
        });

        // Sort
        if (sort && rows.length > 0) {
            let tbody = $('#inventoryTable tbody');
            rows.sort((a, b) => {
                if (sort === 'sold_desc') return b.sold - a.sold;
                if (sort === 'sold_asc') return a.sold - b.sold;
                if (sort === 'stock_desc') return b.stock - a.stock;
                if (sort === 'stock_asc') return a.stock - b.stock;
                return 0;
            });
            rows.forEach(r => tbody.append(r.el));
        }
    }
    
    let searchTimer;
    $('#searchInput').on('keyup', function() { clearTimeout(searchTimer); searchTimer = setTimeout(applyFilters, 300); });
    $('#filterStatus, #filterSort').on('change', applyFilters);

    // Add Stock
    $('#addStockForm').submit(function(e) {
        e.preventDefault();
        Swal.fire({ title: 'กำลังปรับยอด...', didOpen: () => Swal.showLoading() });
        $.post('../api/products/add_stock.php', $(this).serialize(), function(res) {
            res.status === 'success' ? Swal.fire({ icon: 'success', title: 'รับเข้าสำเร็จ!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
        }, 'json');
    });

    // Add Product
    $('#addProductForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({ title: 'กำลังบันทึก...', didOpen: () => Swal.showLoading() });
        $.ajax({
            url: '../api/products/add_product.php', type: 'POST',
            data: new FormData(this), contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                res.status === 'success' ? Swal.fire({ icon: 'success', title: 'เพิ่มสำเร็จ!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
            }
        });
    });

    // Edit Product
    $('#editProductForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({ title: 'กำลังอัปเดต...', didOpen: () => Swal.showLoading() });
        $.post('../api/products/update_product.php', $(this).serialize(), function(res) {
            res.status === 'success' ? Swal.fire({ icon: 'success', title: 'อัปเดตแล้ว!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
        }, 'json');
    });
});

function editProduct(product) {
    $('#edit_prod_id').val(product.prod_id);
    $('#edit_prod_name').val(product.prod_name);
    $('#edit_barcode').val(product.barcode);
    $('#edit_price').val(product.price);
    $('#edit_detail').val(product.detail);
    $('#edit_status').prop('checked', product.status == 1);
    let typeOptions = $('select[name="type_id"]').first().html();
    $('#edit_type_id').html(typeOptions).val(product.type_id);
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

function deleteProduct(id) {
    Swal.fire({
        title: 'ยืนยันลบ?', icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('../api/products/delete_product.php', { prod_id: id }, function(res) {
                res.status === 'success' ? Swal.fire({ icon: 'success', title: 'ลบแล้ว!', timer: 1000, showConfirmButton: false }).then(() => location.reload()) : Swal.fire('ผิดพลาด', res.message, 'error');
            }, 'json');
        }
    });
}

function openAddStockModal(prod) {
    stopPolling();
    $('#stock_prod_id').val(prod.prod_id);
    $('#stock_prod_name').text(prod.prod_name);
    $('#stock_barcode').text(prod.barcode ? 'บาร์โค้ด: ' + prod.barcode : '-');
    $('#stock_qty').val('');
    $('#stock_cost_price').val('');
    $('#stock_expiry').val('');
    new bootstrap.Modal(document.getElementById('addStockModal')).show();
    setTimeout(() => $('#stock_qty').focus(), 500);
}

function clearFilters() {
    $('#searchInput').val('');
    $('#filterStatus').val('');
    $('#filterSort').val('');
    $('#inventoryTable tbody .product-row').show();
}
</script>

<?php include_once '../includes/footer.php'; ?>