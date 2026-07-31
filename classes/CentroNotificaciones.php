<?php
/**
 * CentroNotificaciones
 *
 * Genera las notificaciones del sistema en formato de oraciones planas,
 * listas para mostrarse en el ícono de campana (centro de notificaciones).
 *
 * Fuentes activas:
 *   - Cartera de clientes (facturav)     -> vencida / próxima a vencer
 *   - Cartera de proveedores (facturac)  -> vencida / próxima a vencer
 *   - Inventario (productoinventarios)   -> agotado / stock bajo
 *
 * Cada notificación tiene una 'key' estable (no cambia aunque pasen los
 * días) que permite marcarla como leída y que no vuelva a aparecer, aunque
 * la consulta SQL la siga generando.
 * Requiere la tabla notificaciones_leidas.
 */
class CentroNotificaciones {

    private $pdo;
    private $diasUmbralProximo;
    private $umbralStock;

    /**
     * @param PDO $pdo Conexión activa a la base de datos
     * @param int $diasUmbralProximo Días antes del vencimiento para "próxima a vencer"
     * @param int $umbralStock Cantidad en la que se considera "stock bajo"
     */
    public function __construct($pdo, $diasUmbralProximo = 5, $umbralStock = 5) {
        $this->pdo = $pdo;
        $this->diasUmbralProximo = $diasUmbralProximo;
        $this->umbralStock = $umbralStock;
    }

    // =================================================================
    // FUENTE 1: CARTERA DE CLIENTES
    // =================================================================
    public function obtenerNotificacionesCartera() {
        $notificaciones = [];

        $sql = "
            SELECT
                f.identificacion,
                f.nombre,
                f.consecutivo AS documento,
                f.numero_factura,
                f.fecha_vencimiento,
                CASE
                    WHEN f.saldoReal IS NULL OR f.saldoReal = '' THEN f.valorTotal
                    ELSE f.saldoReal
                END AS saldo_pendiente
            FROM facturav f
            WHERE (f.saldoReal > 0 OR f.saldoReal IS NULL)
            AND f.formaPago LIKE '%Credito%'
            AND f.fecha_vencimiento IS NOT NULL
            AND f.fecha_vencimiento != '0000-00-00'
            AND (CASE
                    WHEN f.saldoReal IS NULL OR f.saldoReal = '' THEN f.valorTotal
                    ELSE f.saldoReal
                END) > 0
        ";

        $stmt = $this->pdo->query($sql);
        $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($facturas as $factura) {
            $saldo = floatval($factura['saldo_pendiente']);
            if ($saldo <= 0) {
                continue;
            }

            $diasParaVencer = $this->calcularDiasParaVencer($factura['fecha_vencimiento']);
            $documento = !empty($factura['numero_factura']) ? $factura['numero_factura'] : $factura['documento'];
            $linkCliente = "views/informes/edadesdecarteraclientes.php?identificacion=" . urlencode($factura['identificacion']);

            // La key NO incluye los días ni el monto: así, aunque pase el
            // tiempo y la mora aumente, sigue siendo "la misma" notificación
            // y si el usuario ya la marcó leída, no vuelve a aparecer.
            $key = $this->generarKey('cliente', $factura['identificacion'] . '|' . $documento);

            if ($diasParaVencer < 0) {
                $diasVencida = abs($diasParaVencer);
                $severidad = $diasVencida > 60 ? 'alta' : ($diasVencida > 30 ? 'media' : 'baja');

                $notificaciones[] = [
                    'key'       => $key,
                    'tipo'      => 'cartera_vencida',
                    'severidad' => $severidad,
                    'mensaje'   => "{$factura['nombre']} tiene la factura {$documento} vencida hace {$diasVencida} día" . ($diasVencida == 1 ? '' : 's'),
                    'monto'     => $saldo,
                    'dias'      => $diasVencida,
                    'link'      => $linkCliente
                ];
            } elseif ($diasParaVencer <= $this->diasUmbralProximo) {
                $texto = $diasParaVencer == 0
                    ? "vence hoy"
                    : "vence en {$diasParaVencer} día" . ($diasParaVencer == 1 ? '' : 's');

                $notificaciones[] = [
                    'key'       => $key,
                    'tipo'      => 'cartera_proxima',
                    'severidad' => 'baja',
                    'mensaje'   => "{$factura['nombre']} tiene la factura {$documento} próxima a vencerse ({$texto})",
                    'monto'     => $saldo,
                    'dias'      => $diasParaVencer,
                    'link'      => $linkCliente
                ];
            }
        }

        return $notificaciones;
    }

    // =================================================================
    // FUENTE 2: CARTERA DE PROVEEDORES (mismo patrón que clientes)
    // =================================================================
    public function obtenerNotificacionesCarteraProveedores() {
        $notificaciones = [];

        $sql = "
            SELECT
                f.identificacion,
                f.nombre,
                f.consecutivo AS documento,
                f.numeroFactura AS numero_factura,
                f.fecha_vencimiento,
                CASE
                    WHEN f.saldoReal IS NULL OR f.saldoReal = '' THEN f.valorTotal
                    ELSE f.saldoReal
                END AS saldo_pendiente
            FROM facturac f
            WHERE (f.saldoReal > 0 OR f.saldoReal IS NULL)
            AND f.formaPago LIKE '%Credito%'
            AND f.fecha_vencimiento IS NOT NULL
            AND f.fecha_vencimiento != '0000-00-00'
            AND (CASE
                    WHEN f.saldoReal IS NULL OR f.saldoReal = '' THEN f.valorTotal
                    ELSE f.saldoReal
                END) > 0
        ";

        $stmt = $this->pdo->query($sql);
        $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($facturas as $factura) {
            $saldo = floatval($factura['saldo_pendiente']);
            if ($saldo <= 0) {
                continue;
            }

            $diasParaVencer = $this->calcularDiasParaVencer($factura['fecha_vencimiento']);
            $documento = !empty($factura['numero_factura']) ? $factura['numero_factura'] : $factura['documento'];
            $linkProveedor = "views/informes/edadesdecarteraproveedores.php?identificacion=" . urlencode($factura['identificacion']);

            $key = $this->generarKey('proveedor', $factura['identificacion'] . '|' . $documento);

            if ($diasParaVencer < 0) {
                $diasVencida = abs($diasParaVencer);
                $severidad = $diasVencida > 60 ? 'alta' : ($diasVencida > 30 ? 'media' : 'baja');

                $notificaciones[] = [
                    'key'       => $key,
                    'tipo'      => 'proveedor_vencida',
                    'severidad' => $severidad,
                    'mensaje'   => "Le debes a {$factura['nombre']} la factura {$documento}, vencida hace {$diasVencida} día" . ($diasVencida == 1 ? '' : 's'),
                    'monto'     => $saldo,
                    'dias'      => $diasVencida,
                    'link'      => $linkProveedor
                ];
            } elseif ($diasParaVencer <= $this->diasUmbralProximo) {
                $texto = $diasParaVencer == 0
                    ? "vence hoy"
                    : "vence en {$diasParaVencer} día" . ($diasParaVencer == 1 ? '' : 's');

                $notificaciones[] = [
                    'key'       => $key,
                    'tipo'      => 'proveedor_proxima',
                    'severidad' => 'baja',
                    'mensaje'   => "Le debes a {$factura['nombre']} la factura {$documento}, próxima a vencerse ({$texto})",
                    'monto'     => $saldo,
                    'dias'      => $diasParaVencer,
                    'link'      => $linkProveedor
                ];
            }
        }

        return $notificaciones;
    }

    // =================================================================
    // FUENTE 3: INVENTARIO (stock agotado / bajo)
    // =================================================================
    public function obtenerNotificacionesInventario() {
        $notificaciones = [];

        $sql = "
            SELECT codigoProducto, descripcionProducto, cantidad
            FROM productoinventarios
            WHERE cantidad <= :umbral
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':umbral' => $this->umbralStock]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productos as $p) {
            $cantidad = (int) $p['cantidad'];
            $key = $this->generarKey('inventario', $p['codigoProducto']);
            // RUTA CORREGIDA: views/informes/existencias.php (confirmada por el usuario)
            $link = "views/informes/existencias.php?codigo=" . urlencode($p['codigoProducto']);

            if ($cantidad <= 0) {
                $notificaciones[] = [
                    'key'       => $key,
                    'tipo'      => 'inventario_agotado',
                    'severidad' => 'alta',
                    'mensaje'   => "El producto \"{$p['descripcionProducto']}\" está agotado (0 unidades)",
                    'monto'     => 0,
                    'dias'      => 0,
                    'link'      => $link
                ];
            } else {
                $notificaciones[] = [
                    'key'       => $key,
                    'tipo'      => 'inventario_bajo',
                    'severidad' => 'baja',
                    'mensaje'   => "El producto \"{$p['descripcionProducto']}\" tiene stock bajo ({$cantidad} unidad" . ($cantidad == 1 ? '' : 'es') . ")",
                    'monto'     => 0,
                    'dias'      => 0,
                    'link'      => $link
                ];
            }
        }

        return $notificaciones;
    }

    /**
     * Reúne TODAS las notificaciones del sistema, quita las ya leídas,
     * y ordena por urgencia: primero vencidas (de mayor a menor mora),
     * luego próximas a vencer/stock bajo.
     */
    public function obtenerTodas() {
        $todas = [];
        $todas = array_merge($todas, $this->obtenerNotificacionesCartera());
        $todas = array_merge($todas, $this->obtenerNotificacionesCarteraProveedores());
        $todas = array_merge($todas, $this->obtenerNotificacionesInventario());

        $todas = $this->filtrarLeidas($todas);

        usort($todas, function ($a, $b) {
            $aVencida = strpos($a['tipo'], 'vencid') !== false || $a['tipo'] === 'inventario_agotado';
            $bVencida = strpos($b['tipo'], 'vencid') !== false || $b['tipo'] === 'inventario_agotado';

            if ($aVencida && !$bVencida) return -1;
            if (!$aVencida && $bVencida) return 1;

            if ($aVencida) {
                return $b['dias'] <=> $a['dias']; // más días vencidos primero
            }
            return $a['dias'] <=> $b['dias']; // más próxima a vencer primero
        });

        return $todas;
    }

    // =================================================================
    // LEÍDAS
    // =================================================================

    /**
     * Quita del arreglo las notificaciones cuya key ya está marcada como leída.
     */
    private function filtrarLeidas($notificaciones) {
        if (empty($notificaciones)) {
            return $notificaciones;
        }

        $keys = array_column($notificaciones, 'key');
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $stmt = $this->pdo->prepare("SELECT notif_key FROM notificaciones_leidas WHERE notif_key IN ($placeholders)");
        $stmt->execute($keys);
        $leidas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $leidasSet = array_flip($leidas);

        return array_values(array_filter($notificaciones, function ($n) use ($leidasSet) {
            return !isset($leidasSet[$n['key']]);
        }));
    }

    /**
     * Marca UNA notificación como leída (se deja de mostrar hasta que
     * la situación cambie de tal forma que genere una key distinta,
     * por ejemplo: la factura se paga y luego se genera otra nueva).
     */
    public function marcarComoLeida($key) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notificaciones_leidas (notif_key) VALUES (:key)
            ON DUPLICATE KEY UPDATE fecha_leida = NOW()
        ");
        return $stmt->execute([':key' => $key]);
    }

    /**
     * Marca TODAS las notificaciones actualmente visibles como leídas.
     * Útil para un botón "Marcar todas como leídas".
     */
    public function marcarTodasComoLeidas() {
        $todas = [];
        $todas = array_merge($todas, $this->obtenerNotificacionesCartera());
        $todas = array_merge($todas, $this->obtenerNotificacionesCarteraProveedores());
        $todas = array_merge($todas, $this->obtenerNotificacionesInventario());

        foreach ($todas as $n) {
            $this->marcarComoLeida($n['key']);
        }

        return count($todas);
    }

    /**
     * Genera una key estable de 32 caracteres para una notificación.
     * OJO: solo debe incluir datos que identifiquen la ocurrencia
     * (ej. identificación + número de factura, o código de producto),
     * nunca datos que cambian con el tiempo (días, monto), o la
     * notificación "leída" dejaría de coincidir al día siguiente.
     */
    private function generarKey($tipo, $identificador) {
        return md5($tipo . '|' . $identificador);
    }

    /**
     * Calcula días hasta el vencimiento.
     * Positivo = faltan X días. Negativo = ya vencida hace X días. 0 = vence hoy.
     */
    private function calcularDiasParaVencer($fechaVencimiento) {
        $hoy = new DateTime(date('Y-m-d'));
        $vencimiento = new DateTime($fechaVencimiento);
        $diferencia = $hoy->diff($vencimiento);
        $dias = (int) $diferencia->days;

        return $diferencia->invert === 1 ? -$dias : $dias;
    }
}