<?php
require('libs/fpdf/fpdf.php');

// Validar que vengan los datos necesarios
if (!isset($_POST['datosProveedores']) || !isset($_POST['fecha'])) {
    die('Error: No se recibieron los datos necesarios');
}

$datosProveedores = json_decode($_POST['datosProveedores'], true);
$fechaCorte = $_POST['fecha'];

// Validar que existan proveedores
if (!isset($datosProveedores['proveedores']) || count($datosProveedores['proveedores']) === 0) {
    die('Error: No hay proveedores para generar el PDF');
}

class PDF extends FPDF
{
    private $fechaCorte;
    
    function setFechaCorte($fecha) {
        $this->fechaCorte = $fecha;
    }
    
    // Cabecera de página
    function Header()
    {
        // Logo (si tienes uno)
        // $this->Image('logo.png', 10, 6, 30);
        
        // Título
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(52, 73, 94);
        $this->Cell(0, 10, utf8_decode('CUÁNTO DEBO'), 0, 1, 'C');
        
        // Subtítulo
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, utf8_decode('Consulte el estado de cuentas por pagar a un proveedor específico'), 0, 1, 'C');
        
        // Fecha de corte
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(0, 0, 0);
        $fechaFormateada = date('d/m/Y', strtotime($this->fechaCorte));
        $this->Cell(0, 8, utf8_decode('Fecha de Corte: ') . $fechaFormateada, 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
    
    // Tabla de proveedores
    function TablaProveedores($proveedores, $totales)
    {
        // Título de la sección
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(52, 73, 94);
        $this->Cell(0, 10, utf8_decode('ESTADO DE CUENTAS POR PAGAR'), 0, 1, 'C');
        $this->Ln(3);
        
        // Colores y fuente de la cabecera
        $this->SetFillColor(13, 110, 253); // Color azul (bootstrap primary)
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 8);
        
        // Cabecera de la tabla - ACTUALIZADO con 6 columnas
        $anchos = array(25, 50, 28, 28, 28, 28);
        $headers = array(
            utf8_decode('Identificación'),
            'Nombre del Proveedor',
            'Total Cartera',
            'Pagos Realizados',
            'Valor Anticipos',
            'Saldo por Pagar'
        );
        
        for($i = 0; $i < count($headers); $i++) {
            $this->Cell($anchos[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Restaurar colores para el contenido
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
        
        // Datos de los proveedores
        $fill = false;
        foreach($proveedores as $proveedor) {
            // Color alternado para las filas
            if ($fill) {
                $this->SetFillColor(248, 249, 250);
            } else {
                $this->SetFillColor(255, 255, 255);
            }
            
            // Verificar si existe valorAnticipos, si no, usar 0
            $valorAnticipos = isset($proveedor['valorAnticipos']) ? $proveedor['valorAnticipos'] : 0;
            
            $this->Cell($anchos[0], 7, $proveedor['identificacion'], 1, 0, 'C', true);
            $this->Cell($anchos[1], 7, utf8_decode(substr($proveedor['nombre'], 0, 30)), 1, 0, 'L', true);
            $this->Cell($anchos[2], 7, number_format($proveedor['totalAdeudado'], 2), 1, 0, 'R', true);
            $this->Cell($anchos[3], 7, number_format($proveedor['valorPagos'], 2), 1, 0, 'R', true);
            $this->Cell($anchos[4], 7, number_format($valorAnticipos, 2), 1, 0, 'R', true);
            $this->Cell($anchos[5], 7, number_format($proveedor['saldoPagar'], 2), 1, 0, 'R', true);
            $this->Ln();
            
            $fill = !$fill;
        }
        
        // Fila de totales
        $this->SetFillColor(248, 249, 250);
        $this->SetFont('Arial', 'B', 9);
        
        // Verificar si existe totalAnticipos en los totales
        $totalAnticipos = isset($totales['totalAnticipos']) ? $totales['totalAnticipos'] : 0;
        
        $this->Cell($anchos[0] + $anchos[1], 8, 'TOTAL', 1, 0, 'C', true);
        $this->Cell($anchos[2], 8, number_format($totales['totalAdeudado'], 2), 1, 0, 'R', true);
        $this->Cell($anchos[3], 8, number_format($totales['totalPagos'], 2), 1, 0, 'R', true);
        $this->Cell($anchos[4], 8, number_format($totalAnticipos, 2), 1, 0, 'R', true);
        $this->Cell($anchos[5], 8, number_format($totales['totalSaldo'], 2), 1, 0, 'R', true);
        $this->Ln();
    }
    
}
// Crear instancia del PDF
$pdf = new PDF('L', 'mm', 'Letter'); // Orientación horizontal
$pdf->AliasNbPages();
$pdf->setFechaCorte($fechaCorte);
$pdf->AddPage();

// Generar tabla
$pdf->TablaProveedores($datosProveedores['proveedores'], $datosProveedores['totales']);


// Información adicional al final
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, utf8_decode('Universidad de Santander - Ingeniería de Software'), 0, 1, 'C');
$pdf->Cell(0, 5, utf8_decode('Todos los derechos reservados © 2025'), 0, 1, 'C');
$pdf->Cell(0, 5, utf8_decode('Creado por iniciativa del programa de Contaduría Pública'), 0, 1, 'C');

// Generar fecha y hora de generación
$fechaGeneracion = date('d/m/Y H:i:s');
$pdf->Ln(3);    
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 5, utf8_decode('Documento generado el: ') . $fechaGeneracion, 0, 1, 'C');

// Salida del PDF
$nombreArchivo = 'Estado_Cuentas_Pagar_' . date('Y-m-d_His') . '.pdf';
$pdf->Output('I', $nombreArchivo); // 'I' para mostrar en navegador, 'D' para descargar
?>