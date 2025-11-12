<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    header('Location: ../login.php');
    exit();
}
include("../db/conexion.php");

// Verificar si la conexión se estableció correctamente
if (!isset($conn) || $conn->connect_error) {
    die("❌ Error: No se pudo conectar a la base de datos. Error: " . $conn->connect_error);
}

// Obtener ID de la factura
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: admin_facturas.php');
    exit();
}

// Obtener datos de la factura
$sql = "SELECT * FROM facturas WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$factura = $result->fetch_assoc();

if (!$factura) {
    header('Location: admin_facturas.php');
    exit();
}

// Obtener productos de la factura (asumiendo que tienes una tabla factura_productos)
$productos_sql = "SELECT * FROM factura_productos WHERE factura_id = ?";
$stmt = $conn->prepare($productos_sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$productos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Factura - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-card { background: #ecf0f1; padding: 15px; border-radius: 5px; }
        .productos-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .productos-table th, .productos-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .productos-table th { background: #34495e; color: white; }
        .btn { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-volver { background: #95a5a6; color: white; }
        .btn-imprimir { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👁️ Detalle de Factura</h1>
            <p>Información completa del pedido</p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h3>Información de la Factura</h3>
                <p><strong>Número:</strong> <?php echo $factura['numero_factura']; ?></p>
                <p><strong>Fecha:</strong> <?php echo $factura['fecha']; ?></p>
                <p><strong>Estado:</strong> 
                    <span style="padding: 5px 10px; border-radius: 5px; 
                        <?php 
                        $estilos = [
                            'pendiente' => 'background: #fff3cd; color: #856404;',
                            'confirmado' => 'background: #d1ecf1; color: #0c5460;',
                            'procesando' => 'background: #d4edda; color: #155724;',
                            'completado' => 'background: #e2e3e5; color: #383d41;'
                        ];
                        echo $estilos[$factura['estado']];
                        ?>">
                        <?php echo ucfirst($factura['estado']); ?>
                    </span>
                </p>
                <p><strong>Total:</strong> $<?php echo number_format($factura['total'], 2); ?> CUP</p>
            </div>

            <div class="info-card">
                <h3>Información del Cliente</h3>
                <p><strong>Nombre:</strong> <?php echo $factura['nombre_cliente'] ?? 'No especificado'; ?></p>
                <p><strong>Email:</strong> <?php echo $factura['email_cliente'] ?? 'No especificado'; ?></p>
                <p><strong>Teléfono:</strong> <?php echo $factura['telefono_cliente'] ?? 'No especificado'; ?></p>
                <p><strong>Método Pago:</strong> <?php echo $factura['metodo_pago']; ?></p>
            </div>
        </div>

        <?php if(isset($factura['observaciones']) && !empty($factura['observaciones'])): ?>
            <div class="info-card">
                <h3>Observaciones</h3>
                <p><?php echo nl2br(htmlspecialchars($factura['observaciones'])); ?></p>
            </div>
        <?php endif; ?>

        <h3>Productos del Pedido</h3>
        <table class="productos-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_general = 0;
                if ($productos && $productos->num_rows > 0) {
                    while($producto = $productos->fetch_assoc()): 
                        $subtotal = $producto['cantidad'] * $producto['precio'];
                        $total_general += $subtotal;
                ?>
                <tr>
                    <td><?php echo $producto['nombre_producto']; ?></td>
                    <td><?php echo $producto['cantidad']; ?></td>
                    <td>$<?php echo number_format($producto['precio'], 2); ?> CUP</td>
                    <td>$<?php echo number_format($subtotal, 2); ?> CUP</td>
                </tr>
                <?php 
                    endwhile;
                } else {
                    echo '<tr><td colspan="4" style="text-align: center;">No se encontraron productos</td></tr>';
                }
                ?>
                <tr style="font-weight: bold;">
                    <td colspan="3" style="text-align: right;">Total General:</td>
                    <td>$<?php echo number_format($total_general, 2); ?> CUP</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <a href="admin_facturas.php" class="btn btn-volver">← Volver al Listado</a>
            <a href="editar_factura.php?id=<?php echo $factura['id']; ?>" class="btn" style="background: #e67e22; color: white;">✏️ Editar Factura</a>
            <button onclick="window.print()" class="btn btn-imprimir">🖨️ Imprimir</button>
        </div>
    </div>
</body>
</html>