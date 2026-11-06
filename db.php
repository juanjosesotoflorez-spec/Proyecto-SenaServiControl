<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'sena_serviteca_1';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$conn->set_charset('utf8');
?>
