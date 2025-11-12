<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nombre = $_POST["nombre"];
  $email = $_POST["email"];
  $mensaje = $_POST["mensaje"];

  // Aquí puedes guardar en base de datos o enviar por correo
  echo "<h2>Gracias, $nombre</h2>";
  echo "<p>Tu mensaje ha sido recibido. Te contactaremos pronto.</p>";
} else {
  echo "<p>Acceso no permitido.</p>";
}
?>
