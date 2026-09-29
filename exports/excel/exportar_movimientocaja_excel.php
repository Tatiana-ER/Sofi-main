<?php
// ================== EXPORTAR MOVIMIENTO DE CAJA A EXCEL ==================
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

// ================== CUENTAS DE CAJA DISPONIBLES (1105xx) ==================
$sql_cuentas_caja = "SELECT DISTINCT codigo_cuenta, nombre_cuenta 
                      FROM libro_diario 
                      WHERE codigo_cuenta LIKE '1105%' 
                      ORDER BY codigo_cuenta";
$stmt_cuentas_caja = $pdo->query($sql_cuentas_caja);
$cuentas_caja = $stmt_cuentas_caja->fetchAll(PDO::FETCH_ASSOC);

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';

$cuenta_caja = isset($_GET['cuenta']) && $_GET['cuenta'] != ''
    ? $_GET['cuenta']
    : (count($cuentas_caja) > 0 ? $cuentas_caja[0]['codigo_cuenta'] : '');

$nombre_cuenta_caja = '';
foreach ($cuentas_caja as $c) {
    if ($c['codigo_cuenta'] == $cuenta_caja) {
        $nombre_cuenta_caja = $c['nombre_cuenta'];
        break;
    }
}

// ================== SALDO INICIAL ==================
$saldoCorriente = 0;

if ($cuenta_caja != '') {
    $sql_saldo_inicial = "SELECT 
                            COALESCE(SUM(debito), 0) as total_debito,
                            COALESCE(SUM(credito), 0) as total_credito
                          FROM libro_diario
                          WHERE codigo_cuenta = :cuenta
                            AND fecha < :desde";
    $params_si = [':cuenta' => $cuenta_caja, ':desde' => $fecha_desde];

    if ($tercero != '') {
        $sql_saldo_inicial .= " AND tercero_identificacion = :tercero";
        $params_si[':tercero'] = $tercero;
    }

    $stmt_si = $pdo->prepare($sql_saldo_inicial);
    $stmt_si->execute($params_si);
    $mov_inicial = $stmt_si->fetch(PDO::FETCH_ASSOC);

    $saldoCorriente = floatval($mov_inicial['total_debito']) - floatval($mov_inicial['total_credito']);
}

$saldoInicialPeriodo = $saldoCorriente;

// ================== MOVIMIENTOS DEL PERIODO ==================
$movimientos = [];

if ($cuenta_caja != '') {
    $sql_mov = "SELECT * FROM libro_diario
                WHERE codigo_cuenta = :cuenta
                  AND fecha BETWEEN :desde AND :hasta";
    $params_mov = [':cuenta' => $cuenta_caja, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

    if ($tercero != '') {
        $sql_mov .= " AND tercero_identificacion = :tercero";
        $params_mov[':tercero'] = $tercero;
    }

    $sql_mov .= " ORDER BY fecha ASC, id ASC";

    $stmt_mov = $pdo->prepare($sql_mov);
    $stmt_mov->execute($params_mov);
    $movimientos = $stmt_mov->fetchAll(PDO::FETCH_ASSOC);
}

// ================== FORMATEAR NOMBRE DE COMPROBANTE ==================
function formatearComprobanteCaja($tipo_documento, $numero_documento) {
    $etiquetas = [
        'factura_venta' => 'Factura Venta',
        'factura_compra' => 'Factura Compra',
        'recibo_caja' => 'Recibo de Caja',
        'comprobante_egreso' => 'Comprobante Egreso',
        'comprobante_contable' => 'Comprobante Contable',
        'cierre_contable' => 'Cierre Contable'
    ];
    $etiqueta = isset($etiquetas[$tipo_documento]) ? $etiquetas[$tipo_documento] : ucfirst(str_replace('_', ' ', $tipo_documento));
    return trim($etiqueta . ' ' . $numero_documento);
}

// ================== CALCULAR SALDO CORRIENTE FILA POR FILA Y TOTALES ==================
$totalDebito = 0;
$totalCredito = 0;
$filasReporte = [];

foreach ($movimientos as $mov) {
    $debito = floatval($mov['debito']);
    $credito = floatval($mov['credito']);

    $saldoInicialFila = $saldoCorriente;
    $saldoCorriente += ($debito - $credito);
    $saldoFinalFila = $saldoCorriente;

    $totalDebito += $debito;
    $totalCredito += $credito;

    $filasReporte[] = [
        'comprobante' => formatearComprobanteCaja($mov['tipo_documento'], $mov['numero_documento']),
        'fecha' => $mov['fecha'],
        'tercero_identificacion' => $mov['tercero_identificacion'],
        'tercero_nombre' => $mov['tercero_nombre'],
        'saldo_inicial' => $saldoInicialFila,
        'debito' => $debito,
        'credito' => $credito,
        'saldo_final' => $saldoFinalFila
    ];
}

$saldoFinalPeriodo = $saldoCorriente;

// ================== CONFIGURAR HEADERS PARA EXCEL ==================
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="movimiento_caja_' . date('Ymd_His') . '.xls"');
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
        <h2 style="text-align: center; margin-bottom: 20px;">MOVIMIENTO DE CAJA</h2>

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

        <?php if ($cuenta_caja != ''): ?>
            <p style="text-align: center;"><strong>Cuenta de Caja:</strong> <?= htmlspecialchars($cuenta_caja) ?> - <?= htmlspecialchars($nombre_cuenta_caja) ?></p>
        <?php endif; ?>
        <?php if ($tercero != ''): ?>
            <p style="text-align: center;"><strong>Tercero:</strong> <?= htmlspecialchars($tercero) ?></p>
        <?php endif; ?>
        <p style="text-align: center;"><strong>Fecha de generación:</strong> <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>COMPROBANTE</th>
                <th>FECHA</th>
                <th>IDENTIFICACIÓN TERCERO</th>
                <th>NOMBRE TERCERO</th>
                <th>SALDO INICIAL</th>
                <th>DÉBITO</th>
                <th>CRÉDITO</th>
                <th>SALDO FINAL</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cuenta_caja == ''): ?>
                <tr><td colspan="8" style="text-align:center; color:#6c757d; font-style:italic;">No hay ninguna cuenta de caja (1105xx) registrada todavía en el libro diario.</td></tr>
            <?php elseif (count($filasReporte) > 0): ?>
                <?php foreach ($filasReporte as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['comprobante']) ?></td>
                        <td><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                        <td style="mso-number-format:'\@';"><?= htmlspecialchars($fila['tercero_identificacion']) ?></td>
                        <td><?= htmlspecialchars($fila['tercero_nombre']) ?></td>
                        <td class="numero"><?= number_format($fila['saldo_inicial'], 2, '.', ',') ?></td>
                        <td class="numero"><?= $fila['debito'] > 0 ? number_format($fila['debito'], 2, '.', ',') : '' ?></td>
                        <td class="numero"><?= $fila['credito'] > 0 ? number_format($fila['credito'], 2, '.', ',') : '' ?></td>
                        <td class="numero"><?= number_format($fila['saldo_final'], 2, '.', ',') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background-color: #D9E1F2; font-weight: bold;">
                    <td colspan="4" style="text-align: right;">TOTALES DEL PERÍODO:</td>
                    <td class="numero"><?= number_format($saldoInicialPeriodo, 2, '.', ',') ?></td>
                    <td class="numero"><?= number_format($totalDebito, 2, '.', ',') ?></td>
                    <td class="numero"><?= number_format($totalCredito, 2, '.', ',') ?></td>
                    <td class="numero"><?= number_format($saldoFinalPeriodo, 2, '.', ',') ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="4">Sin movimientos en el período</td>
                    <td class="numero"><?= number_format($saldoInicialPeriodo, 2, '.', ',') ?></td>
                    <td class="numero">0.00</td>
                    <td class="numero">0.00</td>
                    <td class="numero"><?= number_format($saldoFinalPeriodo, 2, '.', ',') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
