<?php
session_start();
include("db/conexion.php");

// Verificar si hay productos en el carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header('Location: carrito.php');
    exit();
}

// Obtener información del carrito
$productos_carrito = [];
$total = 0;

if (count($_SESSION['carrito']) > 0) {
    $ids_en_carrito = array_column($_SESSION['carrito'], 'id');
    $ids_str = implode(',', $ids_en_carrito);
    $sql = "SELECT * FROM productos WHERE id IN ($ids_str)";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Encontrar la cantidad en el carrito
            $cantidad = 0;
            foreach ($_SESSION['carrito'] as $item) {
                if ($item['id'] == $row['id']) {
                    $cantidad = $item['cantidad'];
                    break;
                }
            }
            
            $subtotal = $row['precio'] * $cantidad;
            $total += $subtotal;
            
            $productos_carrito[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'descripcion' => $row['descripcion'],
                'precio' => $row['precio'],
                'imagen' => $row['imagen'],
                'cantidad' => $cantidad,
                'subtotal' => $subtotal
            ];
        }
    }
}

/// Procesar el pago y guardar en base de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pago'])) {
    // Generar número de factura único (opcional)
    $numero_factura = 'MA-EF-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Procesar el pago y guardar en base de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pago'])) {
    // Generar número de factura único
    $numero_factura = 'MA-EF-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // 1. Insertar PEDIDO en la tabla pedidos (sin datos de cliente)
    $sql_pedido = "INSERT INTO pedidos (usuario_id, total, estado, direccion_entrega, notas, metodo_pago) 
                   VALUES (?, ?, 'pendiente', ?, ?, ?)";
    
    $stmt_pedido = $conn->prepare($sql_pedido);
    
    // Datos del pedido
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $metodo_pago = 'Efectivo';
    $direccion_entrega = ''; 
    $notas = '';

    $stmt_pedido->bind_param('idsss', 
        $usuario_id, 
        $total, 
        $direccion_entrega, 
        $notas, 
        $metodo_pago
    );
    
    if ($stmt_pedido->execute()) {
        $pedido_id = $conn->insert_id;
        
        // 2. Insertar FACTURA en la tabla facturas
        $sql_factura = "INSERT INTO facturas (numero_factura, pedido_id, nombre_cliente, email_cliente, telefono_cliente, total, metodo_pago, estado) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')";
        
        $stmt_factura = $conn->prepare($sql_factura);
        
        // Datos del cliente (del formulario)
        $nombre_cliente = trim($_POST['nombre']);
        $email_cliente = trim($_POST['email']);
        $telefono_cliente = trim($_POST['telefono']);

        $stmt_factura->bind_param('sisssds', 
            $numero_factura,
            $pedido_id,
            $nombre_cliente, 
            $email_cliente, 
            $telefono_cliente, 
            $total, 
            $metodo_pago
        );
        
        if ($stmt_factura->execute()) {
            $factura_id = $conn->insert_id;
            
            // 3. Insertar productos en detalles_pedido
            $sql_detalles = "INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio, subtotal) 
                             VALUES (?, ?, ?, ?, ?)";
            $stmt_detalles = $conn->prepare($sql_detalles);
            
            foreach ($productos_carrito as $producto) {
                $stmt_detalles->bind_param('iiidd', 
                    $pedido_id,
                    $producto['id'],
                    $producto['cantidad'],
                    $producto['precio'],
                    $producto['subtotal']
                );
                $stmt_detalles->execute();
            }
            
            // 4. Insertar productos en factura_productos (para mantener historial)
            $sql_factura_productos = "INSERT INTO factura_productos (factura_id, nombre_producto, cantidad, precio, subtotal) 
                                     VALUES (?, ?, ?, ?, ?)";
            $stmt_factura_productos = $conn->prepare($sql_factura_productos);
            
            foreach ($productos_carrito as $producto) {
                $stmt_factura_productos->bind_param('isidd', 
                    $factura_id,
                    $producto['nombre'],
                    $producto['cantidad'],
                    $producto['precio'],
                    $producto['subtotal']
                );
                $stmt_factura_productos->execute();
            }
            // 5. Insertar en la tabla ventas para estadísticas
$sql_venta = "INSERT INTO ventas (usuario_id, total, metodo_pago, estado) 
              VALUES (?, ?, ?, 'completado')";
$stmt_venta = $conn->prepare($sql_venta);
$stmt_venta->bind_param('ids', $usuario_id, $total, $metodo_pago);
if ($stmt_venta->execute()) {
    $venta_id = $conn->insert_id;

    // 6. Insertar en detalle_venta
    $sql_detalle_venta = "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio) 
                          VALUES (?, ?, ?, ?)";
    $stmt_detalle_venta = $conn->prepare($sql_detalle_venta);

    foreach ($productos_carrito as $producto) {
        $stmt_detalle_venta->bind_param('iiid', 
            $venta_id,
            $producto['id'],
            $producto['cantidad'],
            $producto['precio']
        );
        $stmt_detalle_venta->execute();
    }
} else {
    // Manejar error, pero no interrumpir el flujo principal ya que la venta principal ya se guardó
    error_log("Error al guardar en ventas: " . $conn->error);
}
            // Guardar información en sesión
            $_SESSION['ultima_factura'] = [
                'numero_factura' => $numero_factura,
                'pedido_id' => $pedido_id,
                'fecha' => date('d/m/Y H:i:s'),
                'productos' => $productos_carrito,
                'total' => $total,
                'metodo_pago' => $metodo_pago,
                'estado' => 'pendiente'
            ];
            
            // Vaciar carrito
            unset($_SESSION['carrito']);
            
            // Redirigir a la página de confirmación
            header('Location: confirmacion_pago.php');
            exit();
            
        } else {
            $error_message = "Error al guardar la factura: " . $conn->error;
            error_log($error_message);
        }
    } else {
        $error_message = "Error al guardar el pedido: " . $conn->error;
        error_log($error_message);
    }
}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago en Efectivo - Construcciones Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="styles/pago_efectivo.css">
    
</head>
<body>
    <div class="header-pago">
        <div class="header-content">
            <h1>💰 Pago en Efectivo</h1>
            <a href="carrito.php" class="volver-carrito">
                ← Volver al carrito
            </a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <strong>❌ Error:</strong> <?php echo $error_message; ?>
                <p>Por favor, intenta nuevamente o contacta al administrador.</p>
            </div>
        <?php endif; ?>

        <div class="grid-pago">
            <!-- Formulario de Datos del Cliente -->
            <div class="formulario-cliente">
                <h2>Información de Contacto</h2>
                
                <form method="post" id="formPagoEfectivo">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre">Nombre Completo *</label>
                            <input type="text" id="nombre" name="nombre" required 
                                   placeholder="Ingresa tu nombre completo"
                                   value="<?php echo isset($_SESSION['loggedin']) ? htmlspecialchars($_SESSION['usuario_nombre']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="telefono">Teléfono *</label>
                            <input type="tel" id="telefono" name="telefono" required 
                                   placeholder="+53 5XXXXXXX"
                                   value="<?php echo isset($_SESSION['loggedin']) ? htmlspecialchars($_SESSION['usuario_telefono']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Correo Electrónico *</label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="tu@email.com"
                                   value="<?php echo isset($_SESSION['loggedin']) ? htmlspecialchars($_SESSION['usuario_email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="seccion-instrucciones">
                        <h3>📋 Instrucciones para Pago en Efectivo</h3>
                        <ol>
                            <li>Completa tus datos de contacto</li>
                            <li>Revisa el resumen de tu pedido</li>
                            <li>Confirma tu pedido para generar la factura</li>
                            <li>Acude a nuestra tienda para recoger y pagar tu pedido</li>
                            <li>Recoge en 3-5 días hábiles</li>
                        </ol>
                    </div>

                    <div class="seccion-importante">
                        <strong>💡 IMPORTANTE</strong>
                        <ul>
                            <li>El pago se realiza al momento de recoger el pedido en la tienda</li>
                            <li>Ten el monto exacto disponible (<strong>$<?php echo number_format($total, 2); ?> CUP</strong>)</li>
                            <li>Verifica los productos al recogerlos</li>
                            <li>Guarda tu factura como comprobante</li>
                            <li>Horario de recogida: Lunes a Viernes 8:00 AM - 5:00 PM</li>
                        </ul>
                    </div>

                    <div class="checkbox-confirmacion">
                        <input type="checkbox" id="confirmar_datos" required>
                        <label for="confirmar_datos">
                            Confirmo que los datos proporcionados son correctos y acepto pagar en efectivo al recoger el pedido en la tienda
                        </label>
                    </div>
                    
                    <button type="submit" name="confirmar_pago" class="btn-confirmar" id="btnConfirmar" disabled>
                        ✅ Confirmar Pedido y Generar Factura
                    </button>
                </form>
            </div>

            <!-- Resumen del Pedido -->
            <div class="resumen-pedido">
                <h2>Resumen de tu Pedido</h2>
                
                <div class="lista-productos-pago">
                    <?php foreach ($productos_carrito as $producto): ?>
                        <div class="producto-item-pago">
                            <img src="img/productos/<?php echo $producto['imagen']; ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                 class="producto-imagen-pago">
                            
                            <div class="producto-info-pago">
                                <div class="producto-nombre-pago"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                <div class="producto-detalles-pago">
                                    <span>Cantidad: <?php echo $producto['cantidad']; ?></span>
                                    <span>$<?php echo number_format($producto['subtotal'], 2); ?> CUP</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="detalle-total">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($total, 2); ?> CUP</span>
                </div>
                
                <div class="detalle-total">
                    <span>Envío:</span>
                    <span>No aplica</span>
                </div>
                
                <div class="detalle-total total-final">
                    <span>Total a Pagar:</span>
                    <span>$<?php echo number_format($total, 2); ?> CUP</span>
                </div>

                <div class="info-entrega">
                    <h3>🏪 Información de Recogida</h3>
                    <div class="info-item">
                        <strong>Tiempo de preparación:</strong>
                        <span>3-5 días hábiles</span>
                    </div>
                    <div class="info-item">
                        <strong>Horario de recogida:</strong>
                        <span>Lunes a Viernes 8:00 AM - 5:00 PM</span>
                    </div>
                    <div class="info-item">
                        <strong>Lugar:</strong>
                        <span>Tienda Madre Agua ST</span>
                    </div>
                    <div class="info-item">
                        <strong>Dirección:</strong>
                        <span>Acude a nuestra tienda física</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="scripts/pago_efectivo.js" defer></script>
    <script>
         // Si el usuario está logueado, marcar los campos como válidos automáticamente
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const campos = form.querySelectorAll('input[required]');
            campos.forEach(campo => {
                if (campo.value.trim()) {
                    campo.style.borderColor = '#28a745';
                    campo.style.backgroundColor = '#f8fff9';
                }
            });
            // Revalidar el formulario
            setTimeout(validarFormulario, 100);
        });
        <?php endif; ?>
  
    </script>
</body>
</html>