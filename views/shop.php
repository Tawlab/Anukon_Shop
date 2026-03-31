<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

// ดึงหมวดหมู่เพื่อแสดง filter
$sql_types = "SELECT type_id, type_name FROM prod_types WHERE type_status = 1 ORDER BY type_name";
$result_types = $conn->query($sql_types);
?>

<style>
    .product-card {
        border-radius: 15px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .filter-btn.active { background-color: #0056b3 !important; color: white !important; }
</style>

<main id="content" class="flex-grow-1">
    <div class="container-fluid py-4">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-3">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-store me-2 text-primary"></i>เลือกช้อปสินค้า</h2>
            <div class="d-flex gap-2" style="max-width: 500px; width: 100%;">
                <select id="filterCategory" class="form-select" style="max-width: 180px;">
                    <option value="">ทุกหมวดหมู่</option>
                    <?php while($type = $result_types->fetch_assoc()): ?>
                        <option value="<?php echo $type['type_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <div class="input-group flex-grow-1">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="searchProduct" class="form-control border-start-0" placeholder="ค้นหาชื่อสินค้า...">
                </div>
            </div>
        </div>

        <div class="row g-4" id="product-display">
            <div class="col-12 text-center py-5 text-muted">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <p>กำลังโหลดรายการสินค้า...</p>
            </div>
        </div>

    </div>
</main>

<script>
$(document).ready(function() {
    // 1. โหลดสินค้าทั้งหมดทันทีที่เปิดหน้าเว็บ
    loadProducts();

    // 2. ฟังก์ชันโหลดสินค้าทั้งหมด
    function loadProducts(query = '', type_id = '') {
        $.ajax({
            url: '../api/products/get_products.php',
            type: 'GET',
            data: { search: query, type_id: type_id },
            success: function(response) {
                // นำ HTML ที่ได้จาก API มาแสดงใน div #product-display
                $('#product-display').html(response);
            },
            error: function() {
                $('#product-display').html('<div class="col-12 text-center text-danger py-5">เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า</div>');
            }
        });
    }

    // 3. ระบบค้นหา Real-time (หน่วงเวลา 300ms เพื่อไม่ให้โหลดเซิร์ฟเวอร์หนักเกินไป)
    let timeout = null;
    $('#searchProduct').on('keyup', function() {
        clearTimeout(timeout);
        let query = $(this).val();
        let type_id = $('#filterCategory').val();
        timeout = setTimeout(function() {
            loadProducts(query, type_id);
        }, 300);
    });

    // 4. เมื่อเปลี่ยนหมวดหมู่ โหลดสินค้าใหม่ทันที
    $('#filterCategory').on('change', function() {
        let type_id = $(this).val();
        let query = $('#searchProduct').val();
        loadProducts(query, type_id);
    });
});

// 4. ฟังก์ชันจำลองการเพิ่มสินค้าลงตะกร้า (เตรียมไว้สำหรับไฟล์ต่อไป)
function addToCart(p_id) {
    $.ajax({
        url: '../api/cart/add_to_cart.php',
        type: 'POST',
        data: { prod_id: p_id },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                // ถ้าอยากให้ไฮโซขึ้น สามารถเขียนโค้ดอัปเดตตัวเลขแจ้งเตือนบนไอคอนตะกร้าบน Navbar ได้ด้วย
            } else {
                Swal.fire('ข้อผิดพลาด', res.message, 'warning');
            }
        },
        error: function() {
            Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อระบบได้', 'error');
        }
    });
}
</script>

<?php include_once '../includes/footer.php'; ?>