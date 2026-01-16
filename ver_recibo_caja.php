<?php
require_once 'connection.php';

$conn = new connection();
$pdo = $conn->connect();

$id = $_GET['id'] ?? 0;

// Obtener recibo
$stmt = $pdo->prepare("SELECT * FROM docrecibodecaja WHERE id = :id");
$stmt->execute([':id' => $id]);
$recibo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recibo) {
    die("Recibo no encontrado");
}

// Obtener detalles de facturas aplicadas
$stmtDetalle = $pdo->prepare("SELECT * FROM detalle_recibo_caja WHERE idRecibo = :idRecibo");
$stmtDetalle->execute([':idRecibo' => $id]);
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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo de Caja #<?= $recibo['consecutivo'] ?></title>
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
        
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .receipt-number {
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
        
        .amount {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        
        .message {
            margin: 20px 0;
            padding: 15px;
            background-color: #d1f2eb;
            border-left: 4px solid #2c3e50;
            font-style: italic;
            color: #0a3622;
        }
        
        .sections-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .sections-table td {
            vertical-align: top;
            padding: 0 10px;
        }
        
        .sections-table td:first-child {
            padding-left: 0;
        }
        
        .sections-table td:last-child {
            padding-right: 0;
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
            background-color: #3c4b5a;
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
        
        .paid-stamp {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button onclick="window.print()" class="print-btn">
            <span>🖨️</span> Imprimir Recibo
        </button>
        <a href="generar_pdf_recibo_caja.php?id=<?= $id ?>" class="print-btn btn-pdf" target="_blank">
            <span>📄</span> Descargar PDF
        </a>
        <a href="generar_excel_recibo_caja.php?id=<?= $id ?>" class="print-btn btn-excel" target="_blank">
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
                    <div class="receipt-title">RECIBO DE CAJA</div>
                    <div class="receipt-number">N° <?= htmlspecialchars($recibo['consecutivo']) ?></div>
                    <div class="company-details">
                        Fecha: <?= date('d/m/Y', strtotime($recibo['fecha'])) ?><br>
                    </div>
                </td>
            </tr>
        </table>
    
        <!-- Secciones de información -->
        <table class="sections-table">
            <tr>
                <td width="50%">
                    <div class="section-title">RECIBIDO DE</div>
                    <div class="section-content">
                        <strong><?= htmlspecialchars($recibo['nombre']) ?></strong><br>
                        Identificación: <?= htmlspecialchars($recibo['identificacion']) ?><br>
                    </div>
                </td>
                
                <td width="50%">
                    <div class="section-title">DETALLES DEL PAGO</div>
                    <div class="section-content">
                        Forma de Pago: <strong><?= htmlspecialchars($recibo['formaPago']) ?></strong><br>
                        Total Recibido: <strong style="color: #2c3e50; font-size: 16px;">$<?= number_format($recibo['valorTotal'], 2) ?></strong>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Tabla de facturas aplicadas -->
        <div class="section-title">FACTURAS APLICADAS</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="30%">NÚMERO DE FACTURA</th>
                    <th width="30%" class="text-right">FECHA VENCIMIENTO</th>
                    <th width="40%" class="text-right">VALOR APLICADO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($detalle['consecutivoFactura']) ?></strong></td>
                    <td class="text-right">
                        <?= !empty($detalle['fechaVencimiento']) ? date('d/m/Y', strtotime($detalle['fechaVencimiento'])) : 'N/A' ?>
                    </td>
                    <td class="text-right amount" style="color: #2c3e50;">$<?= number_format($detalle['valorAplicado'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totales -->
        <div class="totals">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="total-row final" style="text-align: right;">TOTAL RECIBIDO:</td>
                    <td class="total-row final" style="text-align: right; width: 120px;">
                        <span class="amount" style="color: #2c3e50; font-size: 18px;">$<?= number_format($recibo['valorTotal'], 2) ?></span>
                    </td>
                </tr>
            </table>
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