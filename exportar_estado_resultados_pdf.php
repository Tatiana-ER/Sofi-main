<?php
require('libs/fpdf/fpdf.php');

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

// ================== CREAR PDF CON FPDF ==================
class PDF extends FPDF
{
    private $nombre_empresa;
    private $nit_empresa;
    private $fecha_desde;
    private $fecha_hasta;
    private $utilidad_bruta;
    private $utilidad_operacional;
    
    function __construct($nombre_empresa = '', $nit_empresa = '', $fecha_desde = '', $fecha_hasta = '') {
        parent::__construct();
        $this->nombre_empresa = $nombre_empresa;
        $this->nit_empresa = $nit_empresa;
        $this->fecha_desde = $fecha_desde;
        $this->fecha_hasta = $fecha_hasta;
    }
    
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
        
        // Título principal
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, convertir_texto('ESTADO DE RESULTADOS'), 0, 1, 'C');
        
        // MEJORA: Información de la empresa centrada
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, convertir_texto('') . convertir_texto($this->nombre_empresa), 0, 1, 'C');
        $this->Cell(0, 6, convertir_texto('') . $this->nit_empresa, 0, 1, 'C');
        $this->Cell(0, 6, convertir_texto('Expresados en pesos Colombianos'), 0, 1, 'C');
        
        // Período
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, convertir_texto('PERÍODO: ') . date('d/m/Y', strtotime($this->fecha_desde)) . ' al ' . date('d/m/Y', strtotime($this->fecha_hasta)), 0, 1, 'C');
        
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

    // MEJORA: Función mejorada para agregar sección con bordes definidos
    function agregarSeccion($titulo, $datos, $total, $tipo_seccion = 'normal')
    {
        // Título de sección - Color más suave
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(230, 240, 255); // Color más suave
        $this->Cell(0, 8, convertir_texto($titulo), 1, 1, 'L', true);
        $this->Ln(2);

        // Encabezados de tabla - Color más suave
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(5, 74, 133); // Azul más suave (SteelBlue)
        $this->SetTextColor(255, 255, 255);
        $this->Cell(30, 8, convertir_texto('Código'), 1, 0, 'C', true);
        $this->Cell(110, 8, convertir_texto('Nombre de la cuenta'), 1, 0, 'C', true);
        $this->Cell(50, 8, convertir_texto('Saldo'), 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);

        // Datos
        $this->SetFont('Arial', '', 9);
        $fill = false;
        
        if (count($datos) > 0) {
            foreach($datos as $fila) {
                $this->SetTextColor(0, 0, 0);
                
                // Solo aplicar fondo a las celdas que tienen datos
                $fill_color = $fill ? array(245, 245, 245) : array(255, 255, 255); // Gris muy claro / blanco
                
                // Código con borde
                $this->SetFillColor($fill_color[0], $fill_color[1], $fill_color[2]);
                $this->Cell(30, 7, $fila['codigo'], 1, 0, 'L', true);
                
                // Nombre con sangría y borde
                $sangria = ($fila['nivel'] - 2) * 3;
                $nombre = convertir_texto($fila['nombre']);
                $this->SetFillColor($fill_color[0], $fill_color[1], $fill_color[2]);
                if ($sangria > 0) {
                    $this->Cell(110, 7, str_repeat(' ', $sangria) . $nombre, 1, 0, 'L', true);
                } else {
                    $this->Cell(110, 7, $nombre, 1, 0, 'L', true);
                }
                
                // Saldo con borde
                $this->SetFillColor($fill_color[0], $fill_color[1], $fill_color[2]);
                $this->Cell(50, 7, number_format($fila['saldo'], 2, ',', '.'), 1, 1, 'R', true);
                
                $fill = !$fill;
            }
            
            // MEJORA: Total de sección - Color gris claro
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(220, 220, 220); // Gris claro
            $this->Cell(140, 8, convertir_texto('TOTAL ' . $titulo), 1, 0, 'L', true);
            $this->Cell(50, 8, number_format($total, 2, ',', '.'), 1, 1, 'R', true);
            
            // MEJORA: Agregar utilidad bruta - Color azul muy claro
            if ($tipo_seccion == 'costos' && isset($this->utilidad_bruta)) {
                $this->SetFont('Arial', 'BI', 10);
                $this->SetFillColor(240, 248, 255); // Azul muy claro
                $this->Cell(140, 8, convertir_texto('UTILIDAD BRUTA (Ingresos - Costos)'), 1, 0, 'L', true);
                $this->Cell(50, 8, number_format($this->utilidad_bruta, 2, ',', '.'), 1, 1, 'R', true);
            }
            
            // MEJORA: Agregar utilidad operacional - Color verde muy claro
            if ($tipo_seccion == 'gastos' && isset($this->utilidad_operacional)) {
                $this->SetFont('Arial', 'BI', 10);
                $this->SetFillColor(240, 248, 255); // Verde muy claro
                $this->Cell(140, 8, convertir_texto('UTILIDAD OPERACIONAL (Utilidad Bruta - Gastos)'), 1, 0, 'L', true);
                $this->Cell(50, 8, number_format($this->utilidad_operacional, 2, ',', '.'), 1, 1, 'R', true);
            }
        } else {
            $this->SetFillColor(255, 255, 255);
            $this->Cell(190, 8, convertir_texto('No hay datos en el período seleccionado'), 1, 1, 'C', true);
        }
        
        $this->Ln(8);
    }
}

// MEJORA: Crear instancia de PDF con datos de la empresa
$pdf = new PDF($nombre_empresa, $nit_empresa, $fecha_desde, $fecha_hasta);
$pdf->AliasNbPages();
$pdf->AddPage();

// Establecer las utilidades en la clase PDF
$pdf->setUtilidades($utilidad_bruta, $utilidad_operacional);

// INGRESOS
$pdf->agregarSeccion('INGRESOS', $ingresos, $totalIngresos, 'normal');

// COSTOS
$pdf->agregarSeccion('COSTOS DE VENTAS', $costos, $totalCostos, 'costos');

// GASTOS
$pdf->agregarSeccion('GASTOS', $gastos, $totalGastos, 'gastos');

$pdf->Ln(8);

// RESULTADO FINAL - MEJORA: Con bordes y alineación correcta
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(5, 74, 133);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, convertir_texto('RESULTADO DEL EJERCICIO'), 1, 1, 'C', true);
$pdf->Ln(2);

// MEJORA: Con bordes y columnas uniformes
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(140, 12, convertir_texto($resultado_ejercicio >= 0 ? 'UTILIDAD DEL EJERCICIO' : 'PÉRDIDA DEL EJERCICIO'), 1, 0, 'L', true);
$pdf->Cell(50, 12, number_format(abs($resultado_ejercicio), 2, ',', '.'), 1, 1, 'R', true);

$pdf->Ln(15);

$pdf->Ln(10);

// INFORMACIÓN ADICIONAL - MEJORA: Con bordes definidos
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 5, convertir_texto('Información del Reporte:'), 1, 1, 'L', true);
$pdf->Cell(0, 4, convertir_texto('Generado el: ') . date('Y-m-d H:i:s'), 0, 1, 'L');
$pdf->Cell(0, 4, convertir_texto('Período fiscal: ') . $periodo_fiscal, 0, 1, 'L');
if ($cuenta_codigo != '') $pdf->Cell(0, 4, convertir_texto('Cuenta filtrada: ') . $cuenta_codigo, 0, 1, 'L');
if ($tercero != '') $pdf->Cell(0, 4, convertir_texto('Tercero filtrado: ') . $tercero, 0, 1, 'L');

// Salida del PDF
$pdf->Output('I', 'estado_resultados_' . date('Y-m-d') . '.pdf');
?>