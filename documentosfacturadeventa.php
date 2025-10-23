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

      echo "<script>alert('Factura y productos registrados exitosamente'); window.location.href='dashboard.php';</script>";

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

        // Enlazamos los parámetros 

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

        // Ejecutamos la sentencia
        $sentencia->execute();

        // Opcional: Redirigir o mostrar mensaje de éxito
        echo "<script>alert('Datos actualizados correctamente');</script>";

    break;

    case "btnEliminar":

      $sentencia = $pdo->prepare("DELETE FROM facturav WHERE id = :id");
      $sentencia->bindParam(':id', $txtId);
      $sentencia->execute();


    break;

}

  $sentencia= $pdo->prepare("SELECT * FROM `facturav` WHERE 1");
  $sentencia->execute();
  $lista=$sentencia->fetchALL(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['identificacion'])) {
    $identificacion = $_POST['identificacion'];

    $stmt = $pdo->prepare("SELECT nombres, apellidos FROM catalogosterceros WHERE cedula = :cedula AND tipoTercero = 'cliente'");
    $stmt->bindParam(':cedula', $identificacion, PDO::PARAM_INT);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        echo json_encode(["nombre" => $cliente['nombres'] . " " . $cliente['apellidos']]);
    } else {
        echo json_encode(["nombre" => "No encontrado o no es un cliente"]);
    }
    exit; // Detenemos la ejecución para evitar que el HTML se mezcle con el JSON
}

// Consultar producto
if (isset($_POST['codigoProducto'])) {
  $codigoProducto = $_POST['codigoProducto'];

  $stmt = $pdo->prepare("SELECT descripcionProducto FROM productoinventarios WHERE codigoProducto = :codigoProducto");
  $stmt->bindParam(':codigoProducto', $codigoProducto, PDO::PARAM_STR);
  $stmt->execute();
  $producto = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($producto) {
      echo json_encode(["nombreProducto" => $producto['descripcionProducto']]);
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
        
        <form action="" method="post">
          <div>
            <label for="id" class="form-label">ID:</label>
            <input type="text" class="form-control" value="<?php echo $txtId;?>" id="txtId" name="txtId" readonly>
          </div><br>
          <div class="mb-3">
            <label for="identificacion" class="form-label">Identificación del Cliente (NIT o CC)*</label>
            <input type="number" name="identificacion" value="<?php echo $identificacion;?>" class="form-control" id="identificacion" placeholder="" required>
          </div>
          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre cliente</label>
            <input type="text" name="nombre" value="<?php echo $nombre;?>" class="form-control" id="nombre" placeholder="" readonly>
          <div class="mb-3">
            <label for="fecha" class="form-label">Fecha de documento</label>
            <input type="date" name="fecha" value="<?php echo $fecha;?>" class="form-control" id="fecha" placeholder="" required>
          </div>
          <div class="mb-3">
            <label for="consecutivo" class="form-label">Consecutivo</label>
            <input type="text" name="consecutivo" value="<?php echo $consecutivo;?>" class="form-control" id="consecutivo" placeholder="">
          </div>
          <div>
        </form>
        <table>
            <thead>
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
              <tr>
                <td><input type="text" name="codigoProducto" id="codigoProducto" placeholder="Código del producto"></td>
                <td><input type="text" name="nombreProducto" id="nombreProducto" placeholder="Nombre del producto" readonly></td>
                <td><input type="number" name="cantidad" id="cantidad" class="quantity" placeholder="Cantidad"></td>
                <td><input type="number" name="precio" id="precio" class="unit-price" placeholder="Precio Unitario"></td>
                <td><input type="number" name="iva" id="iva" class="iva" placeholder="IVA"></td>
                <td><input type="number" name="precioTotal" id="precioTotal" placeholder="Valor Total" ></td>
                <td><button onclick="addRow()">+</button></td>
              </tr>
            </tbody>
          </table>
        </div><br>

        <div class="form-group">
            <label for="forma-pago">FORMA DE PAGO</label>
            <select type="text" id="formaPago" name="formaPago" value="<?php echo $formaPago;?>">
                <option value="">Seleccione una opción</option>
                <?php foreach ($mediosPago as $medio): ?>
                    <option value="<?= htmlspecialchars($medio['metodoPago']) ?>">
                        <?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div><br><br>
  
        <div class="totals">
            <div class="form-group">
                <label for="subtotal">SUBTOTAL</label>
                <input type="text" id="subtotal" name="subtotal" class="subtotal" readonly>
            </div>
            <div class="form-group">
                <label for="iva">IVA</label>
                <input type="text" id="ivaTotal" name="ivaTotal" class="ivaTotal" readonly>
            </div>
            <div class="form-group">
                <label for="retenciones">RETENCIONES</label>
                <input type="text" id="retenciones" name="retenciones"class="retenciones" readonly>
            </div>
            <div class="form-group">
                <label for="valor-total">VALOR TOTAL</label>
                <input type="text" name="valorTotal" id="valorTotal" class="valor-total" readonly>
            </div>
        </div>

        <div class="mb-3">
          <label for="observaciones" class="form-label">Observaciones</label>
          <input type="text" name="observaciones" value="<?php echo $observaciones;?>" class="form-control" id="observaciones" placeholder="">
        </div>

        <br>
        <button value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Agregar</button>
        <button value="btnModificar" type="submit" class="btn btn-primary"  name="accion" >Modificar</button>
        <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>

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
              <tbody id="product-table">
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
                  <input type="submit" value="Editar" name="accion">
                  <button value="btnEliminar" type="submit" class="btn btn-primary"  name="accion" >Eliminar</button>
                  </form>

                  </td>

                </tr>
              <?php } 
              ?>
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
        document.getElementById("identificacion").addEventListener("input", function() {
            let identificacion = this.value;

            if (identificacion.length > 0) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ identificacion: identificacion }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("nombre").value = data.nombre;
                })
                .catch(error => console.error("Error en la consulta:", error));
            } else {
                document.getElementById("nombre").value = "";
            }
        });

        // Buscar la descripción del producto por código
        document.getElementById("codigoProducto").addEventListener("input", function() {
            let codigo = this.value;

            if (codigo.length > 0) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ codigoProducto: codigo }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("nombreProducto").value = data.nombreProducto;
                })
                .catch(error => console.error("Error en la consulta:", error));
            } else {
                document.getElementById("nombreProducto").value = "";
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const cantidadInput = document.getElementById('cantidad');
            const precioInput = document.getElementById('precio');
            const ivaInput = document.getElementById('iva');
            const precioTotalInput = document.getElementById('precioTotal');

            const subtotalInput = document.getElementById('subtotal');
            const ivaTotalInput = document.getElementById('ivaTotal');
            const retencionesInput = document.getElementById('retenciones');
            const valorTotalInput = document.getElementById('valorTotal');

            function calcularValores() {
                const cantidad = parseFloat(cantidadInput.value) || 0;
                const precio = parseFloat(precioInput.value) || 0;

                const subtotal = cantidad * precio;
                const iva = subtotal * 0.19;
                const total = subtotal + iva;

                ivaInput.value = iva.toFixed(2);
                precioTotalInput.value = total.toFixed(2);

                // Actualizar totales generales (solo una fila)
                subtotalInput.value = subtotal.toFixed(2);
                ivaTotalInput.value = iva.toFixed(2);

                const retenciones = 0; // Puedes ajustar si aplicas retención
                retencionesInput.value = retenciones.toFixed(2);

                valorTotalInput.value = (subtotal + iva - retenciones).toFixed(2);
            }

            cantidadInput.addEventListener('input', calcularValores);
            precioInput.addEventListener('input', calcularValores);
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