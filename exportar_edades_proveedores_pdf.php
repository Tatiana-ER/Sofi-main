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
        return utf8_decode($texto);
    }
}

// Función para formatear moneda
function formatear_moneda($valor) {
    return '$ ' . number_format($valor, 2, '.', ',');
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
        // Crear PDF en orientación horizontal
        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // Título principal
        $pdf->Cell(0, 10, convertir_texto($datos['titulo']), 0, 1, 'C');
        $pdf->Ln(3);
        
        // Fecha de generación
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 8, convertir_texto('Fecha de generación: ' . $datos['fechaGeneracion']), 0, 1, 'R');
        $pdf->Ln(5);
        
        // ===== PRIMERA PARTE: INFORME DETALLADO =====
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);
        
        // Definir anchos de columnas (optimizados para orientación horizontal)
        $anchos = array(22, 55, 22, 22, 18, 28, 28, 28, 28);
        $encabezados = array(
            'Identificación', 
            'Nombre del Proveedor', 
            'Documento', 
            'Fecha Venc.', 
            'Días Mora', 
            'Saldo Sin Vencer', 
            'Venc. 1-30 Días', 
            'Venc. 31-60 Días', 
            'Mayor 60 Días'
        );
        
        // Dibujar cabecera
        foreach ($encabezados as $index => $encabezado) {
            $pdf->Cell($anchos[$index], 8, convertir_texto($encabezado), 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Variables para control
        $pdf->SetFont('Arial', '', 8);
        $fill = false;
        $altura_fila = 7;
        $contador_facturas = 0;
        
        $total_sin_vencer = 0;
        $total_vencido_1_30 = 0;
        $total_vencido_31_60 = 0;
        $total_mayor_60 = 0;
        
        // Agrupar facturas por proveedor
        $facturas_por_proveedor = [];
        if (isset($datos['facturas']) && is_array($datos['facturas'])) {
            foreach ($datos['facturas'] as $factura) {
                $id = $factura['identificacion'];
                if (!isset($facturas_por_proveedor[$id])) {
                    $facturas_por_proveedor[$id] = [
                        'nombre' => $factura['nombre'],
                        'facturas' => []
                    ];
                }
                $facturas_por_proveedor[$id]['facturas'][] = $factura;
            }
        }
        
        // Mostrar facturas agrupadas por proveedor
        foreach ($facturas_por_proveedor as $identificacion => $proveedor) {
            // Mostrar cada factura
            foreach ($proveedor['facturas'] as $factura) {
                $pdf->SetFillColor($fill ? 240 : 255, $fill ? 240 : 255, $fill ? 240 : 255);
                
                // Identificación
                $pdf->Cell($anchos[0], $altura_fila, convertir_texto($factura['identificacion']), 1, 0, 'C', $fill);
                
                // Nombre (truncar si es muy largo)
                $nombre = isset($factura['nombre']) ? $factura['nombre'] : '';
                if (strlen($nombre) > 35) {
                    $nombre = substr($nombre, 0, 32) . '...';
                }
                $pdf->Cell($anchos[1], $altura_fila, convertir_texto($nombre), 1, 0, 'L', $fill);
                
                // Documento
                $pdf->Cell($anchos[2], $altura_fila, convertir_texto($factura['documento']), 1, 0, 'C', $fill);
                
                // Fecha vencimiento
                $pdf->Cell($anchos[3], $altura_fila, convertir_texto($factura['fecha_vencimiento']), 1, 0, 'C', $fill);
                
                // Días mora
                $pdf->Cell($anchos[4], $altura_fila, convertir_texto($factura['dias_mora']), 1, 0, 'C', $fill);
                
                // Saldos
                $pdf->Cell($anchos[5], $altura_fila, formatear_moneda($factura['saldo_sin_vencer']), 1, 0, 'R', $fill);
                $pdf->Cell($anchos[6], $altura_fila, formatear_moneda($factura['vencido_1_30']), 1, 0, 'R', $fill);
                $pdf->Cell($anchos[7], $altura_fila, formatear_moneda($factura['vencido_31_60']), 1, 0, 'R', $fill);
                $pdf->Cell($anchos[8], $altura_fila, formatear_moneda($factura['mayor_60']), 1, 1, 'R', $fill);
                
                // Acumular totales generales
                $total_sin_vencer += $factura['saldo_sin_vencer'];
                $total_vencido_1_30 += $factura['vencido_1_30'];
                $total_vencido_31_60 += $factura['vencido_31_60'];
                $total_mayor_60 += $factura['mayor_60'];
                
                $contador_facturas++;
                $fill = !$fill;
            }
        }
        
        // Línea separadora
        $pdf->Ln(5);
        
        // Totales generales (como en la imagen)
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(180, 180, 180);
        
        $ancho_total_primero = array_sum(array_slice($anchos, 0, 5));
        $pdf->Cell($ancho_total_primero, 8, 'TOTALES', 1, 0, 'C', true);
        
        // Totales numéricos
        $pdf->Cell($anchos[5], 8, formatear_moneda($total_sin_vencer), 1, 0, 'R', true);
        $pdf->Cell($anchos[6], 8, formatear_moneda($total_vencido_1_30), 1, 0, 'R', true);
        $pdf->Cell($anchos[7], 8, formatear_moneda($total_vencido_31_60), 1, 0, 'R', true);
        $pdf->Cell($anchos[8], 8, formatear_moneda($total_mayor_60), 1, 1, 'R', true);
        
        // Separador entre secciones
        $pdf->Ln(10);
        
        // ===== SEGUNDA PARTE: TOTALES POR PROVEEDOR =====
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'TOTALES POR PROVEEDOR', 0, 1, 'C');
        $pdf->Ln(3);
        
        // Cabecera de la tabla de totales
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);
        
        $anchos_totales = array(40, 110, 40);
        
        $pdf->Cell($anchos_totales[0], 8, 'Identificación', 1, 0, 'C', true);
        $pdf->Cell($anchos_totales[1], 8, 'Nombre del Proveedor', 1, 0, 'C', true);
        $pdf->Cell($anchos_totales[2], 8, 'Total', 1, 1, 'C', true);
        
        $pdf->SetFont('Arial', '', 8);
        $fill = false;
        
        // Calcular y mostrar totales por proveedor
        $total_general = 0;
        
        foreach ($facturas_por_proveedor as $identificacion => $proveedor) {
            $total_proveedor = 0;
            foreach ($proveedor['facturas'] as $factura) {
                $total_proveedor += $factura['saldo_sin_vencer'] + $factura['vencido_1_30'] + 
                                  $factura['vencido_31_60'] + $factura['mayor_60'];
            }
            
            $pdf->SetFillColor($fill ? 240 : 255, $fill ? 240 : 255, $fill ? 240 : 255);
            
            // Identificación
            $pdf->Cell($anchos_totales[0], $altura_fila, convertir_texto($identificacion), 1, 0, 'C', $fill);
            
            // Nombre
            $nombre = $proveedor['nombre'];
            if (strlen($nombre) > 50) {
                $nombre = substr($nombre, 0, 47) . '...';
            }
            $pdf->Cell($anchos_totales[1], $altura_fila, convertir_texto($nombre), 1, 0, 'L', $fill);
            
            // Total
            $pdf->Cell($anchos_totales[2], $altura_fila, formatear_moneda($total_proveedor), 1, 1, 'R', $fill);
            
            $total_general += $total_proveedor;
            $fill = !$fill;
        }
        
        // Línea separadora antes del total general
        $pdf->Ln(2);
        
        // Total general
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(180, 180, 180);
        
        $pdf->Cell($anchos_totales[0] + $anchos_totales[1], 8, 'TOTAL GENERAL', 1, 0, 'R', true);
        $pdf->Cell($anchos_totales[2], 8, formatear_moneda($total_general), 1, 1, 'R', true);
        
        // Pie de página
        $pdf->SetY(-20);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 8, convertir_texto('Total de facturas: ' . $contador_facturas . ' | Generado el ' . date('d/m/Y H:i:s')), 0, 0, 'L');
        $pdf->Cell(0, 8, convertir_texto('Página ') . $pdf->PageNo(), 0, 0, 'R');
        
        // Salida del PDF
        $pdf->Output('I', 'Edades_Cartera_Proveedores_' . date('Y-m-d') . '.pdf');
        
    } catch (Exception $e) {
        die('Error al generar PDF: ' . $e->getMessage());
    }
    
} else {
    echo "No se recibieron datos para generar el PDF.";
}
?>