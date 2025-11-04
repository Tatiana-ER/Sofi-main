<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

$txtId = (isset($_POST['txtId'])) ? $_POST['txtId'] : "";
$identificacion = (isset($_POST['identificacion'])) ? $_POST['identificacion'] : "";
$nombre = (isset($_POST['nombre'])) ? $_POST['nombre'] : "";
$fecha = (isset($_POST['fecha'])) ? $_POST['fecha'] : "";
$consecutivo = (isset($_POST['consecutivo'])) ? $_POST['consecutivo'] : "";
$numeroFactura = (isset($_POST['numeroFactura'])) ? $_POST['numeroFactura'] : "";
$formaPago = (isset($_POST['forma-pago'])) ? $_POST['forma-pago'] : "";
$subtotal = (isset($_POST['subtotal'])) ? $_POST['subtotal'] : "";
$ivaTotal = (isset($_POST['ivaTotal'])) ? $_POST['ivaTotal'] : "";
$retenciones = (isset($_POST['retenciones'])) ? $_POST['retenciones'] : "";
$valorTotal = (isset($_POST['valorTotal'])) ? $_POST['valorTotal'] : "";
$observaciones = (isset($_POST['observaciones'])) ? $_POST['observaciones'] : "";

$accion = (isset($_POST['accion'])) ? $_POST['accion'] : "";

// Obtener el siguiente consecutivo 
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(CAST(consecutivo AS UNSIGNED)) AS ultimo FROM facturac");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoConsecutivo = $row['ultimo'] ?? 0;
    $nuevoConsecutivo = $ultimoConsecutivo + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}

// Si vienen detalles como JSON (desde el form)
if (isset($_POST['detalles'])) {
    $_POST['detalles'] = json_decode($_POST['detalles'], true);
}

switch ($accion) {
    case "btnAgregar":
        $sentencia = $pdo->prepare("INSERT INTO facturac (identificacion, nombre, fecha, consecutivo, numeroFactura, formaPago, subtotal, ivaTotal, retenciones, valorTotal, observaciones) 
            VALUES (:identificacion, :nombre, :fecha, :consecutivo, :numeroFactura, :formaPago, :subtotal, :ivaTotal, :retenciones, :valorTotal, :observaciones)");

        $sentencia->bindParam(':identificacion', $identificacion);
        $sentencia->bindParam(':nombre', $nombre);
        $sentencia->bindParam(':fecha', $fecha);
        $sentencia->bindParam(':consecutivo', $consecutivo);
        $sentencia->bindParam(':numeroFactura', $numeroFactura);
        $sentencia->bindParam(':formaPago', $formaPago);
        $sentencia->bindParam(':subtotal', $subtotal);
        $sentencia->bindParam(':ivaTotal', $ivaTotal);
        $sentencia->bindParam(':retenciones', $retenciones);
        $sentencia->bindParam(':valorTotal', $valorTotal);
        $sentencia->bindParam(':observaciones', $observaciones);
        $sentencia->execute();

        $idFactura = $pdo->lastInsertId();

        // Guardar los productos del detalle
        if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
            $sqlDetalle = "INSERT INTO detallefacturac (factura_id, codigoProducto, nombreProducto, cantidad, precioUnitario, iva, valorTotal)
                            VALUES (:factura_id, :codigoProducto, :nombreProducto, :cantidad, :precioUnitario, :iva, :valorTotal)";
            $stmtDetalle = $pdo->prepare($sqlDetalle);

            foreach ($_POST['detalles'] as $detalle) {
                $stmtDetalle->execute([
                    ':factura_id' => $idFactura,
                    ':codigoProducto' => $detalle['codigoProducto'],
                    ':nombreProducto' => $detalle['nombreProducto'],
                    ':cantidad' => $detalle['cantidad'],
                    ':precioUnitario' => $detalle['precio'] ?? $detalle['precioUnitario'] ?? 0,
                    ':iva' => $detalle['iva'],
                    ':valorTotal' => $detalle['precioTotal'] ?? $detalle['valorTotal'] ?? 0
                ]);
            }
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=agregado");
        exit;
    break;

    case "btnModificar":
        $sentencia = $pdo->prepare("UPDATE facturac 
                                    SET identificacion = :identificacion, 
                                        nombre = :nombre, 
                                        fecha = :fecha, 
                                        consecutivo = :consecutivo, 
                                        numeroFactura = :numeroFactura, 
                                        formaPago = :formaPago, 
                                        subtotal = :subtotal, 
                                        ivaTotal = :ivaTotal, 
                                        retenciones = :retenciones, 
                                        valorTotal = :valorTotal, 
                                        observaciones = :observaciones 
                                    WHERE id = :id");

        $sentencia->bindParam(':identificacion', $identificacion);
        $sentencia->bindParam(':nombre', $nombre);
        $sentencia->bindParam(':fecha', $fecha);
        $sentencia->bindParam(':consecutivo', $consecutivo);
        $sentencia->bindParam(':numeroFactura', $numeroFactura);
        $sentencia->bindParam(':formaPago', $formaPago);
        $sentencia->bindParam(':subtotal', $subtotal);
        $sentencia->bindParam(':ivaTotal', $ivaTotal);
        $sentencia->bindParam(':retenciones', $retenciones);
        $sentencia->bindParam(':valorTotal', $valorTotal);
        $sentencia->bindParam(':observaciones', $observaciones);
        $sentencia->bindParam(':id', $txtId);
        $sentencia->execute();

        // Eliminar detalles antiguos
        $deleteDetalle = $pdo->prepare("DELETE FROM detallefacturac WHERE factura_id = :factura_id");
        $deleteDetalle->bindParam(':factura_id', $txtId);
        $deleteDetalle->execute();

        // Insertar nuevos detalles
        if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
            $sqlDetalle = "INSERT INTO detallefacturac (factura_id, codigoProducto, nombreProducto, cantidad, precioUnitario, iva, valorTotal)
                          VALUES (:factura_id, :codigoProducto, :nombreProducto, :cantidad, :precioUnitario, :iva, :valorTotal)";
            $stmtDetalle = $pdo->prepare($sqlDetalle);

            foreach ($_POST['detalles'] as $detalle) {
                $stmtDetalle->execute([
                    ':factura_id' => $txtId,
                    ':codigoProducto' => $detalle['codigoProducto'],
                    ':nombreProducto' => $detalle['nombreProducto'],
                    ':cantidad' => $detalle['cantidad'],
                    ':precioUnitario' => $detalle['precio'] ?? $detalle['precioUnitario'] ?? 0,
                    ':iva' => $detalle['iva'],
                    ':valorTotal' => $detalle['precioTotal'] ?? $detalle['valorTotal'] ?? 0
                ]);
            }
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=modificado");
        exit;
    break;

    case "btnEliminar":
        // PRIMERO eliminar detalles relacionados
        $delDetalle = $pdo->prepare("DELETE FROM detallefacturac WHERE factura_id = :factura_id");
        $delDetalle->bindParam(':factura_id', $txtId);
        $delDetalle->execute();

        // LUEGO eliminar la factura principal
        $sentencia = $pdo->prepare("DELETE FROM facturac WHERE id = :id");
        $sentencia->bindParam(':id', $txtId);
        $sentencia->execute();

        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=eliminado");
        exit;
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
            $numeroFactura = $factura['numeroFactura'];
            $formaPago = $factura['formaPago'];
            $subtotal = $factura['subtotal'];
            $ivaTotal = $factura['ivaTotal'];
            $retenciones = $factura['retenciones'];
            $valorTotal = $factura['valorTotal'];
            $observaciones = $factura['observaciones'];
        }

        // Cargar detalles asociados
        $stmtDetalle = $pdo->prepare("SELECT * FROM detallefacturac WHERE factura_id = :id_factura");
        $stmtDetalle->bindParam(':id_factura', $txtId);
        $stmtDetalle->execute();
        $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
    break;
}

// Búsqueda proveedor por identificacion o nombre (peticiones AJAX sin 'accion')
if (isset($_POST['es_ajax']) && $_POST['es_ajax'] == 'proveedor') {

    $ident = $_POST['identificacion'] ?? '';
    $nombreProv = $_POST['nombreProveedor'] ?? '';
    $proveedor = null;

    if (!empty($ident)) {
        $stmt = $pdo->prepare("SELECT cedula, CONCAT(nombres, ' ', apellidos) AS nombreCompleto FROM catalogosterceros WHERE cedula = :cedula AND tipoTercero LIKE '%Proveedor%' LIMIT 1");
        $stmt->bindParam(':cedula', $ident, PDO::PARAM_STR);
        $stmt->execute();
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (!empty($nombreProv)) {
        $likeNombre = "%$nombreProv%";
        $stmt = $pdo->prepare("SELECT cedula, CONCAT(nombres, ' ', apellidos) AS nombreCompleto FROM catalogosterceros WHERE CONCAT(nombres, ' ', apellidos) LIKE :nombre AND tipoTercero LIKE '%Proveedor%' LIMIT 1");
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

// Buscar producto por código o nombre (AJAX)
if (isset($_POST['es_ajax']) && $_POST['es_ajax'] == 'producto') { // Usa 'es_ajax' con un valor distinto

    $codigo = trim($_POST['codigoProducto'] ?? '');
    $nombreProd = trim($_POST['nombreProducto'] ?? '');
    $producto = null;

    if ($codigo !== '') {
        $stmt = $pdo->prepare("SELECT codigoProducto, descripcionProducto FROM productoinventarios WHERE codigoProducto = :codigo LIMIT 1");
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($nombreProd !== '') {
        $likeNombre = "%$nombreProd%";
        $stmt = $pdo->prepare("SELECT codigoProducto, descripcionProducto FROM productoinventarios WHERE descripcionProducto LIKE :nombre LIMIT 1");
        $stmt->bindParam(':nombre', $likeNombre, PDO::PARAM_STR);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($producto) {
        echo json_encode([
            "codigoProducto" => $producto['codigoProducto'],
            "nombreProducto" => $producto['descripcionProducto']
        ]);
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

// Lista de facturas de compra
$sentencia = $pdo->prepare("SELECT * FROM facturac WHERE 1");
$sentencia->execute();
$lista = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Mantener los valores cargados en edición
if (isset($factura) && !empty($factura)) {
  $txtId = $factura['id'];
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
        text: 'El parametro factura de venta se ha agregado correctamente',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "modificado":
      Swal.fire({
        icon: 'success',
        title: 'Modificado correctamente',
        text: 'Los datos se actualizaron con éxito',
        confirmButtonColor: '#3085d6'
      });
      break;

    case "eliminado":
      Swal.fire({
        icon: 'success',
        title: 'Eliminado correctamente',
        text: 'El parametro factura de venta fue eliminado del registro',
        confirmButtonColor: '#3085d6'
      });
      break;
  }

  // Quita el parámetro ?msg=... de la URL sin recargar
  if (window.history.replaceState) {
    const url = new URL(window.location);
    url.searchParams.delete('msg');
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
  <title>SOFI - Factura de Compra</title>

  <!-- Favicons & Fonts & Vendor CSS (mismos que factura de venta) -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Raleway:300,400,500,600,700|Poppins:300,400,500,600,700" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="assets/css/improved-style.css" rel="stylesheet">

  <style>
    input[type="text"], input[type="number"] { width: 100%; box-sizing: border-box; padding: 5px; }
    .add-row-btn { cursor: pointer; background-color: #0d6efd; color: white; border: none; padding: 10px; font-size: 18px; margin-top: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { font-weight: bold; display: inline-block; width: 150px; }
    .totals { margin-top: 20px; text-align: right; }
    .totals label { font-weight: bold; }
    .totals input { width: 160px; }
    .table-container table { width: 100%; border-collapse: collapse; }
    .table-container th, .table-container td { padding: 8px; border: 1px solid #ddd; text-align: left; }
  </style>
</head>

<body>
  <!-- Header -->
  <header id="header" class="fixed-top d-flex align-items-center ">
    <div class="container d-flex align-items-center justify-content-between">
      <h1 class="logo">
        <a href="dashboard.php">
          <img src="./Img/sofilogo5pequeño.png" alt="Logo SOFI" class="logo-icon">
          Software Financiero
        </a>
      </h1>
      <nav id="navbar" class="navbar">
        <ul>
          <li><a class="nav-link scrollto active" href="dashboard.php" style="color: darkblue;">Inicio</a></li>
          <li><a class="nav-link scrollto active" href="perfil.php" style="color: darkblue;">Mi Negocio</a></li>
          <li><a class="nav-link scrollto active" href="index.php" style="color: darkblue;">Cerrar Sesión</a></li>
        </ul>
        <i class="bi bi-list mobile-nav-toggle"></i>
      </nav>
    </div>
  </header>

  <section id="services" class="services">
    <button class="btn-ir" onclick="window.location.href='menudocumentos.php'"><i class="fa-solid fa-arrow-left"></i> Regresar</button>
    <div class="container" data-aos="fade-up">
      <div class="section-title">
        <h2>FACTURA DE COMPRA</h2>
        <p>Para crear una nueva factura de compra diligencie los campos a continuación:</p>
        <p>(Los campos marcados con * son obligatorios)</p>
      </div>

      <form id="formFacturaCompras" action="" method="post" class="container mt-3">
        <input type="hidden" id="txtId" name="txtId" value="<?php echo htmlspecialchars($txtId); ?>">

        <div class="row g-3">
          <div class="col-md-4">
            <label for="identificacion" class="form-label fw-bold">Identificación del Proveedor (NIT o CC)*</label>
            <input type="number" class="form-control" id="identificacion" name="identificacion" placeholder="Ej: 123456789" value="<?php echo htmlspecialchars($identificacion); ?>" required>
          </div>

          <div class="col-md-8">
            <label for="nombre" class="form-label fw-bold">Nombre del Proveedor</label>
            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre del proveedor" value="<?php echo htmlspecialchars($nombre); ?>">
          </div>
        </div>

        <div class="row g-3 mt-2">
          <div class="col-md-4">
            <label for="fecha" class="form-label fw-bold">Fecha del documento</label>
            <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>" required>
          </div>

          <div class="col-md-4">
            <label for="consecutivo" class="form-label fw-bold">Consecutivo</label>
            <input type="text" class="form-control" id="consecutivo" name="consecutivo" placeholder="Número consecutivo" value="<?php echo htmlspecialchars($consecutivo); ?>" readonly>
          </div>

          <div class="col-md-4">
            <label for="numeroFactura" class="form-label fw-bold">Número de factura</label>
            <input type="text" class="form-control" id="numeroFactura" name="numeroFactura" placeholder="Número interno o del proveedor" value="<?php echo htmlspecialchars($numeroFactura); ?>">
          </div>
        </div>

        <!-- Tabla de productos -->
        <div class="table-responsive mt-3 table-container">
          <table>
            <thead class="table-primary text-center">
              <tr>
                <th>Código del producto</th>
                <th>Nombre del producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>IVA</th>
                <th>Valor Total</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="product-table">
              <?php if (!empty($detalles)) : ?>
                <?php foreach ($detalles as $detalle): ?>
                  <tr>
                    <td><input type="text" name="codigoProducto" class="form-control" value="<?= htmlspecialchars($detalle['codigoProducto']) ?>"></td>
                    <td><input type="text" name="nombreProducto" class="form-control" value="<?= htmlspecialchars($detalle['nombreProducto']) ?>"></td>
                    <td><input type="number" name="cantidad" class="form-control quantity" value="<?= htmlspecialchars($detalle['cantidad']) ?>"></td>
                    <td><input type="number" name="precio" class="form-control unit-price" value="<?= htmlspecialchars($detalle['precioUnitario']) ?>"></td>
                    <td><input type="number" name="iva" class="form-control iva" value="<?= htmlspecialchars($detalle['iva']) ?>" readonly></td>
                    <td><input type="number" name="precioTotal" class="form-control total-price" value="<?= htmlspecialchars($detalle['valorTotal']) ?>" readonly></td>
                    <td><button type="button" class="btn-add" onclick="addRow()">+</button></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td><input type="text" name="codigoProducto" class="form-control" value=""></td>
                  <td><input type="text" name="nombreProducto" class="form-control" value=""></td>
                  <td><input type="number" name="cantidad" class="form-control quantity" value=""></td>
                  <td><input type="number" name="precio" class="form-control unit-price" value=""></td>
                  <td><input type="number" name="iva" class="form-control iva" value="0.00" readonly></td>
                  <td><input type="number" name="precioTotal" class="form-control total-price" value="0.00" readonly></td>
                  <td><button type="button" class="btn-add" onclick="addRow()">+</button></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Forma de Pago -->
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="forma-pago" class="form-label fw-bold">Forma de Pago*</label>
            <select class="form-select" id="forma-pago" name="forma-pago" required>
              <option value="">Seleccione una opción</option>
              <?php foreach ($mediosPago as $medio): ?>
                  <option value="<?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>"
                    <?= ($formaPago == $medio['metodoPago']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>
                  </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Totales a la derecha -->
        <div class="col-md-3 ms-auto mt-3">
          <div class="mb-2">
            <label for="subtotal" class="form-label fw-bold">Subtotal</label>
            <input type="text" id="subtotal" name="subtotal" class="form-control text-end" value="<?php echo htmlspecialchars($subtotal ?? ''); ?>" readonly>
          </div>

          <div class="mb-2">
            <label for="ivaTotal" class="form-label fw-bold">IVA</label>
            <input type="text" id="ivaTotal" name="ivaTotal" class="form-control text-end" value="<?php echo htmlspecialchars($ivaTotal ?? ''); ?>" readonly>
          </div>

          <div class="mb-2">
            <label for="retenciones" class="form-label fw-bold">Retenciones</label>
            <input type="text" id="retenciones" name="retenciones" class="form-control text-end" value="<?php echo htmlspecialchars($retenciones ?? '0.00'); ?>" readonly>
          </div>

          <div class="mb-2">
            <label for="valorTotal" class="form-label fw-bold">Valor Total</label>
            <input type="text" id="valorTotal" name="valorTotal" class="form-control text-end fw-bold border-2 border-primary" value="<?php echo htmlspecialchars($valorTotal ?? ''); ?>" readonly>
          </div>
        </div>

        <div class="mb-3 mt-3">
          <label for="observaciones" class="form-label">Observaciones</label>
          <input type="text" name="observaciones" value="<?php echo htmlspecialchars($observaciones); ?>" class="form-control" id="observaciones" placeholder="">
        </div>

        <!-- Botones -->
        <div class="mt-4">
          <button id="btnAgregar" value="btnAgregar" type="submit" class="btn btn-primary" name="accion">Agregar</button>
          <button id="btnModificar" value="btnModificar" type="submit" class="btn btn-warning" name="accion">Modificar</button>
          <button id="btnEliminar" value="btnEliminar" type="submit" class="btn btn-danger" name="accion">Eliminar</button>
          <button id="btnCancelar" type="button" class="btn btn-secondary" style="display:none;">Cancelar</button>
        </div>
      </form>

      <!-- Lista de facturas -->
      <div class="row mt-4">
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Identificacion</th>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Consecutivo</th>
                <th>Numero Factura</th>
                <th>Forma Pago</th>
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
                <td><?php echo htmlspecialchars($usuario['identificacion']); ?></td>
                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                <td><?php echo htmlspecialchars($usuario['fecha']); ?></td>
                <td><?php echo htmlspecialchars($usuario['consecutivo']); ?></td>
                <td><?php echo htmlspecialchars($usuario['numeroFactura']); ?></td>
                <td><?php echo htmlspecialchars($usuario['formaPago']); ?></td>
                <td><?php echo htmlspecialchars($usuario['subtotal']); ?></td>
                <td><?php echo htmlspecialchars($usuario['ivaTotal']); ?></td>
                <td><?php echo htmlspecialchars($usuario['retenciones']); ?></td>
                <td><?php echo htmlspecialchars($usuario['valorTotal']); ?></td>
                <td><?php echo htmlspecialchars($usuario['observaciones']); ?></td>
                <td>
                  <form action="" method="post" class="form-tabla-accion">
                    <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>" >
                    <button type="submit" name="accion" value="btnEditar" class="btn btn-sm btn-info" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button type="submit" name="accion" value="btnEliminar" class="btn btn-sm btn-danger" title="Eliminar">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>

<!-- Scripts -->
      <script>
        // SCRIPT JAVASCRIPT COMPLETO PARA FACTURA DE COMPRA

        // Variable global para controlar el submit
        let permitirSubmit = false;

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
        });

        // Búsqueda proveedor por identificación
        const inputIdentificacion = document.getElementById("identificacion");
        const inputNombre = document.getElementById("nombre");

        inputIdentificacion.addEventListener("input", function () {
            const valor = this.value.trim();
            if (valor.length > 0) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ identificacion: valor, es_ajax: 'proveedor' }),
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

        // Búsqueda proveedor por nombre
        inputNombre.addEventListener("input", function () {
            const valor = this.value.trim();
            if (valor.length >= 3) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ nombreProveedor: valor, es_ajax: 'proveedor' }),
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

        // Delegación para buscar producto por código o nombre
        document.querySelector("#product-table").addEventListener("input", function(e) {
          const target = e.target;
          const row = target.closest("tr");

          if (target.name === "codigoProducto" && target.value.trim() !== "") {
            fetch("", {
                method: "POST",
                body: new URLSearchParams({ codigoProducto: target.value, es_ajax: 'producto' }), 
                headers: { "Content-Type": "application/x-www-form-urlencoded" }
            })
            .then(res => res.json())
            .then(data => {
              row.querySelector('[name="nombreProducto"]').value = data.nombreProducto || "";
            });
          }

          if (target.name === "nombreProducto" && target.value.trim().length >= 3) {
            fetch("", {
                method: "POST",
                body: new URLSearchParams({ nombreProducto: target.value, es_ajax: 'producto' }),
                headers: { "Content-Type": "application/x-www-form-urlencoded" }
            })
            .then(res => res.json())
            .then(data => {
              if (data.codigoProducto) {
                row.querySelector('[name="codigoProducto"]').value = data.codigoProducto;
              }
            });
          }
        });

        // Calcular valores automáticamente
        document.addEventListener("DOMContentLoaded", function () {
            function calcularValores() {
                let subtotal = 0;
                let ivaTotal = 0;

                document.querySelectorAll("#product-table tr").forEach(row => {
                    const cantidad = parseFloat(row.querySelector(".quantity")?.value || 0);
                    const precio = parseFloat(row.querySelector(".unit-price")?.value || 0);
                    const ivaField = row.querySelector(".iva");
                    const totalField = row.querySelector(".total-price");

                    if (!ivaField || !totalField) return;

                    const total = cantidad * precio;
                    const iva = total * 0.19;

                    ivaField.value = iva.toFixed(2);
                    totalField.value = total.toFixed(2);

                    subtotal += total;
                    ivaTotal += iva;
                });

                document.querySelector("#subtotal").value = subtotal.toFixed(2);
                document.querySelector("#ivaTotal").value = ivaTotal.toFixed(2);
                document.querySelector("#retenciones").value = "0.00";
                document.querySelector("#valorTotal").value = (subtotal + ivaTotal).toFixed(2);
            }

            // Escuchar cambios en cantidad o precio
            document.querySelector("#product-table").addEventListener("input", function (event) {
                if (event.target.classList.contains("quantity") || event.target.classList.contains("unit-price")) {
                    calcularValores();
                }
            });

            // Función para agregar nueva fila
            window.addRow = function() {
                const tableBody = document.getElementById("product-table");
                const newRow = tableBody.firstElementChild.cloneNode(true);

                // Limpiar valores
                newRow.querySelectorAll("input").forEach(input => {
                    input.value = "";
                    input.removeAttribute("readonly");
                });

                // Agregar botón de eliminar si no existe
                if (!newRow.querySelector(".btn-remove")) {
                    const btn = document.createElement("button");
                    btn.textContent = "-";
                    btn.className = "btn-remove";
                    btn.style.marginLeft = "10px";
                    btn.style.backgroundColor = "red";
                    btn.style.color = "white";
                    btn.style.border = "none";
                    btn.style.borderRadius = "4px";
                    btn.style.cursor = "pointer";

                    btn.onclick = function() {
                        const rows = tableBody.querySelectorAll("tr");
                        if (rows.length > 1) {
                            this.closest("tr").remove();
                        } else {
                            alert("Debe haber al menos una fila.");
                        }
                    };

                    const lastCell = newRow.lastElementChild;
                    lastCell.appendChild(btn);
                }

                tableBody.appendChild(newRow);
            };

            // Añadir botón eliminar a la primera fila si no existe
            document.addEventListener("DOMContentLoaded", function() {
                const firstRow = document.querySelector("#product-table tr");
                if (firstRow && !firstRow.querySelector(".btn-remove")) {
                    const btn = document.createElement("button");
                    btn.textContent = "-";
                    btn.className = "btn-remove";
                    btn.style.marginLeft = "10px";
                    btn.style.backgroundColor = "red";
                    btn.style.color = "white";
                    btn.style.border = "none";
                    btn.style.borderRadius = "10px";
                    btn.style.cursor = "pointer";

                    btn.onclick = function() {
                        const rows = document.querySelectorAll("#product-table tr");
                        if (rows.length > 1) {
                            this.closest("tr").remove();
                        } else {
                            alert("Debe haber al menos una fila.");
                        }
                    };

                    const lastCell = firstRow.lastElementChild;
                    lastCell.appendChild(btn);
                }
            });
        });

        // Alternar botones y comportamiento cancelar
        document.addEventListener("DOMContentLoaded", function() {
            const id = document.getElementById("txtId").value;
            const btnAgregar = document.getElementById("btnAgregar");
            const btnModificar = document.getElementById("btnModificar");
            const btnEliminar = document.getElementById("btnEliminar");
            const btnCancelar = document.getElementById("btnCancelar");
            const form = document.getElementById("formFacturaCompras");

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
                document.getElementById("fecha").value = "";
                document.getElementById("numeroFactura").value = "";
                document.getElementById("forma-pago").value = "";
                document.getElementById("subtotal").value = "";
                document.getElementById("ivaTotal").value = "";
                document.getElementById("retenciones").value = "";
                document.getElementById("valorTotal").value = "";
                document.getElementById("observaciones").value = "";

                // Limpiar la tabla de productos y dejar solo UNA fila vacía
                const tableBody = document.getElementById("product-table");
                tableBody.innerHTML = `
                    <tr>
                        <td><input type="text" name="codigoProducto" class="form-control" value=""></td>
                        <td><input type="text" name="nombreProducto" class="form-control" value=""></td>
                        <td><input type="number" name="cantidad" class="form-control quantity" value=""></td>
                        <td><input type="number" name="precio" class="form-control unit-price" value=""></td>
                        <td><input type="number" name="iva" class="form-control iva" value="0.00" readonly></td>
                        <td><input type="number" name="precioTotal" class="form-control total-price" value="0.00" readonly></td>
                        <td>
                            <button type="button" class="btn-add" onclick="addRow()">+</button>
                            <button type="button" class="btn-remove" style="margin-left:10px; background-color:red; color:white; border:none; border-radius:4px; cursor:pointer;" onclick="removeRowSafe(this)">-</button>
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
                    url.search = ''; // Elimina todos los parámetros
                    window.history.replaceState({}, document.title, url);
                }
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
                
                // Mostrar confirmación
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

        // Función auxiliar para remover filas de forma segura
        function removeRowSafe(btn) {
            const rows = document.querySelectorAll("#product-table tr");
            if (rows.length > 1) {
                btn.closest("tr").remove();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Debe haber al menos una fila de producto',
                    confirmButtonColor: '#3085d6'
                });
            }
        }

        // Funciones de confirmación con SweetAlert2
        document.addEventListener("DOMContentLoaded", () => {
            // Selecciona TODOS los formularios de la página
            const forms = document.querySelectorAll("form");

            forms.forEach((form) => {
                form.addEventListener("submit", function (e) {
                    const boton = e.submitter; // botón que disparó el envío
                    const accion = boton?.value;

                    // Solo mostrar confirmación para modificar o eliminar
                    if (accion === "btnModificar" || accion === "btnEliminar") {
                        e.preventDefault(); // detener envío temporalmente

                        let titulo = accion === "btnModificar" ? "¿Guardar cambios?" : "¿Eliminar registro?";
                        let texto = accion === "btnModificar"
                            ? "Se actualizarán los datos de esta factura de compra."
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
                                // Crear (si no existe) un campo oculto con la acción seleccionada
                                let inputAccion = form.querySelector("input[name='accionOculta']");
                                if (!inputAccion) {
                                    inputAccion = document.createElement("input");
                                    inputAccion.type = "hidden";
                                    inputAccion.name = "accion";
                                    form.appendChild(inputAccion);
                                }
                                inputAccion.value = accion;

                                form.submit(); // Enviar el formulario correspondiente
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
                    detalles.push({codigoProducto: codigo, nombreProducto: nombre, cantidad, precio, iva, precioTotal: total});
                }
            });

            const inputDetalles = document.createElement("input");
            inputDetalles.type = "hidden";
            inputDetalles.name = "detalles";
            inputDetalles.value = JSON.stringify(detalles);

            this.appendChild(inputDetalles);
        });
      </script>

      <br>
    </div>
  </section>

  <footer id="footer" class="footer-minimalista">
    <p>Universidad de Santander - Ingeniería de Software</p>
    <p>Todos los derechos reservados © 2025</p>
    <p>Creado por iniciativa del programa de Contaduría Pública</p>
  </footer>

  <div id="preloader"></div>
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
