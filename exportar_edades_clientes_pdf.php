<?php
// Verificar si existe la librería
if (!file_exists('libs/fpdf/fpdf.php')) {
    die('Error: No se encuentra la librería FPDF');
}

require('libs/fpdf/fpdf.php');

// Función mejorada para conversión de texto
function convertir_texto($texto) {
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    } else {
        // Fallback si no existe mb_convert_encoding
        return utf8_decode($texto);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['datos'])) {
    
    // Validar y decodificar JSON
    $datos_json = $_POST['datos'];
    if (empty($datos_json)) {
        die('Error: No se recibieron datos');
    }
    
    $datos = json_decode($datos_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Error en formato JSON: ' . json_last_error_msg());
    }
    
    try {
        // Crear PDF
        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // Título
        $pdf->Cell(0, 10, convertir_texto($datos['titulo']), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Fecha de generación
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 10, convertir_texto('Fecha de generación: ' . $datos['fechaGeneracion']), 0, 1, 'R');
        $pdf->Ln(5);
        
        // Cabecera de la tabla
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);
        
        // Ajustar anchos de columnas
        $anchos = array(25, 50, 20, 25, 18, 28, 28, 28, 28);
        $encabezados = array(
            'Identificación', 
            'Nombre del Cliente', 
            'Documento', 
            'Fecha Venc.', 
            'Días Mora', 
            'Saldo Sin Vencer', 
            'Venc. 1-30 Días', 
            'Venc. 31-60 Días', 
            'Mayor 60 Días'
        );
        
        foreach ($encabezados as $index => $encabezado) {
            $pdf->Cell($anchos[$index], 8, convertir_texto($encabezado), 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Datos de las facturas
        $pdf->SetFont('Arial', '', 8);
        $fill = false;
        $altura_fila = 7;
        
        if (isset($datos['facturas']) && is_array($datos['facturas'])) {
            foreach ($datos['facturas'] as $factura) {
                $pdf->SetFillColor($fill ? 240 : 255, $fill ? 240 : 255, $fill ? 240 : 255);
                
                // Identificación
                $pdf->Cell($anchos[0], $altura_fila, convertir_texto($factura['identificacion']), 1, 0, 'C', $fill);
                
                // Nombre (truncar si es muy largo)
                $nombre = isset($factura['nombre']) ? $factura['nombre'] : '';
                if (strlen($nombre) > 30) {
                    $nombre = substr($nombre, 0, 27) . '...';
                }
                $pdf->Cell($anchos[1], $altura_fila, convertir_texto($nombre), 1, 0, 'L', $fill);
                
                // Documento
                $pdf->Cell($anchos[2], $altura_fila, convertir_texto($factura['documento']), 1, 0, 'C', $fill);
                
                // Fecha vencimiento
                $pdf->Cell($anchos[3], $altura_fila, convertir_texto($factura['fecha_vencimiento']), 1, 0, 'C', $fill);
                
                // Días mora
                $pdf->Cell($anchos[4], $altura_fila, convertir_texto($factura['dias_mora']), 1, 0, 'C', $fill);
                
                // Saldos (formato monetario)
                $pdf->Cell($anchos[5], $altura_fila, '$ ' . number_format($factura['saldo_sin_vencer'], 2), 1, 0, 'R', $fill);
                $pdf->Cell($anchos[6], $altura_fila, '$ ' . number_format($factura['vencido_1_30'], 2), 1, 0, 'R', $fill);
                $pdf->Cell($anchos[7], $altura_fila, '$ ' . number_format($factura['vencido_31_60'], 2), 1, 0, 'R', $fill);
                $pdf->Cell($anchos[8], $altura_fila, '$ ' . number_format($factura['mayor_60'], 2), 1, 1, 'R', $fill);
                
                $fill = !$fill;
            }
        }
        
        // Totales
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(180, 180, 180);
        
        // Sumar anchos de las primeras 5 columnas
        $ancho_total_primero = array_sum(array_slice($anchos, 0, 5));
        $pdf->Cell($ancho_total_primero, 8, 'TOTALES', 1, 0, 'C', true);
        
        // Totales numéricos
        $pdf->Cell($anchos[5], 8, '$ ' . number_format($datos['totales']['sinVencer'], 2), 1, 0, 'R', true);
        $pdf->Cell($anchos[6], 8, '$ ' . number_format($datos['totales']['vencido1_30'], 2), 1, 0, 'R', true);
        $pdf->Cell($anchos[7], 8, '$ ' . number_format($datos['totales']['vencido31_60'], 2), 1, 0, 'R', true);
        $pdf->Cell($anchos[8], 8, '$ ' . number_format($datos['totales']['mayor60'], 2), 1, 1, 'R', true);
        
        // Pie de página
        $pdf->SetY(-15);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 10, convertir_texto('Página ') . $pdf->PageNo(), 0, 0, 'C');
        
        // Salida
        $pdf->Output('I', 'Edades_Cartera_' . date('Y-m-d') . '.pdf');
        
    } catch (Exception $e) {
        die('Error al generar PDF: ' . $e->getMessage());
    }
    
} else {
    echo "No se recibieron datos para generar el PDF.";
}
?>