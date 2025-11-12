<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include("../db/conexion.php");

// Primero, vamos a crear las tablas si no existen
$crear_tablas = false;

// Verificar si la tabla pedidos existe
$check_pedidos = $conn->query("SHOW TABLES LIKE 'pedidos'");
if ($check_pedidos->num_rows == 0) {
    $crear_tablas = true;
    
    // Crear tabla pedidos
    $conn->query("CREATE TABLE pedidos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        estado ENUM('pendiente', 'procesando', 'enviado', 'completado', 'cancelado') DEFAULT 'pendiente',
        direccion_entrega TEXT,
        notas TEXT,
        fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    )");
    
    // Crear tabla detalles_pedido
    $conn->query("CREATE TABLE detalles_pedido (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT NOT NULL,
        producto_id INT NOT NULL,
        cantidad INT NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
    )");
}

// Procesar cambio de estado de pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $nuevo_estado = $conn->real_escape_string($_POST['nuevo_estado']);
    
    $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $pedido_id);
    
    if ($stmt->execute()) {
        $mensaje = "Estado del pedido actualizado correctamente";
    } else {
        $error = "Error al actualizar el estado: " . $stmt->error;
    }
    $stmt->close();
}

// Procesar eliminación de pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_pedido'])) {
    $pedido_id = intval($_POST['pedido_id']);
    
    // Primero eliminamos los detalles del pedido
    $stmt = $conn->prepare("DELETE FROM detalles_pedido WHERE pedido_id = ?");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $stmt->close();
    
    // Luego eliminamos el pedido
    $stmt = $conn->prepare("DELETE FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $pedido_id);
    
    if ($stmt->execute()) {
        $mensaje = "Pedido eliminado correctamente";
    } else {
        $error = "Error al eliminar el pedido: " . $stmt->error;
    }
    $stmt->close();
}

// Verificar la estructura de la tabla pedidos
$column_check = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'usuario_id'");
if ($column_check->num_rows == 0) {
    // La columna usuario_id no existe, usar una consulta alternativa
    $query = "
        SELECT p.*, 'Cliente Demo' as cliente_nombre, 'cliente@demo.com' as cliente_email, '000-0000' as cliente_telefono
        FROM pedidos p 
        ORDER BY p.fecha_pedido DESC
    ";
} else {
    // La columna usuario_id existe, usar la consulta normal
    $query = "
        SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono
        FROM pedidos p 
        INNER JOIN usuarios u ON p.usuario_id = u.id 
        ORDER BY p.fecha_pedido DESC
    ";
}

$pedidos = $conn->query($query);

// Obtener estadísticas
$stats_query = "
    SELECT 
        COUNT(*) as total_pedidos,
        COALESCE(SUM(total), 0) as ingresos_totales,
        COALESCE(AVG(total), 0) as promedio_pedido,
        COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
        COUNT(CASE WHEN estado = 'procesando' THEN 1 END) as procesando,
        COUNT(CASE WHEN estado = 'enviado' THEN 1 END) as enviados,
        COUNT(CASE WHEN estado = 'completado' THEN 1 END) as completados,
        COUNT(CASE WHEN estado = 'cancelado' THEN 1 END) as cancelados
    FROM pedidos
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Madre Agua ST</title>
    <link rel="stylesheet" href="styles/panel.css">
    <style>
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

        .sidebar-menu a.active {
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
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
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            margin: 2px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219a52;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #d68910;
        }

        .estado-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .estado-procesando {
            background: #cce7ff;
            color: #004085;
        }

        .estado-enviado {
            background: #d1ecf1;
            color: #0c5460;
        }

        .estado-completado {
            background: #d4edda;
            color: #155724;
        }

        .estado-cancelado {
            background: #f8d7da;
            color: #721c24;
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

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .detalles-pedido {
            margin-top: 15px;
        }

        .detalles-pedido table {
            width: 100%;
            border-collapse: collapse;
        }

        .detalles-pedido th,
        .detalles-pedido td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .filtros {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .filtro-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .no-pedidos {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .no-pedidos i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
    </style>
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
                <h1>Gestión de Pedidos</h1>
                <p>Administra y realiza seguimiento de los pedidos de los clientes</p>
            </div>

            <!-- Mensajes de alerta -->
            <?php if (isset($mensaje)): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($crear_tablas): ?>
                <div class="alert alert-info">
                    ✅ Se han creado las tablas de pedidos correctamente. Ahora puedes gestionar los pedidos.
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_pedidos']; ?></div>
                    <div class="stat-label">Total Pedidos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['ingresos_totales'], 2); ?></div>
                    <div class="stat-label">Ingresos Totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['promedio_pedido'], 2); ?></div>
                    <div class="stat-label">Promedio por Pedido</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pendientes']; ?></div>
                    <div class="stat-label">Pedidos Pendientes</div>
                </div>
            </div>

            <!-- Lista de pedidos -->
            <div class="card">
                <div class="card-header">
                    Lista de Pedidos
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="filtros">
                        <div class="filtro-item">
                            <label>Filtrar por estado:</label>
                            <select id="filtro-estado" class="form-control" style="width: auto;">
                                <option value="">Todos los estados</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="procesando">Procesando</option>
                                <option value="enviado">Enviado</option>
                                <option value="completado">Completado</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <?php if ($pedidos && $pedidos->num_rows > 0): ?>
                        <table class="table" id="tabla-pedidos">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($pedido = $pedidos->fetch_assoc()): ?>
                                <tr data-estado="<?php echo $pedido['estado']; ?>">
                                    <td>#<?php echo $pedido['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($pedido['cliente_email']); ?></small><br>
                                        <small><?php echo htmlspecialchars($pedido['cliente_telefono']); ?></small>
                                    </td>
                                    <td>$<?php echo number_format($pedido['total'], 2); ?> CUP</td>
                                    <td>
                                        <span class="estado-badge estado-<?php echo $pedido['estado']; ?>">
                                            <?php echo ucfirst($pedido['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                    <td>
                                        <button class="btn btn-primary" onclick="verDetalles(<?php echo $pedido['id']; ?>)">
                                            Ver Detalles
                                        </button>
                                        <button class="btn btn-warning" onclick="cambiarEstado(<?php echo $pedido['id']; ?>, '<?php echo $pedido['estado']; ?>')">
                                            Cambiar Estado
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este pedido?');">
                                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                            <button type="submit" name="eliminar_pedido" class="btn btn-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-pedidos">
                            <i>📦</i>
                            <h3>No hay pedidos registrados</h3>
                            <p>Los pedidos de los clientes aparecerán aquí cuando realicen compras en la tienda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para detalles del pedido -->
    <div id="modal-detalles" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Detalles del Pedido</h2>
            <div id="detalles-contenido">
                <!-- Los detalles se cargarán aquí mediante JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal para cambiar estado -->
    <div id="modal-estado" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Cambiar Estado del Pedido</h2>
            <form method="POST" id="form-estado">
                <input type="hidden" name="pedido_id" id="pedido-id-estado">
                <div class="form-group">
                    <label for="nuevo_estado">Nuevo Estado:</label>
                    <select class="form-control" id="nuevo_estado" name="nuevo_estado" required>
                        <option value="pendiente">Pendiente</option>
                        <option value="procesando">Procesando</option>
                        <option value="enviado">Enviado</option>
                        <option value="completado">Completado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <button type="submit" name="cambiar_estado" class="btn btn-primary">Actualizar Estado</button>
            </form>
        </div>
    </div>

    <script>
    // Filtro por estado
    document.getElementById('filtro-estado').addEventListener('change', function() {
        const estado = this.value;
        const filas = document.querySelectorAll('#tabla-pedidos tbody tr');
        
        filas.forEach(fila => {
            if (estado === '' || fila.getAttribute('data-estado') === estado) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    });

    // Funciones para modales
    async function verDetalles(pedidoId) {
        try {
            document.getElementById('detalles-contenido').innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Cargando detalles del pedido...</p>
                </div>
            `;
            
            document.getElementById('modal-detalles').style.display = 'block';

            const response = await fetch(`ajax_pedidos.php?accion=obtener_detalles&pedido_id=${pedidoId}`);
            const data = await response.json();

            if (data.error) {
                document.getElementById('detalles-contenido').innerHTML = `
                    <div class="alert alert-error">
                        Error: ${data.error}
                    </div>
                `;
                return;
            }

            const pedido = data.pedido;
            const detalles = data.detalles;

            // Construir tabla de productos
            let productosHTML = '';
            if (detalles && detalles.length > 0) {
                detalles.forEach(detalle => {
                    const subtotal = detalle.cantidad * detalle.precio;
                    productosHTML += `
                        <tr>
                            <td>
                                <strong>${detalle.producto_nombre}</strong>
                                ${detalle.producto_imagen ? `<br><img src="../img/productos/${detalle.producto_imagen}" alt="${detalle.producto_nombre}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">` : ''}
                            </td>
                            <td>${detalle.cantidad}</td>
                            <td>$${parseFloat(detalle.precio).toFixed(2)} CUP</td>
                            <td>$${subtotal.toFixed(2)} CUP</td>
                        </tr>
                    `;
                });
            } else {
                productosHTML = '<tr><td colspan="4" style="text-align: center;">No se encontraron productos</td></tr>';
            }

            // Construir HTML completo
           const html = `
    <div class="detalles-pedido">
        <div class="info-pedido">
            <h3>Información del Pedido</h3>
            <p><strong>ID del Pedido:</strong> #${pedido.id}</p>
            <p><strong>Fecha:</strong> ${new Date(pedido.fecha_pedido).toLocaleString()}</p>
            <p><strong>Estado:</strong> <span class="estado-badge estado-${pedido.estado}">${pedido.estado.charAt(0).toUpperCase() + pedido.estado.slice(1)}</span></p>
            <p><strong>Método de Pago:</strong> ${pedido.metodo_pago}</p>
            <p><strong>Total:</strong> $${parseFloat(pedido.total).toFixed(2)} CUP</p>
        </div>
                    <div class="info-cliente" style="margin-top: 20px;">
                        <h3>Información del Cliente</h3>
                        <p><strong>Nombre:</strong> ${pedido.cliente_nombre}</p>
                        <p><strong>Email:</strong> ${pedido.cliente_email}</p>
                        <p><strong>Teléfono:</strong> ${pedido.cliente_telefono || 'No proporcionado'}</p>
                    </div>

                    ${pedido.direccion_entrega ? `
                    <div class="info-direccion" style="margin-top: 20px;">
                        <h3>Dirección de Entrega</h3>
                        <p>${pedido.direccion_entrega}</p>
                    </div>
                    ` : ''}

                    ${pedido.notas ? `
                    <div class="info-notas" style="margin-top: 20px;">
                        <h3>Notas del Pedido</h3>
                        <p>${pedido.notas}</p>
                    </div>
                    ` : ''}

                    <div class="productos-pedido" style="margin-top: 20px;">
                        <h3>Productos del Pedido</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${productosHTML}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                                    <td style="font-weight: bold;">$${parseFloat(pedido.total).toFixed(2)} CUP</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            `;

            document.getElementById('detalles-contenido').innerHTML = html;

        } catch (error) {
            console.error('Error:', error);
            document.getElementById('detalles-contenido').innerHTML = `
                <div class="alert alert-error">
                    Error al cargar los detalles del pedido: ${error.message}
                </div>
            `;
        }
    }

    function cambiarEstado(pedidoId, estadoActual) {
        document.getElementById('pedido-id-estado').value = pedidoId;
        document.getElementById('nuevo_estado').value = estadoActual;
        document.getElementById('modal-estado').style.display = 'block';
    }

    function cerrarModal() {
        document.getElementById('modal-detalles').style.display = 'none';
        document.getElementById('modal-estado').style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    // Agregar estilos para el spinner (si no existen)
    if (!document.querySelector('#spinner-styles')) {
        const spinnerStyles = document.createElement('style');
        spinnerStyles.id = 'spinner-styles';
        spinnerStyles.innerHTML = `
            .spinner-border {
                display: inline-block;
                width: 2rem;
                height: 2rem;
                border: 0.25em solid currentColor;
                border-right-color: transparent;
                border-radius: 50%;
                animation: spinner-border .75s linear infinite;
            }
            @keyframes spinner-border {
                to { transform: rotate(360deg); }
            }
            .visually-hidden {
                position: absolute !important;
                width: 1px !important;
                height: 1px !important;
                padding: 0 !important;
                margin: -1px !important;
                overflow: hidden !important;
                clip: rect(0,0,0,0) !important;
                white-space: nowrap !important;
                border: 0 !important;
            }
        `;
        document.head.appendChild(spinnerStyles);
    }
</script>
</body>
</html>