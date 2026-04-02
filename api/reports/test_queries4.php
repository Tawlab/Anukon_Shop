<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_GET['action'] = 'finance';
$_GET['range'] = '30';
ob_start();
include 'dashboard_data.php';

$output = ob_get_clean();
// Strip notice manually for testing
$json_only = preg_replace('/<br \/>\r?\n<b>Notice.*$/m', '', $output);
$json_only = trim($output);

if (($pos = strpos($output, '{"status"')) !== false) {
    $json_output = substr($output, $pos);
    echo "JSON IS: " . $json_output . "\n";
    echo "IS VALID JSON? " . (json_decode($json_output) !== null ? 'YES' : 'NO') . "\n";
}

echo "HEX DUMP OF FIRST 10 CHARS:\n";
for ($i=0; $i<min(10, strlen($output)); $i++) {
    echo bin2hex($output[$i]) . " ";
}
?>
