<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['datos'])) {
    $datos = json_decode($_POST['datos'], true);
    
    // Headers para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Existencias_Inventario_' . date('Y-m-d') . '.xls"');
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
    echo '.header { background-color: #0d6efd; color: white; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Título
    echo '<table>';
    echo '<tr><th colspan="3" style="text-align: center; font-size: 16px;" class="header">' . $datos['titulo'] . '</th></tr>';
    echo '<tr><td colspan="3"><strong>Fecha de generación:</strong> ' . $datos['fechaGeneracion'] . '</td></tr>';
    
    // Fechas de filtro si existen
    if (!empty($datos['fechaDesde']) || !empty($datos['fechaHasta'])) {
        echo '<tr><td colspan="3">';
        if (!empty($datos['fechaDesde'])) {
            echo '<strong>Fecha desde:</strong> ' . $datos['fechaDesde'] . ' ';
        }
        if (!empty($datos['fechaHasta'])) {
            echo '<strong>Fecha hasta:</strong> ' . $datos['fechaHasta'];
        }
        echo '</td></tr>';
    }
    echo '</table>';
    echo '<br>';
    
    // Tabla de datos
    echo '<table>';
    
    // Cabecera
    echo '<tr>';
    echo '<th class="header">Código de Producto</th>';
    echo '<th class="header">Nombre del Producto</th>';
    echo '<th class="header">Saldo Cantidades</th>';
    echo '</tr>';
    
    // Datos
    $totalCantidades = 0;
    foreach ($datos['productos'] as $producto) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($producto['codigo']) . '</td>';
        echo '<td>' . htmlspecialchars($producto['nombre']) . '</td>';
        echo '<td>' . number_format($producto['cantidad'], 0) . '</td>';
        echo '</tr>';
        $totalCantidades += $producto['cantidad'];
    }
    
    // Totales
    echo '<tr class="total">';
    echo '<td colspan="2" style="text-align: center;"><strong>TOTAL DE CANTIDADES</strong></td>';
    echo '<td><strong>' . number_format($totalCantidades, 0) . '</strong></td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit;
} else {
    echo "No se recibieron datos para generar el Excel.";
}
?>