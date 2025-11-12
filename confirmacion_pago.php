<?php
session_start();
require_once "db/conexion.php";

// Verificar si la conexión se estableció correctamente
if (!isset($conn) || $conn->connect_error) {
    die("❌ Error: No se pudo conectar a la base de datos. Error: " . $conn->connect_error);
}

// Verificar si hay una factura en sesión
if (!isset($_SESSION['ultima_factura'])) {
    header('Location: carrito.php');
    exit();
}

// Obtener la factura de la sesión
$factura = $_SESSION['ultima_factura'];

// Actualizar el estado desde la base de datos
$numero_factura = $factura['numero_factura'];

// Usar $conn para la consulta
$query = "SELECT estado FROM facturas WHERE numero_factura = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $numero_factura);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $factura['estado'] = $row['estado'];
    // Actualizar la sesión
    $_SESSION['ultima_factura'] = $factura;
}

// Si se solicita generar el PDF
if (isset($_GET['generar_pdf'])) {
    // Redirigir al generador de PDF
    header('Location: generar_factura.php');
    exit();
}

// Estados posibles
$estados = [
    'pendiente' => [
        'class' => 'estado-pendiente',
        'texto' => '⏳ Pendiente de Pago',
        'descripcion' => 'Esperando confirmación de transferencia bancaria',
        'icono' => '⏳'
    ],
    'confirmado' => [
        'class' => 'estado-confirmado',
        'texto' => '✅ Pago Confirmado',
        'descripcion' => 'Tu pago ha sido verificado correctamente',
        'icono' => '✅'
    ],
    'procesando' => [
        'class' => 'estado-procesando',
        'texto' => '🚚 Procesando Pedido',
        'descripcion' => 'Preparando tu pedido para envío',
        'icono' => '🚚'
    ],
    'completado' => [
        'class' => 'estado-completado',
        'texto' => '🎉 Pedido Completado',
        'descripcion' => 'Tu pedido ha sido entregado',
        'icono' => '🎉'
    ]
];

$estado_actual = $factura['estado'] ?? 'pendiente';
// Si el estado actual no está definido en el array de estados, usamos 'pendiente'
if (!array_key_exists($estado_actual, $estados)) {
    $estado_actual = 'pendiente';
}
$estado_info = $estados[$estado_actual];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de Pago - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/confirmacion_pago.css">
    
</head>
<body>
    <div class="header-confirmacion">
        <div class="header-content">
            <h1>✅ Confirmación de Pago</h1>
            <p>Tu pedido ha sido procesado exitosamente</p>
            <a href="tienda.php" class="volver-tienda">
                ← Volver a la tienda
            </a>
        </div>
    </div>

    <div class="container">
        <div class="grid-confirmacion">
            <!-- Columna izquierda: Estado y seguimiento -->
            <div>
                <div class="card <?php echo $estado_info['class']; ?>">
                    <div class="estado-header">
                        <div class="estado-icono">
                            <?php echo $estado_info['icono']; ?>
                        </div>
                        <div class="estado-texto">
                            <h3><?php echo $estado_info['texto']; ?></h3>
                            <p class="estado-descripcion"><?php echo $estado_info['descripcion']; ?></p>
                        </div>
                    </div>

                    <div class="numero-factura">
                        Factura: <?php echo $factura['numero_factura']; ?>
                    </div>

                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot <?php echo in_array($estado_actual, ['pendiente', 'confirmado', 'procesando', 'completado']) ? 'completado' : ''; ?> <?php echo $estado_actual == 'pendiente' ? 'activo' : ''; ?>"></div>
                            <div class="timeline-content">
                                <h4>Pendiente de Pago</h4>
                                <p>Esperando transferencia bancaria</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-dot <?php echo in_array($estado_actual, ['confirmado', 'procesando', 'completado']) ? 'completado' : ''; ?> <?php echo $estado_actual == 'confirmado' ? 'activo' : ''; ?>"></div>
                            <div class="timeline-content">
                                <h4>Pago Confirmado</h4>
                                <p>Confirmada la recepción de transferencia</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-dot <?php echo in_array($estado_actual, ['procesando', 'completado']) ? 'completado' : ''; ?> <?php echo $estado_actual == 'procesando' ? 'activo' : ''; ?>"></div>
                            <div class="timeline-content">
                                <h4>Procesando Pedido</h4>
                                <p>Preparando tu pedido</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-dot <?php echo $estado_actual == 'completado' ? 'completado' : ''; ?> <?php echo $estado_actual == 'completado' ? 'activo' : ''; ?>"></div>
                            <div class="timeline-content">
                                <h4>Pedido Completado</h4>
                                <p>¡Tu pedido ha sido entregado!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contacto-info">
                    <h3>📞 ¿Necesitas Ayuda?</h3>
                    <p>Si tienes alguna pregunta sobre tu pedido, contáctanos:</p>
                    <p><strong>Teléfono:</strong> 51435405</p>
                    <p><strong>Email:</strong> contacto@madreaguast.cu</p>
                    <p><strong>Horario:</strong> Lunes a Viernes 8:00 AM - 5:00 PM</p>
                </div>
            </div>

            <!-- Columna derecha: Resumen del pedido -->
            <div>
                <div class="card">
                    <h2>📋 Resumen de tu Pedido</h2>
                    
                    <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                        <?php foreach ($factura['productos'] as $producto): ?>
                            <div class="producto-item">
                                <img src="img/productos/<?php echo $producto['imagen']; ?>" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                     class="producto-imagen">
                                
                                <div class="producto-info">
                                    <div class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                    <div class="producto-detalles">
                                        <span>Cantidad: <?php echo $producto['cantidad']; ?></span>
                                        <span>$<?php echo number_format($producto['subtotal'], 2); ?> CUP</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="detalle-total">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($factura['total'], 2); ?> CUP</span>
                    </div>
                    
                    <div class="detalle-total">
                        <span>Envío:</span>
                        <span>Gratis</span>
                    </div>
                    
                    <div class="detalle-total total-final">
                        <span>Total Pagado:</span>
                        <span>$<?php echo number_format($factura['total'], 2); ?> CUP</span>
                    </div>

                    <div class="detalle-total">
                        <span>Método de Pago:</span>
                        <span><?php echo $factura['metodo_pago']; ?></span>
                    </div>

                    <div class="detalle-total">
                        <span>Fecha:</span>
                        <span><?php echo $factura['fecha']; ?></span>
                    </div>
                </div>

                <div class="info-importante">
                    <h3>⚠️ Información Importante</h3>
                    <p><strong>Guarda tu número de factura:</strong> <?php echo $factura['numero_factura']; ?></p>
                    <p>Puedes consultar el estado de tu pedido en cualquier momento volviendo a esta página.</p>
                    <p>Recibirás una confirmación por email cuando tu pago sea verificado.</p>
                </div>

                <div class="acciones">
                    <a href="generar_factura.php" class="btn btn-descargar" target="_blank">
                        📄 Descargar Factura PDF
                    </a>
                    <a href="index.php" class="btn btn-volver">
                        🏠 Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Actualizar automáticamente cada 30 segundos para ver cambios de estado
        setInterval(function() {
            location.reload();
        }, 30000);

        // Mostrar mensaje de actualización
        console.log('La página se actualizará automáticamente cada 30 segundos para mostrar el estado más reciente.');
    </script>
</body>
</html>