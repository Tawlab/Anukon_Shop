<?php 
include_once '../includes/header.php'; 
include_once '../includes/sidebar.php'; 

if ($_SESSION['role'] !== 'admin') {
    // โค้ดเดิมสำหรับ Customer
    $uid = $_SESSION['user_id'];
    $q_orders = $conn->query("SELECT COUNT(*) AS c FROM bill_sales WHERE user_id = $uid");
    $my_orders = $q_orders->fetch_assoc()['c'] ?? 0;
?>
<style>
    .welcome-banner { background: linear-gradient(45deg, var(--primary-color), #6366f1); border-radius: 15px; color: white; padding: 30px; margin-bottom: 30px; }
    .quick-menu-card { border: none; border-radius: 20px; aspect-ratio: 1/1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: #333; transition: 0.3s; background: white; }
    .quick-menu-card:hover { background: var(--primary-color); color: white !important; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .quick-menu-card:hover i { color: white !important; }
    .quick-menu-card i { font-size: 3rem; margin-bottom: 15px; transition: 0.3s; }
</style>
<main id="content" class="flex-grow-1 bg-light">
    <div class="container-fluid py-4">
        <div class="welcome-banner d-flex justify-content-between align-items-center shadow-sm">
            <div>
                <h2 class="fw-bold mb-1">ยินดีต้อนรับ, คุณ <?php echo $_SESSION['full_name']; ?>!</h2>
                <p class="mb-0 opacity-75">เข้าสู่ระบบ V-SHOP วันนี้มีอะไรให้ช่วยไหม?</p>
            </div>
            <div class="d-none d-md-block"><i class="fa-solid fa-store fa-3x"></i></div>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-sm-4 col-md-3">
                <a href="shop.php" class="card quick-menu-card shadow-sm">
                    <i class="fa-solid fa-bag-shopping text-primary"></i><span class="fw-bold">สั่งซื้อสินค้า</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-3">
                <a href="my_orders.php" class="card quick-menu-card shadow-sm">
                    <i class="fa-solid fa-truck-fast text-success"></i><span class="fw-bold">ออเดอร์ (<?php echo $my_orders; ?>)</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-3">
                <a href="profile.php" class="card quick-menu-card shadow-sm">
                    <i class="fa-solid fa-user-gear text-secondary"></i><span class="fw-bold">โปรไฟล์</span>
                </a>
            </div>
        </div>
    </div>
</main>
<?php 
    include_once '../includes/footer.php'; 
    exit;
}

// ================= สำหรับ ADMIN =================
$q_pending = $conn->query("SELECT COUNT(*) AS c FROM bill_sales WHERE sale_status = 1");
$pending = $q_pending->fetch_assoc()['c'] ?? 0;
?>

<style>
    .admin-banner { background: #fff; border-radius: 12px; padding: 20px 30px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .stat-card { border: none; border-radius: 12px; transition: transform 0.2s ease; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .stat-card:hover { transform: translateY(-4px); }
    .icon-box { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    
    .fin-box { padding: 20px; border-radius: 12px; color: white; margin-bottom: 15px; }
    .fin-profit { background: linear-gradient(135deg, #10b981, #059669); }
    .fin-sales { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .fin-cogs { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .fin-exp { background: linear-gradient(135deg, #ef4444, #dc2626); }
    
    .table-sm th { font-size: 0.85rem; color: #6b7280; font-weight: 600; text-transform: uppercase; }
    .table-sm td { font-size: 0.9rem; vertical-align: middle; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main id="content" class="flex-grow-1 bg-light">
    <div class="container-fluid py-4">

        <div class="admin-banner d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1 text-dark">ภาพรวมร้านค้า (Dashboard)</h4>
                <p class="text-muted mb-0 small">สรุปยอดขาย การเงิน และคลังสินค้าประจำเดือน</p>
            </div>
            <div class="text-end">
                <a href="pos.php" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fa-solid fa-cash-register me-2"></i>เครื่อง POS</a>
            </div>
        </div>

        <!-- 1. แผงการเงิน (Finance) -->
        <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-vault me-2"></i>สรุปการเงินเดือนนี้ <span id="currentMonthStr"></span></h6>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="fin-box fin-profit h-100 shadow-sm position-relative overflow-hidden">
                    <i class="fa-solid fa-sack-dollar position-absolute opacity-25" style="font-size: 5rem; right: -10px; bottom: -15px;"></i>
                    <div class="small fw-bold opacity-75 mb-1">กำไรสุทธิ (Net Profit)</div>
                    <h3 class="fw-bold mb-0" id="dash_profit">฿0.00</h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fin-box fin-sales h-100 shadow-sm position-relative overflow-hidden">
                    <i class="fa-solid fa-hand-holding-dollar position-absolute opacity-25" style="font-size: 5rem; right: -10px; bottom: -15px;"></i>
                    <div class="small fw-bold opacity-75 mb-1">ยอดขายสุทธิ</div>
                    <h4 class="fw-bold mb-0" id="dash_sales">฿0.00</h4>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fin-box fin-cogs h-100 shadow-sm position-relative overflow-hidden">
                    <i class="fa-solid fa-boxes-stacked position-absolute opacity-25" style="font-size: 5rem; right: -10px; bottom: -15px;"></i>
                    <div class="small fw-bold opacity-75 mb-1">ต้นทุนขาย (COGS)</div>
                    <h4 class="fw-bold mb-0" id="dash_cogs">฿0.00</h4>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fin-box fin-exp h-100 shadow-sm position-relative overflow-hidden">
                    <i class="fa-solid fa-receipt position-absolute opacity-25" style="font-size: 5rem; right: -10px; bottom: -15px;"></i>
                    <div class="small fw-bold opacity-75 mb-1 d-flex justify-content-between">
                        <span>ค่าใช้จ่ายอื่นๆ</span>
                        <a href="#" class="text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#expModal"><i class="fa-solid fa-plus-circle"></i> บันทึกเพิ่ม</a>
                    </div>
                    <h4 class="fw-bold mb-0" id="dash_exp">฿0.00</h4>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- ซ้าย: กราฟยอดขาย -->
            <div class="col-lg-8">
                <div class="card stat-card h-100 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-line text-primary me-2"></i>เทรนด์ยอดขาย (30 วันล่าสุด)</h6>
                    </div>
                    <div style="height: 300px;">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- ขวา: ทุนร้าน และ ออเดอร์ -->
            <div class="col-lg-4 d-flex flex-column gap-3">
                <div class="card stat-card p-4 shadow-sm border-0 border-start border-danger border-5">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-danger bg-opacity-10 text-danger me-3"><i class="fa-solid fa-boxes-packing"></i></div>
                        <div>
                            <div class="small text-muted fw-bold">ออเดอร์ต้องจัดส่งด่วน</div>
                            <h3 class="fw-bold text-danger mb-0"><?php echo number_format($pending); ?> <span class="fs-6 text-muted fw-normal">รายการ</span></h3>
                        </div>
                    </div>
                </div>

                <div class="card stat-card p-4 shadow-sm h-100 border-0">
                    <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-building-columns me-2"></i>จัดการทุนเปิดร้าน</h6>
                    <p class="small text-muted mb-3">กำหนดทุนเริ่มต้นของร้าน เพื่อใช้คำนวณกำไรสะสมตลอดกาล</p>
                    <form id="capitalForm" class="mt-auto">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">฿</span>
                            <input type="number" step="0.01" class="form-control fw-bold text-primary" id="capital_input" name="amount" placeholder="0.00">
                            <button class="btn btn-outline-primary" type="submit">บันทึก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. อันดับสินค้า -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card stat-card p-4 h-100 border-top border-success border-3">
                    <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-trophy text-success me-2"></i> Top 10 สินค้าขายดีที่สุด (ตลอดกาล)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="topProductsTable">
                            <tbody><!-- Data via AJAX --></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card stat-card p-4 h-100 border-top border-danger border-3">
                    <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Top 10 สินค้าค้างสต็อก (Dead Stock)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="deadProductsTable">
                            <tbody><!-- Data via AJAX --></tbody>
                        </table>
                    </div>
                    <small class="text-danger mt-2 d-block"><i class="fa-solid fa-circle-info"></i> คำนวณจากสินค้าที่มีสต็อกเยอะแต่ขายได้น้อยที่สุด</small>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Modal บันทึกค่าใช้จ่าย -->
<div class="modal fade" id="expModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice me-2"></i>บันทึกค่าใช้จ่ายอื่นๆ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="expenseForm">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">วันที่จ่าย</label>
                        <input type="date" class="form-control" name="exp_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">รายการ (เช่น ค่าไฟ, ค่าน้ำ)</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">จำนวนเงิน (฿)</label>
                        <input type="number" step="0.01" class="form-control text-danger fw-bold fs-5" name="amount" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger px-4">บันทึกค่าใช้จ่าย</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fm(val) {
    return '฿' + parseFloat(val).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits:2});
}

$(document).ready(function() {
    // โหลดข้อมูลการเงิน
    $.get('../api/reports/dashboard_data.php?action=finance', function(res) {
        if(res.status == 'success') {
            $('#dash_profit').text(fm(res.profit));
            $('#dash_sales').text(fm(res.sales));
            $('#dash_cogs').text(fm(res.cogs));
            $('#dash_exp').text(fm(res.expenses));
            $('#capital_input').val(res.capital);
        }
    }, 'json');

    // โหลดกราฟ
    $.get('../api/reports/dashboard_data.php?action=sales_chart', function(res) {
        if(res.status == 'success') {
            const ctx = document.getElementById('salesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: res.labels,
                    datasets: [{
                        label: 'ยอดขายรายวัน (฿)',
                        data: res.sales,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#2563eb',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }, 'json');

    // โหลด Top / Dead
    $.get('../api/reports/dashboard_data.php?action=top_products', function(res) {
        if(res.status == 'success') {
            let topHtml = '';
            res.top.forEach((v, i) => {
                let badge = i<3 ? 'bg-warning text-dark' : 'bg-light text-dark border';
                topHtml += `<tr>
                    <td width="10%"><span class="badge ${badge}">#${i+1}</span></td>
                    <td class="fw-bold">${v.prod_name}</td>
                    <td class="text-end text-success fw-bold">${v.qty} ชิ้น</td>
                </tr>`;
            });
            $('#topProductsTable tbody').html(topHtml || '<tr><td colspan="3" class="text-center text-muted">ยังไม่มียอดขาย</td></tr>');

            let deadHtml = '';
            res.dead.forEach((v, i) => {
                deadHtml += `<tr>
                    <td width="10%"><span class="badge bg-light text-dark border">#${i+1}</span></td>
                    <td class="fw-bold">${v.prod_name}</td>
                    <td class="text-end"><span class="text-muted ms-2">(ขาย: ${v.sold_qty})</span> <span class="text-danger fw-bold ms-2">เหลือ ${v.stock_qty}</span> </td>
                </tr>`;
            });
            $('#deadProductsTable tbody').html(deadHtml || '<tr><td colspan="3" class="text-center text-muted">สต็อกหมุนเวียนดีเยียม ไม่มี Dead Stock</td></tr>');
        }
    }, 'json');

    // ทุนฟอร์ม
    $('#capitalForm').submit(function(e) {
        e.preventDefault();
        $.post('../api/reports/save_capital.php', $(this).serialize(), function(res) {
            if(res.status == 'success') {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
            } else {
                Swal.fire('ข้อผิดพลาด', res.message, 'error');
            }
        });
    });

    // ค่าใช้จ่ายฟอร์ม
    $('#expenseForm').submit(function(e) {
        e.preventDefault();
        Swal.fire({ title: 'กำลังบันทึก...', didOpen: () => Swal.showLoading() });
        $.post('../api/reports/save_expense.php', $(this).serialize(), function(res) {
            if(res.status == 'success') {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false})
                .then(() => location.reload());
            } else {
                Swal.fire('ข้อผิดพลาด', res.message, 'error');
            }
        });
    });
});
</script>
<?php include_once '../includes/footer.php'; ?>