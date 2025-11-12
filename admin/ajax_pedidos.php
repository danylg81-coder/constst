<?php
session_start();
include("../db/conexion.php");

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

// Obtener detalles de un pedido específico
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener_detalles') {
    $pedido_id = intval($_GET['pedido_id']);
    
    // Obtener información del pedido
    $sql_pedido = "
        SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.id = ?
    ";
    $stmt = $conn->prepare($sql_pedido);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result_pedido = $stmt->get_result();
    $pedido = $result_pedido->fetch_assoc();
    
    if (!$pedido) {
        echo json_encode(['error' => 'Pedido no encontrado']);
        exit();
    }
    
    // Obtener detalles del pedido (productos)
    $sql_detalles = "
        SELECT dp.*, pr.nombre as producto_nombre, pr.imagen as producto_imagen
        FROM detalles_pedido dp
        INNER JOIN productos pr ON dp.producto_id = pr.id
        WHERE dp.pedido_id = ?
    ";
    $stmt_detalles = $conn->prepare($sql_detalles);
    $stmt_detalles->bind_param("i", $pedido_id);
    $stmt_detalles->execute();
    $result_detalles = $stmt_detalles->get_result();
    $detalles = $result_detalles->fetch_all(MYSQLI_ASSOC);
    
    // Formatear la respuesta
    $response = [
        'pedido' => [
            'id' => $pedido['id'],
            'cliente_nombre' => $pedido['cliente_nombre'] ?? $pedido['nombre_cliente'] ?? 'Cliente no registrado',
            'cliente_email' => $pedido['cliente_email'] ?? $pedido['email_cliente'] ?? '',
            'cliente_telefono' => $pedido['cliente_telefono'] ?? $pedido['telefono_cliente'] ?? '',
            'total' => $pedido['total'],
            'estado' => $pedido['estado'],
            'direccion_entrega' => $pedido['direccion_entrega'],
            'notas' => $pedido['notas'],
            'metodo_pago' => $pedido['metodo_pago'],
            'fecha_pedido' => $pedido['fecha_pedido']
        ],
        'detalles' => $detalles
    ];
    
    echo json_encode($response);
    exit();
}
?>