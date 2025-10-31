<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Obtener el siguiente consecutivo 
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(consecutivo) AS ultimo FROM facturav");
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

$accion=(isset($_POST['accion']))?$_POST['accion']:"";

if (isset($_POST['detalles'])) {
    $_POST['detalles'] = json_decode($_POST['detalles'], true);
}


switch($accion){
  case "btnAgregar":
      $sentencia=$pdo->prepare("INSERT INTO facturav(identificacion,nombre,fecha,consecutivo,formaPago,subtotal,ivaTotal,retenciones,valorTotal,observaciones) 
      VALUES (:identificacion,:nombre,:fecha,:consecutivo,:formaPago,:subtotal,:ivaTotal,:retenciones,:valorTotal,:observaciones)");
      
      $sentencia->bindParam(':identificacion',$identificacion);
      $sentencia->bindParam(':nombre',$nombre);
      $sentencia->bindParam(':fecha',$fecha);
      $sentencia->bindParam(':consecutivo',$consecutivo);
      $sentencia->bindParam(':formaPago',$formaPago);
      $sentencia->bindParam(':subtotal',$subtotal);
      $sentencia->bindParam(':ivaTotal',$ivaTotal);
      $sentencia->bindParam(':retenciones',$retenciones);
      $sentencia->bindParam(':valorTotal',$valorTotal);
      $sentencia->bindParam(':observaciones',$observaciones);
      $sentencia->execute();

      $idFactura = $pdo->lastInsertId();

      if (isset($_POST['detalles']) && is_array($_POST['detalles'])) {
          $sqlDetalle = "INSERT INTO factura_detalle 
                        (id_factura, codigoProducto, nombreProducto, cantidad, precio_unitario, iva, total)
                        VALUES (:id_factura, :codigoProducto, :nombreProducto, :cantidad, :precio_unitario, :iva, :total)";
          $stmtDetalle = $pdo->prepare($sqlDetalle);

          foreach ($_POST['detalles'] as $detalle) {
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

      header("Location: ".$_SERVER['PHP_SELF']."?msg=agregado");
      exit;
  break;

  case "btnModificar":
      $sentencia = $pdo->prepare("UPDATE facturav 
                                  SET identificacion = :identificacion,
                                      nombre = :nombre,
                                      fecha = :fecha,
                                      consecutivo = :consecutivo,
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
      $sentencia->bindParam(':formaPago', $formaPago);
      $sentencia->bindParam(':subtotal', $subtotal);
      $sentencia->bindParam(':ivaTotal', $ivaTotal);
      $sentencia->bindParam(':retenciones', $retenciones);
      $sentencia->bindParam(':valorTotal', $valorTotal);
      $sentencia->bindParam(':observaciones', $observaciones);
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

          foreach ($_POST['detalles'] as $detalle) {
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

      header("Location: ".$_SERVER['PHP_SELF']."?msg=modificado");
      exit;
  break;

  case "btnEliminar":
      // Primero eliminar los detalles asociados
      $sentenciaDetalle = $pdo->prepare("DELETE FROM factura_detalle WHERE id_factura = :id");
      $sentenciaDetalle->bindParam(':id', $txtId);
      $sentenciaDetalle->execute();

      // Luego eliminar la factura principal
      $sentencia = $pdo->prepare("DELETE FROM facturav WHERE id = :id");
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();

      header("Location: ".$_SERVER['PHP_SELF']."?msg=eliminado");
      exit;
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
          $formaPago = $factura['formaPago'];
          $subtotal = $factura['subtotal'];
          $ivaTotal = $factura['ivaTotal'];
          $retenciones = $factura['retenciones'];
          $valorTotal = $factura['valorTotal'];
          $observaciones = $factura['observaciones'];
      }

      // Cargar detalles asociados
      $stmtDetalle = $pdo->prepare("SELECT * FROM factura_detalle WHERE id_factura = :id_factura");
      $stmtDetalle->bindParam(':id_factura', $txtId);
      $stmtDetalle->execute();
      $detalles = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
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
        // Buscar por cédula
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
        // Buscar por nombre
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

// Buscar producto por código o nombre
if (isset($_POST['codigoProducto']) || isset($_POST['nombreProducto'])) {
    $codigo = trim($_POST['codigoProducto'] ?? '');
    $nombre = trim($_POST['nombreProducto'] ?? '');
    $producto = null;

    if ($codigo !== '') {
        // Buscar solo por código exacto
        $stmt = $pdo->prepare("SELECT codigoProducto, descripcionProducto 
                               FROM productoinventarios 
                               WHERE codigoProducto = :codigo 
                               LIMIT 1");
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    } 
    elseif ($nombre !== '') {
        // Buscar solo por nombre (LIKE)
        $likeNombre = "%$nombre%";
        $stmt = $pdo->prepare("SELECT codigoProducto, descripcionProducto 
                               FROM productoinventarios 
                               WHERE descripcionProducto LIKE :nombre 
                               LIMIT 1");
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
  </style>

</head>

<body>

  <!-- ======= Header ======= -->
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

          <!-- Fecha y Consecutivo -->
          <div class="row g-3 mt-2">
            <div class="col-md-4">
              <label for="fecha" class="form-label fw-bold">Fecha del documento</label>
              <input type="date" class="form-control" id="fecha" name="fecha"
                    value="<?php echo $fecha; ?>" required>
            </div>

            <div class="col-md-4">
              <label for="consecutivo" class="form-label fw-bold">Consecutivo</label>
              <input type="text" class="form-control" id="consecutivo" name="consecutivo"
                    placeholder="Número consecutivo"
                    value="<?php echo $consecutivo; ?> readonly">
            </div>
          </div>

          <!-- Tabla de productos -->
          <div class="table-responsive mt-3">
            <table class="table-container">
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
                          <td><input type="number" name="precio" class="form-control unit-price" value="<?= htmlspecialchars($detalle['precio_unitario']) ?>"></td>
                          <td><input type="number" name="iva" class="form-control iva" value="<?= htmlspecialchars($detalle['iva']) ?>" readonly></td>
                          <td><input type="number" name="precioTotal" class="form-control total-price" value="<?= htmlspecialchars($detalle['total']) ?>" readonly></td>
                          <td><button type="button" class="btn-add" onclick="addRow()">+</button></td>
                      </tr>
                  <?php endforeach; ?>
              <?php else: // Si no hay detalles, imprime una fila vacía para el inicio ?>
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
              <label for="formaPago" class="form-label fw-bold">Forma de Pago*</label>
              <select class="form-select" id="formaPago" name="formaPago" required>
                <option value="">Seleccione una opción</option>
                <?php foreach ($mediosPago as $medio): ?>
                  <option value="<?= htmlspecialchars($medio['metodoPago']) ?>"
                    <?= ($formaPago == $medio['metodoPago']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>
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
                  value="<?php echo $subtotal ?? ''; ?>" readonly>
          </div>

          <div class="mb-2">
            <label for="ivaTotal" class="form-label fw-bold">IVA</label>
            <input type="text" id="ivaTotal" name="ivaTotal" class="form-control text-end" 
                  value="<?php echo $ivaTotal ?? ''; ?>" readonly>
          </div>

          <div class="mb-2">
            <label for="retenciones" class="form-label fw-bold">Retenciones</label>
            <input type="text" id="retenciones" name="retenciones" class="form-control text-end" 
                  value="<?php echo $retenciones ?? ''; ?>" readonly>
          </div>

          <div class="mb-2">
            <label for="valorTotal" class="form-label fw-bold">Valor Total</label>
            <input type="text" id="valorTotal" name="valorTotal" 
                  class="form-control text-end fw-bold border-2 border-primary" 
                  value="<?php echo $valorTotal ?? ''; ?>" readonly>
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
                <td><?php echo $usuario['identificacion']; ?></td>
                <td><?php echo $usuario['nombre']; ?></td>
                <td><?php echo $usuario['fecha']; ?></td>
                <td><?php echo $usuario['consecutivo']; ?></td>
                <td><?php echo $usuario['formaPago']; ?></td>
                <td><?php echo $usuario['subtotal']; ?></td>
                <td><?php echo $usuario['ivaTotal']; ?></td>
                <td><?php echo $usuario['retenciones']; ?></td>
                <td><?php echo $usuario['valorTotal']; ?></td>
                <td><?php echo $usuario['observaciones']; ?></td>
                <td>
                  <form action="" method="post">
                    <input type="hidden" name="txtId" value="<?php echo $usuario['id']; ?>" >
                    <input type="hidden" name="identificacion" value="<?php echo $usuario['identificacion']; ?>" >
                    <input type="hidden" name="nombre" value="<?php echo $usuario['nombre']; ?>" >
                    <input type="hidden" name="fecha" value="<?php echo $usuario['fecha']; ?>" >
                    <input type="hidden" name="consecutivo" value="<?php echo $usuario['consecutivo']; ?>" >
                    <input type="hidden" name="formaPago" value="<?php echo $usuario['formaPago']; ?>" >
                    <input type="hidden" name="subtotal" value="<?php echo $usuario['subtotal']; ?>" >
                    <input type="hidden" name="ivaTotal" value="<?php echo $usuario['ivaTotal']; ?>" >
                    <input type="hidden" name="retenciones" value="<?php echo $usuario['retenciones']; ?>" >
                    <input type="hidden" name="valorTotal" value="<?php echo $usuario['valorTotal']; ?>" >
                    <input type="hidden" name="observaciones" value="<?php echo $usuario['observaciones']; ?>" >
                    
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
        
        <script>
        window.addEventListener('DOMContentLoaded', function() {
            fetch(window.location.pathname + "?get_consecutivo=1")
                .then(response => response.json())
                .then(data => {
                    document.getElementById('consecutivo').value = data.consecutivo;
                })
                .catch(error => console.error('Error al obtener consecutivo:', error));
        });
        // Buscar el cliente solo si es tipo "cliente"
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

        // Buscar por nombre (cuando escriba al menos 3 caracteres)
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

        // Escucha todos los cambios en la tabla (delegación de eventos)
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
              }
            });
          }
        });

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

          // Escucha cambios en cantidad o precio
          document.querySelector("#product-table").addEventListener("input", function (event) {
            if (event.target.classList.contains("quantity") || event.target.classList.contains("unit-price")) {
              calcularValores();
            }
          });

          // Agregar nueva fila
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

              // Suponiendo que el botón se agrega en la última celda
              const lastCell = newRow.lastElementChild;
              lastCell.appendChild(btn);
            }

            tableBody.appendChild(newRow);
          };

          // Agregar botón “–” a la primera fila existente
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

        // Script para alternar botones y manejar cancelar
        document.addEventListener("DOMContentLoaded", function() {
          const id = document.getElementById("txtId").value;
          const btnAgregar = document.getElementById("btnAgregar");
          const btnModificar = document.getElementById("btnModificar");
          const btnEliminar = document.getElementById("btnEliminar");
          const btnCancelar = document.getElementById("btnCancelar");
          const form = document.getElementById("formFacturaVentas");

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
            document.getElementById("consecutivo").value = "";
            document.getElementById("formaPago").value = "";
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
                  ? "Se actualizarán los datos de esta cuenta contable."
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