<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'notessdfsldjkfb';

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
} catch (Exception $e) {
    $conn = null;
}
?>
