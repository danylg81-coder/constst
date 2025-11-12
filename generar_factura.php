<?php
// Evitar que se envíe salida antes del PDF
ob_start();

// Iniciar sesión solo si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("db/conexion.php");
require('extras/fpdf/fpdf.php');

// Verificar si hay una factura en sesión
if (!isset($_SESSION['ultima_factura'])) {
    // Limpiar el buffer y mostrar error
    ob_end_clean();
    die('No hay datos de factura disponibles.');
}

$factura = $_SESSION['ultima_factura'];

// Crear una clase personalizada para la factura con diseño mejorado
class PDF extends FPDF {
    
    // Variables para colores
    var $primaryColor = array(11, 58, 102);    // Azul oscuro #0B3A66
    var $secondaryColor = array(30, 107, 196); // Azul claro #1E6BC4
    var $accentColor = array(40, 167, 69);     // Verde #28a745
    var $lightColor = array(248, 249, 250);    // Gris claro #f8f9fa
    var $darkColor = array(52, 58, 64);        // Gris oscuro #343a40
    
    function Header() {
        // Fondo decorativo en la cabecera - reducido en altura
        $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->Rect(0, 0, 210, 35, 'F');
        
        // Logo más pequeño y mejor posicionado
        if (file_exists('img/logo.png')) {
            $this->Image('img/logo.png', 15, 6, 20);
        } else {
            // Si no existe el logo, mostrar texto
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(255, 255, 255);
            $this->SetXY(15, 8);
            $this->Cell(20, 10, 'MA ST', 0, 0, 'C');
        }
        
        // Información de la empresa - textos más compactos
        $this->SetY(8);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, 'MADRE AGUA ST', 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, 'Materiales de Construcción con Impacto Social', 0, 1, 'C');
        
        // Línea decorativa más arriba
        $this->SetDrawColor($this->secondaryColor[0], $this->secondaryColor[1], $this->secondaryColor[2]);
        $this->SetLineWidth(0.8);
        $this->Line(15, 30, 195, 30);
        
        // Espacio después del header - reducido significativamente
        $this->Ln(8);
    }
    
    function Footer() {
        $this->SetY(-25);
        
        // Fondo del footer
        $this->SetFillColor($this->lightColor[0], $this->lightColor[1], $this->lightColor[2]);
        $this->Rect(0, $this->GetY(), 210, 25, 'F');
        
        // Línea decorativa superior del footer
        $this->SetDrawColor($this->secondaryColor[0], $this->secondaryColor[1], $this->secondaryColor[2]);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        
        // Información de contacto
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]);
        $this->Cell(0, 6, 'Madre Agua ST - Materiales de construcción con impacto social', 0, 1, 'C');
        $this->Cell(0, 4, 'La Habana, Cuba | +53 7 1234567 | contacto@madreaguast.cu', 0, 1, 'C');
        $this->Cell(0, 4, 'www.madreaguast.cu', 0, 1, 'C');
        
        // Número de página
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo(), 0, 0, 'R');
    }
    
    function ChapterTitle($label) {
        // Título de sección con fondo colorido
        $this->SetFillColor($this->secondaryColor[0], $this->secondaryColor[1], $this->secondaryColor[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, $label, 0, 1, 'L', true);
        $this->Ln(2);
    }
    
    function InfoBox($title, $content) {
        // Caja de información estilizada
        $this->SetFillColor($this->lightColor[0], $this->lightColor[1], $this->lightColor[2]);
        $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 8, $title, 0, 1, 'L', true);
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 6, $content);
        $this->Ln(3);
    }
    
    function TablaProductos($productos) {
        // Cabecera de la tabla
        $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 10);
        
        // Anchuras de las columnas
        $w = array(90, 25, 35, 35);
        
        // Cabecera
        $this->Cell($w[0], 10, 'PRODUCTO', 1, 0, 'C', true);
        $this->Cell($w[1], 10, 'CANTIDAD', 1, 0, 'C', true);
        $this->Cell($w[2], 10, 'PRECIO UNIT.', 1, 0, 'C', true);
        $this->Cell($w[3], 10, 'SUBTOTAL', 1, 1, 'C', true);
        
        // Datos
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
        
        $fill = false;
        $total = 0;
        
        foreach($productos as $producto) {
            // Alternar colores de fila
            if ($fill) {
                $this->SetFillColor(245, 245, 245);
            } else {
                $this->SetFillColor(255, 255, 255);
            }
            
            $this->Cell($w[0], 8, $this->truncateText($producto['nombre'], 50), 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 8, $producto['cantidad'], 'LR', 0, 'C', $fill);
            $this->Cell($w[2], 8, '$' . number_format($producto['precio'], 2) . ' CUP', 'LR', 0, 'R', $fill);
            $this->Cell($w[3], 8, '$' . number_format($producto['subtotal'], 2) . ' CUP', 'LR', 1, 'R', $fill);
            
            $fill = !$fill;
            $total += $producto['subtotal'];
        }
        
        // Línea de cierre
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(8);
        
        // Total
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($w[0] + $w[1] + $w[2], 10, 'TOTAL:', 1, 0, 'R', true);
        $this->Cell($w[3], 10, '$' . number_format($total, 2) . ' CUP', 1, 1, 'R', true);
        
        return $total;
    }
    
    function truncateText($text, $length) {
        if (strlen($text) > $length) {
            return substr($text, 0, $length) . '...';
        }
        return $text;
    }
    
    function addWatermark() {
        // Agregar marca de agua sutil sin rotación
        $this->SetFont('Arial', 'B', 40);
        $this->SetTextColor(240, 240, 240);
        $this->SetXY(20, 100);
        $this->Cell(0, 0, 'MADRE AGUA ST', 0, 0, 'C');
    }
}

// Creación del objeto PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Marca de agua de fondo (sin rotación)
$pdf->addWatermark();

// Información de la factura con fondo decorativo
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFillColor($pdf->secondaryColor[0], $pdf->secondaryColor[1], $pdf->secondaryColor[2]);

// Fondo decorativo para el título
$pdf->SetX(40);
$pdf->Cell(130, 12, 'FACTURA COMERCIAL', 0, 1, 'C', true);

// Volver al color original para el resto del contenido
$pdf->SetTextColor($pdf->primaryColor[0], $pdf->primaryColor[1], $pdf->primaryColor[2]);
$pdf->Ln(8);

// Primera fila: Número de factura y fecha
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor($pdf->darkColor[0], $pdf->darkColor[1], $pdf->darkColor[2]);
$pdf->Cell(95, 8, 'Factura No: ' . $factura['numero_factura'], 0, 0, 'L');
$pdf->Cell(95, 8, 'Fecha: ' . $factura['fecha'], 0, 1, 'R');
$pdf->Ln(6);

// Información de la empresa
$pdf->ChapterTitle('INFORMACIÓN DE LA EMPRESA');
$pdf->InfoBox('Madre Agua ST', 
    "Tienda de Materiales de Construcción\n" .
    "La Habana, Cuba\n" .
    "Teléfono: +53 7 1234567\n" .
    "Email: contacto@madreaguast.cu\n" .
    "Sitio web: www.madreaguast.cu"
);

// Información del cliente si está disponible
if (isset($factura['datos_cliente'])) {
    $pdf->ChapterTitle('INFORMACIÓN DEL CLIENTE');
    $pdf->InfoBox('Datos de Contacto', 
        "Nombre: " . $factura['datos_cliente']['nombre'] . "\n" .
        "Teléfono: " . $factura['datos_cliente']['telefono'] . "\n" .
        "Email: " . $factura['datos_cliente']['email'] . "\n" .
        "Dirección: " . $factura['datos_cliente']['direccion']
    );
}

// Método de pago
$pdf->ChapterTitle('INFORMACIÓN DE PAGO');
$pdf->InfoBox('Método de Pago', $factura['metodo_pago']);

// Detalle de productos
$pdf->ChapterTitle('DETALLE DE PRODUCTOS');
$total = $pdf->TablaProductos($factura['productos']);
$pdf->Ln(5);

// Sección de Estado del Pedido
$pdf->ChapterTitle('ESTADO DEL PEDIDO');

// Definir los estados
$estado_texto = [
    'pendiente' => '⏳ PENDIENTE DE PAGO - Esperando confirmación de transferencia',
    'verificando' => '🔍 VERIFICANDO PAGO - Confirmando recepción de fondos',
    'confirmado' => '✅ PAGO CONFIRMADO - Transferencia verificada',
    'procesando' => '🚚 PROCESANDO PEDIDO - Preparando envío',
    'completado' => '🎉 PEDIDO COMPLETADO - Entregado al cliente'
];

// Obtener el estado actual, si no está definido usar 'pendiente'
$estado_actual = $factura['estado'] ?? 'pendiente';
// Si el estado no está en el array, usar el estado por defecto 'pendiente'
$texto_estado = $estado_texto[$estado_actual] ?? $estado_texto['pendiente'];

$pdf->InfoBox('Estado Actual', $texto_estado . "\n\nÚltima actualización: " . date('d/m/Y H:i:s'));

// Notas importantes
$pdf->SetFillColor($pdf->lightColor[0], $pdf->lightColor[1], $pdf->lightColor[2]);
$pdf->SetDrawColor($pdf->secondaryColor[0], $pdf->secondaryColor[1], $pdf->secondaryColor[2]);
$pdf->SetLineWidth(0.3);

// Ajustar la posición de las notas
$currentY = $pdf->GetY();
if ($currentY > 200) {
    $pdf->Ln(10);
}

$pdf->Rect(10, $pdf->GetY(), 190, 25, 'D');
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor($pdf->darkColor[0], $pdf->darkColor[1], $pdf->darkColor[2]);
$pdf->MultiCell(190, 5, 
    "Notas:\n" .
    "• Factura generada automáticamente\n" .
    "• Contacto: contacto@madreaguast.cu"
, 0, 'L');

// Mensaje de agradecimiento
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor($pdf->accentColor[0], $pdf->accentColor[1], $pdf->accentColor[2]);
$pdf->Cell(0, 10, '¡Gracias por su compra!', 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor($pdf->darkColor[0], $pdf->darkColor[1], $pdf->darkColor[2]);
$pdf->Cell(0, 6, 'Materiales de construcción con impacto social', 0, 1, 'C');

// Limpiar el buffer y enviar el PDF
ob_end_clean();
$pdf->Output('I', 'Factura_' . $factura['numero_factura'] . '.pdf');
?>