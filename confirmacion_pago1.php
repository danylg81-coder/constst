<?php
session_start();
include("db/conexion.php");

// Verificar si hay una factura en sesión
if (!isset($_SESSION['ultima_factura'])) {
    header('Location: carrito.php');
    exit();
}

$factura = $_SESSION['ultima_factura'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de Pago - Madre Agua ST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/tienda.css">
    <style>
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-confirmacion {
            background: linear-gradient(135deg, #0B3A66, #1E6BC4);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 25px 25px;
            box-shadow: 0 8px 25px rgba(11, 58, 102, 0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-confirmacion::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="rgba(255,255,255,0.1)"><polygon points="50,0 100,50 50,100 0,50"/></svg>');
            background-size: 80px;
        }

        .card-confirmacion {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid #e0e0e0;
            position: relative;
            overflow: hidden;
        }

        .card-confirmacion::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #0B3A66, #1E6BC4);
        }

        .icono-exito {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out;
        }

        @keyframes bounce {
            0%, 20%, 60%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            80% { transform: translateY(-5px); }
        }

        .numero-factura {
            background: linear-gradient(135deg, #0B3A66, #1E6BC4);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 1.3rem;
            font-weight: bold;
            display: inline-block;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(11, 58, 102, 0.3);
        }

        .detalles-factura {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        .detalle-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #dee2e6;
            align-items: center;
        }

        .detalle-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detalle-label {
            font-weight: bold;
            color: #0B3A66;
            font-size: 1rem;
        }

        .detalle-valor {
            color: #495057;
            font-weight: 500;
        }

        .lista-productos {
            margin: 20px 0;
            background: white;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid #e0e0e0;
        }

        .producto-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
            transition: all 0.3s ease;
        }

        .producto-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .producto-item:last-child {
            border-bottom: none;
        }

        .acciones {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 35px;
            flex-wrap: wrap;
        }

        .btn-descargar {
            background: linear-gradient(135deg, #0B3A66, #1E6BC4);
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(11, 58, 102, 0.3);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-descargar:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(11, 58, 102, 0.4);
        }

        .btn-volver {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-volver:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        .estado-pedido {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 1px solid #b1dfbb;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            color: #155724;
            font-weight: 500;
            text-align: center;
        }

        .badge-estado {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            margin-left: 10px;
        }

        .info-adicional {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
            border-left: 4px solid #1E6BC4;
        }

        @media (max-width: 768px) {
            .acciones {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-descargar, .btn-volver {
                width: 100%;
                justify-content: center;
            }
            
            .detalle-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .container {
                padding: 15px;
            }
            
            .card-confirmacion {
                padding: 25px;
            }
        }

        .timeline {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #e9ecef;
            z-index: 1;
        }

        .timeline-paso {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .timeline-paso .circulo {
            width: 40px;
            height: 40px;
            background: #28a745;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
        }

        .timeline-paso.completado .circulo {
            background: #28a745;
        }

        .timeline-paso.actual .circulo {
            background: #1E6BC4;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(30, 107, 196, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(30, 107, 196, 0); }
            100% { box-shadow: 0 0 0 0 rgba(30, 107, 196, 0); }
        }

        .timeline-paso p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header-confirmacion">
        <div class="container">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;">✅ Pago Confirmado</h1>
            <p style="font-size: 1.2rem; opacity: 0.9;">Tu pedido ha sido procesado exitosamente</p>
        </div>
    </div>

    <div class="container">
        <div class="card-confirmacion">
            <div class="icono-exito">✅</div>
            <h2 style="color: #0B3A66; margin-bottom: 10px;">¡Gracias por tu compra!</h2>
            <p style="color: #6c757d; font-size: 1.1rem; margin-bottom: 20px;">
                Tu pedido ha sido confirmado y está siendo procesado.
            </p>
            
            <div class="numero-factura">
                Factura: <?php echo $factura['numero_factura']; ?>
            </div>

            <div class="timeline">
                <div class="timeline-paso completado">
                    <div class="circulo">1</div>
                    <p>Pedido Confirmado</p>
                </div>
                <div class="timeline-paso actual">
                    <div class="circulo">2</div>
                    <p>Preparando Envío</p>
                </div>
                <div class="timeline-paso">
                    <div class="circulo">3</div>
                    <p>En Camino</p>
                </div>
                <div class="timeline-paso">
                    <div class="circulo">4</div>
                    <p>Entregado</p>
                </div>
            </div>

            <div class="estado-pedido">
                <strong>Estado actual del pedido:</strong> 
                <span class="badge-estado"><?php echo $factura['estado']; ?></span>
            </div>

            <div class="detalles-factura">
                <h3 style="color: #0B3A66; margin-bottom: 20px; text-align: center;">📋 Resumen del Pedido</h3>
                
                <div class="detalle-item">
                    <span class="detalle-label">Fecha:</span>
                    <span class="detalle-valor"><?php echo $factura['fecha']; ?></span>
                </div>
                
                <div class="detalle-item">
                    <span class="detalle-label">Método de Pago:</span>
                    <span class="detalle-valor"><?php echo $factura['metodo_pago']; ?></span>
                </div>
                
                <div class="detalle-item">
                    <span class="detalle-label">Total:</span>
                    <span class="detalle-valor" style="font-size: 1.2rem; color: #28a745; font-weight: bold;">
                        $<?php echo number_format($factura['total'], 2); ?> CUP
                    </span>
                </div>

                <?php if (isset($factura['datos_cliente'])): ?>
                <div class="detalle-item">
                    <span class="detalle-label">Cliente:</span>
                    <span class="detalle-valor"><?php echo $factura['datos_cliente']['nombre']; ?></span>
                </div>
                <?php endif; ?>

                <h4 style="color: #0B3A66; margin: 25px 0 15px;">🛍️ Productos:</h4>
                <div class="lista-productos">
                    <?php foreach ($factura['productos'] as $producto): ?>
                        <div class="producto-item">
                            <span style="flex: 1;"><?php echo $producto['nombre']; ?></span>
                            <span style="margin: 0 15px;">x<?php echo $producto['cantidad']; ?></span>
                            <span style="font-weight: bold; color: #1E6BC4;">
                                $<?php echo number_format($producto['subtotal'], 2); ?> CUP
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="info-adicional">
                <h4 style="color: #0B3A66; margin-bottom: 10px;">📦 Información de Entrega</h4>
                <p style="margin: 5px 0; color: #495057;">
                    <strong>Tiempo estimado:</strong> 3-5 días hábiles<br>
                    <strong>Horario de entrega:</strong> Lunes a Viernes 8:00 AM - 5:00 PM<br>
                    <strong>Zonas de cobertura:</strong> Toda La Habana
                </p>
            </div>

            <div class="acciones">
                <a href="generar_factura.php" class="btn-descargar" target="_blank">
                    📄 Descargar Factura PDF
                </a>
                <a href="tienda.php" class="btn-volver">
                    🏠 Continuar Comprando
                </a>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                <p style="color: #6c757d; font-size: 0.9rem; text-align: center;">
                    <strong>Nota:</strong> Guarda tu factura para cualquier consulta futura. 
                    Si tienes alguna pregunta, contáctanos en <strong>contacto@madreaguast.cu</strong> 
                    o al <strong>+53 7 1234567</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html>