<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include("../db/conexion.php");

// Procesar cambio de rol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $usuario_id = intval($_POST['usuario_id']);
    $nuevo_rol = $conn->real_escape_string($_POST['nuevo_rol']);

    $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_rol, $usuario_id);
    
    if ($stmt->execute()) {
        $mensaje = "Rol actualizado exitosamente";
    } else {
        $error = "Error al actualizar el rol: " . $stmt->error;
    }
    $stmt->close();
}

// Obtener todos los usuarios
$usuarios = $conn->query("SELECT id, usuario, email, rol FROM usuarios ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Madre Agua ST</title>
    <link rel="stylesheet" href="styles/usuarios.css">
</head>
<style> 
            /* Usa los mismos estilos que en panel.php para mantener consistencia */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #34495e;
            text-align: center;
        }

        .sidebar-header h2 {
            font-size: 1.2rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            padding: 0;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover {
            background: #34495e;
            border-left: 4px solid #3498db;
        }

        .main-content {
            flex: 1;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: #3498db;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }

        .card-body {
            padding: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #555;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-control {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
    
</style>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Madre Agua ST</h2>
                <p>Panel de Administración</p>
                <p class="user-info">Usuario: <?php echo $_SESSION['usuario']; ?> (<?php echo $_SESSION['usuario_rol']; ?>)</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="panel.php">Gestión de Productos</a></li>
                <li><a href="pedidos.php">Gestión de Pedidos</a></li>
                <li><a href="admin_facturas.php">Gestión de Facturas</a></li>
                <li><a href="usuarios.php">Gestión de Usuarios</a></li>
                <li><a href="estadisticas.php" class="active">Estadísticas</a></li>
                <li><a href="../index.php">Volver al Sitio</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Gestión de Usuarios</h1>
                <p>Administra los usuarios del sistema</p>
            </div>

            <!-- Mensajes de alerta -->
            <?php if (isset($mensaje)): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Lista de usuarios -->
            <div class="card">
                <div class="card-header">
                    Lista de Usuarios
                </div>
                <div class="card-body">
                    <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Rol Actual</th>
                                    <th>Cambiar Rol</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($usuario = $usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <span class="rol-badge rol-<?php echo $usuario['rol']; ?>">
                                            <?php echo ucfirst($usuario['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                            <select name="nuevo_rol" class="form-control" required>
                                                <option value="user" <?php echo $usuario['rol'] == 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $usuario['rol'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <button type="submit" name="cambiar_rol" class="btn btn-primary">Cambiar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No hay usuarios registrados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>