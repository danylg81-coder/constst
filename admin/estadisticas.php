<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

include("../db/conexion.php");

// Función helper para formatear moneda
function formatearMoneda($valor) {
    return '$' . number_format($valor, 2);
}

// Inicializar arrays para evitar errores
$stats = [
    'total_stock' => 0,
    'total_productos' => 0,
    'ventas_totales' => 0,
    'ventas_totales_formateado' => '$0.00'
];
$ventas_mensuales = [];
$ventas_metodo_pago = [];
$productos_stock_bajo = [];
$productos_mas_vendidos = [];

try {
    // Obtener estadísticas generales
    
    // Total de productos en stock
    $result = $conn->query("SELECT SUM(stock) as total_stock FROM productos");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_stock'] = $row['total_stock'] ?? 0;
    }

    // Total de productos
    $result = $conn->query("SELECT COUNT(*) as total_productos FROM productos");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_productos'] = $row['total_productos'] ?? 0;
    }

    // Verificar si existe la tabla ventas
    $table_check = $conn->query("SHOW TABLES LIKE 'ventas'");
    
    if ($table_check->num_rows > 0) {
        // Ventas totales - CON EL SÍMBOLO DE PESO
        $result = $conn->query("SELECT SUM(total) as ventas_totales FROM ventas WHERE estado = 'completado'");
        if ($result) {
            $row = $result->fetch_assoc();
            $ventas_totales_num = $row['ventas_totales'] ?? 0;
            $stats['ventas_totales'] = $ventas_totales_num;
            $stats['ventas_totales_formateado'] = formatearMoneda($ventas_totales_num);
        }

        // Ventas por mes (últimos 6 meses)
        for ($i = 5; $i >= 0; $i--) {
            $mes = date('Y-m', strtotime("-$i months"));
            $result = $conn->query("SELECT SUM(total) as total FROM ventas WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes' AND estado = 'completado'");
            if ($result) {
                $row = $result->fetch_assoc();
                $ventas_mensuales[$mes] = $row['total'] ?? 0;
            } else {
                $ventas_mensuales[$mes] = 0;
            }
        }

        // Ventas por método de pago
        $result = $conn->query("SELECT metodo_pago, SUM(total) as total FROM ventas WHERE estado = 'completado' GROUP BY metodo_pago");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ventas_metodo_pago[$row['metodo_pago']] = $row['total'];
            }
        }

        // Verificar si existe la tabla detalle_venta
        $table_check_detalle = $conn->query("SHOW TABLES LIKE 'detalle_venta'");
        if ($table_check_detalle->num_rows > 0) {
            // Productos más vendidos
            $result = $conn->query("
                SELECT p.nombre, SUM(dv.cantidad) as cantidad_vendida 
                FROM detalle_venta dv 
                JOIN productos p ON dv.producto_id = p.id 
                JOIN ventas v ON dv.venta_id = v.id 
                WHERE v.estado = 'completado'
                GROUP BY p.id 
                ORDER BY cantidad_vendida DESC 
                LIMIT 10
            ");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $productos_mas_vendidos[] = $row;
                }
            }
        }
    }

    // Productos con stock bajo
    $result = $conn->query("SELECT nombre, stock FROM productos WHERE stock < 10 ORDER BY stock ASC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $productos_stock_bajo[] = $row;
        }
    }

} catch (Exception $e) {
    $error_db = "Error al cargar estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Madre Agua ST</title>
    <link rel="stylesheet" href="styles/panel.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        color: #333;
        min-height: 100vh;
    }

    .admin-container {
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        width: 250px;
        background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 20px 0;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #4a6078;
        text-align: center;
        background: rgba(255,255,255,0.1);
    }

    .sidebar-header h2 {
        font-size: 1.2rem;
        margin-bottom: 5px;
    }

    .user-info {
        font-size: 0.8rem;
        opacity: 0.8;
        margin-top: 10px;
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
        padding: 15px 20px;
        color: #ecf0f1;
        text-decoration: none;
        transition: all 0.3s;
        border-left: 3px solid transparent;
    }

    .sidebar-menu a:hover,
    .sidebar-menu a.active {
        background: rgba(52, 152, 219, 0.2);
        border-left: 3px solid #3498db;
        transform: translateX(5px);
    }

    .main-content {
        flex: 1;
        padding: 20px;
        background: transparent;
    }

    .header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        margin-bottom: 30px;
        text-align: center;
    }

    .header h1 {
        font-size: 2.5rem;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .header p {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    }

    .card-header {
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        color: white;
        padding: 20px 25px;
        font-weight: bold;
        font-size: 1.2rem;
        border-bottom: none;
    }

    .card-body {
        padding: 25px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px 25px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255,255,255,0.1);
        transform: rotate(45deg);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    .stat-card:hover::before {
        transform: rotate(45deg) translate(20px, 20px);
    }

    .stat-card:nth-child(2) {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card:nth-child(3) {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card:nth-child(4) {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .stat-card h3 {
        font-size: 14px;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }

    .stat-card .value {
        font-size: 32px;
        font-weight: bold;
        position: relative;
        z-index: 1;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .chart-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }

    .chart-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .chart-card:hover {
        transform: translateY(-5px);
    }

    .chart-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 25px;
        font-weight: bold;
        font-size: 1.2rem;
    }

    .chart-card .card-body {
        padding: 25px;
        height: 350px;
        position: relative;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .table th,
    .table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }

    .table th {
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table tr:hover {
        background: #f8f9fa;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .table tr:last-child td {
        border-bottom: none;
    }

    .alert-warning {
        background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
        color: #856404;
        border: none;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .text-danger {
        color: #e74c3c;
        font-weight: bold;
        background: rgba(231, 76, 60, 0.1);
        padding: 5px 10px;
        border-radius: 5px;
    }

    .text-warning {
        color: #f39c12;
        font-weight: bold;
        background: rgba(243, 156, 18, 0.1);
        padding: 5px 10px;
        border-radius: 5px;
    }

    .setup-info {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: #155724;
        border: none;
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .no-data {
        text-align: center;
        padding: 40px 20px;
        color: #7f8c8d;
        font-style: italic;
    }

    .no-data::before {
        content: "📊";
        font-size: 3rem;
        display: block;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    /* Animaciones */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stat-card,
    .chart-card,
    .card {
        animation: fadeInUp 0.6s ease-out;
    }

    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .stat-card:nth-child(4) { animation-delay: 0.4s; }

    /* Responsive */
    @media (max-width: 768px) {
        .admin-container {
            flex-direction: column;
        }
        
        .sidebar {
            width: 100%;
            height: auto;
        }
        
        .chart-container {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .header h1 {
            font-size: 2rem;
        }
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
                <li><a href="panel.php">Gestión de Productos</a></li>
                <li><a href="pedidos.php">Gestión de Pedidos</a></li>
                <li><a href="admin_facturas.php">Gestión de Facturas</a></li>
                <li><a href="usuarios.php">Gestión de Usuarios</a></li>
                <li><a href="estadisticas.php" class="active">Estadísticas</a></li>
                <li><a href="../index.php">Volver al Sitio</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>📈 Resúmen de Estadísticas</h1>
                <p>Métricas financieras y análisis de ventas en tiempo real</p>
            </div>

            <?php if (isset($error_db)): ?>
                <div class="alert alert-error"><?php echo $error_db; ?></div>
            <?php endif; ?>

            <!-- Tarjetas de estadísticas principales -->
<div class="stats-grid">
    <div class="stat-card">
        <h3>Ventas Totales</h3>
        <div class="value">$ <?php echo number_format($stats['ventas_totales'], 2); ?> CUP</div>
    </div>
    <div class="stat-card">
        <h3>Total de Productos</h3>
        <div class="value"><?php echo $stats['total_productos']; ?></div>
    </div>
    <div class="stat-card">
        <h3>Stock Total</h3>
        <div class="value"><?php echo $stats['total_stock']; ?> unidades</div>
    </div>
    <div class="stat-card">
        <h3>Productos con Stock Bajo</h3>
        <div class="value"><?php echo count($productos_stock_bajo); ?></div>
    </div>
</div>

            <!-- Gráficos -->
            <?php if ($table_check->num_rows > 0): ?>
            <div class="chart-container">
                <div class="chart-card">
                    <div class="card-header">📊 Ventas por Mes (Últimos 6 meses)</div>
                    <div class="card-body">
                        <canvas id="ventasMensualesChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="card-header">💰 Ventas por Método de Pago</div>
                    <div class="card-body">
                        <canvas id="ventasMetodoPagoChart"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tablas de datos -->
            <div class="chart-container">
                <div class="chart-card">
                    <div class="card-header">⚠️ Productos con Stock Bajo</div>
                    <div class="card-body">
                        <?php if (count($productos_stock_bajo) > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock Actual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($productos_stock_bajo as $producto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td class="<?php echo $producto['stock'] < 5 ? 'text-danger' : 'text-warning'; ?>">
                                            <?php echo $producto['stock']; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                No hay productos con stock bajo
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="card-header">🏆 Productos Más Vendidos</div>
                    <div class="card-body">
                        <?php if (count($productos_mas_vendidos) > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad Vendida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($productos_mas_vendidos as $producto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td><?php echo $producto['cantidad_vendida']; ?> unidades</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                No hay datos de productos vendidos
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php if ($table_check->num_rows > 0): ?>
    <script>
        // Función para crear gradientes
        function createGradient(ctx, color1, color2) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        }

        // Gráfico de ventas mensuales con efecto 3D mejorado
        const ventasMensualesCtx = document.getElementById('ventasMensualesChart').getContext('2d');
        const ventasMensualesChart = new Chart(ventasMensualesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($ventas_mensuales)); ?>,
                datasets: [{
                    label: 'Ventas (CUP)',
                    data: <?php echo json_encode(array_values($ventas_mensuales)); ?>,
                    backgroundColor: createGradient(ventasMensualesCtx, 'rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)'),
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 10,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `Ventas: $${context.parsed.y.toFixed(2)} CUP`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString() + ' CUP';
                            },
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de ventas por método de pago con efecto 3D mejorado
        const ventasMetodoPagoCtx = document.getElementById('ventasMetodoPagoChart').getContext('2d');
        const ventasMetodoPagoChart = new Chart(ventasMetodoPagoCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($ventas_metodo_pago)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($ventas_metodo_pago)); ?>,
                    backgroundColor: [
                        createGradient(ventasMetodoPagoCtx, 'rgba(102, 126, 234, 0.9)', 'rgba(118, 75, 162, 0.9)'),
                        createGradient(ventasMetodoPagoCtx, 'rgba(240, 147, 251, 0.9)', 'rgba(245, 87, 108, 0.9)'),
                        createGradient(ventasMetodoPagoCtx, 'rgba(79, 172, 254, 0.9)', 'rgba(0, 242, 254, 0.9)'),
                        createGradient(ventasMetodoPagoCtx, 'rgba(67, 233, 123, 0.9)', 'rgba(56, 249, 215, 0.9)')
                    ],
                    borderColor: 'white',
                    borderWidth: 3,
                    borderRadius: 10,
                    hoverBorderWidth: 4,
                    hoverOffset: 15,
                    spacing: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 2000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return [
                                    `${context.label}: $${context.parsed.toFixed(2)} CUP`,
                                    `(${percentage}%)`
                                ];
                            }
                        }
                    }
                }
            }
        });

        // Efectos de hover adicionales
        document.querySelectorAll('.chart-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Animación de contadores para las tarjetas de estadísticas
        function animateCounter(element, target) {
            const duration = 2000;
            const step = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    element.textContent = target.toLocaleString();
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current).toLocaleString();
                }
            }, 16);
        }

        // Iniciar animaciones cuando la página cargue
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.stat-card .value').forEach(card => {
                const value = card.textContent;
                if (value.includes('$')) {
                    const numericValue = parseFloat(value.replace(/[$,]/g, ''));
                    animateCounter(card, numericValue);
                } else {
                    const numericValue = parseInt(value.replace(/[^\d]/g, ''));
                    animateCounter(card, numericValue);
                }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>