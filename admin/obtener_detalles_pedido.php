<?php
session_start();
include("../db/conexion.php");

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de pedido no proporcionado']);
    exit();
}

$pedido_id = intval($_GET['id']);

// Obtener información del pedido
$sql_pedido = "
    SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono
    FROM pedidos p 
    INNER JOIN usuarios u ON p.usuario_id = u.id 
    WHERE p.id = ?
";
$stmt = $conn->prepare($sql_pedido);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result_pedido = $stmt->get_result();
$pedido = $result_pedido->fetch_assoc();

if (!$pedido) {
    http_response_code(404);
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
        'cliente_nombre' => $pedido['cliente_nombre'],
        'cliente_email' => $pedido['cliente_email'],
        'cliente_telefono' => $pedido['cliente_telefono'],
        'total' => $pedido['total'],
        'estado' => $pedido['estado'],
        'direccion_entrega' => $pedido['direccion_entrega'],
        'notas' => $pedido['notas'],
        'fecha_pedido' => $pedido['fecha_pedido']
    ],
    'detalles' => $detalles
];

header('Content-Type: application/json');
echo json_encode($response);
?>