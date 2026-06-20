<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['datos'])) {
    $datos = json_decode($_POST['datos'], true);
    
    // Headers para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Edades_Cartera_Proveedores_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // BOM para UTF-8
    echo "\xEF\xBB\xBF";
    
    // Estilos básicos para Excel
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'td { border: 1px solid #000; padding: 5px; }';
    echo 'th { border: 1px solid #000; padding: 5px; background-color: #f2f2f2; font-weight: bold; }';
    echo '.titulo { font-size: 16px; font-weight: bold; text-align: center; }';
    echo '.total { background-color: #e6e6e6; font-weight: bold; }';
    echo '.total-proveedor { background-color: #f8f9fa; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título principal
    echo '<table width="100%">';
    echo '<tr><td colspan="9" class="titulo">' . htmlspecialchars($datos['titulo']) . '</td></tr>';
    echo '<tr><td colspan="9"><strong>Fecha de generación:</strong> ' . htmlspecialchars($datos['fechaGeneracion']) . '</td></tr>';
    echo '</table>';
    echo '<br><br>';
    
    // === PRIMERA PARTE: INFORME DETALLADO ===
    echo '<table cellspacing="0" cellpadding="5">';
    
    // Cabecera de la tabla principal
    echo '<tr>';
    echo '<th>Identificación</th>';
    echo '<th>Nombre del Proveedor</th>';
    echo '<th>Documento</th>';
    echo '<th>Fecha Venc.</th>';
    echo '<th>Días Mora</th>';
    echo '<th>Saldo Sin Vencer</th>';
    echo '<th>Venc. 1-30 Días</th>';
    echo '<th>Venc. 31-60 Días</th>';
    echo '<th>Mayor 60 Días</th>';
    echo '</tr>';
    
    $total_sin_vencer = 0;
    $total_vencido_1_30 = 0;
    $total_vencido_31_60 = 0;
    $total_mayor_60 = 0;
    $contador_facturas = 0;
    
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
        // Mostrar cada factura del proveedor
        foreach ($proveedor['facturas'] as $factura) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($factura['identificacion']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['nombre']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['documento']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['fecha_vencimiento']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['dias_mora']) . '</td>';
            echo '<td style="text-align: right;">' . number_format($factura['saldo_sin_vencer'], 2, '.', ',') . '</td>';
            echo '<td style="text-align: right;">' . number_format($factura['vencido_1_30'], 2, '.', ',') . '</td>';
            echo '<td style="text-align: right;">' . number_format($factura['vencido_31_60'], 2, '.', ',') . '</td>';
            echo '<td style="text-align: right;">' . number_format($factura['mayor_60'], 2, '.', ',') . '</td>';
            echo '</tr>';
            
            // Acumular totales generales
            $total_sin_vencer += $factura['saldo_sin_vencer'];
            $total_vencido_1_30 += $factura['vencido_1_30'];
            $total_vencido_31_60 += $factura['vencido_31_60'];
            $total_mayor_60 += $factura['mayor_60'];
            
            $contador_facturas++;
        }
    }
    
    // Línea separadora
    echo '<tr><td colspan="9" style="border: none; height: 10px;"></td></tr>';
    
    // Totales generales (como en la imagen)
    echo '<tr class="total">';
    echo '<td colspan="5" style="text-align: center;"><strong>TOTALES</strong></td>';
    echo '<td style="text-align: right;"><strong>' . number_format($total_sin_vencer, 2, '.', ',') . '</strong></td>';
    echo '<td style="text-align: right;"><strong>' . number_format($total_vencido_1_30, 2, '.', ',') . '</strong></td>';
    echo '<td style="text-align: right;"><strong>' . number_format($total_vencido_31_60, 2, '.', ',') . '</strong></td>';
    echo '<td style="text-align: right;"><strong>' . number_format($total_mayor_60, 2, '.', ',') . '</strong></td>';
    echo '</tr>';
    
    echo '</table>';
    
    echo '<br><br><br>';
    
    // === SEGUNDA PARTE: TOTALES POR PROVEEDOR ===
    echo '<table cellspacing="0" cellpadding="5" style="margin-top: 20px;">';
    
    echo '<tr><th colspan="3" style="text-align: center; background-color: #f8f9fa;">TOTALES POR PROVEEDOR</th></tr>';
    echo '<tr>';
    echo '<th>Identificación</th>';
    echo '<th>Nombre del Proveedor</th>';
    echo '<th>Total</th>';
    echo '</tr>';
    
    // Calcular totales por proveedor
    $totales_por_proveedor = [];
    $total_general = 0;
    
    foreach ($facturas_por_proveedor as $identificacion => $proveedor) {
        $total_proveedor = 0;
        foreach ($proveedor['facturas'] as $factura) {
            $total_proveedor += $factura['saldo_sin_vencer'] + $factura['vencido_1_30'] + 
                              $factura['vencido_31_60'] + $factura['mayor_60'];
        }
        
        echo '<tr class="total-proveedor">';
        echo '<td>' . htmlspecialchars($identificacion) . '</td>';
        echo '<td>' . htmlspecialchars($proveedor['nombre']) . '</td>';
        echo '<td style="text-align: right;">' . number_format($total_proveedor, 2, '.', ',') . '</td>';
        echo '</tr>';
        
        $total_general += $total_proveedor;
    }
    
    // Línea separadora
    echo '<tr><td colspan="3" style="border: none; height: 10px;"></td></tr>';
    
    // Total general
    echo '<tr class="total">';
    echo '<td colspan="2" style="text-align: right;"><strong>TOTAL GENERAL</strong></td>';
    echo '<td style="text-align: right;"><strong>' . number_format($total_general, 2, '.', ',') . '</strong></td>';
    echo '</tr>';
    
    echo '</table>';
    
    echo '<br><br>';
    
    // Resumen
    echo '<div style="text-align: center; font-style: italic; font-size: 11px;">';
    echo 'Total de facturas procesadas: ' . $contador_facturas . ' | Generado el: ' . date('d/m/Y H:i:s');
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    exit;
} else {
    echo "No se recibieron datos para generar el Excel.";
}
?>