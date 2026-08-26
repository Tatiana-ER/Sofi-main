<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Sofi-main/auth_check.php'; ?>
<?php
require_once '../../config/database.php';
include('../../classes/LibroDiario.php');


$pdo = Database::getConnection();
$libroDiario = new LibroDiario($pdo);

// Obtener consecutivo automático
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(CAST(consecutivo AS UNSIGNED)) AS ultimo FROM docrecibodecaja");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoConsecutivo = $row['ultimo'] ?? 0;
    $nuevoConsecutivo = $ultimoConsecutivo + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}

// Obtener facturas a crédito del cliente con saldo pendiente - MODIFICADO
if (isset($_GET['get_facturas']) && isset($_GET['identificacion'])) {
    $identificacion = $_GET['identificacion'];
    
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            numero_factura,  -- Cambiado de consecutivo a numero_factura
            fecha, 
            fecha_vencimiento,  -- Agregado fecha_vencimiento
            CAST(valorTotal AS DECIMAL(10,2)) as valorTotal,
            COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) as saldoReal
        FROM facturav 
        WHERE identificacion = :identificacion 
        AND formaPago LIKE '%Credito%'
        AND COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) > 0
        ORDER BY fecha ASC
    ");
    $stmt->bindParam(':identificacion', $identificacion);
    $stmt->execute();
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($facturas);
    exit;
}

// Obtener detalles de un recibo para editar
if (isset($_GET['get_detalles']) && isset($_GET['idRecibo'])) {
    $idRecibo = $_GET['idRecibo'];
    
    $stmt = $pdo->prepare("
        SELECT 
            consecutivoFactura,
            valorAplicado,
            fechaVencimiento
        FROM detalle_recibo_caja
        WHERE idRecibo = :idRecibo
        ORDER BY id
    ");
    $stmt->bindParam(':idRecibo', $idRecibo);
    $stmt->execute();
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($detalles);
    exit;
}

// Buscar cliente por identificación
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buscar_cliente'])) {
    $identificacion = $_POST['identificacion'];

    $stmt = $pdo->prepare("
        SELECT cedula, tipoPersona,
               CASE 
                   WHEN tipoPersona = 'Juridica' THEN razonSocial
                   ELSE CONCAT(nombres, ' ', apellidos)
               END AS nombreCompleto
        FROM catalogosterceros
        WHERE cedula = :cedula AND tipoTercero LIKE '%Cliente%'
    ");
    $stmt->bindParam(':cedula', $identificacion);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        echo json_encode([
            "nombre" => $cliente['nombreCompleto'],
            "identificacion" => $cliente['cedula']
        ]);
    } else {
        echo json_encode(["nombre" => "No encontrado o no es un cliente"]);
    }
    exit;
}

// Buscar cliente por nombre (autocompletado)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buscar_cliente_nombre'])) {
    $nombreBuscar = $_POST['nombre'] ?? '';
    $likeNombre = "%$nombreBuscar%";

    $stmt = $pdo->prepare("
        SELECT cedula, tipoPersona,
               CASE 
                   WHEN tipoPersona = 'Juridica' THEN razonSocial
                   ELSE CONCAT(nombres, ' ', apellidos)
               END AS nombreCompleto
        FROM catalogosterceros
        WHERE (CASE WHEN tipoPersona = 'Juridica' THEN razonSocial ELSE CONCAT(nombres, ' ', apellidos) END) LIKE :nombre
          AND tipoTercero LIKE '%Cliente%'
        LIMIT 10
    ");
    $stmt->bindParam(':nombre', $likeNombre, PDO::PARAM_STR);
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($clientes);
    exit;
}

// Variables iniciales - MODIFICADO: fecha actual por defecto
$txtId = $_POST['txtId'] ?? "";
// CORRECCIÓN: Usar $_POST si existe, si no usar fecha actual
$fecha = isset($_POST['fecha']) && !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');
$consecutivo = $_POST['consecutivo'] ?? "";
$identificacion = $_POST['identificacion'] ?? "";
$nombre = $_POST['nombre'] ?? "";
$numeroFactura = $_POST['numeroFactura'] ?? "";
$fechaVencimiento = $_POST['fechaVencimiento'] ?? "";
$valor = $_POST['valor'] ?? "";
$valorTotal = $_POST['valorTotal'] ?? "";
$formaPago = $_POST['formaPago'] ?? "";
$observaciones = $_POST['observaciones'] ?? "";
$accion = $_POST['accion'] ?? "";

// Procesar múltiples medios de pago
$mediosPagoArray = [];
if (isset($_POST['metodosPago']) && is_array($_POST['metodosPago'])) {
    foreach ($_POST['metodosPago'] as $index => $metodoData) {
        if (!empty($metodoData['metodo']) && !empty($metodoData['valor'])) {
            $partes = explode(' - ', $metodoData['metodo']);
            $mediosPagoArray[] = [
                'metodo' => $metodoData['metodo'],
                'forma_pago' => $partes[0] ?? $metodoData['metodo'],
                'cuenta_contable' => $partes[1] ?? '',
                'valor' => floatval($metodoData['valor'])
            ];
        }
    }
}
$mediosPagoRecibo = []; // Se llena en btnEditar

// Datos de facturas (JSON)
$facturasData = $_POST['facturasData'] ?? "";

// Función para actualizar saldos de facturas - MODIFICADO
function actualizarSaldosFacturas($pdo, $facturasData) {
    if (!empty($facturasData)) {
        $dataArray = json_decode($facturasData, true);
        
        foreach ($dataArray as $facturaData) {
            // Obtener información actual de la factura usando numero_factura
            $stmt = $pdo->prepare("
                SELECT 
                    CAST(valorTotal AS DECIMAL(10,2)) as valorTotal,
                    COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) as saldoReal
                FROM facturav 
                WHERE numero_factura = :numero_factura
            ");
            $stmt->execute([':numero_factura' => $facturaData['numero_factura']]);
            $factura = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($factura) {
                // Calcular nuevo saldo
                $nuevoSaldo = $factura['saldoReal'] - floatval($facturaData['valor']);
                
                // Actualizar saldo en la factura
                $stmtUpdate = $pdo->prepare("
                    UPDATE facturav 
                    SET saldoReal = :nuevoSaldo 
                    WHERE numero_factura = :numero_factura
                ");
                $stmtUpdate->execute([
                    ':nuevoSaldo' => $nuevoSaldo,
                    ':numero_factura' => $facturaData['numero_factura']
                ]);
            }
        }
    }
}

// Función para restaurar saldos cuando se elimina o modifica un recibo - MODIFICADO
function restaurarSaldosFacturas($pdo, $idRecibo) {
    // Obtener los detalles del recibo que se va a eliminar/modificar
    $stmt = $pdo->prepare("
        SELECT consecutivoFactura, valorAplicado 
        FROM detalle_recibo_caja 
        WHERE idRecibo = :idRecibo
    ");
    $stmt->execute([':idRecibo' => $idRecibo]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Restaurar saldos usando numero_factura
    foreach ($detalles as $detalle) {
        $stmtUpdate = $pdo->prepare("
            UPDATE facturav 
            SET saldoReal = COALESCE(saldoReal, CAST(valorTotal AS DECIMAL(10,2))) + :valor 
            WHERE numero_factura = :numero_factura
        ");
        $stmtUpdate->execute([
            ':valor' => $detalle['valorAplicado'],
            ':numero_factura' => $detalle['consecutivoFactura']
        ]);
    }
}

switch($accion) {
    case "btnAgregar":
        try {
            $pdo->beginTransaction();

            // Candado de cierre contable: no permitir registrar en un año ya cerrado
            if ($libroDiario->existeCierreActivoParaFecha($fecha)) {
                throw new Exception("No se puede registrar esta factura: el año " . date('Y', strtotime($fecha)) . " ya tiene un cierre contable activo. Si necesitas hacer ajustes, primero revierte el cierre de ese año.");
            }

            // Validar suma de medios de pago contra el valor total
            $sumaMediosPago = array_sum(array_column($mediosPagoArray, 'valor'));
            $diferencia = abs($sumaMediosPago - floatval($valorTotal));

            if ($diferencia > 0.01) {
                $mensajeError = "La suma de los medios de pago (".number_format($sumaMediosPago, 2).") ";
                $mensajeError .= "no coincide con el valor total (".number_format($valorTotal, 2)."). ";
                $mensajeError .= $sumaMediosPago < floatval($valorTotal)
                    ? "Faltan ".number_format(floatval($valorTotal) - $sumaMediosPago, 2)
                    : "Sobran ".number_format($sumaMediosPago - floatval($valorTotal), 2);
                throw new Exception($mensajeError);
            }

            $formaPago = implode(', ', array_column($mediosPagoArray, 'metodo'));
            
            // Validar saldos disponibles antes de procesar - MODIFICADO
            if (!empty($facturasData)) {
                $dataArray = json_decode($facturasData, true);
                
                foreach ($dataArray as $facturaData) {
                    $stmt = $pdo->prepare("
                        SELECT 
                            COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) as saldoReal
                        FROM facturav 
                        WHERE numero_factura = :numero_factura
                    ");
                    $stmt->execute([':numero_factura' => $facturaData['numero_factura']]);
                    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$factura || floatval($facturaData['valor']) > $factura['saldoReal']) {
                        throw new Exception("El valor aplicado a la factura {$facturaData['numero_factura']} excede el saldo disponible");
                    }
                }
            }
            
            // Insertar recibo principal
            $sentencia = $pdo->prepare("INSERT INTO docrecibodecaja(
                fecha, consecutivo, identificacion, nombre, numeroFactura, 
                fechaVencimiento, valor, valorTotal, formaPago, observaciones
            ) VALUES (
                :fecha, :consecutivo, :identificacion, :nombre, :numeroFactura, 
                :fechaVencimiento, :valor, :valorTotal, :formaPago, :observaciones
            )");
            
            $sentencia->bindParam(':fecha', $fecha);
            $sentencia->bindParam(':consecutivo', $consecutivo);
            $sentencia->bindParam(':identificacion', $identificacion);
            $sentencia->bindParam(':nombre', $nombre);
            $sentencia->bindParam(':numeroFactura', $numeroFactura);
            $sentencia->bindParam(':fechaVencimiento', $fechaVencimiento);
            $sentencia->bindParam(':valor', $valor);
            $sentencia->bindParam(':valorTotal', $valorTotal);
            $sentencia->bindParam(':formaPago', $formaPago);
            $sentencia->bindParam(':observaciones', $observaciones);
            $sentencia->execute();
            
            $idRecibo = $pdo->lastInsertId();
            
            // Insertar detalles de facturas - MODIFICADO
            if (!empty($facturasData)) {
                $dataArray = json_decode($facturasData, true);
                
                foreach ($dataArray as $facturaData) {
                    $stmtDetalle = $pdo->prepare("
                        INSERT INTO detalle_recibo_caja 
                        (idRecibo, consecutivoFactura, valorAplicado, fechaVencimiento)
                        VALUES (:idRecibo, :numero_factura, :valor, :fechaVenc)
                    ");
                    $stmtDetalle->execute([
                        ':idRecibo' => $idRecibo,
                        ':numero_factura' => $facturaData['numero_factura'],
                        ':valor' => $facturaData['valor'],
                        ':fechaVenc' => $facturaData['fechaVencimiento'] ?: null
                    ]);
                }
            }

            // Insertar múltiples medios de pago
            if (!empty($mediosPagoArray)) {
                $sqlMedioPago = "INSERT INTO medios_pago_recibo_caja 
                                  (recibo_id, forma_pago, cuenta_contable, nombre_cuenta, valor)
                                  VALUES (:recibo_id, :forma_pago, :cuenta_contable, :nombre_cuenta, :valor)";
                $stmtMedioPago = $pdo->prepare($sqlMedioPago);

                foreach ($mediosPagoArray as $medio) {
                    $stmtMedioPago->execute([
                        ':recibo_id' => $idRecibo,
                        ':forma_pago' => $medio['forma_pago'],
                        ':cuenta_contable' => $medio['cuenta_contable'],
                        ':nombre_cuenta' => $medio['cuenta_contable'],
                        ':valor' => $medio['valor']
                    ]);
                }
            }
            
            // Actualizar saldos de las facturas
            actualizarSaldosFacturas($pdo, $facturasData);

            // ✨ NUEVO: Registrar en Libro Diario
            $libroDiario->registrarReciboCaja($idRecibo);

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=agregado");
            exit();       
            
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
            exit();
        }
        break;

    case "btnModificar":
        try {
            $pdo->beginTransaction();

            // Candado de cierre contable: verificar tanto la fecha original como la nueva
            $stmtFechaOriginal = $pdo->prepare("SELECT fecha FROM docrecibodecaja WHERE id = :id");
            $stmtFechaOriginal->execute([':id' => $txtId]);
            $fechaOriginal = $stmtFechaOriginal->fetchColumn();

            if ($fechaOriginal && $libroDiario->existeCierreActivoParaFecha($fechaOriginal)) {
                throw new Exception("No se puede modificar esta factura: pertenece al año " . date('Y', strtotime($fechaOriginal)) . ", que ya tiene un cierre contable activo.");
            }

            if ($libroDiario->existeCierreActivoParaFecha($fecha)) {
                throw new Exception("No se puede mover esta factura al año " . date('Y', strtotime($fecha)) . ": ese año ya tiene un cierre contable activo.");
            }
            
            // Restaurar saldos de las facturas del recibo original
            restaurarSaldosFacturas($pdo, $txtId);

            // Validar suma de medios de pago contra el valor total
            $sumaMediosPago = array_sum(array_column($mediosPagoArray, 'valor'));
            $diferencia = abs($sumaMediosPago - floatval($valorTotal));

            if ($diferencia > 0.01) {
                $mensajeError = "La suma de los medios de pago (".number_format($sumaMediosPago, 2).") ";
                $mensajeError .= "no coincide con el valor total (".number_format($valorTotal, 2)."). ";
                $mensajeError .= $sumaMediosPago < floatval($valorTotal)
                    ? "Faltan ".number_format(floatval($valorTotal) - $sumaMediosPago, 2)
                    : "Sobran ".number_format($sumaMediosPago - floatval($valorTotal), 2);
                throw new Exception($mensajeError);
            }

            $formaPago = implode(', ', array_column($mediosPagoArray, 'metodo'));
            
            // Validar nuevos saldos - MODIFICADO
            if (!empty($facturasData)) {
                $dataArray = json_decode($facturasData, true);
                
                foreach ($dataArray as $facturaData) {
                    $stmt = $pdo->prepare("
                        SELECT 
                            COALESCE(CAST(saldoReal AS DECIMAL(10,2)), CAST(valorTotal AS DECIMAL(10,2))) as saldoReal
                        FROM facturav 
                        WHERE numero_factura = :numero_factura
                    ");
                    $stmt->execute([':numero_factura' => $facturaData['numero_factura']]);
                    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$factura || floatval($facturaData['valor']) > $factura['saldoReal']) {
                        throw new Exception("El valor aplicado a la factura {$facturaData['numero_factura']} excede el saldo disponible");
                    }
                }
            }
            
            // Actualizar recibo principal
            $sentencia = $pdo->prepare("UPDATE docrecibodecaja SET
                fecha = :fecha,
                consecutivo = :consecutivo,
                identificacion = :identificacion,
                nombre = :nombre,
                numeroFactura = :numeroFactura,
                fechaVencimiento = :fechaVencimiento,
                valor = :valor,
                valorTotal = :valorTotal,
                formaPago = :formaPago,
                observaciones = :observaciones
                WHERE id = :id");
            
            $sentencia->bindParam(':fecha', $fecha);
            $sentencia->bindParam(':consecutivo', $consecutivo);
            $sentencia->bindParam(':identificacion', $identificacion);
            $sentencia->bindParam(':nombre', $nombre);
            $sentencia->bindParam(':numeroFactura', $numeroFactura);
            $sentencia->bindParam(':fechaVencimiento', $fechaVencimiento);
            $sentencia->bindParam(':valor', $valor);
            $sentencia->bindParam(':valorTotal', $valorTotal);
            $sentencia->bindParam(':formaPago', $formaPago);
            $sentencia->bindParam(':observaciones', $observaciones);
            $sentencia->bindParam(':id', $txtId);
            $sentencia->execute();
            
            // Eliminar detalles antiguos
            $stmtDelete = $pdo->prepare("DELETE FROM detalle_recibo_caja WHERE idRecibo = :idRecibo");
            $stmtDelete->execute([':idRecibo' => $txtId]);

            // Eliminar medios de pago antiguos
            $stmtDeleteMedios = $pdo->prepare("DELETE FROM medios_pago_recibo_caja WHERE recibo_id = :idRecibo");
            $stmtDeleteMedios->execute([':idRecibo' => $txtId]);

            // ✨ NUEVO: Eliminar asientos contables antiguos
            $libroDiario->eliminarMovimientos('recibo_caja', $txtId);

            // Restaurar saldos de las facturas del recibo original
            restaurarSaldosFacturas($pdo, $txtId);
            
            // Insertar nuevos detalles - MODIFICADO
            if (!empty($facturasData)) {
                $dataArray = json_decode($facturasData, true);
                
                foreach ($dataArray as $facturaData) {
                    $stmtDetalle = $pdo->prepare("
                        INSERT INTO detalle_recibo_caja 
                        (idRecibo, consecutivoFactura, valorAplicado, fechaVencimiento)
                        VALUES (:idRecibo, :numero_factura, :valor, :fechaVenc)
                    ");
                    $stmtDetalle->execute([
                        ':idRecibo' => $txtId,
                        ':numero_factura' => $facturaData['numero_factura'],
                        ':valor' => $facturaData['valor'],
                        ':fechaVenc' => $facturaData['fechaVencimiento'] ?: null
                    ]);
                }
            }

            if (!empty($mediosPagoArray)) {
                $sqlMedioPago = "INSERT INTO medios_pago_recibo_caja 
                                  (recibo_id, forma_pago, cuenta_contable, nombre_cuenta, valor)
                                  VALUES (:recibo_id, :forma_pago, :cuenta_contable, :nombre_cuenta, :valor)";
                $stmtMedioPago = $pdo->prepare($sqlMedioPago);

                foreach ($mediosPagoArray as $medio) {
                    $stmtMedioPago->execute([
                        ':recibo_id' => $txtId,
                        ':forma_pago' => $medio['forma_pago'],
                        ':cuenta_contable' => $medio['cuenta_contable'],
                        ':nombre_cuenta' => $medio['cuenta_contable'],
                        ':valor' => $medio['valor']
                    ]);
                }
            }
            
            // Actualizar nuevos saldos
            actualizarSaldosFacturas($pdo, $facturasData);

            // ✨ NUEVO: Registrar nuevos asientos contables
            $libroDiario->registrarReciboCaja($txtId);

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=modificado");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
            exit();
        }
        break;

    case "btnEliminar":
      try {
          $pdo->beginTransaction();

          // Candado de cierre contable
          $stmtFechaEliminar = $pdo->prepare("SELECT fecha FROM docrecibodecaja WHERE id = :id");
          $stmtFechaEliminar->execute([':id' => $txtId]);
          $fechaEliminar = $stmtFechaEliminar->fetchColumn();

          if ($fechaEliminar && $libroDiario->existeCierreActivoParaFecha($fechaEliminar)) {
              throw new Exception("No se puede eliminar este recibo: pertenece al año " . date('Y', strtotime($fechaEliminar)) . ", que ya tiene un cierre contable activo.");
          }
          
          // ✨ NUEVO: Eliminar asientos contables
          $libroDiario->eliminarMovimientos('recibo_caja', $txtId);
          
          // Restaurar saldos antes de eliminar
          restaurarSaldosFacturas($pdo, $txtId);
          
          // Eliminar recibo (cascade eliminará los detalles)
          $sentencia = $pdo->prepare("DELETE FROM docrecibodecaja WHERE id = :id");
          $sentencia->bindParam(':id', $txtId);
          $sentencia->execute();
          
          $pdo->commit();
          header("Location: " . $_SERVER['PHP_SELF'] . "?msg=eliminado");
          exit();
          
      } catch (Exception $e) {
          $pdo->rollBack();
          header("Location: " . $_SERVER['PHP_SELF'] . "?msg=error&detalle=" . urlencode($e->getMessage()));
          exit();
      }
  break;

    case "btnEditar":
      // Los datos ya vienen en $_POST desde los campos hidden
      // Cargar medios de pago asociados al recibo
      $stmtMedios = $pdo->prepare("SELECT * FROM medios_pago_recibo_caja WHERE recibo_id = :id");
      $stmtMedios->bindParam(':id', $txtId);
      $stmtMedios->execute();
      $mediosPagoRecibo = $stmtMedios->fetchAll(PDO::FETCH_ASSOC);
      break;
}

// Consulta para mostrar la tabla con información de detalles
$sentencia = $pdo->prepare("
    SELECT 
        r.*,
        (SELECT COUNT(*) FROM detalle_recibo_caja WHERE idRecibo = r.id) as numFacturas
    FROM docrecibodecaja r
    ORDER BY CAST(r.consecutivo AS UNSIGNED) DESC
");
$sentencia->execute();
$lista = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Obtener medios de pago
$mediosPago = [];
$stmt = $pdo->query("SELECT metodoPago, cuentaContable FROM mediosdepago");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mediosPago[] = $row;
    
}
?>

<!-- SweetAlert -->
<?php if (isset($_GET['msg'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const msg = "<?= $_GET['msg'] ?>";
  const detalle = "<?= $_GET['detalle'] ?? '' ?>";
  
  switch (msg) {
    case "agregado":
      Swal.fire({
        icon: 'success',
        title: 'Guardado exitosamente',
        text: 'El recibo de caja se ha agregado correctamente',
        confirmButtonColor: '#103669'
      });
      break;

    case "modificado":
      Swal.fire({
        icon: 'success',
        title: 'Modificado correctamente',
        text: 'Los datos se actualizaron con éxito',
        confirmButtonColor: '#103669'
      });
      break;

    case "eliminado":
      Swal.fire({
        icon: 'success',
        title: 'Eliminado correctamente',
        text: 'El recibo de caja fue eliminado del registro',
        confirmButtonColor: '#103669'
      });
      break;
      
    case "error":
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: detalle || 'Ocurrió un error al procesar la solicitud',
        confirmButtonColor: '#eb0404'
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
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Recibo de Caja - SOFI</title>
  
  <!-- Favicons -->
  <link href="../../assets/img/favicon.png" rel="icon">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Raleway:300,400,500,600,700|Poppins:300,400,500,600,700" rel="stylesheet">
  
  <!-- Vendor CSS -->
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <link href="../../assets/css/improved-style.css" rel="stylesheet">

  <style>

        /* Estilos específicos para la columna de acciones */
    .acciones-col {
        min-width: 280px; /* Ancho mínimo para que quepan todos los botones */
        max-width: 320px;
    }

    .acciones-contenedor {
        display: flex;
        flex-wrap: nowrap;
        gap: 4px;
        justify-content: center;
        align-items: center;
        padding: 2px 0;
    }

    .acciones-contenedor form {
        display: inline-flex;
        margin: 0;
        gap: 3px;
    }

    .factura-row {
      background: #f8f9fa;
      transition: all 0.2s;
    }
    
    .factura-row:hover {
      background: #e9ecef;
    }
    
    .btn-cargar-facturas {
      margin-top: 15px;
      margin-bottom: 10px;
      background: #198754;
      color: white;
      border: none;
      padding: 10px 25px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s;
    }
    
    .btn-cargar-facturas:hover {
      background: #146c43;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .totals {
      margin-top: 20px;
      text-align: right;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 5px;
      border: 2px solid #103669;
    }
    
    .totals label {
      font-weight: bold;
      font-size: 18px;
      color: #103669;
      margin-right: 10px;
    }
    
    .totals input {
      width: 200px;
      font-size: 20px;
      font-weight: bold;
      text-align: right;
      display: inline-block;
      border: 2px solid #103669;
      color: #103669;
    }
    
    .badge {
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .badge-info {
      background: #0dcaf0;
      color: #000;
    }
    
    .badge-warning {
      background: #ffc107;
      color: #000;
    }
    
    .badge-success {
      background: #198754;
      color: #fff;
    }
    
    .info-box {
      background: #e7f3ff;
      border-left: 4px solid #0d6efd;
      padding: 12px;
      margin: 15px 0;
      border-radius: 4px;
    }
    
    .info-box i {
      color: #0d6efd;
      margin-right: 8px;
    }
    
    .form-control:focus {
      border-color: #0d6efd;
      box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .saldo-pendiente {
      font-weight: bold;
      color: #dc3545;
    }
    
    .saldo-info {
      font-size: 12px;
      color: #6c757d;
      font-style: italic;
    }

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

.table-container {
    overflow-x: auto;
    overflow-y: visible;
}

.suggestions-box {
  position: absolute;
  background: #ffffff;
  border: 1px solid #0d6efd;
  border-top: none;
  max-height: 250px;
  overflow-y: auto;
  width: calc(100% - 2px);
  z-index: 1000;
  box-shadow: 0 6px 10px rgba(0,0,0,0.15);
  padding: 0;
  margin: 0;
}
.suggestion-item {
  padding: 10px;
  cursor: pointer;
  border-bottom: 1px solid #f1f1f1;
  transition: background-color 0.2s;
}
.suggestion-item:last-child { border-bottom: none; }
.suggestion-item:hover { background: #e9f5ff; }
.position-relative { position: relative; }

/* Ancho fijo para el menú de acciones (3 puntitos).
   Al moverlo a <body> vía JS para que el scroll de la tabla no lo recorte,
   necesita un ancho explícito: si se deja en "auto" puede calcularse mal
   en el instante justo del reposicionamiento y verse estirado. */
.dropdown-menu {
  width: 220px;
  min-width: 220px;
  max-width: 220px;
}
  </style>
</head>

<body>
  <!-- Header -->
  <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="../../dashboard.php">
          <img src="../../Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link" href="../../dashboard.php">Inicio</a></li>
          <li><a class="nav-link" href="../../perfil.php">Mi Negocio</a></li>
          <li><a class="nav-link" href="../../index.php">Cerrar Sesión</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>

  <!-- Main Section -->
  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='../menus/menudocumentos.php'">
      <i class="fa-solid fa-arrow-left"></i> Regresar
    </button>
    
    <div class="container" data-aos="fade-up">
      <div class="section-title">
        <h2>RECIBO DE CAJA</h2>
        <p>Registre los pagos recibidos de sus clientes</p>
        <p>(Los campos marcados con * son obligatorios)</p>
      </div>
      
      <form id="formReciboCaja" action="" method="post">
        <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">
        <input type="hidden" id="numeroFactura" name="numeroFactura">
        <input type="hidden" id="fechaVencimiento" name="fechaVencimiento">
        <input type="hidden" id="valor" name="valor">
        <input type="hidden" id="facturasData" name="facturasData">

        <!-- Fecha y Consecutivo -->
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="fecha" class="form-label fw-bold">Fecha del Recibo*</label>
            <input type="date" class="form-control" id="fecha" name="fecha"
              value="<?php echo htmlspecialchars($fecha); ?>" required>
          </div>
          <div class="col-md-6">
            <label for="consecutivo" class="form-label fw-bold">Consecutivo*</label>
            <input type="text" class="form-control" id="consecutivo" name="consecutivo"
                   value="<?php echo htmlspecialchars($consecutivo); ?>" readonly required>
          </div>
        </div>

        <!-- Cliente -->
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="identificacion" class="form-label fw-bold">Identificación del Cliente*</label>
            <input type="number" class="form-control" id="identificacion" name="identificacion"
                  placeholder="Ej: 123456789"
                  value="<?php echo htmlspecialchars($identificacion); ?>" required>
          </div>
          <div class="col-md-6 position-relative">
            <label for="nombre" class="form-label fw-bold">Nombre del cliente*</label>
            <input type="text" class="form-control" id="nombre" name="nombre" autocomplete="off"
                  placeholder="Nombre del cliente"
                  value="<?php echo htmlspecialchars($nombre); ?>" required>
            <div id="sugerenciasCliente" class="suggestions-box" style="display:none;"></div>
          </div>
        </div>

        <button type="button" class="btn-cargar-facturas" id="btnCargarFacturas">
          <i class="fas fa-file-invoice-dollar"></i> Cargar Facturas Pendientes
        </button>

        <!-- Tabla de facturas - MODIFICADO: Encabezados cambiados -->
        <div class="table-container">
          <table id="tablaFacturas">
            <thead>
              <tr>
                <th style="width: 12%;">Número Factura</th> <!-- Cambiado de Consecutivo -->
                <th style="width: 12%;">Fecha Factura</th>
                <th style="width: 13%;">Valor Total</th>
                <th style="width: 13%;">Saldo Pendiente</th>
                <th style="width: 13%;">Fecha Vencimiento*</th> <!-- Ahora viene de la BD -->
                <th style="width: 17%;">Valor a Aplicar*</th>
                <th style="width: 10%; text-align: center;">Seleccionar</th>
              </tr>
            </thead>
            <tbody id="facturasBody">
              <tr>
                <td colspan="7" class="text-center" style="padding: 30px;">
                  <i class="fas fa-search" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></i>
                  Ingrese una identificación y cargue las facturas pendientes
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Total -->
        <div class="totals">
          <label for="valorTotal"><i class="fas fa-dollar-sign"></i> VALOR TOTAL:</label>
          <input type="text" id="valorTotal" name="valorTotal" class="form-control" 
                 value="<?php echo htmlspecialchars($valorTotal); ?>" readonly>
        </div>

        <!-- NUEVA SECCION: Multiples medios de pago -->
        <div class="metodos-pago-container">
          <h5 class="fw-bold mb-3">Metodos de Pago</h5>

          <div id="medios-pago-container">
            <?php if (!empty($mediosPagoRecibo)): ?>
              <?php foreach ($mediosPagoRecibo as $index => $medio): ?>
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

        <!-- Observaciones -->
        <div class="mb-3 mt-3">
          <label for="observaciones" class="form-label fw-bold">Observaciones</label>
          <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                    placeholder="Ingrese observaciones adicionales (opcional)"><?php echo htmlspecialchars($observaciones); ?></textarea>
        </div>

        <!-- Botones -->
        <div class="mt-4 mb-4">
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn-agregar" name="accion">
            <i class="fas fa-save"></i> Guardar Recibo
          </button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn-modificar" name="accion" style="display:none;">
            <i class="fas fa-edit"></i> Modificar
          </button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn-eliminar-item" name="accion" style="display:none;">
            <i class="fas fa-trash"></i> Eliminar
          </button>
          <button id="btnCancelar" type="button" class="btn-cancelar" style="display:none;">
            <i class="fas fa-times"></i> Cancelar
          </button>
        </div>
      </form>

      <!-- Tabla de registros -->
      <div class="section-title mt-5">
        <h3>Recibos de Caja Registrados</h3>
      </div>
      
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Consecutivo</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Facturas Aplicadas</th>
              <th>Valor Total</th>
              <th>Forma de Pago</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if(count($lista) > 0): ?>
              <?php foreach($lista as $recibo): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($recibo['consecutivo']); ?></strong></td>
                  <td><?php echo date('d/m/Y', strtotime($recibo['fecha'])); ?></td>
                  <td>
                    <strong><?php echo htmlspecialchars($recibo['nombre']); ?></strong><br>
                    <small class="text-muted">CC: <?php echo htmlspecialchars($recibo['identificacion']); ?></small>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($recibo['numeroFactura']); ?>
                    <br><span class="badge badge-info"><?php echo $recibo['numFacturas']; ?> factura(s)</span>
                  </td>
                  <td><strong style="color: #198754;">$<?php echo number_format($recibo['valorTotal'], 2); ?></strong></td>
                  <td><?php echo htmlspecialchars($recibo['formaPago']); ?></td>
                  <td class="text-center">
                    <div class="dropdown">
                      <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                          <form action="" method="post" class="d-inline">
                            <input type="hidden" name="txtId" value="<?php echo $recibo['id']; ?>">
                            <input type="hidden" name="fecha" value="<?php echo $recibo['fecha']; ?>">
                            <input type="hidden" name="consecutivo" value="<?php echo $recibo['consecutivo']; ?>">
                            <input type="hidden" name="identificacion" value="<?php echo $recibo['identificacion']; ?>">
                            <input type="hidden" name="nombre" value="<?php echo $recibo['nombre']; ?>">
                            <input type="hidden" name="numeroFactura" value="<?php echo $recibo['numeroFactura']; ?>">
                            <input type="hidden" name="fechaVencimiento" value="<?php echo $recibo['fechaVencimiento']; ?>">
                            <input type="hidden" name="valor" value="<?php echo $recibo['valor']; ?>">
                            <input type="hidden" name="valorTotal" value="<?php echo $recibo['valorTotal']; ?>">
                            <input type="hidden" name="formaPago" value="<?php echo $recibo['formaPago']; ?>">
                            <input type="hidden" name="observaciones" value="<?php echo $recibo['observaciones']; ?>">
                            <button type="submit" name="accion" value="btnEditar" class="dropdown-item"><i class="fas fa-edit me-2"></i>Editar</button>
                          </form>
                        </li>
                        <li>
                          <form action="" method="post" class="d-inline">
                            <input type="hidden" name="txtId" value="<?php echo $recibo['id']; ?>">
                            <button type="submit" name="accion" value="btnEliminar" class="dropdown-item text-danger"><i class="fas fa-trash-alt me-2"></i>Eliminar</button>
                          </form>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="ver_recibo_caja.php?id=<?php echo $recibo['id']; ?>" target="_blank"><i class="fas fa-print me-2"></i>Ver / Imprimir</a></li>
                        <li><a class="dropdown-item" href="../../exports/pdf/generar_pdf_recibo_caja.php?id=<?php echo $recibo['id']; ?>" target="_blank"><i class="fas fa-file-pdf me-2"></i>Descargar PDF</a></li>
                        <li><a class="dropdown-item" href="../../exports/excel/generar_excel_recibo_caja.php?id=<?php echo $recibo['id']; ?>" target="_blank"><i class="fas fa-file-excel me-2"></i>Descargar Excel</a></li>
                      </ul>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center" style="padding: 30px;">
                  No hay recibos de caja registrados
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
  </footer>

  <script>
    // Variable global para modo edición
    let modoEdicion = false;

    // Obtener consecutivo
    window.addEventListener('DOMContentLoaded', function() {
      const txtId = document.getElementById('txtId').value;
      
      if (!txtId || txtId.trim() === "") {
        fetch(window.location.pathname + "?get_consecutivo=1")
          .then(r => r.json())
          .then(data => {
            document.getElementById('consecutivo').value = data.consecutivo;
          })
          .catch(err => console.error('Error:', err));
      } else {
        modoEdicion = true;
      }
    });

    // Buscar cliente
    document.getElementById("identificacion").addEventListener("input", function() {
      let identificacion = this.value;
      
      if (identificacion.length > 0) {
        fetch("", {
          method: "POST",
          body: new URLSearchParams({ 
            buscar_cliente: "1",
            identificacion: identificacion 
          }),
          headers: { "Content-Type": "application/x-www-form-urlencoded" }
        })
        .then(r => r.json())
        .then(data => {
          document.getElementById("nombre").value = data.nombre;
        })
        .catch(err => console.error("Error:", err));
      } else {
        document.getElementById("nombre").value = "";
        if (!modoEdicion) {
          document.getElementById("facturasBody").innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 30px;"><i class="fas fa-search" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></i>Ingrese una identificación</td></tr>';
        }
      }
    });

    // Buscar cliente por nombre (autocompletado) - usando el mismo campo "nombre"
    const inputNombreCliente = document.getElementById("nombre");
    const sugerenciasCliente = document.getElementById("sugerenciasCliente");

    inputNombreCliente.addEventListener("input", function () {
        const valor = this.value.trim();
        if (valor.length >= 3) {
            fetch("", {
                method: "POST",
                body: new URLSearchParams({ buscar_cliente_nombre: "1", nombre: valor }),
                headers: { "Content-Type": "application/x-www-form-urlencoded" }
            })
            .then(r => r.json())
            .then(data => {
                sugerenciasCliente.innerHTML = "";
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(c => {
                        const div = document.createElement("div");
                        div.className = "suggestion-item";
                        div.innerHTML = `<strong>${c.cedula}</strong> - ${c.nombreCompleto}`;
                        div.addEventListener("click", () => {
                            document.getElementById("identificacion").value = c.cedula;
                            inputNombreCliente.value = c.nombreCompleto;
                            sugerenciasCliente.style.display = "none";
                            // Dispara la carga de facturas pendientes para este cliente
                            document.getElementById("identificacion").dispatchEvent(new Event("input"));
                        });
                        sugerenciasCliente.appendChild(div);
                    });
                    sugerenciasCliente.style.display = "block";
                } else {
                    sugerenciasCliente.style.display = "none";
                }
            })
            .catch(err => console.error("Error:", err));
        } else {
            sugerenciasCliente.style.display = "none";
        }
    });

    document.addEventListener("click", function (e) {
        if (!e.target.closest("#nombre") && !e.target.closest("#sugerenciasCliente")) {
            sugerenciasCliente.style.display = "none";
        }
    });

    // Cargar facturas pendientes
    document.getElementById("btnCargarFacturas").addEventListener("click", function() {
      const identificacion = document.getElementById("identificacion").value;
      const txtId = document.getElementById("txtId").value;
      
      if (!identificacion) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'Debe ingresar una identificación primero',
          confirmButtonColor: '#103669'
        });
        return;
      }
      
      const tbody = document.getElementById("facturasBody");
      tbody.innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando facturas...</td></tr>';
      
      fetch(`?get_facturas=1&identificacion=${identificacion}`)
        .then(r => r.json())
        .then(facturas => {
          if (facturas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 30px;"><i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; display: block; margin-bottom: 10px;"></i><strong>¡Excelente!</strong><br>Este cliente no tiene facturas pendientes por pagar</td></tr>';
            return;
          }
          
          tbody.innerHTML = "";
          
          // Si estamos editando, cargar los detalles previos
          if (txtId && txtId.trim() !== "") {
            fetch(`?get_detalles=1&idRecibo=${txtId}`)
              .then(r => r.json())
              .then(detalles => {
                renderFacturas(facturas, detalles);
              })
              .catch(err => {
                console.error(err);
                renderFacturas(facturas, []);
              });
          } else {
            renderFacturas(facturas, []);
          }
        })
        .catch(err => {
          console.error(err);
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar las facturas</td></tr>';
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar las facturas',
            confirmButtonColor: '#eb0404'
          });
        });
    });

    // Renderizar facturas con o sin detalles previos - MODIFICADO
    function renderFacturas(facturas, detalles) {
      const tbody = document.getElementById("facturasBody");
      tbody.innerHTML = "";
      
      facturas.forEach(f => {
        const tr = document.createElement("tr");
        tr.className = "factura-row";
        tr.dataset.numero_factura = f.numero_factura; // Cambiado de consecutivo
        tr.dataset.valorTotal = f.valorTotal;
        tr.dataset.saldoReal = f.saldoReal;
        
        // Buscar si esta factura estaba previamente seleccionada
        const detalleExistente = detalles.find(d => d.consecutivoFactura === f.numero_factura); // Cambiado
        
        // Calcular porcentaje de saldo
        const porcentajeSaldo = (parseFloat(f.saldoReal) / parseFloat(f.valorTotal)) * 100;
        let badgeClass = 'badge-success';
        if (porcentajeSaldo > 75) badgeClass = 'badge-warning';
        if (porcentajeSaldo === 100) badgeClass = 'badge-info';
        
        tr.innerHTML = `
          <td><strong>${f.numero_factura || f.consecutivo}</strong></td> <!-- Cambiado -->
          <td>${formatDate(f.fecha)}</td>
          <td><strong>${parseFloat(f.valorTotal).toFixed(2)}</strong></td>
          <td>
            <span class="saldo-pendiente">${parseFloat(f.saldoReal).toFixed(2)}</span>
            <br>
            <span class="badge ${badgeClass}">${porcentajeSaldo.toFixed(0)}% pendiente</span>
          </td>
          <td>
            <input type="date" 
                   class="form-control fecha-venc" 
                   value="${f.fecha_vencimiento || (detalleExistente ? detalleExistente.fechaVencimiento : '')}"
                   placeholder="Fecha vencimiento">
          </td>
          <td>
            <input type="number" 
                   class="form-control valor-aplicar" 
                   step="0.01" 
                   min="0" 
                   max="${parseFloat(f.saldoReal).toFixed(2)}"
                   value="${detalleExistente ? parseFloat(detalleExistente.valorAplicado).toFixed(2) : ''}"
                   placeholder="Máx: ${parseFloat(f.saldoReal).toFixed(2)}">
            <small class="saldo-info">Máximo: ${parseFloat(f.saldoReal).toFixed(2)}</small>
          </td>
          <td style="text-align: center;">
            <input type="checkbox" 
                   class="factura-checkbox form-check-input" 
                   style="width: 20px; height: 20px;"
                   ${detalleExistente ? 'checked' : ''}>
          </td>
        `;
        
        tbody.appendChild(tr);
      });
      
      // Agregar eventos
      document.querySelectorAll(".valor-aplicar").forEach(input => {
        input.addEventListener("input", function() {
          const row = this.closest('.factura-row');
          const saldoReal = parseFloat(row.dataset.saldoReal);
          const valor = parseFloat(this.value) || 0;
          
          if (valor > saldoReal) {
            Swal.fire({
              icon: 'warning',
              title: 'Valor excedido',
              text: `El valor no puede ser mayor al saldo pendiente (${saldoReal.toFixed(2)})`,
              confirmButtonColor: '#103669'
            });
            this.value = saldoReal.toFixed(2);
          }
          
          calcularTotal();
        });
      });
      
      // NUEVO: Evento para selección automática del valor - REQUERIMIENTO 06
      document.querySelectorAll(".factura-checkbox").forEach(checkbox => {
        checkbox.addEventListener("change", function() {
          const row = this.closest('.factura-row');
          const valorInput = row.querySelector('.valor-aplicar');
          const saldoReal = parseFloat(row.dataset.saldoReal);
          
          if (this.checked && valorInput) {
            // REQUERIMIENTO 06: Seleccionar automáticamente el valor pendiente
            valorInput.value = saldoReal.toFixed(2);
          } else if (valorInput) {
            valorInput.value = '';
          }
          
          calcularTotal();
        });
      });
      
      // Calcular total inicial si hay detalles
      if (detalles.length > 0) {
        calcularTotal();
      }
    }

    // Formatear fecha
    function formatDate(dateStr) {
      if (!dateStr) return 'N/A';
      const d = new Date(dateStr + 'T00:00:00');
      return d.toLocaleDateString('es-CO');
    }

    let contadorMediosPago = 1;

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
    calcularTotalMediosPago();
}

function eliminarMedioPago(button) {
    const row = button.closest('.metodo-pago-row');
    if (document.querySelectorAll('.metodo-pago-row').length > 1) {
        row.remove();
        calcularTotalMediosPago();
    } else {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe haber al menos un método de pago', confirmButtonColor: '#103669' });
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

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.valor-metodo').forEach(input => input.addEventListener('input', calcularTotalMediosPago));
    calcularTotalMediosPago();
});

    // Calcular total - MODIFICADO
    function calcularTotal() {
      let total = 0;
      let facturasSeleccionadas = [];
      let fechasVencimiento = [];
      let valoresAplicados = [];
      let facturasData = [];
      
      document.querySelectorAll(".factura-row").forEach(row => {
        const checkbox = row.querySelector(".factura-checkbox");
        const valorInput = row.querySelector(".valor-aplicar");
        const fechaVencInput = row.querySelector(".fecha-venc");
        
        if (checkbox && checkbox.checked && valorInput && valorInput.value) {
          const valor = parseFloat(valorInput.value) || 0;
          
          if (valor > 0) {
            total += valor;
            facturasSeleccionadas.push(row.dataset.numero_factura); // Cambiado
            valoresAplicados.push(valor.toFixed(2));
            
            const fechaVenc = fechaVencInput && fechaVencInput.value ? fechaVencInput.value : '';
            if (fechaVenc) {
              fechasVencimiento.push(fechaVenc);
            }
            
            // Agregar a array de objetos para JSON - MODIFICADO
            facturasData.push({
              numero_factura: row.dataset.numero_factura, // Cambiado
              valor: valor.toFixed(2),
              fechaVencimiento: fechaVenc
            });
          }

        }
      });
      
      document.getElementById("valorTotal").value = total.toFixed(2);
      document.getElementById("numeroFactura").value = facturasSeleccionadas.join(', ');
      document.getElementById("valor").value = valoresAplicados.join(', ');
      document.getElementById("fechaVencimiento").value = fechasVencimiento.join(', ');

      document.getElementById("facturasData").value = JSON.stringify(facturasData);

      // Autoactualizar el primer medio de pago si solo hay uno
      const valorInputs = document.querySelectorAll('.valor-metodo');
      if (valorInputs.length === 1 && !modoEdicion) {
          valorInputs[0].value = total.toFixed(2);
      }
      calcularTotalMediosPago();
    }

    // Modo agregar/editar
    document.addEventListener("DOMContentLoaded", function() {
      const id = document.getElementById("txtId").value;
      const btnAgregar = document.getElementById("btnAgregar");
      const btnModificar = document.getElementById("btnModificar");
      const btnEliminar = document.getElementById("btnEliminar");
      const btnCancelar = document.getElementById("btnCancelar");
      const form = document.getElementById("formReciboCaja");

      function modoAgregar() {
        btnAgregar.style.display = "inline-block";
        btnModificar.style.display = "none";
        btnEliminar.style.display = "none";
        btnCancelar.style.display = "none";

        form.querySelectorAll("input, select, textarea").forEach(el => {
          if (el.type === "checkbox") {
            el.checked = false;
          } else if (el.id !== "consecutivo" && el.type !== "hidden") {
            el.value = "";
          }
        });

        document.getElementById("txtId").value = "";
        document.getElementById("facturasBody").innerHTML = '<tr><td colspan="7" class="text-center" style="padding: 30px;"><i class="fas fa-search" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></i>Seleccione un cliente y cargue las facturas pendientes</td></tr>';
        modoEdicion = false;
      }

      if (id && id.trim() !== "") {
        btnAgregar.style.display = "none";
        btnModificar.style.display = "inline-block";
        btnEliminar.style.display = "inline-block";
        btnCancelar.style.display = "inline-block";
        modoEdicion = true;
        
        // Auto-cargar facturas si hay identificación
        const identificacion = document.getElementById("identificacion").value;
        if (identificacion) {
          setTimeout(() => {
            document.getElementById("btnCargarFacturas").click();
          }, 500);
        }
      } else {
        modoAgregar();
      }

      btnCancelar.addEventListener("click", function(e) {
        e.preventDefault();
        window.location.href = window.location.pathname;
      });
    });

    // Validación antes de enviar
    document.getElementById("formReciboCaja").addEventListener("submit", function(e) {
      const accion = e.submitter?.value;
      
      if (accion === "btnAgregar" || accion === "btnModificar") {
        const facturasData = document.getElementById("facturasData").value;
        const valorTotal = document.getElementById("valorTotal").value;
        
        if (!facturasData || facturasData === "[]") {
          e.preventDefault();
          Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe seleccionar al menos una factura y asignar un valor',
            confirmButtonColor: '#103669'
          });
          return false;
        }
        
        if (!valorTotal || parseFloat(valorTotal) <= 0) {
          e.preventDefault();
          Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'El valor total debe ser mayor a cero',
            confirmButtonColor: '#103669'
          });
          return false;
        }

        const totalMediosPago = calcularTotalMediosPago(false);
        const valorTotalNum = parseFloat(valorTotal) || 0;

        if (Math.abs(totalMediosPago - valorTotalNum) > 0.01) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'La suma de los medios de pago no coincide con el valor total',
                confirmButtonColor: '#103669'
            });
            return false;
        }
      }
    });

    // Confirmaciones
    document.addEventListener("DOMContentLoaded", () => {
      const forms = document.querySelectorAll("form");

      forms.forEach((form) => {
        form.addEventListener("submit", function (e) {
          const boton = e.submitter;
          const accion = boton?.value;

          if (accion === "btnModificar" || accion === "btnEliminar") {
            e.preventDefault();

            let titulo = accion === "btnModificar" ? "¿Guardar cambios?" : "¿Eliminar recibo?";
            let texto = accion === "btnModificar"
              ? "Se actualizarán los datos de este recibo de caja y los saldos de las facturas."
              : "Esta acción eliminará el recibo y restaurará los saldos de las facturas afectadas.";

            Swal.fire({
              title: titulo,
              text: texto,
              icon: "warning",
              showCancelButton: true,
              confirmButtonText: "Sí, continuar",
              cancelButtonText: "Cancelar",
              confirmButtonColor: accion === "btnModificar" ? "#b28b54" : "#eb0404",
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

    // Solución: mover el menú desplegable a <body> para que no lo recorte el scroll de la tabla
    document.addEventListener('show.bs.dropdown', function (e) {
        const button = e.target;
        const menu = button.nextElementSibling; // el <ul class="dropdown-menu">

        if (!menu || !menu.classList.contains('dropdown-menu')) return;

        // Guarda dónde estaba originalmente para devolverlo después
        menu._originalParent = menu.parentNode;
        menu._originalNextSibling = menu.nextSibling;

        document.body.appendChild(menu);
        menu.style.position = 'fixed';
        menu.style.zIndex = '3000';
        menu.style.display = 'block';
        menu.style.width = '220px'; // ancho fijo: evita que se estire al reposicionar

        const posicionar = () => {
            const rect = button.getBoundingClientRect();
            const menuAncho = menu.offsetWidth;

            // Alinea el borde derecho del menú con el borde derecho del botón (como dropdown-menu-end)
            let left = rect.right - menuAncho;
            if (left < 8) left = 8; // evita que se salga por la izquierda

            let top = rect.bottom + 4;
            // Si no cabe abajo, lo abre hacia arriba
            if (top + menu.offsetHeight > window.innerHeight) {
                top = rect.top - menu.offsetHeight - 4;
            }

            menu.style.top = `${top}px`;
            menu.style.left = `${left}px`;
        };

        posicionar();
        // Reposiciona si se hace scroll o resize mientras el menú está abierto
        window.addEventListener('scroll', posicionar, true);
        window.addEventListener('resize', posicionar);
        menu._posicionar = posicionar;
    });

    document.addEventListener('hide.bs.dropdown', function (e) {
        const button = e.target;
        const menu = button.nextElementSibling?.classList.contains('dropdown-menu')
            ? button.nextElementSibling
            : document.body.querySelector('.dropdown-menu[style*="position: fixed"]');

        if (!menu || !menu._originalParent) return;

        window.removeEventListener('scroll', menu._posicionar, true);
        window.removeEventListener('resize', menu._posicionar);

        // Lo regresa a su lugar original en la fila de la tabla
        if (menu._originalNextSibling) {
            menu._originalParent.insertBefore(menu, menu._originalNextSibling);
        } else {
            menu._originalParent.appendChild(menu);
        }
        menu.style.position = '';
        menu.style.zIndex = '';
        menu.style.top = '';
        menu.style.left = '';
        menu.style.display = '';
        menu.style.width = '';
    });
  </script>

  <!-- Vendor JS -->
  <script src="../../assets/vendor/aos/aos.js"></script>
  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="../../assets/js/main.js"></script>

</body>
</html>