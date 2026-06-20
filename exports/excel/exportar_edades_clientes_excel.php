<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['datos'])) {
    $datos = json_decode($_POST['datos'], true);
    
    // Headers para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Edades_Cartera_' . date('Y-m-d') . '.xls"');
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
    echo '.total { background-color: #e6e6e6; font-weight: bold; }';
    echo '.total-cliente { background-color: #e8f4fd; font-weight: bold; }';
    echo '.separador { height: 5px; background-color: #f1f3f4; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título
    echo '<table>';
    echo '<tr><th colspan="9" style="text-align: center; font-size: 16px;">' . $datos['titulo'] . '</th></tr>';
    echo '<tr><td colspan="9"><strong>Fecha de generación:</strong> ' . $datos['fechaGeneracion'] . '</td></tr>';
    echo '</table>';
    echo '<br>';
    
    // Tabla de datos PRINCIPAL (facturas y filas especiales)
    echo '<table>';
    
    // Cabecera
    echo '<tr>';
    echo '<th>Identificación</th>';
    echo '<th>Nombre del Cliente</th>';
    echo '<th>Documento</th>';
    echo '<th>Fecha Vencimiento</th>';
    echo '<th>Días Mora</th>';
    echo '<th>Saldo Sin Vencer</th>';
    echo '<th>Vencido 1-30 Días</th>';
    echo '<th>Vencido 31-60 Días</th>';
    echo '<th>Mayor 60 Días</th>';
    echo '</tr>';
    
    // Procesar facturas y filas especiales en orden
    $indiceFactura = 0;
    
    // Si hay filas especiales, las procesamos en orden
    if (isset($datos['filasEspeciales']) && is_array($datos['filasEspeciales'])) {
        foreach ($datos['filasEspeciales'] as $filaEspecial) {
            if ($filaEspecial['tipo'] == 'total_cliente') {
                // Fila de TOTAL CLIENTE
                echo '<tr class="total-cliente">';
                echo '<td colspan="5" style="text-align: right; font-weight: bold;">';
                echo htmlspecialchars($filaEspecial['texto']);
                echo '</td>';
                echo '<td colspan="4" style="text-align: center; font-weight: bold;">';
                echo htmlspecialchars($filaEspecial['valor']);
                echo '</td>';
                echo '</tr>';
            } 
            elseif ($filaEspecial['tipo'] == 'separador') {
                // Separador (fila vacía con altura)
                echo '<tr class="separador"><td colspan="9"></td></tr>';
            }
        }
        
        // Ahora mostrar las facturas normales
        foreach ($datos['facturas'] as $factura) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($factura['identificacion']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['nombre']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['documento']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['fecha_vencimiento']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['dias_mora']) . '</td>';
            echo '<td>' . number_format($factura['saldo_sin_vencer'], 2) . '</td>';
            echo '<td>' . number_format($factura['vencido_1_30'], 2) . '</td>';
            echo '<td>' . number_format($factura['vencido_31_60'], 2) . '</td>';
            echo '<td>' . number_format($factura['mayor_60'], 2) . '</td>';
            echo '</tr>';
        }
    } else {
        // Versión anterior (solo facturas)
        foreach ($datos['facturas'] as $factura) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($factura['identificacion']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['nombre']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['documento']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['fecha_vencimiento']) . '</td>';
            echo '<td>' . htmlspecialchars($factura['dias_mora']) . '</td>';
            echo '<td>' . number_format($factura['saldo_sin_vencer'], 2) . '</td>';
            echo '<td>' . number_format($factura['vencido_1_30'], 2) . '</td>';
            echo '<td>' . number_format($factura['vencido_31_60'], 2) . '</td>';
            echo '<td>' . number_format($factura['mayor_60'], 2) . '</td>';
            echo '</tr>';
        }
    }
    
    // Totales generales
    echo '<tr class="total">';
    echo '<td colspan="5" style="text-align: center;"><strong>TOTALES</strong></td>';
    echo '<td><strong>' . number_format($datos['totales']['sinVencer'], 2) . '</strong></td>';
    echo '<td><strong>' . number_format($datos['totales']['vencido1_30'], 2) . '</strong></td>';
    echo '<td><strong>' . number_format($datos['totales']['vencido31_60'], 2) . '</strong></td>';
    echo '<td><strong>' . number_format($datos['totales']['mayor60'], 2) . '</strong></td>';
    echo '</tr>';
    
    echo '</table>';
    echo '<br><br>';
    
    // Tabla de TOTALES POR CLIENTE (separada)
    if (isset($datos['totalesPorCliente']) && count($datos['totalesPorCliente']) > 0) {
        echo '<table>';
        echo '<tr><th colspan="3" style="text-align: center; font-size: 14px;">TOTALES POR CLIENTE</th></tr>';
        
        // Cabecera
        echo '<tr>';
        echo '<th style="background-color: #e6e6e6;">Identificación</th>';
        echo '<th style="background-color: #e6e6e6;">Nombre del Cliente</th>';
        echo '<th style="background-color: #e6e6e6;">Total</th>';
        echo '</tr>';
        
        $totalGeneralClientes = 0;
        
        foreach ($datos['totalesPorCliente'] as $identificacion => $cliente) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($identificacion) . '</td>';
            echo '<td>' . htmlspecialchars($cliente['nombre']) . '</td>';
            echo '<td>' . number_format($cliente['total'], 2) . '</td>';
            echo '</tr>';
            
            $totalGeneralClientes += $cliente['total'];
        }
        
        // Total general
        echo '<tr class="total">';
        echo '<td colspan="2" style="text-align: center;"><strong>TOTAL GENERAL</strong></td>';
        echo '<td><strong>' . number_format($totalGeneralClientes, 2) . '</strong></td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    echo '</body>';
    echo '</html>';
    exit;
} else {
    echo "No se recibieron datos para generar el Excel.";
}
?>