<?php

$server = "127.0.0.1";
$user = "root";
$pass = "";
$db = "servifarmacia_rk";

$conexion = mysqli_connect($server, $user, $pass, $db);

if (!$conexion) {
    die("Conexión fallida: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");

?> 