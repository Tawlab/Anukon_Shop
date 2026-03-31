<?php
require_once '../../config/db_config.php';

$prov_id = $_GET['prov_id'] ?? 0;

$sql = "SELECT * FROM districts WHERE prov_id = ? ORDER BY dist_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $prov_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<option value="'.$row['dist_id'].'">'.$row['dist_name'].'</option>';
    }
}
$stmt->close();
$conn->close();
?>