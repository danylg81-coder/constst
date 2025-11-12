<?php
session_start();
include("db/conexion.php");

$numero_factura = $_GET['factura'] ?? $_SESSION['ultima_factura']['numero_factura'] ?? '';

if (empty($numero_factura)) {
    header('Location: carrito.php');
    exit();
}

// Buscar factura en la base de datos
$sql = "SELECT * FROM facturas WHERE numero_factura = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $numero_factura);
$stmt->execute();
$result = $stmt->get_result();
$factura = $result->fetch_assoc();
$stmt->close();

if (!$factura) {
    // Si no existe en BD, usar sesión
    $factura = $_SESSION['ultima_factura'] ?? null;
}

if (!$factura) {
    die('Factura no encontrada.');
}

// Mapear estados a clases CSS y textos
$estados = [
    'pendiente' => ['class' => 'estado-pendiente', 'texto' => '⏳ Pendiente de Pago', 'descripcion' => 'Esperando confirmación de pago'],
    'verificando' => ['class' => 'estado-verificando', 'texto' => '🔍 Verificando Pago', 'descripcion' => 'Estamos confirmando tu transferencia'],
    'confirmado' => ['class' => 'estado-confirmado', 'texto' => '✅ Pago Confirmado', 'descripcion' => 'Tu pago ha sido verificado'],
    'procesando' => ['class' => 'estado-procesando', 'texto' => '🚚 Procesando Pedido', 'descripcion' => 'Preparando tu pedido para envío'],
    'completado' => ['class' => 'estado-completado', 'texto' => '🎉 Pedido Completado', 'descripcion' => 'Tu pedido ha sido entregado'],
    'rechazado' => ['class' => 'estado-rechazado', 'texto' => '❌ Pago Rechazado', 'descripcion' => 'Hubo un problema con tu pago']
];

$estado_actual = $estados[$factura['estado'] ?? 'pendiente'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguimiento de Factura - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0B3A66, #1E6BC4); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        .estado-tracker { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 30px; }
        
        .estado-pendiente { background: #fff3cd; border-left: 5px solid #ffc107; }
        .estado-verificando { background: #cce7ff; border-left: 5px solid #007bff; }
        .estado-confirmado { background: #d4edda; border-left: 5px solid #28a745; }
        .estado-procesando { background: #e2e3e5; border-left: 5px solid #6c757d; }
        .estado-completado { background: #d1ecf1; border-left: 5px solid #17a2b8; }
        .estado-rechazado { background: #f8d7da; border-left: 5px solid #dc3545; }
        
        .timeline { position: relative; margin: 30px 0; }
        .timeline::before { content: ''; position: absolute; left: 20px; top: 0; bottom: 0; width: 2px; background: #e0e0e0; }
        .timeline-item { position: relative; margin-bottom: 20px; padding-left: 50px; }
        .timeline-dot { position: absolute; left: 12px; top: 0; width: 20px; height: 20px; border-radius: 50%; background: #e0e0e0; }
        .timeline-dot.activo { background: #28a745; }
        .timeline-dot.completado { background: #6c757d; }
        
        .info-box { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .btn-descargar { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 12px 25px; border: none; border-radius: 25px; text-decoration: none; display: inline-block; margin: 10px 0; }
        .actualizar-btn { background: #0B3A66; color: white; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Seguimiento de tu Factura</h1>
            <p>Número: <strong><?php echo htmlspecialchars($factura['numero_factura']); ?></strong></p>
        </div>
        
        <div class="estado-tracker <?php echo $estado_actual['class']; ?>">
            <h2>Estado Actual</h2>
            <h3><?php echo $estado_actual['texto']; ?></h3>
            <p><?php echo $estado_actual['descripcion']; ?></p>
            
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot <?php echo in_array($factura['estado'], ['pendiente', 'verificando', 'confirmado', 'procesando', 'completado']) ? 'completado' : ''; ?>"></div>
                    <div>
                        <strong>Pendiente de Pago</strong>
                        <p>Esperando transferencia bancaria</p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-dot <?php echo in_array($factura['estado'], ['verificando', 'confirmado', 'procesando', 'completado']) ? 'completado' : ''; ?>"></div>
                    <div>
                        <strong>Verificando Pago</strong>
                        <p>Confirmando recepción de transferencia</p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-dot <?php echo in_array($factura['estado'], ['confirmado', 'procesando', 'completado']) ? 'completado' : ''; ?>"></div>
                    <div>
                        <strong>Pago Confirmado</strong>
                        <p>Transferencia verificada correctamente</p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-dot <?php echo in_array($factura['estado'], ['procesando', 'completado']) ? 'completado' : ''; ?>"></div>
                    <div>
                        <strong>Procesando Pedido</strong>
                        <p>Preparando tu pedido para envío</p>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-dot <?php echo $factura['estado'] === 'completado' ? 'completado' : ''; ?>"></div>
                    <div>
                        <strong>Pedido Completado</strong>
                        <p>¡Tu pedido ha sido entregado!</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-box">
            <h3>📋 Información de la Factura</h3>
            <p><strong>Número:</strong> <?php echo htmlspecialchars($factura['numero_factura']); ?></p>
            <p><strong>Fecha:</strong> <?php echo $factura['fecha'] ?? $factura['fecha_creacion']; ?></p>
            <p><strong>Total:</strong> $<?php echo number_format($factura['total'], 2); ?> CUP</p>
            <p><strong>Método de Pago:</strong> <?php echo $factura['metodo_pago']; ?></p>
        </div>
        
        <div style="text-align: center;">
            <a href="generar_factura.php" class="btn-descargar">📄 Descargar Factura PDF</a>
            <br>
            <button class="actualizar-btn" onclick="location.reload()">🔄 Actualizar Estado</button>
            <br>
            <a href="index.php" style="color: #0B3A66; text-decoration: none; margin-top: 15px; display: inline-block;">← Volver al Inicio</a>
        </div>
        
        <div class="info-box">
            <h3>📞 ¿Necesitas Ayuda?</h3>
            <p>Si tienes alguna pregunta sobre tu pedido, contáctanos:</p>
            <p><strong>Teléfono:</strong> 51435405</p>
            <p><strong>Email:</strong> contacto@madreaguast.cu</p>
            <p><strong>Horario:</strong> Lunes a Viernes 8:00 AM - 5:00 PM</p>
        </div>
    </div>
    
    <script>
        // Actualizar automáticamente cada 30 segundos
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>