<?php
require('libs/fpdf/fpdf.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolectar datos del formulario (arrays)
    $identificaciones = $_POST['identificaciones'] ?? [];
    $nombres = $_POST['nombres'] ?? [];
    $totalAdeudado = $_POST['totalAdeudado'] ?? [];
    $valorPagos = $_POST['valorPagos'] ?? [];
    $saldoPagar = $_POST['saldoPagar'] ?? [];
    $totalGeneralAdeudado = $_POST['totalGeneralAdeudado'] ?? 0;
    $totalGeneralPagos = $_POST['totalGeneralPagos'] ?? 0;
    $totalGeneralSaldo = $_POST['totalGeneralSaldo'] ?? 0;
    $fechaCorte = $_POST['fechaCorte'] ?? '';

    // Validar que existan proveedores
    if (empty($identificaciones)) {
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
            $this->SetTextColor(220, 53, 69); // Color rojo
            $this->Cell(0, 10, utf8_decode('CUÁNTO DEBO'), 0, 1, 'C');
            
            // Subtítulo
            $this->SetFont('Arial', '', 11);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 6, utf8_decode('Consulte el estado de cuentas por pagar a un proveedor específico'), 0, 1, 'C');
            
            // Fecha de corte
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(0, 0, 0);
            $fechaFormateada = date('d/m/Y', strtotime($this->fechaCorte));
            $this->Cell(0, 8, utf8_decode('Fecha de Corte: ') . $fechaFormateada, 0, 1, 'L');
            
            $this->Ln(3);
        }
        
        // Pie de página
        function Footer()
        {
            $this->SetY(-25);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 4, utf8_decode('Universidad de Santander - Ingeniería de Software'), 0, 1, 'C');
            $this->Cell(0, 4, utf8_decode('Todos los derechos reservados © 2025'), 0, 1, 'C');
            $this->Cell(0, 4, utf8_decode('Creado por iniciativa del programa de Contaduría Pública'), 0, 1, 'C');
            
            // Fecha de generación
            $fechaGeneracion = date('d/m/Y H:i:s');
            $this->Ln(1);
            $this->SetFont('Arial', '', 7);
            $this->Cell(0, 3, utf8_decode('Documento generado el: ') . $fechaGeneracion, 0, 1, 'C');
            
            // Número de página
            $this->SetY(-8);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
        }
        
        // Tabla de proveedores
        function TablaProveedores($proveedores, $totales)
        {
            // Título de la sección
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(52, 73, 94);
            $this->Cell(0, 8, utf8_decode('ESTADO DE CUENTAS POR PAGAR'), 0, 1, 'C');
            $this->Ln(2);
            
            // Colores y fuente de la cabecera
            $this->SetFillColor(220, 53, 69); // Color rojo
            $this->SetTextColor(255, 255, 255);
            $this->SetDrawColor(200, 200, 200);
            $this->SetLineWidth(.3);
            $this->SetFont('Arial', 'B', 9);
            
            // Cabecera de la tabla
            $anchos = array(30, 60, 33, 33, 33);
            $headers = array(
                utf8_decode('Identificación'),
                'Nombre del Proveedor',
                'Total Adeudado',
                'Valor Pagos',
                'Saldo por Pagar'
            );
            
            for($i = 0; $i < count($headers); $i++) {
                $this->Cell($anchos[$i], 8, $headers[$i], 1, 0, 'C', true);
            }
            $this->Ln();
            
            // Restaurar colores para el contenido
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', '', 9);
            
            // Datos de los proveedores
            $fill = false;
            for ($i = 0; $i < count($proveedores['identificaciones']); $i++) {
                // Color alternado para las filas
                if ($fill) {
                    $this->SetFillColor(248, 249, 250);
                } else {
                    $this->SetFillColor(255, 255, 255);
                }
                
                $this->Cell($anchos[0], 7, $proveedores['identificaciones'][$i], 1, 0, 'C', true);
                $this->Cell($anchos[1], 7, utf8_decode(substr($proveedores['nombres'][$i], 0, 35)), 1, 0, 'L', true);
                $this->Cell($anchos[2], 7, $proveedores['totalAdeudado'][$i], 1, 0, 'R', true);
                $this->Cell($anchos[3], 7, $proveedores['valorPagos'][$i], 1, 0, 'R', true);
                $this->Cell($anchos[4], 7, $proveedores['saldoPagar'][$i], 1, 0, 'R', true);
                $this->Ln();
                
                $fill = !$fill;
            }
            
            // Fila de totales
            $this->SetFillColor(248, 249, 250);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell($anchos[0] + $anchos[1], 8, 'TOTAL', 1, 0, 'C', true);
            $this->Cell($anchos[2], 8, $totales['totalAdeudado'], 1, 0, 'R', true);
            $this->Cell($anchos[3], 8, $totales['totalPagos'], 1, 0, 'R', true);
            $this->Cell($anchos[4], 8, $totales['totalSaldo'], 1, 0, 'R', true);
            $this->Ln();
        }
    }

    // Preparar datos para FPDF
    $proveedores = array(
        'identificaciones' => $identificaciones,
        'nombres' => $nombres,
        'totalAdeudado' => $totalAdeudado,
        'valorPagos' => $valorPagos,
        'saldoPagar' => $saldoPagar
    );

    $totales = array(
        'totalAdeudado' => $totalGeneralAdeudado,
        'totalPagos' => $totalGeneralPagos,
        'totalSaldo' => $totalGeneralSaldo
    );

    // Crear instancia del PDF
    $pdf = new PDF('L', 'mm', 'Letter'); // Orientación horizontal
    $pdf->AliasNbPages();
    $pdf->setFechaCorte($fechaCorte);
    $pdf->AddPage();

    // Generar tabla
    $pdf->TablaProveedores($proveedores, $totales);

    // Salida del PDF
    $nombreArchivo = 'Estado_Cuentas_Pagar_' . date('Y-m-d_His') . '.pdf';
    $pdf->Output('I', $nombreArchivo); // 'I' para mostrar en navegador, 'D' para descargar
    exit;
}
?>