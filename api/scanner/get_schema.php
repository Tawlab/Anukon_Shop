<?php
require_once '../../config/db_config.php';
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $table = $row[0];
    echo "TABLE: $table\n";
    $c = $conn->query("SHOW COLUMNS FROM `$table`");
    while($col = $c->fetch_assoc()){
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
?>
