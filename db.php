
<?php
$host = "sql211.infinityfree.com"; // Replace with your actual InfinityFree MySQL host
$user = "if0_39511631";     // Your InfinityFree MySQL username
$pass = "VTRa58jzFaI"; // Your InfinityFree MySQL password
$db   = "if0_39511631_complaints"; // Your database name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>