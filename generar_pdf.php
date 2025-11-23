<?php
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos en formato JSON
    $datosClientes = isset($_POST['datosClientes']) ? json_decode($_POST['datosClientes'], true) : null;
    $fechaCorte = $_POST['fecha'] ?? date('Y-m-d');

    // Validar que los datos existan
    if (!$datosClientes || !isset($datosClientes['clientes'])) {
        die("Error: No se recibieron datos de clientes");
    }

    $clientes = $datosClientes['clientes'];
    $totales = $datosClientes['totales'];

    // Crear las filas dinámicamente
    $filas = '';
    if (!empty($clientes)) {
        foreach ($clientes as $cliente) {
            $identificacion = htmlspecialchars($cliente['identificacion']);
            $nombre = htmlspecialchars($cliente['nombre']);
            $totalFacturado = number_format($cliente['totalFacturado'], 2);
            $valorAnticipos = number_format($cliente['valorAnticipos'], 2);
            $abonosRealizados = number_format($cliente['abonosRealizados'], 2);
            $saldoCobrar = number_format($cliente['saldoCobrar'], 2);

            $filas .= "
                <tr>
                    <td>$identificacion</td>
                    <td>$nombre</td>
                    <td style='text-align:right;'>$totalFacturado</td>
                    <td style='text-align:right;'>$valorAnticipos</td>
                    <td style='text-align:right;'>$abonosRealizados</td>
                    <td style='text-align:right;'>$saldoCobrar</td>
                </tr>
            ";
        }
    } else {
        $filas = "
            <tr>
                <td colspan='6' style='text-align:center;'>No hay clientes seleccionados</td>
            </tr>
        ";
    }

    // Formatear totales
    $totalGeneralFacturado = number_format($totales['totalFacturado'], 2);
    $totalGeneralAnticipos = number_format($totales['totalAnticipos'], 2);
    $totalGeneralAbonos = number_format($totales['totalAbonos'], 2);
    $totalGeneralSaldo = number_format($totales['totalSaldo'], 2);

    // Formatear fecha de corte
    $fechaFormateada = date('d/m/Y', strtotime($fechaCorte));

    // Crear el contenido HTML para el PDF
    $html = "
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 10px; color: #0d6efd; }
        h3 { margin-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 8px; }
        th { background-color: #0d6efd; color: white; text-align: center; }
        tfoot th { background-color: #f8f9fa; font-weight: bold; color: #000; border: 1px solid #000; }
        .info-cliente { margin-bottom: 15px; }
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            font-size: 10px; 
            color: #666;
        }
    </style>

    <h2>CUÁNTO ME DEBEN</h2>
    <p style='text-align: center; color: #666; margin-bottom: 20px;'>
        Consulte el estado de cartera de un cliente específico
    </p>
    
    <div class='info-cliente'>
        <p><strong>Fecha de Corte:</strong> $fechaFormateada</p>
    </div>

    <h3 style='text-align: center; color: #333; margin-bottom: 10px;'>ESTADO DE CUENTA</h3>

    <table>
        <thead>
            <tr>
                <th>Identificación</th>
                <th>Nombre del Cliente</th>
                <th>Total Facturado</th>
                <th>Valor Anticipos</th>
                <th>Abonos Realizados</th>
                <th>Saldo por Cobrar</th>
            </tr>
        </thead>
        <tbody>
            $filas
        </tbody>
        <tfoot>
            <tr>
                <th colspan='2' style='text-align:center;'>TOTAL</th>
                <th style='text-align:right;'>$totalGeneralFacturado</th>
                <th style='text-align:right;'>$totalGeneralAnticipos</th>
                <th style='text-align:right;'>$totalGeneralAbonos</th>
                <th style='text-align:right;'>$totalGeneralSaldo</th>
            </tr>
        </tfoot>
    </table>

    <div class='footer'>
        <p>Universidad de Santander - Ingeniería de Software</p>
        <p>Todos los derechos reservados © 2025</p>
        <p>Creado por iniciativa del programa de Contaduría Pública</p>
        <p style='margin-top: 10px;'><strong>Documento generado el:</strong> " . date('d/m/Y H:i:s') . "</p>
    </div>
    ";

    // Instanciar Dompdf y generar el PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape'); // Usar horizontal para mejor visualización
    $dompdf->render();

    // Nombre del archivo
    $dompdf->stream("estado_cuenta_clientes.pdf", ["Attachment" => true]);
    exit;
}
?>