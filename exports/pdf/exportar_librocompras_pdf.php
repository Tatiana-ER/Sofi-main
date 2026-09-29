<?php
// ================== EXPORTAR LIBRO DE COMPRAS A PDF ==================
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

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-t');
$proveedor_identificacion = isset($_GET['proveedor']) ? $_GET['proveedor'] : '';

// ================== CONSULTA PRINCIPAL: FACTURAS DEL PERIODO ==================
$sql = "SELECT * FROM facturac WHERE fecha BETWEEN :desde AND :hasta";
$params = [':desde' => $fecha_desde, ':hasta' => $fecha_hasta];

if ($proveedor_identificacion != '') {
    $sql .= " AND identificacion = :proveedor";
    $params[':proveedor'] = $proveedor_identificacion;
}

$sql .= " ORDER BY fecha ASC, consecutivo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== BASE GRAVADA / BASE EXENTA POR FACTURA ==================
$stmtBases = $pdo->prepare(
    "SELECT 
        COALESCE(SUM(CASE WHEN iva > 0 THEN (precioUnitario * cantidad) ELSE 0 END), 0) as base_gravada,
        COALESCE(SUM(CASE WHEN iva = 0 THEN (precioUnitario * cantidad) ELSE 0 END), 0) as base_exenta
     FROM detallefacturac
     WHERE factura_id = :factura_id"
);

// ================== GENERAR PDF ==================
class PDF extends FPDF {
    private $fecha_desde;
    private $fecha_hasta;
    private $proveedor;
    private $nombre_empresa;
    private $nit_empresa;

    function __construct($desde, $hasta, $proveedor, $nombre_emp, $nit_emp) {
        parent::__construct('L','mm','A4');
        $this->fecha_desde = $desde;
        $this->fecha_hasta = $hasta;
        $this->proveedor = $proveedor;
        $this->nombre_empresa = $nombre_emp;
        $this->nit_empresa = $nit_emp;
    }

    function Header() {
        if (file_exists('assets/img/logo.png')) {
            $this->Image('assets/img/logo.png', 10, 8, 33);
        }

        $this->SetFont('Arial','B',14);
        $this->Cell(0,8,convertir_texto('LIBRO DE COMPRAS'),0,1,'C');

        $this->SetFont('Arial','B',10);
        $this->Cell(0,6,convertir_texto($this->nombre_empresa),0,1,'C');
        $this->Cell(0,6,$this->nit_empresa,0,1,'C');

        $this->SetFont('Arial','',9);
        $this->Cell(0,6,convertir_texto('PERIODO: ') . date('d/m/Y', strtotime($this->fecha_desde)) . ' A ' . date('d/m/Y', strtotime($this->fecha_hasta)),0,1,'C');

        if ($this->proveedor != '') {
            $this->Cell(0,6,convertir_texto('PROVEEDOR: ') . $this->proveedor,0,1,'C');
        }

        $this->Ln(5);

        $this->SetFont('Arial','B',8);
        $this->SetFillColor(5,74,133);
        $this->SetTextColor(255,255,255);

        $this->Cell(35,6,convertir_texto('Comprobante'),1,0,'C',true);
        $this->Cell(25,6,convertir_texto('Fecha Elab.'),1,0,'C',true);
        $this->Cell(35,6,convertir_texto('ID Tercero'),1,0,'C',true);
        $this->Cell(70,6,convertir_texto('Nombre Tercero'),1,0,'C',true);
        $this->Cell(28,6,convertir_texto('Base Gravada'),1,0,'C',true);
        $this->Cell(28,6,convertir_texto('Base Exenta'),1,0,'C',true);
        $this->Cell(24,6,convertir_texto('IVA'),1,0,'C',true);
        $this->Cell(28,6,convertir_texto('Total'),1,1,'C',true);

        $this->SetTextColor(0,0,0);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,convertir_texto('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF($fecha_desde, $fecha_hasta, $proveedor_identificacion, $nombre_empresa, $nit_empresa);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',7);

$totalBaseGravada = 0;
$totalBaseExenta = 0;
$totalIva = 0;
$totalGeneral = 0;

if (count($facturas) == 0) {
    $pdf->SetFont('Arial','I',9);
    $pdf->Cell(0,10,convertir_texto('No hay facturas de compra en el período seleccionado.'),0,1,'C');
} else {
    foreach ($facturas as $factura) {
        $stmtBases->execute([':factura_id' => $factura['id']]);
        $bases = $stmtBases->fetch(PDO::FETCH_ASSOC);
        $baseGravada = floatval($bases['base_gravada']);
        $baseExenta = floatval($bases['base_exenta']);

        $totalBaseGravada += $baseGravada;
        $totalBaseExenta += $baseExenta;
        $totalIva += floatval($factura['ivaTotal']);
        $totalGeneral += floatval($factura['valorTotal']);

        $comprobante = 'FC-' . (!empty($factura['numeroFactura']) ? $factura['numeroFactura'] : $factura['consecutivo']);

        $pdf->Cell(35,5,convertir_texto(substr($comprobante, 0, 22)),1,0,'L');
        $pdf->Cell(25,5,date('d/m/Y', strtotime($factura['fecha'])),1,0,'C');
        $pdf->Cell(35,5,convertir_texto(substr($factura['identificacion'], 0, 22)),1,0,'L');
        $pdf->Cell(70,5,convertir_texto(substr($factura['nombre'], 0, 46)),1,0,'L');
        $pdf->Cell(28,5,number_format($baseGravada, 2, '.', ','),1,0,'R');
        $pdf->Cell(28,5,number_format($baseExenta, 2, '.', ','),1,0,'R');
        $pdf->Cell(24,5,number_format($factura['ivaTotal'], 2, '.', ','),1,0,'R');
        $pdf->Cell(28,5,number_format($factura['valorTotal'], 2, '.', ','),1,1,'R');
    }

    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(217,225,242);
    // 35+25+35+70 = 165
    $pdf->Cell(165,6,convertir_texto('TOTALES (' . count($facturas) . ' factura' . (count($facturas) != 1 ? 's' : '') . '):'),1,0,'R',true);
    $pdf->Cell(28,6,number_format($totalBaseGravada, 2, '.', ','),1,0,'R',true);
    $pdf->Cell(28,6,number_format($totalBaseExenta, 2, '.', ','),1,0,'R',true);
    $pdf->Cell(24,6,number_format($totalIva, 2, '.', ','),1,0,'R',true);
    $pdf->Cell(28,6,number_format($totalGeneral, 2, '.', ','),1,1,'R',true);
}

$pdf->Ln(5);
$pdf->SetFont('Arial','I',8);
$pdf->SetFillColor(240,240,240);
$pdf->Cell(0,5,convertir_texto('Información del Reporte:'),0,1,'L',true);
$pdf->Cell(0,4,convertir_texto('Generado el: ').date('Y-m-d H:i:s'),0,1,'L');
$pdf->Cell(0,4,convertir_texto('Total de facturas: ').count($facturas),0,1,'L');

$pdf->Output('I', 'Libro_Compras_' . date('Ymd_His') . '.pdf');
