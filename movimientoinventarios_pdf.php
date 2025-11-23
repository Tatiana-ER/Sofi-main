<?php
require('libs/fpdf/fpdf.php');

// ================== RECIBIR DATOS POR POST ==================
$datosMovimientos = isset($_POST['datosMovimientos']) ? json_decode($_POST['datosMovimientos'], true) : null;
$filtros = isset($_POST['filtros']) ? json_decode($_POST['filtros'], true) : null;

// Verificar que se recibieron los datos
if (!$datosMovimientos) {
    die('Error: No se recibieron datos para generar el PDF');
}

// ================== FUNCIÓN PARA CONVERTIR TEXTO ==================
function convertText($text) {
    return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
}

// ================== EXTRAER DATOS ==================
$movimientos = $datosMovimientos['movimientos'];
$totales = $datosMovimientos['totales'];

// ================== CREAR PDF CON FPDF ==================
class PDF extends FPDF {
    private $title = 'MOVIMIENTO DE INVENTARIOS';
    
    function Header() {
        if (file_exists('./Img/sofilogo5pequeño.png')) {
            $this->Image('./Img/sofilogo5pequeño.png', 10, 8, 20);
        }
        
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, convertText($this->title), 0, 1, 'C');
        
        $this->SetLineWidth(0.5);
        $this->Line(10, 30, 287, 30); // Línea más larga para orientación horizontal
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, convertText('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    function FiltrosAplicados($filtros) {
        if (!$filtros) return;
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, convertText('Filtros aplicados:'), 0, 1, 'L');
        $this->SetFont('Arial', '', 9);
        
        $hayFiltros = false;
        
        if (!empty($filtros['fechaDesde']) || !empty($filtros['fechaHasta'])) {
            $textoFecha = 'Período: ';
            if (!empty($filtros['fechaDesde'])) {
                $textoFecha .= date('d/m/Y', strtotime($filtros['fechaDesde']));
            }
            if (!empty($filtros['fechaHasta'])) {
                $textoFecha .= ' - ' . date('d/m/Y', strtotime($filtros['fechaHasta']));
            }
            $this->Cell(0, 5, convertText($textoFecha), 0, 1, 'L');
            $hayFiltros = true;
        }
        
        if (!empty($filtros['categoria']) && $filtros['categoria'] != 'Todas las categorías') {
            $this->Cell(0, 5, convertText('Categoría: ' . $filtros['categoria']), 0, 1, 'L');
            $hayFiltros = true;
        }
        
        if (!empty($filtros['producto']) && $filtros['producto'] != 'Todos los productos') {
            $this->Cell(0, 5, convertText('Producto: ' . $filtros['producto']), 0, 1, 'L');
            $hayFiltros = true;
        }
        
        if (!empty($filtros['tipo']) && $filtros['tipo'] != 'Todos') {
            $this->Cell(0, 5, convertText('Tipo: ' . $filtros['tipo']), 0, 1, 'L');
            $hayFiltros = true;
        }
        
        if (!$hayFiltros) {
            $this->Cell(0, 5, convertText('Sin filtros - Mostrando todos los movimientos'), 0, 1, 'L');
        }
        
        $this->Ln(3);
    }
    
    function TableHeader() {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(5, 74, 133);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(22, 8, convertText('Código'), 1, 0, 'C', true);
        $this->Cell(55, 8, convertText('Nombre Producto'), 1, 0, 'C', true);
        $this->Cell(28, 8, convertText('Comprobante'), 1, 0, 'C', true);
        $this->Cell(22, 8, convertText('Fecha'), 1, 0, 'C', true);
        $this->Cell(25, 8, convertText('Cant. Inicial'), 1, 0, 'C', true);
        $this->Cell(25, 8, convertText('Cant. Entrada'), 1, 0, 'C', true);
        $this->Cell(25, 8, convertText('Cant. Salida'), 1, 0, 'C', true);
        $this->Cell(25, 8, convertText('Saldo'), 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0);
    }
    
    function TableRow($fila, $fill) {
        $this->SetFont('Arial', '', 8);
        
        // Alternar color de fondo
        if ($fill) {
            $this->SetFillColor(245, 245, 245);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        $this->Cell(22, 6, $fila['codigoProducto'], 1, 0, 'L', $fill);
        $this->Cell(55, 6, convertText(substr($fila['nombreProducto'], 0, 35)), 1, 0, 'L', $fill);
        $this->Cell(28, 6, $fila['comprobante'], 1, 0, 'C', $fill);
        $this->Cell(22, 6, $fila['fecha'], 1, 0, 'C', $fill);
        $this->Cell(25, 6, number_format($fila['cantidadInicial'], 0, ',', '.'), 1, 0, 'R', $fill);
        $this->Cell(25, 6, number_format($fila['cantidadEntrada'], 0, ',', '.'), 1, 0, 'R', $fill);
        $this->Cell(25, 6, number_format($fila['cantidadSalida'], 0, ',', '.'), 1, 0, 'R', $fill);
        $this->Cell(25, 6, number_format($fila['saldo'], 0, ',', '.'), 1, 1, 'R', $fill);
    }
    
    function TableTotal($totales) {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220, 220, 220);
        
        $this->Cell(127, 7, convertText('TOTAL'), 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totales['totalInicial'], 0, ',', '.'), 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totales['totalEntrada'], 0, ',', '.'), 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totales['totalSalida'], 0, ',', '.'), 1, 0, 'R', true);
        $this->Cell(25, 7, number_format($totales['totalSaldo'], 0, ',', '.'), 1, 1, 'R', true);
    }
}

// Crear instancia del PDF en orientación horizontal
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('L'); // 'L' = Landscape (Horizontal)

// Mostrar filtros aplicados
$pdf->FiltrosAplicados($filtros);

// Verificar si hay datos
if (count($movimientos) == 0) {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, convertText('No se encontraron movimientos con los filtros seleccionados'), 0, 1, 'C');
} else {
    // Tabla de movimientos
    $pdf->TableHeader();
    
    $fill = false;
    foreach ($movimientos as $fila) {
        // Si se llega al final de la página, crear nueva página
        if ($pdf->GetY() > 180) {
            $pdf->AddPage('L');
            $pdf->TableHeader();
            $fill = false;
        }
        
        $pdf->TableRow($fila, $fill);
        $fill = !$fill;
    }
    
    // Fila de totales
    $pdf->TableTotal($totales);
}
$pdf->Ln(15);

// Salida del PDF - Descargar directamente
$filename = 'Movimiento_Inventarios_' . date('Y-m-d_His') . '.pdf';
$pdf->Output('D', $filename); // 'D' para descargar directamente
?>