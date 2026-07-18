<?php
require 'db.php';
echo $conn->connect_error
     ? 'DB Error: ' . $conn->connect_error
     : 'Connection OK!';
?>