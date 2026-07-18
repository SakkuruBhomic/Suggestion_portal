<?php
include 'db.php';
echo $mysqli->connect_error
     ? "DB Error: " . $mysqli->connect_error
     : "Connection OK!";
?>