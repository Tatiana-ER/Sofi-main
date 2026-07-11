<?php
require_once '../../config/database.php';
include('../../classes/LibroDiario.php');


$pdo = Database::getConnection();
$libroDiario = new LibroDiario($pdo);

// Obtener el siguiente consecutivo 
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(CAST(consecutivo AS UNSIGNED)) AS ultimo FROM facturav");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoConsecutivo = $row['ultimo'] ?? 0;
    $nuevoConsecutivo = $ultimoConsecutivo + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}

$txtId=(isset($_POST['txtId']))?$_POST['txtId']:"";
$identificacion=(isset($_POST['identificacion']))?$_POST['identificacion']:"";
$nombre=(isset($_POST['nombre']))?$_POST['nombre']:"";
$fecha=(isset($_POST['fecha']))?$_POST['fecha']:"";
$consecutivo=(isset($_POST['consecutivo']))?$_POST['consecutivo']:"";
$formaPago=(isset($_POST['formaPago']))?$_POST['formaPago']:"";
$subtotal=(isset($_POST['subtotal']))?$_POST['subtotal']:"";
$ivaTotal=(isset($_POST['ivaTotal']))?$_POST['ivaTotal']:"";
$retenciones=(isset($_POST['retenciones']))?$_POST['retenciones']:"";
$valorTotal=(isset($_POST['valorTotal']))?$_POST['valorTotal']:"";
$observaciones=(isset($_POST['observaciones']))?$_POST['observaciones']:"";
$selectRetencion=(isset($_POST['selectRetencion']))?$_POST['selectRetencion']:"";
$numeroFactura=(isset($_POST['numeroFactura']))?$_POST['numeroFactura']:"";
$fechaVencimiento=(isset($_POST['fechaVencimiento']))?$_POST['fechaVencimiento']:"";

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

// Procesar multiples medios de pago
$mediosPagoArray = [];
if (isset($_POST['metodosPago']) && is_array($_POST['metodosPago'])) {
    foreach ($_POST['metodosPago'] as $index => $metodoData) {
        if (!empty($metodoData['metodo']) && !empty($metodoData['valor'])) {
            $mediosPagoArray[] = [
                'metodo' => $metodoData['metodo'],
                'cuenta' => explode(' - ', $metodoData['metodo'])[1] ?? '',
                'valor' => floatval($metodoData['valor'])
            ];
        }
    }
}

// Variable por defecto para el formulario (se llena en btnEditar)
$mediosPagoFactura = [];

switch($accion){
  case "btnAgregar":
    try {
        $pdo->beginTransaction();

        if (isset($_POST['detalles'])) {
            $decoded = json_decode($_POST['detalles'], true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded) || empty($decoded)) {
                throw new Exception("No se pudieron procesar los detalles de la factura. Intenta nuevamente.");
            }
            $_POST['detalles'] = $decoded;
        } else {
            throw new Exception("No se recibieron los detalles de la factura.");
        }

        // Validar suma de medios de pago
        $sumaMediosPago = array_sum(array_column($mediosPagoArray, 'valor'));
        $diferencia = abs($sumaMediosPago - floatval($valorTotal));

        if ($diferencia > 0.01) {
            $mensajeError = "La suma de los medios de pago (".number_format($sumaMediosPago, 2).") ";
            $mensajeError .= "no coincide con el valor total (".number_format($valorTotal, 2)."). ";

            if ($sumaMediosPago < floatval($valorTotal)) {
                $mensajeError .= "Faltan ".number_format(floatval($valorTotal) - $sumaMediosPago, 2);
            } else {
                $mensajeError .= "Sobran ".number_format($sumaMediosPago - floatval($valorTotal), 2);
            }

            throw new Exception($mensajeError);
        }

        $formaPago = implode(', ', array_column($mediosPagoArray, 'metodo'));

        // Insertar factura (MODIFICADO: Se agregaron numero_factura y fecha_vencimiento)
        $sentencia=$pdo->prepare("INSERT INTO facturav(identificacion,nombre,fecha,consecutivo,numero_factura,formaPago,fecha_vencimiento,subtotal,ivaTotal,retenciones,valorTotal,observaciones,retencion_tarifa) 
        VALUES (:identificacion,:nombre,:fecha,:consecutivo,:numero_factura,:formaPago,:fecha_vencimiento,:subtotal,:ivaTotal,:retenciones,:valorTotal,:observaciones,:retencion_tarifa)");
        
        $sentencia->bindParam(':identificacion',$identificacion);
        $sentencia->bindParam(':nombre',$nombre);
        $sentencia->bindParam(':fecha',$fecha);
        $sentencia->bindParam(':consecutivo',$consecutivo);
        $sentencia->bindParam(':numero_factura',$numeroFactura);
        $sentencia->bindParam(':formaPago',$formaPago);
        $sentencia->bindParam(':fecha_vencimiento',$fechaVencimiento);
        $sentencia->bindParam(':subtotal',$subtotal);
        $sentencia->bindParam(':ivaTotal',$ivaTotal);
        $sentencia->bindParam(':retenciones',$retenciones);
        $sentencia->bindParam(':valorTotal',$valorTotal);
        $sentencia->bindParam(':observaciones',$observaciones);
        $sentencia->bindParam(':retencion_tarifa',$selectRetencion);
        $sentencia->execute();

        $idFactura = $pdo->lastInsertId();

        // Insertar detalles - El trigger maneja la actualización de inventario
        if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
          $sqlDetalle = "INSERT INTO factura_detalle 
                        (id_factura, codigoProducto, nombreProducto, cantidad, precio_unitario, iva, total)
                        VALUES (:id_factura, :codigoProducto, :nombreProducto, :cantidad, :precio_unitario, :iva, :total)";
          $stmtDetalle = $pdo->prepare($sqlDetalle);

          $checkItem = $pdo->prepare("SELECT tipoItem, cantidad FROM productoinventarios WHERE codigoProducto = :codigo");

          foreach ($_POST['detalles'] as $detalle) {
              $checkItem->execute([':codigo' => $detalle['codigoProducto']]);
              $item = $checkItem->fetch(PDO::FETCH_ASSOC);

              if (!$item) {
                  throw new Exception("El código {$detalle['codigoProducto']} no existe en el inventario.");
              }

              // Validar stock disponible (pero NO actualizar - el trigger lo hace)
              if (strtolower($item['tipoItem']) === 'producto') {
                  if ($item['cantidad'] < $detalle['cantidad']) {
                      throw new Exception("Stock insuficiente para {$detalle['nombreProducto']}. Disponible: {$item['cantidad']}, Solicitado: {$detalle['cantidad']}");
                  }
                  // NOTA: La cantidad se actualiza mediante un trigger en la base de datos
              }

              $stmtDetalle->execute([
                  ':id_factura' => $idFactura,
                  ':codigoProducto' => $detalle['codigoProducto'],
                  ':nombreProducto' => $detalle['nombreProducto'],
                  ':cantidad' => $detalle['cantidad'],
                  ':precio_unitario' => $detalle['precio'],
                  ':iva' => $detalle['iva'],
                  ':total' => $detalle['precioTotal']
              ]);
          }
      }

      // Insertar multiples medios de pago
      if (!empty($mediosPagoArray)) {
          $sqlMedioPago = "INSERT INTO medios_pago_factura 
                          (factura_id, tipo_factura, forma_pago, cuenta_contable, nombre_cuenta, valor) 
                          VALUES (:factura_id, 'venta', :forma_pago, :cuenta_contable, :nombre_cuenta, :valor)";
          $stmtMedioPago = $pdo->prepare($sqlMedioPago);

          foreach ($mediosPagoArray as $medio) {
              $partes = explode(' - ', $medio['metodo']);
              $forma_pago_nombre = $partes[0] ?? $medio['metodo'];
              $cuenta_contable = $partes[1] ?? '';
              $nombre_cuenta = $partes[1] ?? '';

              $stmtMedioPago->execute([
                  ':factura_id' => $idFactura,
                  ':forma_pago' => $forma_pago_nombre,
                  ':cuenta_contable' => $cuenta_contable,
                  ':nombre_cuenta' => $nombre_cuenta,
                  ':valor' => $medio['valor']
              ]);
          }
      }
        // Registrar en Libro Diario (con retenciones)
        $libroDiario->registrarFacturaVenta($idFactura);

        $pdo->commit();
        header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ".$_SERVER['PHP_SELF']."?msg=error&detalle=".urlencode($e->getMessage()));
        exit;
    }
break;

  case "btnModificar":
    try {
        $pdo->beginTransaction();

        if (isset($_POST['detalles'])) {
            $decoded = json_decode($_POST['detalles'], true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded) || empty($decoded)) {
                throw new Exception("No se pudieron procesar los detalles de la factura. Intenta nuevamente.");
            }
            $_POST['detalles'] = $decoded;
        } else {
            throw new Exception("No se recibieron los detalles de la factura.");
        }

        // Validar suma de medios de pago
        $sumaMediosPago = array_sum(array_column($mediosPagoArray, 'valor'));
        $diferencia = abs($sumaMediosPago - floatval($valorTotal));

        if ($diferencia > 0.01) {
            throw new Exception("La suma de los medios de pago (".number_format($sumaMediosPago, 2).") no coincide con el valor total (".number_format($valorTotal, 2)."). Faltan ".number_format(floatval($valorTotal) - $sumaMediosPago, 2));
        }

        // Eliminar asientos contables antiguos
        $libroDiario->eliminarMovimientos('factura_venta', $txtId);

        // Obtener detalles antiguos para restaurar inventario
        $stmtOldDetails = $pdo->prepare("
            SELECT fd.codigoProducto, fd.cantidad, pi.tipoItem 
            FROM factura_detalle fd
            INNER JOIN productoinventarios pi ON fd.codigoProducto = pi.codigoProducto
            WHERE fd.id_factura = :id_factura
        ");
        $stmtOldDetails->execute([':id_factura' => $txtId]);
        $oldDetails = $stmtOldDetails->fetchAll(PDO::FETCH_ASSOC);

        // NOTA: No restaurar inventario manualmente - el trigger lo maneja cuando se eliminan los detalles

        // Actualizar factura (MODIFICADO: Se agregaron numero_factura y fecha_vencimiento)
        $sentencia = $pdo->prepare("UPDATE facturav 
                                    SET identificacion = :identificacion,
                                        nombre = :nombre,
                                        fecha = :fecha,
                                        consecutivo = :consecutivo,
                                        numero_factura = :numero_factura,
                                        formaPago = :formaPago,
                                        fecha_vencimiento = :fecha_vencimiento,
                                        subtotal = :subtotal,
                                        ivaTotal = :ivaTotal,
                                        retenciones = :retenciones,
                                        valorTotal = :valorTotal,
                                        observaciones = :observaciones,
                                        retencion_tarifa = :retencion_tarifa
                                    WHERE id = :id");

        $sentencia->bindParam(':identificacion', $identificacion);
        $sentencia->bindParam(':nombre', $nombre);
        $sentencia->bindParam(':fecha', $fecha);
        $sentencia->bindParam(':consecutivo', $consecutivo);
        $sentencia->bindParam(':numero_factura', $numeroFactura);
        $sentencia->bindParam(':formaPago', $formaPago);
        $sentencia->bindParam(':fecha_vencimiento', $fechaVencimiento);
        $sentencia->bindParam(':subtotal', $subtotal);
        $sentencia->bindParam(':ivaTotal', $ivaTotal);
        $sentencia->bindParam(':retenciones', $retenciones);
        $sentencia->bindParam(':valorTotal', $valorTotal);
        $sentencia->bindParam(':observaciones', $observaciones);
        $sentencia->bindParam(':retencion_tarifa', $selectRetencion);
        $sentencia->bindParam(':id', $txtId);
        $sentencia->execute();

        // Eliminar detalles antiguos
        $deleteDetalle = $pdo->prepare("DELETE FROM factura_detalle WHERE id_factura = :id_factura");
        $deleteDetalle->bindParam(':id_factura', $txtId);
        $deleteDetalle->execute();

        // Insertar nuevos detalles
        if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
            $sqlDetalle = "INSERT INTO factura_detalle 
                          (id_factura, codigoProducto, nombreProducto, cantidad, precio_unitario, iva, total)
                          VALUES (:id_factura, :codigoProducto, :nombreProducto, :cantidad, :precio_unitario, :iva, :total)";
            $stmtDetalle = $pdo->prepare($sqlDetalle);

            $checkItem = $pdo->prepare("SELECT tipoItem, cantidad FROM productoinventarios WHERE codigoProducto = :codigo");

            foreach ($_POST['detalles'] as $detalle) {
                $checkItem->execute([':codigo' => $detalle['codigoProducto']]);
                $item = $checkItem->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    throw new Exception("El código {$detalle['codigoProducto']} no existe en el inventario.");
                }

                // Validar stock disponible (pero NO actualizar - el trigger lo hace)
                if (strtolower($item['tipoItem']) === 'producto') {
                    if ($item['cantidad'] < $detalle['cantidad']) {
                        throw new Exception("Stock insuficiente para {$detalle['nombreProducto']}. Disponible: {$item['cantidad']}, Solicitado: {$detalle['cantidad']}");
                    }
                    // NOTA: La cantidad se actualiza mediante un trigger en la base de datos
                }

                $stmtDetalle->execute([
                    ':id_factura' => $txtId,
                    ':codigoProducto' => $detalle['codigoProducto'],
                    ':nombreProducto' => $detalle['nombreProducto'],
                    ':cantidad' => $detalle['cantidad'],
                    ':precio_unitario' => $detalle['precio'],
                    ':iva' => $detalle['iva'],
                    ':total' => $detalle['precioTotal']
                ]);
            }
        }

        // Eliminar medios de pago antiguos
        $deleteMediosPago = $pdo->prepare("DELETE FROM medios_pago_factura WHERE factura_id = :factura_id AND tipo_factura = 'venta'");
        $deleteMediosPago->execute([':factura_id' => $txtId]);

        // Insertar nuevos medios de pago
        if (!empty($mediosPagoArray)) {
            $sqlMedioPago = "INSERT INTO medios_pago_factura 
                            (factura_id, tipo_factura, forma_pago, cuenta_contable, nombre_cuenta, valor) 
                            VALUES (:factura_id, 'venta', :forma_pago, :cuenta_contable, :nombre_cuenta, :valor)";
            $stmtMedioPago = $pdo->prepare($sqlMedioPago);

            foreach ($mediosPagoArray as $medio) {
                $partes = explode(' - ', $medio['metodo']);
                $forma_pago_nombre = $partes[0] ?? $medio['metodo'];
                $cuenta_contable = $partes[1] ?? '';
                $nombre_cuenta = $partes[1] ?? '';

                $stmtMedioPago->execute([
                    ':factura_id' => $txtId,
                    ':forma_pago' => $forma_pago_nombre,
                    ':cuenta_contable' => $cuenta_contable,
                    ':nombre_cuenta' => $nombre_cuenta,
                    ':valor' => $medio['valor']
                ]);
            }
        }

        // Registrar nuevos asientos contables
        $libroDiario->registrarFacturaVenta($txtId);

        $pdo->commit();
        header("Location: ".$_SERVER['PHP_SELF']."?msg=modificado");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ".$_SERVER['PHP_SELF']."?msg=error&detalle=".urlencode($e->getMessage()));
        exit;
    }
break;

  case "btnEliminar":
    try {
        $pdo->beginTransaction();

        // Eliminar asientos contables
        $libroDiario->eliminarMovimientos('factura_venta', $txtId);

        // Obtener detalles para restaurar inventario
        $stmtDetails = $pdo->prepare("
            SELECT fd.codigoProducto, fd.cantidad, pi.tipoItem 
            FROM factura_detalle fd
            INNER JOIN productoinventarios pi ON fd.codigoProducto = pi.codigoProducto
            WHERE fd.id_factura = :id_factura
        ");
        $stmtDetails->execute([':id_factura' => $txtId]);
        $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

        // Restaurar inventario
        $restoreStock = $pdo->prepare("UPDATE productoinventarios SET cantidad = cantidad + :cantidad WHERE codigoProducto = :codigo");
        foreach ($details as $detail) {
            if (strtolower($detail['tipoItem']) === 'producto') {
                $restoreStock->execute([
                    ':cantidad' => $detail['cantidad'],
                    ':codigo' => $detail['codigoProducto']
                ]);
            }
        }

        // Eliminar medios de pago asociados
        $sentenciaMedios = $pdo->prepare("DELETE FROM medios_pago_factura WHERE factura_id = :id AND tipo_factura = 'venta'");
        $sentenciaMedios->bindParam(':id', $txtId);
        $sentenciaMedios->execute();

        // Eliminar detalles
        $sentenciaDetalle = $pdo->prepare("DELETE FROM factura_detalle WHERE id_factura = :id");
        $sentenciaDetalle->bindParam(':id', $txtId);
        $sentenciaDetalle->execute();

        // Eliminar factura
        $sentencia = $pdo->prepare("DELETE FROM facturav WHERE id = :id");
        $sentencia->bindParam(':id', $txtId);
        $sentencia->execute();

        $pdo->commit();
        header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminado");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ".$_SERVER['PHP_SELF']."?msg=error&detalle=".urlencode($e->getMessage()));
        exit;
    }
break;

  case "btnEditar":
      // Cargar datos de la factura
      $sentencia = $pdo->prepare("SELECT * FROM facturav WHERE id = :id");
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();
      $factura = $sentencia->fetch(PDO::FETCH_ASSOC);

      if ($factura) {
          $identificacion = $factura['identificacion'];
          $nombre = $factura['nombre'];
          $fecha = $factura['fecha'];
          $consecutivo = $factura['consecutivo'];
          $numeroFactura = $factura['numero_factura'] ?? ""; // NUEVO CAMPO
          $formaPago = $factura['formaPago'];
          $fechaVencimiento = $factura['fecha_vencimiento'] ?? ""; // NUEVO CAMPO
          $subtotal = $factura['subtotal'];
          $ivaTotal = $factura['ivaTotal'];
          $retenciones = $factura['retenciones'];
          $valorTotal = $factura['valorTotal'];
          $observaciones = $factura['observaciones'];
          $selectRetencion = $factura['retencion_tarifa'] ?? "";
      }

      // Cargar detalles asociados
      $stmtDetalle = $pdo->prepare("SELECT * FROM factura_detalle WHERE id_factura = :id_factura");
      $stmtDetalle->bindParam(':id_factura', $txtId);
      $stmtDetalle->execute();
      $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

      // Cargar medios de pago asociados
      $stmtMedios = $pdo->prepare("SELECT * FROM medios_pago_factura WHERE factura_id = :id_factura AND tipo_factura = 'venta'");
      $stmtMedios->bindParam(':id_factura', $txtId);
      $stmtMedios->execute();
      $mediosPagoFactura = $stmtMedios->fetchAll(PDO::FETCH_ASSOC);
  break;
}

$sentencia= $pdo->prepare("SELECT * FROM `facturav` WHERE 1");
$sentencia->execute();
$lista=$sentencia->fetchALL(PDO::FETCH_ASSOC);

// Buscar cliente por identificación o nombre
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['identificacion']) || isset($_POST['nombreCliente'])) && !isset($_POST['accion'])) {

    $identificacion = $_POST['identificacion'] ?? '';
    $nombreCliente = $_POST['nombreCliente'] ?? '';
    $cliente = null;

    if (!empty($identificacion)) {
        $stmt = $pdo->prepare("
            SELECT cedula, CONCAT(nombres, ' ', apellidos) AS nombreCompleto
            FROM catalogosterceros
            WHERE cedula = :cedula AND tipoTercero LIKE '%Cliente%'
            LIMIT 1
        ");
        $stmt->bindParam(':cedula', $identificacion, PDO::PARAM_STR);
        $stmt->execute();
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (!empty($nombreCliente)) {
        $likeNombre = "%$nombreCliente%";
        $stmt = $pdo->prepare("
            SELECT cedula, CONCAT(nombres, ' ', apellidos) AS nombreCompleto
            FROM catalogosterceros
            WHERE CONCAT(nombres, ' ', apellidos) LIKE :nombre AND tipoTercero LIKE '%Cliente%'
            LIMIT 1
        ");
        $stmt->bindParam(':nombre', $likeNombre, PDO::PARAM_STR);
        $stmt->execute();
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($cliente) {
        echo json_encode([
            "nombre" => $cliente['nombreCompleto'],
            "identificacion" => $cliente['cedula']
        ]);
    } else {
        echo json_encode(["nombre" => "No encontrado o no es cliente"]);
    }
    exit;
}

// Buscar producto por código (para el select)
if (isset($_POST['codigoProducto'])) {
    $codigo = trim($_POST['codigoProducto']);
    $producto = null;

    if ($codigo !== '') {
        $stmt = $pdo->prepare("SELECT codigoProducto, descripcionProducto, cantidad, tipoItem, precioUnitario 
                               FROM productoinventarios 
                               WHERE codigoProducto = :codigo 
                               LIMIT 1");
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($producto) {
        $response = [
            "codigoProducto" => $producto['codigoProducto'],
            "nombreProducto" => $producto['descripcionProducto'],
            "tipoItem" => $producto['tipoItem'],
            "precioUnitario" => $producto['precioUnitario'] ?? 0
        ];
        
        // Solo mostrar stock si es producto (no servicio)
        if (strtolower($producto['tipoItem']) === 'producto') {
            $response['stockDisponible'] = $producto['cantidad'];
        }
        
        echo json_encode($response);
    } else {
        echo json_encode(["nombreProducto" => "No encontrado"]);
    }
    exit;
}

// visualisar metodos de pago
$mediosPago = [];
$stmt = $pdo->query("SELECT metodoPago, cuentaContable FROM mediosdepago");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mediosPago[] = $row;
}

// Obtener impuestos de retención
$impuestos = [];
$stmt = $pdo->query("SELECT id, codigo, descripcion, tarifa, tipo FROM impuestos_retenciones WHERE activo = 1 ORDER BY tipo, tarifa");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $impuestos[] = $row;
}

// Establecer fecha actual por defecto si no hay fecha
if (empty($fecha)) {
    $fecha = date('Y-m-d');
}

?>

<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  switch ("<?= $_GET['msg'] ?>") {
    case "agregado":
      Swal.fire({
        icon: 'success',
        title: 'Guardado exitosamente',
        text: 'La factura de venta se ha agregado y el inventario se ha actualizado',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "modificado":
      Swal.fire({
        icon: 'success',
        title: 'Modificado correctamente',
        text: 'Los datos se actualizaron y el inventario se ajustó correctamente',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "eliminado":
      Swal.fire({
        icon: 'success',
        title: 'Eliminado correctamente',
        text: 'La factura fue eliminada y el inventario se restauró',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "error":
      const detalle = new URLSearchParams(window.location.search).get('detalle');
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: detalle || 'Ocurrió un error al procesar la operación',
        confirmButtonColor: '#d33'
      });
      break;
  }

  if (window.history.replaceState) {
    const url = new URL(window.location);
    url.searchParams.delete('msg');
    url.searchParams.delete('detalle');
    window.history.replaceState({}, document.title, url);
  }
});
</script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>SOFI - UDES</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="../../assets/img/favicon.png" rel="icon">
  <link href="../../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../../assets/vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="../../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../../assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="../../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <link href="../../assets/css/improved-style.css" rel="stylesheet">

  <style> 
    input[type="text"] {
      width: 100%;
      box-sizing: border-box;
      padding: 5px;
    }

    .add-row-btn {
      cursor: pointer;
      background-color: #0d6efd;
      color: white;
      border: none;
      padding: 10px;
      font-size: 18px;
      margin-top: 20px;
    }

    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      font-weight: bold;
      display: inline-block;
      width: 150px;
    }
    .totals {
      margin-top: 20px;
      text-align: right;
    }
    .totals label {
      font-weight: bold;
    }
    .totals input {
      width: 160px;
    }
/* Centra los botones +/- dentro de la celda de acciones */
    .acciones-fila {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    /* Estilos para seccion de medios de pago */
    .metodos-pago-container {
      margin-top: 20px;
      padding: 18px 20px;
      border: 1px solid #dfe3e8;
      border-radius: 6px;
      background-color: #fafbfc;
    }
    .metodos-pago-container h5 {
      color: #2c3e50;
      font-size: 0.95rem;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      border-bottom: 1px solid #e5e8eb;
      padding-bottom: 10px;
    }
    .metodo-pago-row {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-bottom: 10px;
    }
    .metodo-pago-row select,
    .metodo-pago-row input { flex: 1; }
    .btn-metodo {
      width: 38px;
      height: 38px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
      border: 1px solid #d7dbe0;
      background-color: #f1f3f5;
      color: #495057;
    }
    .btn-metodo.btn-success:hover { background-color: #e2e8ef; color: #2c3e50; }
    .btn-metodo.btn-danger:hover { background-color: #f3dede; color: #7a3232; }
    .total-medios-pago {
      margin-top: 12px;
      padding: 10px 14px;
      background-color: #ffffff;
      border: 1px solid #dfe3e8;
      border-left: 3px solid #2c3e50;
      border-radius: 4px;
      font-weight: 600;
      color: #2c3e50;
    }
    .validacion-error { color: #a94442; font-weight: 600; font-size: 0.9rem; }
    .validacion-exito { color: #2f6f4e; font-weight: 600; font-size: 0.9rem; }
  </style>
  </style>

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="../../dashboard.php">
          <img src="../../Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li>
            <a class="nav-link scrollto active" href="../../dashboard.php" style="color: darkblue;">Inicio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="../../perfil.php" style="color: darkblue;">Mi Negocio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="../../index.php" style="color: darkblue;">Cerrar Sesión</a>
          </li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

    <!-- ======= Services Section ======= -->
    <section id="services" class="services">
      <button class="btn-ir" onclick="window.location.href='../menus/menudocumentos.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>FACTURA DE VENTA</h2>
          <p>Para crear una nueva factura de venta diligencie los campos a continuación:</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>
        
        <form id="formFacturaVentas" action="" method="post" class="container mt-3">

          <!-- ID oculto -->
          <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">

          <!-- Identificación y Nombre -->
          <div class="row g-3">
            <div class="col-md-4">
              <label for="identificacion" class="form-label fw-bold">Identificación del Cliente (NIT o CC)*</label>
              <input type="number" class="form-control" id="identificacion" name="identificacion"
                    placeholder="Ej: 123456789"
                    value="<?php echo $identificacion; ?>" required>
            </div>

            <div class="col-md-8">
              <label for="nombre" class="form-label fw-bold">Nombre del cliente</label>
              <input type="text" class="form-control" id="nombre" name="nombre"
                    placeholder="Nombre del cliente"
                    value="<?php echo $nombre; ?>">
            </div>
          </div>

          <!-- Fecha, Consecutivo y Número de Factura -->
          <div class="row g-3 mt-2">
            <div class="col-md-3">
              <label for="fecha" class="form-label fw-bold">Fecha del documento</label>
              <input type="date" class="form-control" id="fecha" name="fecha"
                    value="<?php echo $fecha; ?>" required>
            </div>

            <div class="col-md-3">
              <label for="consecutivo" class="form-label fw-bold">Consecutivo</label>
              <input type="text" class="form-control" id="consecutivo" name="consecutivo"
                    placeholder="Número consecutivo"
                    value="<?php echo $consecutivo; ?>" readonly>
            </div>

            <div class="col-md-3">
              <label for="numeroFactura" class="form-label fw-bold">Número de Factura</label>
              <input type="text" class="form-control" id="numeroFactura" name="numeroFactura"
                    placeholder="Ej: FV-001"
                    value="<?php echo $numeroFactura ?? ''; ?>">
            </div>
          </div>

          <!-- Tabla de productos -->
          <div class="table-responsive mt-3">
            <table class="table-container">
              <thead class="table-primary text-center">
                <tr>
                  <th width="20%">Código del producto</th>
                  <th width="20%">Nombre del producto</th>
                  <th width="10%">Cantidad</th>
                  <th width="15%">Precio Unitario</th>
                  <th width="12%">IVA</th>
                  <th width="15%">Valor Total</th>
                  <th width="5%">Acciones</th>
                </tr>
              </thead>
              <tbody id="product-table">
                <?php if (!empty($detalles)) : ?>
                  <?php foreach ($detalles as $detalle): ?>
                    <tr>
                      <td>
                        <select name="codigoProducto" class="form-control select-producto" onchange="cargarProducto(this)">
                          <option value="">Seleccionar producto</option>
                          <!-- En la sección de detalles existentes -->
                          <?php
                          $productos = $pdo->query("SELECT codigoProducto, descripcionProducto FROM productoinventarios ORDER BY descripcionProducto");
                          while ($prod = $productos->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($prod['codigoProducto'] == $detalle['codigoProducto']) ? 'selected' : '';
                            echo "<option value='{$prod['codigoProducto']}' data-nombre='{$prod['descripcionProducto']}' $selected>{$prod['codigoProducto']}</option>";
                          }
                          ?>
                        </select>
                      </td>
                      <td><input type="text" name="nombreProducto" class="form-control" value="<?= htmlspecialchars($detalle['nombreProducto']) ?>" readonly></td>
                      <td><input type="number" name="cantidad" class="form-control quantity" value="<?= htmlspecialchars($detalle['cantidad']) ?>"></td>
                      <td><input type="number" name="precio" class="form-control unit-price" value="<?= htmlspecialchars($detalle['precio_unitario']) ?>"></td>
                      <td><input type="number" name="iva" class="form-control iva" value="<?= htmlspecialchars($detalle['iva']) ?>" readonly></td>
                      <td><input type="number" name="precioTotal" class="form-control total-price" value="<?= htmlspecialchars($detalle['total']) ?>" readonly></td>
                      <td>
                        <button type="button" class="btn-add" onclick="addRow()">+</button>
                        <button type="button" class="btn-remove" onclick="removeRowSafe(this)">-</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td>
                      <select name="codigoProducto" class="form-control select-producto" onchange="cargarProducto(this)">
                        <option value="">Seleccionar producto</option>
                        <!-- En la sección de nueva fila -->
                        <?php
                        $productos = $pdo->query("SELECT codigoProducto, descripcionProducto FROM productoinventarios ORDER BY descripcionProducto");
                        while ($prod = $productos->fetch(PDO::FETCH_ASSOC)) {
                          echo "<option value='{$prod['codigoProducto']}' data-nombre='{$prod['descripcionProducto']}'>{$prod['codigoProducto']}</option>";
                        }
                        ?>
                      </select>
                    </td>
                    <td><input type="text" name="nombreProducto" class="form-control" value="" readonly></td>
                    <td><input type="number" name="cantidad" class="form-control quantity" value=""></td>
                    <td><input type="number" name="precio" class="form-control unit-price" value=""></td>
                    <td><input type="number" name="iva" class="form-control iva" value="0.00" readonly></td>
                    <td><input type="number" name="precioTotal" class="form-control total-price" value="0.00" readonly></td>
                    <td>
                      <button type="button" class="btn-add" onclick="addRow()">+</button>
                      <button type="button" class="btn-remove" onclick="removeRowSafe(this)">-</button>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="col-md-6" id="retencionContainer">
              <label for="selectRetencion" class="form-label fw-bold">Retencion aplicable</label>
              <select class="form-select" id="selectRetencion" name="selectRetencion">
                <option value="">Seleccione una retencion</option>
                <?php foreach ($impuestos as $impuesto): ?>
                  <option value="<?= $impuesto['tarifa'] ?>" <?= ($selectRetencion == $impuesto['tarifa']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($impuesto['descripcion']) ?> (<?= $impuesto['tarifa'] ?>%)
                  </option>
                <?php endforeach; ?>
              </select>
          </div>

          <!-- Forma de Pago -->
          <!-- NUEVA SECCION: Multiples medios de pago -->
          <div class="metodos-pago-container">
            <h5 class="fw-bold mb-3">Metodos de Pago</h5>

            <div id="medios-pago-container">
              <?php if (!empty($mediosPagoFactura)): ?>
                <?php foreach ($mediosPagoFactura as $index => $medio): ?>
                  <div class="metodo-pago-row" data-index="<?= $index ?>">
                    <select name="metodosPago[<?= $index ?>][metodo]" class="form-select select-metodo-pago" required>
                      <option value="">Seleccione método</option>
                      <?php foreach ($mediosPago as $mp): ?>
                        <?php $valorCompleto = $mp['metodoPago'] . ' - ' . $mp['cuentaContable']; ?>
                        <option value="<?= htmlspecialchars($valorCompleto) ?>"
                          <?= ($valorCompleto == ($medio['forma_pago'] . ' - ' . $medio['cuenta_contable'])) ? 'selected' : '' ?>>
                          <?= htmlspecialchars($mp['metodoPago']) ?> - <?= htmlspecialchars($mp['cuentaContable']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <input type="number" name="metodosPago[<?= $index ?>][valor]" class="form-control valor-metodo"
                          placeholder="Valor" step="0.01" min="0"
                          value="<?= number_format($medio['valor'], 2, '.', '') ?>" required>
                    <?php if ($index == 0): ?>
                      <button type="button" class="btn btn-success btn-metodo" onclick="agregarMedioPago()"><i class="fas fa-plus"></i></button>
                    <?php else: ?>
                      <button type="button" class="btn btn-danger btn-metodo" onclick="eliminarMedioPago(this)"><i class="fas fa-minus"></i></button>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="metodo-pago-row" data-index="0">
                  <select name="metodosPago[0][metodo]" class="form-select select-metodo-pago" required>
                    <option value="">Seleccione método</option>
                    <?php foreach ($mediosPago as $mp): ?>
                      <option value="<?= htmlspecialchars($mp['metodoPago'] . ' - ' . $mp['cuentaContable']) ?>">
                        <?= htmlspecialchars($mp['metodoPago']) ?> - <?= htmlspecialchars($mp['cuentaContable']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <input type="number" name="metodosPago[0][valor]" class="form-control valor-metodo"
                        placeholder="Valor" step="0.01" min="0"
                        value="<?php echo isset($valorTotal) && !empty($valorTotal) ? $valorTotal : '0.00'; ?>" required>
                  <button type="button" class="btn btn-success btn-metodo" onclick="agregarMedioPago()"><i class="fas fa-plus"></i></button>
                </div>
              <?php endif; ?>
            </div>

            <div class="total-medios-pago">
              <div class="row">
                <div class="col-md-6">
                  <span>Total medios de pago: </span>
                  <span id="total-medios-pago"><?php echo isset($valorTotal) && !empty($valorTotal) ? $valorTotal : '0.00'; ?></span>
                </div>
                <div class="col-md-6"><span id="validacion-medios-pago"></span></div>
              </div>
            </div>
          </div>

          <!-- Campo de Fecha de Vencimiento (oculto inicialmente) -->
          <div class="row g-3 mt-3">
            <div class="col-md-6" id="fechaVencimientoContainer" style="display: none;">
              <label for="fechaVencimiento" class="form-label fw-bold">Fecha de Vencimiento*</label>
              <input type="date" class="form-control" id="fechaVencimiento" name="fechaVencimiento"
                    value="<?php echo $fechaVencimiento ?? ''; ?>" min="<?php echo date('Y-m-d'); ?>">
            </div>
          </div>

          <!-- Totales alineados a la derecha uno debajo del otro -->
          <div class="col-md-3 ms-auto mt-3">
            <div class="mb-2">
              <label for="subtotal" class="form-label fw-bold">Subtotal</label>
              <input type="text" id="subtotal" name="subtotal" class="form-control text-end" 
                    value="<?php echo $subtotal ?? '0.00'; ?>" readonly>
            </div>

            <div class="mb-2">
              <label for="ivaTotal" class="form-label fw-bold">IVA</label>
              <input type="text" id="ivaTotal" name="ivaTotal" class="form-control text-end" 
                    value="<?php echo $ivaTotal ?? '0.00'; ?>" readonly>
            </div>

            <div class="mb-2">
              <label for="retenciones" class="form-label fw-bold">Retenciones</label>
              <input type="text" id="retenciones" name="retenciones" class="form-control text-end" 
                    value="<?php echo $retenciones ?? '0.00'; ?>" readonly>
            </div>

            <div class="mb-2">
              <label for="valorTotal" class="form-label fw-bold">Valor Total</label>
              <input type="text" id="valorTotal" name="valorTotal" 
                    class="form-control text-end fw-bold border-2 border-primary" 
                    value="<?php echo $valorTotal ?? '0.00'; ?>" readonly>
            </div>
          </div>

        <div class="mb-3">
          <label for="observaciones" class="form-label">Observaciones</label>
          <input type="text" name="observaciones" value="<?php echo $observaciones;?>" class="form-control" id="observaciones" placeholder="">
        </div>

        <!-- Botones de acción -->
        <div class="mt-4">
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion">Modificar</button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion">Eliminar</button>
          <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
        </div>
      </form>

      <div class="row">
        <div class="table-container">
          <table class="table-historial">
            <thead>
            <tr>
                <th>Cliente</th>
                <th>Documento</th>
                <th>Forma Pago</th>
                <th>Vencimiento</th>
                <th class="text-end">Valor Total</th>
                <th>Observaciones</th>
                <th>Acción</th>
            </tr>
            </thead>
            <tbody id="tabla-registros">
            <?php $modalesParaRenderizar = []; ?>
            <?php foreach($lista as $usuario){ ?>
                <tr>
                <td>
                    <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong><br>
                    <span class="text-muted" style="font-size:11px;">ID: <?php echo htmlspecialchars($usuario['identificacion']); ?></span>
                </td>
                <td>
                    <?php echo date('d/m/Y', strtotime($usuario['fecha'])); ?><br>
                    <span class="text-muted" style="font-size:11px;">
                    Cons. <?php echo $usuario['consecutivo']; ?><?php echo !empty($usuario['numero_factura']) ? ' · Fact. '.$usuario['numero_factura'] : ''; ?>
                    </span>
                </td>
                <td>
                    <?php 
                    $stmtMedios = $pdo->prepare("SELECT forma_pago, cuenta_contable, valor FROM medios_pago_factura 
                                                WHERE factura_id = :factura_id AND tipo_factura = 'venta'");
                    $stmtMedios->execute([':factura_id' => $usuario['id']]);
                    $mediosFactura = $stmtMedios->fetchAll(PDO::FETCH_ASSOC);

                    if (is_array($mediosFactura) && count($mediosFactura) > 0) {
                        $primerMedio = $mediosFactura[0]['forma_pago'];
                        $partes = explode(' - ', $primerMedio);
                        $nombreCorto = $partes[0] ?? $primerMedio;
                        
                        $modalId = "modalMediosPago" . $usuario['id'];
                        
                        $modalesParaRenderizar[] = [
                            'modalId' => $modalId,
                            'consecutivo' => $usuario['consecutivo'],
                            'medios' => $mediosFactura,
                            'valorTotal' => $usuario['valorTotal']
                        ];
                        ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-medios-pago" 
                                data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>">
                            <i class="fas fa-credit-card me-1"></i>
                            <?php echo htmlspecialchars($nombreCorto); ?>
                            <?php if (count($mediosFactura) > 1): ?>
                                <span class="badge bg-secondary ms-1">+<?php echo (count($mediosFactura) - 1); ?></span>
                            <?php endif; ?>
                        </button>
                        <?php
                    } else {
                        echo '<span class="text-muted"><i class="fas fa-ban me-1"></i>Sin medios</span>';
                    }
                    ?>
                </td>
                <td><?php echo !empty($usuario['fecha_vencimiento']) && $usuario['fecha_vencimiento'] != '0000-00-00' ? date('d/m/Y', strtotime($usuario['fecha_vencimiento'])) : '—'; ?></td>
                <td class="text-end fw-bold"
                    title="Subtotal: $<?php echo number_format($usuario['subtotal'],2); ?> · IVA: $<?php echo number_format($usuario['ivaTotal'],2); ?> · Retenciones: $<?php echo number_format($usuario['retenciones'],2); ?>">
                    $<?php echo number_format($usuario['valorTotal'],2); ?>
                </td>
                <td class="col-observaciones" title="<?php echo htmlspecialchars($usuario['observaciones']); ?>">
                    <?php echo htmlspecialchars($usuario['observaciones']); ?>
                </td>
                <td>
                    <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                        <form action="" method="post" class="d-inline">
                            <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
                            <input type="hidden" name="identificacion" value="<?php echo $usuario['identificacion']; ?>">
                            <input type="hidden" name="nombre" value="<?php echo $usuario['nombre']; ?>">
                            <input type="hidden" name="fecha" value="<?php echo $usuario['fecha']; ?>">
                            <input type="hidden" name="consecutivo" value="<?php echo $usuario['consecutivo']; ?>">
                            <input type="hidden" name="numeroFactura" value="<?php echo $usuario['numero_factura'] ?? ''; ?>">
                            <input type="hidden" name="formaPago" value="<?php echo $usuario['formaPago']; ?>">
                            <input type="hidden" name="fechaVencimiento" value="<?php echo $usuario['fecha_vencimiento'] ?? ''; ?>">
                            <input type="hidden" name="subtotal" value="<?php echo $usuario['subtotal']; ?>">
                            <input type="hidden" name="ivaTotal" value="<?php echo $usuario['ivaTotal']; ?>">
                            <input type="hidden" name="retenciones" value="<?php echo $usuario['retenciones']; ?>">
                            <input type="hidden" name="valorTotal" value="<?php echo $usuario['valorTotal']; ?>">
                            <input type="hidden" name="observaciones" value="<?php echo $usuario['observaciones']; ?>">
                            <input type="hidden" name="selectRetencion" value="<?php echo $usuario['retencion_tarifa'] ?? ''; ?>">
                            <button type="submit" name="accion" value="btnEditar" class="dropdown-item"><i class="fas fa-edit me-2"></i>Editar</button>
                        </form>
                        </li>
                        <li>
                        <form action="" method="post" class="d-inline">
                            <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>">
                            <button type="submit" name="accion" value="btnEliminar" class="dropdown-item text-danger"
                            onclick="return confirm('¿Eliminar esta factura?');"><i class="fas fa-trash-alt me-2"></i>Eliminar</button>
                        </form>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="ver_factura_venta.php?id=<?php echo $usuario['id']; ?>" target="_blank"><i class="fas fa-print me-2"></i>Ver / Imprimir</a></li>
                        <li><a class="dropdown-item" href="../../exports/pdf/generar_pdf_factura_venta.php?id=<?php echo $usuario['id']; ?>" target="_blank"><i class="fas fa-file-pdf me-2"></i>Descargar PDF</a></li>
                        <li><a class="dropdown-item" href="../../exports/excel/generar_excel_factura_venta.php?id=<?php echo $usuario['id']; ?>" target="_blank"><i class="fas fa-file-excel me-2"></i>Descargar Excel</a></li>
                    </ul>
                    </div>
                </td>
                </tr>
            <?php } ?>
          </tbody>
          </table> 

      </div>
      </div>
        
        <script>
        // Obtener consecutivo al cargar la página SOLO si no hay ID (modo agregar)
        window.addEventListener('DOMContentLoaded', function() {
            const txtId = document.getElementById("txtId").value;
            
            // Solo obtener nuevo consecutivo si estamos en modo AGREGAR (sin ID)
            if (!txtId || txtId.trim() === "") {
                fetch(window.location.pathname + "?get_consecutivo=1")
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('consecutivo').value = data.consecutivo;
                    })
                    .catch(error => console.error('Error al obtener consecutivo:', error));
            }
            
            // Permitir seleccionar cualquier fecha (pasada o futura)
            const fechaInput = document.getElementById("fecha");
            const hoy = new Date().toISOString().split('T')[0];

            // Solo establecer fecha por defecto si está vacía
            if (!fechaInput.value) {
                fechaInput.value = hoy;
            }
            // Calcular total de medios de pago inicial y configurar valor inicial
            inicializarMediosPago();
        });

        // Buscar el cliente
        const inputIdentificacion = document.getElementById("identificacion");
        const inputNombre = document.getElementById("nombre");

        // Buscar por identificación
        inputIdentificacion.addEventListener("input", function () {
            const valor = this.value.trim();
            if (valor.length > 0) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ identificacion: valor }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(res => res.json())
                .then(data => {
                    inputNombre.value = data.nombre || "No encontrado";
                })
                .catch(console.error);
            } else {
                inputNombre.value = "";
            }
        });

        // Buscar por nombre
        inputNombre.addEventListener("input", function () {
            const valor = this.value.trim();
            if (valor.length >= 3) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ nombreCliente: valor }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.identificacion) {
                        inputIdentificacion.value = data.identificacion;
                    }
                })
                .catch(console.error);
            }
        });

        // Funciones para manejar múltiples medios de pago
        let contadorMediosPago = <?= !empty($mediosPagoFactura) ? count($mediosPagoFactura) : 1 ?>;

        function inicializarMediosPago() {
            const valorTotal = parseFloat(document.getElementById('valorTotal').value) || 0;
            const primerValorInput = document.querySelector('.valor-metodo');
            const txtId = document.getElementById('txtId').value;
            const modoEdicion = txtId && txtId.trim() !== "";

            if (!modoEdicion && primerValorInput) {
                primerValorInput.value = valorTotal.toFixed(2);
            }
            calcularTotalMediosPago();
            mostrarFechaVencimiento();
        }

        function agregarMedioPago() {
            const container = document.getElementById('medios-pago-container');
            const newRow = document.createElement('div');
            newRow.className = 'metodo-pago-row';
            newRow.setAttribute('data-index', contadorMediosPago);

            const valorTotal = parseFloat(document.getElementById('valorTotal').value) || 0;
            const totalActual = calcularTotalMediosPago(false);
            const valorRestante = Math.max(0, valorTotal - totalActual);

            newRow.innerHTML = `
                <select name="metodosPago[${contadorMediosPago}][metodo]" class="form-select select-metodo-pago" required>
                    <option value="">Seleccione método</option>
                    <?php foreach ($mediosPago as $mp): ?>
                        <option value="<?= htmlspecialchars($mp['metodoPago'] . ' - ' . $mp['cuentaContable']) ?>">
                            <?= htmlspecialchars($mp['metodoPago']) ?> - <?= htmlspecialchars($mp['cuentaContable']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="metodosPago[${contadorMediosPago}][valor]" class="form-control valor-metodo"
                      placeholder="Valor" step="0.01" min="0" value="${valorRestante.toFixed(2)}" required>
                <button type="button" class="btn btn-danger btn-metodo" onclick="eliminarMedioPago(this)"><i class="fas fa-minus"></i></button>
            `;

            container.appendChild(newRow);
            contadorMediosPago++;
            newRow.querySelector('.valor-metodo').addEventListener('input', calcularTotalMediosPago);
            newRow.querySelector('.select-metodo-pago').addEventListener('change', function() { mostrarFechaVencimiento(); });
            calcularTotalMediosPago();
        }

        function eliminarMedioPago(button) {
            const row = button.closest('.metodo-pago-row');
            if (document.querySelectorAll('.metodo-pago-row').length > 1) {
                row.remove();
                calcularTotalMediosPago();
                mostrarFechaVencimiento();
            } else {
                Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe haber al menos un método de pago', confirmButtonColor: '#3085d6' });
            }
        }

        function calcularTotalMediosPago(updateUI = true) {
            let total = 0;
            document.querySelectorAll('.valor-metodo').forEach(input => { total += parseFloat(input.value) || 0; });

            if (updateUI) {
                document.getElementById('total-medios-pago').textContent = total.toFixed(2);
                const valorTotal = parseFloat(document.getElementById('valorTotal').value) || 0;
                const validacionElement = document.getElementById('validacion-medios-pago');

                if (Math.abs(total - valorTotal) < 0.01) {
                    validacionElement.textContent = 'Total correcto';
                    validacionElement.className = 'validacion-exito';
                } else {
                    const diferencia = valorTotal - total;
                    validacionElement.textContent = diferencia > 0 ? `Faltan: ${diferencia.toFixed(2)}` : `Sobran: ${Math.abs(diferencia).toFixed(2)}`;
                    validacionElement.className = 'validacion-error';
                }
            }
            return total;
        }

        function mostrarFechaVencimiento() {
            const formaPagoSelects = document.querySelectorAll('.select-metodo-pago');
            const fechaVencimientoContainer = document.getElementById('fechaVencimientoContainer');
            const retencionContainer = document.getElementById('retencionContainer');
            const fechaVencimientoInput = document.getElementById('fechaVencimiento');

            let esCredito = false;
            formaPagoSelects.forEach(select => {
                const valor = select.value.toLowerCase();
                if (valor.includes('credito') || valor.includes('crédito')) esCredito = true;
            });

            if (esCredito) {
                fechaVencimientoContainer.style.display = 'block';
                retencionContainer.classList.remove('col-md-6');
                retencionContainer.classList.add('col-md-12');

                const fechaDocumento = document.getElementById('fecha').value;
                if (fechaDocumento) fechaVencimientoInput.setAttribute('min', fechaDocumento);

                if (!fechaVencimientoInput.value && fechaDocumento) {
                    const fechaDefault = new Date(fechaDocumento);
                    fechaDefault.setDate(fechaDefault.getDate() + 30);
                    fechaVencimientoInput.value = fechaDefault.toISOString().split('T')[0];
                }
                fechaVencimientoInput.required = true;
            } else {
                fechaVencimientoContainer.style.display = 'none';
                retencionContainer.classList.remove('col-md-12');
                retencionContainer.classList.add('col-md-6');
                fechaVencimientoInput.value = '';
                fechaVencimientoInput.required = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.valor-metodo').forEach(input => input.addEventListener('input', calcularTotalMediosPago));
            document.querySelectorAll('.select-metodo-pago').forEach(select => select.addEventListener('change', function() { mostrarFechaVencimiento(); }));
        });

        document.addEventListener('DOMContentLoaded', function() {
            mostrarFechaVencimiento();
        });

        // Función para cargar precio automáticamente
        function cargarPrecioDesdeInventario(row) {
            const codigoInput = row.querySelector('[name="codigoProducto"]');
            const nombreInput = row.querySelector('[name="nombreProducto"]');
            const precioInput = row.querySelector('.unit-price');
            const codigo = codigoInput.value.trim();
            
            if (codigo) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ codigoProducto: codigo }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.precioUnitario && data.precioUnitario > 0) {
                        precioInput.value = data.precioUnitario;
                        if (data.nombreProducto && !nombreInput.value) {
                            nombreInput.value = data.nombreProducto;
                        }
                        // Recalcular valores
                        calcularValores();
                    }
                })
                .catch(console.error);
            }
        }

        // Escucha todos los cambios en la tabla
        document.querySelector("#product-table").addEventListener("input", function(e) {
          const target = e.target;
          const row = target.closest("tr");

          // Buscar por código
          if (target.name === "codigoProducto" && target.value.trim() !== "") {
              fetch("", {
                  method: "POST",
                  body: new URLSearchParams({ codigoProducto: target.value }),
                  headers: { "Content-Type": "application/x-www-form-urlencoded" }
              })
              .then(res => res.json())
              .then(data => {
                  row.querySelector('[name="nombreProducto"]').value = data.nombreProducto || "";
                  // Cargar precio automáticamente si existe
                  if (data.precioUnitario && data.precioUnitario > 0) {
                      const precioInput = row.querySelector('.unit-price');
                      precioInput.value = data.precioUnitario;
                      // Recalcular valores
                      calcularValores();
                  }
              });
          }

          // Buscar por nombre
          if (target.name === "nombreProducto" && target.value.trim().length >= 3) {
            fetch("", {
              method: "POST",
              body: new URLSearchParams({ nombreProducto: target.value }),
              headers: { "Content-Type": "application/x-www-form-urlencoded" }
            })
            .then(res => res.json())
            .then(data => {
              if (data.codigoProducto) {
                row.querySelector('[name="codigoProducto"]').value = data.codigoProducto;
                // Cargar precio automáticamente
                if (data.precioUnitario && data.precioUnitario > 0) {
                    const precioInput = row.querySelector('.unit-price');
                    precioInput.value = data.precioUnitario;
                    calcularValores();
                }
              }
            });            
          }
        });

        // Calcular Retenciones y Total
        function calcularRetencionesYTotal() {
            const subtotal = parseFloat(document.querySelector("#subtotal").value) || 0;
            const ivaTotal = parseFloat(document.querySelector("#ivaTotal").value) || 0;
            const selectRetencion = document.getElementById("selectRetencion");
            const tarifaRetencion = parseFloat(selectRetencion.value) || 0;
            
            // Calcular retención (sobre el subtotal)
            const retencion = subtotal * (tarifaRetencion / 100);
            
            // Calcular valor total (Subtotal + IVA - Retenciones)
            const valorTotal = subtotal + ivaTotal - retencion;
            
            document.querySelector("#retenciones").value = retencion.toFixed(2);
            document.querySelector("#valorTotal").value = valorTotal.toFixed(2);

            if (typeof actualizarValorMediosPago === 'function') {
                actualizarValorMediosPago();
            }
        }

        // Función principal para calcular valores
        function calcularValores() {
            let subtotal = 0;
            let ivaTotal = 0;
            let totalGeneral = 0;

            document.querySelectorAll("#product-table tr").forEach(row => {
                const cantidad = parseFloat(row.querySelector(".quantity")?.value || 0);
                const precio = parseFloat(row.querySelector(".unit-price")?.value || 0);
                const ivaField = row.querySelector(".iva");
                const totalField = row.querySelector(".total-price");

                if (!ivaField || !totalField) return;

                // Calcular subtotal (sin impuestos)
                const subtotalLinea = cantidad * precio;
                
                // Calcular IVA
                const iva = subtotalLinea * 0.19;
                
                // Calcular total (subtotal + IVA)
                const total = subtotalLinea + iva;

                ivaField.value = iva.toFixed(2);
                totalField.value = total.toFixed(2);

                subtotal += subtotalLinea;
                ivaTotal += iva;
                totalGeneral += total;
            });

            document.querySelector("#subtotal").value = subtotal.toFixed(2);
            document.querySelector("#ivaTotal").value = ivaTotal.toFixed(2);
            
            // Llamar a la función de retenciones
            calcularRetencionesYTotal();

        }

        
            // Función auxiliar: si solo hay un medio de pago, mantiene su valor igual al total
            function actualizarValorMediosPago() {  
                const valorTotal = parseFloat(document.getElementById('valorTotal').value) || 0;
                const txtId = document.getElementById('txtId').value;
                const modoEdicion = txtId && txtId.trim() !== "";

                if (!modoEdicion) {
                    const valorInputs = document.querySelectorAll('.valor-metodo');
                    if (valorInputs.length === 1) {
                        valorInputs[0].value = valorTotal.toFixed(2);
                        calcularTotalMediosPago();
                    }
                }
            }

            // Recalcular medios de pago cuando cambian los productos
            document.querySelector("#product-table").addEventListener("input", function (event) {
                setTimeout(actualizarValorMediosPago, 100);
            });

        // Event listener para cambios en el selector de retención
        document.getElementById("selectRetencion").addEventListener("change", calcularRetencionesYTotal);

        // Event listener para cambios en cantidad o precio
        document.querySelector("#product-table").addEventListener("input", function (event) {
            if (event.target.classList.contains("quantity") || event.target.classList.contains("unit-price")) {
                calcularValores();
            }
        });

        // Función para cargar producto cuando se selecciona del dropdown
        function cargarProducto(selectElement) {
            const row = selectElement.closest('tr');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const codigo = selectElement.value;
            const nombre = selectedOption.getAttribute('data-nombre');
            const nombreInput = row.querySelector('[name="nombreProducto"]');
            const precioInput = row.querySelector('.unit-price');
            
            if (codigo && nombre) {
                nombreInput.value = nombre;
                
                // Obtener precio desde la base de datos
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ codigoProducto: codigo }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.precioUnitario && data.precioUnitario > 0) {
                        precioInput.value = data.precioUnitario;
                    }
                    // Recalcular valores
                    calcularValores();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            } else {
                nombreInput.value = "";
                precioInput.value = "";
            }
        }

        // Agregar nueva fila
        window.addRow = function() {
            const tableBody = document.getElementById("product-table");
            const newRow = tableBody.firstElementChild.cloneNode(true);

            // Limpiar valores
            newRow.querySelectorAll("input").forEach(input => {
                if (!input.readOnly) {
                    input.value = "";
                }
            });
            
            // Resetear el select
            const select = newRow.querySelector('.select-producto');
            if (select) {
                select.selectedIndex = 0;
            }

            tableBody.appendChild(newRow);
        };

        // Función auxiliar para remover filas de forma segura
        function removeRowSafe(btn) {
            const rows = document.querySelectorAll("#product-table tr");
            if (rows.length > 1) {
                btn.closest("tr").remove();
                calcularValores();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe haber al menos una fila de producto',
                    confirmButtonColor: '#3085d6'
                });
            }
        }

        // Script para alternar botones y manejar cancelar
        document.addEventListener("DOMContentLoaded", function() {
            const id = document.getElementById("txtId").value;
            const btnAgregar = document.getElementById("btnAgregar");
            const btnModificar = document.getElementById("btnModificar");
            const btnEliminar = document.getElementById("btnEliminar");
            const btnCancelar = document.getElementById("btnCancelar");

            function modoAgregar() {
                // Ocultar/mostrar botones
                btnAgregar.style.display = "inline-block";
                btnModificar.style.display = "none";
                btnEliminar.style.display = "none";
                btnCancelar.style.display = "none";

                // Limpiar txtId
                document.getElementById("txtId").value = "";

               // Limpiar campos del formulario
              document.getElementById("identificacion").value = "";
              document.getElementById("nombre").value = "";
              document.getElementById("fecha").value = new Date().toISOString().split('T')[0];
              document.getElementById("numeroFactura").value = ""; // NUEVO CAMPO
              //document.getElementById("formaPago").value = "";
              document.getElementById("fechaVencimiento").value = ""; // NUEVO CAMPO
              document.getElementById("selectRetencion").value = "";
              document.getElementById("observaciones").value = "";
              
              // Ocultar fecha de vencimiento
              document.getElementById("fechaVencimientoContainer").style.display = "none";
              document.getElementById("retencionContainer").classList.remove("col-md-12");
              document.getElementById("retencionContainer").classList.add("col-md-6");

                    // Limpiar la tabla de productos y dejar solo UNA fila vacía con select
                const tableBody = document.getElementById("product-table");
                tableBody.innerHTML = `
                <tr>
                    <td>
                        <select name="codigoProducto" class="form-control select-producto" onchange="cargarProducto(this)">
                            <option value="">Seleccionar producto</option>
                            <?php
                            $productos = $pdo->query("SELECT codigoProducto, descripcionProducto FROM productoinventarios ORDER BY descripcionProducto");
                            while ($prod = $productos->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$prod['codigoProducto']}' data-nombre='{$prod['descripcionProducto']}'>{$prod['codigoProducto']}</option>";
                            }
                            ?>
                        </select>
                    </td>
                    <td><input type="text" name="nombreProducto" class="form-control" value="" readonly></td>
                    <td><input type="number" name="cantidad" class="form-control quantity" value=""></td>
                    <td><input type="number" name="precio" class="form-control unit-price" value=""></td>
                    <td><input type="number" name="iva" class="form-control iva" value="0.00" readonly></td>
                    <td><input type="number" name="precioTotal" class="form-control total-price" value="0.00" readonly></td>
                    <td>
                      <div class="acciones-fila">
                        <button type="button" class="btn-add" onclick="addRow()">+</button>
                        <button type="button" class="btn-remove" onclick="removeRowSafe(this)">-</button>
                      </div>
                    </td>
                </tr>
            `;

                // Obtener nuevo consecutivo
                fetch(window.location.pathname + "?get_consecutivo=1")
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('consecutivo').value = data.consecutivo;
                    })
                    .catch(error => console.error('Error al obtener consecutivo:', error));

                // Limpiar parámetros de la URL
                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.search = '';
                    window.history.replaceState({}, document.title, url);
                }

                const mediosPagoContainer = document.getElementById('medios-pago-container');
                mediosPagoContainer.innerHTML = `
                    <div class="metodo-pago-row" data-index="0">
                        <select name="metodosPago[0][metodo]" class="form-select select-metodo-pago" required>
                            <option value="">Seleccione método</option>
                            <?php foreach ($mediosPago as $mp): ?>
                                <option value="<?= htmlspecialchars($mp['metodoPago'] . ' - ' . $mp['cuentaContable']) ?>">
                                    <?= htmlspecialchars($mp['metodoPago']) ?> - <?= htmlspecialchars($mp['cuentaContable']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="metodosPago[0][valor]" class="form-control valor-metodo"
                              placeholder="Valor" step="0.01" min="0" value="0.00" required>
                        <button type="button" class="btn btn-success btn-metodo" onclick="agregarMedioPago()"><i class="fas fa-plus"></i></button>
                    </div>
                `;
                contadorMediosPago = 1;
                document.querySelectorAll('.valor-metodo').forEach(input => input.addEventListener('input', calcularTotalMediosPago));
                document.querySelectorAll('.select-metodo-pago').forEach(select => select.addEventListener('change', function() { mostrarFechaVencimiento(); }));

                // Resetear totales
                calcularValores();
                inicializarMediosPago();
            }

            // Estado inicial (modo modificar o agregar)
            if (id && id.trim() !== "") {
                btnAgregar.style.display = "none";
                btnModificar.style.display = "inline-block";
                btnEliminar.style.display = "inline-block";
                btnCancelar.style.display = "inline-block";
            } else {
                modoAgregar();
            }

            // Evento cancelar
            btnCancelar.addEventListener("click", function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: '¿Cancelar edición?',
                    text: "Se perderán los cambios no guardados",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'No',
                    confirmButtonColor: '#6c757d',
                    cancelButtonColor: '#3085d6'
                }).then((result) => {
                    if (result.isConfirmed) {
                        modoAgregar();
                    }
                });
            });
        });

        // Funciones de confirmación con SweetAlert2
        document.addEventListener("DOMContentLoaded", () => {
            const forms = document.querySelectorAll("form");

            forms.forEach((form) => {
                form.addEventListener("submit", function (e) {
                    const boton = e.submitter;
                    const accion = boton?.value;

                    if (accion === "btnModificar" || accion === "btnEliminar") {
                        e.preventDefault();

                        let titulo = accion === "btnModificar" ? "¿Guardar cambios?" : "¿Eliminar registro?";
                        let texto = accion === "btnModificar"
                            ? "Se actualizarán los datos de esta factura."
                            : "Esta acción eliminará el registro permanentemente.";

                        Swal.fire({
                            title: titulo,
                            text: texto,
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Sí, continuar",
                            cancelButtonText: "Cancelar",
                            confirmButtonColor: accion === "btnModificar" ? "#3085d6" : "#d33",
                            cancelButtonColor: "#6c757d",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                let inputAccion = form.querySelector("input[name='accionOculta']");
                                if (!inputAccion) {
                                    inputAccion = document.createElement("input");
                                    inputAccion.type = "hidden";
                                    inputAccion.name = "accion";
                                    form.appendChild(inputAccion);
                                }
                                inputAccion.value = accion;
                                form.submit();
                            }
                        });
                    }
                });
            });
        });

        // Empaquetar detalles antes de enviar el formulario
        document.getElementById("formFacturaVentas").addEventListener("submit", function(e) {
            const rows = document.querySelectorAll("#product-table tr");
            let detalles = [];

            rows.forEach(row => {
                const codigo = row.querySelector("[name='codigoProducto']")?.value || "";
                const nombre = row.querySelector("[name='nombreProducto']")?.value || "";
                const cantidad = row.querySelector("[name='cantidad']")?.value || "";
                const precio = row.querySelector("[name='precio']")?.value || "";
                const iva = row.querySelector("[name='iva']")?.value || "";
                const total = row.querySelector("[name='precioTotal']")?.value || "";

                if (codigo && nombre && cantidad && precio) {
                    detalles.push({
                        codigoProducto: codigo, 
                        nombreProducto: nombre, 
                        cantidad: parseFloat(cantidad), 
                        precio: parseFloat(precio), 
                        iva: parseFloat(iva), 
                        precioTotal: parseFloat(total)
                    });
                }
            });

            if (detalles.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debe agregar al menos un producto a la factura',
                    confirmButtonColor: '#d33'
                });
                return;
            }

            const inputDetalles = document.createElement("input");
            inputDetalles.type = "hidden";
            inputDetalles.name = "detalles";
            inputDetalles.value = JSON.stringify(detalles);

            // Validar suma de medios de pago
            const valorTotal = parseFloat(document.getElementById('valorTotal').value) || 0;
            const totalMediosPago = parseFloat(document.getElementById('total-medios-pago').textContent) || 0;

            if (Math.abs(valorTotal - totalMediosPago) > 0.01) {
                e.preventDefault();
                const diferencia = valorTotal - totalMediosPago;
                Swal.fire({
                    icon: 'error',
                    title: 'Error en medios de pago',
                    html: `La suma de los medios de pago (${totalMediosPago.toFixed(2)})<br>
                          no coincide con el valor total (${valorTotal.toFixed(2)})<br>
                          <strong>Faltan: ${diferencia.toFixed(2)}</strong>`,
                    confirmButtonColor: '#d33'
                });
                return;
            }

            const metodosCompletos = Array.from(document.querySelectorAll('.select-metodo-pago'))
              .filter(select => select.value !== "").length;
            if (metodosCompletos === 0) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Debe seleccionar al menos un método de pago', confirmButtonColor: '#d33' });
                return;
            }

            this.appendChild(inputDetalles);
        });

        // Establecer fecha actual al cargar la página si está vacía
        window.addEventListener('DOMContentLoaded', function() {
          const fechaInput = document.getElementById('fecha');
          const txtId = document.getElementById('txtId').value;
          
          // Solo establecer fecha actual si NO estamos editando
          if (!txtId || txtId.trim() === "") {
            // CORREGIDO: Obtener fecha local de Colombia (GMT-5)
            const hoy = new Date();
            const year = hoy.getFullYear();
            const month = String(hoy.getMonth() + 1).padStart(2, '0');
            const day = String(hoy.getDate()).padStart(2, '0');
            const fechaLocal = `${year}-${month}-${day}`;
            
            fechaInput.value = fechaLocal;
          }
        });

        </script>
        <br>
      </div>
    </section><!-- End Services Section -->

      <!-- Modales de medios de pago (FUERA de la tabla) -->
      <?php if (!empty($modalesParaRenderizar)): ?>
        <?php foreach ($modalesParaRenderizar as $modalData): ?>
        <div class="modal fade" id="<?php echo $modalData['modalId']; ?>" tabindex="-1" 
            aria-labelledby="<?php echo $modalData['modalId']; ?>Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="<?php echo $modalData['modalId']; ?>Label">
                            <i class="fas fa-credit-card me-2"></i>
                            Factura #<?php echo htmlspecialchars($modalData['consecutivo']); ?>
                        </h5> 
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Metodo de Pago</th>
                                        <th>Cuenta</th>
                                        <th class="text-end">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalMedios = 0;
                                    foreach ($modalData['medios'] as $medio): 
                                        $totalMedios += floatval($medio['valor'] ?? 0);
                                        $partesMedio = explode(' - ', $medio['forma_pago']);
                                        $metodo = htmlspecialchars($partesMedio[0] ?? ($medio['forma_pago'] ?? ''));
                                        $cuenta = !empty($medio['cuenta_contable']) 
                                            ? htmlspecialchars($medio['cuenta_contable'])
                                            : (isset($partesMedio[1]) ? htmlspecialchars($partesMedio[1]) : '');
                                    ?>
                                    <tr>
                                        <td><?php echo $metodo; ?></td>
                                        <td><?php echo $cuenta; ?></td>
                                        <td class="text-end">$<?php echo number_format($medio['valor'] ?? 0, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

    <!-- ======= Footer ======= -->
    <footer id="footer" class="footer-minimalista">
      <p>Universidad de Santander - Ingeniería de Software</p>
      <p>Todos los derechos reservados © 2025</p>
      <p>Creado por iniciativa del programa de Contaduría Pública</p>
    </footer><!-- End Footer -->

  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="../../assets/vendor/aos/aos.js"></script>
  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="../../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="../../assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="../../assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="../../assets/js/main.js"></script>

</body>

</html>