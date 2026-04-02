<?php 
include_once '../includes/header.php'; 

if (!in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: dashboard.php');
    exit;
}

$is_admin = ($_SESSION['role'] === 'admin');
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    /* Premium UI Components */
    .glass-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 1.25rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .glass-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
    }
    .mini-stat-card {
        border-radius: 1rem;
        padding: 1.5rem;
        color: white;
        position: relative;
        overflow: hidden;
    }
    .mini-stat-card::after {
        content: '';
        position: absolute;
        top: -20px;
        right: -20px;
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }
    .bg-gradient-profit { background: linear-gradient(135deg, #10b981, #059669); }
    .bg-gradient-sales { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .bg-gradient-cogs { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .bg-gradient-expense { background: linear-gradient(135deg, #ef4444, #dc2626); }
    
    .spinner-centered { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 11; }
    
    .table-premium th {
        background: transparent;
        color: var(--text-muted);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--border);
    }
    .table-premium td {
        vertical-align: middle;
        border-bottom: 1px dashed var(--border);
    }
</style>

<main class="page-content fade-up">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-chart-pie text-primary me-2"></i>สรุปการขายและการเงิน</h4>
            <p class="text-muted small mb-0">รายงานภาพรวมรายได้ ค่าใช้จ่าย และสถิติสินค้า</p>
        </div>
        
        <div class="d-flex gap-2 align-items-center bg-white p-2 rounded-pill shadow-sm">
            <i class="fa-regular fa-calendar text-muted ms-2"></i>
            <select id="dateRangeFilter" class="form-select border-0 shadow-none bg-transparent fw-bold text-primary" style="min-width: 150px; cursor: pointer;">
                <option value="today">วันนี้</option>
                <option value="7">7 วันย้อนหลัง</option>
                <option value="30" selected>30 วันย้อนหลัง</option>
                <option value="all">ตัวเลขทั้งหมด</option>
            </select>
        </div>
    </div>

    <!-- 4 Main Finance Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="mini-stat-card bg-gradient-profit shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="small fw-bold opacity-75">กำไร/ขาดทุน สุทธิ</div>
                    <i class="fa-solid fa-sack-dollar opacity-50 fs-4"></i>
                </div>
                <div class="spinner-border spinner-border-sm text-light spinner-finance d-none"></div>
                <h3 class="fw-bold mb-0 finance-val" id="sum_profit">-</h3>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="mini-stat-card bg-gradient-sales shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="small fw-bold opacity-75">ยอดขาย (รายได้)</div>
                    <i class="fa-solid fa-arrow-trend-up opacity-50 fs-4"></i>
                </div>
                <div class="spinner-border spinner-border-sm text-light spinner-finance d-none"></div>
                <h3 class="fw-bold mb-0 finance-val" id="sum_sales">-</h3>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="mini-stat-card bg-gradient-cogs shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="small fw-bold opacity-75">ต้นทุนรับเข้า (PO)</div>
                    <i class="fa-solid fa-boxes-stacked opacity-50 fs-4"></i>
                </div>
                <div class="spinner-border spinner-border-sm text-light spinner-finance d-none"></div>
                <h3 class="fw-bold mb-0 finance-val" id="sum_cogs">-</h3>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="mini-stat-card bg-gradient-expense shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="small fw-bold opacity-75">ค่าใช้จ่ายอื่นๆ</div>
                    <i class="fa-solid fa-receipt opacity-50 fs-4"></i>
                </div>
                <div class="spinner-border spinner-border-sm text-light spinner-finance d-none"></div>
                <h3 class="fw-bold mb-0 finance-val" id="sum_exp">-</h3>
            </div>
        </div>
    </div>

    <!-- Chart & Capital Section -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="glass-card p-4 h-100 position-relative" id="chartBox">
                <div class="spinner-border text-primary spinner-centered chart-spinner d-none"></div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-area text-primary me-2"></i>กราฟแนวโน้มยอดขาย</h6>
                    <span class="badge bg-primary-soft text-primary rounded-pill px-3">รายวัน</span>
                </div>
                <div style="height: 300px;"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="glass-card p-4 h-100 d-flex flex-column position-relative" id="capitalBox">
                <div class="spinner-border text-warning spinner-centered cap-spinner d-none"></div>
                <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-vault text-warning me-2"></i>การจัดการเงินทุนร้านค้า</h6>
                
                <div class="p-3 bg-light rounded-4 mb-3 border">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small fw-bold">1. ทุนเปิดร้าน (ตั้งต้น)</span>
                        <span class="fw-bold text-dark" id="disp_init_cap">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                        <span class="text-secondary small fw-bold">2. ทุนหมุนเวียนอัดฉีดเข้า</span>
                        <span class="fw-bold text-primary" id="disp_inj_cap">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                        <span class="text-secondary small fw-bold">3. กำไร/ขาดทุนสะสมสุทธิ</span>
                        <span class="fw-bold" id="disp_flow_profit">-</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-success">ทุนหมุนเวียนคงเหลือรวม</span>
                        <span class="fw-bold text-success fs-4" id="disp_working_cap">-</span>
                    </div>
                </div>

                <?php if ($is_admin): ?>
                <ul class="nav nav-pills nav-fill mb-3 flex-nowrap gap-1" style="font-size: 0.8rem;">
                    <li class="nav-item">
                        <button class="nav-link active py-1" data-bs-toggle="tab" data-bs-target="#tabInj">อัดฉีดหมุนเวียน</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1" data-bs-toggle="tab" data-bs-target="#tabInit">ตั้งทุนเริ่มต้นใหม่</button>
                    </li>
                </ul>

                <div class="tab-content mt-auto">
                    <!-- Tab อัดฉีดหมุนเวียน -->
                    <div class="tab-pane fade show active" id="tabInj">
                        <form id="frmCapitalWorking" class="mt-auto">
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

                    <!-- Tab ทุนตั้งต้นตายตัว -->
                    <div class="tab-pane fade" id="tabInit">
                        <form id="frmCapitalInit" class="mt-auto">
                            <input type="hidden" name="type" value="initial">
                            <div class="input-group input-group-sm mb-2 shadow-sm">
                                <span class="input-group-text bg-white border-end-0 text-warning"><i class="fa-solid fa-store"></i></span>
                                <input type="number" step="0.01" class="form-control border-start-0 fw-bold" id="input_initial" name="amount" placeholder="ยอดทุนเปิดร้านเดิม (฿)" required>
                            </div>
                            <button class="btn btn-warning w-100 fw-bold btn-sm shadow-sm text-dark" type="submit">อัปเดตแทนที่ทุนเริ่มต้น</button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="mt-auto text-center border p-3 rounded-3 bg-white">
                    <i class="fa-solid fa-lock text-muted mb-2 fs-5"></i>
                    <p class="small text-muted mb-0">เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถตั้งค่าเงินทุนได้</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Products Tables -->
    <div class="row g-4 mb-4">
        <!-- Top Products -->
        <div class="col-lg-6">
            <div class="glass-card p-4 h-100 position-relative" id="topBox">
                <div class="spinner-border text-warning spinner-centered top-spinner d-none"></div>
                <h6 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-fire text-danger me-2"></i>สินค้าขายดี Top 10</h6>
                <div class="table-responsive">
                    <table class="table table-premium mb-0" id="tableTop">
                        <thead><tr><th width="15%">อันดับ</th><th>ชื่อสินค้า</th><th class="text-end">จำนวนที่ขายได้</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Dead Products -->
        <div class="col-lg-6">
            <div class="glass-card p-4 h-100 position-relative" id="deadBox">
                <div class="spinner-border text-danger spinner-centered top-spinner d-none"></div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-ghost text-secondary me-2"></i>สินค้าขายไม่ออก (Dead Stock)</h6>
                    <span class="badge bg-light text-muted border px-2" data-bs-toggle="tooltip" title="สต็อก > 10 ชิ้น แต่ขายได้ < 3 ชิ้น"><i class="fa-solid fa-circle-info"></i> เงื่อนไข</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-premium mb-0" id="tableDead">
                        <thead><tr><th width="15%">อันดับ</th><th>ชื่อสินค้า</th><th class="text-end">สถานะสต็อก</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
let myChart = null;

function formatMoney(amount) {
    return '฿' + parseFloat(amount).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits:2});
}

function loadData() {
    let range = $('#dateRangeFilter').val();

    // Show skeletons
    $('.finance-val').addClass('d-none');
    $('.spinner-finance, .chart-spinner, .top-spinner, .cap-spinner').removeClass('d-none');
    $('#chartBox canvas, #tableTop tbody, #tableDead tbody, #capitalBox .bg-light').css('opacity', '0.3');

    // 1. Finance & Capital
    $.get(`../api/reports/dashboard_data.php?action=finance&range=${range}`, function(res) {
        $('.spinner-finance, .cap-spinner').addClass('d-none');
        $('.finance-val').removeClass('d-none');
        $('#capitalBox .bg-light').css('opacity', '1');

        if(res.status === 'success') {
            $('#sum_profit').text(formatMoney(res.profit));
            $('#sum_sales').text(formatMoney(res.sales));
            $('#sum_cogs').text(formatMoney(res.cogs));
            $('#sum_exp').text(formatMoney(res.expenses));
            
            if($('#input_initial').length) $('#input_initial').val(res.initial_capital);
            $('#disp_init_cap').text(formatMoney(res.initial_capital));
            $('#disp_inj_cap').text(formatMoney(res.injected_capital));
            
            let flowEl = $('#disp_flow_profit');
            flowEl.text((res.profit > 0 ? '+' : '') + formatMoney(res.profit));
            flowEl.attr('class', 'fw-bold ' + (res.profit < 0 ? 'text-danger' : (res.profit > 0 ? 'text-success' : 'text-dark')));

            let workEl = $('#disp_working_cap');
            workEl.text(formatMoney(res.working_capital));
            workEl.attr('class', 'fw-bold fs-4 ' + (res.working_capital < 0 ? 'text-danger' : 'text-primary'));
        }
    }, 'json');

    // 2. Chart
    $.get(`../api/reports/dashboard_data.php?action=sales_chart&range=${range}`, function(res) {
        $('.chart-spinner').addClass('d-none');
        $('#chartBox canvas').css('opacity', '1');

        if(res.status === 'success') {
            const ctx = document.getElementById('trendChart').getContext('2d');
            if(myChart) myChart.destroy();
            
            let gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

            if (res.sales.length === 0) {
                myChart = new Chart(ctx, { type: 'line', data: { labels: ['ว่าง'], datasets: [{ data: [0] }] }, options: { plugins: { legend: { display:false } }} });
            } else {
                myChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: res.labels,
                        datasets: [{
                            label: 'ยอดขายสุทธิ',
                            data: res.sales,
                            borderColor: '#3b82f6',
                            backgroundColor: gradient,
                            borderWidth: 3, fill: true, tension: 0.4,
                            pointBackgroundColor: '#fff', pointBorderColor: '#3b82f6',
                            pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => formatMoney(c.raw) } } },
                        scales: { y: { beginAtZero: true, border: {dash: [4,4]}, grid: {color: '#e2e8f0'} }, x: { grid: {display: false} } }
                    }
                });
            }
        }
    }, 'json');

    // 3. Products Ranking
    $.get(`../api/reports/dashboard_data.php?action=top_products&range=${range}`, function(res) {
        $('.top-spinner').addClass('d-none');
        $('#tableTop tbody, #tableDead tbody').css('opacity', '1');

        if(res.status === 'success') {
            let topHtml = '';
            res.top.forEach((v, i) => {
                let badge = i===0 ? 'bg-warning text-dark' : (i===1 ? 'bg-secondary' : (i===2 ? 'bg-orange text-white' : 'bg-light text-muted'));
                if(i===2) badge = 'text-white';
                let style = i===2 ? 'background: #d97706;' : '';
                topHtml += `<tr>
                    <td><span class="badge ${badge} rounded-pill px-2 py-1" style="${style}"># ${i+1}</span></td>
                    <td class="fw-bold text-dark">${v.prod_name}</td>
                    <td class="text-end text-success fw-bold">${v.qty}</td>
                </tr>`;
            });
            $('#tableTop tbody').html(topHtml || `<tr><td colspan="3" class="text-center py-4 text-muted">ไม่พบข้อมูล</td></tr>`);

            let deadHtml = '';
            res.dead.forEach((v, i) => {
                deadHtml += `<tr>
                    <td><span class="badge bg-light text-muted rounded-pill px-2 py-1"># ${i+1}</span></td>
                    <td class="fw-bold text-dark">${v.prod_name}</td>
                    <td class="text-end">
                        <div class="small fw-bold text-danger">เหลือ ${v.stock_qty}</div>
                        <div class="small text-muted" style="font-size:0.7rem;">ขายได้ ${v.sold_qty}</div>
                    </td>
                </tr>`;
            });
            $('#tableDead tbody').html(deadHtml || `<tr><td colspan="3" class="text-center py-4 text-muted">ไม่มีสินค้าค้างสต็อก</td></tr>`);
        }
    }, 'json');
}

$(document).ready(function() {
    // Tooltip init
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) })

    loadData();

    $('#dateRangeFilter').change(function() {
        loadData();
    });

    <?php if ($is_admin): ?>
    $('#frmCapitalWorking, #frmCapitalInit').submit(function(e) {
        e.preventDefault();
        let form = $(this);
        let btn = form.find('button');
        let orgText = btn.text();
        btn.html('<i class="fa-solid fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.post('../api/reports/save_capital.php', form.serialize(), function(res) {
            btn.text(orgText).prop('disabled', false);
            if(res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'อัปเดตเงินทุนสำเร็จ', timer: 1200, showConfirmButton: false});
                if (form.attr('id') === 'frmCapitalWorking') form[0].reset();
                loadData(); 
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        }, 'json');
    });
    <?php endif; ?>
});
</script>

<?php include_once '../includes/footer.php'; ?>
