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
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título
    echo '<table>';
    echo '<tr><th colspan="9" style="text-align: center; font-size: 16px;">' . $datos['titulo'] . '</th></tr>';
    echo '<tr><td colspan="9"><strong>Fecha de generación:</strong> ' . $datos['fechaGeneracion'] . '</td></tr>';
    echo '</table>';
    echo '<br>';
    
    // Tabla de datos
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
    
    // Datos
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
    
    // Totales
    echo '<tr class="total">';
    echo '<td colspan="5" style="text-align: center;"><strong>TOTALES</strong></td>';
    echo '<td><strong>' . number_format($datos['totales']['sinVencer'], 2) . '</strong></td>';
    echo '<td><strong>' . number_format($datos['totales']['vencido1_30'], 2) . '</strong></td>';
    echo '<td><strong>' . number_format($datos['totales']['vencido31_60'], 2) . '</strong></td>';
    echo '<td><strong>' . number_format($datos['totales']['mayor60'], 2) . '</strong></td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit;
} else {
    echo "No se recibieron datos para generar el Excel.";
}
?>