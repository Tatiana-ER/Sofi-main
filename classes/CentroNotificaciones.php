<?php

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
            $idBase = $factura['identificacion'] . '|' . $documento;

            if ($diasParaVencer < 0) {
                $diasVencida = abs($diasParaVencer);
                $severidad = $diasVencida > 60 ? 'alta' : ($diasVencida > 30 ? 'media' : 'baja');
                $key = $this->generarKey('cliente_vencida', $idBase . '|' . $severidad);

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

                $key = $this->generarKey('cliente_proxima', $idBase);

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
            $idBase = $factura['identificacion'] . '|' . $documento;

            if ($diasParaVencer < 0) {
                $diasVencida = abs($diasParaVencer);
                $severidad = $diasVencida > 60 ? 'alta' : ($diasVencida > 30 ? 'media' : 'baja');
                $key = $this->generarKey('proveedor_vencida', $idBase . '|' . $severidad);

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

                $key = $this->generarKey('proveedor_proxima', $idBase);

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
            $link = "views/informes/existencias.php?codigo=" . urlencode($p['codigoProducto']);

            if ($cantidad <= 0) {
                $severidad = 'alta';
                $tipo = 'inventario_agotado';
                $mensaje = "El producto \"{$p['descripcionProducto']}\" está agotado (0 unidades)";
            } else {
                $severidad = 'baja';
                $tipo = 'inventario_bajo';
                $mensaje = "El producto \"{$p['descripcionProducto']}\" tiene stock bajo ({$cantidad} unidad" . ($cantidad == 1 ? '' : 'es') . ")";
            }

            $key = $this->generarKey('inventario', $p['codigoProducto'] . '|' . $severidad);

            $notificaciones[] = [
                'key'       => $key,
                'tipo'      => $tipo,
                'severidad' => $severidad,
                'mensaje'   => $mensaje,
                'monto'     => 0,
                'dias'      => 0,
                'link'      => $link
            ];
        }

        return $notificaciones;
    }

    /**
     * Reúne TODAS las notificaciones activas del sistema (sin ocultar las
     * leídas), marca cuáles ya fueron leídas, y ordena: primero las NO
     * leídas (de más urgente a menos urgente), luego las leídas (en el
     * mismo orden de urgencia).
     */
    public function obtenerTodas() {
        $todas = [];
        $todas = array_merge($todas, $this->obtenerNotificacionesCartera());
        $todas = array_merge($todas, $this->obtenerNotificacionesCarteraProveedores());
        $todas = array_merge($todas, $this->obtenerNotificacionesInventario());

        $todas = $this->marcarLeidas($todas);

        usort($todas, function ($a, $b) {
            // No leídas primero
            if ($a['leida'] !== $b['leida']) {
                return $a['leida'] ? 1 : -1;
            }

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

    /**
     * Cuenta cuántas de las notificaciones activas todavía NO se han leído.
     * Útil para el badge de la campana.
     */
    public function contarNoLeidas($notificaciones = null) {
        if ($notificaciones === null) {
            $notificaciones = $this->obtenerTodas();
        }
        return count(array_filter($notificaciones, function ($n) {
            return !$n['leida'];
        }));
    }

    // =================================================================
    // LEÍDAS
    // =================================================================

    /**
     * Anota cada notificación con 'leida' => true/false, SIN quitarla de
     * la lista. Las leídas se siguen mostrando (atenuadas en el frontend)
     * mientras la situación exista.
     */
    private function marcarLeidas($notificaciones) {
        if (empty($notificaciones)) {
            return $notificaciones;
        }

        $keys = array_column($notificaciones, 'key');
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $stmt = $this->pdo->prepare("SELECT notif_key FROM notificaciones_leidas WHERE notif_key IN ($placeholders)");
        $stmt->execute($keys);
        $leidas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $leidasSet = array_flip($leidas);

        foreach ($notificaciones as &$n) {
            $n['leida'] = isset($leidasSet[$n['key']]);
        }
        unset($n);

        return $notificaciones;
    }

    /**
     * Marca UNA notificación como leída. Sigue apareciendo en el panel
     * (atenuada) hasta que la situación se resuelva por sí sola.
     */
    public function marcarComoLeida($key) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notificaciones_leidas (notif_key) VALUES (:key)
            ON DUPLICATE KEY UPDATE fecha_leida = NOW()
        ");
        return $stmt->execute([':key' => $key]);
    }

    /**
     * Marca TODAS las notificaciones actualmente activas como leídas.
     * Siguen apareciendo en el panel (atenuadas); el badge queda en 0.
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
     * Incluye SIEMPRE la severidad (ver nota al inicio de la clase).
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