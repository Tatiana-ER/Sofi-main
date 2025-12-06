<?php
// ================== EXPORTAR BALANCE DE PRUEBA A EXCEL ==================
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

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
$periodo_fiscal = isset($_GET['periodo_fiscal']) ? $_GET['periodo_fiscal'] : date('Y');
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';
$tipo_cuenta = isset($_GET['tipo_cuenta']) ? $_GET['tipo_cuenta'] : '';
$mostrar_saldo_inicial = isset($_GET['mostrar_saldo_inicial']) && $_GET['mostrar_saldo_inicial'] == '1' ? '1' : '0';

// ================== OBTENER NOMBRES DE CUENTAS REALES ==================
function obtenerNombresCuentas($pdo) {
    $sql = "SELECT nivel1, nivel2, nivel3, nivel4, nivel5, nivel6 FROM cuentas_contables";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nombres = [];
    foreach ($resultados as $fila) {
        for ($i = 1; $i <= 6; $i++) {
            $campo = 'nivel' . $i;
            if (!empty($fila[$campo])) {
                $partes = explode('-', $fila[$campo], 2);
                if (count($partes) == 2) {
                    $codigo = trim($partes[0]);
                    $nombre = trim($partes[1]);
                    $nombres[$codigo] = $nombre;
                }
            }
        }
    }
    return $nombres;
}

$nombres_cuentas = obtenerNombresCuentas($pdo);

// ================== FUNCIÓN PARA CALCULAR MOVIMIENTOS ==================
function calcularMovimientos($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    $naturaleza = substr($codigo_cuenta, 0, 1);
    
    $sql_inicial = "
        SELECT 
            COALESCE(SUM(debito), 0) as suma_debito,
            COALESCE(SUM(credito), 0) as suma_credito
        FROM libro_diario 
        WHERE codigo_cuenta = :cuenta
          AND fecha < :desde
    ";
    
    $params_ini = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde];
    
    if ($tercero != '') {
        $sql_inicial .= " AND (
            tercero_identificacion = :tercero
            OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
        )";
        $params_ini[':tercero'] = $tercero;
        $params_ini[':tercero_con_guion'] = $tercero . ' -';
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
    
    $sql_mov = "
        SELECT 
            COALESCE(SUM(debito), 0) as mov_debito,
            COALESCE(SUM(credito), 0) as mov_credito
        FROM libro_diario 
        WHERE codigo_cuenta = :cuenta
          AND fecha BETWEEN :desde AND :hasta
    ";
    
    $params_mov = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_mov .= " AND (
            tercero_identificacion = :tercero
            OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
        )";
        $params_mov[':tercero'] = $tercero;
        $params_mov[':tercero_con_guion'] = $tercero . ' -';
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

// ================== FUNCIÓN PARA CALCULAR MOVIMIENTOS DE SUBCUENTAS ==================
function calcularMovimientosConSubcuentas($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    $naturaleza = substr($codigo_cuenta, 0, 1);
    $patron_subcuentas = $codigo_cuenta . '%';
    
    $sql_inicial = "
        SELECT 
            COALESCE(SUM(debito), 0) as suma_debito,
            COALESCE(SUM(credito), 0) as suma_credito
        FROM libro_diario 
        WHERE codigo_cuenta LIKE :patron
          AND fecha < :desde
    ";
    
    $params_ini = [':patron' => $patron_subcuentas, ':desde' => $fecha_desde];
    
    if ($tercero != '') {
        $sql_inicial .= " AND (
            tercero_identificacion = :tercero
            OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
        )";
        $params_ini[':tercero'] = $tercero;
        $params_ini[':tercero_con_guion'] = $tercero . ' -';
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
    
    $sql_mov = "
        SELECT 
            COALESCE(SUM(debito), 0) as mov_debito,
            COALESCE(SUM(credito), 0) as mov_credito
        FROM libro_diario 
        WHERE codigo_cuenta LIKE :patron
          AND fecha BETWEEN :desde AND :hasta
    ";
    
    $params_mov = [':patron' => $patron_subcuentas, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_mov .= " AND (
            tercero_identificacion = :tercero
            OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
        )";
        $params_mov[':tercero'] = $tercero;
        $params_mov[':tercero_con_guion'] = $tercero . ' -';
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

// ================== OBTENER TODAS LAS CUENTAS CON MOVIMIENTOS ==================
$sql_cuentas_base = "
    SELECT DISTINCT codigo_cuenta, nombre_cuenta
    FROM libro_diario
    WHERE fecha BETWEEN :desde AND :hasta
";

$params_base = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($cuenta_codigo != '') {
    $sql_cuentas_base .= " AND codigo_cuenta = :cuenta";
    $params_base[':cuenta'] = $cuenta_codigo;
}

if ($tercero != '') {
    $sql_cuentas_base .= " AND (
        tercero_identificacion = :tercero
        OR tercero_identificacion LIKE CONCAT(:tercero_con_guion, '%')
    )";
    $params_base[':tercero'] = $tercero;
    $params_base[':tercero_con_guion'] = $tercero . ' -';
}

$sql_cuentas_base .= " ORDER BY codigo_cuenta";
$stmt_base = $pdo->prepare($sql_cuentas_base);
$stmt_base->execute($params_base);
$cuentas_con_movimientos = $stmt_base->fetchAll(PDO::FETCH_ASSOC);

// ================== CONSTRUIR ESTRUCTURA SEGÚN FILTRO ==================
$cuentas_completas = [];
$codigos_procesados = [];

if (!empty($tipo_cuenta)) {
    $tipo_cuenta_int = intval($tipo_cuenta);
    
    $cuentas_padre = [];
    
    foreach ($cuentas_con_movimientos as $cuenta) {
        $codigo_completo = $cuenta['codigo_cuenta'];
        
        if (strlen($codigo_completo) >= $tipo_cuenta_int) {
            $codigo_nivel = substr($codigo_completo, 0, $tipo_cuenta_int);
            
            if (!isset($cuentas_padre[$codigo_nivel])) {
                $cuentas_padre[$codigo_nivel] = true;
            }
        }
    }
    
    foreach ($cuentas_padre as $codigo_padre => $dummy) {
        $nombre = isset($nombres_cuentas[$codigo_padre]) ? 
                  $nombres_cuentas[$codigo_padre] : 
                  'Cuenta ' . $codigo_padre;
        
        $movs = calcularMovimientosConSubcuentas($pdo, $codigo_padre, $fecha_desde, $fecha_hasta, $tercero);
        
        if ($movs['debito'] != 0 || $movs['credito'] != 0 || $movs['saldo_inicial'] != 0) {
            $cuentas_completas[] = [
                'codigo' => $codigo_padre,
                'nombre' => $nombre,
                'saldo_inicial' => $movs['saldo_inicial'],
                'debito' => $movs['debito'],
                'credito' => $movs['credito'],
                'saldo_final' => $movs['saldo_final'],
                'nivel' => strlen($codigo_padre)
            ];
        }
    }
    
    usort($cuentas_completas, function($a, $b) {
        return strcmp($a['codigo'], $b['codigo']);
    });
    
} else {
    foreach ($cuentas_con_movimientos as $cuenta) {
        $codigo = $cuenta['codigo_cuenta'];

        $nombre = isset($nombres_cuentas[$codigo]) ? $nombres_cuentas[$codigo] : $cuenta['nombre_cuenta'];

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
                $nombre_nivel = isset($nombres_cuentas[$nivel_codigo]) ?
                               $nombres_cuentas[$nivel_codigo] :
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
}

// ================== CALCULAR TOTALES GENERALES ==================
$total_saldo_inicial = 0;
$total_debito = 0;
$total_credito = 0;
$total_saldo_final = 0;

foreach ($cuentas_completas as $cuenta) {
    if ($cuenta['nivel'] == 1 || !empty($tipo_cuenta)) {
        $total_saldo_inicial += $cuenta['saldo_inicial'];
        $total_debito += $cuenta['debito'];
        $total_credito += $cuenta['credito'];
        $total_saldo_final += $cuenta['saldo_final'];
    }
}

// ================== GENERAR EXCEL MEJORADO ==================
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
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 10px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 6px 4px; text-align: left; vertical-align: top; }
        th { background-color: #054a85; color: white; font-weight: bold; font-size: 12px; }
        .nivel-1 { background-color: #e3f2fd; font-weight: bold; font-size: 13px; }
        .nivel-2 { background-color: #f1f8ff; font-weight: bold; font-size: 12px; }
        .nivel-4 { font-weight: 600; font-size: 12px; }
        .nivel-6, .nivel-8, .nivel-10 { font-size: 11px; }
        .total { background-color: #054a85; color: white; font-weight: bold; font-size: 13px; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .header-info { text-align: center; margin-bottom: 15px; }
        .empresa-info { margin-bottom: 15px; }
        .filtros-info { margin-bottom: 10px; font-size: 11px; color: #333; }
        .codigo-col { width: 80px; }
        .nombre-col { width: 350px; word-wrap: break-word; }
        .monto-col { width: 100px; }
        .sangria-0 { padding-left: 5px; }
        .sangria-1 { padding-left: 15px; }
        .sangria-2 { padding-left: 25px; }
        .sangria-3 { padding-left: 35px; }
        .sangria-4 { padding-left: 45px; }
        .sangria-5 { padding-left: 55px; }
        .info-footer { margin-top: 20px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header-info">
        <h2 style="margin: 0; font-size: 18px; color: #054a85;">BALANCE DE PRUEBA GENERAL</h2>
        
        <div class="empresa-info">
            <div style="margin-bottom: 8px;">
                <strong style="font-size: 13px;">NOMBRE DE LA EMPRESA:</strong><br>
                <span style="font-size: 13px;"><?= htmlspecialchars($nombre_empresa) ?></span>
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong>NIT DE LA EMPRESA:</strong><br>
                <!-- Prefijar con un apóstrofe para forzar texto en Excel -->
                <span>'<?= htmlspecialchars($nit_empresa) ?></span>
            </div>
            
            <div style="margin-bottom: 5px;">
                <strong style="font-size: 13px;">PERIODO:</strong> 
                <span style="font-size: 13px;"><?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Información de filtros -->
    <div class="filtros-info">
        <?php if ($cuenta_codigo != ''): ?>
            <div><strong>Cuenta filtrada:</strong> <?= htmlspecialchars($cuenta_codigo) ?></div>
        <?php endif; ?>
        <?php if ($tercero != ''): ?>
            <div><strong>Tercero filtrado:</strong> <?= htmlspecialchars($tercero) ?></div>
        <?php endif; ?>
        <?php if ($tipo_cuenta != ''): ?>
            <div><strong>Nivel de cuenta:</strong> <?= htmlspecialchars($tipo_cuenta) ?> dígitos</div>
        <?php endif; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th class="codigo-col">Código cuenta</th>
                <th class="nombre-col">Nombre cuenta contable</th>
                <?php if ($mostrar_saldo_inicial == '1'): ?>
                <th class="monto-col">Saldo inicial</th>
                <?php endif; ?>
                <th class="monto-col">Movimiento débito</th>
                <th class="monto-col">Movimiento crédito</th>
                <th class="monto-col">Saldo final</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cuentas_completas as $cuenta): ?>
            <?php
                $sangria_class = 'sangria-' . min($cuenta['nivel'] - 1, 5);
                $clase_nivel = 'nivel-' . $cuenta['nivel'];
                
                // Aplicar sangría al nombre (sin espacios extra)
                $nombre_con_sangria = str_repeat('  ', min($cuenta['nivel'] - 1, 5)) . $cuenta['nombre'];
            ?>
            <tr class="<?= $clase_nivel ?>">
                <td style="mso-number-format:'\@';"><?= htmlspecialchars($cuenta['codigo']) ?></td>
                <td class="text-left" style="word-wrap: break-word;"><?= htmlspecialchars($nombre_con_sangria) ?></td>
                <?php if ($mostrar_saldo_inicial == '1'): ?>
                <td class="text-right"><?= number_format($cuenta['saldo_inicial'], 2, ',', '.') ?></td>
                <?php endif; ?>
                <td class="text-right"><?= number_format($cuenta['debito'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format($cuenta['credito'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format($cuenta['saldo_final'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            
            <!-- Totales generales -->
            <tr class="total">
                <td colspan="<?= $mostrar_saldo_inicial == '1' ? '2' : '2' ?>"><strong>TOTALES</strong></td>
                <?php if ($mostrar_saldo_inicial == '1'): ?>
                <td class="text-right"><strong><?= number_format($total_saldo_inicial, 2, ',', '.') ?></strong></td>
                <?php endif; ?>
                <td class="text-right"><strong><?= number_format($total_debito, 2, ',', '.') ?></strong></td>
                <td class="text-right"><strong><?= number_format($total_credito, 2, ',', '.') ?></strong></td>
                <td class="text-right"><strong><?= number_format($total_saldo_final, 2, ',', '.') ?></strong></td>
            </tr>
        </tbody>
    </table>
    
    <!-- Información adicional -->
    <div class="info-footer">
        <p><strong>Información del Reporte:</strong></p>
        <p>Generado el: <?= date('Y-m-d H:i:s') ?></p>
        <p>Total de cuentas: <?= count($cuentas_completas) ?></p>
        <p>Exportado desde: Software Financiero SOFI</p>
    </div>
</body>
</html>