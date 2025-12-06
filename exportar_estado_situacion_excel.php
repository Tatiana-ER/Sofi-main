<?php
// ================== CONEXIÓN ==================
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
    $niveles_validos = [1, 2, 4, 6, 8, 10];
    
    // Crear todas las agrupaciones necesarias
    foreach ($array_cuentas as $cuenta) {
        $codigo = $cuenta['codigo'];
        $longitud_actual = strlen($codigo);
        
        foreach ($niveles_validos as $longitud) {
            if ($longitud < $longitud_actual) {
                $grupo = substr($codigo, 0, $longitud);
                
                if ($grupo != $codigo && !in_array($grupo, $cuentas_procesadas)) {
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
                }
            }
        }
    }
    
    // Calcular saldos sumando SOLO los hijos directos (del nivel inmediatamente inferior)
    // Procesar de mayor a menor nivel para acumular correctamente
    foreach (array_reverse($niveles_validos) as $nivel_actual) {
        // Para cada agrupación de este nivel
        foreach ($agrupaciones as $cod_grupo => &$grupo) {
            if ($grupo['nivel'] == $nivel_actual) {
                $nivel_hijo_esperado = null;
                
                // Determinar el nivel del hijo directo esperado
                foreach ($niveles_validos as $nv) {
                    if ($nv > $nivel_actual) {
                        $nivel_hijo_esperado = $nv;
                        break;
                    }
                }
                
                if ($nivel_hijo_esperado) {
                    // Sumar cuentas detalle del nivel hijo
                    foreach ($array_cuentas as $cuenta) {
                        if (strlen($cuenta['codigo']) == $nivel_hijo_esperado && 
                            strpos($cuenta['codigo'], $cod_grupo) === 0) {
                            $grupo['saldo'] += $cuenta['saldo'];
                            if ($mostrar_saldo_inicial) {
                                $grupo['saldo_inicial'] += $cuenta['saldo_inicial'];
                            }
                        }
                    }
                    
                    // Sumar agrupaciones del nivel hijo
                    foreach ($agrupaciones as $cod_hijo => $hijo) {
                        if ($hijo['nivel'] == $nivel_hijo_esperado && 
                            strpos($cod_hijo, $cod_grupo) === 0 && 
                            $cod_hijo != $cod_grupo) {
                            $grupo['saldo'] += $hijo['saldo'];
                            if ($mostrar_saldo_inicial) {
                                $grupo['saldo_inicial'] += $hijo['saldo_inicial'];
                            }
                        }
                    }
                }
            }
        }
        unset($grupo);
    }
    
    $resultado = array_merge(array_values($agrupaciones), $array_cuentas);
    
    usort($resultado, function($a, $b) {
        return strcmp($a['codigo'], $b['codigo']);
    });
    
    return $resultado;
}

// Aplicar agrupaciones a activos y pasivos
$activos = agregarAgrupaciones($activos, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);
$pasivos = agregarAgrupaciones($pasivos, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);

// ================== AGREGAR RESULTADO DEL EJERCICIO ANTES DE AGRUPACIONES ==================
$resultado_ejercicio = obtenerResultadoEjercicio($pdo, $fecha_desde, $fecha_hasta, $tercero);

if ($resultado_ejercicio != 0) {
    $cuenta_resultado = [
        'codigo' => ($resultado_ejercicio >= 0) ? '360501' : '361001',
        'nombre' => ($resultado_ejercicio >= 0) ? 'Utilidad del ejercicio' : 'Pérdida del ejercicio',
        'saldo_inicial' => 0,
        'saldo' => $resultado_ejercicio, // VALOR CON SIGNO (negativo si es pérdida)
        'nivel' => 6,
        'es_resultado' => true
    ];
    
    $patrimonios[] = $cuenta_resultado;
    $totalPatrimonios += $resultado_ejercicio;
    $cuentas_procesadas[] = $cuenta_resultado['codigo'];
}

// Ahora sí aplicar agrupaciones al patrimonio
$patrimonios = agregarAgrupaciones($patrimonios, $cuentas_procesadas, $nombres_cuentas, $mostrar_saldo_inicial);

// ================== GENERAR EXCEL ==================
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment;filename="Estado_Situacion_Financiera_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>';
echo 'td, th { border: 1px solid #ddd; padding: 4px; font-family: Arial, sans-serif; }';
echo '.centered { text-align: center; }';
echo '.right { text-align: right; }';
echo '.left { text-align: left; }';
echo '.bold { font-weight: bold; }';
echo '.total-row { background-color: #f8f9fa; font-weight: bold; }';
echo '.header-blue { background-color: #054a85; color: white; }';
echo '.section-header { background-color: #e3f2fd; }';
echo '.equilibrio-ok { background-color: #d1ecf1; }';
echo '.equilibrio-error { background-color: #f8d7da; }';
echo '.valor-negativo { color: #000000; }'; // MEJORA: Negro en lugar de rojo
echo '</style>';
echo '<!--[if gte mso 9]>';
echo '<xml>';
echo '<x:ExcelWorkbook>';
echo '<x:ExcelWorksheets>';
echo '<x:ExcelWorksheet>';
echo '<x:Name>Estado Situación Financiera</x:Name>';
echo '<x:WorksheetOptions>';
echo '<x:DisplayGridlines/>';
echo '<x:FitToPage/>';
echo '<x:Print>';
echo '<x:FitWidth>1</x:FitWidth>';
echo '<x:FitHeight>1</x:FitHeight>';
echo '</x:Print>';
echo '</x:WorksheetOptions>';
echo '</x:ExcelWorksheet>';
echo '</x:ExcelWorksheets>';
echo '</x:ExcelWorkbook>';
echo '</xml>';
echo '<![endif]-->';
echo '</head>';
echo '<body>';

echo '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%;">';

// TÍTULO PRINCIPAL Y DATOS DE EMPRESA (MEJORA SOLICITADA)
echo '<tr>';
echo '<th colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" class="header-blue" style="font-size: 16px; padding: 10px;">ESTADO DE SITUACIÓN FINANCIERA</th>';
echo '</tr>';

echo '<tr>';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" class="centered bold" style="background-color: #f0f0f0; padding: 8px;">';
echo 'NOMBRE DE LA EMPRESA: ' . htmlspecialchars($nombre_empresa, ENT_QUOTES, 'UTF-8');
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" class="centered bold" style="background-color: #f0f0f0; padding: 8px;">';
echo 'NIT DE LA EMPRESA: ' . htmlspecialchars($nit_empresa, ENT_QUOTES, 'UTF-8');
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" class="centered" style="background-color: #f0f0f0; padding: 8px;">';
echo 'PERÍODO: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta));
echo '</td>';
echo '</tr>';

echo '<tr><td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="padding: 10px;">&nbsp;</td></tr>';

// ACTIVOS
echo '<tr>';
echo '<th colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" class="section-header bold" style="padding: 8px;">ACTIVOS</th>';
echo '</tr>';

echo '<tr class="header-blue">';
echo '<th style="padding: 6px; width: 80px;">Código</th>';
echo '<th style="padding: 6px;">Nombre de la cuenta</th>';
if ($mostrar_saldo_inicial) {
    echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo Inicial</th>';
    echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo</th>';
} else {
    echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo</th>';
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

echo '<tr class="total-row">';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '2' : '2') . '" style="padding: 6px;">TOTAL ACTIVOS</td>';
if ($mostrar_saldo_inicial) {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalSaldoInicialActivos, 2, ',', '.') . '</td>';
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalActivos, 2, ',', '.') . '</td>';
} else {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalActivos, 2, ',', '.') . '</td>';
}
echo '</tr>';

echo '<tr><td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="padding: 15px;">&nbsp;</td></tr>';

// PASIVOS
echo '<tr>';
echo '<th colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" class="section-header bold" style="padding: 8px;">PASIVOS</th>';
echo '</tr>';

echo '<tr class="header-blue">';
echo '<th style="padding: 6px; width: 80px;">Código</th>';
echo '<th style="padding: 6px;">Nombre de la cuenta</th>';
if ($mostrar_saldo_inicial) {
    echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo Inicial</th>';
    echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo</th>';
} else {
    echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo</th>';
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

echo '<tr class="total-row">';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '2' : '2') . '" style="padding: 6px;">TOTAL PASIVOS</td>';
if ($mostrar_saldo_inicial) {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalSaldoInicialPasivos, 2, ',', '.') . '</td>';
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalPasivos, 2, ',', '.') . '</td>';
} else {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalPasivos, 2, ',', '.') . '</td>';
}
echo '</tr>';

echo '<tr><td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="padding: 15px;">&nbsp;</td></tr>';

// PATRIMONIO
echo '<tr>';
echo '<th colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" class="section-header bold" style="padding: 8px;">PATRIMONIO</th>';
echo '</tr>';

echo '<tr class="header-blue">';
echo '<th style="padding: 6px; width: 80px;">Código</th>';
echo '<th style="padding: 6px;">Nombre de la cuenta</th>';
if ($mostrar_saldo_inicial) {
    echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo Inicial</th>';
    echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo</th>';
} else {
    echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo</th>';
}
echo '</tr>';

foreach ($patrimonios as $fila) {
    $padding_left = ($fila['nivel'] - 1) * 15;
    // MEJORA: Valores negativos en negro, no rojo
    $color_style = 'color: #000000;'; // Siempre negro
    
    echo '<tr>';
    echo '<td style="padding: 5px;">' . htmlspecialchars($fila['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; padding-left: ' . $padding_left . 'px;">' . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . '</td>';
    if ($mostrar_saldo_inicial) {
        echo '<td style="padding: 5px; text-align: right; ' . $color_style . '">' . number_format($fila['saldo_inicial'], 2, ',', '.') . '</td>';
        echo '<td style="padding: 5px; text-align: right; ' . $color_style . '">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    } else {
        echo '<td style="padding: 5px; text-align: right; ' . $color_style . '">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    }
    echo '</tr>';
}

echo '<tr class="total-row">';
echo '<td colspan="' . ($mostrar_saldo_inicial ? '2' : '2') . '" style="padding: 6px;">TOTAL PATRIMONIO</td>';
if ($mostrar_saldo_inicial) {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalSaldoInicialPatrimonios, 2, ',', '.') . '</td>';
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalPatrimonios, 2, ',', '.') . '</td>';
} else {
    echo '<td style="padding: 6px; text-align: right;">' . number_format($totalPatrimonios, 2, ',', '.') . '</td>';
}
echo '</tr>';

echo '<tr><td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="padding: 15px;">&nbsp;</td></tr>';

// EQUILIBRIO CONTABLE
$total_pasivo_patrimonio = $totalPasivos + $totalPatrimonios;
$diferencia = $totalActivos - $total_pasivo_patrimonio;
$esta_equilibrado = abs($diferencia) < 0.01;

// MEJORA: Color acorde (no verde que sobresale)
$equilibrio_class = $esta_equilibrado ? 'equilibrio-ok' : 'equilibrio-error';

echo '<tr class="' . $equilibrio_class . ' bold">';
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
    echo '<tr class="equilibrio-error">';
    echo '<td colspan="' . ($mostrar_saldo_inicial ? '4' : '3') . '" style="padding: 6px; text-align: center;">Diferencia: ' . number_format($diferencia, 2, ',', '.') . '</td>';
    echo '</tr>';
}

echo '</table>';

// INFORMACIÓN ADICIONAL (MEJORA: añadir información del reporte)
echo '<br><br>';
echo '<table border="0" style="width: 100%; border-collapse: collapse; background-color: #f8f9fa;">';
echo '<tr>';
echo '<td colspan="2" style="padding: 5px; font-style: italic;"><strong>Información del Reporte:</strong></td>';
echo '</tr>';
echo '<tr>';
echo '<td style="padding: 3px;">Generado el:</td>';
echo '<td style="padding: 3px;">' . date('Y-m-d H:i:s') . '</td>';
echo '</tr>';
echo '<tr>';
echo '<td style="padding: 3px;">Período fiscal:</td>';
echo '<td style="padding: 3px;">' . date('Y', strtotime($fecha_desde)) . '</td>';
echo '</tr>';
echo '</table>';

echo '</body></html>';
?>