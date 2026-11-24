<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'hospital_resource_system';
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
$smtp_enabled = true; 
$smtp_user = 'minaliarandara@gmail.com'; 
$smtp_pass = 'fsubodzihzrdebdz';
