<?php
require_once 'connection.php';

$conn = new connection();
$pdo = $conn->connect();

$id = $_GET['id'] ?? 0;

// Obtener comprobante
$stmt = $pdo->prepare("SELECT * FROM doccomprobantecontable WHERE id = :id");
$stmt->execute([':id' => $id]);
$comprobante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comprobante) {
    die("Comprobante contable no encontrado");
}

// Obtener detalles
$stmtDetalle = $pdo->prepare("SELECT * FROM detallecomprobantecontable WHERE comprobante_id = :comprobante_id");
$stmtDetalle->execute([':comprobante_id' => $id]);
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

// Calcular totales
$sumaDebito = 0;
$sumaCredito = 0;
foreach ($detalles as $detalle) {
    $sumaDebito += floatval($detalle['valorDebito']);
    $sumaCredito += floatval($detalle['valorCredito']);
}
$diferencia = $sumaDebito - $sumaCredito;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante Contable #<?= $comprobante['consecutivo'] ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 10px; }
            .container { border: none; box-shadow: none; }
        }
        
        @page {
            margin: 20px;
        }
        
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background-color: white;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2c3e50;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.4;
        }
        
        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .document-number {
            font-size: 16px;
            color: #2c3e50;
        }
        
        .section-title {
            font-weight: bold;
            color: #2c3e50;
            background-color: #f8f9fa;
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 10px;
        }
        
        .section-content {
            font-size: 11px;
            line-height: 1.5;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th {
            background-color: #2c3e50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .items-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .totals {
            width: 300px;
            margin-left: auto;
            margin-top: 20px;
        }
        
        .total-row {
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .total-row:last-child {
            border-bottom: 2px solid #2c3e50;
        }
        
        .total-row.final {
            font-weight: bold;
            font-size: 14px;
            background-color: #f8f9fa;
            padding: 12px 0;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .amount {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        
        .balance-ok {
            color: #27ae60;
        }
        
        .balance-error {
            color: #e74c3c;
        }
        
        .print-controls {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .print-btn {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 0 5px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .print-btn:hover {
            background-color: #34495e;
        }
        
        .btn-pdf {
            background-color: #e74c3c;
        }
        
        .btn-pdf:hover {
            background-color: #c0392b;
        }
        
        .btn-excel {
            background-color: #27ae60;
        }
        
        .btn-excel:hover {
            background-color: #219653;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button onclick="window.print()" class="print-btn">
            <span>🖨️</span> Imprimir Comprobante
        </button>
        <a href="generar_pdf_comprobante_contable.php?id=<?= $id ?>" class="print-btn btn-pdf" target="_blank">
            <span>📄</span> Descargar PDF
        </a>
        <a href="generar_excel_comprobante_contable.php?id=<?= $id ?>" class="print-btn btn-excel" target="_blank">
            <span>📊</span> Descargar Excel
        </a>
    </div>
    
    <div class="container">
        <!-- Encabezado -->
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="header">
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
                    <div class="document-title">COMPROBANTE CONTABLE</div>
                    <div class="document-number">Consecutivo: <?= htmlspecialchars($comprobante['consecutivo']) ?></div>
                    <div class="company-details">
                        Fecha de emisión: <?= date('d/m/Y', strtotime($comprobante['fecha'])) ?><br>
                        Generado por SOFI - Sistema de Gestión Financiera
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Información del comprobante -->
        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
            <tr>
                <td width="50%" style="vertical-align: top;">
                    <div class="section-title">INFORMACIÓN GENERAL</div>
                    <div class="section-content">
                        <strong>Consecutivo:</strong> <?= htmlspecialchars($comprobante['consecutivo']) ?><br>
                        <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($comprobante['fecha'])) ?><br>
                        <strong>Observaciones:</strong> <?= htmlspecialchars($comprobante['observaciones']) ?>
                    </div>
                </td>
                <td width="50%" style="vertical-align: top; text-align: right;">
                    <div class="section-title">RESUMEN DE MOVIMIENTOS</div>
                    <div class="section-content">
                        <strong>Total Débito:</strong> $<?= number_format($sumaDebito, 2) ?><br>
                        <strong>Total Crédito:</strong> $<?= number_format($sumaCredito, 2) ?><br>
                        <strong>Diferencia:</strong> 
                        <span class="amount <?= ($diferencia == 0) ? 'balance-ok' : 'balance-error' ?>">
                            $<?= number_format($diferencia, 2) ?>
                        </span>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Tabla de detalles -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="15%">Cuenta Contable</th>
                    <th width="25%">Descripción</th>
                    <th width="25%">Tercero</th>
                    <th width="20%">Detalle</th>
                    <th width="10%" class="text-right">Débito</th>
                    <th width="10%" class="text-right">Crédito</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): 
                    // Separar tercero en cédula y nombre si está en formato "cedula - nombre"
                    $terceroMostrar = $detalle['tercero'];
                    if (strpos($detalle['tercero'], '-') !== false) {
                        $partes = explode('-', $detalle['tercero'], 2);
                        $terceroMostrar = trim($partes[0]) . '<br><small>' . trim($partes[1] ?? '') . '</small>';
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($detalle['cuentaContable']) ?></td>
                    <td><?= htmlspecialchars($detalle['descripcionCuenta']) ?></td>
                    <td><?= $terceroMostrar ?></td>
                    <td><?= htmlspecialchars($detalle['detalle']) ?></td>
                    <td class="text-right amount">$<?= number_format($detalle['valorDebito'], 2) ?></td>
                    <td class="text-right amount">$<?= number_format($detalle['valorCredito'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totales -->
        <div class="totals">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="total-row" style="text-align: right;">SUMA DÉBITO:</td>
                    <td class="total-row" style="text-align: right; width: 120px;">
                        <span class="amount">$<?= number_format($sumaDebito, 2) ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="total-row" style="text-align: right;">SUMA CRÉDITO:</td>
                    <td class="total-row" style="text-align: right;">
                        <span class="amount">$<?= number_format($sumaCredito, 2) ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="total-row final" style="text-align: right;">DIFERENCIA:</td>
                    <td class="total-row final" style="text-align: right;">
                        <span class="amount <?= ($diferencia == 0) ? 'balance-ok' : 'balance-error' ?>">
                            $<?= number_format($diferencia, 2) ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Validación contable -->
        <div style="margin-top: 20px; padding: 15px; background-color: <?= ($diferencia == 0) ? '#d4edda' : '#f8d7da' ?>; border: 1px solid <?= ($diferencia == 0) ? '#c3e6cb' : '#f5c6cb' ?>; border-radius: 4px;">
            <strong style="color: <?= ($diferencia == 0) ? '#155724' : '#721c24' ?>;">
                <?php if ($diferencia == 0): ?>
                    ✓ COMPROBANTE EQUILIBRADO - Débito igual a Crédito
                <?php else: ?>
                    ⚠ COMPROBANTE DESEQUILIBRADO - Débito ($<?= number_format($sumaDebito, 2) ?>) diferente de Crédito ($<?= number_format($sumaCredito, 2) ?>)
                <?php endif; ?>
            </strong>
        </div>
        
        <!-- Pie de página -->
        <div class="footer">
            <p><strong><?= htmlspecialchars($nombreEmpresa) ?></strong></p>
            <p><?= htmlspecialchars($perfil['direccion']) ?> | <?= htmlspecialchars($perfil['ciudad'] . ', ' . $perfil['departamento']) ?></p>
            <p>Tel: <?= htmlspecialchars($perfil['telefono']) ?> | Email: <?= htmlspecialchars($perfil['email']) ?> | NIT: <?= htmlspecialchars($perfil['cedula']) ?></p>
            <p>Documento generado electrónicamente por SOFI - <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
    
    <script>
        // Asegurar que los enlaces de descarga funcionen
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href*="generar_"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.getAttribute('target') === '_blank') {
                        e.preventDefault();
                        window.open(this.href, '_blank');
                    }
                });
            });
        });
    </script>
</body>
</html>