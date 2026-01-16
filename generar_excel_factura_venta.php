<?php
require_once 'connection.php';

$conn = new connection();
$pdo = $conn->connect();

$id = $_GET['id'] ?? 0;

// Obtener factura
$stmt = $pdo->prepare("SELECT * FROM facturav WHERE id = :id");
$stmt->execute([':id' => $id]);
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$factura) {
    die("Factura no encontrada");
}

// Obtener detalles
$stmtDetalle = $pdo->prepare("SELECT * FROM factura_detalle WHERE id_factura = :id_factura");
$stmtDetalle->execute([':id_factura' => $id]);
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
header('Content-Disposition: attachment; filename="Factura_Venta_' . ($factura['numero_factura'] ?? $factura['consecutivo']) . '_' . date('Y-m-d') . '.xls"');
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
        .invoice-title { font-size: 20px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .invoice-number { font-size: 16px; color: #2c3e50; }
        .section-title { font-weight: bold; color: #2c3e50; background-color: #f8f9fa; padding: 8px; border-bottom: 1px solid #dee2e6; margin-bottom: 10px; }
        .section-content { font-size: 11px; line-height: 1.5; }
        .due-date { color: #e74c3c; font-weight: bold; }
        .message { margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #2c3e50; font-style: italic; color: #666; }
        .amount { font-family: "Courier New", monospace; font-weight: bold; }
        .text-right { text-align: right; }
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
                    <div class="invoice-title">FACTURA DE VENTA</div>
                    <div class="invoice-number">N° <?= htmlspecialchars($factura['numero_factura'] ?? $factura['consecutivo']) ?></div>
                    <div class="company-details">
                        Fecha de emisión: <?= date('d/m/Y', strtotime($factura['fecha'])) ?><br>
                        <?php if (!empty($factura['fecha_vencimiento'])): ?>
                            Vencimiento: <span class="due-date"><?= date('d/m/Y', strtotime($factura['fecha_vencimiento'])) ?></span><br>
                        <?php endif; ?>
                        Consecutivo: <?= htmlspecialchars($factura['consecutivo']) ?>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Secciones de información -->
        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
            <tr>
                <td width="33%" style="vertical-align: top; padding-right: 10px;">
                    <div class="section-title">FACTURAR A</div>
                    <div class="section-content">
                        <strong><?= htmlspecialchars($factura['nombre']) ?></strong><br>
                        ID: <?= htmlspecialchars($factura['identificacion']) ?><br>
                        <br>
                        <em>Información de contacto completa del cliente se encuentra en nuestro sistema.</em>
                    </div>
                </td>
                
                <td width="33%" style="vertical-align: top; padding: 0 10px;">
                    <div class="section-title">DETALLES</div>
                    <div class="section-content">
                        Factura generada por SOFI - Sistema de Gestión Financiera<br>
                        Forma de Pago: <?= htmlspecialchars($factura['formaPago']) ?><br>
                        <?php if ($factura['retenciones'] > 0): ?>
                            Retención aplicada: <?= htmlspecialchars($factura['retencion_tarifa'] ?? '0') ?>%<br>
                        <?php endif; ?>
                    </div>
                </td>
                
                <td width="33%" style="vertical-align: top; padding-left: 10px;">
                    <div class="section-title">PAGO</div>
                    <div class="section-content">
                        <?php if (!empty($factura['fecha_vencimiento'])): ?>
                            <div class="due-date">
                                Vencimiento: <?= date('d/m/Y', strtotime($factura['fecha_vencimiento'])) ?>
                            </div>
                        <?php else: ?>
                            <div>Pago inmediato</div>
                        <?php endif; ?>
                        <div style="margin-top: 10px;">
                            Total a pagar:<br>
                            <span style="font-size: 18px; font-weight: bold; color: #2c3e50;">$<?= number_format($factura['valorTotal'], 2) ?></span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Tabla de artículos CORREGIDA -->
        <table width="100%" border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; margin: 20px 0;">
            <thead>
                <tr style="background-color: #2c3e50; color: white;">
                    <th width="40%" style="padding: 12px; text-align: left; font-weight: bold;">ARTÍCULOS</th>
                    <th width="10%" style="padding: 12px; text-align: right; font-weight: bold;">CANT.</th>
                    <th width="20%" style="padding: 12px; text-align: right; font-weight: bold;">PRECIO UNITARIO</th>
                    <th width="15%" style="padding: 12px; text-align: right; font-weight: bold;">IVA</th>
                    <th width="15%" style="padding: 12px; text-align: right; font-weight: bold;">MONTO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $index => $detalle): ?>
                <tr style="border-bottom: 1px solid #dee2e6; <?= ($index % 2 == 0) ? 'background-color: #f8f9fa;' : '' ?>">
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <strong><?= htmlspecialchars($detalle['nombreProducto']) ?></strong><br>
                        <span style="font-size: 10px; color: #666;">Código: <?= htmlspecialchars($detalle['codigoProducto']) ?></span>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: right;"><?= number_format($detalle['cantidad'], 0) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: right;" class="amount">$<?= number_format($detalle['precio_unitario'], 2) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: right;" class="amount">$<?= number_format($detalle['iva'], 2) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: right;" class="amount">$<?= number_format($detalle['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totales -->
        <table width="300" border="0" cellpadding="0" cellspacing="0" style="margin-left: auto; margin-top: 20px;">
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #ddd; text-align: right;">SUBTOTAL:</td>
                <td style="padding: 8px 0; border-bottom: 1px solid #ddd; text-align: right; width: 120px;"><span class="amount">$<?= number_format($factura['subtotal'], 2) ?></span></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #ddd; text-align: right;">IVA (<?= ($factura['ivaTotal'] > 0 ? '19%' : '0%') ?>):</td>
                <td style="padding: 8px 0; border-bottom: 1px solid #ddd; text-align: right;"><span class="amount">$<?= number_format($factura['ivaTotal'], 2) ?></span></td>
            </tr>
            <?php if ($factura['retenciones'] > 0): ?>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #ddd; text-align: right;">RETENCIONES (<?= htmlspecialchars($factura['retencion_tarifa'] ?? '0') ?>%):</td>
                <td style="padding: 8px 0; border-bottom: 1px solid #ddd; text-align: right;"><span class="amount">($<?= number_format($factura['retenciones'], 2) ?>)</span></td>
            </tr>
            <?php endif; ?>
            <tr style="font-weight: bold; font-size: 14px; background-color: #f8f9fa;">
                <td style="padding: 12px 0; border-bottom: 2px solid #2c3e50; text-align: right;">TOTAL A PAGAR:</td>
                <td style="padding: 12px 0; border-bottom: 2px solid #2c3e50; text-align: right;"><span class="amount">$<?= number_format($factura['valorTotal'], 2) ?></span></td>
            </tr>
        </table>
        
    </div>
</body>
</html>
<?php
exit;
?>