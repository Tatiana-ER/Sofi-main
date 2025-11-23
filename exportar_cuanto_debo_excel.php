<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['datosProveedores']) && isset($_POST['fecha'])) {
    $datosProveedores = json_decode($_POST['datosProveedores'], true);
    $fechaCorte = $_POST['fecha'];
    
    // Validar que existan proveedores
    if (!isset($datosProveedores['proveedores']) || count($datosProveedores['proveedores']) === 0) {
        die('Error: No hay proveedores para generar el Excel');
    }
    
    // Headers para descarga de Excel
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Cuanto_Debo_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // BOM para UTF-8 (para caracteres especiales como tildes)
    echo "\xEF\xBB\xBF";
    
    // Formatear fecha
    $fechaFormateada = date('d/m/Y', strtotime($fechaCorte));
    
    // Inicio del documento HTML para Excel
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
    echo 'th { background-color: #0d6efd; color: white; font-weight: bold; text-align: center; }';
    echo '.titulo { font-size: 18px; font-weight: bold; text-align: center; background-color: #e3f2fd; }';
    echo '.subtitulo { font-size: 12px; text-align: center; background-color: #f5f5f5; }';
    echo '.total { background-color: #f8f9fa; font-weight: bold; }';
    echo '.numero { text-align: right; }';
    echo '.centro { text-align: center; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Tabla principal
    echo '<table>';
    
    // Título
    echo '<tr>';
    echo '<td colspan="6" class="titulo">CUÁNTO DEBO</td>';
    echo '</tr>';
    
    // Subtítulo
    echo '<tr>';
    echo '<td colspan="6" class="subtitulo">Estado de Cuentas por Pagar a Proveedores</td>';
    echo '</tr>';
    
    // Fecha de corte
    echo '<tr>';
    echo '<td colspan="6" class="subtitulo"><strong>Fecha de Corte:</strong> ' . htmlspecialchars($fechaFormateada) . '</td>';
    echo '</tr>';
    
    // Espacio
    echo '<tr><td colspan="6" style="border: none; height: 10px;"></td></tr>';
    
    // Cabecera de la tabla
    echo '<tr>';
    echo '<th>Identificación</th>';
    echo '<th>Nombre del Proveedor</th>';
    echo '<th>Total Cartera</th>';
    echo '<th>Pagos Realizados</th>';
    echo '<th>Valor Anticipos</th>';
    echo '<th>Saldo por Pagar</th>';
    echo '</tr>';
    
    // Datos de los proveedores
    foreach ($datosProveedores['proveedores'] as $proveedor) {
        echo '<tr>';
        echo '<td class="centro">' . htmlspecialchars($proveedor['identificacion']) . '</td>';
        echo '<td>' . htmlspecialchars($proveedor['nombre']) . '</td>';
        echo '<td class="numero">$ ' . number_format($proveedor['totalAdeudado'], 2, '.', ',') . '</td>';
        echo '<td class="numero">$ ' . number_format($proveedor['valorPagos'], 2, '.', ',') . '</td>';
        echo '<td class="numero">$ ' . number_format($proveedor['valorAnticipos'], 2, '.', ',') . '</td>';
        echo '<td class="numero">$ ' . number_format($proveedor['saldoPagar'], 2, '.', ',') . '</td>';
        echo '</tr>';
    }
    
    // Fila de totales
    echo '<tr class="total">';
    echo '<td colspan="2" class="centro"><strong>TOTAL</strong></td>';
    echo '<td class="numero"><strong>$ ' . number_format($datosProveedores['totales']['totalAdeudado'], 2, '.', ',') . '</strong></td>';
    echo '<td class="numero"><strong>$ ' . number_format($datosProveedores['totales']['totalPagos'], 2, '.', ',') . '</strong></td>';
    echo '<td class="numero"><strong>$ ' . number_format($datosProveedores['totales']['totalAnticipos'], 2, '.', ',') . '</strong></td>';
    echo '<td class="numero"><strong>$ ' . number_format($datosProveedores['totales']['totalSaldo'], 2, '.', ',') . '</strong></td>';
    echo '</tr>';
    
    echo '</table>';
    
    // Espacio
    echo '<br><br>';
    
    
    // Footer con información
    echo '<table style="width: 100%; border: none;">';
    echo '<tr>';
    echo '<td colspan="6" style="text-align: center; border: none; font-size: 10px; color: #666;">Universidad de Santander - Ingeniería de Software</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="6" style="text-align: center; border: none; font-size: 10px; color: #666;">Todos los derechos reservados © 2025</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="6" style="text-align: center; border: none; font-size: 10px; color: #666;">Creado por iniciativa del programa de Contaduría Pública</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="6" style="text-align: center; border: none; font-size: 9px; color: #999; padding-top: 10px;">Documento generado el: ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    echo '</table>';
    
    echo '</body>';
    echo '</html>';
    
    exit;
} else {
    echo "Error: No se recibieron los datos necesarios para generar el Excel.";
}
?>