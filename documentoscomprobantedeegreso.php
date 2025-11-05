<?php
include("connection.php");
 
$conn = new connection();
$pdo = $conn->connect();
 
if (isset($_GET['get_consecutivo'])) {
    $stmt = $pdo->query("SELECT MAX(consecutivo) AS ultimo FROM doccomprobanteegreso");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ultimoConsecutivo = $row['ultimo'] ?? 0;
    $nuevoConsecutivo = $ultimoConsecutivo + 1;
    echo json_encode(['consecutivo' => $nuevoConsecutivo]);
    exit;
}
 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === 'btnAgregar') {
    try {
        $fecha = $_POST['fecha'];
        $consecutivo = $_POST['consecutivo'];
        $identificacion = $_POST['identificacion'];
        $nombre = $_POST['nombre'];
        $formaPago = $_POST['formaPago'];
        $valorTotal = $_POST['valorTotal'];
        $observaciones = $_POST['observaciones'];
 
        // Insertar encabezado
        $stmt = $pdo->prepare("INSERT INTO doccomprobanteegreso (consecutivo, fecha, identificacion, nombreProveedor, formaPago, valorTotal, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$consecutivo, $fecha, $identificacion, $nombre, $formaPago, $valorTotal, $observaciones]);
 
        $reciboId = $pdo->lastInsertId();
 
        // Detalles de las facturas (recolectar desde JS idealmente como array de inputs ocultos)
        if (isset($_POST['numeroFactura']) && is_array($_POST['numeroFactura'])) {
            foreach ($_POST['numeroFactura'] as $i => $factura) {
                $fechaVenc = $_POST['fechaVencimiento'][$i] ?? null;
                $valor = $_POST['valor'][$i] ?? 0;
 
                $stmt = $pdo->prepare("INSERT INTO detalle_docrecibodecaja (recibo_id, numeroFactura, fechaVencimiento, valor) VALUES (?, ?, ?, ?)");
                $stmt->execute([$reciboId, $factura, $fechaVenc, $valor]);
            }
        }
 
        echo "<script>alert('Comprobante registrado correctamente'); window.location.href='dashboard.php';</script>";
 
    } catch (PDOException $e) {
        echo "<script>alert('Error al registrar: " . $e->getMessage() . "');</script>";
    }
}
 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['identificacion'])) {
  $identificacion = $_POST['identificacion'];
 
  // Consultar nombre del proveedor
  $stmt = $pdo->prepare("SELECT nombres, apellidos FROM catalogosterceros WHERE cedula = :cedula AND tipoTercero = 'proveedor'");
  $stmt->bindParam(':cedula', $identificacion, PDO::PARAM_INT);
  $stmt->execute();
  $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
 
  $nombreCompleto = $cliente ? $cliente['nombres'] . " " . $cliente['apellidos'] : "No encontrado o no es un cliente";
 
  // Obtener última factura registrada
  $stmt = $pdo->prepare("SELECT numeroFactura, valorTotal FROM facturac WHERE identificacion = :identificacion ORDER BY id DESC LIMIT 1");
  $stmt->bindParam(':identificacion', $identificacion, PDO::PARAM_INT);
  $stmt->execute();
  $factura = $stmt->fetch(PDO::FETCH_ASSOC);
 
  echo json_encode([
      "nombre" => $nombreCompleto,
      "numeroFactura" => $factura['numeroFactura'] ?? "",
      "valorTotal" => $factura['valorTotal'] ?? ""
  ]);
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
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i%22 rel="stylesheet">
 
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
      text-align: left;
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
          <h2>COMPROBANTE DE EGRESO</h2>
          <p>Para crear un nuevo comprobante de egreso diligencie los campos a continuación:</p>
          <p>(Los campos marcados con * son obligatorios)</p>
        </div>
       
        <form method="POST" action="">
          <div class="mb-3">
            <label for="fecha" class="form-label">Fecha de documento*</label>
            <input type="date" name="fecha" class="form-control" id="fecha" placeholder="" required>
          </div>
          <div class="mb-3">
            <label for="consecutivo" class="form-label">Consecutivo</label>
            <input type="text" name="consecutivo" class="form-control" id="consecutivo" placeholder="">
          </div>
          <div class="mb-3">
            <label for="identificacion" class="form-label">Identificación del proveedor (NIT o CC)*</label>
            <input type="number" name="identificacion" class="form-control" id="identificacion" placeholder="" required>
          </div>
          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del proveedor</label>
            <input type="text" name="nombre" class="form-control" id="nombre" placeholder="">
          </div>
 
          <div>
            <table>
              <thead>
                <tr>
                  <th>Número de factura </th>
                  <th>Fecha de vencimiento</th>
                  <th>Valor</th>
                  <th>¿Seleccionar?</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="product-table">
                <tr>
                  <td><input type="text" placeholder="Número de factura"></td>
                  <td><input type="text" placeholder="Fecha de vencimiento"></td>
                  <td><input type="number" class="quantity" placeholder="Valor"></td>
                  <td><input type="checkbox" class="" placeholder=""></td>
                  <td><button class="add-row" onclick="addRow()">+</button></td>
                </tr>
              </tbody>
            </table>
          </div><br>
 
          <div class="totals">
            <div class="form-group">
                <label for="valor-total">VALOR TOTAL</label>
                <input type="text" id="valorTotal" class="valorTSotal" readonly>
            </div>
          </div>
 
          <div class="form-group">
              <label for="forma-pago">FORMA DE PAGO</label>
              <select type="text" id="formaPago" name="formaPago">
                  <option value="">Seleccione una opción</option>
                  <?php foreach ($mediosPago as $medio): ?>
                      <option value="<?= htmlspecialchars($medio['metodoPago']) ?>">
                          <?= htmlspecialchars($medio['metodoPago']) ?> - <?= htmlspecialchars($medio['cuentaContable']) ?>
                      </option>
                  <?php endforeach; ?>
              </select>
          </div><br><br>
 
          <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones</label>
            <input type="text" name="observaciones" class="form-control" id="exampleFormControlInput1" placeholder="">
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
        // Buscar el cliente solo si es tipo "cliente"
        document.getElementById("identificacion").addEventListener("input", function () {
            let identificacion = this.value;
 
            if (identificacion.length > 0) {
                fetch("", {
                    method: "POST",
                    body: new URLSearchParams({ identificacion: identificacion }),
                    headers: { "Content-Type": "application/x-www-form-urlencoded" }
                })
                .then(response => response.json())
                .then(data => {
                    // Mostrar nombre
                    document.getElementById("nombre").value = data.nombre;
 
                    // Insertar factura en la primera fila
                    const fila = document.querySelector("#product-table tr");
                    if (fila) {
                        fila.cells[0].querySelector("input").value = data.numeroFactura || "";
                        fila.cells[2].querySelector("input").value = data.valorTotal || "";
                    }
 
                    // Mostrar valor total
                    document.getElementById("valorTotal").value = data.valorTotal || "";
                })
                .catch(error => console.error("Error en la consulta:", error));
            } else {
                document.getElementById("nombre").value = "";
                document.getElementById("valorTotal").value = "";
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