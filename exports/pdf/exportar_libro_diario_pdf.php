<?php
// ================== EXPORTAR LIBRO DIARIO A PDF ==================
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

// ================== FILTROS (mismos que ver_libro_diario.php) ==================
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo_documento = $_GET['tipo_documento'] ?? '';
$codigo_cuenta = $_GET['codigo_cuenta'] ?? '';

$etiquetas_tipo = [
    'factura_venta' => 'Factura Venta',
    'factura_compra' => 'Factura Compra',
    'recibo_caja' => 'Recibo de Caja',
    'comprobante_egreso' => 'Comprobante Egreso',
    'comprobante_contable' => 'Comprobante Contable'
];

// ================== CONSULTA (idéntica a ver_libro_diario.php) ==================
$sql = "SELECT 
            ld.id,
            ld.fecha,
            ld.tipo_documento,
            ld.numero_documento,
            ld.codigo_cuenta,
            ld.nombre_cuenta,
            ld.tercero_identificacion,
            ld.tercero_nombre,
            ld.concepto,
            ld.debito,
            ld.credito
        FROM libro_diario ld
        WHERE ld.fecha BETWEEN :fecha_inicio AND :fecha_fin";

if (!empty($tipo_documento)) {
    $sql .= " AND ld.tipo_documento = :tipo_documento";
}

if (!empty($codigo_cuenta)) {
    $sql .= " AND ld.codigo_cuenta LIKE :codigo_cuenta";
}

$sql .= " ORDER BY ld.fecha ASC, ld.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);

if (!empty($tipo_documento)) {
    $stmt->bindParam(':tipo_documento', $tipo_documento);
}

if (!empty($codigo_cuenta)) {
    $codigo_busqueda = $codigo_cuenta . '%';
    $stmt->bindParam(':codigo_cuenta', $codigo_busqueda);
}

$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== GENERAR PDF ==================
class PDF extends FPDF {
    private $fecha_inicio;
    private $fecha_fin;
    private $tipo_documento;
    private $codigo_cuenta;
    private $nombre_empresa;
    private $nit_empresa;
    private $etiquetas_tipo;

    function __construct($desde, $hasta, $tipo, $cuenta, $nombre_emp, $nit_emp, $etiquetas) {
        parent::__construct('L','mm','A4');
        $this->fecha_inicio = $desde;
        $this->fecha_fin = $hasta;
        $this->tipo_documento = $tipo;
        $this->codigo_cuenta = $cuenta;
        $this->nombre_empresa = $nombre_emp;
        $this->nit_empresa = $nit_emp;
        $this->etiquetas_tipo = $etiquetas;
    }

    function Header() {
        if (file_exists('assets/img/logo.png')) {
            $this->Image('assets/img/logo.png', 10, 8, 33);
        }

        $this->SetFont('Arial','B',14);
        $this->Cell(0,8,convertir_texto('LIBRO DIARIO'),0,1,'C');

        $this->SetFont('Arial','B',10);
        $this->Cell(0,6,convertir_texto($this->nombre_empresa),0,1,'C');
        $this->Cell(0,6,$this->nit_empresa,0,1,'C');

        $this->SetFont('Arial','',9);
        $this->Cell(0,6,convertir_texto('PERIODO: ') . date('d/m/Y', strtotime($this->fecha_inicio)) . ' A ' . date('d/m/Y', strtotime($this->fecha_fin)),0,1,'C');

        if ($this->tipo_documento != '') {
            $etiqueta = $this->etiquetas_tipo[$this->tipo_documento] ?? $this->tipo_documento;
            $this->Cell(0,6,convertir_texto('TIPO DOCUMENTO: ') . convertir_texto($etiqueta),0,1,'C');
        }
        if ($this->codigo_cuenta != '') {
            $this->Cell(0,6,convertir_texto('CUENTA: ') . $this->codigo_cuenta,0,1,'C');
        }

        $this->Ln(5);

        $this->SetFont('Arial','B',8);
        $this->SetFillColor(5,74,133);
        $this->SetTextColor(255,255,255);

        $this->Cell(20,6,convertir_texto('Fecha'),1,0,'C',true);
        $this->Cell(28,6,convertir_texto('Tipo Doc.'),1,0,'C',true);
        $this->Cell(20,6,convertir_texto('No. Doc.'),1,0,'C',true);
        $this->Cell(22,6,convertir_texto('Cód. Cuenta'),1,0,'C',true);
        $this->Cell(45,6,convertir_texto('Nombre Cuenta'),1,0,'C',true);
        $this->Cell(45,6,convertir_texto('Tercero'),1,0,'C',true);
        $this->Cell(50,6,convertir_texto('Concepto'),1,0,'C',true);
        $this->Cell(25,6,convertir_texto('Débito'),1,0,'C',true);
        $this->Cell(25,6,convertir_texto('Crédito'),1,1,'C',true);

        $this->SetTextColor(0,0,0);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,convertir_texto('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF($fecha_inicio, $fecha_fin, $tipo_documento, $codigo_cuenta, $nombre_empresa, $nit_empresa, $etiquetas_tipo);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',7);

$total_debito = 0;
$total_credito = 0;

if (count($movimientos) == 0) {
    $pdf->SetFont('Arial','I',9);
    $pdf->Cell(0,10,convertir_texto('No hay movimientos registrados en el período seleccionado.'),0,1,'C');
} else {
    foreach ($movimientos as $mov) {
        $debito = floatval($mov['debito']);
        $credito = floatval($mov['credito']);
        $total_debito += $debito;
        $total_credito += $credito;

        $etiqueta_tipo = $etiquetas_tipo[$mov['tipo_documento']] ?? $mov['tipo_documento'];

        $tercero = trim($mov['tercero_identificacion'] . ' ' . $mov['tercero_nombre']);

        $pdf->Cell(20,5,date('d/m/Y', strtotime($mov['fecha'])),1,0,'C');
        $pdf->Cell(28,5,convertir_texto(substr($etiqueta_tipo, 0, 18)),1,0,'L');
        $pdf->Cell(20,5,convertir_texto(substr($mov['numero_documento'], 0, 12)),1,0,'C');
        $pdf->Cell(22,5,convertir_texto(substr($mov['codigo_cuenta'], 0, 14)),1,0,'L');
        $pdf->Cell(45,5,convertir_texto(substr($mov['nombre_cuenta'], 0, 30)),1,0,'L');
        $pdf->Cell(45,5,convertir_texto(substr($tercero, 0, 30)),1,0,'L');
        $pdf->Cell(50,5,convertir_texto(substr($mov['concepto'], 0, 34)),1,0,'L');
        $pdf->Cell(25,5,$debito > 0 ? number_format($debito, 2, '.', ',') : '',1,0,'R');
        $pdf->Cell(25,5,$credito > 0 ? number_format($credito, 2, '.', ',') : '',1,1,'R');
    }

    // Fila de totales
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(217,225,242);
    // 20+28+20+22+45+45+50 = 230
    $pdf->Cell(230,6,convertir_texto('TOTALES:'),1,0,'R',true);
    $pdf->Cell(25,6,number_format($total_debito, 2, '.', ','),1,0,'R',true);
    $pdf->Cell(25,6,number_format($total_credito, 2, '.', ','),1,1,'R',true);

    // Fila de diferencia (cuadrado / descuadrado)
    $diferencia = abs($total_debito - $total_credito);
    $cuadrado = $diferencia < 0.01;
    if ($cuadrado) {
        $pdf->SetFillColor(212,237,218);
        $pdf->SetTextColor(21,87,36);
    } else {
        $pdf->SetFillColor(248,215,218);
        $pdf->SetTextColor(114,28,36);
    }
    $pdf->Cell(230,6,convertir_texto('DIFERENCIA:'),1,0,'R',true);
    $pdf->Cell(50,6,'$' . number_format($diferencia, 2, '.', ',') . ' - ' . convertir_texto($cuadrado ? 'CUADRADO' : 'DESCUADRADO'),1,1,'C',true);
    $pdf->SetTextColor(0,0,0);
}

$pdf->Ln(5);
$pdf->SetFont('Arial','I',8);
$pdf->SetFillColor(240,240,240);
$pdf->Cell(0,5,convertir_texto('Información del Reporte:'),0,1,'L',true);
$pdf->Cell(0,4,convertir_texto('Generado el: ').date('Y-m-d H:i:s'),0,1,'L');
$pdf->Cell(0,4,convertir_texto('Total de movimientos: ').count($movimientos),0,1,'L');

$pdf->Output('I', 'Libro_Diario_' . date('Ymd_His') . '.pdf');