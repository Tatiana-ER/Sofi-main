<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

// Validar si llega por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $cedula = $_POST['cedula'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $identificacion = $_POST['identificacion'] ?? '';
    $nombreCliente = $_POST['nombreCliente'] ?? '';
    $totalCartera = $_POST['totalCartera'] ?? 0;
    $valorAnticipos = $_POST['valorAnticipos'] ?? 0;
    $saldoCobrar = $_POST['saldoCobrar'] ?? 0;

    $totalCarteraSum = $_POST['totalCarteraSum'] ?? 0;
    $totalAnticiposSum = $_POST['totalAnticiposSum'] ?? 0;
    $totalSaldoSum = $_POST['totalSaldoSum'] ?? 0;

    // Crear HTML para el PDF
    $html = "
    <h2>Informe de Cartera - CUÁNTO ME DEBEN</h2>
    <p><strong>Fecha de corte:</strong> $fecha</p>
    <p><strong>Cédula:</strong> $cedula</p>
    <p><strong>Nombre cliente:</strong> $nombres</p>

    <table border='1' cellpadding='5' cellspacing='0' width='100%'>
        <thead>
            <tr>
                <th>Identificación</th>
                <th>Nombre del cliente</th>
                <th>Total Cartera</th>
                <th>Valor Anticipos</th>
                <th>Saldo por cobrar</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>$identificacion</td>
                <td>$nombreCliente</td>
                <td>$totalCartera</td>
                <td>$valorAnticipos</td>
                <td>$saldoCobrar</td>
            </tr>
            <tr>
                <th colspan='2'>TOTAL</th>
                <td>$totalCarteraSum</td>
                <td>$totalAnticiposSum</td>
                <td>$totalSaldoSum</td>
            </tr>
        </tbody>
    </table>
    ";

    // Generar PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Descargar el PDF
    $dompdf->stream("reporte_cliente.pdf", ["Attachment" => true]);
    exit;
}
?>