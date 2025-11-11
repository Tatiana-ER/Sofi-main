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
        $item = [
            'codigo' => $codigo,
            'nombre' => $cuenta['nombre_cuenta'],
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
function agregarAgrupaciones(&$array_cuentas, $cuentas_procesadas) {
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
                    $nombre = 'Grupo ' . $grupo;
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

$ingresos = agregarAgrupaciones($ingresos, $cuentas_procesadas);
$costos = agregarAgrupaciones($costos, $cuentas_procesadas);
$gastos = agregarAgrupaciones($gastos, $cuentas_procesadas);

// ================== RESULTADO DEL EJERCICIO ==================
$resultado_ejercicio = $totalIngresos - $totalCostos - $totalGastos;
$utilidad_bruta = $totalIngresos - $totalCostos;
$utilidad_operacional = $utilidad_bruta - $totalGastos;

// ================== CREAR PDF CON FPDF ==================
class PDF extends FPDF
{
    // Variables para almacenar los totales
    private $utilidad_bruta;
    private $utilidad_operacional;

    // Función para establecer los valores de utilidad
    public function setUtilidades($utilidad_bruta, $utilidad_operacional) {
        $this->utilidad_bruta = $utilidad_bruta;
        $this->utilidad_operacional = $utilidad_operacional;
    }

    // Cabecera de página
    function Header()
    {
        // Logo
        if (file_exists('assets/img/logo.png')) {
            $this->Image('assets/img/logo.png', 10, 8, 33);
        }
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Título
        $this->Cell(0, 10, 'ESTADO DE RESULTADOS', 0, 1, 'C');
        // Fecha
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'SOFI - Software Financiero', 0, 1, 'C');
        // Salto de línea
        $this->Ln(5);
    }

    // Pie de página
    function Footer()
    {
        // Posición a 1.5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Función para agregar sección
    function agregarSeccion($titulo, $datos, $total, $tipo_seccion = 'normal')
    {
        // Título de sección
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(227, 242, 253);
        $this->Cell(0, 8, $titulo, 0, 1, 'L', true);
        $this->Ln(2);

        // Cabecera de tabla
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(5, 74, 133);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(25, 8, 'Codigo', 0, 0, 'L', true);
        $this->Cell(115, 8, 'Nombre de la cuenta', 0, 0, 'L', true);
        $this->Cell(40, 8, 'Saldo', 0, 1, 'R', true);
        $this->SetTextColor(0, 0, 0);

        // Datos
        $this->SetFont('Arial', '', 9);
        if (count($datos) > 0) {
            foreach($datos as $fila) {
                $this->Cell(25, 6, $fila['codigo'], 0, 0, 'L');
                
                // Aplicar sangría según nivel
                $sangria = ($fila['nivel'] - 2) * 3;
                $nombre = $fila['nombre'];
                if ($sangria > 0) {
                    $this->Cell(115, 6, str_repeat(' ', $sangria) . $nombre, 0, 0, 'L');
                } else {
                    $this->Cell(115, 6, $nombre, 0, 0, 'L');
                }
                
                $this->Cell(40, 6, number_format($fila['saldo'], 2, ',', '.'), 0, 1, 'R');
            }
            
            // Total de sección
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(248, 249, 250);
            $this->Cell(140, 8, 'TOTAL ' . $titulo, 0, 0, 'L', true);
            $this->Cell(40, 8, number_format($total, 2, ',', '.'), 0, 1, 'R', true);
            
            // Agregar utilidad bruta después de costos
            if ($tipo_seccion == 'costos') {
                $this->SetFont('Arial', 'BI', 10);
                $this->SetFillColor(232, 244, 248);
                $this->Cell(140, 8, 'UTILIDAD BRUTA (Ingresos - Costos)', 0, 0, 'L', true);
                $this->Cell(40, 8, number_format($this->utilidad_bruta, 2, ',', '.'), 0, 1, 'R', true);
            }
            
            // Agregar utilidad operacional después de gastos
            if ($tipo_seccion == 'gastos') {
                $this->SetFont('Arial', 'BI', 10);
                $this->SetFillColor(232, 244, 248);
                $this->Cell(140, 8, 'UTILIDAD OPERACIONAL (Utilidad Bruta - Gastos)', 0, 0, 'L', true);
                $this->Cell(40, 8, number_format($this->utilidad_operacional, 2, ',', '.'), 0, 1, 'R', true);
            }
        } else {
            $this->Cell(0, 8, 'No hay datos en el periodo seleccionado', 0, 1, 'C');
        }
        
        $this->Ln(5);
    }
}

// Crear instancia de PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Establecer las utilidades en la clase PDF
$pdf->setUtilidades($utilidad_bruta, $utilidad_operacional);

// Información del período
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Periodo: ' . $fecha_desde . ' al ' . $fecha_hasta, 0, 1, 'L');
if ($cuenta_codigo != '') {
    $pdf->Cell(0, 5, 'Cuenta filtrada: ' . $cuenta_codigo, 0, 1, 'L');
}
if ($tercero != '') {
    $pdf->Cell(0, 5, 'Tercero filtrado: ' . $tercero, 0, 1, 'L');
}
$pdf->Ln(5);

// INGRESOS
$pdf->agregarSeccion('INGRESOS', $ingresos, $totalIngresos, 'normal');

// COSTOS
$pdf->agregarSeccion('COSTOS DE VENTAS', $costos, $totalCostos, 'costos');

// GASTOS
$pdf->agregarSeccion('GASTOS', $gastos, $totalGastos, 'gastos');

$pdf->Ln(8);

// RESULTADO FINAL
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(5, 74, 133);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'RESULTADO DEL EJERCICIO', 0, 1, 'C', true);
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(5, 74, 133);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(140, 12, ($resultado_ejercicio >= 0 ? 'UTILIDAD DEL EJERCICIO' : 'PERDIDA DEL EJERCICIO'), 0, 0, 'L', true);
$pdf->Cell(40, 12, number_format(abs($resultado_ejercicio), 2, ',', '.'), 0, 1, 'R', true);
$pdf->SetTextColor(0, 0, 0);

$pdf->Ln(15);

// FIRMAS
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(90, 5, '_________________________', 0, 0, 'C');
$pdf->Cell(20, 5, '', 0, 0, 'C');
$pdf->Cell(90, 5, '_________________________', 0, 1, 'C');

$pdf->Cell(90, 5, 'CONTADOR PUBLICO', 0, 0, 'C');
$pdf->Cell(20, 5, '', 0, 0, 'C');
$pdf->Cell(90, 5, 'REPRESENTANTE LEGAL', 0, 1, 'C');

$pdf->Cell(90, 5, 'T.P. __________', 0, 0, 'C');
$pdf->Cell(20, 5, '', 0, 0, 'C');
$pdf->Cell(90, 5, 'C.C. __________', 0, 1, 'C');

$pdf->Ln(10);

// INFORMACIÓN ADICIONAL
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 5, 'Informacion del Reporte:', 0, 1, 'L', true);
$pdf->Cell(0, 4, 'Generado el: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
$pdf->Cell(0, 4, 'Periodo fiscal: ' . $periodo_fiscal, 0, 1, 'L');
if ($cuenta_codigo != '') $pdf->Cell(0, 4, 'Cuenta filtrada: ' . $cuenta_codigo, 0, 1, 'L');
if ($tercero != '') $pdf->Cell(0, 4, 'Tercero filtrado: ' . $tercero, 0, 1, 'L');

// Salida del PDF
$pdf->Output('I', 'estado_resultados_' . date('Y-m-d') . '.pdf');
?>