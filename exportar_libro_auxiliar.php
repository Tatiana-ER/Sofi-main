<?php
// ================== CONEXIÓN ==================
include("connection.php");
$conn = new connection();
$pdo = $conn->connect();

// ================== FILTROS ==================
$fecha_desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-01-01');
$fecha_hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-12-31');
$cuenta_codigo = isset($_GET['cuenta']) ? $_GET['cuenta'] : '';
$tercero = isset($_GET['tercero']) ? $_GET['tercero'] : '';

// ================== OBTENER CUENTAS ==================
if ($cuenta_codigo != '') {
    $sql_cuentas = "SELECT codigo_cuenta, MIN(nombre_cuenta) as nombre_cuenta
                    FROM libro_diario
                    WHERE codigo_cuenta = :cuenta
                    GROUP BY codigo_cuenta
                    ORDER BY codigo_cuenta";
    $stmt_cuentas = $pdo->prepare($sql_cuentas);
    $stmt_cuentas->execute([':cuenta' => $cuenta_codigo]);
} else {
    $sql_cuentas = "SELECT codigo_cuenta, MIN(nombre_cuenta) as nombre_cuenta
                    FROM libro_diario
                    WHERE fecha BETWEEN :desde AND :hasta
                    GROUP BY codigo_cuenta
                    ORDER BY codigo_cuenta";
    $stmt_cuentas = $pdo->prepare($sql_cuentas);
    $stmt_cuentas->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
}
$cuentas = $stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);

// ================== FUNCIÓN PARA OBTENER MOVIMIENTOS ==================
function obtenerMovimientosCuenta($pdo, $codigo_cuenta, $fecha_desde, $fecha_hasta, $tercero = '') {
    // Naturaleza por primer dígito
    $naturaleza = substr($codigo_cuenta, 0, 1);

    // Saldo acumulado anterior al periodo
    $sql_saldo = "SELECT 
                    COALESCE(SUM(debito),0) as suma_debito_prev,
                    COALESCE(SUM(credito),0) as suma_credito_prev
                  FROM libro_diario
                  WHERE codigo_cuenta = :cuenta AND fecha < :desde";
    $stmt_saldo = $pdo->prepare($sql_saldo);
    $stmt_saldo->execute([':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde]);
    $row = $stmt_saldo->fetch(PDO::FETCH_ASSOC);

    $deb_prev = floatval($row['suma_debito_prev']);
    $cred_prev = floatval($row['suma_credito_prev']);

    // Saldo inicial según naturaleza
    if (in_array($naturaleza, ['1','5','6','7'])) {
        // Activo / Costos / Gastos --> debito - credito
        $saldo_inicial = $deb_prev - $cred_prev;
    } else {
        // Pasivo / Patrimonio / Ingresos --> credito - debito
        $saldo_inicial = $cred_prev - $deb_prev;
    }

    // El saldo inicial no puede ser negativo salvo cuentas IVA (contienen 2408)
    if ($saldo_inicial < 0 && strpos($codigo_cuenta, '2408') === false) {
        $saldo_inicial = 0;
    }

    // Obtener movimientos del período
    $sql_mov = "SELECT * FROM libro_diario
                WHERE codigo_cuenta = :cuenta
                  AND fecha BETWEEN :desde AND :hasta";
    $params = [':cuenta' => $codigo_cuenta, ':desde' => $fecha_desde, ':hasta' => $fecha_hasta];
    
    if ($tercero != '') {
        $sql_mov .= " AND tercero_identificacion = :tercero";
        $params[':tercero'] = $tercero;
    }
    
    $sql_mov .= " ORDER BY fecha ASC, id ASC";
    $stmt_mov = $pdo->prepare($sql_mov);
    $stmt_mov->execute($params);
    $movimientos = $stmt_mov->fetchAll(PDO::FETCH_ASSOC);

    // Calcular saldos acumulados fila por fila
    $saldo = $saldo_inicial;
    foreach ($movimientos as $k => $m) {
        $debito = floatval($m['debito']);
        $credito = floatval($m['credito']);

        // Guardamos el saldo ANTES del movimiento (saldo inicial de esta fila)
        $movimientos[$k]['saldo_inicial_fila'] = $saldo;

        // Aplicamos el movimiento
        if (in_array($naturaleza, ['1','5','6','7'])) {
            // Activo / Costos / Gastos
            $saldo += ($debito - $credito);
        } else {
            // Pasivo / Patrimonio / Ingresos
            $saldo += ($credito - $debito);
        }

        // El saldo no puede mostrarse negativo a menos que sea IVA (2408)
        if ($saldo < 0 && strpos($codigo_cuenta, '2408') === false) {
            $saldo = 0;
        }

        $movimientos[$k]['saldo_final_fila'] = $saldo;
    }

    return [
        'saldo_inicial' => $saldo_inicial,
        'movimientos' => $movimientos
    ];
}

// ================== CONFIGURAR HEADERS PARA EXCEL ==================
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="libro_auxiliar_' . date('Ymd_His') . '.xls"');
header('Cache-Control: max-age=0');

// ================== GENERAR HTML PARA EXCEL ==================
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
        th, td { border: 1px solid #000; padding: 8px; font-size: 11px; }
        th { background-color: #0d6efd; color: white; font-weight: bold; text-align: center; }
        .numero { text-align: right; }
        .header-info { margin-bottom: 20px; font-family: Arial, sans-serif; }
        .header-info h2 { color: #0d6efd; margin-bottom: 10px; }
        .header-info p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="header-info">
        <h2>LIBRO AUXILIAR</h2>
        <p><strong>Período:</strong> <?= date('d/m/Y', strtotime($fecha_desde)) ?> al <?= date('d/m/Y', strtotime($fecha_hasta)) ?></p>
        <?php if ($cuenta_codigo != ''): ?>
            <p><strong>Cuenta:</strong> <?= htmlspecialchars($cuenta_codigo) ?></p>
        <?php endif; ?>
        <?php if ($tercero != ''): ?>
            <p><strong>Tercero:</strong> <?= htmlspecialchars($tercero) ?></p>
        <?php endif; ?>
        <p><strong>Fecha de generación:</strong> <?= date('d/m/Y H:i:s') ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>CÓDIGO CONTABLE</th>
                <th>NOMBRE DE LA CUENTA</th>
                <th>IDENTIFICACIÓN TERCERO</th>
                <th>NOMBRE TERCERO</th>
                <th>FECHA</th>
                <th>COMPROBANTE</th>
                <th>CONCEPTO</th>
                <th>SALDO INICIAL</th>
                <th>DÉBITO</th>
                <th>CRÉDITO</th>
                <th>SALDO FINAL</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (count($cuentas) == 0) {
                echo "<tr><td colspan='11' style='text-align:center; color:#6c757d; font-style:italic;'>No se encontraron movimientos en el período seleccionado.</td></tr>";
            } else {
                $total_debito = 0;
                $total_credito = 0;
                
                foreach ($cuentas as $cuenta) {
                    $datos = obtenerMovimientosCuenta($pdo, $cuenta['codigo_cuenta'], $fecha_desde, $fecha_hasta, $tercero);

                    if (count($datos['movimientos']) > 0) {
                        foreach ($datos['movimientos'] as $mov) {
                            // Acumular totales
                            $total_debito += floatval($mov['debito']);
                            $total_credito += floatval($mov['credito']);
                            
                            // Separar identificación y nombre si están concatenados
                            $tercero_id = $mov['tercero_identificacion'] ?? '';
                            $tercero_nombre = $mov['tercero_nombre'] ?? '';
                            
                            if (empty($tercero_nombre) && strpos($tercero_id, ' - ') !== false) {
                                $partes = explode(' - ', $tercero_id, 2);
                                $tercero_id = trim($partes[0]);
                                $tercero_nombre = trim($partes[1]);
                            } elseif (empty($tercero_nombre) && strpos($tercero_id, '-') !== false) {
                                $partes = explode('-', $tercero_id, 2);
                                $tercero_id = trim($partes[0]);
                                $tercero_nombre = trim($partes[1]);
                            }
                            
                            // Formato del comprobante
                            $tipo_comp = '';
                            switch ($mov['tipo_documento']) {
                                case 'factura_venta': $tipo_comp = 'FAC.VTA.No.'; break;
                                case 'factura_compra': $tipo_comp = 'FRA.COMPRA No.'; break;
                                case 'recibo_caja': $tipo_comp = 'REC.CAJA No.'; break;
                                case 'comprobante_egreso': $tipo_comp = 'COMP.EGRES.No.'; break;
                                case 'comprobante_contable': $tipo_comp = 'COMP.CONTAB.No.'; break;
                                default: $tipo_comp = strtoupper($mov['tipo_documento']);
                            }
                            $comprobante = $tipo_comp . ' ' . $mov['numero_documento'];

                            echo "<tr>
                                    <td>" . htmlspecialchars($cuenta['codigo_cuenta']) . "</td>
                                    <td>" . htmlspecialchars($cuenta['nombre_cuenta']) . "</td>
                                    <td>" . htmlspecialchars($tercero_id) . "</td>
                                    <td>" . htmlspecialchars($tercero_nombre) . "</td>
                                    <td>" . date('d/m/Y', strtotime($mov['fecha'])) . "</td>
                                    <td>" . htmlspecialchars($comprobante) . "</td>
                                    <td>" . htmlspecialchars($mov['concepto']) . "</td>
                                    <td class='numero'>" . number_format($mov['saldo_inicial_fila'], 2, '.', ',') . "</td>
                                    <td class='numero'>" . ($mov['debito'] > 0 ? number_format($mov['debito'], 2, '.', ',') : '') . "</td>
                                    <td class='numero'>" . ($mov['credito'] > 0 ? number_format($mov['credito'], 2, '.', ',') : '') . "</td>
                                    <td class='numero'>" . number_format($mov['saldo_final_fila'], 2, '.', ',') . "</td>
                                  </tr>";
                        }
                    }
                }
                
                // Fila de totales
                echo "<tr style='background-color: #D9E1F2; font-weight: bold;'>
                        <td colspan='7' style='text-align: right;'>TOTALES:</td>
                        <td class='numero'></td>
                        <td class='numero'>" . number_format($total_debito, 2, '.', ',') . "</td>
                        <td class='numero'>" . number_format($total_credito, 2, '.', ',') . "</td>
                        <td class='numero'></td>
                      </tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>