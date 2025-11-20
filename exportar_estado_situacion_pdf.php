<?php
require('libs/fpdf/fpdf.php');

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

// ================== FUNCIÓN PARA CONVERTIR TEXTO ==================
function convertText($text) {
    return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
}

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

// ================== CREAR PDF CON FPDF ==================
class PDF extends FPDF {
    private $title = 'ESTADO DE SITUACIÓN FINANCIERA';
    private $mostrar_saldo_inicial;
    
    function __construct($mostrar_saldo_inicial = false) {
        parent::__construct();
        $this->mostrar_saldo_inicial = $mostrar_saldo_inicial;
    }
    
    function Header() {
        if (file_exists('./Img/sofilogo5pequeño.png')) {
            $this->Image('./Img/sofilogo5pequeño.png', 10, 8, 20);
        }
        
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, convertText($this->title), 0, 1, 'C');
        
        $this->SetLineWidth(0.5);
        $this->Line(10, 30, 200, 30);
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, convertText('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    function ChapterTitle($title) {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(0, 8, convertText($title), 0, 1, 'L', true);
        $this->Ln(2);
    }
    
    function TableHeader() {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(5, 74, 133);
        $this->SetTextColor(255, 255, 255);
        
        if ($this->mostrar_saldo_inicial) {
            $this->Cell(25, 8, convertText('Código'), 1, 0, 'C', true);
            $this->Cell(85, 8, convertText('Nombre de la cuenta'), 1, 0, 'C', true);
            $this->Cell(40, 8, convertText('Saldo Inicial'), 1, 0, 'C', true);
            $this->Cell(40, 8, convertText('Saldo'), 1, 1, 'C', true);
        } else {
            $this->Cell(25, 8, convertText('Código'), 1, 0, 'C', true);
            $this->Cell(125, 8, convertText('Nombre de la cuenta'), 1, 0, 'C', true);
            $this->Cell(40, 8, convertText('Saldo'), 1, 1, 'C', true);
        }
    }
    
    function TableRow($fila, $fill) {
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0, 0, 0);
        
        // Si es valor negativo, usar color rojo
        $es_negativo = $fila['saldo'] < 0;
        if ($es_negativo) {
            $this->SetTextColor(220, 53, 69); // Color rojo
        }
        
        $font_style = '';
        $indent = ($fila['nivel'] - 1) * 3;
        
        if ($fila['nivel'] <= 2) {
            $font_style = 'B';
            $this->SetFillColor(230, 240, 255);
        } elseif ($fila['nivel'] <= 4) {
            $font_style = '';
            $this->SetFillColor(245, 245, 245);
        } else {
            $font_style = '';
            $this->SetFillColor(255, 255, 255);
        }
        
        $this->SetFont('Arial', $font_style, 9);
        
        if ($this->mostrar_saldo_inicial) {
            $this->Cell(25, 7, $fila['codigo'], 1, 0, 'L', $fill);
            $this->Cell(85, 7, convertText(str_repeat('  ', $indent) . $fila['nombre']), 1, 0, 'L', $fill);
            $this->Cell(40, 7, number_format($fila['saldo_inicial'], 2, ',', '.'), 1, 0, 'R', $fill);
            $this->Cell(40, 7, number_format($fila['saldo'], 2, ',', '.'), 1, 1, 'R', $fill);
        } else {
            $this->Cell(25, 7, $fila['codigo'], 1, 0, 'L', $fill);
            $this->Cell(125, 7, convertText(str_repeat('  ', $indent) . $fila['nombre']), 1, 0, 'L', $fill);
            $this->Cell(40, 7, number_format($fila['saldo'], 2, ',', '.'), 1, 1, 'R', $fill);
        }
        
        // Restaurar color de texto
        $this->SetTextColor(0, 0, 0);
    }
    
    function TableTotal($label, $total_saldo_inicial, $total) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(220, 220, 220);
        
        if ($this->mostrar_saldo_inicial) {
            $this->Cell(110, 8, convertText($label), 1, 0, 'R', true);
            $this->Cell(40, 8, number_format($total_saldo_inicial, 2, ',', '.'), 1, 0, 'R', true);
            $this->Cell(40, 8, number_format($total, 2, ',', '.'), 1, 1, 'R', true);
        } else {
            $this->Cell(150, 8, convertText($label), 1, 0, 'R', true);
            $this->Cell(40, 8, number_format($total, 2, ',', '.'), 1, 1, 'R', true);
        }
    }
}

// Crear instancia del PDF
$pdf = new PDF($mostrar_saldo_inicial);
$pdf->AliasNbPages();
$pdf->AddPage();

// Información del período
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, convertText('Período: ') . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta)), 0, 1, 'C');
$pdf->Ln(5);

// ACTIVOS
$pdf->ChapterTitle('ACTIVOS');
$pdf->TableHeader();

$fill = false;
foreach ($activos as $fila) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->ChapterTitle('ACTIVOS (continuación)');
        $pdf->TableHeader();
        $fill = false;
    }
    
    $pdf->TableRow($fila, $fill);
    $fill = !$fill;
}

$pdf->TableTotal('TOTAL ACTIVOS', $totalSaldoInicialActivos, $totalActivos);
$pdf->Ln(8);

// PASIVOS
if ($pdf->GetY() > 200) {
    $pdf->AddPage();
}

$pdf->ChapterTitle('PASIVOS');
$pdf->TableHeader();

$fill = false;
foreach ($pasivos as $fila) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->ChapterTitle('PASIVOS (continuación)');
        $pdf->TableHeader();
        $fill = false;
    }
    
    $pdf->TableRow($fila, $fill);
    $fill = !$fill;
}

$pdf->TableTotal('TOTAL PASIVOS', $totalSaldoInicialPasivos, $totalPasivos);
$pdf->Ln(8);

// PATRIMONIO
if ($pdf->GetY() > 200) {
    $pdf->AddPage();
}

$pdf->ChapterTitle('PATRIMONIO');
$pdf->TableHeader();

$fill = false;
foreach ($patrimonios as $fila) {
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        $pdf->ChapterTitle('PATRIMONIO (continuación)');
        $pdf->TableHeader();
        $fill = false;
    }
    
    $pdf->TableRow($fila, $fill);
    $fill = !$fill;
}

$pdf->TableTotal('TOTAL PATRIMONIO', $totalSaldoInicialPatrimonios, $totalPatrimonios);
$pdf->Ln(10);

// EQUILIBRIO CONTABLE
$total_pasivo_patrimonio = $totalPasivos + $totalPatrimonios;
$diferencia = $totalActivos - $total_pasivo_patrimonio;
$esta_equilibrado = abs($diferencia) < 0.01;

$pdf->SetFont('Arial', 'B', 12);
if ($esta_equilibrado) {
    $pdf->SetFillColor(200, 255, 200);
    $equilibrio_texto = '✓ ACTIVOS = PASIVOS + PATRIMONIO';
} else {
    $pdf->SetFillColor(255, 200, 200);
    $equilibrio_texto = '✗ DESEQUILIBRIO CONTABLE';
}

if ($mostrar_saldo_inicial) {
    $pdf->Cell(95, 10, convertText($equilibrio_texto), 1, 0, 'C', true);
    $pdf->Cell(45, 10, number_format($totalSaldoInicialActivos, 2, ',', '.') . ' = ' . 
                number_format($totalSaldoInicialPasivos + $totalSaldoInicialPatrimonios, 2, ',', '.'), 1, 0, 'C', true);
    $pdf->Cell(50, 10, number_format($totalActivos, 2, ',', '.') . ' = ' . 
                number_format($total_pasivo_patrimonio, 2, ',', '.'), 1, 1, 'C', true);
} else {
    $pdf->Cell(140, 10, convertText($equilibrio_texto), 1, 0, 'C', true);
    $pdf->Cell(50, 10, number_format($totalActivos, 2, ',', '.') . ' = ' . 
                number_format($total_pasivo_patrimonio, 2, ',', '.'), 1, 1, 'C', true);
}

if (!$esta_equilibrado) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, convertText('Diferencia: ') . number_format($diferencia, 2, ',', '.'), 0, 1, 'C');
}

$pdf->Ln(15);

// FIRMAS
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 5, '________________________', 0, 0, 'C');
$pdf->Cell(95, 5, '________________________', 0, 1, 'C');
$pdf->Cell(95, 5, convertText('CONTADOR PÚBLICO'), 0, 0, 'C');
$pdf->Cell(95, 5, convertText('REPRESENTANTE LEGAL'), 0, 1, 'C');

// Salida del PDF
$pdf->Output('I', 'Estado_Situacion_Financiera_' . date('Y-m-d') . '.pdf');
?>