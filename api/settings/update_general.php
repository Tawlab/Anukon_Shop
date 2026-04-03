<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if ($_SESSION['role'] !== 'admin') exit;

foreach ($_POST as $key => $value) {
    // ใช้คำสั่ง INSERT ... ON DUPLICATE KEY UPDATE เพื่อความสะดวก
    $stmt = $conn->prepare("INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
}

echo json_encode(['status' => 'success']);
?>