<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'sales_chart') {
    // ยอดขาย 30 วันย้อนหลัง
    $sql = "SELECT DATE(sale_date) as t_date, SUM(total_price) as total 
            FROM bill_sales 
            WHERE sale_status = 3 AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            GROUP BY DATE(sale_date) ORDER BY t_date ASC";
    $result = $conn->query($sql);
    
    $labels = [];
    $data_sales = [];
    
    while($row = $result->fetch_assoc()) {
        $labels[] = date('d/m', strtotime($row['t_date']));
        $data_sales[] = (float)$row['total'];
    }
    
    echo json_encode(['status' => 'success', 'labels' => $labels, 'sales' => $data_sales]);
}
elseif ($action === 'top_products') {
    // 10 อันดับขายดี
    $sql = "SELECT p.prod_name, SUM(d.quantity) as qty 
            FROM details_sales d 
            JOIN bill_sales b ON d.sale_id = b.sale_id
            JOIN products p ON d.product_id = p.prod_id
            WHERE b.sale_status = 3
            GROUP BY d.product_id 
            ORDER BY qty DESC LIMIT 10";
    $result = $conn->query($sql);
    $top = [];
    while($row = $result->fetch_assoc()) {
        $top[] = $row;
    }

    // 10 อันดับค้างสต็อก (ขายได้น้อยสุด หรือไม่เคยขายเลย แต่มีสต็อกเยอะ)
    $sql2 = "SELECT p.prod_name, COALESCE(s.total_qty, 0) as stock_qty, 
                    COALESCE(SUM(d.quantity), 0) as sold_qty
             FROM products p
             LEFT JOIN stocks s ON p.prod_id = s.prod_id
             LEFT JOIN details_sales d ON p.prod_id = d.product_id
             LEFT JOIN bill_sales b ON d.sale_id = b.sale_id AND b.sale_status = 3
             GROUP BY p.prod_id
             HAVING stock_qty > 0
             ORDER BY sold_qty ASC, stock_qty DESC LIMIT 10";
    $result2 = $conn->query($sql2);
    $dead = [];
    while($row = $result2->fetch_assoc()) {
        $dead[] = $row;
    }

    echo json_encode(['status' => 'success', 'top' => $top, 'dead' => $dead]);
}
elseif ($action === 'finance') {
    // คำนวณกำไร
    $month = date('m');
    $year = date('Y');

    // 1. ยอดขายรวมเดือนนี้
    $q_sales = $conn->query("SELECT SUM(total_price) AS sum FROM bill_sales WHERE MONTH(sale_date) = $month AND YEAR(sale_date) = $year AND sale_status = 3");
    $total_sales = (float)($q_sales->fetch_assoc()['sum'] ?? 0);

    // 2. ต้นทุนขาย (COGS) เดือนนี้ (อิงจากราคาทุนปัจจุบันของสินค้า)
    $sql_cogs = "SELECT SUM(d.quantity * p.cost) as sum_cogs 
                 FROM details_sales d 
                 JOIN bill_sales b ON d.sale_id = b.sale_id 
                 JOIN products p ON d.product_id = p.prod_id
                 WHERE MONTH(b.sale_date) = $month AND YEAR(b.sale_date) = $year AND b.sale_status = 3";
    $q_cogs = $conn->query($sql_cogs);
    $total_cogs = (float)($q_cogs->fetch_assoc()['sum_cogs'] ?? 0);

    // 3. ค่าใช้จ่ายอื่นๆ 
    $q_exp = $conn->query("SELECT SUM(amount) AS sum_exp FROM expenses WHERE MONTH(exp_date) = $month AND YEAR(exp_date) = $year");
    $total_exp = (float)($q_exp->fetch_assoc()['sum_exp'] ?? 0);

    // 4. ทุนเริ่มต้น
    $q_cap = $conn->query("SELECT setting_value FROM store_settings WHERE setting_key = 'initial_capital'");
    $cap = (float)($q_cap->fetch_assoc()['setting_value'] ?? 0);

    $net_profit = $total_sales - $total_cogs - $total_exp;

    echo json_encode([
        'status' => 'success',
        'sales' => $total_sales,
        'cogs' => $total_cogs,
        'expenses' => $total_exp,
        'profit' => $net_profit,
        'capital' => $cap
    ]);
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
$conn->close();
?>
