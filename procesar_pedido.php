<?php
session_start();
require_once "db/conexion.php";

// Verificar si hay carrito en sesión
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header('Location: carrito.php');
    exit();
}

// Recibir datos del formulario de checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_cliente = $conn->real_escape_string($_POST['nombre_cliente'] ?? '');
    $email_cliente = $conn->real_escape_string($_POST['email_cliente'] ?? '');
    $telefono_cliente = $conn->real_escape_string($_POST['telefono_cliente'] ?? '');
    $metodo_pago = $conn->real_escape_string($_POST['metodo_pago'] ?? 'transferencia');
    
    // Calcular total
    $total = 0;
    foreach ($_SESSION['carrito'] as $producto) {
        $total += $producto['subtotal'];
    }
    
    // Generar número de factura único
    $numero_factura = 'FACT-' . date('Ymd-His') . '-' . rand(1000, 9999);
    
    // Insertar factura en la base de datos
    $sql = "INSERT INTO facturas (numero_factura, nombre_cliente, email_cliente, telefono_cliente, total, metodo_pago, estado) 
            VALUES (?, ?, ?, ?, ?, ?, 'pendiente')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssds', $numero_factura, $nombre_cliente, $email_cliente, $telefono_cliente, $total, $metodo_pago);
    
    if ($stmt->execute()) {
        $factura_id = $conn->insert_id;
        
        // Insertar productos de la factura
        $sql_productos = "INSERT INTO factura_productos (factura_id, nombre_producto, cantidad, precio, subtotal) 
                         VALUES (?, ?, ?, ?, ?)";
        $stmt_productos = $conn->prepare($sql_productos);
        
        foreach ($_SESSION['carrito'] as $producto) {
            $stmt_productos->bind_param('isidd', 
                $factura_id, 
                $producto['nombre'], 
                $producto['cantidad'], 
                $producto['precio'], 
                $producto['subtotal']
            );
            $stmt_productos->execute();
        }
        
        // Guardar factura en sesión para mostrar en confirmación
        $_SESSION['ultima_factura'] = [
            'numero_factura' => $numero_factura,
            'fecha' => date('d/m/Y H:i'),
            'nombre_cliente' => $nombre_cliente,
            'email_cliente' => $email_cliente,
            'telefono_cliente' => $telefono_cliente,
            'total' => $total,
            'metodo_pago' => $metodo_pago,
            'estado' => 'pendiente',
            'productos' => $_SESSION['carrito']
        ];
        
        // Vaciar carrito
        unset($_SESSION['carrito']);
        
        // Redirigir a confirmación de pago
        header('Location: confirmacion_pago.php');
        exit();
        
    } else {
        die("Error al guardar la factura: " . $conn->error);
    }
}
?>