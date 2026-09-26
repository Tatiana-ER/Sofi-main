<?php
// ================== EXPORTAR LIBRO DE COMPRAS A EXCEL ==================
require_once '../../config/database.php';

$pdo = Database::getConnection();

// ================== OBTENER DATOS DEL PERFIL ==================
$sql_perfil = "SELECT persona, nombres, apellidos, razon, cedula, digito FROM perfil LIMIT 1";
$stmt_perfil = $pdo->query($sql_perfil);
$perfil = $stmt_perfil->fetch(PDO::FETCH_ASSOC);

if ($perfil) {
    if ($perfil['persona'] == 'juridica' && !empty($perfil['razon'])) {
        $nombre_empresa = $perfil['razon'];
    } else {
        $nombre_empresa = trim($perfil['nombres'] . ' ' . $perfil['apellidos']);
    }
    $nit_empresa = $perfil['cedula'] . ($perfil['digito'] > 0 ? '-' . $perfil['digito'] : '');
} else {
    $nombre_empresa = 'Nombre de la Empresa';
    $nit_empresa = 'NIT de la Empresa';
}

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$proveedor_identificacion = isset($_GET['proveedor']) ? $_GET['proveedor'] : '';

// ================== CONSULTA PRINCIPAL: FACTURAS DEL PERIODO ==================
$sql = "SELECT * FROM facturac WHERE fecha BETWEEN :desde AND :hasta";
$params = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($proveedor_identificacion != '') {
    $sql .= " AND identificacion = :proveedor";
    $params[':proveedor'] = $proveedor_identificacion;
}

$sql .= " ORDER BY fecha ASC, consecutivo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== BASE GRAVADA / BASE EXENTA POR FACTURA ==================
// Ojo: en detallefacturac la columna es "precioUnitario" (sin guión bajo)
$stmtBases = $pdo->prepare(
    "SELECT 
        COALESCE(SUM(CASE WHEN iva > 0 THEN (precioUnitario * cantidad) ELSE 0 END), 0) as base_gravada,
        COALESCE(SUM(CASE WHEN iva = 0 THEN (precioUnitario * cantidad) ELSE 0 END), 0) as base_exenta
     FROM detallefacturac
     WHERE factura_id = :factura_id"
);

$totalBaseGravada = 0;
$totalBaseExenta = 0;
$totalIva = 0;
$totalGeneral = 0;

// ================== CONFIGURAR HEADERS PARA EXCEL ==================
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="libro_compras_' . date('Ymd_His') . '.xls"');
header('Cache-Control: max-age=0');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
        th, td { border: 1px solid #000; padding: 8px; font-size: 11px; }
        th { background-color: #054a85; color: white; font-weight: bold; text-align: center; }
        .numero { text-align: right; }
        .header-info { margin-bottom: 20px; font-family: Arial, sans-serif; }
        .header-info h2 { color: #054a85; margin-bottom: 10px; }
        .header-info p { margin: 5px 0; }
        .texto-numerico { mso-number-format:"\@"; }
    </style>
</head>
<body>
    <div class="header-info">
        <h2 style="text-align: center; margin-bottom: 20px;">LIBRO DE COMPRAS</h2>

        <div style="text-align: center; margin: 20px 0; padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
            <div style="margin-bottom: 10px;">
                <strong>NOMBRE DE LA EMPRESA:</strong><br>
                <?= htmlspecialchars($nombre_empresa) ?>
            </div>
            <div style="margin-bottom: 10px;">
                <strong>NIT DE LA EMPRESA:</strong><br>
                <span>'<?= htmlspecialchars($nit_empresa) ?></span>
            </div>
            <div style="margin-bottom: 5px;">
                <strong>PERIODO:</strong> <?= date('d/m/Y', strtotime($fecha_desde)) ?> A <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
            </div>
        </div>

        <?php if ($proveedor_identificacion != ''): ?>
            <p style="text-align: center;"><strong>Proveedor:</strong> <?= htmlspecialchars($proveedor_identificacion) ?></p>
        <?php endif; ?>
        <p style="text-align: center;"><strong>Fecha de generación:</strong> <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>COMPROBANTE</th>
                <th>FECHA DE ELABORACIÓN</th>
                <th>IDENTIFICACIÓN TERCERO</th>
                <th>NOMBRE TERCERO</th>
                <th>BASE GRAVADA</th>
                <th>BASE EXENTA</th>
                <th>IVA</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($facturas) > 0): ?>
                <?php foreach ($facturas as $factura): ?>
                    <?php
                        $stmtBases->execute([':factura_id' => $factura['id']]);
                        $bases = $stmtBases->fetch(PDO::FETCH_ASSOC);
                        $baseGravada = floatval($bases['base_gravada']);
                        $baseExenta = floatval($bases['base_exenta']);

                        $totalBaseGravada += $baseGravada;
                        $totalBaseExenta += $baseExenta;
                        $totalIva += floatval($factura['ivaTotal']);
                        $totalGeneral += floatval($factura['valorTotal']);

                        $comprobante = 'FC-' . (!empty($factura['numeroFactura']) ? $factura['numeroFactura'] : $factura['consecutivo']);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($comprobante) ?></td>
                        <td><?= date('d/m/Y', strtotime($factura['fecha'])) ?></td>
                        <td style="mso-number-format:'\@';"><?= htmlspecialchars($factura['identificacion']) ?></td>
                        <td><?= htmlspecialchars($factura['nombre']) ?></td>
                        <td class="numero"><?= number_format($baseGravada, 2, '.', ',') ?></td>
                        <td class="numero"><?= number_format($baseExenta, 2, '.', ',') ?></td>
                        <td class="numero"><?= number_format($factura['ivaTotal'], 2, '.', ',') ?></td>
                        <td class="numero"><?= number_format($factura['valorTotal'], 2, '.', ',') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background-color: #D9E1F2; font-weight: bold;">
                    <td colspan="4" style="text-align: right;">TOTALES (<?= count($facturas) ?> factura<?= count($facturas) != 1 ? 's' : '' ?>):</td>
                    <td class="numero"><?= number_format($totalBaseGravada, 2, '.', ',') ?></td>
                    <td class="numero"><?= number_format($totalBaseExenta, 2, '.', ',') ?></td>
                    <td class="numero"><?= number_format($totalIva, 2, '.', ',') ?></td>
                    <td class="numero"><?= number_format($totalGeneral, 2, '.', ',') ?></td>
                </tr>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center; color:#6c757d; font-style:italic;">No hay facturas de compra en el período seleccionado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
