<?php
// ================== RECIBIR DATOS POR POST ==================
$datosMovimientos = isset($_POST['datosMovimientos']) ? json_decode($_POST['datosMovimientos'], true) : null;
$filtros = isset($_POST['filtros']) ? json_decode($_POST['filtros'], true) : null;

// Verificar que se recibieron los datos
if (!$datosMovimientos) {
    die('Error: No se recibieron datos para generar el Excel');
}

// ================== EXTRAER DATOS ==================
$movimientos = $datosMovimientos['movimientos'];
$totales = $datosMovimientos['totales'];

// ================== CONFIGURAR HEADERS PARA EXCEL ==================
$filename = 'Movimiento_Inventarios_' . date('Y-m-d_His') . '.xls';

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// ================== GENERAR HTML PARA EXCEL ==================
echo "\xEF\xBB\xBF"; // BOM para UTF-8

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
        }
        .header {
            background-color: #0d6efd;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            font-size: 16px;
        }
        .filtros {
            background-color: #f8f9fa;
            padding: 5px;
            font-size: 11px;
            margin-bottom: 10px;
        }
        .filtros-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
        th {
            background-color: #054a85;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            border: 1px solid #000;
        }
        td {
            padding: 6px;
            border: 1px solid #ccc;
            text-align: center;
        }
        .total-row {
            background-color: #dcdcdc;
            font-weight: bold;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>

<!-- Encabezado -->
<table>
    <tr>
        <td colspan="8" class="header">MOVIMIENTO DE INVENTARIOS</td>
    </tr>
</table>

<br>

<!-- Filtros aplicados -->
<?php if ($filtros): ?>
<table>
    <tr>
        <td colspan="8" class="filtros">
            <div class="filtros-title">Filtros aplicados:</div>
            <?php
            $hayFiltros = false;
            
            if (!empty($filtros['fechaDesde']) || !empty($filtros['fechaHasta'])) {
                $textoFecha = 'Período: ';
                if (!empty($filtros['fechaDesde'])) {
                    $textoFecha .= date('d/m/Y', strtotime($filtros['fechaDesde']));
                }
                if (!empty($filtros['fechaHasta'])) {
                    $textoFecha .= ' - ' . date('d/m/Y', strtotime($filtros['fechaHasta']));
                }
                echo '<div>' . htmlspecialchars($textoFecha) . '</div>';
                $hayFiltros = true;
            }
            
            if (!empty($filtros['categoria']) && $filtros['categoria'] != 'Todas las categorías') {
                echo '<div>Categoría: ' . htmlspecialchars($filtros['categoria']) . '</div>';
                $hayFiltros = true;
            }
            
            if (!empty($filtros['producto']) && $filtros['producto'] != 'Todos los productos') {
                echo '<div>Producto: ' . htmlspecialchars($filtros['producto']) . '</div>';
                $hayFiltros = true;
            }
            
            if (!empty($filtros['tipo']) && $filtros['tipo'] != 'Todos') {
                echo '<div>Tipo: ' . htmlspecialchars($filtros['tipo']) . '</div>';
                $hayFiltros = true;
            }
            
            if (!$hayFiltros) {
                echo '<div>Sin filtros - Mostrando todos los movimientos</div>';
            }
            ?>
        </td>
    </tr>
</table>

<br>
<?php endif; ?>

<!-- Tabla de datos -->
<table>
    <thead>
        <tr>
            <th>Código de Producto</th>
            <th>Nombre del Producto</th>
            <th>Comprobante</th>
            <th>Fecha de Elaboración</th>
            <th>Cantidad Inicial</th>
            <th>Cantidad Entrada</th>
            <th>Cantidad Salida</th>
            <th>Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($movimientos) > 0): ?>
            <?php foreach ($movimientos as $mov): ?>
            <tr>
                <td class="text-left"><?php echo htmlspecialchars($mov['codigoProducto']); ?></td>
                <td class="text-left"><?php echo htmlspecialchars($mov['nombreProducto']); ?></td>
                <td><?php echo htmlspecialchars($mov['comprobante']); ?></td>
                <td><?php echo htmlspecialchars($mov['fecha']); ?></td>
                <td class="text-right"><?php echo number_format($mov['cantidadInicial'], 0, ',', '.'); ?></td>
                <td class="text-right"><?php echo number_format($mov['cantidadEntrada'], 0, ',', '.'); ?></td>
                <td class="text-right"><?php echo number_format($mov['cantidadSalida'], 0, ',', '.'); ?></td>
                <td class="text-right"><?php echo number_format($mov['saldo'], 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center; font-style: italic;">
                    No se encontraron movimientos con los filtros seleccionados
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="4" class="text-right">TOTAL</td>
            <td class="text-right"><?php echo number_format($totales['totalInicial'], 0, ',', '.'); ?></td>
            <td class="text-right"><?php echo number_format($totales['totalEntrada'], 0, ',', '.'); ?></td>
            <td class="text-right"><?php echo number_format($totales['totalSalida'], 0, ',', '.'); ?></td>
            <td class="text-right"><?php echo number_format($totales['totalSaldo'], 0, ',', '.'); ?></td>
        </tr>
    </tfoot>
</table>

</body>
</html>