<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_GET['action'] = 'top_products';
$_GET['range'] = '30';
ob_start();
include 'dashboard_data.php';
$output = ob_get_clean();
echo "RAW_OUTPUT_START\n" . $output . "\nRAW_OUTPUT_END";
?>
