<?php
session_start();

// Verificar si hay carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header('Location: carrito.php');
    exit();
}

// Calcular total
$total = 0;
foreach ($_SESSION['carrito'] as $producto) {
    $total += $producto['subtotal'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Madre Agua ST</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-pagar { background: #27ae60; color: white; font-size: 16px; }
        .resumen-pedido { background: #ecf0f1; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .producto-item { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Finalizar Compra</h1>
        
        <div class="resumen-pedido">
            <h3>Resumen de tu Pedido</h3>
            <?php foreach ($_SESSION['carrito'] as $producto): ?>
                <div class="producto-item">
                    <span><?php echo $producto['nombre']; ?> (x<?php echo $producto['cantidad']; ?>)</span>
                    <span>$<?php echo number_format($producto['subtotal'], 2); ?> CUP</span>
                </div>
            <?php endforeach; ?>
            <div style="border-top: 2px solid #2c3e50; margin-top: 10px; padding-top: 10px; font-weight: bold;">
                Total: $<?php echo number_format($total, 2); ?> CUP
            </div>
        </div>

        <form action="procesar_pedido.php" method="POST">
            <h3>Información de Contacto</h3>
            
            <div class="form-group">
                <label for="nombre_cliente">Nombre Completo *</label>
                <input type="text" id="nombre_cliente" name="nombre_cliente" required>
            </div>
            
            <div class="form-group">
                <label for="email_cliente">Email *</label>
                <input type="email" id="email_cliente" name="email_cliente" required>
            </div>
            
            <div class="form-group">
                <label for="telefono_cliente">Teléfono *</label>
                <input type="text" id="telefono_cliente" name="telefono_cliente" required>
            </div>
            
            <div class="form-group">
                <label for="metodo_pago">Método de Pago *</label>
                <select id="metodo_pago" name="metodo_pago" required>
                    <option value="transferencia">Transferencia Bancaria</option>
                    <option value="efectivo">Efectivo</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-pagar">✅ Realizar Pedido</button>
        </form>
    </div>
</body>
</html>