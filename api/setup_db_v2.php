<?php
require_once dirname(__DIR__) . '/config/db_config.php';

// 1. Table store_settings (for Initial Capital)
$conn->query("CREATE TABLE IF NOT EXISTS store_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255)
)");
$conn->query("INSERT IGNORE INTO store_settings (setting_key, setting_value) VALUES ('initial_capital', '0')");

// 2. Table expenses (for Other expenses)
$conn->query("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exp_date DATE NOT NULL,
    title VARCHAR(255),
    amount DECIMAL(10,2),
    remark TEXT
)");

echo "Phase 8 Database Setup Complete!\n";
$conn->close();
?>
