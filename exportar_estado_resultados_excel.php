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

// ================== FUNCIÓN PARA CONVERTIR TEXTO ==================
function convertir_texto($texto) {
    return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
}

// ================== FUNCIÓN PARA CALCULAR SALDOS POR CUENTA ==================
function calcularSaldoCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
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
    
    if ($tercero != '') {
        $sql .= " AND tercero_identificacion = :tercero";
        $params[':tercero'] = $tercero;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ================== OBTENER NOMBRES DE CUENTAS DESDE LA TABLA cuentas_contables ==================
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

// ================== OBTENER TODAS LAS CUENTAS DE INGRESOS (4), COSTOS (6) Y GASTOS (5) ==================
$sql_cuentas = "SELECT DISTINCT 
                    codigo_cuenta, 
                    nombre_cuenta,
                    SUBSTRING(codigo_cuenta, 1, 1) as clase
                FROM libro_diario 
                WHERE fecha BETWEEN :desde AND :hasta
                  AND SUBSTRING(codigo_cuenta, 1, 1) IN ('4', '5', '6')";

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

// ================== SEPARAR Y CALCULAR POR TIPO ==================
$ingresos = [];
$costos = [];
$gastos = [];
$totalIngresos = 0;
$totalCostos = 0;
$totalGastos = 0;

$cuentas_procesadas = [];

foreach ($todas_cuentas as $cuenta) {
    $codigo = $cuenta['codigo_cuenta'];
    $clase = $cuenta['clase'];
    
    $movimientos = calcularSaldoCuenta($pdo, $codigo, $fecha_desde, $fecha_hasta, $tercero);
    $debito = floatval($movimientos['total_debito']);
    $credito = floatval($movimientos['total_credito']);
    
    $saldo = 0;
    if ($clase == '4') {
        $saldo = $credito - $debito;
    } else {
        $saldo = $debito - $credito;
    }
    
    if ($saldo != 0) {
        $nombre_cuenta = isset($nombres_cuentas[$codigo]) ? $nombres_cuentas[$codigo] : $cuenta['nombre_cuenta'];
        
        $item = [
            'codigo' => $codigo,
            'nombre' => $nombre_cuenta,
            'saldo' => $saldo,
            'nivel' => strlen($codigo)
        ];
        
        if ($clase == '4') {
            $ingresos[] = $item;
            $totalIngresos += $saldo;
        } elseif ($clase == '6') {
            $costos[] = $item;
            $totalCostos += $saldo;
        } elseif ($clase == '5') {
            $gastos[] = $item;
            $totalGastos += $saldo;
        }
        
        $cuentas_procesadas[] = $codigo;
    }
}

// ================== AGREGAR AGRUPACIONES SUPERIORES ==================
function agregarAgrupaciones(&$array_cuentas, $cuentas_procesadas, $nombres_cuentas) {
    $agrupaciones = [];
    $niveles_validos = [1, 2, 4, 6, 8, 10];
    
    // Primero: identificar todas las agrupaciones necesarias
    $cuentas_con_saldo = [];
    foreach ($array_cuentas as $cuenta) {
        $cuentas_con_saldo[$cuenta['codigo']] = $cuenta;
    }
    
    foreach ($array_cuentas as $cuenta) {
        $codigo = $cuenta['codigo'];
        $longitud_actual = strlen($codigo);
        
        foreach ($niveles_validos as $longitud) {
            if ($longitud < $longitud_actual) {
                $grupo = substr($codigo, 0, $longitud);
                
                if (!isset($cuentas_con_saldo[$grupo]) && !in_array($grupo, $cuentas_procesadas)) {
                    $nombre = isset($nombres_cuentas[$grupo]) ? $nombres_cuentas[$grupo] : 'Grupo ' . $grupo;
                    
                    if (!isset($agrupaciones[$grupo])) {
                        $agrupaciones[$grupo] = [
                            'codigo' => $grupo,
                            'nombre' => $nombre,
                            'saldo' => 0,
                            'nivel' => strlen($grupo),
                            'es_grupo' => true
                        ];
                    }
                }
            }
        }
    }
    
    // Segundo: acumular saldos de niveles inferiores a superiores
    foreach ($niveles_validos as $nivel) {
        foreach (array_reverse($niveles_validos) as $nivel_hijo) {
            if ($nivel_hijo > $nivel) {
                foreach (array_merge($array_cuentas, array_values($agrupaciones)) as $item) {
                    if (strlen($item['codigo']) == $nivel_hijo) {
                        $codigo_padre = substr($item['codigo'], 0, $nivel);
                        
                        if (isset($agrupaciones[$codigo_padre])) {
                            $agrupaciones[$codigo_padre]['saldo'] += $item['saldo'];
                        }
                    }
                }
            }
        }
    }
    
    foreach ($agrupaciones as $codigo => $grupo) {
        $cuentas_procesadas[] = $codigo;
    }
    
    $resultado = array_merge(array_values($agrupaciones), $array_cuentas);
    
    usort($resultado, function($a, $b) {
        return strcmp($a['codigo'], $b['codigo']);
    });
    
    return $resultado;
}

$ingresos = agregarAgrupaciones($ingresos, $cuentas_procesadas, $nombres_cuentas);
$costos = agregarAgrupaciones($costos, $cuentas_procesadas, $nombres_cuentas);
$gastos = agregarAgrupaciones($gastos, $cuentas_procesadas, $nombres_cuentas);

// ================== RESULTADO DEL EJERCICIO ==================
$resultado_ejercicio = $totalIngresos - $totalCostos - $totalGastos;
$utilidad_bruta = $totalIngresos - $totalCostos;
$utilidad_operacional = $utilidad_bruta - $totalGastos;

// ================== CONFIGURACIÓN EXCEL ==================
header('Content-Type: application/vnd.ms-excel; charset=ISO-8859-1');
header('Content-Disposition: attachment;filename="estado_resultados_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');

// ================== GENERAR EXCEL ==================
echo '<html>';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">';
echo '<style>';
echo 'td { mso-number-format:"\\@"; padding: 3px; }';
echo '.titulo { background-color: #054a85; color: white; font-size: 16px; height: 30px; text-align: center; }';
echo '.subtitulo { background-color: #e3f2fd; font-weight: bold; font-size: 14px; }';
echo '.encabezado { background-color: #054a85; color: white; font-weight: bold; }';
echo '.total { background-color: #f8f9fa; font-weight: bold; }';
echo '.utilidad { background-color: #e8f4f8; font-weight: bold; font-style: italic; }';
echo '.resultado { background-color: #054a85; color: white; font-weight: bold; font-size: 14px; height: 40px; }';
echo '.info { background-color: #f0f0f0; font-size: 12px; }';
echo '</style>';
echo '</head>';
echo '<body>';

echo '<table border="1" cellpadding="3" cellspacing="0" width="100%">';
echo '<tr><td colspan="3" class="titulo">' . convertir_texto('ESTADO DE RESULTADOS') . '</td></tr>';
echo '<tr><td colspan="3" class="info">' . convertir_texto('Período: ') . $fecha_desde . ' al ' . $fecha_hasta . '</td></tr>';

// INGRESOS
echo '<tr><td colspan="3" class="subtitulo">' . convertir_texto('INGRESOS') . '</td></tr>';
echo '<tr class="encabezado">';
echo '<td width="150">' . convertir_texto('Código') . '</td>';
echo '<td width="400">' . convertir_texto('Nombre de la cuenta') . '</td>';
echo '<td width="150" align="right">' . convertir_texto('Saldo') . '</td>';
echo '</tr>';

if (count($ingresos) > 0) {
    foreach($ingresos as $fila) {
        $padding = ($fila['nivel'] - 2) * 10;
        echo '<tr>';
        echo '<td>' . htmlspecialchars($fila['codigo']) . '</td>';
        echo '<td style="padding-left: ' . $padding . 'px;">' . convertir_texto($fila['nombre']) . '</td>';
        echo '<td align="right">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
        echo '</tr>';
    }
    echo '<tr class="total">';
    echo '<td colspan="2">' . convertir_texto('TOTAL INGRESOS') . '</td>';
    echo '<td align="right">' . number_format($totalIngresos, 2, ',', '.') . '</td>';
    echo '</tr>';
} else {
    echo '<tr><td colspan="3" align="center">' . convertir_texto('No hay ingresos en el período seleccionado') . '</td></tr>';
}

// COSTOS
echo '<tr><td colspan="3" style="height: 10px; border: none;"></td></tr>';
echo '<tr><td colspan="3" class="subtitulo">' . convertir_texto('COSTOS DE VENTAS') . '</td></tr>';
echo '<tr class="encabezado">';
echo '<td>' . convertir_texto('Código') . '</td>';
echo '<td>' . convertir_texto('Nombre de la cuenta') . '</td>';
echo '<td align="right">' . convertir_texto('Saldo') . '</td>';
echo '</tr>';

if (count($costos) > 0) {
    foreach($costos as $fila) {
        $padding = ($fila['nivel'] - 2) * 10;
        echo '<tr>';
        echo '<td>' . htmlspecialchars($fila['codigo']) . '</td>';
        echo '<td style="padding-left: ' . $padding . 'px;">' . convertir_texto($fila['nombre']) . '</td>';
        echo '<td align="right">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
        echo '</tr>';
    }
    echo '<tr class="total">';
    echo '<td colspan="2">' . convertir_texto('TOTAL COSTOS') . '</td>';
    echo '<td align="right">' . number_format($totalCostos, 2, ',', '.') . '</td>';
    echo '</tr>';
    echo '<tr class="utilidad">';
    echo '<td colspan="2">' . convertir_texto('UTILIDAD BRUTA (Ingresos - Costos)') . '</td>';
    echo '<td align="right">' . number_format($utilidad_bruta, 2, ',', '.') . '</td>';
    echo '</tr>';
} else {
    echo '<tr><td colspan="3" align="center">' . convertir_texto('No hay costos en el período seleccionado') . '</td></tr>';
}

// GASTOS
echo '<tr><td colspan="3" style="height: 10px; border: none;"></td></tr>';
echo '<tr><td colspan="3" class="subtitulo">' . convertir_texto('GASTOS') . '</td></tr>';
echo '<tr class="encabezado">';
echo '<td>' . convertir_texto('Código') . '</td>';
echo '<td>' . convertir_texto('Nombre de la cuenta') . '</td>';
echo '<td align="right">' . convertir_texto('Saldo') . '</td>';
echo '</tr>';

if (count($gastos) > 0) {
    foreach($gastos as $fila) {
        $padding = ($fila['nivel'] - 2) * 10;
        echo '<tr>';
        echo '<td>' . htmlspecialchars($fila['codigo']) . '</td>';
        echo '<td style="padding-left: ' . $padding . 'px;">' . convertir_texto($fila['nombre']) . '</td>';
        echo '<td align="right">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
        echo '</tr>';
    }
    echo '<tr class="total">';
    echo '<td colspan="2">' . convertir_texto('TOTAL GASTOS') . '</td>';
    echo '<td align="right">' . number_format($totalGastos, 2, ',', '.') . '</td>';
    echo '</tr>';
    echo '<tr class="utilidad">';
    echo '<td colspan="2">' . convertir_texto('UTILIDAD OPERACIONAL (Utilidad Bruta - Gastos)') . '</td>';
    echo '<td align="right">' . number_format($utilidad_operacional, 2, ',', '.') . '</td>';
    echo '</tr>';
} else {
    echo '<tr><td colspan="3" align="center">' . convertir_texto('No hay gastos en el período seleccionado') . '</td></tr>';
}

// RESULTADO FINAL
echo '<tr><td colspan="3" style="height: 10px; border: none;"></td></tr>';
echo '<tr><td colspan="3" class="resultado">' . convertir_texto('RESULTADO DEL EJERCICIO') . '</td></tr>';
echo '<tr class="resultado">';
echo '<td colspan="2">' . convertir_texto($resultado_ejercicio >= 0 ? 'UTILIDAD DEL EJERCICIO' : 'PÉRDIDA DEL EJERCICIO') . '</td>';
echo '<td align="right">' . number_format(abs($resultado_ejercicio), 2, ',', '.') . '</td>';
echo '</tr>';

// INFORMACIÓN ADICIONAL
echo '<tr><td colspan="3" style="height: 20px; border: none;"></td></tr>';
echo '<tr><td colspan="3" class="info">';
echo '<strong>' . convertir_texto('Información del Reporte:') . '</strong><br>';
echo convertir_texto('Generado el: ') . date('Y-m-d H:i:s') . '<br>';
echo convertir_texto('Período fiscal: ') . $periodo_fiscal . '<br>';
if ($cuenta_codigo != '') echo convertir_texto('Cuenta filtrada: ') . $cuenta_codigo . '<br>';
if ($tercero != '') echo convertir_texto('Tercero filtrado: ') . $tercero . '<br>';
echo '</td></tr>';

echo '</table>';
echo '</body>';
echo '</html>';
?>