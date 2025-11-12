<?php
session_start();
include("db/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['carrito'])) {
        // Limpiar el carrito actual de la sesión
        $_SESSION['carrito'] = [];
        
        // Agregar productos del carrito de localStorage
        foreach ($input['carrito'] as $item) {
            $_SESSION['carrito'][] = [
                'id' => intval($item['id']),
                'cantidad' => intval($item['cantidad'])
            ];
        }
        
        echo json_encode(['success' => true, 'message' => 'Carrito sincronizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>