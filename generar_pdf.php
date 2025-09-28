<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $fecha = $_POST['fecha'];
    $identificacion = $_POST['identificacion'];
    $nombreCliente = $_POST['nombreCliente'];
    $totalCartera = $_POST['totalCartera'];
    $valorAnticipos = $_POST['valorAnticipos'];
    $saldoPagar = $_POST['saldoPagar'];

    // Crear el HTML que se convertirá en PDF
    $html = "
    <h2>Informe de Cartera</h2>
    <p><strong>Fecha de corte:</strong> $fecha</p>
    <p><strong>Cédula:</strong> $cedula</p>
    <p><strong>Nombre proveedor:</strong> $nombre</p>

    <table border='1' cellpadding='5' cellspacing='0' width='100%'>
        <thead>
            <tr>
                <th>Identificación</th>
                <th>Nombre del cliente</th>
                <th>Total Cartera</th>
                <th>Valor Anticipos</th>
                <th>Saldo por pagar</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>$identificacion</td>
                <td>$nombreCliente</td>
                <td>$totalCartera</td>
                <td>$valorAnticipos</td>
                <td>$saldoPagar</td>
            </tr>
        </tbody>
    </table>
    ";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("reporte_cartera.pdf", ["Attachment" => true]);
    exit;
}
?>