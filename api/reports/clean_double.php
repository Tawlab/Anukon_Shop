<?php
require_once dirname(dirname(__DIR__)) . '/config/db_config.php';
$conn->query("DELETE FROM capital_history WHERE remark = 'ยอดยกมาจากระบบเดิม (ทุนเปิดร้าน)'");
echo "Cleaned up double count\n";
?>
