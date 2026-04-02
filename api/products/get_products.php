<?php
require_once '../../config/db_config.php';

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';
$search_param = "%{$search}%";

$type_id = $_GET['category'] ?? ''; // Matching the new shop.php key

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

$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode(['status' => 'success', 'products' => $products]);
} else {
    echo json_encode(['status' => 'success', 'products' => []]);
}

$stmt->close();
$conn->close();
?>