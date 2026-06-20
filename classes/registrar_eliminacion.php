<?php
/**
 * FUNCIÓN PARA REGISTRAR ELIMINACIÓN DE DOCUMENTOS
 * Base de datos: sofi
 * 
 * Esta función registra automáticamente cuando un usuario elimina un documento
 * del sistema contable.
 */

/**
 * Registra la eliminación de un documento en la tabla documentos_eliminados
 * 
 * @param PDO $conn - Conexión a la base de datos
 * @param int|null $id_usuario - ID del usuario que elimina (puede ser null)
 * @param string $nombre_usuario - Nombre del usuario que elimina
 * @param string $tipo_documento - Tipo de documento (Factura, Recibo, Comprobante, etc.)
 * @param int $id_documento - ID del documento eliminado
 * @param string|null $numero_documento - Número del documento
 * @param string|null $nombre_documento - Nombre descriptivo del documento
 * @param string|null $tercero - Cliente/Proveedor relacionado
 * @param float|null $total - Valor total del documento
 * @param string|null $fecha_documento - Fecha original del documento
 * @param string|null $detalles - Información adicional
 * @param array|null $datos_json - Datos completos en formato JSON (opcional)
 * @return bool - True si se registró correctamente, False en caso contrario
 */
function registrarEliminacion(
    $conn, 
    $id_usuario = null,
    $nombre_usuario = '', 
    $tipo_documento = '', 
    $id_documento = 0, 
    $numero_documento = null,
    $nombre_documento = null, 
    $tercero = null,
    $total = null,
    $fecha_documento = null,
    $detalles = null,
    $datos_json = null
) {
    try {
        date_default_timezone_set('America/Bogota');
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        
        // Convertir array a JSON si es necesario
        if (is_array($datos_json)) {
            $datos_json = json_encode($datos_json, JSON_UNESCAPED_UNICODE);
        }
        
        $sql = "INSERT INTO documentos_eliminados 
                (id_usuario, nombre_usuario, tipo_documento, id_documento, numero_documento, 
                 nombre_documento, tercero, total, fecha_documento, fecha_eliminacion, 
                 hora_eliminacion, detalles, datos_json) 
                VALUES 
                (:id_usuario, :nombre_usuario, :tipo_documento, :id_documento, :numero_documento,
                 :nombre_documento, :tercero, :total, :fecha_documento, :fecha, :hora, :detalles, :datos_json)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->bindParam(':nombre_usuario', $nombre_usuario);
        $stmt->bindParam(':tipo_documento', $tipo_documento);
        $stmt->bindParam(':id_documento', $id_documento);
        $stmt->bindParam(':numero_documento', $numero_documento);
        $stmt->bindParam(':nombre_documento', $nombre_documento);
        $stmt->bindParam(':tercero', $tercero);
        $stmt->bindParam(':total', $total);
        $stmt->bindParam(':fecha_documento', $fecha_documento);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':detalles', $detalles);
        $stmt->bindParam(':datos_json', $datos_json);
        
        return $stmt->execute();
        
    } catch(PDOException $e) {
        error_log("Error al registrar eliminación: " . $e->getMessage());
        return false;
    }
}

/**
 * Función helper para obtener nombre de usuario desde la sesión
 */
function obtenerUsuarioActual() {
    if (isset($_SESSION['nombre']) && isset($_SESSION['apellidos'])) {
        return $_SESSION['nombre'] . ' ' . $_SESSION['apellidos'];
    } elseif (isset($_SESSION['usuario'])) {
        return $_SESSION['usuario'];
    }
    return 'Usuario Desconocido';
}

/**
 * Función helper para obtener ID de usuario desde la sesión
 */
function obtenerIdUsuarioActual() {
    return isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;
}

// ==========================================
// EJEMPLOS DE USO PARA CADA TIPO DE DOCUMENTO
// ==========================================

/*
// ------------------------------------------
// EJEMPLO 1: ELIMINAR FACTURA DE VENTA (facturav)
// ------------------------------------------

// Incluir este archivo
require_once 'registrar_eliminacion.php';

// Antes de eliminar, obtener información de la factura
$id_factura = $_POST['id_factura']; // o $_GET['id']

$sql = "SELECT f.*, t.nombres, t.apellidos, t.razonSocial 
        FROM facturav f 
        LEFT JOIN catalogosterceros t ON f.id_tercero = t.id 
        WHERE f.id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id_factura);
$stmt->execute();
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if ($factura) {
    // Preparar el nombre del tercero
    $tercero = !empty($factura['razonSocial']) 
        ? $factura['razonSocial'] 
        : $factura['nombres'] . ' ' . $factura['apellidos'];
    
    // Eliminar la factura
    $sql_delete = "DELETE FROM facturav WHERE id = :id";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bindParam(':id', $id_factura);
    
    if ($stmt_delete->execute()) {
        // Registrar la eliminación
        registrarEliminacion(
            $conn,
            obtenerIdUsuarioActual(),
            obtenerUsuarioActual(),
            'Factura de Venta',
            $id_factura,
            $factura['numeroFactura'],
            'Factura #' . $factura['numeroFactura'],
            $tercero,
            $factura['total'],
            $factura['fecha'],
            'Subtotal: $' . $factura['subtotal'] . ', IVA: $' . $factura['iva'],
            $factura // Guardar todos los datos como JSON
        );
        
        echo json_encode(['success' => true, 'message' => 'Factura eliminada correctamente']);
    }
}

// ------------------------------------------
// EJEMPLO 2: ELIMINAR FACTURA DE COMPRA (facturac)
// ------------------------------------------

$id_factura_compra = $_POST['id'];

$sql = "SELECT f.*, t.nombres, t.apellidos, t.razonSocial 
        FROM facturac f 
        LEFT JOIN catalogosterceros t ON f.id_tercero = t.id 
        WHERE f.id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id_factura_compra);
$stmt->execute();
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

if ($factura) {
    $proveedor = !empty($factura['razonSocial']) 
        ? $factura['razonSocial'] 
        : $factura['nombres'] . ' ' . $factura['apellidos'];
    
    $sql_delete = "DELETE FROM facturac WHERE id = :id";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bindParam(':id', $id_factura_compra);
    
    if ($stmt_delete->execute()) {
        registrarEliminacion(
            $conn,
            obtenerIdUsuarioActual(),
            obtenerUsuarioActual(),
            'Factura de Compra',
            $id_factura_compra,
            $factura['numeroFactura'],
            'Factura Compra #' . $factura['numeroFactura'],
            $proveedor,
            $factura['total'],
            $factura['fecha'],
            'Compra a proveedor'
        );
        
        echo json_encode(['success' => true, 'message' => 'Factura de compra eliminada']);
    }
}

// ------------------------------------------
// EJEMPLO 3: ELIMINAR RECIBO DE CAJA (docrecibodecaja)
// ------------------------------------------

$id_recibo = $_POST['id'];

$sql = "SELECT r.*, t.nombres, t.apellidos, t.razonSocial 
        FROM docrecibodecaja r 
        LEFT JOIN catalogosterceros t ON r.id_tercero = t.id 
        WHERE r.id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id_recibo);
$stmt->execute();
$recibo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($recibo) {
    $tercero = !empty($recibo['razonSocial']) 
        ? $recibo['razonSocial'] 
        : $recibo['nombres'] . ' ' . $recibo['apellidos'];
    
    $sql_delete = "DELETE FROM docrecibodecaja WHERE id = :id";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bindParam(':id', $id_recibo);
    
    if ($stmt_delete->execute()) {
        registrarEliminacion(
            $conn,
            obtenerIdUsuarioActual(),
            obtenerUsuarioActual(),
            'Recibo de Caja',
            $id_recibo,
            $recibo['numeroRecibo'],
            'Recibo #' . $recibo['numeroRecibo'],
            $tercero,
            $recibo['total'],
            $recibo['fecha'],
            'Concepto: ' . $recibo['concepto']
        );
        
        echo json_encode(['success' => true, 'message' => 'Recibo de caja eliminado']);
    }
}

// ------------------------------------------
// EJEMPLO 4: ELIMINAR COMPROBANTE CONTABLE (doccomprobantecontable)
// ------------------------------------------

$id_comprobante = $_POST['id'];

$sql = "SELECT * FROM doccomprobantecontable WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id_comprobante);
$stmt->execute();
$comprobante = $stmt->fetch(PDO::FETCH_ASSOC);

if ($comprobante) {
    $sql_delete = "DELETE FROM doccomprobantecontable WHERE id = :id";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bindParam(':id', $id_comprobante);
    
    if ($stmt_delete->execute()) {
        registrarEliminacion(
            $conn,
            obtenerIdUsuarioActual(),
            obtenerUsuarioActual(),
            'Comprobante Contable',
            $id_comprobante,
            $comprobante['numeroComprobante'],
            'Comprobante #' . $comprobante['numeroComprobante'],
            null, // Sin tercero específico
            $comprobante['total'],
            $comprobante['fecha'],
            'Concepto: ' . $comprobante['concepto']
        );
        
        echo json_encode(['success' => true, 'message' => 'Comprobante contable eliminado']);
    }
}

// ------------------------------------------
// EJEMPLO 5: ELIMINAR COMPROBANTE DE EGRESO (doccomprobanteegreso)
// ------------------------------------------

$id_egreso = $_POST['id'];

$sql = "SELECT e.*, t.nombres, t.apellidos, t.razonSocial 
        FROM doccomprobanteegreso e 
        LEFT JOIN catalogosterceros t ON e.id_tercero = t.id 
        WHERE e.id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id_egreso);
$stmt->execute();
$egreso = $stmt->fetch(PDO::FETCH_ASSOC);

if ($egreso) {
    $tercero = !empty($egreso['razonSocial']) 
        ? $egreso['razonSocial'] 
        : $egreso['nombres'] . ' ' . $egreso['apellidos'];
    
    $sql_delete = "DELETE FROM doccomprobanteegreso WHERE id = :id";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bindParam(':id', $id_egreso);
    
    if ($stmt_delete->execute()) {
        registrarEliminacion(
            $conn,
            obtenerIdUsuarioActual(),
            obtenerUsuarioActual(),
            'Comprobante de Egreso',
            $id_egreso,
            $egreso['numeroComprobante'],
            'Egreso #' . $egreso['numeroComprobante'],
            $tercero,
            $egreso['total'],
            $egreso['fecha'],
            'Concepto: ' . $egreso['concepto']
        );
        
        echo json_encode(['success' => true, 'message' => 'Comprobante de egreso eliminado']);
    }
}

// ------------------------------------------
// EJEMPLO 6: ELIMINAR TERCERO (catalogosterceros)
// ------------------------------------------

$id_tercero = $_POST['id'];

$sql = "SELECT * FROM catalogosterceros WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id_tercero);
$stmt->execute();
$tercero_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tercero_data) {
    $nombre_tercero = !empty($tercero_data['razonSocial']) 
        ? $tercero_data['razonSocial'] 
        : $tercero_data['nombres'] . ' ' . $tercero_data['apellidos'];
    
    $sql_delete = "DELETE FROM catalogosterceros WHERE id = :id";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bindParam(':id', $id_tercero);
    
    if ($stmt_delete->execute()) {
        registrarEliminacion(
            $conn,
            obtenerIdUsuarioActual(),
            obtenerUsuarioActual(),
            'Tercero',
            $id_tercero,
            $tercero_data['cedula'],
            $nombre_tercero,
            null,
            null,
            null,
            'Tipo: ' . $tercero_data['tipoTercero'] . ', CC/NIT: ' . $tercero_data['cedula']
        );
        
        echo json_encode(['success' => true, 'message' => 'Tercero eliminado']);
    }
}

// ------------------------------------------
// EJEMPLO 7: ELIMINAR PRODUCTO INVENTARIO (productoinventarios)
// ------------------------------------------

$id_producto = $_POST['id'];

$sql = "SELECT * FROM productoinventarios WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id_producto);
$stmt->execute();
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if ($producto) {
    $sql_delete = "DELETE FROM productoinventarios WHERE id = :id";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bindParam(':id', $id_producto);
    
    if ($stmt_delete->execute()) {
        registrarEliminacion(
            $conn,
            obtenerIdUsuarioActual(),
            obtenerUsuarioActual(),
            'Producto Inventario',
            $id_producto,
            $producto['codigo'],
            $producto['descripcion'],
            null,
            $producto['precio'],
            null,
            'Cantidad: ' . $producto['cantidad'] . ', Categoría: ' . $producto['categoria']
        );
        
        echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
    }
}

// ------------------------------------------
// EJEMPLO 8: ELIMINAR CUENTA CONTABLE (catalogoscuentascontables)
// ------------------------------------------

$id_cuenta = $_POST['id'];

$sql = "SELECT * FROM catalogoscuentascontables WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id_cuenta);
$stmt->execute();
$cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cuenta) {
    $sql_delete = "DELETE FROM catalogoscuentascontables WHERE id = :id";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bindParam(':id', $id_cuenta);
    
    if ($stmt_delete->execute()) {
        $nombre_cuenta = $cuenta['clase'] . ' - ' . $cuenta['auxiliar'];
        
        registrarEliminacion(
            $conn,
            obtenerIdUsuarioActual(),
            obtenerUsuarioActual(),
            'Cuenta Contable',
            $id_cuenta,
            $cuenta['auxiliar'],
            $nombre_cuenta,
            null,
            null,
            null,
            'Naturaleza: ' . $cuenta['naturalezaContable']
        );
        
        echo json_encode(['success' => true, 'message' => 'Cuenta contable eliminada']);
    }
}
*/
?>