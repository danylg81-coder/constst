<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include("../db/conexion.php");

// Procesar formulario de nuevo producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_producto'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    
    // Procesar imagen
    $imagen_nombre = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $imagen_nombre = uniqid() . '.' . $extension;
        $ruta_destino = "../img/productos/" . $imagen_nombre;
        
        move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino);
    }
    
    $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, imagen, categoria) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiss", $nombre, $descripcion, $precio, $stock, $imagen_nombre, $categoria);
    
    if ($stmt->execute()) {
        $mensaje = "Producto agregado exitosamente";
        // Limpiar POST para evitar reenvío al recargar
        header('Location: panel.php?success=1');
        exit();
    } else {
        $error = "Error al agregar producto: " . $stmt->error;
    }
    $stmt->close();
}

// Procesar actualización de producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_producto'])) {
    $producto_id = intval($_POST['producto_id']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    
    // Procesar imagen si se subió una nueva
    $imagen_nombre = $_POST['imagen_actual'];
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $imagen_nombre = uniqid() . '.' . $extension;
        $ruta_destino = "../img/productos/" . $imagen_nombre;
        
        move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino);
        
        // Eliminar imagen anterior si existe
        if (!empty($_POST['imagen_actual']) && $_POST['imagen_actual'] != $imagen_nombre) {
            $ruta_anterior = "../img/productos/" . $_POST['imagen_actual'];
            if (file_exists($ruta_anterior)) {
                unlink($ruta_anterior);
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, imagen = ?, categoria = ? WHERE id = ?");
    $stmt->bind_param("ssdissi", $nombre, $descripcion, $precio, $stock, $imagen_nombre, $categoria, $producto_id);
    
    if ($stmt->execute()) {
        $mensaje = "Producto actualizado exitosamente";
        header('Location: panel.php?success=2');
        exit();
    } else {
        $error = "Error al actualizar producto: " . $stmt->error;
    }
    $stmt->close();
}

// Procesar eliminación de producto
if (isset($_GET['eliminar'])) {
    $producto_id = intval($_GET['eliminar']);
    
    // Obtener información de la imagen para eliminarla
    $sql = "SELECT imagen FROM productos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    
    // Eliminar imagen del servidor
    if ($producto && !empty($producto['imagen'])) {
        $ruta_imagen = "../img/productos/" . $producto['imagen'];
        if (file_exists($ruta_imagen)) {
            unlink($ruta_imagen);
        }
    }
    
    // Eliminar producto de la base de datos
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $producto_id);
    
    if ($stmt->execute()) {
        $mensaje = "Producto eliminado exitosamente";
        header('Location: panel.php?success=3');
        exit();
    } else {
        $error = "Error al eliminar producto: " . $stmt->error;
    }
    $stmt->close();
}

// Mostrar mensajes de éxito desde URL
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case '1':
            $mensaje = "Producto agregado exitosamente";
            break;
        case '2':
            $mensaje = "Producto actualizado exitosamente";
            break;
        case '3':
            $mensaje = "Producto eliminado exitosamente";
            break;
    }
}

// Obtener productos existentes
$productos = $conn->query("SELECT * FROM productos ORDER BY id DESC");

// Obtener producto específico para edición
$producto_editar = null;
if (isset($_GET['editar'])) {
    $producto_id = intval($_GET['editar']);
    $sql = "SELECT * FROM productos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto_editar = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Madre Agua ST</title>
    <link rel="stylesheet" href="styles/panel.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        background-color: #f5f5f5;
        color: #333;
    }

    .admin-container {
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        width: 250px;
        background: #2c3e50;
        color: white;
        padding: 20px 0;
    }

    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #34495e;
        text-align: center;
    }

    .sidebar-header h2 {
        font-size: 1.2rem;
    }

    .sidebar-menu {
        list-style: none;
        padding: 20px 0;
    }

    .sidebar-menu li {
        padding: 0;
    }

    .sidebar-menu a {
        display: block;
        padding: 12px 20px;
        color: #ecf0f1;
        text-decoration: none;
        transition: all 0.3s;
    }

    .sidebar-menu a:hover {
        background: #34495e;
        border-left: 4px solid #3498db;
    }

    .main-content {
        flex: 1;
        padding: 20px;
    }

    .header {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .card-header {
        background: #3498db;
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }

    .card-body {
        padding: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-control:focus {
        border-color: #3498db;
        outline: none;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.3s;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-primary {
        background: #3498db;
        color: white;
    }

    .btn-primary:hover {
        background: #2980b9;
    }

    .btn-danger {
        background: #e74c3c;
        color: white;
    }

    .btn-danger:hover {
        background: #c0392b;
    }

    .btn-warning {
        background: #f39c12;
        color: white;
    }

    .btn-warning:hover {
        background: #d68910;
    }

    .btn-success {
        background: #27ae60;
        color: white;
    }

    .btn-success:hover {
        background: #219a52;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .table th {
        background: #f8f9fa;
        font-weight: bold;
        color: #555;
    }

    .table img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }

    .alert {
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .product-image-preview {
        max-width: 200px;
        max-height: 200px;
        margin-top: 10px;
        border-radius: 4px;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .cancel-btn {
        background: #95a5a6;
        color: white;
    }

    .cancel-btn:hover {
        background: #7f8c8d;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
    }

    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
    }

    .close {
        float: right;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
    }
</style>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Madre Agua ST</h2>
                <p>Panel de Administración</p>
                <p class="user-info">Usuario: <?php echo $_SESSION['usuario']; ?> (<?php echo $_SESSION['usuario_rol']; ?>)</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="panel.php" class="active">Gestión de Productos</a></li>
                <li><a href="pedidos.php">Gestión de Pedidos</a></li>
                <li><a href="admin_facturas.php">Gestión de Facturas</a></li>
                <li><a href="estadisticas.php">Estadísticas</a></li>
                <li><a href="usuarios.php">Gestión de Usuarios</a></li>
                <li><a href="../index.php">Volver al Sitio</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Gestión de Productos</h1>
                <p>Agrega y gestiona los productos de tu tienda</p>
            </div>

            <!-- Mensajes de alerta -->
            <?php if (isset($mensaje)): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Formulario para agregar/editar producto -->
            <div class="card">
                <div class="card-header">
                    <?php echo $producto_editar ? 'Editar Producto' : 'Agregar Nuevo Producto'; ?>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="form-producto">
                        <?php if ($producto_editar): ?>
                            <input type="hidden" name="producto_id" value="<?php echo $producto_editar['id']; ?>">
                            <input type="hidden" name="imagen_actual" value="<?php echo $producto_editar['imagen']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nombre">Nombre del Producto</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo $producto_editar ? htmlspecialchars($producto_editar['nombre']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php echo $producto_editar ? htmlspecialchars($producto_editar['descripcion']) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="precio">Precio (CUP)</label>
                            <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" 
                                   value="<?php echo $producto_editar ? $producto_editar['precio'] : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock Disponible</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0" 
                                   value="<?php echo $producto_editar ? $producto_editar['stock'] : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="categoria">Categoría</label>
                            <select class="form-control" id="categoria" name="categoria" required>
                                <option value="">Seleccionar categoría</option>
                                <option value="cemento" <?php echo ($producto_editar && $producto_editar['categoria'] == 'cemento') ? 'selected' : ''; ?>>Cemento</option>
                                <option value="bloques" <?php echo ($producto_editar && $producto_editar['categoria'] == 'bloques') ? 'selected' : ''; ?>>Bloques</option>
                                <option value="acero" <?php echo ($producto_editar && $producto_editar['categoria'] == 'acero') ? 'selected' : ''; ?>>Acero</option>
                                <option value="pintura" <?php echo ($producto_editar && $producto_editar['categoria'] == 'pintura') ? 'selected' : ''; ?>>Pintura</option>
                                <option value="seguridad" <?php echo ($producto_editar && $producto_editar['categoria'] == 'seguridad') ? 'selected' : ''; ?>>Seguridad</option>
                                <option value="otros" <?php echo ($producto_editar && $producto_editar['categoria'] == 'otros') ? 'selected' : ''; ?>>Otros</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="imagen">Imagen del Producto</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" 
                                   <?php echo !$producto_editar ? 'required' : ''; ?> onchange="previewImage(this)">
                            
                            <?php if ($producto_editar && !empty($producto_editar['imagen'])): ?>
                                <div class="current-image">
                                    <p>Imagen actual:</p>
                                    <img src="../img/productos/<?php echo $producto_editar['imagen']; ?>" 
                                         alt="<?php echo htmlspecialchars($producto_editar['nombre']); ?>"
                                         class="product-image-preview">
                                </div>
                            <?php else: ?>
                                <img id="image-preview" class="product-image-preview" src="" alt="Vista previa" style="display: none;">
                            <?php endif; ?>
                            <div class="form-note"><?php echo $producto_editar ? 'Dejar en blanco para mantener la imagen actual' : 'Se requiere imagen para nuevo producto'; ?></div>
                        </div>

                        <div class="form-actions">
                            <?php if ($producto_editar): ?>
                                <button type="submit" name="actualizar_producto" class="btn btn-success">Actualizar Producto</button>
                                <a href="panel.php" class="btn cancel-btn">Cancelar</a>
                            <?php else: ?>
                                <button type="submit" name="agregar_producto" class="btn btn-primary">Agregar Producto</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de productos existentes -->
            <div class="card">
                <div class="card-header">
                    Productos Existentes
                </div>
                <div class="card-body">
                    <?php if ($productos && $productos->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Categoría</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($producto = $productos->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="../img/productos/<?php echo $producto['imagen']; ?>" 
                                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                             onerror="this.src='../img/placeholder.jpg'">
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td>$<?php echo number_format($producto['precio'], 2); ?> CUP</td>
                                    <td><?php echo $producto['stock']; ?></td>
                                    <td>
                                        <?php 
                                        if (isset($producto['categoria']) && !empty($producto['categoria'])) {
                                            echo ucfirst($producto['categoria']);
                                        } else {
                                            echo 'Sin categoría';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="panel.php?editar=<?php echo $producto['id']; ?>" class="btn btn-warning">
                                            Editar
                                        </a>
                                        <a href="panel.php?eliminar=<?php echo $producto['id']; ?>" class="btn btn-danger" 
                                           onclick="return confirm('¿Estás seguro de que deseas eliminar el producto: <?php echo htmlspecialchars($producto['nombre']); ?>?')">
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No hay productos registrados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

   <script>
    // Scroll al formulario cuando se está editando
        <?php if ($producto_editar): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
            });
        <?php endif; ?>
   </script>
    <script src="../scripts/panel.js" defer></script>
</body>
</html>