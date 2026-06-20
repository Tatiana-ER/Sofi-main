<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Función para convertir caracteres especiales
    function convertir_texto($texto) {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }

    // Obtener los datos en formato JSON
    $datosClientes = isset($_POST['datosClientes']) ? json_decode($_POST['datosClientes'], true) : null;
    $fechaCorte = $_POST['fecha'] ?? date('Y-m-d');

    // Validar que los datos existan
    if (!$datosClientes || !isset($datosClientes['clientes'])) {
        die("Error: No se recibieron datos de clientes");
    }

    $clientes = $datosClientes['clientes'];
    $totales = $datosClientes['totales'];

    // Formatear fecha
    $fechaFormateada = date('d/m/Y', strtotime($fechaCorte));

    // Limpiar buffer de salida
    if (ob_get_length()) ob_end_clean();

    // Headers para Excel
    header('Content-Type: application/vnd.ms-excel; charset=ISO-8859-1');
    header('Content-Disposition: attachment;filename="cuanto_me_deben_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    // ================== GENERAR CONTENIDO EXCEL ==================
    echo "<!DOCTYPE html><html><head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\">";
    echo "<style>";
    echo "td { mso-number-format:\\@; padding: 3px; border: 1px solid #ccc; }";
    echo ".numero { mso-number-format:'#,##0.00'; }";
    echo ".texto { mso-number-format:'\\@'; }";
    echo "</style>";
    echo "</head><body>";
    echo "<table border='1' cellpadding='3' cellspacing='0'>";
    
    // Títulos
    echo "<tr><td colspan='6' style='background-color:#0d6efd;color:white;font-weight:bold;text-align:center;'>" . convertir_texto('CUÁNTO ME DEBEN') . "</td></tr>";
    echo "<tr><td colspan='6' style='background-color:#e3f2fd;text-align:center;font-style:italic;'>" . convertir_texto('Estado de Cartera de Clientes') . "</td></tr>";
    echo "<tr><td colspan='6' style='background-color:#f0f0f0;font-weight:bold;'>" . convertir_texto('Fecha de Corte: ') . $fechaFormateada . "</td></tr>";
    echo "<tr><td colspan='6' style='height:10px;'></td></tr>";
    
    // Encabezados de columnas
    echo "<tr style='background-color:#0d6efd;color:white;font-weight:bold;'>";
    echo "<td width='120'>" . convertir_texto('Identificación') . "</td>";
    echo "<td width='250'>" . convertir_texto('Nombre del Cliente') . "</td>";
    echo "<td width='120'>" . convertir_texto('Total Facturado') . "</td>";
    echo "<td width='120'>" . convertir_texto('Valor Anticipos') . "</td>";
    echo "<td width='120'>" . convertir_texto('Abonos Realizados') . "</td>";
    echo "<td width='120'>" . convertir_texto('Saldo por Cobrar') . "</td>";
    echo "</tr>";

    // Datos de clientes
    if (!empty($clientes)) {
        foreach ($clientes as $cliente) {
            echo "<tr>";
            echo "<td class='texto'>" . htmlspecialchars($cliente['identificacion']) . "</td>";
            echo "<td class='texto'>" . convertir_texto($cliente['nombre']) . "</td>";
            echo "<td class='numero' align='right'>" . number_format($cliente['totalFacturado'], 2, '.', '') . "</td>";
            echo "<td class='numero' align='right'>" . number_format($cliente['valorAnticipos'], 2, '.', '') . "</td>";
            echo "<td class='numero' align='right'>" . number_format($cliente['abonosRealizados'], 2, '.', '') . "</td>";
            echo "<td class='numero' align='right'>" . number_format($cliente['saldoCobrar'], 2, '.', '') . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6' align='center'>" . convertir_texto('No hay clientes para mostrar') . "</td></tr>";
    }

    // Totales
    echo "<tr style='background-color:#f8f9fa;font-weight:bold;'>";
    echo "<td colspan='2' align='center'>" . convertir_texto('TOTAL') . "</td>";
    echo "<td class='numero' align='right'>" . number_format($totales['totalFacturado'], 2, '.', '') . "</td>";
    echo "<td class='numero' align='right'>" . number_format($totales['totalAnticipos'], 2, '.', '') . "</td>";
    echo "<td class='numero' align='right'>" . number_format($totales['totalAbonos'], 2, '.', '') . "</td>";
    echo "<td class='numero' align='right'>" . number_format($totales['totalSaldo'], 2, '.', '') . "</td>";
    echo "</tr>";

    // Pie de página
    echo "<tr><td colspan='6' style='height:15px;'></td></tr>";
    echo "<tr><td colspan='6' style='background-color:#f8f9fa;font-size:10px;text-align:center;'>" . convertir_texto('Universidad de Santander - Ingeniería de Software') . "</td></tr>";
    echo "<tr><td colspan='6' style='background-color:#f8f9fa;font-size:10px;text-align:center;'>" . convertir_texto('Todos los derechos reservados © 2025') . "</td></tr>";
    echo "<tr><td colspan='6' style='background-color:#f8f9fa;font-size:10px;text-align:center;'>" . convertir_texto('Creado por iniciativa del programa de Contaduría Pública') . "</td></tr>";
    echo "<tr><td colspan='6' style='background-color:#f8f9fa;font-size:10px;text-align:center;'>" . convertir_texto('Documento generado el: ') . date('d/m/Y H:i:s') . "</td></tr>";
    
    echo "</table></body></html>";
    exit;
}
?>