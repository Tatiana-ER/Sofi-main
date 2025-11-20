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
    
    // Determinar si es efectivo, transferencia o crédito
    $formaPago = $factura['formaPago'];
    $esCredito = stripos($formaPago, 'credito') !== false;
    
    // 1. REGISTRO DEL DÉBITO (Cliente o Medio de Pago)
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
     * CORRECCIÓN: Se registra el SUBTOTAL en inventario, no el valor total con IVA
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
        
        // Obtener detalles con categorías
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
        
        // 1. DEBITO: Inventario/Compras (agrupado por categoría) - CORREGIDO: usar subtotal, no valorTotal
        $comprasPorCategoria = [];
        foreach ($detalles as $detalle) {
            // Solo registrar en inventario si es producto, no servicio
            if (strtolower($detalle['tipoItem']) === 'producto') {
                $catId = $detalle['categoriaInventarios'];
                if (!isset($comprasPorCategoria[$catId])) {
                    $comprasPorCategoria[$catId] = 0;
                }
                // CORRECCIÓN: Calcular subtotal (precioUnitario * cantidad) en lugar de usar valorTotal
                $subtotalLinea = $detalle['precioUnitario'] * $detalle['cantidad'];
                $comprasPorCategoria[$catId] += $subtotalLinea;
            }
        }
        
        foreach ($comprasPorCategoria as $catId => $total) {
            $cuentas = $this->obtenerCuentasCategoria($catId);
            
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
        
        // 2. DEBITO: IVA Descontable (se debita en compras para compensar)
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
        
        // 4. CREDITO: Proveedor o Medio de Pago
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
            // CREDITO: Caja/Banco
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
        
        // Obtener cuenta del medio de pago
        $cuentaPago = $this->obtenerCuentaMedioPago($recibo['formaPago']);
        
        // 1. DEBITO: Caja/Banco
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
        
        // 2. CREDITO: Clientes
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
        
        // 1. DEBITO: Proveedores
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
        
        // 2. CREDITO: Caja/Banco
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
            // Extraer código de cuenta (puede venir como "1105-Caja" o solo "1105")
            $codigoCuenta = $detalle['cuentaContable'];
            if (strpos($codigoCuenta, '-') !== false) {
                $partes = explode('-', $codigoCuenta);
                $codigoCuenta = $partes[0];
            }
            
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
}
?>