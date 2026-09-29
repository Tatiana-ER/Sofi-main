<?php
// ================== EXPORTAR LIBRO DE BANCOS A PDF ==================
require('../../libs/fpdf/fpdf.php');

require_once '../../config/database.php';

$pdo = Database::getConnection();

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

// ================== CUENTAS BANCARIAS DISPONIBLES (1110xx) ==================
$sql_cuentas_banco = "SELECT DISTINCT codigo_cuenta, nombre_cuenta 
                      FROM libro_diario 
                      WHERE codigo_cuenta LIKE '1110%' 
                      ORDER BY codigo_cuenta";
$stmt_cuentas_banco = $pdo->query($sql_cuentas_banco);
$cuentas_banco = $stmt_cuentas_banco->fetchAll(PDO::FETCH_ASSOC);

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';
$cuenta_banco = isset($_GET['cuenta']) ? $_GET['cuenta'] : 'todas';

$nombre_cuenta_banco = '';
$nombrePorCuenta = [];
foreach ($cuentas_banco as $c) {
    $nombrePorCuenta[$c['codigo_cuenta']] = $c['nombre_cuenta'];
    if ($c['codigo_cuenta'] == $cuenta_banco) {
        $nombre_cuenta_banco = $c['nombre_cuenta'];
    }
}

$cuentasAConsultar = [];
if ($cuenta_banco !== 'todas' && $cuenta_banco !== '') {
    $cuentasAConsultar = [$cuenta_banco];
} else {
    foreach ($cuentas_banco as $c) {
        $cuentasAConsultar[] = $c['codigo_cuenta'];
    }
}

// ================== SALDO INICIAL POR CUENTA ==================
$saldoPorCuenta = [];
foreach ($cuentasAConsultar as $codigoCuenta) {
    $sql_saldo_inicial = "SELECT 
                            COALESCE(SUM(debito), 0) as total_debito,
                            COALESCE(SUM(credito), 0) as total_credito
                          FROM libro_diario
                          WHERE codigo_cuenta = :cuenta
                            AND fecha < :desde";
    $params_si = [':cuenta' => $codigoCuenta, ':desde' => $fecha_desde];

    if ($tercero != '') {
        $sql_saldo_inicial .= " AND tercero_identificacion = :tercero";
        $params_si[':tercero'] = $tercero;
    }

    $stmt_si = $pdo->prepare($sql_saldo_inicial);
    $stmt_si->execute($params_si);
    $mov_inicial = $stmt_si->fetch(PDO::FETCH_ASSOC);

    $saldoPorCuenta[$codigoCuenta] = floatval($mov_inicial['total_debito']) - floatval($mov_inicial['total_credito']);
}

$saldoInicialPeriodo = array_sum($saldoPorCuenta);

// ================== MOVIMIENTOS DEL PERIODO ==================
$movimientos = [];

if (count($cuentasAConsultar) > 0) {
    $placeholders = [];
    $params_mov = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

    foreach ($cuentasAConsultar as $indice => $codigoCuenta) {
        $clave = ':cta' . $indice;
        $placeholders[] = $clave;
        $params_mov[$clave] = $codigoCuenta;
    }

    $sql_mov = "SELECT * FROM libro_diario
                WHERE codigo_cuenta IN (" . implode(',', $placeholders) . ")
                  AND fecha BETWEEN :desde AND :hasta";

    if ($tercero != '') {
        $sql_mov .= " AND tercero_identificacion = :tercero";
        $params_mov[':tercero'] = $tercero;
    }

    $sql_mov .= " ORDER BY fecha ASC, id ASC";

    $stmt_mov = $pdo->prepare($sql_mov);
    $stmt_mov->execute($params_mov);
    $movimientos = $stmt_mov->fetchAll(PDO::FETCH_ASSOC);
}

function formatearComprobanteBancoPdf($tipo_documento, $numero_documento) {
    $etiquetas = [
        'factura_venta' => 'FAC.VTA.',
        'factura_compra' => 'FRA.COMP.',
        'recibo_caja' => 'REC.CAJA',
        'comprobante_egreso' => 'COMP.EGR.',
        'comprobante_contable' => 'COMP.CONT.',
        'cierre_contable' => 'CIERRE'
    ];
    $etiqueta = isset($etiquetas[$tipo_documento]) ? $etiquetas[$tipo_documento] : strtoupper($tipo_documento);
    return trim($etiqueta . ' ' . $numero_documento);
}

// ================== GENERAR PDF ==================
class PDF extends FPDF {
    private $fecha_desde;
    private $fecha_hasta;
    private $cuenta_banco;
    private $nombre_cuenta_banco;
    private $tercero;
    private $nombre_empresa;
    private $nit_empresa;

    function __construct($desde, $hasta, $cuenta, $nombreCuenta, $terc, $nombre_emp, $nit_emp) {
        parent::__construct('L','mm','A4');
        $this->fecha_desde = $desde;
        $this->fecha_hasta = $hasta;
        $this->cuenta_banco = $cuenta;
        $this->nombre_cuenta_banco = $nombreCuenta;
        $this->tercero = $terc;
        $this->nombre_empresa = $nombre_emp;
        $this->nit_empresa = $nit_emp;
    }

    function Header() {
        if (file_exists('assets/img/logo.png')) {
            $this->Image('assets/img/logo.png', 10, 8, 33);
        }

        $this->SetFont('Arial','B',14);
        $this->Cell(0,8,convertir_texto('LIBRO DE CUENTAS DE BANCO'),0,1,'C');

        $this->SetFont('Arial','B',10);
        $this->Cell(0,6,convertir_texto($this->nombre_empresa),0,1,'C');
        $this->Cell(0,6,$this->nit_empresa,0,1,'C');

        $this->SetFont('Arial','',9);
        $this->Cell(0,6,convertir_texto('PERIODO: ') . date('d/m/Y', strtotime($this->fecha_desde)) . ' A ' . date('d/m/Y', strtotime($this->fecha_hasta)),0,1,'C');

        if ($this->cuenta_banco == 'todas') {
            $this->Cell(0,6,convertir_texto('CUENTA: Todas las cuentas bancarias'),0,1,'C');
        } elseif ($this->cuenta_banco != '') {
            $this->Cell(0,6,convertir_texto('CUENTA: ') . $this->cuenta_banco . ' - ' . convertir_texto($this->nombre_cuenta_banco),0,1,'C');
        }
        if ($this->tercero != '') {
            $this->Cell(0,6,convertir_texto('TERCERO: ') . $this->tercero,0,1,'C');
        }

        $this->Ln(5);

        $this->SetFont('Arial','B',8);
        $this->SetFillColor(5,74,133);
        $this->SetTextColor(255,255,255);

        $this->Cell(40,6,convertir_texto('Cuenta'),1,0,'C',true);
        $this->Cell(38,6,convertir_texto('Comprobante'),1,0,'C',true);
        $this->Cell(20,6,convertir_texto('Fecha'),1,0,'C',true);
        $this->Cell(28,6,convertir_texto('ID Tercero'),1,0,'C',true);
        $this->Cell(45,6,convertir_texto('Nombre Tercero'),1,0,'C',true);
        $this->Cell(30,6,convertir_texto('Saldo Inicial'),1,0,'C',true);
        $this->Cell(26,6,convertir_texto('Débito'),1,0,'C',true);
        $this->Cell(26,6,convertir_texto('Crédito'),1,0,'C',true);
        $this->Cell(27,6,convertir_texto('Saldo Final'),1,1,'C',true);

        $this->SetTextColor(0,0,0);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,convertir_texto('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF($fecha_desde, $fecha_hasta, $cuenta_banco, $nombre_cuenta_banco, $tercero, $nombre_empresa, $nit_empresa);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',7);

$totalDebito = 0;
$totalCredito = 0;
$totalMovimientos = 0;
$saldoPorCuentaCorrida = $saldoPorCuenta;

if (count($cuentasAConsultar) == 0) {
    $pdf->SetFont('Arial','I',9);
    $pdf->Cell(0,10,convertir_texto('No hay ninguna cuenta bancaria (1110xx) registrada todavía en el libro diario.'),0,1,'C');
} elseif (count($movimientos) == 0) {
    $pdf->SetFont('Arial','I',9);
    $pdf->Cell(0,10,convertir_texto('Sin movimientos en el período seleccionado.'),0,1,'C');
} else {
    foreach ($movimientos as $mov) {
        $codigoCuentaFila = $mov['codigo_cuenta'];
        $debito = floatval($mov['debito']);
        $credito = floatval($mov['credito']);

        $saldoInicialFila = $saldoPorCuentaCorrida[$codigoCuentaFila];
        $saldoPorCuentaCorrida[$codigoCuentaFila] += ($debito - $credito);
        $saldoFinalFila = $saldoPorCuentaCorrida[$codigoCuentaFila];

        $totalDebito += $debito;
        $totalCredito += $credito;
        $totalMovimientos++;

        $nombreCta = $nombrePorCuenta[$codigoCuentaFila] ?? $mov['nombre_cuenta'];
        $comprobante = formatearComprobanteBancoPdf($mov['tipo_documento'], $mov['numero_documento']);

        $pdf->Cell(40,5,convertir_texto(substr($codigoCuentaFila . ' - ' . $nombreCta, 0, 26)),1,0,'L');
        $pdf->Cell(38,5,convertir_texto(substr($comprobante, 0, 24)),1,0,'L');
        $pdf->Cell(20,5,date('d/m/Y', strtotime($mov['fecha'])),1,0,'C');
        $pdf->Cell(28,5,convertir_texto(substr($mov['tercero_identificacion'], 0, 16)),1,0,'L');
        $pdf->Cell(45,5,convertir_texto(substr($mov['tercero_nombre'], 0, 28)),1,0,'L');
        $pdf->Cell(30,5,number_format($saldoInicialFila, 2, '.', ','),1,0,'R');
        $pdf->Cell(26,5,$debito > 0 ? number_format($debito, 2, '.', ',') : '',1,0,'R');
        $pdf->Cell(26,5,$credito > 0 ? number_format($credito, 2, '.', ',') : '',1,0,'R');
        $pdf->Cell(27,5,number_format($saldoFinalFila, 2, '.', ','),1,1,'R');
    }

    $saldoFinalPeriodo = array_sum($saldoPorCuentaCorrida);

    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(217,225,242);
    // 40+38+20+28+45 = 171
    $pdf->Cell(171,6,convertir_texto('TOTALES DEL PERÍODO' . ($cuenta_banco == 'todas' ? ' (todas las cuentas)' : '') . ':'),1,0,'R',true);
    $pdf->Cell(30,6,number_format($saldoInicialPeriodo, 2, '.', ','),1,0,'R',true);
    $pdf->Cell(26,6,number_format($totalDebito, 2, '.', ','),1,0,'R',true);
    $pdf->Cell(26,6,number_format($totalCredito, 2, '.', ','),1,0,'R',true);
    $pdf->Cell(27,6,number_format($saldoFinalPeriodo, 2, '.', ','),1,1,'R',true);
}

$pdf->Ln(5);
$pdf->SetFont('Arial','I',8);
$pdf->SetFillColor(240,240,240);
$pdf->Cell(0,5,convertir_texto('Información del Reporte:'),0,1,'L',true);
$pdf->Cell(0,4,convertir_texto('Generado el: ').date('Y-m-d H:i:s'),0,1,'L');
$pdf->Cell(0,4,convertir_texto('Total de movimientos: ').$totalMovimientos,0,1,'L');

$pdf->Output('I', 'Libro_Bancos_' . date('Ymd_His') . '.pdf');
