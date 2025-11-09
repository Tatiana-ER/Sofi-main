<?php
// ================== EXPORTAR BALANCE DE PRUEBA A PDF ==================
require('libs/fpdf/fpdf.php'); // Asegúrate de tener FPDF instalado

include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

function convertir_texto($texto) {
    return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
}

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';
$mostrar_saldo_inicial = isset($_GET['mostrar_saldo_inicial']) && $_GET['mostrar_saldo_inicial'] == '1' ? '1' : '0';

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

if ($cuenta_codigo != '') {
    $sql_cuentas .= " AND codigo_cuenta = :cuenta";
    $params[':cuenta'] = $cuenta_codigo;
}

if ($tercero != '') {
    $sql_cuentas .= " AND tercero_identificacion = :tercero";
    $params[':tercero'] = $tercero;
}

$sql_cuentas .= " ORDER BY codigo_cuenta";

$stmt = $pdo->prepare($sql_cuentas);
$stmt->execute($params);
$cuentas_detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construir jerarquía (mismo código que en balance.php)
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

// Calcular totales (mismo código)
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

// ================== GENERAR PDF ==================
class PDF extends FPDF {
    // Agregar constructor para pasar parámetros correctamente
    function __construct($orientation='P', $unit='mm', $size='A4') {
        parent::__construct($orientation, $unit, $size);
    }
    
    function Header() {
        $this->SetFont('Arial','B',16);
        $this->Cell(0,10,convertir_texto('BALANCE DE PRUEBA GENERAL'),0,1,'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,convertir_texto('Página ').$this->PageNo(),0,0,'C');
    }
}

$pdf = new PDF('L','mm','A4'); // Orientación horizontal
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

// Información del periodo
$pdf->Cell(0,6,convertir_texto('Período: ') . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta)),0,1);
$pdf->Ln(3);

// Encabezados de tabla
$pdf->SetFont('Arial','B',8);
$pdf->SetFillColor(5,74,133);
$pdf->SetTextColor(255,255,255);

if ($mostrar_saldo_inicial == '1') {
    $pdf->Cell(30,7,convertir_texto('Código'),1,0,'C',true);
    $pdf->Cell(80,7,convertir_texto('Nombre Cuenta'),1,0,'C',true);
    $pdf->Cell(35,7,convertir_texto('Saldo Inicial'),1,0,'C',true);
    $pdf->Cell(35,7,convertir_texto('Débito'),1,0,'C',true);
    $pdf->Cell(35,7,convertir_texto('Crédito'),1,0,'C',true);
    $pdf->Cell(35,7,convertir_texto('Saldo Final'),1,1,'C',true);
} else {
    $pdf->Cell(35,7,convertir_texto('Código'),1,0,'C',true);
    $pdf->Cell(95,7,convertir_texto('Nombre Cuenta'),1,0,'C',true);
    $pdf->Cell(45,7,convertir_texto('Débito'),1,0,'C',true);
    $pdf->Cell(45,7,convertir_texto('Crédito'),1,0,'C',true);
    $pdf->Cell(45,7,convertir_texto('Saldo Final'),1,1,'C',true);
}

// Datos
$pdf->SetFont('Arial','',7);
$pdf->SetTextColor(0,0,0);

foreach ($cuentas_completas as $cuenta) {
    // Color según nivel
    if ($cuenta['nivel'] == 1) {
        $pdf->SetFillColor(227,242,253);
        $pdf->SetFont('Arial','B',7);
    } elseif ($cuenta['nivel'] == 2) {
        $pdf->SetFillColor(241,248,255);
        $pdf->SetFont('Arial','B',7);
    } else {
        $pdf->SetFillColor(255,255,255);
        $pdf->SetFont('Arial','',7);
    }
    
    if ($mostrar_saldo_inicial == '1') {
        $pdf->Cell(30,6,convertir_texto($cuenta['codigo']),1,0,'L',true);
        $pdf->Cell(80,6,convertir_texto(substr($cuenta['nombre'], 0, 50)),1,0,'L',true);
        $pdf->Cell(35,6,number_format($cuenta['saldo_inicial'], 2, ',', '.'),1,0,'R',true);
        $pdf->Cell(35,6,number_format($cuenta['debito'], 2, ',', '.'),1,0,'R',true);
        $pdf->Cell(35,6,number_format($cuenta['credito'], 2, ',', '.'),1,0,'R',true);
        $pdf->Cell(35,6,number_format($cuenta['saldo_final'], 2, ',', '.'),1,1,'R',true);
    } else {
        $pdf->Cell(35,6,convertir_texto($cuenta['codigo']),1,0,'L',true);
        $pdf->Cell(95,6,convertir_texto(substr($cuenta['nombre'], 0, 60)),1,0,'L',true);
        $pdf->Cell(45,6,number_format($cuenta['debito'], 2, ',', '.'),1,0,'R',true);
        $pdf->Cell(45,6,number_format($cuenta['credito'], 2, ',', '.'),1,0,'R',true);
        $pdf->Cell(45,6,number_format($cuenta['saldo_final'], 2, ',', '.'),1,1,'R',true);
    }
}

// Totales
$pdf->SetFont('Arial','B',8);
$pdf->SetFillColor(5,74,133);
$pdf->SetTextColor(255,255,255);

if ($mostrar_saldo_inicial == '1') {
    $pdf->Cell(110,7,'TOTALES',1,0,'C',true);
    $pdf->Cell(35,7,number_format($total_saldo_inicial, 2, ',', '.'),1,0,'R',true);
    $pdf->Cell(35,7,number_format($total_debito, 2, ',', '.'),1,0,'R',true);
    $pdf->Cell(35,7,number_format($total_credito, 2, ',', '.'),1,0,'R',true);
    $pdf->Cell(35,7,number_format($total_saldo_final, 2, ',', '.'),1,1,'R',true);
} else {
    $pdf->Cell(130,7,'TOTALES',1,0,'C',true);
    $pdf->Cell(45,7,number_format($total_debito, 2, ',', '.'),1,0,'R',true);
    $pdf->Cell(45,7,number_format($total_credito, 2, ',', '.'),1,0,'R',true);
    $pdf->Cell(45,7,number_format($total_saldo_final, 2, ',', '.'),1,1,'R',true);
}

$pdf->Output('D', 'Balance_Prueba_' . date('Y-m-d') . '.pdf');
?>