<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Obtener parámetros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Consulta
$sql = "SELECT 
            ld.fecha,
            ld.tipo_documento,
            ld.numero_documento,
            ld.codigo_cuenta,
            ld.nombre_cuenta,
            ld.tercero_identificacion,
            ld.tercero_nombre,
            ld.concepto,
            ld.debito,
            ld.credito
        FROM libro_diario ld
        WHERE ld.fecha BETWEEN :fecha_inicio AND :fecha_fin
        ORDER BY ld.fecha ASC, ld.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);
$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Headers para descargar como Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="libro_diario_' . date('Ymd') . '.xls"');
header('Cache-Control: max-age=0');

// Calcular totales
$total_debito = 0;
$total_credito = 0;
foreach ($movimientos as $mov) {
    $total_debito += $mov['debito'];
    $total_credito += $mov['credito'];
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 5px; }
        th { background-color: #4472C4; color: white; font-weight: bold; }
        .total { background-color: #D9E1F2; font-weight: bold; }
        .numero { text-align: right; }
    </style>
</head>
<body>
    <h2>LIBRO DIARIO</h2>
    <p>Período: <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo Documento</th>
                <th>No. Documento</th>
                <th>Código Cuenta</th>
                <th>Nombre Cuenta</th>
                <th>Identificación Tercero</th>
                <th>Nombre Tercero</th>
                <th>Concepto</th>
                <th>Débito</th>
                <th>Crédito</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $mov): ?>
                <tr>
                    <td><?php echo $mov['fecha']; ?></td>
                    <td><?php echo $mov['tipo_documento']; ?></td>
                    <td><?php echo $mov['numero_documento']; ?></td>
                    <td><?php echo $mov['codigo_cuenta']; ?></td>
                    <td><?php echo $mov['nombre_cuenta']; ?></td>
                    <td><?php echo $mov['tercero_identificacion']; ?></td>
                    <td><?php echo $mov['tercero_nombre']; ?></td>
                    <td><?php echo $mov['concepto']; ?></td>
                    <td class="numero"><?php echo $mov['debito'] > 0 ? number_format($mov['debito'], 2) : ''; ?></td>
                    <td class="numero"><?php echo $mov['credito'] > 0 ? number_format($mov['credito'], 2) : ''; ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="8" style="text-align: right;">TOTALES:</td>
                <td class="numero"><?php echo number_format($total_debito, 2); ?></td>
                <td class="numero"><?php echo number_format($total_credito, 2); ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>