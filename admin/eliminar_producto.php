<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

include("../db/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Obtener información de la imagen para eliminarla
    $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($producto = $result->fetch_assoc()) {
        // Eliminar la imagen del servidor
        if (!empty($producto['imagen'])) {
            $ruta_imagen = "../img/productos/" . $producto['imagen'];
            if (file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }
        }
        
        // Eliminar el producto de la base de datos
        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Solicitud inválida']);
}
?>