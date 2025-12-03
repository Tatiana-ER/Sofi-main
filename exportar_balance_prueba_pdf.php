<?php
// ================== EXPORTAR BALANCE DE PRUEBA A PDF ==================
require('libs/fpdf/fpdf.php');

include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

function convertir_texto($texto) {
    return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
}

// ================== FILTROS ==================
$periodo_fiscal = isset($_GET['periodo_fiscal']) ? $_GET['periodo_fiscal'] : date('Y');
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';
$mostrar_saldo_inicial = isset($_GET['mostrar_saldo_inicial']) && $_GET['mostrar_saldo_inicial'] == '1' ? '1' : '0';

// ================== FUNCIÓN PARA CALCULAR MOVIMIENTOS DE UNA CUENTA ==================
function calcularMovimientos($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    $naturaleza = substr($codigo_cuenta, 0, 1);
    
    // Saldo inicial (antes del periodo)
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
    
    // Calcular saldo inicial según naturaleza
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
    
    // Calcular saldo final
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

// ================== OBTENER CUENTAS Y CONSTRUIR JERARQUÍA ==================
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

// Array para almacenar todas las cuentas (detalle + agrupaciones)
$cuentas_completas = [];
$codigos_procesados = [];

// Nombres de agrupación por código
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
    '17' => 'Otros activos no financieros',
    '22' => 'Proveedores',
    '23' => 'Acreedores comerciales y otras cuentas por pagar',
    '24' => 'Impuestos, gravámenes y tasas',
    '25' => 'Beneficios a empleados',
    '28' => 'Pasivos no financieros',
    '31' => 'Capital social',
    '36' => 'Resultado del ejercicio',
    '41' => 'Ingresos de actividades ordinarias',
    '42' => 'Otros ingresos de actividades ordinarias',
    '51' => 'Administrativos',
    '52' => 'Ventas',
    '53' => 'Otros gastos de actividades ordinarias',
    '61' => 'Costo de ventas y de prestación de servicios'
];

// Procesar cada cuenta detalle
foreach ($cuentas_detalle as $cuenta) {
    $codigo = $cuenta['codigo_cuenta'];
    $nombre = $cuenta['nombre_cuenta'];
    
    // Agregar cuenta auxiliar (completa)
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
    
    // Generar agrupaciones superiores
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

// Ordenar por código
usort($cuentas_completas, function($a, $b) {
    return strcmp($a['codigo'], $b['codigo']);
});

// Calcular sumas para agrupaciones
$totales_por_codigo = [];

foreach ($cuentas_completas as &$cuenta) {
    if (!isset($cuenta['es_agrupacion'])) {
        $totales_por_codigo[$cuenta['codigo']] = [
            'saldo_inicial' => $cuenta['saldo_inicial'],
            'debito' => $cuenta['debito'],
            'credito' => $cuenta['credito'],
            'saldo_final' => $cuenta['saldo_final']
        ];
        
        // Sumar a niveles superiores
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

// Asignar totales calculados a las agrupaciones
foreach ($cuentas_completas as &$cuenta) {
    if (isset($cuenta['es_agrupacion']) && isset($totales_por_codigo[$cuenta['codigo']])) {
        $cuenta['saldo_inicial'] = $totales_por_codigo[$cuenta['codigo']]['saldo_inicial'];
        $cuenta['debito'] = $totales_por_codigo[$cuenta['codigo']]['debito'];
        $cuenta['credito'] = $totales_por_codigo[$cuenta['codigo']]['credito'];
        $cuenta['saldo_final'] = $totales_por_codigo[$cuenta['codigo']]['saldo_final'];
    }
}

// Calcular totales generales
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

// ================== CREAR PDF CON FPDF ==================
class PDF extends FPDF
{
    // Cabecera de página
    // Agregar constructor para pasar parámetros correctamente
    function __construct($orientation='P', $unit='mm', $size='A4') {
        parent::__construct($orientation, $unit, $size);
    }

    // Pie de página
    function Footer()
    {
        // Posición a 1.5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, convertir_texto('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Crear instancia de PDF en orientación horizontal
$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

// Información del período
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, convertir_texto('Período: ') . $fecha_desde . ' al ' . $fecha_hasta, 0, 1, 'L');
$pdf->Cell(0, 5, convertir_texto('Período Fiscal: ') . $periodo_fiscal, 0, 1, 'L');
if ($cuenta_codigo != '') {
    $pdf->Cell(0, 5, convertir_texto('Cuenta filtrada: ') . $cuenta_codigo, 0, 1, 'L');
}
if ($tercero != '') {
    $pdf->Cell(0, 5, convertir_texto('Tercero filtrado: ') . $tercero, 0, 1, 'L');
}
$pdf->Cell(0, 5, convertir_texto('Mostrar saldo inicial: ') . ($mostrar_saldo_inicial == '1' ? 'Sí' : 'No'), 0, 1, 'L');
$pdf->Ln(3);

// Calcular anchos de columnas según si se muestra saldo inicial
if ($mostrar_saldo_inicial == '1') {
    $ancho_codigo = 25;
    $ancho_nombre = 75;
    $ancho_columna = 35;
    $total_columnas = 6;
} else {
    $ancho_codigo = 30;
    $ancho_nombre = 90;
    $ancho_columna = 40;
    $total_columnas = 5;
}

// Encabezados de tabla
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(5, 74, 133);
$pdf->SetTextColor(255, 255, 255);

if ($mostrar_saldo_inicial == '1') {
    $pdf->Cell($ancho_codigo, 8, convertir_texto('Código'), 1, 0, 'C', true);
    $pdf->Cell($ancho_nombre, 8, convertir_texto('Nombre de la cuenta'), 1, 0, 'C', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Saldo Inicial'), 1, 0, 'C', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Mov. Débito'), 1, 0, 'C', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Mov. Crédito'), 1, 0, 'C', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Saldo Final'), 1, 1, 'C', true);
} else {
    $pdf->Cell($ancho_codigo, 8, convertir_texto('Código'), 1, 0, 'C', true);
    $pdf->Cell($ancho_nombre, 8, convertir_texto('Nombre de la cuenta'), 1, 0, 'C', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Movimiento Débito'), 1, 0, 'C', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Movimiento Crédito'), 1, 0, 'C', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Saldo Final'), 1, 1, 'C', true);
}

// Datos
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);

foreach ($cuentas_completas as $cuenta) {
    // Determinar estilo según nivel
    switch($cuenta['nivel']) {
        case 1:
            $pdf->SetFillColor(227, 242, 253); // Azul claro
            $pdf->SetFont('Arial', 'B', 9);
            break;
        case 2:
            $pdf->SetFillColor(241, 248, 255); // Azul muy claro
            $pdf->SetFont('Arial', 'B', 8);
            break;
        case 4:
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 8);
            break;
        default:
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetFont('Arial', '', 8);
    }
    
    // Aplicar sangría según nivel
    $sangria = ($cuenta['nivel'] - 1) * 3;
    $nombre = $cuenta['nombre'];
    if ($sangria > 0) {
        $nombre = str_repeat(' ', $sangria) . $nombre;
    }
    
    // Limitar longitud del nombre para que quepa en la columna
    $max_caracteres = $mostrar_saldo_inicial == '1' ? 45 : 50;
    $nombre = strlen($nombre) > $max_caracteres ? substr($nombre, 0, $max_caracteres - 3) . '...' : $nombre;
    
    if ($mostrar_saldo_inicial == '1') {
        $pdf->Cell($ancho_codigo, 6, $cuenta['codigo'], 1, 0, 'L', true);
        $pdf->Cell($ancho_nombre, 6, convertir_texto($nombre), 1, 0, 'L', true);
        $pdf->Cell($ancho_columna, 6, number_format($cuenta['saldo_inicial'], 2, ',', '.'), 1, 0, 'R', true);
        $pdf->Cell($ancho_columna, 6, number_format($cuenta['debito'], 2, ',', '.'), 1, 0, 'R', true);
        $pdf->Cell($ancho_columna, 6, number_format($cuenta['credito'], 2, ',', '.'), 1, 0, 'R', true);
        $pdf->Cell($ancho_columna, 6, number_format($cuenta['saldo_final'], 2, ',', '.'), 1, 1, 'R', true);
    } else {
        $pdf->Cell($ancho_codigo, 6, $cuenta['codigo'], 1, 0, 'L', true);
        $pdf->Cell($ancho_nombre, 6, convertir_texto($nombre), 1, 0, 'L', true);
        $pdf->Cell($ancho_columna, 6, number_format($cuenta['debito'], 2, ',', '.'), 1, 0, 'R', true);
        $pdf->Cell($ancho_columna, 6, number_format($cuenta['credito'], 2, ',', '.'), 1, 0, 'R', true);
        $pdf->Cell($ancho_columna, 6, number_format($cuenta['saldo_final'], 2, ',', '.'), 1, 1, 'R', true);
    }
}

// Línea separadora antes de totales
$pdf->Ln(2);

// Totales generales
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(5, 74, 133);
$pdf->SetTextColor(255, 255, 255);

if ($mostrar_saldo_inicial == '1') {
    $ancho_total_nombre = $ancho_codigo + $ancho_nombre;
    $pdf->Cell($ancho_total_nombre, 8, 'TOTALES GENERALES', 1, 0, 'C', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_saldo_inicial, 2, ',', '.'), 1, 0, 'R', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_debito, 2, ',', '.'), 1, 0, 'R', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_credito, 2, ',', '.'), 1, 0, 'R', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_saldo_final, 2, ',', '.'), 1, 1, 'R', true);
} else {
    $ancho_total_nombre = $ancho_codigo + $ancho_nombre;
    $pdf->Cell($ancho_total_nombre, 8, 'TOTALES GENERALES', 1, 0, 'C', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_debito, 2, ',', '.'), 1, 0, 'R', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_credito, 2, ',', '.'), 1, 0, 'R', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_saldo_final, 2, ',', '.'), 1, 1, 'R', true);
}

$pdf->Ln(10);

// FIRMAS
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Calcular posición centrada para firmas
$ancho_pagina = 280; // Ancho aproximado en modo horizontal A4
$ancho_firma = 80;
$espacio_entre_firmas = 40;
$posicion_x = ($ancho_pagina - ($ancho_firma * 2 + $espacio_entre_firmas)) / 2;

$pdf->SetX($posicion_x);
$pdf->Cell($ancho_firma, 5, '_________________________', 0, 0, 'C');
$pdf->Cell($espacio_entre_firmas, 5, '', 0, 0, 'C');
$pdf->Cell($ancho_firma, 5, '_________________________', 0, 1, 'C');

$pdf->SetX($posicion_x);
$pdf->Cell($ancho_firma, 5, convertir_texto('CONTADOR PÚBLICO'), 0, 0, 'C');
$pdf->Cell($espacio_entre_firmas, 5, '', 0, 0, 'C');
$pdf->Cell($ancho_firma, 5, convertir_texto('REPRESENTANTE LEGAL'), 0, 1, 'C');

$pdf->SetX($posicion_x);
$pdf->Cell($ancho_firma, 5, 'T.P. __________', 0, 0, 'C');
$pdf->Cell($espacio_entre_firmas, 5, '', 0, 0, 'C');
$pdf->Cell($ancho_firma, 5, 'C.C. __________', 0, 1, 'C');

$pdf->Ln(8);

// INFORMACIÓN ADICIONAL
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 5, convertir_texto('Información del Reporte:'), 0, 1, 'L', true);
$pdf->Cell(0, 4, convertir_texto('Generado el: ') . date('Y-m-d H:i:s'), 0, 1, 'L');
$pdf->Cell(0, 4, convertir_texto('Total de cuentas: ') . count($cuentas_completas), 0, 1, 'L');

// Salida del PDF - 'I' para abrir en navegador
$pdf->Output('I', 'balance_prueba_' . date('Y-m-d') . '.pdf');
?>