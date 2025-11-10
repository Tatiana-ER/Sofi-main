<?php
// ================== EXPORTAR BALANCE DE PRUEBA A EXCEL ==================
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_desde = isset($_GET['cuenta_desde']) ? $_GET['cuenta_desde'] : '';
$cuenta_hasta = isset($_GET['cuenta_hasta']) ? $_GET['cuenta_hasta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';

// ================== FUNCIÓN PARA CALCULAR MOVIMIENTOS ==================
function calcularMovimientos($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    $naturaleza = substr($codigo_cuenta, 0, 1);
    
    // Saldo inicial
    $sql_inicial = "SELECT 
                        COALESCE(SUM(debito), 0) as suma_debito,
                        COALESCE(SUM(credito), 0) as suma_credito
                    FROM libro_diario 
                    WHERE codigo_cuenta = :cuenta 
                      AND fecha < :desde";
    
    $params_ini = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde];
    
    if ($tercero != '') {
        $sql_inicial .= " AND tercero_identificacion = :tercero";
        $params_ini[':tercero'] = $tercero;
    }
    
    $stmt_ini = $pdo->prepare($sql_inicial);
    $stmt_ini->execute($params_ini);
    $ini = $stmt_ini->fetch(PDO::FETCH_ASSOC);
    
    $deb_ini = floatval($ini['suma_debito']);
    $cred_ini = floatval($ini['suma_credito']);
    
    if (in_array($naturaleza, ['1','5','6','7'])) {
        $saldo_inicial = $deb_ini - $cred_ini;
    } else {
        $saldo_inicial = $cred_ini - $deb_ini;
    }
    
    // Movimientos del periodo
    $sql_mov = "SELECT 
                    COALESCE(SUM(debito), 0) as mov_debito,
                    COALESCE(SUM(credito), 0) as mov_credito
                FROM libro_diario 
                WHERE codigo_cuenta = :cuenta 
                  AND fecha BETWEEN :desde AND :hasta";
    
    $params_mov = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_mov .= " AND tercero_identificacion = :tercero";
        $params_mov[':tercero'] = $tercero;
    }
    
    $stmt_mov = $pdo->prepare($sql_mov);
    $stmt_mov->execute($params_mov);
    $mov = $stmt_mov->fetch(PDO::FETCH_ASSOC);
    
    $mov_debito = floatval($mov['mov_debito']);
    $mov_credito = floatval($mov['mov_credito']);
    
    if (in_array($naturaleza, ['1','5','6','7'])) {
        $saldo_final = $saldo_inicial + $mov_debito - $mov_credito;
    } else {
        $saldo_final = $saldo_inicial + $mov_credito - $mov_debito;
    }
    
    return [
        'saldo_inicial' => $saldo_inicial,
        'debito' => $mov_debito,
        'credito' => $mov_credito,
        'saldo_final' => $saldo_final
    ];
}

// ================== OBTENER CUENTAS ==================
$sql_cuentas = "SELECT DISTINCT codigo_cuenta, nombre_cuenta 
                FROM libro_diario 
                WHERE fecha BETWEEN :desde AND :hasta";

$params = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($cuenta_desde != '' && $cuenta_hasta != '') {
    $sql_cuentas .= " AND codigo_cuenta BETWEEN :cuenta_desde AND :cuenta_hasta";
    $params[':cuenta_desde'] = $cuenta_desde;
    $params[':cuenta_hasta'] = $cuenta_hasta;
}

if ($tercero != '') {
    $sql_cuentas .= " AND tercero_identificacion = :tercero";
    $params[':tercero'] = $tercero;
}

$sql_cuentas .= " ORDER BY codigo_cuenta";

$stmt = $pdo->prepare($sql_cuentas);
$stmt->execute($params);
$cuentas_detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construir jerarquía
$cuentas_completas = [];
$codigos_procesados = [];

$nombres_agrupacion = [
    '1' => 'Activo',
    '2' => 'Pasivo',
    '3' => 'Patrimonio',
    '4' => 'Ingresos',
    '5' => 'Gastos',
    '6' => 'Costos de ventas',
    '11' => 'Efectivo y equivalentes de efectivo',
    '13' => 'Deudores comerciales y otras cuentas por cobrar',
    '14' => 'Inventarios',
    '15' => 'Propiedad planta y equipo',
    '22' => 'Proveedores',
    '23' => 'Acreedores comerciales y otras cuentas por pagar',
    '24' => 'Impuestos, gravámenes y tasas',
    '25' => 'Beneficios a empleados',
    '31' => 'Capital social',
    '36' => 'Resultado del ejercicio',
    '41' => 'Ingresos de actividades ordinarias',
    '51' => 'Administrativos',
    '61' => 'Costo de ventas y de prestación de servicios'
];

foreach ($cuentas_detalle as $cuenta) {
    $codigo = $cuenta['codigo_cuenta'];
    $nombre = $cuenta['nombre_cuenta'];
    
    if (!in_array($codigo, $codigos_procesados)) {
        $movs = calcularMovimientos($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero);
        $cuentas_completas[] = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'saldo_inicial' => $movs['saldo_inicial'],
            'debito' => $movs['debito'],
            'credito' => $movs['credito'],
            'saldo_final' => $movs['saldo_final'],
            'nivel' => strlen($codigo)
        ];
        $codigos_procesados[] = $codigo;
    }
    
    $niveles = [
        substr($codigo, 0, 6),
        substr($codigo, 0, 4),
        substr($codigo, 0, 2),
        substr($codigo, 0, 1)
    ];
    
    foreach ($niveles as $nivel_codigo) {
        if ($nivel_codigo != $codigo && !in_array($nivel_codigo, $codigos_procesados)) {
            $nombre_nivel = isset($nombres_agrupacion[$nivel_codigo]) ? 
                          $nombres_agrupacion[$nivel_codigo] : 
                          'Cuenta ' . $nivel_codigo;
            
            $cuentas_completas[] = [
                'codigo' => $nivel_codigo,
                'nombre' => $nombre_nivel,
                'saldo_inicial' => 0,
                'debito' => 0,
                'credito' => 0,
                'saldo_final' => 0,
                'nivel' => strlen($nivel_codigo),
                'es_agrupacion' => true
            ];
            $codigos_procesados[] = $nivel_codigo;
        }
    }
}

usort($cuentas_completas, function($a, $b) {
    return strcmp($a['codigo'], $b['codigo']);
});

// Calcular totales
$totales_por_codigo = [];

foreach ($cuentas_completas as &$cuenta) {
    if (!isset($cuenta['es_agrupacion'])) {
        $totales_por_codigo[$cuenta['codigo']] = [
            'saldo_inicial' => $cuenta['saldo_inicial'],
            'debito' => $cuenta['debito'],
            'credito' => $cuenta['credito'],
            'saldo_final' => $cuenta['saldo_final']
        ];
        
        $codigo = $cuenta['codigo'];
        $niveles_superiores = [
            substr($codigo, 0, 6),
            substr($codigo, 0, 4),
            substr($codigo, 0, 2),
            substr($codigo, 0, 1)
        ];
        
        foreach ($niveles_superiores as $sup) {
            if ($sup != $codigo) {
                if (!isset($totales_por_codigo[$sup])) {
                    $totales_por_codigo[$sup] = [
                        'saldo_inicial' => 0,
                        'debito' => 0,
                        'credito' => 0,
                        'saldo_final' => 0
                    ];
                }
                $totales_por_codigo[$sup]['saldo_inicial'] += $cuenta['saldo_inicial'];
                $totales_por_codigo[$sup]['debito'] += $cuenta['debito'];
                $totales_por_codigo[$sup]['credito'] += $cuenta['credito'];
                $totales_por_codigo[$sup]['saldo_final'] += $cuenta['saldo_final'];
            }
        }
    }
}

foreach ($cuentas_completas as &$cuenta) {
    if (isset($cuenta['es_agrupacion']) && isset($totales_por_codigo[$cuenta['codigo']])) {
        $cuenta['saldo_inicial'] = $totales_por_codigo[$cuenta['codigo']]['saldo_inicial'];
        $cuenta['debito'] = $totales_por_codigo[$cuenta['codigo']]['debito'];
        $cuenta['credito'] = $totales_por_codigo[$cuenta['codigo']]['credito'];
        $cuenta['saldo_final'] = $totales_por_codigo[$cuenta['codigo']]['saldo_final'];
    }
}

// Totales generales
$total_saldo_inicial = 0;
$total_debito = 0;
$total_credito = 0;
$total_saldo_final = 0;

foreach ($cuentas_completas as $cuenta) {
    if ($cuenta['nivel'] == 1) {
        $total_saldo_inicial += $cuenta['saldo_inicial'];
        $total_debito += $cuenta['debito'];
        $total_credito += $cuenta['credito'];
        $total_saldo_final += $cuenta['saldo_final'];
    }
}

// ================== GENERAR EXCEL ==================
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="Balance_Prueba_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF"; // BOM para UTF-8

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 5px; }
        th { background-color: #054a85; color: white; font-weight: bold; }
        .nivel-1 { background-color: #e3f2fd; font-weight: bold; }
        .nivel-2 { background-color: #f1f8ff; font-weight: bold; }
        .nivel-4 { font-weight: 600; }
        .total { background-color: #054a85; color: white; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>BALANCE DE PRUEBA GENERAL</h2>
    <p><strong>Período:</strong> <?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Código cuenta contable</th>
                <th>Nombre cuenta contable</th>
                <th>Saldo inicial</th>
                <th>Movimiento débito</th>
                <th>Movimiento crédito</th>
                <th>Saldo final</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cuentas_completas as $cuenta): ?>
            <tr class="nivel-<?= $cuenta['nivel'] ?>">
                <td><?= htmlspecialchars($cuenta['codigo']) ?></td>
                <td><?= htmlspecialchars($cuenta['nombre']) ?></td>
                <td class="text-right"><?= number_format($cuenta['saldo_inicial'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format($cuenta['debito'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format($cuenta['credito'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format($cuenta['saldo_final'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="2"><strong>TOTALES</strong></td>
                <td class="text-right"><strong><?= number_format($total_saldo_inicial, 2, ',', '.') ?></strong></td>
                <td class="text-right"><strong><?= number_format($total_debito, 2, ',', '.') ?></strong></td>
                <td class="text-right"><strong><?= number_format($total_credito, 2, ',', '.') ?></strong></td>
                <td class="text-right"><strong><?= number_format($total_saldo_final, 2, ',', '.') ?></strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>