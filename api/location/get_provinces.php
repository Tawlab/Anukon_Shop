<?php
require_once '../../config/db_config.php';

$sql = "SELECT * FROM provinces ORDER BY prov_name ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<option value="'.$row['prov_id'].'">'.$row['prov_name'].'</option>';
    }
}
$conn->close();
?>