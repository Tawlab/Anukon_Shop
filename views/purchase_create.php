<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// โหลดซัพพลายเออร์ที่เปิดใช้งาน
$sql_sup = "SELECT sp_id, sp_name FROM supplers WHERE sp_status = 1";
$res_sup = $conn->query($sql_sup);

// โหลดข้อมูลสินค้า
$sql_prod = "SELECT prod_id, prod_name, price FROM products WHERE status = 1";
$res_prod = $conn->query($sql_prod);
$products = [];
while($p = $res_prod->fetch_assoc()) {
    $products[] = $p;
}
?>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>สร้างใบสั่งซื้อ (PO)</h2>
            <a href="purchases.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับ
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <form id="purchaseForm">
                <div class="card-body p-4">
                    
                    <div class="row g-3 mb-4 p-3 bg-light rounded">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">เลือกซัพพลายเออร์ <span class="text-danger">*</span></label>
                            <select class="form-select" name="sp_id" required>
                                <option value="">-- เลือกผู้จัดจำหน่าย --</option>
                                <?php while($s = $res_sup->fetch_assoc()): ?>
                                    <option value="<?php echo $s['sp_id']; ?>"><?php echo htmlspecialchars($s['sp_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">ลงวันที่ <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-12 mt-3">
                            <label class="form-label fw-bold">หมายเหตุ (ถ้ามี)</label>
                            <input type="text" class="form-control" name="remark" placeholder="คำอธิบายเพิ่มเติม...">
                        </div>
                    </div>

                    <h5 class="fw-bold text-primary mb-3">รายการสินค้าที่ต้องการสั่งซื้อ</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="poTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="40%">สินค้า</th>
                                    <th width="15%">ราคาต่อหน่วย (฿)</th>
                                    <th width="15%">จำนวน (ชิ้น)</th>
                                    <th width="20%">ยอดรวม (฿)</th>
                                    <th width="10%" class="text-center">ลบ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Template Row จะถูกแทรกที่นี่ผ่าน JS -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-left bg-light">
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="addRowBtn">
                                            <i class="fa-solid fa-plus me-1"></i> เพิ่มรายการออเดอร์
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end align-items-center mt-3 p-3 bg-primary bg-opacity-10 rounded">
                        <h4 class="mb-0 fw-bold me-4">ยอดรวมสุทธิ (Total Cost):</h4>
                        <h2 class="mb-0 fw-bold text-primary" id="grandTotal">฿ 0.00</h2>
                        <input type="hidden" name="total_cost" id="hiddenTotalCost" value="0">
                    </div>

                </div>
                <div class="card-footer border-0 p-4 pt-0 bg-transparent text-end">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm rounded-pill">
                        <i class="fa-solid fa-paper-plane me-1"></i> บันทึกเข้าระบบ PO
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>

<script>
const products = <?php echo json_encode($products); ?>;
let rowCount = 0;

function addRow() {
    rowCount++;
    let rowHtml = `
    <tr id="row_${rowCount}">
        <td>
            <select class="form-select prod-select" name="items[${rowCount}][prod_id]" required>
                <option value="">-- เลือกสินค้า --</option>
                ${products.map(p => `<option value="${p.prod_id}" data-price="${p.price}">${p.prod_name}</option>`).join('')}
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0" class="form-control prod-price" name="items[${rowCount}][unit_cost]" required>
        </td>
        <td>
            <input type="number" min="1" value="1" class="form-control prod-qty" name="items[${rowCount}][order_qty]" required>
        </td>
        <td>
            <input type="text" class="form-control prod-subtotal" readonly value="0.00">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeRow(${rowCount})"><i class="fa-solid fa-trash"></i></button>
        </td>
    </tr>
    `;
    $('#poTable tbody').append(rowHtml);
}

function removeRow(id) {
    $(`#row_${id}`).remove();
    calculateGrandTotal();
}

function calculateRow(row) {
    let price = parseFloat(row.find('.prod-price').val()) || 0;
    let qty = parseInt(row.find('.prod-qty').val()) || 0;
    let sub = price * qty;
    row.find('.prod-subtotal').val(sub.toFixed(2));
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let grand = 0;
    $('.prod-subtotal').each(function() {
        grand += parseFloat($(this).val()) || 0;
    });
    $('#grandTotal').text('฿ ' + grand.toFixed(2));
    $('#hiddenTotalCost').val(grand);
}

$(document).ready(function() {
    // เพิ่มบรรทัดแรกอัตโนมัติ
    addRow();

    $('#addRowBtn').click(addRow);

    // ดึงราคาซัพพลายตามที่เลือกสินค้า
    $(document).on('change', '.prod-select', function() {
        let price = $(this).find(':selected').data('price') || 0;
        let row = $(this).closest('tr');
        row.find('.prod-price').val(price); // ตั้งราคาตั้งต้นให้เท่ากับราคาขาย แต่แก้ได้
        calculateRow(row);
    });

    $(document).on('input', '.prod-price, .prod-qty', function() {
        calculateRow($(this).closest('tr'));
    });

    // Submit form ผ่าน API
    $('#purchaseForm').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#poTable tbody tr').length === 0) {
            Swal.fire('ข้อผิดพลาด', 'กรุณาเพิ่มรายการสินค้าอย่างน้อย 1 รายการ', 'warning');
            return;
        }

        Swal.fire({ title: 'กำลังบันทึก PO...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        
        $.ajax({
            url: '../api/purchases/po_api.php',
            type: 'POST',
            data: $(this).serialize() + '&action=create_po',
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สร้างใบสั่งซื้อสำเร็จ!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'purchases.php';
                    });
                } else {
                    Swal.fire('ล้มเหลว', res.message, 'error');
                }
            }
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
