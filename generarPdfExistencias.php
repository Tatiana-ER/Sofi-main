<?php
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolectar datos del formulario (arrays)
    $codigos = $_POST['codigos'] ?? [];
    $nombres = $_POST['nombres'] ?? [];
    $saldos = $_POST['saldos'] ?? [];
    $total = $_POST['total'] ?? 0;
    $fechaDesde = $_POST['fechaDesde'] ?? '';
    $fechaHasta = $_POST['fechaHasta'] ?? '';

    // Crear las filas dinámicamente
    $filas = '';
    if (!empty($codigos)) {
        for ($i = 0; $i < count($codigos); $i++) {
            $codigo = htmlspecialchars($codigos[$i]);
            $nombre = htmlspecialchars($nombres[$i]);
            $saldo = htmlspecialchars($saldos[$i]);

            $filas .= "
                <tr>
                    <td>$codigo</td>
                    <td>$nombre</td>
                    <td style='text-align:right;'>$saldo</td>
                </tr>
            ";
        }
    } else {
        $filas = "
            <tr>
                <td colspan='3' style='text-align:center;'>No hay productos seleccionados</td>
            </tr>
        ";
    }

    // Crear el contenido HTML para el PDF
    $html = "
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 8px; }
        th { background-color: #f2f2f2; text-align: center; }
        tfoot th { background-color: #e0e0e0; font-weight: bold; }
    </style>

    <h2>Informe de Existencias</h2>
    <p><strong>Fecha de corte:</strong> Desde $fechaDesde hasta $fechaHasta</p>

    <table>
        <thead>
            <tr>
                <th>Código de producto</th>
                <th>Nombre producto</th>
                <th>Saldo cantidades</th>
            </tr>
        </thead>
        <tbody>
            $filas
        </tbody>
        <tfoot>
            <tr>
                <th colspan='2' style='text-align:right;'>TOTAL</th>
                <th style='text-align:right;'>$total</th>
            </tr>
        </tfoot>
    </table>
    ";

    // Instanciar Dompdf y generar el PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Nombre del archivo
    $dompdf->stream("informe_existencias.pdf", ["Attachment" => true]);
    exit;
}
?>
