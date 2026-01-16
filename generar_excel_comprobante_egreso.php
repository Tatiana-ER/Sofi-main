<?php
require_once 'connection.php';

$conn = new connection();
$pdo = $conn->connect();

$id = $_GET['id'] ?? 0;

// Obtener comprobante
$stmt = $pdo->prepare("SELECT * FROM doccomprobanteegreso WHERE id = :id");
$stmt->execute([':id' => $id]);
$comprobante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comprobante) {
    die("Comprobante no encontrado");
}

// Obtener detalles de facturas aplicadas
$stmtDetalle = $pdo->prepare("SELECT * FROM detalle_comprobante_egreso WHERE idComprobante = :idComprobante");
$stmtDetalle->execute([':idComprobante' => $id]);
$detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

// Obtener información del perfil de la empresa
$stmtPerfil = $pdo->prepare("SELECT * FROM perfil ORDER BY id DESC LIMIT 1");
$stmtPerfil->execute();
$perfil = $stmtPerfil->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    $perfil = [
        'razon' => 'Nombre del Negocio',
        'nombres' => '',
        'apellidos' => '',
        'direccion' => 'Dirección no especificada',
        'ciudad' => '',
        'departamento' => '',
        'telefono' => '',
        'email' => '',
        'cedula' => ''
    ];
}

// Preparar nombre de la empresa
$nombreEmpresa = '';
if (!empty($perfil['razon'])) {
    $nombreEmpresa = $perfil['razon'];
} elseif (!empty($perfil['nombres']) || !empty($perfil['apellidos'])) {
    $nombreEmpresa = trim($perfil['nombres'] . ' ' . $perfil['apellidos']);
} else {
    $nombreEmpresa = 'Nombre del Negocio';
}

// Headers para Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Comprobante_Egreso_' . $comprobante['consecutivo'] . '_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF"; // BOM para UTF-8

?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .company-details { font-size: 11px; color: #666; line-height: 1.4; }
        .voucher-title { font-size: 20px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .voucher-number { font-size: 16px; color: #2c3e50; }
        .section-title { font-weight: bold; color: #2c3e50; background-color: #f8f9fa; padding: 8px; border-bottom: 1px solid #dee2e6; margin-bottom: 10px; }
        .section-content { font-size: 11px; line-height: 1.5; }
        .message { margin: 20px 0; padding: 15px; background-color: #f8d7da; border-left: 4px solid #2c3e50; font-style: italic; color: #721c24; }
        .amount { font-family: "Courier New", monospace; font-weight: bold; }
        .text-right { text-align: right; }
        .paid-stamp { display: inline-block; padding: 10px 20px; background-color: #2c3e50; color: white; font-weight: bold; border-radius: 5px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #2c3e50;">
            <tr>
                <td width="70%" style="vertical-align: top;">
                    <div class="company-name"><?= htmlspecialchars($nombreEmpresa) ?></div>
                    <div class="company-details">
                        <?= htmlspecialchars($perfil['direccion']) ?><br>
                        <?= htmlspecialchars($perfil['ciudad'] . ', ' . $perfil['departamento']) ?><br>
                        Tel: <?= htmlspecialchars($perfil['telefono']) ?> | Email: <?= htmlspecialchars($perfil['email']) ?><br>
                        NIT: <?= htmlspecialchars($perfil['cedula']) ?>
                    </div>
                </td>
                <td width="30%" style="vertical-align: top; text-align: right;">
                    <div class="voucher-title">COMPROBANTE DE EGRESO</div>
                    <div class="voucher-number">N° <?= htmlspecialchars($comprobante['consecutivo']) ?></div>
                    <div class="company-details">
                        Fecha: <?= date('d/m/Y', strtotime($comprobante['fecha'])) ?><br>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Secciones de información -->
        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
            <tr>
                <td width="50%" style="vertical-align: top; padding-right: 10px;">
                    <div class="section-title">PAGADO A</div>
                    <div class="section-content">
                        <strong><?= htmlspecialchars($comprobante['nombre']) ?></strong><br>
                        Identificación: <?= htmlspecialchars($comprobante['identificacion']) ?><br>
                    </div>
                </td>
                
                <td width="50%" style="vertical-align: top; padding-left: 10px;">
                    <div class="section-title">DETALLES DEL PAGO</div>
                    <div class="section-content">
                        Forma de Pago: <strong><?= htmlspecialchars($comprobante['formaPago']) ?></strong><br>
                        Total Pagado: <strong style="color: #2c3e50; font-size: 16px;">$<?= number_format($comprobante['valorTotal'], 2) ?></strong>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Tabla de facturas aplicadas -->
        <div class="section-title">FACTURAS PAGADAS</div>
        <table width="100%" border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; margin: 20px 0;">
            <thead>
                <tr style="background-color: #2c3e50; color: white;">
                    <th width="30%" style="padding: 12px; text-align: left; font-weight: bold;">NÚMERO DE FACTURA</th>
                    <th width="30%" style="padding: 12px; text-align: right; font-weight: bold;">FECHA VENCIMIENTO</th>
                    <th width="40%" style="padding: 12px; text-align: right; font-weight: bold;">VALOR APLICADO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $index => $detalle): ?>
                <tr style="border-bottom: 1px solid #dee2e6; <?= ($index % 2 == 0) ? 'background-color: #f8f9fa;' : '' ?>">
                    <td style="padding: 10px; border: 1px solid #ddd;"><strong><?= htmlspecialchars($detalle['consecutivoFactura']) ?></strong></td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">
                        <?= !empty($detalle['fechaVencimiento']) ? date('d/m/Y', strtotime($detalle['fechaVencimiento'])) : 'N/A' ?>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: right;" class="amount">
                        <span style="color: #2c3e50;">$<?= number_format($detalle['valorAplicado'], 2) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totales -->
        <table width="300" border="0" cellpadding="0" cellspacing="0" style="margin-left: auto; margin-top: 20px;">
            <tr style="font-weight: bold; font-size: 14px; background-color: #f8f9fa;">
                <td style="padding: 12px 0; border-bottom: 2px solid #2c3e50; text-align: right;">TOTAL PAGADO:</td>
                <td style="padding: 12px 0; border-bottom: 2px solid #2c3e50; text-align: right; width: 120px;">
                    <span class="amount" style="color: #2c3e50; font-size: 18px;">$<?= number_format($comprobante['valorTotal'], 2) ?></span>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
<?php
exit;
?>