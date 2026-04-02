<?php
require_once dirname(dirname(__DIR__)) . '/config/db_config.php';

$sql = "CREATE TABLE IF NOT EXISTS capital_history (
    cap_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    amount DECIMAL(12,2) NOT NULL,
    remark VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'capital_history' ensured.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Check if we need to migrate existing capital from store_settings
$res = $conn->query("SELECT setting_value FROM store_settings WHERE setting_key = 'capital' OR setting_key = 'initial_capital' LIMIT 1");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $existing = (float)$row['setting_value'];
    
    if ($existing > 0) {
        $check = $conn->query("SELECT COUNT(*) AS c FROM capital_history");
        $count = $check->fetch_assoc()['c'];
        if ($count == 0) {
            $stmt = $conn->prepare("INSERT INTO capital_history (amount, remark) VALUES (?, 'ยอดยกมาจากระบบเดิม (ทุนเปิดร้าน)')");
            $stmt->bind_param("d", $existing);
            $stmt->execute();
            echo "Migrated $existing to capital_history.\n";
        }
    }
}
$conn->close();
?>
