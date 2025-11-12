<?php
session_start();
require_once "db/conexion.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Por favor, ingrese email y contraseña.';
    } else {
        // Consulta modificada para incluir el rol
        $sql = "SELECT id, usuario, nombre, email, telefono, direccion, password, rol FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $usuario = $result->fetch_assoc();
            if (password_verify($password, $usuario['password'])) {
                // Iniciar sesión con todos los datos
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario'] = $usuario['usuario'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_telefono'] = $usuario['telefono'];
                $_SESSION['usuario_direccion'] = $usuario['direccion'];
                $_SESSION['usuario_rol'] = $usuario['rol']; // Nuevo campo de rol
                $_SESSION['loggedin'] = true;

                // Redirigir según el rol
                if ($usuario['rol'] == 'admin') {
                    header('Location: index.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'No existe una cuenta con ese email.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/login.css">
  
</head>
<body>
    <!-- Fondo con partículas animadas -->
    <div class="particles-container" id="particlesContainer">
        <!-- Partículas se generan con JavaScript -->
        <div class="glow-effect" style="top: 20%; left: 10%;"></div>
        <div class="glow-effect" style="top: 60%; right: 15%; animation-delay: -2s;"></div>
        <div class="glow-effect" style="bottom: 10%; left: 20%; animation-delay: -4s;"></div>
    </div>

    <div class="container">
        <div class="logo">
            <h1>Madre Agua ST</h1>
            <p>Iniciar sesión en tu cuenta</p>
        </div>

        <?php if ($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="input-group">
                <span class="input-icon">📧</span>
                <input type="email" id="email" name="email" class="form-input" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       placeholder="Ingresa tu email" required>
            </div>

            <div class="input-group">
                <span class="input-icon">🔒</span>
                <input type="password" id="password" name="password" class="form-input" 
                       placeholder="Ingresa tu contraseña" required>
            </div>

            <button type="submit" class="btn-login">
                <span style="margin-right: 8px;">🔐</span>
                Iniciar Sesión
            </button>
        </form>

        <div class="links">
            <a href="registro.php">
                <span>📝</span>
                Crear nueva cuenta
            </a>
            <a href="index.php">
                <span>←</span>
                Volver al inicio
            </a>
        </div>

        <!-- <div class="demo-account">
            <h3>💡 Cuenta de demostración</h3>
            <p><strong>Email:</strong> demo@madreagua.cu</p>
            <p><strong>Contraseña:</strong> demodemo</p>
        </div> -->
    </div>
<script src="scripts/login.js" defer></script>
</body>
</html>