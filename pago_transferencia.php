<?php
session_start();
include("db/conexion.php");

// Incluir la librería QR Code
require_once 'extras/phpqrcode/qrlib.php';

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

// Función para generar datos del QR en formato Transfermóvil Cuba
function generarDatosQRTransfermovil($total) {
    $numero_cuenta = '9205129971805073';
    $telefono = '51435405';
    
    $texto = "TRANSFERMOVIL_ETECSA,TRANSFERENCIA,{$numero_cuenta},{$telefono}";
    
    return $texto;
}

// Función para generar el código QR
function generarCodigoQR($data) {
    $tempDir = 'temp/';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $filename = $tempDir . 'qr_transfermovil_' . time() . '.png';
    
    try {
        QRcode::png($data, $filename, QR_ECLEVEL_L, 8, 2);
        
        if (file_exists($filename)) {
            $imageData = base64_encode(file_get_contents($filename));
            unlink($filename);
            
            return 'data:image/png;base64,' . $imageData;
        }
    } catch (Exception $e) {
        error_log("Error generando QR: " . $e->getMessage());
    }
    
    return null;
}

// Generar código QR para Transfermóvil
$qrData = generarDatosQRTransfermovil($total);
$qrImage = generarCodigoQR($qrData);

// Procesar el pago y guardar en base de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pago'])) {
    // Generar número de factura único (opcional, si lo quieres mantener)
    $numero_factura = 'MA-' . date('Ymd') . '-' . rand(1000, 9999);
    
 // Procesar el pago y guardar en base de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pago'])) {
    // Generar número de factura único
    $numero_factura = 'MA-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // OBTENER DATOS DEL CLIENTE (SECCIÓN CRÍTICA - AGREGAR ESTO)
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $nombre_cliente = $_SESSION['usuario_nombre'] ?? 'Cliente Transfermóvil';
    $email_cliente = $_SESSION['usuario_email'] ?? 'no-email@madreagua.com';
    $telefono_cliente = $_SESSION['usuario_telefono'] ?? 'No proporcionado';
    $metodo_pago = 'Transfermovil';
    
    // 1. Insertar PEDIDO en la tabla pedidos
    $sql_pedido = "INSERT INTO pedidos (usuario_id, total, estado, direccion_entrega, notas, metodo_pago) 
                   VALUES (?, ?, 'pendiente', ?, ?, ?)";

    $stmt_pedido = $conn->prepare($sql_pedido);
    $direccion_entrega = '';
    $notas = 'Pago por Transfermóvil - Pendiente de confirmación';

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
        
        // Ahora las variables $nombre_cliente, $email_cliente, $telefono_cliente están definidas
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
    <title>Pago por Transfermóvil - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/tienda.css">
    <link rel="stylesheet" href="styles/pago_transferencia.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-pago {
            background: linear-gradient(135deg, #0B3A66, #1E6BC4);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 20px rgba(11, 58, 102, 0.3);
        }

        .header-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .volver-carrito {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .volver-carrito:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }

        .grid-pago {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .grid-pago {
                grid-template-columns: 1fr;
            }
        }

        .informacion-transferencia {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px;
        }

        .informacion-transferencia h2 {
            color: #0B3A66;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1E6BC4;
        }

        .datos-bancarios {
            background: linear-gradient(135deg, #f8f9fa, #e3f2fd);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #1E6BC4;
        }

        .banco-item {
            display: flex;
            justify-content: between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .banco-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .banco-label {
            font-weight: bold;
            color: #0B3A66;
            min-width: 150px;
        }

        .banco-valor {
            color: #333;
            flex: 1;
        }

        .instrucciones {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .instrucciones h3 {
            color: #856404;
            margin-bottom: 15px;
        }

        .instrucciones ol {
            padding-left: 20px;
            margin-bottom: 0;
        }

        .instrucciones li {
            margin-bottom: 10px;
            color: #856404;
        }

        .resumen-pedido {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .resumen-pedido h2 {
            color: #0B3A66;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1E6BC4;
            text-align: center;
        }

        .lista-productos-pago {
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
        }

        .producto-item-pago {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
            gap: 12px;
        }

        .producto-item-pago:last-child {
            border-bottom: none;
        }

        .producto-imagen-pago {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .producto-info-pago {
            flex: 1;
        }

        .producto-nombre-pago {
            font-weight: bold;
            color: #0B3A66;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .producto-detalles-pago {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #666;
        }

        .detalle-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .total-final {
            font-size: 1.3rem;
            font-weight: bold;
            color: #0B3A66;
            border-bottom: none;
            border-top: 2px solid #1E6BC4;
            padding-top: 15px;
        }

        .form-confirmacion {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .checkbox-confirmacion {
            margin-bottom: 20px;
        }

        .checkbox-confirmacion input {
            margin-right: 10px;
        }

        .btn-confirmar {
            width: 100%;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-confirmar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .btn-confirmar:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .nota-importante {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            color: #155724;
        }

        .qr-code {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 2px solid #1E6BC4;
            box-shadow: 0 4px 15px rgba(30, 107, 196, 0.1);
        }

        .qr-code img {
            width: 200px;
            height: 200px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            background: white;
        }

        .qr-placeholder {
            width: 200px;
            height: 200px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #666;
            font-style: italic;
        }

        .qr-info {
            margin-top: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .qr-datos {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 0.8rem;
            text-align: left;
        }

        .qr-datos h4 {
            color: #0B3A66;
            margin-bottom: 10px;
            text-align: center;
        }

        .qr-datos-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e9ecef;
        }

        .qr-datos-item:last-child {
            border-bottom: none;
        }

        .qr-datos-label {
            font-weight: bold;
            color: #495057;
        }

        .qr-datos-valor {
            color: #0B3A66;
        }

        .transfermovil-logo {
            background: linear-gradient(135deg, #00a650, #008c3a);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .qr-raw-data {
            background: #f8f9fa;
            border: 1px dashed #6c757d;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 0.7rem;
            word-break: break-all;
            color: #495057;
        }

        .monto-destacado {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
            font-size: 1.3rem;
            font-weight: bold;
            color: #856404;
        }

        .btn-abrir-app {
            display: inline-block;
            background: linear-gradient(135deg, #00a650, #008c3a);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            margin: 15px 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 166, 80, 0.3);
            border: none;
            cursor: pointer;
        }

        .btn-abrir-app:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 166, 80, 0.4);
        }

        .opciones-pago {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 20px 0;
        }

        .opcion-pago {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .opcion-pago:hover {
            border-color: #00a650;
            transform: translateY(-2px);
        }

        .opcion-pago h4 {
            margin: 0 0 10px 0;
            color: #0B3A66;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="header-pago">
        <div class="header-content">
            <h1>📱 Pago por Transfermóvil</h1>
            <a href="carrito.php" class="volver-carrito">
                ← Volver al carrito
            </a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <strong>❌ Error:</strong> <?php echo $error_message; ?>
                <p>Por favor, intenta nuevamente o contacta al administrador.</p>
            </div>
        <?php endif; ?>

        <div class="grid-pago">
            <!-- Información de Transferencia -->
            <div class="informacion-transferencia">
                <h2>Datos para Pago con Transfermóvil</h2>
                
                <div class="datos-bancarios">
                    <div class="banco-item">
                        <span class="banco-label">Cuenta:</span>
                        <span class="banco-valor">9205 1299 7180 5073</span>
                    </div>
                    <div class="banco-item">
                        <span class="banco-label">Titular:</span>
                        <span class="banco-valor">Madre Agua ST S.A.</span>
                    </div>
                    <div class="banco-item">
                        <span class="banco-label">Teléfono:</span>
                        <span class="banco-valor">51435405</span>
                    </div>
                </div>

                <div class="monto-destacado">
                    💰 MONTO A PAGAR: $<?php echo number_format($total, 2); ?> CUP
                </div>

                <div class="opciones-pago">
                    <div class="opcion-pago">
                        <h4>🚀 Opción Rápida</h4>
                        <p>Abre Transfermóvil directamente con los datos prellenados</p>
                        <button class="btn-abrir-app" onclick="abrirTransfermovil()">
                            📱 Abrir Transfermóvil
                        </button>
                    </div>

                    <div class="opcion-pago">
                        <h4>📷 Opción con QR</h4>
                        <p>Escanea el código QR desde la app</p>
                    </div>
                </div>

                <div class="qr-code">
                    <div class="transfermovil-logo">
                    <img src="img/transfermovil-logo.jpg" alt="Logo Transfermovil" style="height:80px;">
                    </div>
                    <h3>💰 Escanear para Pagar</h3>
                    <?php if ($qrImage): ?>
                        <img src="<?php echo $qrImage; ?>" alt="Código QR para Transfermóvil">
                        <div class="qr-info">
                            <strong>Escanea este código con la app Transfermóvil</strong><br>
                            Se detectará automáticamente la cuenta y teléfono.
                        </div>
                        
                        <!-- Mostrar los datos en crudo para referencia -->
                        <div class="qr-raw-data">
                            <?php echo htmlspecialchars($qrData); ?>
                        </div>
                        
                        <div class="qr-datos">
                            <h4>📋 Información incluida en el QR:</h4>
                            <div class="qr-datos-item">
                                <span class="qr-datos-label">Servicio:</span>
                                <span class="qr-datos-valor">TRANSFERMOVIL</span>
                            </div>
                            <div class="qr-datos-item">
                                <span class="qr-datos-label">Operación:</span>
                                <span class="qr-datos-valor">TRANSFERENCIA</span>
                            </div>
                            <div class="qr-datos-item">
                                <span class="qr-datos-label">Cuenta:</span>
                                <span class="qr-datos-valor">9205129971805073</span>
                            </div>
                            <div class="qr-datos-item">
                                <span class="qr-datos-label">Teléfono:</span>
                                <span class="qr-datos-valor">51435405</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="qr-placeholder">
                            ❌ Error generando código QR<br>
                            (Contacte al administrador)
                        </div>
                    <?php endif; ?>
                </div>

                <div class="instrucciones">
                    <h3>📋 Instrucciones para pagar con Transfermóvil:</h3>
                    <ol>
                        <li><strong>Opción Rápida:</strong> Haz clic en "Abrir Transfermóvil" para abrir la app directamente</li>
                        <li><strong>Opción QR:</strong> Abre Transfermóvil manualmente y escanea el código QR</li>
                        <li>Selecciona la opción <strong>"Transferencia"</strong> en el menú Operaciones</li>
                        <li><strong>INGRESA MANUALMENTE EL MONTO:</strong> $<?php echo number_format($total, 2); ?> CUP</li>
                        <li>Verifica que todos los datos sean correctos</li>
                        <li>Confirma el pago</li>
                        <li>Guarda el comprobante de la transacción</li>
                        <li>Regresa a esta página y confirma tu pago</li>
                    </ol>
                </div>

                <div class="nota-importante">
                    <strong>⚠️ IMPORTANTE:</strong> El QR contiene la cuenta (9205129971805073) y teléfono (51435405). <strong>DEBES INGRESAR MANUALMENTE EL MONTO</strong> de $<?php echo number_format($total, 2); ?> CUP en la aplicación. Tu pedido será procesado una vez que confirmemos la recepción del pago.
                </div>
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
                    <span>Gratis</span>
                </div>
                
                <div class="detalle-total total-final">
                    <span>Total a Pagar:</span>
                    <span>$<?php echo number_format($total, 2); ?> CUP</span>
                </div>

                <!-- Formulario de Confirmación -->
                <form method="post" class="form-confirmacion">
                    <div class="checkbox-confirmacion">
                        <input type="checkbox" id="confirmar_transferencia" required>
                        <label for="confirmar_transferencia">
                            Confirmo que he realizado el pago por Transfermóvil por el monto exacto de $<?php echo number_format($total, 2); ?> CUP
                        </label>
                    </div>
                    
                    <button type="submit" name="confirmar_pago" class="btn-confirmar" id="btnConfirmar" disabled>
                        ✅ Confirmar Pago y Generar Factura
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Habilitar botón de confirmación cuando se marque el checkbox
        document.getElementById('confirmar_transferencia').addEventListener('change', function() {
            document.getElementById('btnConfirmar').disabled = !this.checked;
        });

        // Mostrar alerta de confirmación antes de enviar el formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que has realizado el pago por Transfermóvil? Esta acción generará tu factura y procesará tu pedido.')) {
                e.preventDefault();
            }
        });

        // Función para abrir Transfermóvil con deep link
        function abrirTransfermovil() {
            const telefono = '51435405';
            const cuenta = '9205129971805073';
            const monto = <?php echo $total; ?>;
            
            const transfermovilUrl = `transfermovil://transferencia?cuenta=${cuenta}&telefono=${telefono}&monto=${monto}`;
            const androidIntent = `intent://transferencia/#Intent;package=com.etecsa.transfermovil;scheme=transfermovil;S.cuenta=${cuenta};S.telefono=${telefono};S.monto=${monto};end`;
            
            window.location.href = transfermovilUrl;
            
            setTimeout(function() {
                if (!document.hidden) {
                    window.location.href = 'https://www.apklis.cu/application/cu.etecsa.cubacel.tr.tm';
                }
            }, 2000);
        }

        // Detectar si estamos en móvil
        function esDispositivoMovil() {
            return /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        // Si no es móvil, mostrar advertencia al hacer clic en el botón
        document.querySelector('.btn-abrir-app').addEventListener('click', function(e) {
            if (!esDispositivoMovil()) {
                e.preventDefault();
                alert('Esta función está diseñada para dispositivos móviles. En un dispositivo móvil, este botón abriría directamente la aplicación Transfermóvil.');
            }
        });
    </script>
</body>
</html>