<?php
session_start();
require_once "db/conexion.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $usuario = trim($_POST['usuario']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    if (empty($nombre) || empty($email) || empty($usuario) || empty($password)) {
        $error = 'Por favor, complete todos los campos obligatorios.';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (strlen($usuario) < 4) {
        $error = 'El nombre de usuario debe tener al menos 4 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
        $error = 'El nombre de usuario solo puede contener letras, números y guiones bajos.';
    } else {
        // Verificar si el email ya existe
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'El email ya está registrado.';
        } else {
            // Verificar si el usuario ya existe
            $sql = "SELECT id FROM usuarios WHERE usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = 'El nombre de usuario ya está en uso.';
            } else {
                // Hash de la contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $rol = 'user'; // Rol por defecto

                // Insertar el nuevo usuario con todos los campos
                $sql = "INSERT INTO usuarios (nombre, email, telefono, direccion, password, usuario, rol) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssssss', $nombre, $email, $telefono, $direccion, $password_hash, $usuario, $rol);

                if ($stmt->execute()) {
                    $success = 'Registro exitoso. Ahora puede iniciar sesión.';
                    // Redirigir al login después de 2 segundos
                    header('refresh:2;url=login.php');
                } else {
                    $error = 'Error al registrar el usuario. Inténtelo de nuevo.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/registro.css">
    
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Madre Agua ST</h1>
            <p>Crear nueva cuenta</p>
        </div>

        <?php if ($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <form method="post" action="registro.php">
            <div class="form-group">
                <label for="nombre">Nombre completo <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="usuario">Nombre de usuario <span class="required">*</span></label>
                <input type="text" id="usuario" name="usuario" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
                <div class="form-note">Mínimo 4 caracteres. Solo letras, números y _</div>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="direccion">Dirección</label>
                <textarea id="direccion" name="direccion" rows="3"><?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?></textarea>
                <div class="form-note">Para envíos a domicilio</div>
            </div>

            <div class="form-group">
                <label for="password">Contraseña <span class="required">*</span></label>
                <input type="password" id="password" name="password" required>
                <div class="form-note">Mínimo 6 caracteres</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar contraseña <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn">📝 Registrarse</button>
        </form>

        <div class="links">
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            <p><a href="index.php">← Volver al inicio</a></p>
        </div>
    </div>
<script src="scripts/registro.js" defer></script>
    
</body>
</html>