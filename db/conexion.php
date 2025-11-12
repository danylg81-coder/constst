<?php
// Datos de conexión
$host = "localhost";
$usuario = "root";
$contrasena = "";
$base_de_datos = "constst_db";

// Crear conexión
$conn = new mysqli($host, $usuario, $contrasena, $base_de_datos);

// Verificar conexión
if ($conn->connect_error) {
  die("Error de conexión: " . $conn->connect_error);
}

// Establecer codificación UTF-8
$conn->set_charset("utf8");
?>
