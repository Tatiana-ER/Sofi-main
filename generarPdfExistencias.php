<?php
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolectar datos del formulario
    $codigo = $_POST['codigo'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $saldo = $_POST['saldo'] ?? '';
    $total = $_POST['total'] ?? '';
    $fechaDesde = $_POST['fechaDesde'] ?? '';
    $fechaHasta = $_POST['fechaHasta'] ?? '';

    // Crear el contenido HTML para el PDF
    $html = "
    <h2>Informe de Existencias</h2>
    <p><strong>Fecha de corte:</strong> Desde $fechaDesde hasta $fechaHasta</p>
    <table border='1' cellpadding='8' cellspacing='0' width='100%'>
        <thead>
            <tr>
                <th>Código de producto</th>
                <th>Nombre producto</th>
                <th>Saldo cantidades</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>$codigo</td>
                <td>$nombre</td>
                <td>$saldo</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th colspan='2'>TOTAL</th>
                <th>$total</th>
            </tr>
        </tfoot>
    </table>
    ";

    // Instanciar Dompdf y generar el PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("existencias_producto_$codigo.pdf", ["Attachment" => true]);
    exit;
}
?>
