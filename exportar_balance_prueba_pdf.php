<?php
// ================== EXPORTAR BALANCE DE PRUEBA A PDF ==================
require('libs/fpdf/fpdf.php');

include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

function convertir_texto($texto) {
    return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
}

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
    // ====== MODO: FILTRADO POR TIPO DE CUENTA ======
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
    // ====== MODO: SIN FILTRO (MOSTRAR JERARQUÍA COMPLETA) ======
    
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

// ================== CREAR PDF CON FPDF ==================
class PDF extends FPDF
{
    private $nombre_empresa;
    private $nit_empresa;
    private $mostrar_saldo_inicial;
    
    function __construct($orientation='L', $unit='mm', $size='A4', $nombre_emp='', $nit_emp='', $mostrar_si='0') {
        parent::__construct($orientation, $unit, $size);
        $this->nombre_empresa = $nombre_emp;
        $this->nit_empresa = $nit_emp;
        $this->mostrar_saldo_inicial = $mostrar_si;
    }

    // Cabecera de página
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, convertir_texto('BALANCE DE PRUEBA'), 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, convertir_texto('NOMBRE DE LA EMPRESA: ') . convertir_texto($this->nombre_empresa), 0, 1, 'C');
        $this->Cell(0, 6, convertir_texto('NIT DE LA EMPRESA: ') . $this->nit_empresa, 0, 1, 'C');
        
        $this->Ln(5);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, convertir_texto('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    // Función para truncar texto largo (PERO SIN PUNTOS SUSPENSIVOS)
    function truncateText($text, $maxWidth)
    {
        // Si el texto cabe en el ancho, lo dejamos completo
        if ($this->GetStringWidth($text) <= $maxWidth) {
            return $text;
        }
        
        // Si no cabe, lo dividimos en líneas
        $lines = [];
        $currentLine = '';
        $words = explode(' ', $text);
        
        foreach ($words as $word) {
            $testLine = $currentLine . ' ' . $word;
            if ($this->GetStringWidth($testLine) <= $maxWidth) {
                $currentLine = $testLine;
            } else {
                if ($currentLine != '') {
                    $lines[] = trim($currentLine);
                }
                $currentLine = $word;
            }
        }
        
        if ($currentLine != '') {
            $lines[] = trim($currentLine);
        }
        
        return $lines;
    }
}

// Crear instancia de PDF en orientación horizontal
$pdf = new PDF('L', 'mm', 'A4', $nombre_empresa, $nit_empresa, $mostrar_saldo_inicial);
$pdf->AliasNbPages();
$pdf->AddPage();

// Información del período
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, convertir_texto('PERIODO: ') . date('d/m/Y', strtotime($fecha_desde)) . ' AL ' . date('d/m/Y', strtotime($fecha_hasta)), 0, 1, 'C');

// Filtros aplicados
$pdf->SetFont('Arial', '', 10);
if ($cuenta_codigo != '') {
    $pdf->Cell(0, 5, convertir_texto('Cuenta filtrada: ') . $cuenta_codigo, 0, 1, 'L');
}
if ($tercero != '') {
    $pdf->Cell(0, 5, convertir_texto('Tercero filtrado: ') . $tercero, 0, 1, 'L');
}
if ($tipo_cuenta != '') {
    $pdf->Cell(0, 5, convertir_texto('Nivel de cuenta: ') . $tipo_cuenta . ' dígitos', 0, 1, 'L');
}

$pdf->Ln(3);

// Calcular anchos de columnas - MÁS ANCHO PARA NOMBRES LARGOS
if ($mostrar_saldo_inicial == '1') {
    $ancho_codigo = 20;
    $ancho_nombre = 110; // MÁS ANCHO PARA NOMBRES LARGOS
    $ancho_columna = 25;
    $total_columnas = 6;
} else {
    $ancho_codigo = 25;
    $ancho_nombre = 130; // MÁS ANCHO PARA NOMBRES LARGOS
    $ancho_columna = 35;
    $total_columnas = 5;
}

// Encabezados de tabla - ALINEADOS A LA IZQUIERDA
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(5, 74, 133);
$pdf->SetTextColor(255, 255, 255);

if ($mostrar_saldo_inicial == '1') {
    $pdf->Cell($ancho_codigo, 8, convertir_texto('Código'), 1, 0, 'L', true);
    $pdf->Cell($ancho_nombre, 8, convertir_texto('Nombre de la cuenta'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Saldo Inicial'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Mov. Débito'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Mov. Crédito'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Saldo Final'), 1, 1, 'L', true);
} else {
    $pdf->Cell($ancho_codigo, 8, convertir_texto('Código'), 1, 0, 'L', true);
    $pdf->Cell($ancho_nombre, 8, convertir_texto('Nombre de la cuenta'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Movimiento Débito'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Movimiento Crédito'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, convertir_texto('Saldo Final'), 1, 1, 'L', true);
}

// Datos
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);

foreach ($cuentas_completas as $cuenta) {
    // Determinar estilo según nivel
    switch($cuenta['nivel']) {
        case 1:
            $pdf->SetFillColor(227, 242, 253);
            $pdf->SetFont('Arial', 'B', 9);
            break;
        case 2:
            $pdf->SetFillColor(241, 248, 255);
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
    $nombre_completo = $cuenta['nombre'];
    
    // SIN TRUNCAR EL NOMBRE - lo mostramos completo
    // Si es muy largo, simplemente lo escribimos y FPDF manejará el salto de línea automáticamente
    
    // Calcular la altura necesaria para el nombre
    $nombre_truncado = str_repeat(' ', $sangria) . $nombre_completo;
    $altura_nombre = 6;
    
    // Si el nombre es muy largo, calculamos cuántas líneas necesitará
    $ancho_disponible = $ancho_nombre - 2; // Margen interno
    $ancho_texto = $pdf->GetStringWidth($nombre_truncado);
    
    if ($ancho_texto > $ancho_disponible) {
        // Calcular cuántas líneas necesitamos
        $lineas_necesarias = ceil($ancho_texto / $ancho_disponible);
        $altura_nombre = 6 * $lineas_necesarias;
    }
    
    if ($mostrar_saldo_inicial == '1') {
        $pdf->Cell($ancho_codigo, $altura_nombre, $cuenta['codigo'], 1, 0, 'L', true);
        $pdf->Cell($ancho_nombre, $altura_nombre, convertir_texto($nombre_truncado), 1, 0, 'L', true);
        $pdf->Cell($ancho_columna, $altura_nombre, number_format($cuenta['saldo_inicial'], 2, ',', '.'), 1, 0, 'L', true);
        $pdf->Cell($ancho_columna, $altura_nombre, number_format($cuenta['debito'], 2, ',', '.'), 1, 0, 'L', true);
        $pdf->Cell($ancho_columna, $altura_nombre, number_format($cuenta['credito'], 2, ',', '.'), 1, 0, 'L', true);
        $pdf->Cell($ancho_columna, $altura_nombre, number_format($cuenta['saldo_final'], 2, ',', '.'), 1, 1, 'L', true);
    } else {
        $pdf->Cell($ancho_codigo, $altura_nombre, $cuenta['codigo'], 1, 0, 'L', true);
        $pdf->Cell($ancho_nombre, $altura_nombre, convertir_texto($nombre_truncado), 1, 0, 'L', true);
        $pdf->Cell($ancho_columna, $altura_nombre, number_format($cuenta['debito'], 2, ',', '.'), 1, 0, 'L', true);
        $pdf->Cell($ancho_columna, $altura_nombre, number_format($cuenta['credito'], 2, ',', '.'), 1, 0, 'L', true);
        $pdf->Cell($ancho_columna, $altura_nombre, number_format($cuenta['saldo_final'], 2, ',', '.'), 1, 1, 'L', true);
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
    $pdf->Cell($ancho_total_nombre, 8, 'TOTALES GENERALES', 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_saldo_inicial, 2, ',', '.'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_debito, 2, ',', '.'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_credito, 2, ',', '.'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_saldo_final, 2, ',', '.'), 1, 1, 'L', true);
} else {
    $ancho_total_nombre = $ancho_codigo + $ancho_nombre;
    $pdf->Cell($ancho_total_nombre, 8, 'TOTALES GENERALES', 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_debito, 2, ',', '.'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_credito, 2, ',', '.'), 1, 0, 'L', true);
    $pdf->Cell($ancho_columna, 8, number_format($total_saldo_final, 2, ',', '.'), 1, 1, 'L', true);
}

// Salida del PDF
$pdf->Output('I', 'balance_prueba_' . date('Y-m-d') . '.pdf');
?>