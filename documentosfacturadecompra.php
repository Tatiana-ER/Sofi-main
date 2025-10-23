<?php
include("connection.php");

$conn = new connection();
$pdo = $conn->connect();

// Obtener el siguiente consecutivo 
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(consecutivo) AS ultimo FROM facturac");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoConsecutivo = $row['ultimo'] ?? 0;
    $nuevoConsecutivo = $ultimoConsecutivo + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}

// registro de datos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    $consecutivo = $_POST['consecutivo'] ?? null;
    $numeroFactura = $_POST['numeroFactura'] ?? null;
    $fecha = $_POST['fecha'] ?? null;
    $identificacion = $_POST['identificacion'] ?? null;
    $nombre = $_POST['nombre'] ?? null;
    $formaPago = $_POST['forma-pago'] ?? null;
    $subtotal = $_POST['subtotal'] ?? 0;
    $ivaTotal = $_POST['ivaTotal'] ?? 0;
    $retenciones = $_POST['retenciones'] ?? 0;
    $valorTotal = $_POST['valorTotal'] ?? 0;
    $observaciones = $_POST['observaciones'] ?? '';

    $productosJSON = $_POST['productosJSON'] ?? '[]';
    $productos = json_decode($productosJSON, true);

    if ($consecutivo && $numeroFactura && $fecha && $identificacion) {
        try {
            // Insertar factura
            $stmt = $pdo->prepare("INSERT INTO facturac (identificacion, nombre, fecha, consecutivo, numeroFactura, formaPago, subtotal, ivaTotal, retenciones, valorTotal, observaciones)
                                   VALUES (:identificacion, :nombre, :fecha, :consecutivo, :numeroFactura, :formaPago, :subtotal, :ivaTotal, :retenciones, :valorTotal, :observaciones)");
            $stmt->execute([
                ':identificacion' => $identificacion,
                ':nombre' => $nombre,
                ':fecha' => $fecha,
                ':consecutivo' => $consecutivo,
                ':numeroFactura' => $numeroFactura,
                ':formaPago' => $formaPago,
                ':subtotal' => $subtotal,
                ':ivaTotal' => $ivaTotal,
                ':retenciones' => $retenciones,
                ':valorTotal' => $valorTotal,
                ':observaciones' => $observaciones
            ]);

            // Obtener ID insertado
            $facturaId = $pdo->lastInsertId();

            // Insertar productos
            foreach ($productos as $p) {
                $stmtDetalle = $pdo->prepare("INSERT INTO detallefacturac (factura_id, codigoProducto, nombreProducto, cantidad, precioUnitario, iva, valorTotal)
                                              VALUES (:factura_id, :codigoProducto, :nombreProducto, :cantidad, :precioUnitario, :iva, :valorTotal)");
                $stmtDetalle->execute([
                    ':factura_id' => $facturaId,
                    ':codigoProducto' => $p['codigoProducto'],
                    ':nombreProducto' => $p['nombreProducto'],
                    ':cantidad' => $p['cantidad'],
                    ':precioUnitario' => $p['precioUnitario'],
                    ':iva' => $p['iva'],
                    ':valorTotal' => $p['valorTotal']
                ]);
            }

            echo "<script>alert('Factura y productos registrados exitosamente'); window.location.href='dashboard.php';</script>";

        } catch (PDOException $e) {
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Faltan datos requeridos');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['identificacion'])) {
    $identificacion = $_POST['identificacion'];

    // Consultamos solo si el tipoTercero es 'proveedor'
    $stmt = $pdo->prepare("SELECT nombres, apellidos FROM catalogosterceros WHERE cedula = :cedula AND tipoTercero = 'proveedor'");
    $stmt->bindParam(':cedula', $identificacion, PDO::PARAM_INT);
    $stmt->execute();
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($proveedor) {
        echo json_encode(["nombre" => $proveedor['nombres'] . " " . $proveedor['apellidos']]);
    } else {
        echo json_encode(["nombre" => "No encontrado o no es un proveedor"]);
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
          <h2>FACTURA DE COMPRA </h2>
          <p>Para crear una nueva factura de compra diligencie los campos a continuación:</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>
        <form method="POST" action="">        
          <div class="mb-3">
            <label for="identificacion" class="form-label">Identificación del Proveedor (NIT o CC)*</label>
            <input type="number" name="identificacion" class="form-control" id="identificacion" placeholder="" required>
          </div>
          <div class="mb-3">
            <label for="exampleFormControlInput1" class="form-label">Nombre del Proveedor</label>
            <input type="text" name="nombre" class="form-control" id="nombre" placeholder="" readonly>
          </div>
          <div class="mb-3">
            <label for="fecha" class="form-label">Fecha de documento</label>
            <input type="date" name="fecha" class="form-control" id="fecha" placeholder="" required>
          </div>
          <div class="mb-3">
            <label for="consecutivo" class="form-label">Consecutivo</label>
            <input type="text" name="consecutivo" class="form-control" id="consecutivo" placeholder="">
          </div>
          <div class="mb-3">
            <label for="numeroFactura" class="form-label">Numero de factura</label>
            <input type="text" name="numeroFactura" class="form-control" id="numeroFactura" placeholder="">
          </div>
          <div>
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
                  <td><input type="text" name="nombreProducto" id="nombreProducto" placeholder="Nombre del producto"></td>
                  <td><input type="number" name="cantidad" id="cantidad" class="quantity" placeholder="Cantidad"></td>
                  <td><input type="number" name="precio" id="precio" class="unit-price" placeholder="Precio Unitario"></td>
                  <td><input type="number" name="iva" id="iva" class="iva" placeholder="IVA"></td>
                  <td><input type="number" name="precioTotal" id="precioTotal" placeholder="Valor Total" readonly></td>
                  <td><button class="add-row" onclick="addRow()">+</button></td>
                </tr>
              </tbody>
            </table>
          </div><br>

          <div class="form-group">
              <label for="forma-pago">FORMA DE PAGO</label>
              <select type="text" id="forma-pago" name="forma-pago">
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
                  <input type="text" id="subtotal" class="subtotal" readonly>
              </div>
              <div class="form-group">
                  <label for="iva">IVA</label>
                  <input type="text" id="ivaTotal" class="ivaTotal" readonly>
              </div>
              <div class="form-group">
                  <label for="retenciones">RETENCIONES</label>
                  <input type="text" id="retenciones" class="retenciones" readonly>
              </div>
              <div class="form-group">
                  <label for="valor-total">VALOR TOTAL</label>
                  <input type="text" name="valorTotal" id="valorTotal" class="valor-total" readonly>
              </div>
          </div>

          <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones</label>
            <input type="text" name="observaciones" class="form-control" id="observaciones" placeholder="">
          </div>

          <br>
          <button value="btnAgregar" type="submit" class="btn btn-primary"  name="accion" >Agregar</button>
        </form>   
                 
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                fetch(window.location.pathname + "?get_consecutivo=1")
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('consecutivo').value = data.consecutivo;
                    })
                    .catch(error => console.error('Error al obtener consecutivo:', error));
            });
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById("identificacion").addEventListener("input", function () {
                    let cedula = this.value;

                    if (cedula.length > 0) {
                        fetch("", {  // Hacemos la petición al mismo archivo PHP
                            method: "POST",
                            body: new URLSearchParams({ identificacion: cedula }),
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
            
            document.addEventListener("DOMContentLoaded", function () {
            function calcularValores() {
                let subtotal = 0;
                let ivaTotal = 0;

                document.querySelectorAll("#product-table tr").forEach(row => {
                    let cantidadField = row.querySelector(".quantity");
                    let precioField = row.querySelector(".unit-price");
                    let ivaField = row.querySelector(".iva");
                    let precioTotalField = row.querySelector("#precioTotal");

                    let cantidad = parseFloat(cantidadField?.value) || 0;
                    let precio = parseFloat(precioField?.value) || 0;

                    // Calcular precio total del producto (cantidad * precio unitario)
                    let precioTotal = cantidad * precio;
                    precioTotalField.value = precioTotal.toFixed(2);

                    // Calcular IVA (19% del precio unitario por la cantidad)
                    let iva = precioTotal * 0.19;
                    ivaField.value = iva.toFixed(2);

                    // Sumar al subtotal general
                    subtotal += precioTotal;
                    ivaTotal += iva;
                });

                // Actualizar los valores de los totales
                document.querySelector("#subtotal").value = subtotal.toFixed(2);
                document.querySelector("#ivaTotal").value = ivaTotal.toFixed(2);

                let valorTotal = subtotal + ivaTotal;
                document.querySelector("#valorTotal").value = valorTotal.toFixed(2);
            }

            // Event listener para recalcular cuando se ingresen valores
            document.querySelector("#product-table").addEventListener("input", function (event) {
                    if (event.target.classList.contains("quantity") || event.target.classList.contains("unit-price")) {
                        calcularValores();
                    }
                });

                // Función para agregar una nueva fila con eventos de cálculo
                window.addRow = function () {
                    const tableBody = document.getElementById("product-table");
                    const lastRow = tableBody.lastElementChild;
                    const newRow = lastRow.cloneNode(true);

                    // Limpiar los valores de la nueva fila
                    newRow.querySelectorAll("input").forEach(input => input.value = "");

                    // Agregar la nueva fila a la tabla
                    tableBody.appendChild(newRow);
                };
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