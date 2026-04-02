<?php
ob_start();
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$range = $_GET['range'] ?? 30; 
$end_date = date('Y-m-d 23:59:59');
$start_date = date('Y-m-d 00:00:00', strtotime("-$range days"));

if ($range == 'today') {
    $start_date = date('Y-m-d 00:00:00');
    $end_date = date('Y-m-d 23:59:59');
} elseif ($range == '7') {
    $start_date = date('Y-m-d 00:00:00', strtotime("-7 days"));
} elseif ($range == '30') {
    $start_date = date('Y-m-d 00:00:00', strtotime("-30 days"));
} elseif ($range == 'all') {
    $start_date = '2000-01-01 00:00:00';
}

if ($action === 'finance') {
    $data = [
        'status' => 'success',
        'sales' => 0,
        'cogs' => 0,
        'expenses' => 0,
        'profit' => 0,
        'initial_capital' => 0,
        'injected_capital' => 0,
        'working_capital' => 0
    ];

    $start_date_only = date('Y-m-d', strtotime($start_date));
    $end_date_only = date('Y-m-d', strtotime($end_date));

    // 1. รายได้ (ยอดขาย)
    $sql_sales = "SELECT SUM(total_price) as total_sales FROM bill_sales 
                  WHERE sale_status IN (2, 3) AND sale_date BETWEEN ? AND ?";
    $stmt_sales = $conn->prepare($sql_sales);
    $stmt_sales->bind_param("ss", $start_date, $end_date);
    $stmt_sales->execute();
    $data['sales'] = (float)($stmt_sales->get_result()->fetch_assoc()['total_sales'] ?? 0);

    // 2. รายจ่ายส่วนที่ 1: ต้นทุนสินค้า (PO)
    $sql_cogs = "SELECT SUM(total_cost) as total_cogs FROM bill_purchases 
                 WHERE purchase_status = 2 AND received_date BETWEEN ? AND ?";
    $stmt_cogs = $conn->prepare($sql_cogs);
    $stmt_cogs->bind_param("ss", $start_date_only, $end_date_only);
    $stmt_cogs->execute();
    $data['cogs'] = (float)($stmt_cogs->get_result()->fetch_assoc()['total_cogs'] ?? 0);

    // 3. รายจ่ายส่วนที่ 2: ค่าใช้จ่ายจิปาถะ
    $sql_exp = "SELECT SUM(amount) as total_exp FROM expenses WHERE exp_date BETWEEN ? AND ?";
    $stmt_exp = $conn->prepare($sql_exp);
    $stmt_exp->bind_param("ss", $start_date_only, $end_date_only);
    $stmt_exp->execute();
    $data['expenses'] = (float)($stmt_exp->get_result()->fetch_assoc()['total_exp'] ?? 0);

    // 4. ทุนตั้งต้นร้าน (Initial Capital Fixed)
    $sql_init = "SELECT setting_value FROM store_settings WHERE setting_key = 'initial_capital'";
    $res_init = $conn->query($sql_init);
    $data['initial_capital'] = (float)($res_init->num_rows > 0 ? $res_init->fetch_assoc()['setting_value'] : 0);

    // 4.1 ทุนหมุนเวียนอัดฉีด (Injected Capital)
    $sql_cap = "SELECT SUM(amount) as total_cap FROM capital_history";
    $res_cap = $conn->query($sql_cap);
    $data['injected_capital'] = (float)($res_cap->num_rows > 0 ? $res_cap->fetch_assoc()['total_cap'] : 0);

    // 5. คำนวณกำไร/ขาดทุนสุทธิ (รายได้ - รายจ่ายทั้งหมด)
    $data['profit'] = $data['sales'] - $data['cogs'] - $data['expenses'];

    // 6. คำนวณทุนหมุนเวียนคงเหลือ (ทุนตั้งต้น + ทุนหมุนเวียนอัดฉีด + กำไรสุทธิ)
    $data['working_capital'] = $data['initial_capital'] + $data['injected_capital'] + $data['profit'];

    ob_clean();
    echo json_encode($data);
} elseif ($action === 'sales_chart') {
    // ... (ส่วนโค้ดกราฟ ใช้ของเดิมที่คุณมีได้เลยครับ)
    // ละไว้เพื่อความสั้นกระชับ
    $sql = "SELECT DATE(sale_date) as ddate, SUM(total_price) as daily_sales 
            FROM bill_sales 
            WHERE sale_status IN (2, 3) AND sale_date BETWEEN ? AND ?
            GROUP BY DATE(sale_date) ORDER BY DATE(sale_date) ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $res = $stmt->get_result();

    $labels = []; $sales = [];
    while ($row = $res->fetch_assoc()) {
        $labels[] = date('d/m/Y', strtotime($row['ddate']));
        $sales[] = (float)$row['daily_sales'];
    }
    ob_clean();
    echo json_encode(['status' => 'success', 'labels' => $labels, 'sales' => $sales]);

} elseif ($action === 'top_products') {
    // ... (ส่วนโค้ดสินค้าขายดี ใช้ของเดิมที่คุณมีได้เลยครับ)
    $data = ['status' => 'success', 'top' => [], 'dead' => []];

    $sql_top = "SELECT p.prod_name, SUM(ds.quantity) as qty 
                FROM details_sales ds JOIN bill_sales bs ON ds.sale_id = bs.sale_id 
                JOIN products p ON ds.product_id = p.prod_id
                WHERE bs.sale_status IN (2, 3) AND bs.sale_date BETWEEN ? AND ?
                GROUP BY ds.product_id ORDER BY qty DESC LIMIT 10";
    $stmt_top = $conn->prepare($sql_top);
    $stmt_top->bind_param("ss", $start_date, $end_date);
    $stmt_top->execute();
    $res_top = $stmt_top->get_result();
    while ($r = $res_top->fetch_assoc()) $data['top'][] = $r;

    $sql_dead = "SELECT p.prod_name, s.total_qty as stock_qty, COALESCE(SUM(ds.quantity), 0) as sold_qty
                 FROM products p JOIN stocks s ON p.prod_id = s.prod_id
                 LEFT JOIN details_sales ds ON p.prod_id = ds.product_id 
                 AND ds.sale_id IN (SELECT sale_id FROM bill_sales WHERE sale_status IN (2,3) AND sale_date BETWEEN ? AND ?)
                 WHERE s.total_qty > 10 GROUP BY p.prod_id HAVING sold_qty < 3 ORDER BY stock_qty DESC LIMIT 10";
    $stmt_dead = $conn->prepare($sql_dead);
    $stmt_dead->bind_param("ss", $start_date, $end_date);
    $stmt_dead->execute();
    $res_dead = $stmt_dead->get_result();
    while ($r = $res_dead->fetch_assoc()) $data['dead'][] = $r;

    ob_clean();
    echo json_encode($data);
} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>