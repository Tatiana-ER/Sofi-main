<?php
include("connection.php");
include("LibroDiario.php");

$conn = new connection();
$pdo = $conn->connect();
$libroDiario = new LibroDiario($pdo);

// Obtener el siguiente consecutivo 
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(CAST(consecutivo AS UNSIGNED)) AS ultimo FROM facturac");
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

if (isset($_POST['detalles'])) {
    $_POST['detalles'] = json_decode($_POST['detalles'], true);
}

switch($accion){
  case "btnAgregar":
    try {
        $pdo->beginTransaction();

        // Insertar factura - CORREGIDO: Vincular todos los parámetros
        $sentencia=$pdo->prepare("INSERT INTO facturac(identificacion,nombre,fecha,consecutivo,numeroFactura,formaPago,fecha_vencimiento,subtotal,ivaTotal,retenciones,valorTotal,observaciones,retencion_tarifa) 
        VALUES (:identificacion,:nombre,:fecha,:consecutivo,:numeroFactura,:formaPago,:fecha_vencimiento,:subtotal,:ivaTotal,:retenciones,:valorTotal,:observaciones,:retencion_tarifa)");
        
        // Vincular todos los parámetros - ESTO FALTABA
        $sentencia->bindParam(':identificacion',$identificacion);
        $sentencia->bindParam(':nombre',$nombre);
        $sentencia->bindParam(':fecha',$fecha);
        $sentencia->bindParam(':consecutivo',$consecutivo);
        $sentencia->bindParam(':numeroFactura',$numeroFactura);
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

        // Insertar detalles y actualizar inventario - MODIFICADO: actualizar costo
        if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
          $sqlDetalle = "INSERT INTO detallefacturac 
                        (factura_id, codigoProducto, nombreProducto, cantidad, precioUnitario, iva, valorTotal)
                        VALUES (:factura_id, :codigoProducto, :nombreProducto, :cantidad, :precioUnitario, :iva, :valorTotal)";
          $stmtDetalle = $pdo->prepare($sqlDetalle);

          $checkItem = $pdo->prepare("SELECT tipoItem, cantidad, costoUnitario FROM productoinventarios WHERE codigoProducto = :codigo");

          foreach ($_POST['detalles'] as $detalle) {
              $checkItem->execute([':codigo' => $detalle['codigoProducto']]);
              $item = $checkItem->fetch(PDO::FETCH_ASSOC);

              if (!$item) {
                  throw new Exception("El código {$detalle['codigoProducto']} no existe en el inventario.");
              }

              // Solo actualizar costo si es PRODUCTO (no servicio)
              // NOTA: La cantidad se actualiza mediante un trigger en la base de datos
              if (strtolower($item['tipoItem']) === 'producto') {
                  $nuevoCosto = $detalle['precio'];
                  
                  // Solo actualizar costo, NO cantidad (el trigger maneja la cantidad)
                  $updateCost = $pdo->prepare("UPDATE productoinventarios SET 
                                              costoUnitario = :costoUnitario
                                              WHERE codigoProducto = :codigo");
                  
                  $updateCost->execute([
                      ':costoUnitario' => $nuevoCosto,
                      ':codigo' => $detalle['codigoProducto']
                  ]);
              }

              $stmtDetalle->execute([
                  ':factura_id' => $idFactura,
                  ':codigoProducto' => $detalle['codigoProducto'],
                  ':nombreProducto' => $detalle['nombreProducto'],
                  ':cantidad' => $detalle['cantidad'],
                  ':precioUnitario' => $detalle['precio'],
                  ':iva' => $detalle['iva'],
                  ':valorTotal' => $detalle['precioTotal']
              ]);
          }
      }

        // Registrar en Libro Diario
        $libroDiario->registrarFacturaCompra($idFactura);

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

        // Eliminar asientos contables antiguos
        $libroDiario->eliminarMovimientos('factura_compra', $txtId);

        // Obtener detalles antiguos para restaurar inventario
        $stmtOldDetails = $pdo->prepare("
            SELECT fd.codigoProducto, fd.cantidad, pi.tipoItem, pi.costoUnitario
            FROM detallefacturac fd
            INNER JOIN productoinventarios pi ON fd.codigoProducto = pi.codigoProducto
            WHERE fd.factura_id = :factura_id
        ");
        $stmtOldDetails->execute([':factura_id' => $txtId]);
        $oldDetails = $stmtOldDetails->fetchAll(PDO::FETCH_ASSOC);

        // Restaurar inventario (revertir la compra anterior) - MODIFICADO: restaurar costo anterior
        $restoreStockAndCost = $pdo->prepare("UPDATE productoinventarios SET 
                                            cantidad = cantidad - :cantidad,
                                            costoUnitario = :costoUnitario
                                            WHERE codigoProducto = :codigo");
        foreach ($oldDetails as $old) {
            if (strtolower($old['tipoItem']) === 'producto') {
                $restoreStockAndCost->execute([
                    ':cantidad' => $old['cantidad'],
                    ':costoUnitario' => $old['costoUnitario'], // Restaurar costo anterior
                    ':codigo' => $old['codigoProducto']
                ]);
            }
        }

        // Actualizar factura 
        $sentencia = $pdo->prepare("UPDATE facturac 
                                    SET identificacion = :identificacion,
                                        nombre = :nombre,
                                        fecha = :fecha,
                                        consecutivo = :consecutivo,
                                        numeroFactura = :numeroFactura,
                                        formaPago = :formaPago,
                                        fecha_vencimiento = :fecha_vencimiento,
                                        subtotal = :subtotal,
                                        ivaTotal = :ivaTotal,
                                        retenciones = :retenciones,
                                        valorTotal = :valorTotal,
                                        observaciones = :observaciones,
                                        retencion_tarifa = :retencion_tarifa
                                    WHERE id = :id");

        // ... (parámetros de la factura - asegúrate de que todos estén vinculados)
        $sentencia->bindParam(':identificacion', $identificacion);
        $sentencia->bindParam(':nombre', $nombre);
        $sentencia->bindParam(':fecha', $fecha);
        $sentencia->bindParam(':consecutivo', $consecutivo);
        $sentencia->bindParam(':numeroFactura', $numeroFactura);
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
        $deleteDetalle = $pdo->prepare("DELETE FROM detallefacturac WHERE factura_id = :factura_id");
        $deleteDetalle->bindParam(':factura_id', $txtId);
        $deleteDetalle->execute();

        // Insertar detalles y actualizar inventario
        if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
            $sqlDetalle = "INSERT INTO detallefacturac 
                          (factura_id, codigoProducto, nombreProducto, cantidad, precioUnitario, iva, valorTotal)
                          VALUES (:factura_id, :codigoProducto, :nombreProducto, :cantidad, :precioUnitario, :iva, :valorTotal)";
            $stmtDetalle = $pdo->prepare($sqlDetalle);

            $checkItem = $pdo->prepare("SELECT tipoItem, cantidad, costoUnitario FROM productoinventarios WHERE codigoProducto = :codigo");
            
            // <CHANGE> Preparar UPDATE de forma más segura - Se prepara DENTRO del foreach para evitar reutilización
            // que puede causar duplicación de updates

            foreach ($_POST['detalles'] as $detalle) {
                // Validar que el producto existe
                $checkItem->execute([':codigo' => $detalle['codigoProducto']]);
                $item = $checkItem->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    throw new Exception("El código {$detalle['codigoProducto']} no existe en el inventario.");
                }

               // Insertar detalle PRIMERO
                $stmtDetalle->execute([
                    ':factura_id' => $txtId,  // CORREGIDO: usar $txtId en lugar de $idFactura
                    ':codigoProducto' => $detalle['codigoProducto'],
                    ':nombreProducto' => $detalle['nombreProducto'],
                    ':cantidad' => $detalle['cantidad'],
                    ':precioUnitario' => $detalle['precio'],
                    ':iva' => $detalle['iva'],
                    ':valorTotal' => $detalle['precioTotal']
                ]);

                // <CHANGE> Actualizar inventario SOLO para productos (no servicios)
                // Preparar el UPDATE cada vez para evitar problemas de reutilización de statements
                if (strtolower($item['tipoItem']) === 'producto') {
                    $nuevoCosto = $detalle['precio'];
                    
                    $updateStockAndCost = $pdo->prepare("UPDATE productoinventarios SET 
                                                        cantidad = cantidad + :cantidad,
                                                        costoUnitario = :costoUnitario
                                                        WHERE codigoProducto = :codigo");
                    
                    $resultUpdate = $updateStockAndCost->execute([
                        ':cantidad' => $detalle['cantidad'],
                        ':costoUnitario' => $nuevoCosto,
                        ':codigo' => $detalle['codigoProducto']
                    ]);

                    // <CHANGE> Validar que el UPDATE se ejecutó correctamente
                    if (!$resultUpdate) {
                        throw new Exception("Error al actualizar el inventario del producto {$detalle['codigoProducto']}");
                    }

                    // <CHANGE> Verificar que se actualizó al menos una fila
                    $rowsAffected = $updateStockAndCost->rowCount();
                    if ($rowsAffected === 0) {
                        throw new Exception("No se pudo actualizar el inventario: producto {$detalle['codigoProducto']} no encontrado");
                    }
                }
            }
        }

        // Registrar nuevos asientos contables
        $libroDiario->registrarFacturaCompra($txtId);

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
        $libroDiario->eliminarMovimientos('factura_compra', $txtId);

        // Obtener detalles para restaurar inventario
        $stmtDetails = $pdo->prepare("
            SELECT fd.codigoProducto, fd.cantidad, pi.tipoItem 
            FROM detallefacturac fd
            INNER JOIN productoinventarios pi ON fd.codigoProducto = pi.codigoProducto
            WHERE fd.factura_id = :factura_id
        ");
        $stmtDetails->execute([':factura_id' => $txtId]);
        $details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

        // Restaurar inventario (revertir la compra)
        $restoreStock = $pdo->prepare("UPDATE productoinventarios SET cantidad = cantidad - :cantidad WHERE codigoProducto = :codigo");
        foreach ($details as $detail) {
            if (strtolower($detail['tipoItem']) === 'producto') {
                $restoreStock->execute([
                    ':cantidad' => $detail['cantidad'],
                    ':codigo' => $detail['codigoProducto']
                ]);
            }
        }

        // Eliminar detalles
        $sentenciaDetalle = $pdo->prepare("DELETE FROM detallefacturac WHERE factura_id = :id");
        $sentenciaDetalle->bindParam(':id', $txtId);
        $sentenciaDetalle->execute();

        // Eliminar factura
        $sentencia = $pdo->prepare("DELETE FROM facturac WHERE id = :id");
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
      $sentencia = $pdo->prepare("SELECT * FROM facturac WHERE id = :id");
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();
      $factura = $sentencia->fetch(PDO::FETCH_ASSOC);

      if ($factura) {
          $identificacion = $factura['identificacion'];
          $nombre = $factura['nombre'];
          $fecha = $factura['fecha'];
          $consecutivo = $factura['consecutivo'];
          $numeroFactura = $factura['numeroFactura'] ?? "";
          $formaPago = $factura['formaPago'];
          $fechaVencimiento = $factura['fecha_vencimiento'] ?? "";
          $subtotal = $factura['subtotal'];
          $ivaTotal = $factura['ivaTotal'];
          $retenciones = $factura['retenciones'];
          $valorTotal = $factura['valorTotal'];
          $observaciones = $factura['observaciones'];
          $selectRetencion = $factura['retencion_tarifa'] ?? "";
      }

      // Cargar detalles asociados
      $stmtDetalle = $pdo->prepare("SELECT * FROM detallefacturac WHERE factura_id = :id_factura");
      $stmtDetalle->bindParam(':id_factura', $txtId);
      $stmtDetalle->execute();
      $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
  break;
}

$sentencia= $pdo->prepare("SELECT * FROM `facturac` WHERE 1");
$sentencia->execute();
$lista=$sentencia->fetchALL(PDO::FETCH_ASSOC);

// Buscar proveedor por identificación o nombre
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['identificacion']) || isset($_POST['nombreProveedor'])) && !isset($_POST['accion'])) {

    $identificacion = $_POST['identificacion'] ?? '';
    $nombreProveedor = $_POST['nombreProveedor'] ?? '';
    $proveedor = null;

    if (!empty($identificacion)) {
        $stmt = $pdo->prepare("
            SELECT cedula, CONCAT(nombres, ' ', apellidos) AS nombreCompleto
            FROM catalogosterceros
            WHERE cedula = :cedula AND tipoTercero LIKE '%Proveedor%'
            LIMIT 1
        ");
        $stmt->bindParam(':cedula', $identificacion, PDO::PARAM_STR);
        $stmt->execute();
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (!empty($nombreProveedor)) {
        $likeNombre = "%$nombreProveedor%";
        $stmt = $pdo->prepare("
            SELECT cedula, CONCAT(nombres, ' ', apellidos) AS nombreCompleto
            FROM catalogosterceros
            WHERE CONCAT(nombres, ' ', apellidos) LIKE :nombre AND tipoTercero LIKE '%Proveedor%'
            LIMIT 1
        ");
        $stmt->bindParam(':nombre', $likeNombre, PDO::PARAM_STR);
        $stmt->execute();
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($proveedor) {
        echo json_encode([
            "nombre" => $proveedor['nombreCompleto'],
            "identificacion" => $proveedor['cedula']
        ]);
    } else {
        echo json_encode(["nombre" => "No encontrado o no es proveedor"]);
    }
    exit;
}

// Buscar producto por código (para el select) - MODIFICADO: obtener costoUnitario
if (isset($_POST['codigoProducto'])) {
    $codigo = trim($_POST['codigoProducto']);
    $producto = null;

    if ($codigo !== '') {
        $stmt = $pdo->prepare("SELECT codigoProducto, descripcionProducto, cantidad, tipoItem, precioUnitario, costoUnitario 
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
            "precioUnitario" => $producto['precioUnitario'] ?? 0,
            "costoUnitario" => $producto['costoUnitario'] ?? 0 // NUEVO: retornar costo
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
        text: 'La factura de compra se ha agregado y el inventario se ha actualizado',
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
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <link href="assets/css/improved-style.css" rel="stylesheet">

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
    .btn-remove {
      margin-left: 10px;
      background-color: red;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      padding: 5px 10px;
    }
    .btn-add {
      background-color: #0d6efd;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      padding: 5px 10px;
    }
  </style>

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="dashboard.php">
          <img src="./Img/logosofi1.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li>
            <a class="nav-link scrollto active" href="dashboard.php" style="color: darkblue;">Inicio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="perfil.php" style="color: darkblue;">Mi Negocio</a>
          </li>
          <li>
            <a class="nav-link scrollto active" href="index.php" style="color: darkblue;">Cerrar Sesión</a>
          </li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav><!-- .navbar -->
    </div>
  </header><!-- End Header -->

    <!-- ======= Services Section ======= -->
    <section id="services" class="services">
      <button class="btn-ir" onclick="window.location.href='menudocumentos.php'">
        <i class="fa-solid fa-arrow-left"></i> Regresar
      </button>
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>FACTURA DE COMPRA</h2>
          <p>Para crear una nueva factura de compra diligencie los campos a continuación:</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>
        
        <form id="formFacturaCompras" action="" method="post" class="container mt-3">

          <!-- ID oculto -->
          <input type="hidden" value="<?php echo $txtId; ?>" id="txtId" name="txtId">

          <!-- Identificación y Nombre -->
          <div class="row g-3">
            <div class="col-md-4">
              <label for="identificacion" class="form-label fw-bold">Identificación del Proveedor (NIT o CC)*</label>
              <input type="number" class="form-control" id="identificacion" name="identificacion"
                    placeholder="Ej: 123456789"
                    value="<?php echo $identificacion; ?>" required>
            </div>

            <div class="col-md-8">
              <label for="nombre" class="form-label fw-bold">Nombre del proveedor</label>
              <input type="text" class="form-control" id="nombre" name="nombre"
                    placeholder="Nombre del proveedor"
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
                    placeholder="Ej: FC-001"
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
                      <td><input type="number" name="precio" class="form-control unit-price" value="<?= htmlspecialchars($detalle['precioUnitario']) ?>"></td>
                      <td><input type="number" name="iva" class="form-control iva" value="<?= htmlspecialchars($detalle['iva']) ?>" readonly></td>
                      <td><input type="number" name="precioTotal" class="form-control total-price" value="<?= htmlspecialchars($detalle['valorTotal']) ?>" readonly></td>
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

          <!-- Forma de Pago -->
          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label for="formaPago" class="form-label fw-bold">Forma de Pago*</label>
              <select class="form-select" id="formaPago" name="formaPago" required onchange="mostrarFechaVencimiento()">
                <option value="">Seleccione una opción</option>
                <?php foreach ($mediosPago as $medio): ?>
                  <option value="<?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>"
                    <?= ($formaPago == $medio['metodoPago']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <!-- Campo de Fecha de Vencimiento (oculto inicialmente) -->
            <div class="col-md-6" id="fechaVencimientoContainer" style="display: none;">
              <label for="fechaVencimiento" class="form-label fw-bold">Fecha de Vencimiento*</label>
              <input type="date" class="form-control" id="fechaVencimiento" name="fechaVencimiento"
                    value="<?php echo $fechaVencimiento ?? ''; ?>" min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <!-- Selector de retención -->
            <div class="col-md-6" id="retencionContainer">
              <label for="selectRetencion" class="form-label fw-bold">Retención aplicable</label>
              <select class="form-select" id="selectRetencion" name="selectRetencion">
                <option value="">Seleccione una retención</option>
                <?php foreach ($impuestos as $impuesto): ?>
                  <option value="<?= $impuesto['tarifa'] ?>" 
                    <?= ($selectRetencion == $impuesto['tarifa']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($impuesto['descripcion']) ?> (<?= $impuesto['tarifa'] ?>%)
                  </option>
                <?php endforeach; ?>
              </select>
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
          <table>
            <thead>
              <tr>
                <th>Identificacion</th>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Consecutivo</th>
                <th>N° Factura</th>
                <th>Forma Pago</th>
                <th>Vencimiento</th>
                <th>Subtotal</th>
                <th>Iva Total</th>
                <th>Retenciones</th>
                <th>Valor Total</th>
                <th>Observaciones</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody id="tabla-registros">
            <?php foreach($lista as $usuario){ ?>
              <tr>
                <td><?php echo $usuario['identificacion']; ?></td>
                <td><?php echo $usuario['nombre']; ?></td>
                <td><?php echo $usuario['fecha']; ?></td>
                <td><?php echo $usuario['consecutivo']; ?></td>
                <td><?php echo $usuario['numeroFactura'] ?? ''; ?></td>
                <td><?php echo $usuario['formaPago']; ?></td>
                <td><?php echo $usuario['fecha_vencimiento'] ?? ''; ?></td>
                <td><?php echo $usuario['subtotal']; ?></td>
                <td><?php echo $usuario['ivaTotal']; ?></td>
                <td><?php echo $usuario['retenciones']; ?></td>
                <td><?php echo $usuario['valorTotal']; ?></td>
                <td><?php echo $usuario['observaciones']; ?></td>
                <td>
                  <div style="display:flex; gap:5px;">
                    <form action="" method="post">
                      <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>" >
                      <input type="hidden" name="identificacion" value="<?php echo $usuario['identificacion']; ?>" >
                      <input type="hidden" name="nombre" value="<?php echo $usuario['nombre']; ?>" >
                      <input type="hidden" name="fecha" value="<?php echo $usuario['fecha']; ?>" >
                      <input type="hidden" name="consecutivo" value="<?php echo $usuario['consecutivo']; ?>" >
                      <input type="hidden" name="numeroFactura" value="<?php echo $usuario['numeroFactura'] ?? ''; ?>" >
                      <input type="hidden" name="formaPago" value="<?php echo $usuario['formaPago']; ?>" >
                      <input type="hidden" name="fechaVencimiento" value="<?php echo $usuario['fecha_vencimiento'] ?? ''; ?>" >
                      <input type="hidden" name="subtotal" value="<?php echo $usuario['subtotal']; ?>" >
                      <input type="hidden" name="ivaTotal" value="<?php echo $usuario['ivaTotal']; ?>" >
                      <input type="hidden" name="retenciones" value="<?php echo $usuario['retenciones']; ?>" >
                      <input type="hidden" name="valorTotal" value="<?php echo $usuario['valorTotal']; ?>" >
                      <input type="hidden" name="observaciones" value="<?php echo $usuario['observaciones']; ?>" >
                      <input type="hidden" name="selectRetencion" value="<?php echo $usuario['retencion_tarifa'] ?? ''; ?>" >
                      
                      <button type="submit" name="accion" value="btnEditar" class="btn btn-sm btn-info" title="Editar">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="submit" name="accion" value="btnEliminar" class="btn btn-sm btn-danger" title="Eliminar">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </form>
                    <!-- NUEVOS BOTONES -->
                    <a href="ver_factura_compra.php?id=<?php echo $usuario['id']; ?>" 
                      class="btn btn-sm btn-primary" 
                      target="_blank" 
                      title="Ver/Imprimir">
                      <i class="fas fa-print"></i>
                    </a>
                    <a href="generar_pdf_factura_compra.php?id=<?php echo $usuario['id']; ?>" 
                      class="btn btn-sm btn-danger" 
                      target="_blank" 
                      title="Descargar PDF">
                      <i class="fas fa-file-pdf"></i>
                    </a>
                    <a href="generar_excel_factura_compra.php?id=<?php echo $usuario['id']; ?>" 
                      class="btn btn-sm btn-success" 
                      target="_blank" 
                      title="Descargar Excel">
                      <i class="fas fa-file-excel"></i>
                    </a>
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
        });

        // Buscar el proveedor
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
                    body: new URLSearchParams({ nombreProveedor: valor }),
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

        // Función para mostrar/ocultar fecha de vencimiento según forma de pago
        function mostrarFechaVencimiento() {
            const formaPagoSelect = document.getElementById('formaPago');
            const fechaVencimientoContainer = document.getElementById('fechaVencimientoContainer');
            const retencionContainer = document.getElementById('retencionContainer');
            const fechaVencimientoInput = document.getElementById('fechaVencimiento');
            
            const formaPago = formaPagoSelect.value.toLowerCase();
            const esCredito = formaPago.includes('credito') || formaPago.includes('crédito');
            
            if (esCredito) {
                fechaVencimientoContainer.style.display = 'block';
                retencionContainer.classList.remove('col-md-6');
                retencionContainer.classList.add('col-md-12');
                
            // Establecer fecha mínima como la fecha del documento
            const fechaDocumento = document.getElementById('fecha').value;
            if (fechaDocumento) {
                fechaVencimientoInput.setAttribute('min', fechaDocumento);
            }

            // Si no hay fecha establecida, poner 30 días desde la fecha del documento
            if (!fechaVencimientoInput.value && fechaDocumento) {
                const fechaDefault = new Date(fechaDocumento);
                fechaDefault.setDate(fechaDefault.getDate() + 30);
                fechaVencimientoInput.value = fechaDefault.toISOString().split('T')[0];
            }
            } else {
                fechaVencimientoContainer.style.display = 'none';
                retencionContainer.classList.remove('col-md-12');
                retencionContainer.classList.add('col-md-6');
                fechaVencimientoInput.value = '';
            }
        }

        // Ejecutar al cargar la página para verificar el estado inicial
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

          // Buscar por nombre de producto (mantener esta funcionalidad)
          if (target.name === "nombreProducto" && target.value.trim().length >= 3) {
            fetch("", {
              method: "POST",
              body: new URLSearchParams({ nombreProducto: target.value }),
              headers: { "Content-Type": "application/x-www-form-urlencoded" }
            })
            .then(res => res.json())
            .then(data => {
              if (data.codigoProducto) {
                const codigoInput = row.querySelector('[name="codigoProducto"]');
                const precioInput = row.querySelector('.unit-price');
                
                // Si es un SELECT, buscar y seleccionar la opción correcta
                if (codigoInput.tagName === 'SELECT') {
                  const option = Array.from(codigoInput.options).find(
                    opt => opt.value === data.codigoProducto
                  );
                  if (option) {
                    codigoInput.value = data.codigoProducto;
                  }
                } else {
                  codigoInput.value = data.codigoProducto;
                }
                
                // Cargar el COSTO (no el precio) desde la base de datos
                if (data.costoUnitario && data.costoUnitario > 0) {
                  precioInput.value = data.costoUnitario;
                  precioInput.placeholder = `Costo actual: ${data.costoUnitario}`;
                } else if (data.precioUnitario && data.precioUnitario > 0) {
                  precioInput.placeholder = `Precio venta ref: ${data.precioUnitario}`;
                }
                
                calcularValores();
              }
            })
            .catch(error => console.error('Error:', error));
          }
          
          // ELIMINAR COMPLETAMENTE este bloque que buscaba por código:
          // if (target.name === "codigoProducto" && target.value.trim() !== "") {
          //   ... código que cargaba precioUnitario ...
          // }
        });

        // Event listener para cambios en cantidad o precio
        document.querySelector("#product-table").addEventListener("input", function (event) {
          if (event.target.classList.contains("quantity") || event.target.classList.contains("unit-price")) {
            calcularValores();
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

        // Event listener para cambios en el selector de retención
        document.getElementById("selectRetencion").addEventListener("change", calcularRetencionesYTotal);

        // Event listener para cambios en cantidad o precio
        document.querySelector("#product-table").addEventListener("input", function (event) {
            if (event.target.classList.contains("quantity") || event.target.classList.contains("unit-price")) {
                calcularValores();
            }
        });

        // Función para cargar producto cuando se selecciona del dropdown - MODIFICADA
        function cargarProducto(selectElement) {
            const row = selectElement.closest('tr');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const codigo = selectElement.value;
            const nombre = selectedOption.getAttribute('data-nombre');
            const nombreInput = row.querySelector('[name="nombreProducto"]');
            const precioInput = row.querySelector('.unit-price');
            
            if (codigo && nombre) {
                nombreInput.value = nombre;
                
                // Obtener precio y costo desde la base de datos
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ codigoProducto: codigo }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(res => res.json())
                .then(data => {
                    // En factura de COMPRA, sugerir el costoUnitario como precio de compra
                    // pero permitir que el usuario lo modifique
                    if (data.costoUnitario && data.costoUnitario > 0) {
                        precioInput.value = data.costoUnitario;
                        precioInput.placeholder = `Costo actual: ${data.costoUnitario}`;
                    } else if (data.precioUnitario && data.precioUnitario > 0) {
                        // Si no hay costo, sugerir el precio de venta como referencia
                        precioInput.placeholder = `Precio venta ref: ${data.precioUnitario}`;
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
                precioInput.placeholder = "";
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
              document.getElementById("numeroFactura").value = "";
              document.getElementById("formaPago").value = "";
              document.getElementById("fechaVencimiento").value = "";
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
                        <button type="button" class="btn-add" onclick="addRow()">+</button>
                        <button type="button" class="btn-remove" onclick="removeRowSafe(this)">-</button>
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

                // Resetear totales
                calcularValores();
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
        document.getElementById("formFacturaCompras").addEventListener("submit", function(e) {
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

    <!-- ======= Footer ======= -->
    <footer id="footer" class="footer-minimalista">
      <p>Universidad de Santander - Ingeniería de Software</p>
      <p>Todos los derechos reservados © 2025</p>
      <p>Creado por iniciativa del programa de Contaduría Pública</p>
    </footer><!-- End Footer -->

  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>