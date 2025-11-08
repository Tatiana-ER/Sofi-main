<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$fecha_desde = $_GET['desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['hasta'] ?? date('Y-m-t');
$cuenta_codigo = $_GET['cuenta'] ?? '';
$tercero = $_GET['tercero'] ?? '';

// Obtener información de la cuenta
$info_cuenta = null;
if ($cuenta_codigo != '') {
    $sql_cuenta = "SELECT 
                    COALESCE(nivel6, nivel5, nivel4, nivel3, nivel2, nivel1) as codigo,
                    CASE 
                        WHEN nivel6 IS NOT NULL AND nivel6 != '' THEN nivel5
                        WHEN nivel5 IS NOT NULL AND nivel5 != '' THEN nivel4
                        WHEN nivel4 IS NOT NULL AND nivel4 != '' THEN nivel3
                        WHEN nivel3 IS NOT NULL AND nivel3 != '' THEN nivel2
                        WHEN nivel2 IS NOT NULL AND nivel2 != '' THEN nivel1
                        ELSE 'Cuenta'
                    END as nombre
                   FROM catalogoscuentascontables
                   WHERE nivel2 = :codigo 
                      OR nivel3 = :codigo 
                      OR nivel4 = :codigo 
                      OR nivel5 = :codigo 
                      OR nivel6 = :codigo
                   LIMIT 1";
    
    $stmt_cuenta = $pdo->prepare($sql_cuenta);
    $stmt_cuenta->execute([':codigo' => $cuenta_codigo]);
    $info_cuenta = $stmt_cuenta->fetch(PDO::FETCH_ASSOC);
}

// Saldo inicial
$saldo_inicial = 0;
if ($cuenta_codigo != '') {
    $sql_saldo_inicial = "SELECT COALESCE(SUM(debito - credito), 0) as saldo
                          FROM libro_diario
                          WHERE codigo_cuenta = :cuenta AND fecha < :fecha_desde";
    
    $stmt_saldo = $pdo->prepare($sql_saldo_inicial);
    $stmt_saldo->execute([':cuenta' => $cuenta_codigo, ':fecha_desde' => $fecha_desde]);
    $result_saldo = $stmt_saldo->fetch(PDO::FETCH_ASSOC);
    $saldo_inicial = $result_saldo['saldo'] ?? 0;
}

// Movimientos
$sql = "SELECT ld.* FROM libro_diario ld
        WHERE ld.fecha BETWEEN :desde AND :hasta";

if ($cuenta_codigo != '') {
    $sql .= " AND ld.codigo_cuenta = :cuenta";
}
if ($tercero != '') {
    $sql .= " AND ld.tercero_identificacion LIKE :tercero";
}

$sql .= " ORDER BY ld.fecha ASC, ld.id ASC";

$stmt = $pdo->prepare($sql);
$params = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
if ($cuenta_codigo != '') $params[':cuenta'] = $cuenta_codigo;
if ($tercero != '') $params[':tercero'] = "%$tercero%";

$stmt->execute($params);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular saldos
$saldo_acumulado = $saldo_inicial;
$total_debito = 0;
$total_credito = 0;

foreach ($movimientos as $key => $mov) {
    $total_debito += $mov['debito'];
    $total_credito += $mov['credito'];
    $saldo_acumulado += ($mov['debito'] - $mov['credito']);
    $movimientos[$key]['saldo_acumulado'] = $saldo_acumulado;
}

// Headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="libro_auxiliar_' . $cuenta_codigo . '_' . date('Ymd') . '.xls"');
header('Cache-Control: max-age=0');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 5px; }
        th { background-color: #4472C4; color: white; font-weight: bold; }
        .total { background-color: #D9E1F2; font-weight: bold; }
        .numero { text-align: right; }
    </style>
</head>
<body>
    <h2>LIBRO AUXILIAR</h2>
    <p><strong>Cuenta:</strong> <?= htmlspecialchars($cuenta_codigo) ?> - <?= htmlspecialchars($info_cuenta['nombre'] ?? 'N/A') ?></p>
    <p><strong>Período:</strong> <?= date('d/m/Y', strtotime($fecha_desde)) ?> al <?= date('d/m/Y', strtotime($fecha_hasta)) ?></p>
    <p><strong>Saldo Inicial:</strong> $<?= number_format($saldo_inicial, 2) ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo Documento</th>
                <th>No. Documento</th>
                <th>ID Tercero</th>
                <th>Nombre Tercero</th>
                <th>Concepto</th>
                <th>Débito</th>
                <th>Crédito</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $mov): ?>
                <tr>
                    <td><?= $mov['fecha'] ?></td>
                    <td><?= $mov['tipo_documento'] ?></td>
                    <td><?= $mov['numero_documento'] ?></td>
                    <td><?= $mov['tercero_identificacion'] ?></td>
                    <td><?= $mov['tercero_nombre'] ?></td>
                    <td><?= $mov['concepto'] ?></td>
                    <td class="numero"><?= $mov['debito'] > 0 ? number_format($mov['debito'], 2) : '' ?></td>
                    <td class="numero"><?= $mov['credito'] > 0 ? number_format($mov['credito'], 2) : '' ?></td>
                    <td class="numero"><?= number_format($mov['saldo_acumulado'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="6" style="text-align: right;">TOTALES:</td>
                <td class="numero"><?= number_format($total_debito, 2) ?></td>
                <td class="numero"><?= number_format($total_credito, 2) ?></td>
                <td class="numero"><?= number_format($saldo_acumulado, 2) ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>