<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Obtener parámetros de filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy
$tipo_documento = $_GET['tipo_documento'] ?? '';
$codigo_cuenta = $_GET['codigo_cuenta'] ?? '';

// Construir consulta con filtros
$sql = "SELECT 
            ld.id,
            ld.fecha,
            ld.tipo_documento,
            ld.numero_documento,
            ld.codigo_cuenta,
            ld.nombre_cuenta,
            ld.tercero_identificacion,
            ld.tercero_nombre,
            ld.concepto,
            FORMAT(ld.debito, 2) as debito,
            FORMAT(ld.credito, 2) as credito
        FROM libro_diario ld
        WHERE ld.fecha BETWEEN :fecha_inicio AND :fecha_fin";

if (!empty($tipo_documento)) {
    $sql .= " AND ld.tipo_documento = :tipo_documento";
}

if (!empty($codigo_cuenta)) {
    $sql .= " AND ld.codigo_cuenta LIKE :codigo_cuenta";
}

$sql .= " ORDER BY ld.fecha ASC, ld.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);

if (!empty($tipo_documento)) {
    $stmt->bindParam(':tipo_documento', $tipo_documento);
}

if (!empty($codigo_cuenta)) {
    $codigo_busqueda = $codigo_cuenta . '%';
    $stmt->bindParam(':codigo_cuenta', $codigo_busqueda);
}

$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_debito = 0;
$total_credito = 0;
foreach ($movimientos as $mov) {
    $total_debito += floatval(str_replace(',', '', $mov['debito']));
    $total_credito += floatval(str_replace(',', '', $mov['credito']));
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libro Diario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-libro-diario {
            font-size: 0.85rem;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .debito {
            text-align: right;
            color: #0066cc;
        }
        .credito {
            text-align: right;
            color: #cc0000;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2>📚 Libro Diario</h2>
                <hr>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-12">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha Inicio:</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin:</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo Documento:</label>
                        <select name="tipo_documento" class="form-select">
                            <option value="">Todos</option>
                            <option value="factura_venta" <?php echo $tipo_documento == 'factura_venta' ? 'selected' : ''; ?>>Factura Venta</option>
                            <option value="factura_compra" <?php echo $tipo_documento == 'factura_compra' ? 'selected' : ''; ?>>Factura Compra</option>
                            <option value="recibo_caja" <?php echo $tipo_documento == 'recibo_caja' ? 'selected' : ''; ?>>Recibo de Caja</option>
                            <option value="comprobante_egreso" <?php echo $tipo_documento == 'comprobante_egreso' ? 'selected' : ''; ?>>Comprobante Egreso</option>
                            <option value="comprobante_contable" <?php echo $tipo_documento == 'comprobante_contable' ? 'selected' : ''; ?>>Comprobante Contable</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cuenta Contable:</label>
                        <input type="text" name="codigo_cuenta" class="form-control" value="<?php echo $codigo_cuenta; ?>" placeholder="Ej: 1105">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover table-libro-diario">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 80px;">Fecha</th>
                                        <th style="width: 100px;">Tipo Doc.</th>
                                        <th style="width: 80px;">No. Doc.</th>
                                        <th style="width: 100px;">Código Cuenta</th>
                                        <th style="width: 200px;">Nombre Cuenta</th>
                                        <th style="width: 100px;">Tercero</th>
                                        <th>Concepto</th>
                                        <th style="width: 120px;" class="text-end">Débito</th>
                                        <th style="width: 120px;" class="text-end">Crédito</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($movimientos) > 0): ?>
                                        <?php foreach ($movimientos as $mov): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($mov['fecha'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $tipo_etiquetas = [
                                                        'factura_venta' => '<span class="badge bg-success">F. Venta</span>',
                                                        'factura_compra' => '<span class="badge bg-info">F. Compra</span>',
                                                        'recibo_caja' => '<span class="badge bg-primary">R. Caja</span>',
                                                        'comprobante_egreso' => '<span class="badge bg-warning">C. Egreso</span>',
                                                        'comprobante_contable' => '<span class="badge bg-secondary">C. Contable</span>'
                                                    ];
                                                    echo $tipo_etiquetas[$mov['tipo_documento']] ?? $mov['tipo_documento'];
                                                    ?>
                                                </td>
                                                <td class="text-center"><?php echo $mov['numero_documento']; ?></td>
                                                <td><?php echo $mov['codigo_cuenta']; ?></td>
                                                <td><?php echo $mov['nombre_cuenta']; ?></td>
                                                <td>
                                                    <?php 
                                                    if ($mov['tercero_identificacion']) {
                                                        echo $mov['tercero_identificacion'];
                                                        if ($mov['tercero_nombre']) {
                                                            echo '<br><small>' . $mov['tercero_nombre'] . '</small>';
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $mov['concepto']; ?></td>
                                                <td class="debito">
                                                    <?php 
                                                    $debito = floatval(str_replace(',', '', $mov['debito']));
                                                    echo $debito > 0 ? '$' . number_format($debito, 2) : '';
                                                    ?>
                                                </td>
                                                <td class="credito">
                                                    <?php 
                                                    $credito = floatval(str_replace(',', '', $mov['credito']));
                                                    echo $credito > 0 ? '$' . number_format($credito, 2) : '';
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="total-row">
                                            <td colspan="7" class="text-end">TOTALES:</td>
                                            <td class="text-end">$<?php echo number_format($total_debito, 2); ?></td>
                                            <td class="text-end">$<?php echo number_format($total_credito, 2); ?></td>
                                        </tr>
                                        <tr class="<?php echo abs($total_debito - $total_credito) > 0.01 ? 'table-danger' : 'table-success'; ?>">
                                            <td colspan="7" class="text-end">DIFERENCIA:</td>
                                            <td colspan="2" class="text-center">
                                                $<?php echo number_format(abs($total_debito - $total_credito), 2); ?>
                                                <?php if (abs($total_debito - $total_credito) < 0.01): ?>
                                                    ✅ Cuadrado
                                                <?php else: ?>
                                                    ❌ Descuadrado
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No hay movimientos registrados en el período seleccionado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de exportación -->
        <div class="row mt-3 mb-5">
            <div class="col-12">
                <button onclick="window.print()" class="btn btn-secondary">
                    🖨️ Imprimir
                </button>
                <a href="exportar_excel_libro_diario.php?fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>" class="btn btn-success">
                    📊 Exportar a Excel
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>