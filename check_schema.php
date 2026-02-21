<?php
require_once 'config/db.php';
$res = mysqli_query($conn, "DESCRIBE follow_ups");
$rows = [];
while($row = mysqli_fetch_assoc($res)) {
    $rows[] = $row;
}
file_put_contents('schema_out.txt', var_export($rows, true));
echo "Done\n";
?>
