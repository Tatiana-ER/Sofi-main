<?php
class LibroDiario {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Registra un movimiento contable en el libro diario
     */
    public function registrarMovimiento($params) {
        $sql = "INSERT INTO libro_diario 
                (fecha, tipo_documento, numero_documento, id_documento, 
                 codigo_cuenta, nombre_cuenta, tercero_identificacion, 
                 tercero_nombre, concepto, debito, credito)
                VALUES 
                (:fecha, :tipo_documento, :numero_documento, :id_documento,
                 :codigo_cuenta, :nombre_cuenta, :tercero_identificacion,
                 :tercero_nombre, :concepto, :debito, :credito)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':fecha' => $params['fecha'],
            ':tipo_documento' => $params['tipo_documento'],
            ':numero_documento' => $params['numero_documento'],
            ':id_documento' => $params['id_documento'],
            ':codigo_cuenta' => $params['codigo_cuenta'],
            ':nombre_cuenta' => $params['nombre_cuenta'],
            ':tercero_identificacion' => $params['tercero_identificacion'] ?? null,
            ':tercero_nombre' => $params['tercero_nombre'] ?? null,
            ':concepto' => $params['concepto'],
            ':debito' => $params['debito'] ?? 0,
            ':credito' => $params['credito'] ?? 0
        ]);
    }
    
    /**
     * Elimina todos los movimientos de un documento específico
     */
    public function eliminarMovimientos($tipo_documento, $id_documento) {
        $sql = "DELETE FROM libro_diario 
                WHERE tipo_documento = :tipo_documento 
                AND id_documento = :id_documento";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':tipo_documento' => $tipo_documento,
            ':id_documento' => $id_documento
        ]);
    }
    
    /**
     * Obtiene la cuenta contable según el medio de pago
     */
    public function obtenerCuentaMedioPago($formaPago) {
        // El formaPago viene como: "Tipo - Código-Nombre"
        // Ejemplo: "Pago Electronico - 11100501-Bancolombia"
        
        // Buscar el patrón: número seguido de guión y texto
        if (preg_match('/(\d+)-([^,]+)/', $formaPago, $matches)) {
            $codigoCuenta = trim($matches[1]);
            $nombreCuenta = trim($matches[2]);
            
            return [
                'codigo' => $codigoCuenta,
                'nombre' => $nombreCuenta
            ];
        }
        
        // Patrón alternativo para formatos como "Credito - 130505-Nacionales"
        if (preg_match('/-\s*(\d+)-(.+)$/', $formaPago, $matches)) {
            $codigoCuenta = trim($matches[1]);
            $nombreCuenta = trim($matches[2]);
            
            return [
                'codigo' => $codigoCuenta,
                'nombre' => $nombreCuenta
            ];
        }
        
        // Por defecto, caja general
        return [
            'codigo' => '110505', 
            'nombre' => 'Caja General'
        ];
    }
    
    /**
     * Obtiene las cuentas de una categoría de inventario
     */
    public function obtenerCuentasCategoria($categoriaId) {
        $sql = "SELECT 
                    codigoCuentaVentas, cuentaVentas,
                    codigoCuentaInventarios, cuentaInventarios,
                    codigoCuentaCostos, cuentaCostos
                FROM categoriainventarios 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $categoriaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Ejecuta el cierre contable de un año fiscal completo.
     * - Calcula la utilidad/pérdida del ejercicio (ingresos - costos - gastos)
     * - Genera el asiento de cierre que deja en cero las cuentas 4, 5, 6 y 7
     * - Traslada el resultado a la cuenta de patrimonio 360501 (Utilidad del ejercicio)
     */
    public function ejecutarCierreContable($anoFiscal) {
        // 1. Verificar que no exista ya un cierre ACTIVO para ese año
        $stmtCheck = $this->pdo->prepare(
            "SELECT id FROM cierres_contables WHERE ano_fiscal = :ano AND estado = 'activo'"
        );
        $stmtCheck->execute([':ano' => $anoFiscal]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Ya existe un cierre contable activo para el año $anoFiscal. Revierte el cierre existente antes de volver a cerrarlo.");
        }

        $fechaInicio = $anoFiscal . '-01-01';
        $fechaCierre = $anoFiscal . '-12-31';

        $this->pdo->beginTransaction();

        try {
            // 2. Obtener el saldo del año de cada cuenta de ingresos, costos y gastos
            $sql = "SELECT 
                        codigo_cuenta, 
                        nombre_cuenta,
                        SUBSTRING(codigo_cuenta, 1, 1) as clase,
                        COALESCE(SUM(debito), 0) as total_debito,
                        COALESCE(SUM(credito), 0) as total_credito
                    FROM libro_diario
                    WHERE fecha BETWEEN :inicio AND :fin
                    AND SUBSTRING(codigo_cuenta, 1, 1) IN ('4', '5', '6', '7')
                    GROUP BY codigo_cuenta, nombre_cuenta";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaCierre]);
            $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Crear primero el registro del cierre para tener su ID
            //    (la utilidad se actualiza al final, cuando ya la calculamos)
            $stmtInsertCierre = $this->pdo->prepare(
                "INSERT INTO cierres_contables (ano_fiscal, fecha_cierre, utilidad_ejercicio) 
                VALUES (:ano, :fecha, 0)"
            );
            $stmtInsertCierre->execute([':ano' => $anoFiscal, ':fecha' => $fechaCierre]);
            $idCierre = $this->pdo->lastInsertId();

            $totalIngresos = 0;
            $totalCostosGastos = 0;

            // 4. Generar una línea de cierre por cada cuenta con saldo distinto de cero
            foreach ($cuentas as $cuenta) {
                $debito = floatval($cuenta['total_debito']);
                $credito = floatval($cuenta['total_credito']);
                $clase = $cuenta['clase'];

                if ($clase == '4') {
                    // Ingresos: naturaleza crédito. Saldo = credito - debito
                    $saldo = $credito - $debito;
                    $totalIngresos += $saldo;

                    if ($saldo == 0) continue;

                    // Para cerrarla (dejarla en cero) se hace lo contrario a su naturaleza
                    $debitoCierre = $saldo > 0 ? $saldo : 0;
                    $creditoCierre = $saldo < 0 ? abs($saldo) : 0;
                } else {
                    // Costos (6,7) y Gastos (5): naturaleza débito. Saldo = debito - credito
                    $saldo = $debito - $credito;
                    $totalCostosGastos += $saldo;

                    if ($saldo == 0) continue;

                    $creditoCierre = $saldo > 0 ? $saldo : 0;
                    $debitoCierre = $saldo < 0 ? abs($saldo) : 0;
                }

                $this->registrarMovimiento([
                    'fecha' => $fechaCierre,
                    'tipo_documento' => 'cierre_contable',
                    'numero_documento' => 'CIERRE-' . $anoFiscal,
                    'id_documento' => $idCierre,
                    'codigo_cuenta' => $cuenta['codigo_cuenta'],
                    'nombre_cuenta' => $cuenta['nombre_cuenta'],
                    'concepto' => "Cierre contable año $anoFiscal - cancelación de saldo",
                    'debito' => $debitoCierre,
                    'credito' => $creditoCierre
                ]);
            }

            // 5. Calcular la utilidad/pérdida del ejercicio y trasladarla a patrimonio
            $utilidadEjercicio = $totalIngresos - $totalCostosGastos;

            if ($utilidadEjercicio > 0) {
                $this->registrarMovimiento([
                    'fecha' => $fechaCierre,
                    'tipo_documento' => 'cierre_contable',
                    'numero_documento' => 'CIERRE-' . $anoFiscal,
                    'id_documento' => $idCierre,
                    'codigo_cuenta' => '360505',
                    'nombre_cuenta' => 'Utilidad del ejercicio',
                    'concepto' => "Cierre contable año $anoFiscal - traslado de utilidad a patrimonio",
                    'debito' => 0,
                    'credito' => $utilidadEjercicio
                ]);
            } elseif ($utilidadEjercicio < 0) {
                $this->registrarMovimiento([
                    'fecha' => $fechaCierre,
                    'tipo_documento' => 'cierre_contable',
                    'numero_documento' => 'CIERRE-' . $anoFiscal,
                    'id_documento' => $idCierre,
                    'codigo_cuenta' => '361005',
                    'nombre_cuenta' => 'Pérdida del ejercicio',
                    'concepto' => "Cierre contable año $anoFiscal - traslado de pérdida a patrimonio",
                    'debito' => abs($utilidadEjercicio),
                    'credito' => 0
                ]);
            }

            // 6. Actualizar el registro del cierre con la utilidad ya calculada
            $stmtUpdate = $this->pdo->prepare(
                "UPDATE cierres_contables SET utilidad_ejercicio = :utilidad WHERE id = :id"
            );
            $stmtUpdate->execute([':utilidad' => $utilidadEjercicio, ':id' => $idCierre]);

            $this->pdo->commit();

            return [
                'id_cierre' => $idCierre,
                'ano_fiscal' => $anoFiscal,
                'utilidad_ejercicio' => $utilidadEjercicio
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Revierte un cierre contable activo: elimina el asiento de cierre generado
     * y marca el registro como 'reversado' (no lo borra, para dejar historial).
     */
    public function reversarCierreContable($anoFiscal) {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM cierres_contables WHERE ano_fiscal = :ano AND estado = 'activo'"
        );
        $stmt->execute([':ano' => $anoFiscal]);
        $cierre = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cierre) {
            throw new Exception("No hay un cierre contable activo para el año $anoFiscal.");
        }

        $idCierre = $cierre['id'];

        $this->pdo->beginTransaction();

        try {
            // Eliminar todas las líneas del asiento de cierre generadas para este cierre
            $this->eliminarMovimientos('cierre_contable', $idCierre);

            // Marcar el cierre como reversado (se conserva el registro como historial)
            $stmtUpdate = $this->pdo->prepare(
                "UPDATE cierres_contables SET estado = 'reversado' WHERE id = :id"
            );
            $stmtUpdate->execute([':id' => $idCierre]);

            $this->pdo->commit();

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Verifica si el año de una fecha dada ya tiene un cierre contable ACTIVO.
     * Se usa para bloquear la creación/edición/eliminación de documentos
     * dentro de un periodo ya cerrado.
     */
    public function existeCierreActivoParaFecha($fecha) {
        $anio = date('Y', strtotime($fecha));
        $stmt = $this->pdo->prepare(
            "SELECT id FROM cierres_contables WHERE ano_fiscal = :anio AND estado = 'activo'"
        );
        $stmt->execute([':anio' => $anio]);
        return $stmt->fetch() !== false;
    }
    
    /**
 * Registra asientos de Factura de Venta
 * CORRECCIÓN: Se usa subtotal sin IVA para las ventas
 */
public function registrarFacturaVenta($idFactura) {
    // Obtener datos de la factura
    $sqlFactura = "SELECT * FROM facturav WHERE id = :id";
    $stmt = $this->pdo->prepare($sqlFactura);
    $stmt->execute([':id' => $idFactura]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$factura) {
        throw new Exception("Factura no encontrada");
    }
    
    // Obtener detalles con categorías
    $sqlDetalle = "SELECT 
                    fd.*,
                    pi.categoriaInventarios,
                    pi.tipoItem,
                    pi.costoUnitario as costo_promedio
                FROM factura_detalle fd
                INNER JOIN productoinventarios pi ON fd.codigoProducto = pi.codigoProducto
                WHERE fd.id_factura = :id_factura";
    $stmt = $this->pdo->prepare($sqlDetalle);
    $stmt->execute([':id_factura' => $idFactura]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // DEBUG: Verificar que se estén obteniendo los detalles
    if (empty($detalles)) {
        error_log("DEBUG VENTA: No se encontraron detalles (JOIN vacío) para factura ID: " . $idFactura);
    }
    
    // 1. VERIFICAR SI HAY MULTIPLES MEDIOS DE PAGO
    $sqlMediosPago = "SELECT * FROM medios_pago_factura 
                     WHERE factura_id = :factura_id AND tipo_factura = 'venta'";
    $stmt = $this->pdo->prepare($sqlMediosPago);
    $stmt->execute([':factura_id' => $idFactura]);
    $mediosPago = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // REGISTRO DEL DEBITO (Cliente o Medio de Pago)
    if (!empty($mediosPago)) {
        // Multiples medios de pago: procesar cada uno individualmente
        $totalMediosPago = 0;

        foreach ($mediosPago as $medio) {
            $valor = floatval($medio['valor']);
            $totalMediosPago += $valor;

            $esCredito = stripos($medio['forma_pago'], 'credito') !== false ||
                        stripos($medio['forma_pago'], 'crédito') !== false;

            if ($esCredito) {
                // DEBITO: Clientes
                $this->registrarMovimiento([
                    'fecha' => $factura['fecha'],
                    'tipo_documento' => 'factura_venta',
                    'numero_documento' => $factura['consecutivo'],
                    'id_documento' => $idFactura,
                    'codigo_cuenta' => '130505',
                    'nombre_cuenta' => 'Clientes Nacionales',
                    'tercero_identificacion' => $factura['identificacion'],
                    'tercero_nombre' => $factura['nombre'],
                    'concepto' => "Venta a crédito según factura {$factura['consecutivo']} - {$medio['forma_pago']}",
                    'debito' => $valor,
                    'credito' => 0
                ]);
            } else {
                // DEBITO: Medio de pago especifico
                $formaPagoCompleta = $medio['forma_pago'] . ' - ' . $medio['cuenta_contable'];
                $cuentaPago = $this->obtenerCuentaMedioPago($formaPagoCompleta);

                $this->registrarMovimiento([
                    'fecha' => $factura['fecha'],
                    'tipo_documento' => 'factura_venta',
                    'numero_documento' => $factura['consecutivo'],
                    'id_documento' => $idFactura,
                    'codigo_cuenta' => $cuentaPago['codigo'],
                    'nombre_cuenta' => $cuentaPago['nombre'],
                    'tercero_identificacion' => $factura['identificacion'],
                    'tercero_nombre' => $factura['nombre'],
                    'concepto' => "Cobro venta según factura {$factura['consecutivo']} - {$medio['forma_pago']}",
                    'debito' => $valor,
                    'credito' => 0
                ]);
            }
        }

        // Validar que la suma de medios de pago coincida con el valor total
        $valorTotalFactura = floatval($factura['valorTotal']);
        $diferencia = abs($valorTotalFactura - $totalMediosPago);

        if ($diferencia > 0.01) {
            throw new Exception(sprintf(
                "Error en medios de pago: Total factura=%.2f, Suma medios pago=%.2f, Diferencia=%.2f",
                $valorTotalFactura,
                $totalMediosPago,
                $diferencia
            ));
        }

    } else {
        // Metodo antiguo: usar solo formaPago de la factura (compatibilidad con facturas viejas)
        $formaPago = $factura['formaPago'];
        $esCredito = stripos($formaPago, 'credito') !== false;

        if ($esCredito) {
            // DEBITO: Clientes
            $this->registrarMovimiento([
                'fecha' => $factura['fecha'],
                'tipo_documento' => 'factura_venta',
                'numero_documento' => $factura['consecutivo'],
                'id_documento' => $idFactura,
                'codigo_cuenta' => '130505',
                'nombre_cuenta' => 'Clientes Nacionales',
                'tercero_identificacion' => $factura['identificacion'],
                'tercero_nombre' => $factura['nombre'],
                'concepto' => "Venta a crédito según factura {$factura['consecutivo']}",
                'debito' => $factura['valorTotal'],
                'credito' => 0
            ]);
        } else {
            // DEBITO: Caja/Banco según medio de pago
            $cuentaPago = $this->obtenerCuentaMedioPago($formaPago);
            $this->registrarMovimiento([
                'fecha' => $factura['fecha'],
                'tipo_documento' => 'factura_venta',
                'numero_documento' => $factura['consecutivo'],
                'id_documento' => $idFactura,
                'codigo_cuenta' => $cuentaPago['codigo'],
                'nombre_cuenta' => $cuentaPago['nombre'],
                'tercero_identificacion' => $factura['identificacion'],
                'tercero_nombre' => $factura['nombre'],
                'concepto' => "Cobro venta según factura {$factura['consecutivo']}",
                'debito' => $factura['valorTotal'],
                'credito' => 0
            ]);
        }
    }
    
    // 2. CREDITO: IVA por Pagar
    if ($factura['ivaTotal'] > 0) {
        $this->registrarMovimiento([
            'fecha' => $factura['fecha'],
            'tipo_documento' => 'factura_venta',
            'numero_documento' => $factura['consecutivo'],
            'id_documento' => $idFactura,
            'codigo_cuenta' => '240805',
            'nombre_cuenta' => 'IVA por Pagar',
            'tercero_identificacion' => $factura['identificacion'],
            'tercero_nombre' => $factura['nombre'],
            'concepto' => "IVA generado en venta factura {$factura['consecutivo']}",
            'debito' => 0,
            'credito' => $factura['ivaTotal']
        ]);
    }
    
    // 3. CREDITO: Retención en la Fuente (CORREGIDO: cuenta 135515)
    if ($factura['retenciones'] > 0) {
        $this->registrarMovimiento([
            'fecha' => $factura['fecha'],
            'tipo_documento' => 'factura_venta',
            'numero_documento' => $factura['consecutivo'],
            'id_documento' => $idFactura,
            'codigo_cuenta' => '135515', // CUENTA CORREGIDA
            'nombre_cuenta' => 'Retención en la Fuente por Cobrar',
            'tercero_identificacion' => $factura['identificacion'],
            'tercero_nombre' => $factura['nombre'],
            'concepto' => "Retención aplicada factura {$factura['consecutivo']}",
            'credito' => 0,
            'debito' => $factura['retenciones']
        ]);
    }
    
    // 4. CREDITO: Ventas (agrupado por categoría) - CORREGIDO: usar subtotal sin IVA
    $ventasPorCategoria = [];
    foreach ($detalles as $detalle) {
        $catId = $detalle['categoriaInventarios'];
        if (!isset($ventasPorCategoria[$catId])) {
            $ventasPorCategoria[$catId] = 0;
        }
        // CORRECCIÓN: Calcular subtotal sin IVA (precioUnitario * cantidad)
        $subtotalItem = $detalle['precio_unitario'] * $detalle['cantidad'];
        $ventasPorCategoria[$catId] += $subtotalItem;
    }
    
    foreach ($ventasPorCategoria as $catId => $subtotal) {
        $cuentas = $this->obtenerCuentasCategoria($catId);
        
        $this->registrarMovimiento([
            'fecha' => $factura['fecha'],
            'tipo_documento' => 'factura_venta',
            'numero_documento' => $factura['consecutivo'],
            'id_documento' => $idFactura,
            'codigo_cuenta' => $cuentas['codigoCuentaVentas'],
            'nombre_cuenta' => $cuentas['cuentaVentas'],
            'tercero_identificacion' => $factura['identificacion'],
            'tercero_nombre' => $factura['nombre'],
            'concepto' => "Venta de mercancías factura {$factura['consecutivo']}",
            'debito' => 0,
            'credito' => $subtotal
        ]);
    }
    
    // 5. REGISTRO DEL COSTO (solo para productos) - MÉTODO SIMPLIFICADO
    $costosPorCategoria = [];
    foreach ($detalles as $detalle) {
        if (strtolower($detalle['tipoItem']) === 'producto') {
            $catId = $detalle['categoriaInventarios'];
            
            // USAR DIRECTAMENTE EL COSTO UNITARIO DE LA TABLA
            $costoUnitario = $detalle['costo_promedio'] ?? 0;
            
            // Si no hay costo definido, estimar como 70% del precio de venta
            if ($costoUnitario <= 0) {
                $costoUnitario = $detalle['precio_unitario'] * 0.7;
            }
            
            $costoTotal = $costoUnitario * $detalle['cantidad'];
            
            if (!isset($costosPorCategoria[$catId])) {
                $costosPorCategoria[$catId] = 0;
            }
            $costosPorCategoria[$catId] += $costoTotal;
        }
    }

    // REGISTRAR LOS COSTOS SOLO SI HAY PRODUCTOS CON COSTO
    foreach ($costosPorCategoria as $catId => $costoTotal) {
        if ($costoTotal > 0) {
            $cuentas = $this->obtenerCuentasCategoria($catId);
            
            // DEBITO: Costo de Ventas
            $this->registrarMovimiento([
                'fecha' => $factura['fecha'],
                'tipo_documento' => 'factura_venta',
                'numero_documento' => $factura['consecutivo'],
                'id_documento' => $idFactura,
                'codigo_cuenta' => $cuentas['codigoCuentaCostos'],
                'nombre_cuenta' => $cuentas['cuentaCostos'],
                'tercero_identificacion' => $factura['identificacion'],
                'tercero_nombre' => $factura['nombre'],
                'concepto' => "Costo de mercancía vendida factura {$factura['consecutivo']}",
                'debito' => $costoTotal,
                'credito' => 0
            ]);
            
            // CREDITO: Inventario
            $this->registrarMovimiento([
                'fecha' => $factura['fecha'],
                'tipo_documento' => 'factura_venta',
                'numero_documento' => $factura['consecutivo'],
                'id_documento' => $idFactura,
                'codigo_cuenta' => $cuentas['codigoCuentaInventarios'],
                'nombre_cuenta' => $cuentas['cuentaInventarios'],
                'tercero_identificacion' => $factura['identificacion'],
                'tercero_nombre' => $factura['nombre'],
                'concepto' => "Salida de inventario factura {$factura['consecutivo']}",
                'debito' => 0,
                'credito' => $costoTotal
            ]);
        }
    }
}
    
    /**
 * Registra asientos de Factura de Compra
 * CORRECCIÓN: Maneja múltiples medios de pago manteniendo la cuenta correcta del inventario
 */
public function registrarFacturaCompra($idFactura) {
    // Obtener datos de la factura
    $sqlFactura = "SELECT * FROM facturac WHERE id = :id";
    $stmt = $this->pdo->prepare($sqlFactura);
    $stmt->execute([':id' => $idFactura]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$factura) {
        throw new Exception("Factura de compra no encontrada");
    }
    
    // Obtener detalles con categorías - IMPORTANTE: verificar que la consulta funcione
    $sqlDetalle = "SELECT 
                    fd.*,
                    pi.categoriaInventarios,
                    pi.tipoItem
                FROM detallefacturac fd
                INNER JOIN productoinventarios pi ON fd.codigoProducto = pi.codigoProducto
                WHERE fd.factura_id = :id_factura";
    $stmt = $this->pdo->prepare($sqlDetalle);
    $stmt->execute([':id_factura' => $idFactura]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // DEBUG: Verificar lo que se está obteniendo
    if (empty($detalles)) {
        error_log("DEBUG: No se encontraron detalles para factura ID: " . $idFactura);
    } else {
        foreach ($detalles as $detalle) {
            error_log("DEBUG: Producto: " . $detalle['codigoProducto'] . 
                     ", Categoría: " . $detalle['categoriaInventarios'] . 
                     ", Tipo: " . $detalle['tipoItem']);
        }
    }
    
    // 1. DEBITO: Inventario/Compras (agrupado por categoría) - MÉTODO DEL CÓDIGO ANTERIOR
    $comprasPorCategoria = [];
    $totalInventario = 0;
    
    foreach ($detalles as $detalle) {
        // Solo registrar en inventario si es producto
        if (strtolower($detalle['tipoItem']) === 'producto') {
            $catId = $detalle['categoriaInventarios'] ?? 0;
            if (!isset($comprasPorCategoria[$catId])) {
                $comprasPorCategoria[$catId] = 0;
            }
            // Calcular subtotal (precioUnitario * cantidad)
            $subtotalLinea = $detalle['precioUnitario'] * $detalle['cantidad'];
            $comprasPorCategoria[$catId] += $subtotalLinea;
            $totalInventario += $subtotalLinea;
        }
    }
    
    // REGISTRAR EL DÉBITO DE INVENTARIO - MÉTODO MEJORADO
    if ($totalInventario > 0) {
        // Si hay productos con categorías definidas
        foreach ($comprasPorCategoria as $catId => $total) {
            if ($catId > 0) {
                $cuentas = $this->obtenerCuentasCategoria($catId);
                if ($cuentas && isset($cuentas['codigoCuentaInventarios'])) {
                    $this->registrarMovimiento([
                        'fecha' => $factura['fecha'],
                        'tipo_documento' => 'factura_compra',
                        'numero_documento' => $factura['consecutivo'],
                        'id_documento' => $idFactura,
                        'codigo_cuenta' => $cuentas['codigoCuentaInventarios'],
                        'nombre_cuenta' => $cuentas['cuentaInventarios'],
                        'tercero_identificacion' => $factura['identificacion'],
                        'tercero_nombre' => $factura['nombre'],
                        'concepto' => "Compra de mercancías factura {$factura['numeroFactura']}",
                        'debito' => $total,
                        'credito' => 0
                    ]);
                }
            }
        }
        
        // Si alguna categoría no fue encontrada (catId = 0 o no existe), usar cuenta por defecto
        if (isset($comprasPorCategoria[0]) && $comprasPorCategoria[0] > 0) {
            $this->registrarMovimiento([
                'fecha' => $factura['fecha'],
                'tipo_documento' => 'factura_compra',
                'numero_documento' => $factura['consecutivo'],
                'id_documento' => $idFactura,
                'codigo_cuenta' => '143501', // Cuenta por defecto para inventario
                'nombre_cuenta' => 'Mercancía gravada tarifa general_19%',
                'tercero_identificacion' => $factura['identificacion'],
                'tercero_nombre' => $factura['nombre'],
                'concepto' => "Compra de mercancías factura {$factura['numeroFactura']}",
                'debito' => $comprasPorCategoria[0],
                'credito' => 0
            ]);
        }
    } else {
        // Si no hay productos (solo servicios), usar el subtotal directamente
        // Esto es mejor que usar "Gastos Varios"
        $this->registrarMovimiento([
            'fecha' => $factura['fecha'],
            'tipo_documento' => 'factura_compra',
            'numero_documento' => $factura['consecutivo'],
            'id_documento' => $idFactura,
            'codigo_cuenta' => '143501', // MISMA CUENTA que usa el código anterior
            'nombre_cuenta' => 'Mercancía gravada tarifa general_19%',
            'tercero_identificacion' => $factura['identificacion'],
            'tercero_nombre' => $factura['nombre'],
            'concepto' => "Compra de mercancías factura {$factura['numeroFactura']}",
            'debito' => $factura['subtotal'],
            'credito' => 0
        ]);
    }
    
    // 2. DEBITO: IVA Descontable
    if ($factura['ivaTotal'] > 0) {
        $this->registrarMovimiento([
            'fecha' => $factura['fecha'],
            'tipo_documento' => 'factura_compra',
            'numero_documento' => $factura['consecutivo'],
            'id_documento' => $idFactura,
            'codigo_cuenta' => '240805',
            'nombre_cuenta' => 'IVA por Pagar',
            'tercero_identificacion' => $factura['identificacion'],
            'tercero_nombre' => $factura['nombre'],
            'concepto' => "IVA en compras factura {$factura['numeroFactura']}",
            'debito' => $factura['ivaTotal'],
            'credito' => 0
        ]);
    }
    
    // 3. CREDITO: Retención en la Fuente (si aplica)
    if (isset($factura['retenciones']) && $factura['retenciones'] > 0) {
        $this->registrarMovimiento([
            'fecha' => $factura['fecha'],
            'tipo_documento' => 'factura_compra',
            'numero_documento' => $factura['consecutivo'],
            'id_documento' => $idFactura,
            'codigo_cuenta' => '236505',
            'nombre_cuenta' => 'Retención en la Fuente por Pagar',
            'tercero_identificacion' => $factura['identificacion'],
            'tercero_nombre' => $factura['nombre'],
            'concepto' => "Retención factura {$factura['numeroFactura']}",
            'debito' => 0,
            'credito' => $factura['retenciones']
        ]);
    }
    
    // 4. VERIFICAR SI HAY MÚLTIPLES MEDIOS DE PAGO
    $sqlMediosPago = "SELECT * FROM medios_pago_factura 
                     WHERE factura_id = :factura_id AND tipo_factura = 'compra'";
    $stmt = $this->pdo->prepare($sqlMediosPago);
    $stmt->execute([':factura_id' => $idFactura]);
    $mediosPago = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si hay múltiples medios de pago, procesarlos individualmente
    if (!empty($mediosPago)) {
        $totalMediosPago = 0;
        
        foreach ($mediosPago as $medio) {
            $valor = floatval($medio['valor']);
            $totalMediosPago += $valor;
            
            // Determinar si es crédito
            $esCredito = stripos($medio['forma_pago'], 'credito') !== false || 
                        stripos($medio['forma_pago'], 'crédito') !== false;
            
            if ($esCredito) {
                // CREDITO: Proveedores
                $this->registrarMovimiento([
                    'fecha' => $factura['fecha'],
                    'tipo_documento' => 'factura_compra',
                    'numero_documento' => $factura['consecutivo'],
                    'id_documento' => $idFactura,
                    'codigo_cuenta' => '220501',
                    'nombre_cuenta' => 'Proveedores Nacionales',
                    'tercero_identificacion' => $factura['identificacion'],
                    'tercero_nombre' => $factura['nombre'],
                    'concepto' => "Compra a crédito factura {$factura['numeroFactura']} - {$medio['forma_pago']}",
                    'debito' => 0,
                    'credito' => $valor
                ]);
            } else {
                // CREDITO: Medio de pago específico
                $formaPagoCompleta = $medio['forma_pago'] . ' - ' . $medio['cuenta_contable'];
                $cuentaPago = $this->obtenerCuentaMedioPago($formaPagoCompleta);
                
                $this->registrarMovimiento([
                    'fecha' => $factura['fecha'],
                    'tipo_documento' => 'factura_compra',
                    'numero_documento' => $factura['consecutivo'],
                    'id_documento' => $idFactura,
                    'codigo_cuenta' => $cuentaPago['codigo'],
                    'nombre_cuenta' => $cuentaPago['nombre'],
                    'tercero_identificacion' => $factura['identificacion'],
                    'tercero_nombre' => $factura['nombre'],
                    'concepto' => "Pago compra factura {$factura['numeroFactura']} - {$medio['forma_pago']}",
                    'debito' => 0,
                    'credito' => $valor
                ]);
            }
        }
        
        // Validar que la suma de medios de pago coincida con el valor total
        $valorTotalFactura = floatval($factura['valorTotal']);
        $diferencia = abs($valorTotalFactura - $totalMediosPago);
        
        if ($diferencia > 0.01) {
            throw new Exception(sprintf(
                "Error en medios de pago: Total factura=%.2f, Suma medios pago=%.2f, Diferencia=%.2f",
                $valorTotalFactura,
                $totalMediosPago,
                $diferencia
            ));
        }
        
    } else {
        // Método antiguo: usar solo formaPago de la factura
        $formaPago = $factura['formaPago'];
        $esCredito = stripos($formaPago, 'credito') !== false;
        
        if ($esCredito) {
            // CREDITO: Proveedores
            $this->registrarMovimiento([
                'fecha' => $factura['fecha'],
                'tipo_documento' => 'factura_compra',
                'numero_documento' => $factura['consecutivo'],
                'id_documento' => $idFactura,
                'codigo_cuenta' => '220501',
                'nombre_cuenta' => 'Proveedores Nacionales',
                'tercero_identificacion' => $factura['identificacion'],
                'tercero_nombre' => $factura['nombre'],
                'concepto' => "Compra a crédito factura {$factura['numeroFactura']}",
                'debito' => 0,
                'credito' => $factura['valorTotal']
            ]);
        } else {
            // CREDITO: Medio de pago único
            $cuentaPago = $this->obtenerCuentaMedioPago($formaPago);
            $this->registrarMovimiento([
                'fecha' => $factura['fecha'],
                'tipo_documento' => 'factura_compra',
                'numero_documento' => $factura['consecutivo'],
                'id_documento' => $idFactura,
                'codigo_cuenta' => $cuentaPago['codigo'],
                'nombre_cuenta' => $cuentaPago['nombre'],
                'tercero_identificacion' => $factura['identificacion'],
                'tercero_nombre' => $factura['nombre'],
                'concepto' => "Pago compra factura {$factura['numeroFactura']}",
                'debito' => 0,
                'credito' => $factura['valorTotal']
            ]);
        }
    }
}
    /**
     * Registra asientos de Recibo de Caja
     */
    public function registrarReciboCaja($idRecibo) {
        // Obtener datos del recibo
        $sqlRecibo = "SELECT * FROM docrecibodecaja WHERE id = :id";
        $stmt = $this->pdo->prepare($sqlRecibo);
        $stmt->execute([':id' => $idRecibo]);
        $recibo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recibo) {
            throw new Exception("Recibo de caja no encontrado");
        }

        // Verificar si hay múltiples medios de pago
        $sqlMediosPago = "SELECT * FROM medios_pago_recibo_caja WHERE recibo_id = :recibo_id";
        $stmt = $this->pdo->prepare($sqlMediosPago);
        $stmt->execute([':recibo_id' => $idRecibo]);
        $mediosPago = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($mediosPago)) {
            // Múltiples medios de pago: un DEBITO por cada uno
            $totalMediosPago = 0;

            foreach ($mediosPago as $medio) {
                $valor = floatval($medio['valor']);
                $totalMediosPago += $valor;

                $formaPagoCompleta = $medio['forma_pago'] . ' - ' . $medio['cuenta_contable'];
                $cuentaPago = $this->obtenerCuentaMedioPago($formaPagoCompleta);

                // DEBITO: Caja/Banco según medio de pago
                $this->registrarMovimiento([
                    'fecha' => $recibo['fecha'],
                    'tipo_documento' => 'recibo_caja',
                    'numero_documento' => $recibo['consecutivo'],
                    'id_documento' => $idRecibo,
                    'codigo_cuenta' => $cuentaPago['codigo'],
                    'nombre_cuenta' => $cuentaPago['nombre'],
                    'tercero_identificacion' => $recibo['identificacion'],
                    'tercero_nombre' => $recibo['nombre'],
                    'concepto' => "Recibo de caja No. {$recibo['consecutivo']} - {$medio['forma_pago']}",
                    'debito' => $valor,
                    'credito' => 0
                ]);
            }

            // Validar que la suma de medios de pago coincida con el valor total
            $valorTotalRecibo = floatval($recibo['valorTotal']);
            $diferencia = abs($valorTotalRecibo - $totalMediosPago);

            if ($diferencia > 0.01) {
                throw new Exception(sprintf(
                    "Error en medios de pago del recibo: Total recibo=%.2f, Suma medios pago=%.2f, Diferencia=%.2f",
                    $valorTotalRecibo, $totalMediosPago, $diferencia
                ));
            }

        } else {
            // Compatibilidad con recibos antiguos (un solo medio de pago en formaPago)
            $cuentaPago = $this->obtenerCuentaMedioPago($recibo['formaPago']);

            $this->registrarMovimiento([
                'fecha' => $recibo['fecha'],
                'tipo_documento' => 'recibo_caja',
                'numero_documento' => $recibo['consecutivo'],
                'id_documento' => $idRecibo,
                'codigo_cuenta' => $cuentaPago['codigo'],
                'nombre_cuenta' => $cuentaPago['nombre'],
                'tercero_identificacion' => $recibo['identificacion'],
                'tercero_nombre' => $recibo['nombre'],
                'concepto' => "Recibo de caja No. {$recibo['consecutivo']} - Pago de {$recibo['nombre']}",
                'debito' => $recibo['valorTotal'],
                'credito' => 0
            ]);
        }

        // CREDITO: Clientes (siempre, sin importar cuántos medios de pago hubo)
        $this->registrarMovimiento([
            'fecha' => $recibo['fecha'],
            'tipo_documento' => 'recibo_caja',
            'numero_documento' => $recibo['consecutivo'],
            'id_documento' => $idRecibo,
            'codigo_cuenta' => '130505',
            'nombre_cuenta' => 'Clientes Nacionales',
            'tercero_identificacion' => $recibo['identificacion'],
            'tercero_nombre' => $recibo['nombre'],
            'concepto' => "Abono cliente recibo No. {$recibo['consecutivo']}",
            'debito' => 0,
            'credito' => $recibo['valorTotal']
        ]);
    }
    
    /**
     * Registra asientos de Comprobante de Egreso
     */
    public function registrarComprobanteEgreso($idComprobante) {
        // Obtener datos del comprobante
        $sqlComprobante = "SELECT * FROM doccomprobanteegreso WHERE id = :id";
        $stmt = $this->pdo->prepare($sqlComprobante);
        $stmt->execute([':id' => $idComprobante]);
        $comprobante = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comprobante) {
            throw new Exception("Comprobante de egreso no encontrado");
        }
        
        // 1. DEBITO: Proveedores (siempre, por el valor total)
        $this->registrarMovimiento([
            'fecha' => $comprobante['fecha'],
            'tipo_documento' => 'comprobante_egreso',
            'numero_documento' => $comprobante['consecutivo'],
            'id_documento' => $idComprobante,
            'codigo_cuenta' => '220501',
            'nombre_cuenta' => 'Proveedores Nacionales',
            'tercero_identificacion' => $comprobante['identificacion'],
            'tercero_nombre' => $comprobante['nombre'],
            'concepto' => "Pago a proveedor comprobante No. {$comprobante['consecutivo']}",
            'debito' => $comprobante['valorTotal'],
            'credito' => 0
        ]);

        // 2. Verificar si hay múltiples medios de pago
        $sqlMediosPago = "SELECT * FROM medios_pago_comprobante_egreso WHERE comprobante_id = :comprobante_id";
        $stmt = $this->pdo->prepare($sqlMediosPago);
        $stmt->execute([':comprobante_id' => $idComprobante]);
        $mediosPago = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($mediosPago)) {
            // Múltiples medios de pago: un CREDITO por cada uno
            $totalMediosPago = 0;

            foreach ($mediosPago as $medio) {
                $valor = floatval($medio['valor']);
                $totalMediosPago += $valor;

                $formaPagoCompleta = $medio['forma_pago'] . ' - ' . $medio['cuenta_contable'];
                $cuentaPago = $this->obtenerCuentaMedioPago($formaPagoCompleta);

                // CREDITO: Caja/Banco según medio de pago
                $this->registrarMovimiento([
                    'fecha' => $comprobante['fecha'],
                    'tipo_documento' => 'comprobante_egreso',
                    'numero_documento' => $comprobante['consecutivo'],
                    'id_documento' => $idComprobante,
                    'codigo_cuenta' => $cuentaPago['codigo'],
                    'nombre_cuenta' => $cuentaPago['nombre'],
                    'tercero_identificacion' => $comprobante['identificacion'],
                    'tercero_nombre' => $comprobante['nombre'],
                    'concepto' => "Egreso por pago comprobante No. {$comprobante['consecutivo']} - {$medio['forma_pago']}",
                    'debito' => 0,
                    'credito' => $valor
                ]);
            }

            // Validar que la suma de medios de pago coincida con el valor total
            $valorTotalComprobante = floatval($comprobante['valorTotal']);
            $diferencia = abs($valorTotalComprobante - $totalMediosPago);

            if ($diferencia > 0.01) {
                throw new Exception(sprintf(
                    "Error en medios de pago del comprobante: Total comprobante=%.2f, Suma medios pago=%.2f, Diferencia=%.2f",
                    $valorTotalComprobante, $totalMediosPago, $diferencia
                ));
            }

        } else {
            // Compatibilidad con comprobantes antiguos (un solo medio de pago en formaPago)
            $cuentaPago = $this->obtenerCuentaMedioPago($comprobante['formaPago']);
            $this->registrarMovimiento([
                'fecha' => $comprobante['fecha'],
                'tipo_documento' => 'comprobante_egreso',
                'numero_documento' => $comprobante['consecutivo'],
                'id_documento' => $idComprobante,
                'codigo_cuenta' => $cuentaPago['codigo'],
                'nombre_cuenta' => $cuentaPago['nombre'],
                'tercero_identificacion' => $comprobante['identificacion'],
                'tercero_nombre' => $comprobante['nombre'],
                'concepto' => "Egreso por pago comprobante No. {$comprobante['consecutivo']}",
                'debito' => 0,
                'credito' => $comprobante['valorTotal']
            ]);
        }
    }
    
    /**
     * Registra asientos de Comprobante Contable
     */
    public function registrarComprobanteContable($idComprobante) {
        // Obtener detalles del comprobante
        $sqlDetalles = "SELECT * FROM detallecomprobantecontable WHERE comprobante_id = :id";
        $stmt = $this->pdo->prepare($sqlDetalles);
        $stmt->execute([':id' => $idComprobante]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener fecha y consecutivo del comprobante
        $sqlComprobante = "SELECT * FROM doccomprobantecontable WHERE id = :id";
        $stmt = $this->pdo->prepare($sqlComprobante);
        $stmt->execute([':id' => $idComprobante]);
        $comprobante = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comprobante) {
            throw new Exception("Comprobante contable no encontrado");
        }
        
        // Registrar cada línea del comprobante
        foreach ($detalles as $detalle) {
            // Extraer código de cuenta y normalizar nombre
            $codigoCuenta = $detalle['cuentaContable'];
            $nombreCuenta = $detalle['descripcionCuenta'];

            // Si el código viene con formato "110505-Caja general", separarlo
            if (strpos($codigoCuenta, '-') !== false) {
                $partes = explode('-', $codigoCuenta, 2);
                $codigoCuenta = trim($partes[0]);
                // Usar el nombre proporcionado o el extraído
                if (empty($nombreCuenta) && isset($partes[1])) {
                    $nombreCuenta = trim($partes[1]);
                }
            }

            // NORMALIZAR NOMBRES DE CUENTAS ESPECÍFICAS
            switch($codigoCuenta) {
                case '130505':
                    $nombreCuenta = 'Clientes Nacionales'; // FORZAR NOMBRE CONSISTENTE
                    break;
                case '110505':
                    $nombreCuenta = 'Caja general';
                    break;
                case '11100501':
                    $nombreCuenta = 'Bancolombia';
                    break;
                // Agregar más casos según necesites
            }

            // Después de extraer el código y nombre:
            $nombreCuenta = $this->normalizarNombreCuenta($codigoCuenta, $nombreCuenta);

            $this->registrarMovimiento([
                'fecha' => $comprobante['fecha'],
                'tipo_documento' => 'comprobante_contable',
                'numero_documento' => $comprobante['consecutivo'],
                'id_documento' => $idComprobante,
                'codigo_cuenta' => $codigoCuenta,
                'nombre_cuenta' => $detalle['descripcionCuenta'],
                'tercero_identificacion' => $detalle['tercero'],
                'tercero_nombre' => null,
                'concepto' => $detalle['detalle'] ?? "Comprobante contable No. {$comprobante['consecutivo']}",
                'debito' => $detalle['valorDebito'] ?? 0,
                'credito' => $detalle['valorCredito'] ?? 0
            ]);
        }
    }

     /**
     * Normaliza el nombre de una cuenta contable para mantener consistencia
     */
    private function normalizarNombreCuenta($codigo, $nombreActual) {
        $nombresNormalizados = [
            '130505' => 'Clientes Nacionales',
            '110505' => 'Caja general', 
            '11100501' => 'Bancolombia',
            '220501' => 'Proveedores Nacionales',
            '240805' => 'IVA por Pagar',
            '135515' => 'Retención en la Fuente por Cobrar',
            '236505' => 'Retención en la Fuente por Pagar'
        ];
        
        return $nombresNormalizados[$codigo] ?? $nombreActual;
    }
}
?>