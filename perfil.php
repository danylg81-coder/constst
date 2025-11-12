<?php
session_start();
require_once "db/conexion.php";

// Redirigir si no está logueado
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Obtener datos actuales del usuario
$usuario_id = $_SESSION['usuario_id'];
$sql = "SELECT nombre, email, telefono FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];

    if (empty($nombre)) {
        $error = 'El nombre es obligatorio.';
    } else {
        if (!empty($password)) {
            // Actualizar con contraseña
            if (strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nombre = ?, telefono = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssi', $nombre, $telefono, $password_hash, $usuario_id);
            }
        } else {
            // Actualizar sin contraseña
            $sql = "UPDATE usuarios SET nombre = ?, telefono = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssi', $nombre, $telefono, $usuario_id);
        }

        if (empty($error)) {
            if ($stmt->execute()) {
                // Actualizar sesión
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_telefono'] = $telefono;
                
                $success = 'Perfil actualizado correctamente.';
                
                // Recargar datos actualizados
                $sql = "SELECT nombre, email, telefono FROM usuarios WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $usuario_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $usuario = $result->fetch_assoc();
            } else {
                $error = 'Error al actualizar el perfil: ' . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/perfil.css">
    
</head>
<body>
    <!-- Menú de usuario desplegable -->
    <div class="user-menu-container" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <div class="user-menu">
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                <!-- Usuario logueado -->
                <button class="user-toggle" id="userToggle">
                    <span class="user-icon">👤</span>
                    <span class="user-name"><?php echo explode(' ', $_SESSION['usuario_nombre'])[0]; ?></span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-info">
                        <strong><?php echo $_SESSION['usuario_nombre']; ?></strong>
                        <span><?php echo $_SESSION['usuario_email']; ?></span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="perfil.php" class="dropdown-item">
                        <span class="item-icon">⚙️</span>
                        Mi Perfil
                    </a>
                    <a href="mis_pedidos.php" class="dropdown-item">
                        <span class="item-icon">📦</span>
                        Mis Pedidos
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout">
                        <span class="item-icon">🚪</span>
                        Cerrar Sesión
                    </a>
                </div>
            <?php else: ?>
                <!-- Usuario no logueado -->
                <button class="user-toggle" id="userToggle">
                    <span class="user-icon">👤</span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                
                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <strong>Mi Cuenta</strong>
                    </div>
                    <a href="login.php" class="dropdown-item">
                        <span class="item-icon">🔐</span>
                        Iniciar Sesión
                    </a>
                    <a href="registro.php" class="dropdown-item">
                        <span class="item-icon">📝</span>
                        Crear Cuenta
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>⚙️ Mi Perfil</h1>
            <p>Actualiza tu información personal</p>
        </div>

        <?php if ($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="info-box">
            <h3>💡 Información importante</h3>
            <p>Tu email no se puede modificar. Si necesitas cambiar tu email, contacta con el administrador.</p>
        </div>

        <form method="post" action="perfil.php">
            <div class="form-group">
                <label for="nombre">Nombre completo <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" 
                       value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled>
                <div class="form-note">El email no se puede modificar</div>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" 
                       value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                <div class="form-note">Para contactarte sobre tus pedidos</div>
            </div>

            <div class="password-section">
                <h3 style="color: #0B3A66; margin-bottom: 15px;">🔒 Cambiar contraseña</h3>
                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Dejar en blanco para no cambiar">
                    <div class="form-note">Mínimo 6 caracteres</div>
                </div>
            </div>

            <button type="submit" class="btn">💾 Guardar Cambios</button>
        </form>

        <div class="links">
            <a href="mis_pedidos.php">📦 Ver mis pedidos</a>
            <a href="index.php">← Volver al inicio</a>
        </div>
    </div>

    <script src="scripts/perfil.js" defer></script>
</body>
</html>