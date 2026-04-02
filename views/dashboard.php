<?php
include_once '../includes/header.php';

// Check roles: Allow admin and staff
if (!in_array($_SESSION['role'], ['admin', 'staff'])) {
    // If Customer, show their orders snapshot
    $uid = $_SESSION['user_id'];
    $q_orders = $conn->prepare("SELECT COUNT(*) AS c FROM bill_sales WHERE user_id = ?");
    $q_orders->bind_param("i", $uid);
    $q_orders->execute();
    $my_orders = $q_orders->get_result()->fetch_assoc()['c'] ?? 0;
    ?>

    <main class="page-content fade-up">
        <div class="mb-4 p-4 rounded-4" style="background: linear-gradient(135deg, #6366f1, #818cf8); color: white;">
            <h4 class="fw-bold mb-1">สวัสดี, <?php echo $_SESSION['full_name']; ?> 👋</h4>
            <p class="mb-0 opacity-75 small">ยินดีต้อนรับเข้าสู่ Anukon Shop</p>
        </div>

        <div class="row g-3">
            <div class="col-6 col-md-4">
                <a href="shop.php" class="card text-center p-4 text-decoration-none h-100">
                    <i class="fa-solid fa-bag-shopping text-primary mb-2" style="font-size: 2rem;"></i>
                    <span class="fw-bold small text-dark">สั่งซื้อสินค้า</span>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="my_orders.php" class="card text-center p-4 text-decoration-none h-100">
                    <i class="fa-solid fa-truck-fast text-success mb-2" style="font-size: 2rem;"></i>
                    <span class="fw-bold small text-dark">ออเดอร์ (<?php echo $my_orders; ?>)</span>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="profile.php" class="card text-center p-4 text-decoration-none h-100">
                    <i class="fa-solid fa-circle-user text-secondary mb-2" style="font-size: 2rem;"></i>
                    <span class="fw-bold small text-dark">โปรไฟล์</span>
                </a>
            </div>
        </div>
    </main>

    <?php
    include_once '../includes/footer.php';
    exit;
}

// ================= ADMIN & STAFF DASHBOARD =================
$is_admin = ($_SESSION['role'] === 'admin');

// Count Pending Orders (Wait for shipping)
$q_pending = $conn->query("SELECT COUNT(*) AS c FROM bill_sales WHERE sale_status = 1");
$pending = $q_pending->fetch_assoc()['c'] ?? 0;
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .loading-overlay {
        position: relative;
    }

    .loading-overlay::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.7);
        z-index: 10;
        border-radius: inherit;
    }

    .spinner-centered {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 11;
    }
</style>

<main class="page-content fade-up">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h5 class="fw-bold mb-1">ภาพรวมร้านค้า</h5>
            <p class="text-muted small mb-0">สรุปยอดขายและการเงิน</p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <select id="dateRangeFilter" class="form-select border-0 shadow-sm"
                style="width: auto; background: var(--surface);">
                <option value="today">วันนี้</option>
                <option value="7">7 วันย้อนหลัง</option>
                <option value="30" selected>30 วันย้อนหลัง</option>
                <option value="all">ทั้งหมด</option>
            </select>
            <a href="pos.php" class="btn btn-primary px-3 shadow-sm"><i
                    class="fa-solid fa-cash-register me-2"></i>POS</a>
        </div>
    </div>

    <!-- Finance Cards -->
    <div class="row g-3 mb-4" id="financeCards">
        <div class="col-6 col-md-3">
            <div class="card p-3 h-100 border-0 shadow-sm"
                style="background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff;">
                <div class="small fw-bold opacity-75 mb-1"><i class="fa-solid fa-sack-dollar me-1"></i>กำไรสุทธิ</div>
                <div class="spinner-border spinner-border-sm text-light spinner-finance d-none"></div>
                <h4 class="fw-bold mb-0 finance-val" id="dash_profit" style="font-size: 1.35rem;">-</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 h-100 border-0 shadow-sm"
                style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff;">
                <div class="small fw-bold opacity-75 mb-1"><i class="fa-solid fa-chart-line me-1"></i>ยอดขาย</div>
                <div class="spinner-border spinner-border-sm text-light spinner-finance d-none"></div>
                <h4 class="fw-bold mb-0 finance-val" id="dash_sales" style="font-size: 1.35rem;">-</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 h-100 border-0 shadow-sm"
                style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;">
                <div class="small fw-bold opacity-75 mb-1"><i class="fa-solid fa-boxes-stacked me-1"></i>ต้นทุนรับเข้า
                    (PO)</div>
                <div class="spinner-border spinner-border-sm text-light spinner-finance d-none"></div>
                <h4 class="fw-bold mb-0 finance-val" id="dash_cogs" style="font-size: 1.35rem;">-</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 h-100 border-0 shadow-sm"
                style="background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff;">
                <div class="small fw-bold opacity-75 mb-1">
                    <i class="fa-solid fa-receipt me-1"></i>ค่าใช้จ่าย
                    <?php if ($is_admin): ?>
                        <a href="#" class="text-white float-end" data-bs-toggle="modal" data-bs-target="#expModal"><i
                                class="fa-solid fa-plus-circle"></i></a>
                    <?php endif; ?>
                </div>
                <div class="spinner-border spinner-border-sm text-light spinner-finance d-none"></div>
                <h4 class="fw-bold mb-0 finance-val" id="dash_exp" style="font-size: 1.35rem;">-</h4>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Sales Chart -->
        <div class="col-lg-8">
            <div class="card p-4 h-100 shadow-sm position-relative" id="chartCard">
                <div class="spinner-border text-primary spinner-centered chart-spinner d-none"></div>
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-line text-primary me-2"></i>แนวโน้มยอดขาย</h6>
                <div style="height: 280px;"><canvas id="salesChart"></canvas></div>
            </div>
        </div>

        <!-- Capital + Urgent Orders -->
        <div class="col-lg-4 d-flex flex-column gap-3">
            <!-- Pending Orders -->
            <a href="orders_manage.php"
                class="card p-3 border-start border-danger border-4 border-0 shadow-sm text-decoration-none">
                <div class="d-flex align-items-center gap-3">
                    <div
                        style="width: 44px; height: 44px; border-radius: 12px; background: var(--danger-soft); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-boxes-packing text-danger"></i>
                    </div>
                    <div>
                        <div class="small text-muted fw-bold">ออเดอร์รอจัดส่ง</div>
                        <h4 class="fw-bold text-danger mb-0"><?php echo number_format($pending); ?></h4>
                    </div>
                </div>
            </a>

            <!-- Capital -->
            <div class="card p-4 h-100 shadow-sm border-0 position-relative" id="capitalCard">
                <div class="spinner-border spinner-border-sm text-primary spinner-centered cap-spinner d-none"></div>
                <h6 class="fw-bold text-dark mb-3"><i
                        class="fa-solid fa-vault text-warning me-2"></i>สถานะเงินทุนร้านค้า</h6>

                <div class="mb-4">
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <span class="text-muted small">1. ทุนตั้งต้น (Initial Capital)</span>
                        <span class="fw-bold text-dark" id="initial_capital_display">-</span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <span class="text-muted small">2. ทุนอัดฉีดหมุนเวียน</span>
                        <span class="fw-bold text-primary" id="injected_capital_display">-</span>
                    </div>

                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <span class="text-muted small">3. กำไร / ขาดทุน สุทธิ</span>
                        <span class="fw-bold" id="profit_flow_display">-</span>
                    </div>

                    <div class="d-flex justify-content-between bg-light p-3 rounded-3 mt-3 border">
                        <span class="fw-bold text-success small align-self-center">4. ทุนหมุนเวียนคงเหลือรวม</span>
                        <span class="fw-bold text-success fs-5" id="working_capital_display">-</span>
                    </div>
                </div>

                <?php if ($is_admin): ?>
                    <ul class="nav nav-pills nav-fill mb-2 text-sm flex-nowrap gap-1" style="font-size: 0.8rem;">
                        <li class="nav-item">
                            <button class="nav-link active py-1" data-bs-toggle="tab" data-bs-target="#tabDashInj">อัดฉีดหมุนเวียน</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1" data-bs-toggle="tab" data-bs-target="#tabDashInit">ตั้งทุนเริ่มต้น</button>
                        </li>
                    </ul>

                    <div class="tab-content mt-auto border-top pt-2">
                        <!-- Tab อัดฉีดหมุนเวียน -->
                        <div class="tab-pane fade show active" id="tabDashInj">
                            <form id="capitalFormWorking" class="mt-auto">
                                <input type="hidden" name="type" value="working">
                                <div class="input-group input-group-sm mb-2 shadow-sm">
                                    <span class="input-group-text bg-white border-end-0 text-primary"><i class="fa-solid fa-coins"></i></span>
                                    <input type="number" step="0.01" class="form-control border-start-0 fw-bold" name="amount" placeholder="ยอดเงิน (฿)" required>
                                </div>
                                <div class="input-group input-group-sm shadow-sm">
                                    <span class="input-group-text bg-white border-end-0 text-secondary"><i class="fa-solid fa-pen"></i></span>
                                    <input type="text" class="form-control border-start-0" name="remark" placeholder="หมายเหตุ" required>
                                    <button class="btn btn-primary fw-bold px-3" type="submit">เพิ่ม</button>
                                </div>
                            </form>
                        </div>
                        <!-- Tab ทุนเริ่มต้นตายตัว -->
                        <div class="tab-pane fade" id="tabDashInit">
                            <form id="capitalFormInit" class="mt-auto">
                                <input type="hidden" name="type" value="initial">
                                <div class="input-group input-group-sm mb-2 shadow-sm">
                                    <span class="input-group-text bg-white border-end-0 text-warning"><i class="fa-solid fa-store"></i></span>
                                    <input type="number" step="0.01" class="form-control border-start-0 fw-bold" id="capital_input" name="amount" placeholder="ยอดทุนเปิดร้านเดิม (฿)" required>
                                </div>
                                <button class="btn btn-warning w-100 fw-bold btn-sm shadow-sm text-dark" type="submit">อัปเดตแทนที่ทุนเริ่มต้น</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-auto p-2 bg-light rounded text-center small text-muted">
                        <i class="fa-solid fa-lock me-1"></i> เฉพาะ Admin ที่แก้ไขทุนตั้งต้นได้
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top & Dead Products -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card p-4 h-100 shadow-sm position-relative" id="topProductsCard">
                <div class="spinner-border text-warning spinner-centered top-spinner d-none"></div>
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-trophy text-warning me-2"></i>สินค้าขายดี Top 10</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle" id="topProductsTable">
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-4 h-100 shadow-sm position-relative" id="deadProductsCard">
                <div class="spinner-border text-danger spinner-centered top-spinner d-none"></div>
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>สินค้าควรระวัง
                    (Dead Stock)</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle" id="deadProductsTable">
                        <tbody></tbody>
                    </table>
                </div>
                <small class="text-muted mt-2"><i class="fa-solid fa-circle-info"></i> สินค้าที่สต็อก > 10 ชิ้น
                    แต่ขายได้น้อยกว่า 3 ชิ้น</small>
            </div>
        </div>
    </div>
</main>

<?php if ($is_admin): ?>
    <!-- Expense Modal -->
    <div class="modal fade" id="expModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white border-0">
                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice me-2"></i>บันทึกค่าใช้จ่ายใหม่</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="expenseForm">
                    <div class="modal-body p-4 bg-light">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">วันที่บันทึก</label>
                            <input type="date" class="form-control shadow-sm border-0" name="exp_date"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">รายการค่าใช้จ่าย</label>
                            <input type="text" class="form-control shadow-sm border-0" name="title"
                                placeholder="เช่น ค่าไฟ, ค่าน้ำ, ค่าจัดส่ง" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">จำนวนเงิน (฿)</label>
                            <input type="number" step="0.01"
                                class="form-control shadow-sm border-0 text-danger fw-bold fs-5" name="amount" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-white">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-danger px-4 fw-bold">บันทึกรายการ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    let salesChart = null;

    function fm(val) {
        return '฿' + parseFloat(val).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function loadDashboardData() {
        let range = $('#dateRangeFilter').val();

        // Show loaders
        $('.finance-val').addClass('d-none');
        $('.spinner-finance').removeClass('d-none');
        $('#chartCard .chart-spinner').removeClass('d-none');
        $('#chartCard canvas').css('opacity', '0.4');
        $('#topProductsCard .top-spinner, #deadProductsCard .top-spinner').removeClass('d-none');
        $('#topProductsTable tbody, #deadProductsTable tbody').css('opacity', '0.4');
        $('.cap-spinner').removeClass('d-none');

        // 1. Finance Data
    $.get(`../api/reports/dashboard_data.php?action=finance&range=${range}`, function(res) {
        $('.spinner-finance').addClass('d-none');
        $('.finance-val').removeClass('d-none');
        $('.cap-spinner').addClass('d-none');

        if(res.status === 'success') {
            // อัปเดตตัวเลขการ์ดหลักด้านบน
            $('#dash_profit').text(fm(res.profit));
            $('#dash_sales').text(fm(res.sales));
            $('#dash_cogs').text(fm(res.cogs));
            $('#dash_exp').text(fm(res.expenses));
            
            // อัปเดตส่วนสถานะเงินทุนร้านค้า (Capital Card)
            if($('#capital_input').length) $('#capital_input').val(res.initial_capital);
            $('#initial_capital_display').text(fm(res.initial_capital));
            $('#injected_capital_display').text(fm(res.injected_capital));
            
            // จัดการสีกำไร/ขาดทุน
            let profitFlowEl = $('#profit_flow_display');
            profitFlowEl.text((res.profit > 0 ? '+' : '') + fm(res.profit));
            if(res.profit < 0) {
                profitFlowEl.removeClass('text-success text-dark').addClass('text-danger');
            } else if(res.profit > 0) {
                profitFlowEl.removeClass('text-danger text-dark').addClass('text-success');
            } else {
                profitFlowEl.removeClass('text-success text-danger').addClass('text-dark');
            }

            // จัดการสีทุนหมุนเวียนคงเหลือ
            let workingCapEl = $('#working_capital_display');
            workingCapEl.text(fm(res.working_capital));
            if(res.working_capital < 0) {
                workingCapEl.removeClass('text-primary text-success').addClass('text-danger');
            } else {
                workingCapEl.removeClass('text-danger text-success').addClass('text-primary');
            }
        }
    }, 'json');

        // 2. Chart Data
        $.get(`../api/reports/dashboard_data.php?action=sales_chart&range=${range}`, function (res) {
            $('#chartCard .chart-spinner').addClass('d-none');
            $('#chartCard canvas').css('opacity', '1');

            if (res.status === 'success') {
                const ctx = document.getElementById('salesChart').getContext('2d');

                if (salesChart) salesChart.destroy();

                if (res.sales.length === 0) {
                    // Empty state if no data
                    salesChart = new Chart(ctx, {
                        type: 'line', data: { labels: ['ไม่มีข้อมูล'], datasets: [{ label: 'ยอดขาย', data: [0] }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                    });
                } else {
                    salesChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: res.labels,
                            datasets: [{
                                label: 'ยอดขายสุทธิ (฿)',
                                data: res.sales,
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99,102,241,0.1)',
                                borderWidth: 3, fill: true, tension: 0.4,
                                pointBackgroundColor: '#fff', pointBorderColor: '#6366f1',
                                pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: { label: function (c) { return fm(c.raw); } }
                                }
                            },
                            scales: {
                                y: { beginAtZero: true, grid: { color: '#f1f5f9', borderDash: [5, 5] }, ticks: { callback: v => '฿' + v } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }
            }
        }, 'json');

        // 3. Products Data
        $.get(`../api/reports/dashboard_data.php?action=top_products&range=${range}`, function (res) {
            $('#topProductsCard .top-spinner, #deadProductsCard .top-spinner').addClass('d-none');
            $('#topProductsTable tbody, #deadProductsTable tbody').css('opacity', '1');

            if (res.status === 'success') {
                let topHtml = '';
                res.top.forEach((v, i) => {
                    let badgeClass = i === 0 ? 'bg-warning text-dark' : (i === 1 ? 'bg-secondary text-white' : (i === 2 ? 'bg-orange text-white' : 'bg-light text-muted'));
                    if (i === 2) badgeClass = 'text-white';
                    let style = i === 2 ? 'background: #d97706;' : ''; // Bronze color

                    topHtml += `<tr>
                    <td width="12%"><span class="badge ${badgeClass}" style="${style}">#${i + 1}</span></td>
                    <td class="fw-bold text-dark">${v.prod_name}</td>
                    <td class="text-end text-success fw-bold">${v.qty} ชิ้น</td>
                </tr>`;
                });
                $('#topProductsTable tbody').html(topHtml || `<tr><td colspan="3" class="text-center text-muted py-5"><i class="fa-solid fa-box-open fa-3x opacity-25 mb-3 d-block"></i>ยังไม่มีข้อมูลการขายในช่วงเวลานี้</td></tr>`);

                let deadHtml = '';
                res.dead.forEach((v, i) => {
                    deadHtml += `<tr>
                    <td width="12%"><span class="badge bg-light text-muted">#${i + 1}</span></td>
                    <td class="fw-bold text-dark">${v.prod_name}</td>
                    <td class="text-end"><span class="badge bg-danger-soft text-danger me-1">เหลือ ${v.stock_qty}</span><span class="text-muted small">ขายได้แค่ ${v.sold_qty}</span></td>
                </tr>`;
                });
                $('#deadProductsTable tbody').html(deadHtml || `<tr><td colspan="3" class="text-center text-muted py-5"><i class="fa-solid fa-check-circle fa-3x opacity-25 text-success mb-3 d-block"></i>คลังสินค้ามีสุขภาพดีเยี่ยม!</td></tr>`);
            }
        }, 'json');
    }

    $(document).ready(function () {
        loadDashboardData();

        $('#dateRangeFilter').change(function () {
            loadDashboardData();
        });

        <?php if ($is_admin): ?>
            $('#capitalForm').submit(function (e) {
                e.preventDefault();
                let btn = $(this).find('button[type="submit"]');
                let orgText = btn.text();
                btn.html('<i class="fa-solid fa-spinner fa-spin"></i>').prop('disabled', true);

                $.post('../api/reports/save_capital.php', $(this).serialize(), function (res) {
                    btn.text(orgText).prop('disabled', false);
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'เพิ่มประวัติอัดฉีดทุนเรียบร้อย', timer: 1200, showConfirmButton: false });
                        $('#capitalForm')[0].reset();
                        loadDashboardData(); // Reload to calc working capital
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }, 'json');
            });

            $('#expenseForm').submit(function (e) {
                e.preventDefault();
                Swal.fire({ title: 'กำลังบันทึก...', didOpen: () => Swal.showLoading() });
                $.post('../api/reports/save_expense.php', $(this).serialize(), function (res) {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'บันทึกค่าใช้จ่ายแล้ว', timer: 1200, showConfirmButton: false });
                        $('#expModal').modal('hide');
                        $('#expenseForm')[0].reset();
                        loadDashboardData(); // Reload UI data to reflect new expense
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }, 'json');
            });
        <?php endif; ?>
    });
</script>

<?php include_once '../includes/footer.php'; ?>