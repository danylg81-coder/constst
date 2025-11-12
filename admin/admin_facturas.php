<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Incluir y verificar conexión
include("../db/conexion.php");

// Verificar si la conexión se estableció correctamente
if (!isset($conn) || $conn->connect_error) {
    die("❌ Error: No se pudo conectar a la base de datos. Error: " . $conn->connect_error);
}

// Consultar todas las facturas
$sql = "SELECT * FROM facturas ORDER BY fecha DESC";
$resultado = $conn->query($sql);

// Verificar si hay error en la consulta
if (!$resultado) {
    die("❌ Error en la consulta: " . $conn->error);
}

// Estados disponibles
$estados = ['pendiente', 'confirmado', 'procesando', 'completado'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Madre Agua ST</title>
    <link rel="stylesheet" href="../styles/panel.css">
     <link rel="stylesheet" href="../styles/admin_facturas.css">
</head>

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
                <h1>Gestión de Facturas</h1>
                <p>Administra los estados de los pedidos de los clientes</p>
            </div>

            <?php
            // Mostrar mensajes de sesión
            if (isset($_SESSION['mensaje'])) {
                echo '<div class="success">' . $_SESSION['mensaje'] . '</div>';
                unset($_SESSION['mensaje']);
            }

            // Estadísticas rápidas
            $stats_sql = "SELECT estado, COUNT(*) as total FROM facturas GROUP BY estado";
            $stats_result = $conn->query($stats_sql);
            
            if (!$stats_result) {
                echo '<div class="error">Error al cargar estadísticas: ' . $conn->error . '</div>';
                $estadisticas = [];
            } else {
                $estadisticas = [];
                while($row = $stats_result->fetch_assoc()) {
                    $estadisticas[$row['estado']] = $row['total'];
                }
            }
            ?>

            <div class="stats">
                <div class="stat-card pendiente">
                    <h3>⏳ Pendientes</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $estadisticas['pendiente'] ?? 0; ?></p>
                </div>
                <div class="stat-card confirmado">
                    <h3>✅ Confirmados</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $estadisticas['confirmado'] ?? 0; ?></p>
                </div>
                <div class="stat-card procesando">
                    <h3>🚚 Procesando</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $estadisticas['procesando'] ?? 0; ?></p>
                </div>
                <div class="stat-card completado">
                    <h3>🎉 Completados</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $estadisticas['completado'] ?? 0; ?></p>
                </div>
            </div>

            <div class="search-box">
                <form method="GET">
                    <input type="text" name="buscar" placeholder="Buscar por número de factura..." 
                           value="<?php echo $_GET['buscar'] ?? ''; ?>">
                    <select name="estado">
                        <option value="">Todos los estados</option>
                        <?php foreach($estados as $estado): ?>
                            <option value="<?php echo $estado; ?>" 
                                <?php echo (($_GET['estado'] ?? '') == $estado) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($estado); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">🔍 Buscar</button>
                    <a href="admin_facturas.php" class="btn" style="background: #95a5a6; color: white;">Limpiar</a>
                </form>
            </div>

            <?php
            // Construir consulta con filtros
            $where = [];
            $params = [];
            $types = '';

            if (!empty($_GET['buscar'])) {
                $where[] = "numero_factura LIKE ?";
                $params[] = '%' . $_GET['buscar'] . '%';
                $types .= 's';
            }

            if (!empty($_GET['estado'])) {
                $where[] = "estado = ?";
                $params[] = $_GET['estado'];
                $types .= 's';
            }

            $sql = "SELECT * FROM facturas";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY fecha DESC";

            if (!empty($params)) {
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                } else {
                    echo '<div class="error">Error al preparar la consulta: ' . $conn->error . '</div>';
                    $resultado = false;
                }
            } else {
                $resultado = $conn->query($sql);
            }

            if (!$resultado) {
                echo '<div class="error">Error en la consulta: ' . $conn->error . '</div>';
            }
            ?>

            <table>
                <thead>
                    <tr>
                        <th>N° Factura</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Método Pago</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($resultado && $resultado->num_rows > 0) {
                        while($factura = $resultado->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($factura['numero_factura']); ?></td>
                        <td><?php echo htmlspecialchars($factura['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($factura['nombre_cliente'] ?? 'Cliente'); ?></td>
                        <td>$<?php echo number_format($factura['total'], 2); ?> CUP</td>
                        <td><?php echo htmlspecialchars($factura['metodo_pago']); ?></td>
                        <td>
                            <span class="estado-<?php echo $factura['estado']; ?>">
                                <?php 
                                $estados_texto = [
                                    'pendiente' => '⏳ Pendiente',
                                    'confirmado' => '✅ Confirmado', 
                                    'procesando' => '🚚 Procesando',
                                    'completado' => '🎉 Completado'
                                ];
                                echo $estados_texto[$factura['estado']] ?? $factura['estado'];
                                ?>
                            </span>
                        </td>
                        <td>
                            <a href="editar_factura.php?id=<?php echo $factura['id']; ?>" class="btn btn-editar">
                                ✏️ Editar
                            </a>
                            <a href="ver_factura.php?id=<?php echo $factura['id']; ?>" class="btn btn-ver" target="_blank">
                                👁️ Ver
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        echo '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #7f8c8d;">No se encontraron facturas.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>