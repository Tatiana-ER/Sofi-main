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

// Obtener nombres de cuentas
$nombres_cuentas = obtenerNombresCuentas($pdo);

// ================== OBTENER DATOS ==================
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

// Aplicar agrupaciones
$ingresos = agregarAgrupaciones($ingresos, $cuentas_procesadas, $nombres_cuentas);
$costos = agregarAgrupaciones($costos, $cuentas_procesadas, $nombres_cuentas);
$gastos = agregarAgrupaciones($gastos, $cuentas_procesadas, $nombres_cuentas);

// ================== RESULTADOS ==================
$resultado_ejercicio = $totalIngresos - $totalCostos - $totalGastos;
$utilidad_bruta = $totalIngresos - $totalCostos;
$utilidad_operacional = $utilidad_bruta - $totalGastos;

// ================== GENERAR EXCEL ==================
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment;filename="Estado_Resultados_' . date('Y-m-d') . '.xls"');
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
echo '.ingresos-header { background-color: #d1e7dd; }'; // Verde claro para ingresos
echo '.costos-header { background-color: #f8d7da; }';   // Rojo claro para costos
echo '.gastos-header { background-color: #fff3cd; }';   // Amarillo claro para gastos
echo '.utilidad-bruta { background-color: #cfe2ff; }';  // Azul claro
echo '.utilidad-operacional { background-color: #d1ecf1; }'; // Cyan claro
echo '.resultado-final { background-color: #054a85; color: white; }';
echo '.valor-negativo { color: #000000; }';
echo '</style>';
echo '<!--[if gte mso 9]>';
echo '<xml>';
echo '<x:ExcelWorkbook>';
echo '<x:ExcelWorksheets>';
echo '<x:ExcelWorksheet>';
echo '<x:Name>Estado de Resultados</x:Name>';
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

// TÍTULO PRINCIPAL Y DATOS DE EMPRESA
echo '<tr>';
echo '<th colspan="3" class="header-blue" style="font-size: 16px; padding: 10px;">ESTADO DE RESULTADOS</th>';
echo '</tr>';

echo '<tr>';
echo '<td colspan="3" class="centered bold" style="background-color: #f0f0f0; padding: 8px;">';
echo 'NOMBRE DE LA EMPRESA: ' . htmlspecialchars($nombre_empresa, ENT_QUOTES, 'UTF-8');
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td colspan="3" class="centered bold" style="background-color: #f0f0f0; padding: 8px;">';
echo 'NIT DE LA EMPRESA: ' . htmlspecialchars($nit_empresa, ENT_QUOTES, 'UTF-8');
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td colspan="3" class="centered" style="background-color: #f0f0f0; padding: 8px;">';
echo 'PERÍODO: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta));
echo '</td>';
echo '</tr>';

echo '<tr><td colspan="3" style="padding: 10px;">&nbsp;</td></tr>';

// INGRESOS
echo '<tr>';
echo '<th colspan="3" class="ingresos-header bold" style="padding: 8px;">INGRESOS</th>';
echo '</tr>';

echo '<tr class="header-blue">';
echo '<th style="padding: 6px; width: 80px;">Código</th>';
echo '<th style="padding: 6px;">Nombre de la cuenta</th>';
echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo</th>';
echo '</tr>';

foreach ($ingresos as $fila) {
    $padding_left = ($fila['nivel'] - 2) * 3; // Ajuste de sangría similar al PDF
    echo '<tr>';
    echo '<td style="padding: 5px;">' . htmlspecialchars($fila['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; padding-left: ' . $padding_left . 'px;">' . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    echo '</tr>';
}

echo '<tr class="total-row">';
echo '<td colspan="2" style="padding: 6px;">TOTAL INGRESOS</td>';
echo '<td style="padding: 6px; text-align: right;">' . number_format($totalIngresos, 2, ',', '.') . '</td>';
echo '</tr>';

echo '<tr><td colspan="3" style="padding: 10px;">&nbsp;</td></tr>';

// COSTOS
echo '<tr>';
echo '<th colspan="3" class="costos-header bold" style="padding: 8px;">COSTOS DE VENTAS</th>';
echo '</tr>';

echo '<tr class="header-blue">';
echo '<th style="padding: 6px; width: 80px;">Código</th>';
echo '<th style="padding: 6px;">Nombre de la cuenta</th>';
echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo</th>';
echo '</tr>';

foreach ($costos as $fila) {
    $padding_left = ($fila['nivel'] - 2) * 3;
    echo '<tr>';
    echo '<td style="padding: 5px;">' . htmlspecialchars($fila['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; padding-left: ' . $padding_left . 'px;">' . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    echo '</tr>';
}

echo '<tr class="total-row">';
echo '<td colspan="2" style="padding: 6px;">TOTAL COSTOS</td>';
echo '<td style="padding: 6px; text-align: right;">' . number_format($totalCostos, 2, ',', '.') . '</td>';
echo '</tr>';

// UTILIDAD BRUTA (como en el PDF)
echo '<tr class="utilidad-bruta bold">';
echo '<td colspan="2" style="padding: 8px;">UTILIDAD BRUTA (Ingresos - Costos)</td>';
echo '<td style="padding: 8px; text-align: right;">' . number_format($utilidad_bruta, 2, ',', '.') . '</td>';
echo '</tr>';

echo '<tr><td colspan="3" style="padding: 10px;">&nbsp;</td></tr>';

// GASTOS
echo '<tr>';
echo '<th colspan="3" class="gastos-header bold" style="padding: 8px;">GASTOS</th>';
echo '</tr>';

echo '<tr class="header-blue">';
echo '<th style="padding: 6px; width: 80px;">Código</th>';
echo '<th style="padding: 6px;">Nombre de la cuenta</th>';
echo '<th style="padding: 6px; width: 120px; text-align: right;">Saldo</th>';
echo '</tr>';

foreach ($gastos as $fila) {
    $padding_left = ($fila['nivel'] - 2) * 3;
    echo '<tr>';
    echo '<td style="padding: 5px;">' . htmlspecialchars($fila['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; padding-left: ' . $padding_left . 'px;">' . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td style="padding: 5px; text-align: right;">' . number_format($fila['saldo'], 2, ',', '.') . '</td>';
    echo '</tr>';
}

echo '<tr class="total-row">';
echo '<td colspan="2" style="padding: 6px;">TOTAL GASTOS</td>';
echo '<td style="padding: 6px; text-align: right;">' . number_format($totalGastos, 2, ',', '.') . '</td>';
echo '</tr>';

// UTILIDAD OPERACIONAL (como en el PDF)
echo '<tr class="utilidad-operacional bold">';
echo '<td colspan="2" style="padding: 8px;">UTILIDAD OPERACIONAL (Utilidad Bruta - Gastos)</td>';
echo '<td style="padding: 8px; text-align: right;">' . number_format($utilidad_operacional, 2, ',', '.') . '</td>';
echo '</tr>';

echo '<tr><td colspan="3" style="padding: 10px;">&nbsp;</td></tr>';

// RESULTADO FINAL DEL EJERCICIO
echo '<tr class="resultado-final bold">';
echo '<td colspan="3" class="centered" style="padding: 10px; font-size: 14px;">RESULTADO DEL EJERCICIO</td>';
echo '</tr>';

echo '<tr class="total-row">';
echo '<td colspan="2" style="padding: 10px; font-size: 12px;">';
echo $resultado_ejercicio >= 0 ? 'UTILIDAD DEL EJERCICIO' : 'PÉRDIDA DEL EJERCICIO';
echo '</td>';
echo '<td style="padding: 10px; text-align: right; font-size: 12px;">' . number_format(abs($resultado_ejercicio), 2, ',', '.') . '</td>';
echo '</tr>';

echo '</table>';

// INFORMACIÓN ADICIONAL
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
echo '<td style="padding: 3px;">' . $periodo_fiscal . '</td>';
echo '</tr>';
if ($cuenta_codigo != '') {
    echo '<tr>';
    echo '<td style="padding: 3px;">Cuenta filtrada:</td>';
    echo '<td style="padding: 3px;">' . htmlspecialchars($cuenta_codigo, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '</tr>';
}
if ($tercero != '') {
    echo '<tr>';
    echo '<td style="padding: 3px;">Tercero filtrado:</td>';
    echo '<td style="padding: 3px;">' . htmlspecialchars($tercero, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '</body></html>';
?>