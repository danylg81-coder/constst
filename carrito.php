<?php
session_start();
include("db/conexion.php");

// Inicializar carrito en sesión si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Manejar actualizaciones y eliminaciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);
        
        if (isset($_POST['actualizar']) && isset($_POST['cantidad'])) {
            $cantidad = intval($_POST['cantidad']);
            if ($cantidad > 0) {
                // Actualizar cantidad
                foreach ($_SESSION['carrito'] as &$producto) {
                    if ($producto['id'] == $id) {
                        $producto['cantidad'] = $cantidad;
                        break;
                    }
                }
            } else {
                // Eliminar si cantidad es 0
                $_SESSION['carrito'] = array_filter($_SESSION['carrito'], fn($p) => $p['id'] != $id);
            }
        } elseif (isset($_POST['eliminar'])) {
            // Eliminar producto
            $_SESSION['carrito'] = array_filter($_SESSION['carrito'], fn($p) => $p['id'] != $id);
        } elseif (isset($_POST['vaciar_carrito'])) {
            // Vaciar todo el carrito
            $_SESSION['carrito'] = [];
        }
    }
    
    header('Location: carrito.php');
    exit();
}

// Obtener productos del carrito con información completa
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Carrito de Compras - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/tienda.css">
    <style>
        /* Estilos específicos para la página del carrito */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-carrito {
            background: linear-gradient(135deg, #0B3A66, #1E6BC4);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 20px rgba(11, 58, 102, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .volver-tienda {
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

        .volver-tienda:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }

        .carrito-vacio {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin: 40px 0;
        }

        .carrito-vacio h2 {
            color: #0B3A66;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .carrito-vacio p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .btn-explorar {
            display: inline-block;
            background: linear-gradient(135deg, #0B3A66, #1E6BC4);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(11, 58, 102, 0.3);
        }

        .btn-explorar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(11, 58, 102, 0.4);
        }

        .grid-carrito {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .grid-carrito {
                grid-template-columns: 1fr;
            }
        }

        .lista-productos {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .producto-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .producto-item:hover {
            background: #f8f9fa;
        }

        .producto-item:last-child {
            border-bottom: none;
        }

        .producto-imagen {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 20px;
        }

        .producto-info {
            flex: 1;
        }

        .producto-nombre {
            font-weight: bold;
            color: #0B3A66;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .producto-precio {
            color: #1E6BC4;
            font-weight: bold;
        }

        .producto-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .cantidad-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cantidad-input {
            width: 60px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }

        .btn-actualizar {
            background: #1E6BC4;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-actualizar:hover {
            background: #0B3A66;
            transform: translateY(-2px);
        }

        .btn-eliminar {
            background: #ff4444;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-eliminar:hover {
            background: #cc0000;
            transform: translateY(-2px);
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

        .resumen-pedido h3 {
            color: #0B3A66;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1E6BC4;
            text-align: center;
        }

        .detalle-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
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

        .metodos-pago {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .metodo-pago {
            margin-bottom: 15px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .metodo-pago:hover {
            border-color: #1E6BC4;
            background: #f8f9fa;
        }

        .metodo-pago.seleccionado {
            border-color: #1E6BC4;
            background: linear-gradient(135deg, #f8f9fa, #e3f2fd);
        }

        .metodo-pago input {
            margin-right: 10px;
        }

        .btn-pagar {
            width: 100%;
            background: linear-gradient(135deg, #0B3A66, #1E6BC4);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(11, 58, 102, 0.3);
        }

        .btn-pagar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(11, 58, 102, 0.4);
        }

        .acciones-carrito {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-vaciar {
            background: #ff4444;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-vaciar:hover {
            background: #cc0000;
            transform: translateY(-2px);
        }

        .btn-seguir-comprando {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
            text-decoration: none;
        }

        .btn-seguir-comprando:hover {
            background: #218838;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="header-carrito">
        <div class="header-content">
            <h1>🛒 Carrito de Compras</h1>
            <a href="tienda.php" class="volver-tienda">
                ← Volver a la tienda
            </a>
        </div>
    </div>

    <div class="container">
        <?php if (empty($productos_carrito)): ?>
            <div class="carrito-vacio">
                <h2>Tu carrito está vacío</h2>
                <p>Parece que no has agregado ningún producto a tu carrito.</p>
                <a href="tienda.php" class="btn-explorar">Explorar Productos</a>
            </div>
        <?php else: ?>
            <div class="grid-carrito">
                <!-- Lista de Productos -->
                <div class="lista-productos">
                    <?php foreach ($productos_carrito as $producto): ?>
                        <div class="producto-item">
                            <img src="img/productos/<?php echo $producto['imagen']; ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                 class="producto-imagen">
                            
                            <div class="producto-info">
                                <div class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                <div class="producto-precio">$<?php echo number_format($producto['precio'], 2); ?> CUP</div>
                            </div>
                            
                            <div class="producto-controls">
                                <form method="post" class="cantidad-control">
                                    <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                                    <input type="number" name="cantidad" value="<?php echo $producto['cantidad']; ?>" 
                                           min="1" class="cantidad-input">
                                    <button type="submit" name="actualizar" class="btn-actualizar">Actualizar</button>
                                </form>
                                
                                <form method="post">
                                    <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                                    <button type="submit" name="eliminar" class="btn-eliminar" 
                                            onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                            
                            <div class="producto-subtotal">
                                <strong>$<?php echo number_format($producto['subtotal'], 2); ?> CUP</strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Resumen del Pedido -->
                <div class="resumen-pedido">
                    <h3>Resumen del Pedido</h3>
                    
                    <div class="detalle-total">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($total, 2); ?> CUP</span>
                    </div>
                    
                    <div class="detalle-total">
                        <span>Envío:</span>
                        <span>Gratis</span>
                    </div>
                    
                    <div class="detalle-total total-final">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?> CUP</span>
                    </div>

                    <!-- Métodos de Pago -->
                    <div class="metodos-pago">
                        <h4>Método de Pago</h4>
                        
                        <div class="metodo-pago seleccionado">
                            <input type="radio" id="transferencia" name="metodo_pago" value="transferencia" checked>
                            <label for="transferencia">
                                <strong>Transferencia Bancaria</strong><br>
                                <small>Realiza tu pago mediante transferencia bancaria</small>
                            </label>
                        </div>
                        
                        <div class="metodo-pago">
                            <input type="radio" id="efectivo" name="metodo_pago" value="efectivo">
                            <label for="efectivo">
                                <strong>Pago en Efectivo</strong><br>
                                <small>Paga al momento de recoger tu pedido</small>
                            </label>
                        </div>
                        
                        <button type="button" class="btn-pagar" onclick="procesarPago()">
                            Proceder al Pago
                        </button>
                    </div>

                    <!-- Acciones Adicionales -->
                    <div class="acciones-carrito">
                        <form method="post">
                            <button type="submit" name="vaciar_carrito" class="btn-vaciar"
                                    onclick="return confirm('¿Estás seguro de vaciar todo el carrito?')">
                                Vaciar Carrito
                            </button>
                        </form>
                        
                        <a href="tienda.php" class="btn-seguir-comprando">
                            Seguir Comprando
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Selección de método de pago
        document.querySelectorAll('.metodo-pago').forEach(div => {
            div.addEventListener('click', function() {
                document.querySelectorAll('.metodo-pago').forEach(d => {
                    d.classList.remove('seleccionado');
                });
                this.classList.add('seleccionado');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });

        // Función para procesar el pago
        function procesarPago() {
            const metodoPago = document.querySelector('input[name="metodo_pago"]:checked').value;
            
            if (metodoPago === 'transferencia') {
                window.location.href = 'pago_transferencia.php';
            } else if (metodoPago === 'efectivo') {
                window.location.href = 'pago_efectivo.php';
            }
        }

        // Sincronizar con localStorage al cargar la página
        window.addEventListener('load', function() {
            // Guardar el carrito actual en localStorage para consistencia
            const carritoActual = <?php echo json_encode($_SESSION['carrito']); ?>;
            localStorage.setItem('carrito', JSON.stringify(carritoActual));
        });
    </script>
</body>
</html>