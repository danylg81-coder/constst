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

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_estado = $conn->real_escape_string($_POST['estado']);
    $observaciones = $conn->real_escape_string($_POST['observaciones']);

    $update_sql = "UPDATE facturas SET estado = ?, observaciones = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('ssi', $nuevo_estado, $observaciones, $id);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "✅ Estado actualizado correctamente";
        header("Location: admin_facturas.php");
        exit();
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Factura - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .header { background: #3498db; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-guardar { background: #27ae60; color: white; }
        .btn-cancelar { background: #95a5a6; color: white; }
        .info-factura { background: #ecf0f1; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Editar Factura</h1>
            <p>Actualizar estado y observaciones</p>
        </div>

        <?php if(isset($error)): ?>
            <div style="background: #e74c3c; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="info-factura">
            <h3>Información de la Factura</h3>
            <p><strong>Número:</strong> <?php echo $factura['numero_factura']; ?></p>
            <p><strong>Cliente:</strong> <?php echo $factura['nombre_cliente'] ?? 'Cliente'; ?></p>
            <p><strong>Total:</strong> $<?php echo number_format($factura['total'], 2); ?> CUP</p>
            <p><strong>Fecha:</strong> <?php echo $factura['fecha']; ?></p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="estado">Estado Actual:</label>
                <select name="estado" id="estado" required>
                    <option value="pendiente" <?php echo $factura['estado'] == 'pendiente' ? 'selected' : ''; ?>>⏳ Pendiente</option>
                    <option value="confirmado" <?php echo $factura['estado'] == 'confirmado' ? 'selected' : ''; ?>>✅ Confirmado</option>
                    <option value="procesando" <?php echo $factura['estado'] == 'procesando' ? 'selected' : ''; ?>>🚚 Procesando</option>
                    <option value="completado" <?php echo $factura['estado'] == 'completado' ? 'selected' : ''; ?>>🎉 Completado</option>
                </select>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones (opcional):</label>
                <textarea name="observaciones" id="observaciones" rows="4" 
                          placeholder="Agregar notas internas sobre este pedido..."><?php echo $factura['observaciones'] ?? ''; ?></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-guardar">💾 Guardar Cambios</button>
                <a href="admin_facturas.php" class="btn btn-cancelar">❌ Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>