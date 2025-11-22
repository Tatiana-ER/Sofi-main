<?php
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['datos'])) {
    // Decodificar los datos JSON
    $datos = json_decode($_POST['datos'], true);
    
    // Extraer los datos
    $productos = $datos['productos'] ?? [];
    $total = $datos['total'] ?? 0;
    $fechaDesde = $datos['fechaDesde'] ?? '';
    $fechaHasta = $datos['fechaHasta'] ?? '';
    $fechaGeneracion = $datos['fechaGeneracion'] ?? date('d/m/Y');
    $titulo = $datos['titulo'] ?? 'Informe de Existencias';

    // Crear las filas dinámicamente
    $filas = '';
    if (!empty($productos)) {
        foreach ($productos as $producto) {
            $codigo = htmlspecialchars($producto['codigo']);
            $nombre = htmlspecialchars($producto['nombre']);
            $saldo = htmlspecialchars($producto['cantidad']);

            $filas .= "
                <tr>
                    <td style='text-align:center;'>$codigo</td>
                    <td>$nombre</td>
                    <td style='text-align:right;'>" . number_format($saldo, 0) . "</td>
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

    // Formatear las fechas para mostrar
    $rangoFechas = '';
    if (!empty($fechaDesde) && !empty($fechaHasta)) {
        $rangoFechas = "<p><strong>Período:</strong> Desde $fechaDesde hasta $fechaHasta</p>";
    } elseif (!empty($fechaDesde)) {
        $rangoFechas = "<p><strong>Fecha desde:</strong> $fechaDesde</p>";
    } elseif (!empty($fechaHasta)) {
        $rangoFechas = "<p><strong>Fecha hasta:</strong> $fechaHasta</p>";
    }

    // Crear el contenido HTML para el PDF
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                font-size: 12px; 
                margin: 20px;
            }
            .header { 
                text-align: center; 
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .title { 
                font-size: 18px; 
                font-weight: bold; 
                margin-bottom: 5px;
            }
            .subtitle {
                font-size: 14px;
                color: #666;
            }
            table { 
                border-collapse: collapse; 
                width: 100%; 
                margin-top: 15px;
            }
            th, td { 
                border: 1px solid #000; 
                padding: 8px; 
            }
            th { 
                background-color: #0d6efd; 
                color: white; 
                text-align: center; 
                font-weight: bold;
            }
            tfoot th { 
                background-color: #e0e0e0; 
                font-weight: bold;
            }
            .info-section {
                margin-bottom: 15px;
            }
            .total-row {
                font-weight: bold;
                background-color: #f8f9fa;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='title'>$titulo</div>
            <div class='subtitle'>Sistema de Gestión SOFI</div>
        </div>

        <div class='info-section'>
            <p><strong>Fecha de generación:</strong> $fechaGeneracion</p>
            $rangoFechas
        </div>

        <table>
            <thead>
                <tr>
                    <th width='20%'>Código de Producto</th>
                    <th width='60%'>Nombre del Producto</th>
                    <th width='20%'>Saldo Cantidades</th>
                </tr>
            </thead>
            <tbody>
                $filas
            </tbody>
            <tfoot>
                <tr class='total-row'>
                    <th colspan='2' style='text-align:right;'>TOTAL DE CANTIDADES</th>
                    <th style='text-align:right;'>" . number_format($total, 0) . "</th>
                </tr>
            </tfoot>
        </table>

        <div style='margin-top: 20px; font-size: 10px; text-align: center; color: #666;'>
            Generado automáticamente por SOFI - Universidad de Santander
        </div>
    </body>
    </html>
    ";

    try {
        // Instanciar Dompdf y generar el PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        
        // Renderizar el PDF
        $dompdf->render();

        // Configurar y enviar el PDF
        $dompdf->stream("informe_existencias_" . date('Y-m-d') . ".pdf", [
            "Attachment" => true
        ]);
        
    } catch (Exception $e) {
        // En caso de error, mostrar mensaje
        echo "Error al generar el PDF: " . $e->getMessage();
    }
    
    exit;
} else {
    echo "No se recibieron datos para generar el PDF.";
}
?>