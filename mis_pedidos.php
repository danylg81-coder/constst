<?php
session_start();
require_once "db/conexion.php";

// Redirigir si no está logueado
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener pedidos del usuario
$sql = "SELECT * FROM facturas WHERE email_cliente = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $_SESSION['usuario_email']);
$stmt->execute();
$result = $stmt->get_result();
$pedidos = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/mis_pedidos.css">
    
</head>
<body>
    <!-- Menú de usuario desplegable -->
    <div class="user-menu-container" style="position: absolute; top: 20px; right: 20px; z-index: 1000;">
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
            <h1>📦 Mis Pedidos</h1>
            <p>Revisa el historial y estado de tus pedidos</p>
        </div>
        
        <?php if (count($pedidos) > 0): ?>
            <?php foreach ($pedidos as $pedido): ?>
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div class="pedido-numero">Factura: <?php echo $pedido['numero_factura']; ?></div>
                        <div class="pedido-fecha"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></div>
                    </div>
                    
                    <div class="pedido-info">
                        <div class="info-item">
                            <span class="info-label">Total</span>
                            <span class="info-value">$<?php echo number_format($pedido['total'], 2); ?> CUP</span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Método de Pago</span>
                            <span class="info-value"><?php echo $pedido['metodo_pago']; ?></span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Estado</span>
                            <span class="estado estado-<?php echo $pedido['estado']; ?>">
                                <?php 
                                $estados = [
                                    'pendiente' => '⏳ Pendiente',
                                    'confirmado' => '✅ Confirmado',
                                    'procesando' => '🚚 Procesando',
                                    'completado' => '🎉 Completado'
                                ];
                                echo $estados[$pedido['estado']] ?? $pedido['estado'];
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="text-align: right;">
                        <a href="ver_factura.php?id=<?php echo $pedido['id']; ?>" class="btn" target="_blank">
                            👁️ Ver Detalles
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📦</div>
                <h3>No tienes pedidos aún</h3>
                <p>Cuando realices tu primer pedido, aparecerá aquí.</p>
                <a href="tienda.php" class="volver-inicio">🛒 Ir a la tienda</a>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="volver-inicio">← Volver al inicio</a>
        </div>
    </div>

    
    <script src="scripts/mis_pedidos.js" defer></script>
</body>
</html>