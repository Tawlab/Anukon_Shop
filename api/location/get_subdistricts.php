<?php
require_once '../../config/db_config.php';

$dist_id = $_GET['dist_id'] ?? 0;

$sql = "SELECT * FROM subdistricts WHERE dist_id = ? ORDER BY sub_dist_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $dist_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // เก็บ zip_code ไว้ใน data-zip เพื่อให้ jQuery ดึงไปแสดงในช่องรหัสไปรษณีย์
        echo '<option value="'.$row['sub_dist_id'].'" data-zip="'.$row['zip_code'].'">'.$row['sub_dist_name'].'</option>';
    }
}
$stmt->close();
$conn->close();
?>