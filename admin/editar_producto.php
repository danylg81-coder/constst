<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include("../db/conexion.php");

$error = '';
$success = '';

// Obtener el ID del producto de la URL
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si no hay ID, redirigir al panel
if ($producto_id == 0) {
    header('Location: panel.php');
    exit();
}

// Obtener los datos actuales del producto
$sql = "SELECT * FROM productos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $producto_id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();

// Si el producto no existe, redirigir
if (!$producto) {
    header('Location: panel.php');
    exit();
}

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_producto'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    
    // Procesar imagen si se subió una nueva
    $imagen_nombre = $producto['imagen']; // Mantener la imagen actual por defecto
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $imagen_nombre = uniqid() . '.' . $extension;
        $ruta_destino = "../img/productos/" . $imagen_nombre;
        
        move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino);
        
        // Eliminar la imagen anterior si existe y no es la misma
        if ($producto['imagen'] && $producto['imagen'] != $imagen_nombre) {
            $ruta_anterior = "../img/productos/" . $producto['imagen'];
            if (file_exists($ruta_anterior)) {
                unlink($ruta_anterior);
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, imagen = ?, categoria = ? WHERE id = ?");
    $stmt->bind_param("ssdissi", $nombre, $descripcion, $precio, $stock, $imagen_nombre, $categoria, $producto_id);
    
    if ($stmt->execute()) {
        $success = "Producto actualizado exitosamente";
        // Actualizar los datos del producto en la variable $producto
        $producto['nombre'] = $nombre;
        $producto['descripcion'] = $descripcion;
        $producto['precio'] = $precio;
        $producto['stock'] = $stock;
        $producto['categoria'] = $categoria;
        $producto['imagen'] = $imagen_nombre;
    } else {
        $error = "Error al actualizar producto: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Madre Agua ST</title>
    <link rel="stylesheet" href="styles/panel.css">
    <style>
        /* Usar los mismos estilos que panel.php */
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
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
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
        }
    </style>
</head>
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
                <li><a href="panel.php">Gestión de Productos</a></li>
                <li><a href="pedidos.php">Gestión de Pedidos</a></li>
                <li><a href="usuarios.php">Gestión de Usuarios</a></li>
                <li><a href="../tienda.php">Volver a la Tienda</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Editar Producto</h1>
                <p>Modifica los datos del producto</p>
            </div>

            <!-- Mensajes de alerta -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Formulario para editar producto -->
            <div class="card">
                <div class="card-header">
                    Editar Producto: <?php echo htmlspecialchars($producto['nombre']); ?>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="form-producto">
                        <div class="form-group">
                            <label for="nombre">Nombre del Producto</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="precio">Precio (CUP)</label>
                            <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" value="<?php echo $producto['precio']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock Disponible</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo $producto['stock']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="categoria">Categoría</label>
                            <select class="form-control" id="categoria" name="categoria" required>
                                <option value="">Seleccionar categoría</option>
                                <option value="cemento" <?php echo $producto['categoria'] == 'cemento' ? 'selected' : ''; ?>>Cemento</option>
                                <option value="bloques" <?php echo $producto['categoria'] == 'bloques' ? 'selected' : ''; ?>>Bloques</option>
                                <option value="acero" <?php echo $producto['categoria'] == 'acero' ? 'selected' : ''; ?>>Acero</option>
                                <option value="pintura" <?php echo $producto['categoria'] == 'pintura' ? 'selected' : ''; ?>>Pintura</option>
                                <option value="seguridad" <?php echo $producto['categoria'] == 'seguridad' ? 'selected' : ''; ?>>Seguridad</option>
                                <option value="otros" <?php echo $producto['categoria'] == 'otros' ? 'selected' : ''; ?>>Otros</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="imagen">Imagen del Producto</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" onchange="previewImage(this)">
                            <div class="form-note">Dejar en blanco para mantener la imagen actual.</div>
                            <?php if ($producto['imagen']): ?>
                                <img id="image-preview" class="product-image-preview" src="../img/productos/<?php echo $producto['imagen']; ?>" alt="Vista previa">
                            <?php else: ?>
                                <img id="image-preview" class="product-image-preview" src="" alt="Vista previa" style="display: none;">
                            <?php endif; ?>
                        </div>

                        <button type="submit" name="actualizar_producto" class="btn btn-primary">Actualizar Producto</button>
                        <a href="panel.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>