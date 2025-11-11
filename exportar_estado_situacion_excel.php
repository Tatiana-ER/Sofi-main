<?php
// ================== CONEXIÓN ==================
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== FILTROS ==================
$periodo_fiscal = isset($_GET['periodo_fiscal']) ? $_GET['periodo_fiscal'] : date('Y');
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';
$mostrar_saldo_inicial = isset($_GET['mostrar_saldo_inicial']) ? $_GET['mostrar_saldo_inicial'] === '1' : false;

// ================== FUNCIÓN PARA CALCULAR SALDOS POR CUENTA ==================
function calcularSaldoCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '', $calcular_saldo_inicial = false) {
    if ($calcular_saldo_inicial) {
        $ano_fiscal = date('Y', strtotime($fecha_desde));
        $fecha_inicio_saldo_inicial = $ano_fiscal . '-01-01';
        $fecha_fin_saldo_inicial = date('Y-m-d', strtotime($fecha_desde . ' -1 day'));
        
        $sql = "SELECT 
                    COALESCE(SUM(debito), 0) as total_debito,
                    COALESCE(SUM(credito), 0) as total_credito
                FROM libro_diario 
                WHERE codigo_cuenta = :cuenta 
                  AND fecha BETWEEN :desde AND :hasta";
        
        $params = [
            ':cuenta' => $codigo_cuenta, 
            ':desde' => $fecha_inicio_saldo_inicial, 
            ':hasta' => $fecha_fin_saldo_inicial
        ];
    } else {
        $sql = "SELECT 
                    COALESCE(SUM(debito), 0) as total_debito,
                    COALESCE(SUM(credito), 0) as total_credito
                FROM libro_diario 
                WHERE codigo_cuenta = :cuenta 
                  AND fecha BETWEEN :desde AND :hasta";
        
        $params = [
            ':cuenta' => $codigo_cuenta, 
            ':desde' => $fecha_desde, 
            ':hasta' => $fecha_hasta
        ];
    }
    
    if ($tercero != '') {
        $sql .= " AND tercero_identificacion = :tercero";
        $params[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ================== OBTENER NOMBRES DE CUENTAS ==================
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

// ================== OBTENER RESULTADO DEL EJERCICIO ==================
function obtenerResultadoEjercicio($pdo, $fecha_desde, $fecha_hasta, $tercero = '') {
    $sql_ingresos = "SELECT COALESCE(SUM(credito - debito), 0) as saldo 
                     FROM libro_diario 
                     WHERE SUBSTRING(codigo_cuenta, 1, 1) = '4' 
                     AND fecha BETWEEN :desde AND :hasta";
    
    $params_ingresos = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_ingresos .= " AND tercero_identificacion = :tercero";
        $params_ingresos[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql_ingresos);
    $stmt->execute($params_ingresos);
    $ingresos = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
    
    $sql_costos = "SELECT COALESCE(SUM(debito - credito), 0) as saldo 
                   FROM libro_diario 
                   WHERE SUBSTRING(codigo_cuenta, 1, 1) = '6' 
                   AND fecha BETWEEN :desde AND :hasta";
    
    $params_costos = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_costos .= " AND tercero_identificacion = :tercero";
        $params_costos[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql_costos);
    $stmt->execute($params_costos);
    $costos = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
    
    $sql_gastos = "SELECT COALESCE(SUM(debito - credito), 0) as saldo 
                   FROM libro_diario 
                   WHERE SUBSTRING(codigo_cuenta, 1, 1) = '5' 
                   AND fecha BETWEEN :desde AND :hasta";
    
    $params_gastos = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_gastos .= " AND tercero_identificacion = :tercero";
        $params_gastos[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql_gastos);
    $stmt->execute($params_gastos);
    $gastos = $stmt->fetch(PDO::FETCH_ASSOC)['saldo'];
    
    return $ingresos - $costos - $gastos;
}

// Obtener nombres de cuentas
$nombres_cuentas = obtenerNombresCuentas($pdo);

// ================== OBTENER DATOS ==================
$sql_cuentas = "SELECT DISTINCT 
                    codigo_cuenta, 
                    nombre_cuenta,
                    SUBSTRING(codigo_cuenta, 1, 1) as clase
                FROM libro_diario 
                WHERE fecha BETWEEN :desde AND :hasta
                  AND SUBSTRING(codigo_cuenta, 1, 1) IN ('1', '2', '3')";

$params_cuentas = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($cuenta_codigo != '') {
    $sql_cuentas .= " AND codigo_cuenta = :cuenta";
    $params_cuentas[':cuenta'] = $cuenta_codigo;
}

if ($tercero != '') {
    $sql_cuentas .= " AND tercero_identificacion = :tercero";
    $params_cuentas[':tercero'] = $tercero;
}

$sql_cuentas .= " ORDER BY clase, codigo_cuenta";

$stmt = $pdo->prepare($sql_cuentas);
$stmt->execute($params_cuentas);
$todas_cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== PROCESAR DATOS ==================
$activos = []; $pasivos = []; $patrimonios = [];
$totalActivos = 0; $totalPasivos = 0; $totalPatrimonios = 0;
$totalSaldoInicialActivos = 0; $totalSaldoInicialPasivos = 0; $totalSaldoInicialPatrimonios = 0;

$cuentas_procesadas = [];

foreach ($todas_cuentas as $cuenta) {
    $codigo = $cuenta['codigo_cuenta'];
    $clase = $cuenta['clase'];
    
    $movimientos = calcularSaldoCuenta($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero);
    $debito = floatval($movimientos['total_debito']);
    $credito = floatval($movimientos['total_credito']);
    
    $saldo_inicial = 0;
    if ($mostrar_saldo_inicial) {
        $movimientos_inicial = calcularSaldoCuenta($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero, true);
        $debito_inicial = floatval($movimientos_inicial['total_debito']);
        $credito_inicial = floatval($movimientos_inicial['total_credito']);
        
        if ($clase == '1') {
            $saldo_inicial = $debito_inicial - $credito_inicial;
        } else {
            $saldo_inicial = $credito_inicial - $debito_inicial;
        }
    }
    
    $saldo = 0;
    if ($clase == '1') {
        $saldo = $debito - $credito;
    } else {
        $saldo = $credito - $debito;
    }
    
    if ($saldo != 0 || $saldo_inicial != 0) {
        $nombre_cuenta = isset($nombres_cuentas[$codigo]) ? $nombres_cuentas[$codigo] : $cuenta['nombre_cuenta'];
        
        $item = [
            'codigo' => $codigo,
            'nombre' => $nombre_cuenta,
            'saldo_inicial' => $saldo_inicial,
            'saldo' => $saldo,
            'nivel' => strlen($codigo)
        ];
        
        if ($clase == '1') {
            $activos[] = $item;
            $totalActivos += $saldo;
            $totalSaldoInicialActivos += $saldo_inicial;
        } elseif ($clase == '2') {
            $pasivos[] = $item;
            $totalPasivos += $saldo;
            $totalSaldoInicialPasivos += $saldo_inicial;
        } elseif ($clase == '3') {
            $patrimonios[] = $item;
            $totalPatrimonios += $saldo;
            $totalSaldoInicialPatrimonios += $saldo_inicial;
        }
        
        $cuentas_procesadas[] = $codigo;
    }
}

// ================== AGREGAR AGRUPACIONES ==================
function agregarAgrupaciones(&$array_cuentas, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial = false) {
    $agrupaciones = [];
    
    // Niveles válidos: 1, 2, 4, 6, 8, 10 dígitos
    $niveles_validos = [1, 2, 4, 6, 8, 10];
    
    foreach ($array_cuentas as $cuenta) {
        $codigo = $cuenta['codigo'];
        $longitud_actual = strlen($codigo);
        
        // Generar códigos de agrupación solo para los niveles válidos
        foreach ($niveles_validos as $longitud) {
            // Solo generar agrupaciones para niveles superiores al actual
            if ($longitud < $longitud_actual) {
                $grupo = substr($codigo, 0, $longitud);
                
                // Verificar que no sea la cuenta actual y que no esté ya procesada
                if ($grupo != $codigo && !in_array($grupo, $cuentas_procesadas)) {
                    // Usar el nombre de la tabla cuentas_contables si existe
                    $nombre = isset($nombres_cuentas[$grupo]) ? $nombres_cuentas[$grupo] : 'Grupo ' . $grupo;
                    
                    if (!isset($agrupaciones[$grupo])) {
                        $agrupaciones[$grupo] = [
                            'codigo' => $grupo,
                            'nombre' => $nombre,
                            'saldo_inicial' => 0,
                            'saldo' => 0,
                            'nivel' => strlen($grupo),
                            'es_grupo' => true
                        ];
                        $cuentas_procesadas[] = $grupo;
                    }
                    $agrupaciones[$grupo]['saldo'] += $cuenta['saldo'];
                    if ($mostrar_saldo_inicial) {
                        $agrupaciones[$grupo]['saldo_inicial'] += $cuenta['saldo_inicial'];
                    }
                }
            }
        }
    }
    
    // Fusionar agrupaciones con cuentas detalle
    $resultado = array_merge(array_values($agrupaciones), $array_cuentas);
    
    // Ordenar por código
    usort($resultado, function($a, $b) {
        return strcmp($a['codigo'], $b['codigo']);
    });
    
    return $resultado;
}

// Aplicar agrupaciones
$activos = agregarAgrupaciones($activos, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);
$pasivos = agregarAgrupaciones($pasivos, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);
$patrimonios = agregarAgrupaciones($patrimonios, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);

// Agregar resultado del ejercicio
$resultado_ejercicio = obtenerResultadoEjercicio($pdo, $fecha_desde, $fecha_hasta, $tercero);
if ($resultado_ejercicio != 0) {
    $patrimonios[] = [
        'codigo' => ($resultado_ejercicio >= 0) ? '360501' : '361001',
        'nombre' => ($resultado_ejercicio >= 0) ? 'Utilidad del ejercicio' : 'Pérdida del ejercicio',
        'saldo_inicial' => 0,
        'saldo' => abs($resultado_ejercicio),
        'nivel' => 6
    ];
    $totalPatrimonios += $resultado_ejercicio;
}

// ================== GENERAR EXCEL ==================
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment;filename="Estado_Situacion_Financiera_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<!--[if gte mso 9]>';
echo '<xml>';
echo '<x:ExcelWorkbook>';
echo '<x:ExcelWorksheets>';
echo '<x:ExcelWorksheet>';
echo '<x:Name>Estado Situación Financiera</x:Name>';
echo '<x:WorksheetOptions>';
echo '<x:DisplayGridlines/>';
echo '</x:WorksheetOptions>';
echo '</x:ExcelWorksheet>';
echo '</x:ExcelWorksheets>';
echo '</x:ExcelWorkbook>';
echo '</xml>';
echo '<![endif]-->';
echo '</head>';
echo '<body>';

echo '<table border="1" style="border-collapse: collapse;">';
echo '<tr><th colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="background-color: #054a85; color: white; font-size: 16px; padding: 10px;">ESTADO DE SITUACIÓN FINANCIERA</th></tr>';
echo '<tr><th colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="background-color: #f0f0f0; padding: 8px;">Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta)) . '</th></tr>';
echo '<tr><td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '">&nbsp;</td></tr>';

// ACTIVOS
echo '<tr><th colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="background-color: #e3f2fd; padding: 8px;">ACTIVOS</th></tr>';
echo '<tr style="background-color: #054a85; color: white;">';
echo '<th style="padding: 6px;">Código</th>';
echo '<th style="padding: 6px;">Nombre de la cuenta</th>';
if ($mostrar_saldo_inicial) {
    echo '<th style="padding: 6px; text-align: right;">Saldo Inicial</th>';
    echo '<th style="padding: 6px; text-align: right;">Saldo</th>';
} else {
    echo '<th style="padding: 6px; text-align: right;">Saldo</th>';
}
echo '</tr>';

foreach ($activos as $fila) {
    $padding_left = ($fila['nivel'] - 1) * 15;
    echo '<tr>';
    echo '<td style="padding: 5px;">' . htmlspecialchars($fila['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; padding-left: ' . $padding_left . 'px;">' . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . '</td>';
    if ($mostrar_saldo_inicial) {
        echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo_inicial'], 2, ',', '.') . '</td>';
        echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    } else {
        echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    }
    echo '</tr>';
}

echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '2' : '2') . '" style="padding: 6px;">TOTAL ACTIVOS</td>';
if ($mostrar_saldo_inicial) {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalSaldoInicialActivos, 2, ',', '.') . '</td>';
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalActivos, 2, ',', '.') . '</td>';
} else {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalActivos, 2, ',', '.') . '</td>';
}
echo '</tr>';

echo '<tr><td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '">&nbsp;</td></tr>';

// PASIVOS
echo '<tr><th colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="background-color: #e3f2fd; padding: 8px;">PASIVOS</th></tr>';
echo '<tr style="background-color: #054a85; color: white;">';
echo '<th style="padding: 6px;">Código</th>';
echo '<th style="padding: 6px;">Nombre de la cuenta</th>';
if ($mostrar_saldo_inicial) {
    echo '<th style="padding: 6px; text-align: right;">Saldo Inicial</th>';
    echo '<th style="padding: 6px; text-align: right;">Saldo</th>';
} else {
    echo '<th style="padding: 6px; text-align: right;">Saldo</th>';
}
echo '</tr>';

foreach ($pasivos as $fila) {
    $padding_left = ($fila['nivel'] - 1) * 15;
    echo '<tr>';
    echo '<td style="padding: 5px;">' . htmlspecialchars($fila['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; padding-left: ' . $padding_left . 'px;">' . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . '</td>';
    if ($mostrar_saldo_inicial) {
        echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo_inicial'], 2, ',', '.') . '</td>';
        echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    } else {
        echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    }
    echo '</tr>';
}

echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '2' : '2') . '" style="padding: 6px;">TOTAL PASIVOS</td>';
if ($mostrar_saldo_inicial) {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalSaldoInicialPasivos, 2, ',', '.') . '</td>';
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalPasivos, 2, ',', '.') . '</td>';
} else {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalPasivos, 2, ',', '.') . '</td>';
}
echo '</tr>';

echo '<tr><td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '">&nbsp;</td></tr>';

// PATRIMONIO
echo '<tr><th colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="background-color: #e3f2fd; padding: 8px;">PATRIMONIO</th></tr>';
echo '<tr style="background-color: #054a85; color: white;">';
echo '<th style="padding: 6px;">Código</th>';
echo '<th style="padding: 6px;">Nombre de la cuenta</th>';
if ($mostrar_saldo_inicial) {
    echo '<th style="padding: 6px; text-align: right;">Saldo Inicial</th>';
    echo '<th style="padding: 6px; text-align: right;">Saldo</th>';
} else {
    echo '<th style="padding: 6px; text-align: right;">Saldo</th>';
}
echo '</tr>';

foreach ($patrimonios as $fila) {
    $padding_left = ($fila['nivel'] - 1) * 15;
    echo '<tr>';
    echo '<td style="padding: 5px;">' . htmlspecialchars($fila['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; padding-left: ' . $padding_left . 'px;">' . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . '</td>';
    if ($mostrar_saldo_inicial) {
        echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo_inicial'], 2, ',', '.') . '</td>';
        echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    } else {
        echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    }
    echo '</tr>';
}

echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '2' : '2') . '" style="padding: 6px;">TOTAL PATRIMONIO</td>';
if ($mostrar_saldo_inicial) {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalSaldoInicialPatrimonios, 2, ',', '.') . '</td>';
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalPatrimonios, 2, ',', '.') . '</td>';
} else {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalPatrimonios, 2, ',', '.') . '</td>';
}
echo '</tr>';

echo '<tr><td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '">&nbsp;</td></tr>';

// EQUILIBRIO CONTABLE
$total_pasivo_patrimonio = $totalPasivos + $totalPatrimonios;
$diferencia = $totalActivos - $total_pasivo_patrimonio;
$esta_equilibrado = abs($diferencia) < 0.01;

echo '<tr style="background-color: ' . ($esta_equilibrado ? '#d4edda' : '#f8d7da') . '; font-weight: bold;">';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '2' : '2') . '" style="padding: 8px;">';
echo $esta_equilibrado ? '✓ ACTIVOS = PASIVOS + PATRIMONIO' : '✗ DESEQUILIBRIO CONTABLE';
echo '</td>';
if ($mostrar_saldo_inicial) {
    echo '<td style="padding: 8px; text-align: right;">' . number_format($totalSaldoInicialActivos, 2, ',', '.') . ' = ' . number_format($totalSaldoInicialPasivos + $totalSaldoInicialPatrimonios, 2, ',', '.') . '</td>';
    echo '<td style="padding: 8px; text-align: right;">' . number_format($totalActivos, 2, ',', '.') . ' = ' . number_format($total_pasivo_patrimonio, 2, ',', '.') . '</td>';
} else {
    echo '<td style="padding: 8px; text-align: right;">' . number_format($totalActivos, 2, ',', '.') . ' = ' . number_format($total_pasivo_patrimonio, 2, ',', '.') . '</td>';
}
echo '</tr>';

if (!$esta_equilibrado) {
    echo '<tr style="background-color: #f8d7da; color: #721c24;">';
    echo '<td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="padding: 6px; text-align: center;">Diferencia: ' . number_format($diferencia, 2, ',', '.') . '</td>';
    echo '</tr>';
}

echo '</table>';

echo '<br><br>';
echo '<table border="0" style="width: 100%;">';
echo '<tr>';
echo '<td width="50%" style="text-align: center;">________________________<br>CONTADOR PÚBLICO</td>';
echo '<td width="50%" style="text-align: center;">________________________<br>REPRESENTANTE LEGAL</td>';
echo '</tr>';
echo '</table>';

echo '</body></html>';
?>